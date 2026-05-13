<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    public function showLoginForm(): \Illuminate\View\View|RedirectResponse
    {
        // Already fully authenticated as customer
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($user->hasRole('Customer')) {
                return redirect()->route('customer.dashboard');
            }
            // Admin/staff authenticated — send them to admin dashboard
            return redirect()->route('admin.dashboard');
        }

        // Pending OTP session — redirect to OTP form
        if ($this->otpService->hasPendingSession()) {
            $panel = session(OtpService::SESSION_KEY_PANEL, 'admin');
            if ($panel === 'customer') {
                return redirect()->route('customer.otp.show');
            }
        }

        return view('customer.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->resolveUser($request->input('email'));

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('These credentials do not match our records.')]);
        }

        if (! $user->hasRole('Customer')) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('These credentials do not match our records.')]);
        }

        // OTP is always mandatory for the customer portal (matches old system behaviour).
        $this->otpService->generateAndSend($user, 'customer');

        return redirect()->route('customer.otp.show')
            ->with('status', __('A verification code has been sent to :email', ['email' => $user->email]));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->otpService->clearSession();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }

    private function resolveUser(string $emailOrUsername): ?User
    {
        return User::where('email', $emailOrUsername)->first()
            ?? User::where('username', $emailOrUsername)->first();
    }
}
