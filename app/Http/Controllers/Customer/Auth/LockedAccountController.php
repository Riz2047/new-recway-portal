<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\OtpVerification;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class LockedAccountController extends Controller
{
    /**
     * Step 1 — Show the locked-account recovery start page.
     * The email is passed as a query param when redirected from LoginController.
     */
    public function showReset(Request $request): View|RedirectResponse
    {
        $email = $request->query('email', session('locked_email', ''));

        if ($email) {
            session(['locked_email' => $email]);
        }

        if (! $email || ! LoginAttempt::isAccountLocked($email)) {
            return redirect()->route('customer.login')
                ->with('error', __('Your account is not locked.'));
        }

        return view('customer.auth.locked-account-reset', compact('email'));
    }

    /**
     * Step 2 — Generate OTP and send it to the user's email.
     */
    public function sendMfa(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = $request->input('email');

        if (! LoginAttempt::isAccountLocked($email)) {
            return redirect()->route('customer.login')
                ->with('error', __('Your account is not locked.'));
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return back()->withErrors(['email' => __('No account found with that email address.')]);
        }

        try {
            $otp = (string) random_int(100000, 999999);

            OtpVerification::updateOrCreate(
                ['email' => $email],
                ['otp' => $otp, 'otp_created_at' => now()]
            );

            Mail::send('customer.auth.emails.locked-mfa', [
                'otp'  => $otp,
                'name' => $user->name,
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject(__('Account Recovery Code — :app', ['app' => config('app.name')]));
            });

            session(['locked_email' => $email]);

            Log::info('Locked account MFA code sent.', ['email' => $email]);

            return redirect()->route('customer.locked.verify-mfa')
                ->with('status', __('A verification code has been sent to :email', ['email' => $email]));
        } catch (Exception $e) {
            Log::error('Failed to send locked account MFA.', ['email' => $email, 'error' => $e->getMessage()]);

            return back()->with('error', __('Failed to send the verification code. Please try again.'));
        }
    }

    /**
     * Step 3 — Show the OTP input form.
     */
    public function showMfaVerify(): View|RedirectResponse
    {
        $email = session('locked_email', '');

        if (! $email || ! LoginAttempt::isAccountLocked($email)) {
            return redirect()->route('customer.login');
        }

        return view('customer.auth.verify-locked-mfa', compact('email'));
    }

    /**
     * Step 4 — Verify the OTP.
     */
    public function verifyMfa(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $email = session('locked_email', '');

        if (! $email || ! LoginAttempt::isAccountLocked($email)) {
            return redirect()->route('customer.login');
        }

        $record = OtpVerification::where('email', $email)
            ->where('otp', $request->input('otp'))
            ->first();

        if (! $record) {
            return back()->withErrors(['otp' => __('The verification code is incorrect.')]);
        }

        $record->delete();
        LoginAttempt::verifyMfa($email);

        Log::info('Locked account MFA verified.', ['email' => $email]);

        return redirect()->route('customer.locked.new-password')
            ->with('status', __('Identity verified. Please set a new password.'));
    }

    /**
     * Step 5 — Show the forced password-reset form.
     */
    public function showForcedReset(): View|RedirectResponse
    {
        $email = session('locked_email', '');

        if (! $email || ! LoginAttempt::isMfaVerified($email)) {
            return redirect()->route('customer.locked.verify-mfa')
                ->with('error', __('Please complete identity verification first.'));
        }

        return view('customer.auth.passwords.forced-reset', compact('email'));
    }

    /**
     * Step 6 — Process the forced password reset.
     * Requirements: min 14 chars, uppercase, lowercase, digit, special character.
     */
    public function processForcedReset(Request $request): RedirectResponse
    {
        $email = session('locked_email', '');

        if (! $email || ! LoginAttempt::isMfaVerified($email)) {
            return redirect()->route('customer.locked.verify-mfa')
                ->with('error', __('Please complete identity verification first.'));
        }

        $request->validate([
            'password' => [
                'required',
                'string',
                'min:14',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&#^_\-+=\[\]{}|;:,.<>\/]/',
            ],
        ], [
            'password.min'      => __('Password must be at least 14 characters.'),
            'password.regex'    => __('Password must contain uppercase, lowercase, a number, and a special character.'),
            'password.confirmed' => __('Passwords do not match.'),
        ]);

        $user = User::where('email', $email)->first();
        if (! $user) {
            return redirect()->route('customer.login')
                ->with('error', __('Account not found.'));
        }

        $user->password = bcrypt($request->input('password'));
        $user->save();

        LoginAttempt::resetAttempts($email);
        session()->forget('locked_email');

        Log::info('Locked account password reset completed.', ['email' => $email, 'user_id' => $user->id]);

        return redirect()->route('customer.login')
            ->with('status', __('Your password has been reset. You can now sign in.'));
    }
}
