<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Backend\Auth\ForgotPasswordController;
use App\Http\Controllers\Backend\Auth\LoginController;
use App\Http\Controllers\Backend\Auth\OtpVerificationController;
use App\Http\Controllers\Backend\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StaffRoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->registerDynamicStaffRoutes();
    }

    protected function registerDynamicStaffRoutes(): void
    {
        $staffLoginRoute = config('settings.staff_login_route', 'staff/login');

        // ── Guest-only routes ───────────────────────────────────────────────
        Route::middleware(['web', 'guest'])->group(function () use ($staffLoginRoute) {
            Route::get($staffLoginRoute, [LoginController::class, 'showLoginForm'])->name('staff.login');
            Route::post($staffLoginRoute, [LoginController::class, 'login'])
                ->middleware(['recaptcha:login', 'throttle:20,1'])
                ->name('staff.login.submit');

            Route::prefix('staff')->name('staff.')->group(function () {
                Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
                Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
                    ->middleware('throttle:20,1')->name('password.reset.submit');
                Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
                Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
                    ->middleware(['recaptcha:forgot_password', 'throttle:20,1'])->name('password.email');
            });
        });

        // ── OTP verification (web only — NOT guest-guarded) ─────────────────
        Route::middleware('web')->prefix('staff/otp')->name('staff.otp.')->group(function () {
            Route::get('/', [OtpVerificationController::class, 'showForm'])->name('show');
            Route::post('/', [OtpVerificationController::class, 'verify'])->name('verify')
                ->middleware('throttle:10,1');
            Route::post('/resend', [OtpVerificationController::class, 'resend'])->name('resend')
                ->middleware('throttle:5,1');
            Route::get('/cancel', [OtpVerificationController::class, 'cancel'])->name('cancel');
        });

        // ── Logout ──────────────────────────────────────────────────────────
        Route::middleware('web')
            ->post('/staff/logout/submit', [LoginController::class, 'logout'])
            ->name('staff.logout.submit');
    }
}
