<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Accepts roles as variadic params so both formats work:
     *   'role:Admin,Manager'          → $roles = ['Admin', 'Manager']
     *   'role:Manager with statistics' → $roles = ['Manager with statistics']
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if ($user) {
            foreach ($roles as $role) {
                if ($user->hasRole(trim($role))) {
                    return $next($request);
                }
            }
        }

        abort(403, __('Unauthorized'));
    }
}
