<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only handle exact /admin path
        if ($request->path() === 'admin') {
            // If user is authenticated, let the route handle it normally (dashboard)
            if (Auth::check()) {
                return $next($request);
            }

            // For non-authenticated users:
            $disableRedirect = config('settings.disable_default_admin_redirect', '0') === '1';

            // If redirect is disabled, show 403
            if ($disableRedirect) {
                abort(403, __('Unauthorized access'));
            }

            // Otherwise redirect to login (using the admin.login route which points to the custom path)
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
