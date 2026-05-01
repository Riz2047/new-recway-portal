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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = Auth::user();
        $roleList = array_map('trim', explode(',', $roles));
        $hasAnyRole = false;
        if ($user) {
            foreach ($roleList as $role) {
                if ($user->hasRole($role)) {
                    $hasAnyRole = true;
                    break;
                }
            }
        }
        if (!$hasAnyRole) {
            abort(403, __('Unauthorized'));
        }
        return $next($request);
    }
}
