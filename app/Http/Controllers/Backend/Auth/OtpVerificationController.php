<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the second step of the two-factor authentication flow.
 *
 * Routes (admin and staff variants are mirrored):
 *   GET  admin/otp  → showForm()
 *   POST admin/otp  → verify()
 *   POST admin/otp/resend → resend()
 */
class OtpVerificationController extends Controller
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    // -------------------------------------------------------------------------
    // Show the OTP input form
    // -------------------------------------------------------------------------

    public function showForm(Request $request): \Illuminate\View\View|RedirectResponse
    {
        // If no pending session exists, redirect back to login.
        if (! $this->otpService->hasPendingSession()) {
            return $this->redirectToLogin($request);
        }

        // Already authenticated — go to dashboard.
        if (Auth::guard('web')->check()) {
            return $this->redirectToDashboard($request);
        }

        $email = session(OtpService::SESSION_KEY_EMAIL, '');
        $cooldownRemaining = $this->otpService->resendCooldownRemaining();
        $maskedEmail = $this->maskEmail($email);
        $panel = session(OtpService::SESSION_KEY_PANEL, 'admin');

        return view('backend.auth.otp-verify', compact(
            'maskedEmail',
            'cooldownRemaining',
            'panel'
        ));
    }

    // -------------------------------------------------------------------------
    // Verify the submitted code
    // -------------------------------------------------------------------------

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'min:4', 'max:10'],
        ]);

        if (! $this->otpService->hasPendingSession()) {
            return $this->redirectToLogin($request)
                ->with('error', __('Session expired. Please log in again.'));
        }

        $result = $this->otpService->verify($request->input('otp', ''));

        if (! $result['success']) {
            // If no session left (expired / exhausted), force back to login.
            if (! $this->otpService->hasPendingSession()) {
                return $this->redirectToLogin($request)
                    ->with('error', $result['error'] ?? __('Please log in again.'));
            }

            return back()->withErrors(['otp' => $result['error']]);
        }

        /** @var \App\Models\User $user */
        $user = $result['user'];
        $panel = $result['panel'] ?? 'admin';

        // Regenerate session to prevent fixation attacks.
        $request->session()->regenerate();

        Auth::guard('web')->login($user, false);

        $dashboardRoute = $panel === 'staff' ? 'staff.dashboard' : 'admin.dashboard';

        // Always redirect directly — never use redirect()->intended() here because
        // a stale admin intended-URL in the session would send staff users to an
        // admin route they cannot access.
        session()->forget('url.intended');

        return redirect()->route($dashboardRoute)
            ->with('success', __('Successfully logged in!'));
    }

    // -------------------------------------------------------------------------
    // Resend OTP
    // -------------------------------------------------------------------------

    public function resend(Request $request): RedirectResponse
    {
        if (! $this->otpService->hasPendingSession()) {
            return $this->redirectToLogin($request);
        }

        $sent = $this->otpService->resend();
        $panel = session(OtpService::SESSION_KEY_PANEL, 'admin');
        $route = $panel === 'staff' ? 'staff.otp.show' : 'admin.otp.show';

        return redirect()->route($route)->with(
            $sent ? 'status' : 'error',
            $sent
                ? __('A new verification code has been sent to your email.')
                : __('Please wait before requesting another code.')
        );
    }

    // -------------------------------------------------------------------------
    // Cancel — clears pending session and returns to login
    // -------------------------------------------------------------------------

    public function cancel(Request $request): RedirectResponse
    {
        $this->otpService->clearSession();
        return $this->redirectToLogin($request);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function redirectToLogin(Request $request): RedirectResponse
    {
        $isStaff = str_contains($request->path(), 'staff');
        return redirect()->route($isStaff ? 'staff.login' : 'admin.login');
    }

    private function redirectToDashboard(Request $request): RedirectResponse
    {
        $isStaff = str_contains($request->path(), 'staff');
        return redirect()->route($isStaff ? 'staff.dashboard' : 'admin.dashboard');
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
