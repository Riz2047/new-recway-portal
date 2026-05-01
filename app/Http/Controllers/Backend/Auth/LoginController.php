<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\DemoAppService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    public function __construct(private readonly DemoAppService $demoAppService)
    {
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::ADMIN_DASHBOARD;

    public function showLoginForm()
    {
        $path = request()->path();
        $isStaff = str_starts_with($path, 'staff');
        $isAdmin = str_starts_with($path, 'admin');

        if (Auth::guard('web')->check()) {
            if ($isStaff) {
                return redirect()->route('staff.dashboard');
            }
            return redirect()->route('admin.dashboard');
        }

        $this->demoAppService->maybeSetDemoLocaleToEnByDefault();

        $email = app()->environment('local') ? 'superadmin@example.com' : '';
        $password = app()->environment('local') ? '12345678' : '';

        $roleType = $isStaff ? 'staff' : 'admin';
        return view('backend.auth.login')->with(compact('email', 'password', 'roleType'));
    }

    public function login(LoginRequest $request): RedirectResponse|Response
    {
        $path = $request->path();
        $isStaff = str_starts_with($path, 'staff');
        $dashboardRoute = $isStaff ? 'staff.dashboard' : 'admin.dashboard';

        if (Auth::guard('web')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
            $this->demoAppService->maybeSetDemoLocaleToEnByDefault();
            session()->flash('success', 'Successfully Logged in!');
            return redirect()->route($dashboardRoute);
        }

        if (Auth::guard('web')->attempt(['username' => $request->email, 'password' => $request->password], $request->remember)) {
            $this->demoAppService->maybeSetDemoLocaleToEnByDefault();
            session()->flash('success', 'Successfully Logged in!');
            return redirect()->route($dashboardRoute);
        }

        return $this->sendFailedLoginResponse($request);
    }

    public function logout(): RedirectResponse
    {
        $path = request()->path();
        $isStaff = str_starts_with($path, 'staff');
        Auth::guard('web')->logout();
        return redirect()->route($isStaff ? 'staff.login' : 'admin.login');
    }
}
