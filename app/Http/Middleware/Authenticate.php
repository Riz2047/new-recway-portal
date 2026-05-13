<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // Staff routes → staff login
            if ($request->is('staff/*') || $request->is('staff')) {
                return route('staff.login');
            }

            // Customer routes → customer login (base URL)
            if ($request->is('customer/*') || $request->is('customer')) {
                return route('customer.login');
            }

            // Admin routes → admin login (with optional disable flag)
            if ($request->is('admin/*') || $request->is('admin')) {
                $disableRedirect = config('settings.disable_default_admin_redirect', '0') === '1';

                if ($disableRedirect) {
                    abort(403, 'Unauthorized access');
                }

                return route('admin.login');
            }

            // Frontend fallback
            if (app()->routesAreCached() || \Illuminate\Support\Facades\Route::has('login')) {
                return route('login');
            }

            return route('admin.login');
        }

        return null;
    }
}
