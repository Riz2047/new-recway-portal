<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\OtpService;
use App\Services\DemoAppService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::ADMIN_DASHBOARD;

    public function __construct(
        private readonly DemoAppService $demoAppService,
        private readonly OtpService $otpService,
    ) {
    }

    // -------------------------------------------------------------------------
    // Show login form
    // -------------------------------------------------------------------------

    public function showLoginForm(): \Illuminate\View\View|RedirectResponse
    {
        $path = request()->path();
        $isStaff = str_starts_with($path, 'staff');

        if (Auth::guard('web')->check()) {
            return redirect()->route($isStaff ? 'staff.dashboard' : 'admin.dashboard');
        }

        // If a pending OTP session exists, redirect to verification instead.
        if ($this->otpService->hasPendingSession()) {
            return redirect()->route($isStaff ? 'staff.otp.show' : 'admin.otp.show');
        }

        $this->demoAppService->maybeSetDemoLocaleToEnByDefault();

        $email = app()->environment('local') ? 'superadmin@example.com' : '';
        $password = app()->environment('local') ? '12345678' : '';
        $roleType = $isStaff ? 'staff' : 'admin';

        return view('backend.auth.login', compact('email', 'password', 'roleType'));
    }

    // -------------------------------------------------------------------------
    // Handle login submission
    // -------------------------------------------------------------------------

    public function login(LoginRequest $request): RedirectResponse|Response
    {
        $path = $request->path();
        $isStaff = str_starts_with($path, 'staff');
        $panel = $isStaff ? 'staff' : 'admin';
        $dashboardRoute = $isStaff ? 'staff.dashboard' : 'admin.dashboard';
        $otpRoute = $isStaff ? 'staff.otp.show' : 'admin.otp.show';

        // Resolve user by email or username.
        $user = $this->resolveUser($request->email);

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->sendFailedLoginResponse($request);
        }

        // If 2FA is required for this user, start the OTP flow.
        if ($this->otpService->requiresOtp($user)) {
            $this->otpService->generateAndSend($user, $panel);

            return redirect()->route($otpRoute)
                ->with('status', __('A verification code has been sent to :email', ['email' => $user->email]));
        }

        // No 2FA needed — log in directly.
        Auth::guard('web')->login($user, (bool) $request->remember);
        $this->demoAppService->maybeSetDemoLocaleToEnByDefault();
        session()->flash('success', 'Successfully Logged in!');

        return redirect()->route($dashboardRoute);
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function logout(): RedirectResponse
    {
        $path = request()->path();
        $isStaff = str_starts_with($path, 'staff');

        $this->otpService->clearSession();
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route($isStaff ? 'staff.login' : 'admin.login');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveUser(string $emailOrUsername): ?User
    {
        return User::where('email', $emailOrUsername)->first()
            ?? User::where('username', $emailOrUsername)->first();
    }
}
