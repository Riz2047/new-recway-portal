<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Auth\OtpService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to authenticated admin/staff routes when there is a
 * pending OTP session (user has passed credentials but not yet entered the code).
 *
 * This prevents the unlikely but possible edge-case where someone with access
 * to the server session could skip the OTP step by directly navigating to the
 * dashboard. In practice the `auth` middleware already requires Auth::check()
 * which is false during a pending OTP session — so this middleware is an extra
 * safety net used on the dashboard routes.
 */
class RequireOtpVerification
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // If OTP is pending and the user somehow reached a protected route,
        // send them to the verification form.
        if ($this->otpService->hasPendingSession()) {
            $panel = (string) session(OtpService::SESSION_KEY_PANEL, 'admin');

            if ($panel === 'staff') {
                $route = 'staff.otp.show';
            } elseif ($panel === 'customer') {
                $route = 'customer.otp.show';
            } else {
                $route = 'admin.otp.show';
            }

            return redirect()->route($route)
                ->with('status', __('Please complete the two-factor verification first.'));
        }

        return $next($request);
    }
}
