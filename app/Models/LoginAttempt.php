<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    protected $fillable = [
        'email',
        'attempts',
        'is_locked',
        'password_reset_required',
        'mfa_verified',
        'mfa_type',
        'locked_at',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_at'               => 'datetime',
            'last_attempt_at'         => 'datetime',
            'is_locked'               => 'boolean',
            'password_reset_required' => 'boolean',
            'mfa_verified'            => 'boolean',
        ];
    }

    const MAX_ATTEMPTS = 10;

    public static function recordFailedAttempt(string $email): self
    {
        $record = self::firstOrCreate(
            ['email' => $email],
            ['attempts' => 0]
        );

        $record->increment('attempts');
        $record->last_attempt_at = Carbon::now();
        $record->save();

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->is_locked               = true;
            $record->password_reset_required = true;
            $record->locked_at               = Carbon::now();
            $record->save();
        }

        return $record->fresh();
    }

    public static function isAccountLocked(string $email): bool
    {
        $record = self::where('email', $email)->first();

        return $record && $record->is_locked;
    }

    public static function getRemainingAttempts(string $email): int
    {
        $record = self::where('email', $email)->first();

        return $record
            ? max(0, self::MAX_ATTEMPTS - $record->attempts)
            : self::MAX_ATTEMPTS;
    }

    public static function resetAttempts(string $email): void
    {
        self::where('email', $email)->update([
            'attempts'                => 0,
            'is_locked'               => false,
            'password_reset_required' => false,
            'mfa_verified'            => false,
            'mfa_type'                => null,
            'locked_at'               => null,
        ]);
    }

    public static function verifyMfa(string $email): void
    {
        self::where('email', $email)->update([
            'mfa_verified' => true,
            'mfa_type'     => 'email',
        ]);
    }

    public static function isMfaVerified(string $email): bool
    {
        return (bool) self::where('email', $email)->value('mfa_verified');
    }
}
