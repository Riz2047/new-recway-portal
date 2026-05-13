<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpVerificationController extends Controller
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    public function showForm(): \Illuminate\View\View|RedirectResponse
    {
        if (! $this->otpService->hasPendingSession()) {
            return redirect()->route('customer.login');
        }

        if (Auth::guard('web')->check()) {
            return redirect()->route('customer.dashboard');
        }

        $email = session(OtpService::SESSION_KEY_EMAIL, '');
        $maskedEmail = $this->maskEmail($email);
        $cooldownRemaining = $this->otpService->resendCooldownRemaining();
        $panel = 'customer';

        return view('customer.auth.otp-verify', compact('maskedEmail', 'cooldownRemaining', 'panel'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'min:4', 'max:10'],
        ]);

        if (! $this->otpService->hasPendingSession()) {
            return redirect()->route('customer.login')
                ->with('error', __('Session expired. Please log in again.'));
        }

        $result = $this->otpService->verify($request->input('otp', ''));

        if (! $result['success']) {
            if (! $this->otpService->hasPendingSession()) {
                return redirect()->route('customer.login')
                    ->with('error', $result['error'] ?? __('Please log in again.'));
            }

            return back()->withErrors(['otp' => $result['error']]);
        }

        /** @var \App\Models\User $user */
        $user = $result['user'];

        $request->session()->regenerate();
        Auth::guard('web')->login($user, false);
        session()->forget('url.intended');

        return redirect()->route('customer.dashboard')
            ->with('success', __('Successfully logged in!'));
    }

    public function resend(): RedirectResponse
    {
        if (! $this->otpService->hasPendingSession()) {
            return redirect()->route('customer.login');
        }

        $sent = $this->otpService->resend();

        return redirect()->route('customer.otp.show')->with(
            $sent ? 'status' : 'error',
            $sent
                ? __('A new verification code has been sent to your email.')
                : __('Please wait before requesting another code.')
        );
    }

    public function cancel(): RedirectResponse
    {
        $this->otpService->clearSession();

        return redirect()->route('customer.login');
    }

    private function maskEmail(string $email): string
    {
        if (empty($email) || ! str_contains($email, '@')) {
            return '***@***';
        }

        [$local, $domain] = explode('@', $email, 2);
        $maskedLocal = substr($local, 0, min(2, strlen($local))) . str_repeat('*', max(0, strlen($local) - 2));

        return $maskedLocal . '@' . $domain;
    }
}
