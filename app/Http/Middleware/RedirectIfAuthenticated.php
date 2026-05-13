<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard('web')->check()) {
            // Send already-authenticated users to the correct dashboard
            // based on which portal URL they are visiting.
            if ($request->is('staff/*') || $request->is('staff')) {
                return redirect()->route('staff.dashboard');
            }

            return redirect(RouteServiceProvider::ADMIN_DASHBOARD);
        }

        if (Auth::guard($guard)->check()) {
            return redirect(RouteServiceProvider::HOME);
        }

        return $next($request);
    }
}
