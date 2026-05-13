<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Mail\OtpMail;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Handles email-based OTP (One-Time Password) for two-factor authentication.
 *
 * Flow:
 *  1. After successful credential check → generateAndSend($user)
 *  2. User submits OTP code on verify form → verify($email, $code)
 *  3. Success → caller logs in the user
 *
 * Configuration:
 *  env('ADMIN_2FA_ENABLED', false)  → globally enable/disable 2FA
 *  env('ADMIN_2FA_ROLES', 'Admin')  → comma-separated roles that require 2FA
 */
class OtpService
{
    public const SESSION_KEY_USER = 'otp_pending.user_id';
    public const SESSION_KEY_EMAIL = 'otp_pending.email';
    public const SESSION_KEY_PANEL = 'otp_pending.panel';    // 'admin' | 'staff'
    public const SESSION_KEY_SENT_AT = 'otp_pending.sent_at';

    // Cooldown between resend requests (seconds).
    private const RESEND_COOLDOWN = 60;

    // -------------------------------------------------------------------------
    // Enable check
    // -------------------------------------------------------------------------

    /**
     * Is 2FA enabled globally?
     */
    public function isEnabled(): bool
    {
        return (bool) env('ADMIN_2FA_ENABLED', config('auth.otp_enabled', false));
    }

    /**
     * Does this user require 2FA based on their roles?
     */
    public function requiresOtp(User $user): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $requiredRoles = array_map(
            'trim',
            explode(',', env('ADMIN_2FA_ROLES', 'Admin'))
        );

        return $user->roles()->whereIn('name', $requiredRoles)->exists();
    }

    // -------------------------------------------------------------------------
    // Generate + send
    // -------------------------------------------------------------------------

    /**
     * Generate (or re-use an unexpired) OTP, store it, send the email.
     * Stores the pending-login state in the session.
     */
    public function generateAndSend(User $user, string $panel = 'admin'): void
    {
        $email = $user->email;
        $existing = OtpVerification::findValid($email);

        if ($existing && ! $existing->isExpired() && ! $existing->isExhausted()) {
            $otp = $existing->otp;
        } else {
            // Delete any old records for this email.
            OtpVerification::where('email', $email)->delete();

            $otp = $this->generate();

            OtpVerification::create([
                'email' => $email,
                'otp' => $otp,
                'attempts' => 0,
                'date_time' => now(),
            ]);
        }

        $this->sendEmail($user, $otp);

        // Store pending session state (user is NOT logged in yet).
        session([
            self::SESSION_KEY_USER => $user->id,
            self::SESSION_KEY_EMAIL => $email,
            self::SESSION_KEY_PANEL => $panel,
            self::SESSION_KEY_SENT_AT => now()->toDateTimeString(),
        ]);
    }

    /**
     * Resend the OTP (enforces a cooldown to prevent abuse).
     * Returns true on success, false if cooldown not yet expired.
     */
    public function resend(): bool
    {
        $sentAt = session(self::SESSION_KEY_SENT_AT);
        if ($sentAt) {
            $elapsed = now()->diffInSeconds(\Carbon\Carbon::parse($sentAt));
            if ($elapsed < self::RESEND_COOLDOWN) {
                return false;
            }
        }

        $userId = session(self::SESSION_KEY_USER);
        $user = User::find($userId);

        if (! $user) {
            return false;
        }

        // Delete existing OTP and generate a fresh one.
        OtpVerification::where('email', $user->email)->delete();

        $otp = $this->generate();
        OtpVerification::create([
            'email' => $user->email,
            'otp' => $otp,
            'attempts' => 0,
            'date_time' => now(),
        ]);

        $this->sendEmail($user, $otp);

        session([self::SESSION_KEY_SENT_AT => now()->toDateTimeString()]);

        return true;
    }

    // -------------------------------------------------------------------------
    // Verify
    // -------------------------------------------------------------------------

    /**
     * Validate the submitted OTP code.
     *
     * @return array{success:bool, error?:string, user?:User}
     */
    public function verify(string $submittedCode): array
    {
        $userId = session(self::SESSION_KEY_USER);
        $email = session(self::SESSION_KEY_EMAIL);

        if (! $userId || ! $email) {
            return ['success' => false, 'error' => 'No pending verification session. Please log in again.'];
        }

        $record = OtpVerification::findValid($email);

        if (! $record) {
            $this->clearSession();
            return ['success' => false, 'error' => 'Verification code has expired. Please log in again.'];
        }

        if ($record->isExhausted()) {
            $this->clearSession();
            return ['success' => false, 'error' => 'Too many incorrect attempts. Please log in again.'];
        }

        // Increment attempt counter before checking.
        $record->increment('attempts');

        if (! hash_equals($record->otp, strtoupper(trim($submittedCode)))) {
            $remaining = OtpVerification::MAX_ATTEMPTS - $record->fresh()->attempts;
            return [
                'success' => false,
                'error' => $remaining > 0
                    ? "Incorrect code. {$remaining} attempt(s) remaining."
                    : 'Too many incorrect attempts. Please log in again.',
            ];
        }

        // Code is correct — clean up.
        $record->delete();
        $user = User::find($userId);

        if (! $user) {
            $this->clearSession();
            return ['success' => false, 'error' => 'User not found. Please log in again.'];
        }

        $panel = session(self::SESSION_KEY_PANEL, 'admin');
        $this->clearSession();

        return ['success' => true, 'user' => $user, 'panel' => $panel];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function hasPendingSession(): bool
    {
        return session()->has(self::SESSION_KEY_USER) && session()->has(self::SESSION_KEY_EMAIL);
    }

    public function clearSession(): void
    {
        session()->forget([
            self::SESSION_KEY_USER,
            self::SESSION_KEY_EMAIL,
            self::SESSION_KEY_PANEL,
            self::SESSION_KEY_SENT_AT,
        ]);
    }

    /** Seconds until next resend is allowed (0 means can resend now). */
    public function resendCooldownRemaining(): int
    {
        $sentAt = session(self::SESSION_KEY_SENT_AT);
        if (! $sentAt) {
            return 0;
        }
        $elapsed = now()->diffInSeconds(\Carbon\Carbon::parse($sentAt));
        return max(0, self::RESEND_COOLDOWN - (int) $elapsed);
    }

    private function generate(): string
    {
        // 6-char uppercase alphanumeric code, mirrors old system's md5/uniqid approach.
        return strtoupper(Str::random(6));
    }

    private function sendEmail(User $user, string $otp): void
    {
        Mail::to($user->email, $user->name)->send(new OtpMail($otp, $user->name));
    }
}
