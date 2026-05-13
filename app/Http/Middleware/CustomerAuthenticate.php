<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('customer.login');
        }

        if (! Auth::guard('web')->user()->hasRole('Customer')) {
            abort(403, __('Unauthorized'));
        }

        return $next($request);
    }
}
