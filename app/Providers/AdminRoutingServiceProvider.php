<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Backend\Auth\ForgotPasswordController;
use App\Http\Controllers\Backend\Auth\LoginController;
use App\Http\Controllers\Backend\Auth\OtpVerificationController;
use App\Http\Controllers\Backend\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AdminRoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->registerDynamicAdminRoutes();
    }

    protected function registerDynamicAdminRoutes(): void
    {
        $adminLoginRoute = config('settings.admin_login_route', 'admin/login');

        // ── Guest-only routes (login + OTP + password) ─────────────────────
        Route::middleware(['web', 'guest'])->group(function () use ($adminLoginRoute) {

            // Dynamic login URL (configurable from admin settings).
            Route::get($adminLoginRoute, [LoginController::class, 'showLoginForm'])->name('admin.login');
            Route::post($adminLoginRoute, [LoginController::class, 'login'])
                ->middleware(['recaptcha:login', 'throttle:20,1'])
                ->name('admin.login.submit');

            // Password reset (always at standard path).
            Route::prefix('admin')->name('admin.')->group(function () {
                Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
                Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
                    ->middleware('throttle:20,1')->name('password.reset.submit');
                Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
                Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
                    ->middleware(['recaptcha:forgot_password', 'throttle:20,1'])->name('password.email');
            });
        });

        // ── OTP verification (web middleware only — NOT guest-guarded,
        //    because the user has a pending session but is not yet Auth::check()) ──
        Route::middleware('web')->prefix('admin/otp')->name('admin.otp.')->group(function () {
            Route::get('/', [OtpVerificationController::class, 'showForm'])->name('show');
            Route::post('/', [OtpVerificationController::class, 'verify'])->name('verify')
                ->middleware('throttle:10,1');
            Route::post('/resend', [OtpVerificationController::class, 'resend'])->name('resend')
                ->middleware('throttle:5,1');
            Route::get('/cancel', [OtpVerificationController::class, 'cancel'])->name('cancel');
        });

        // ── Logout ─────────────────────────────────────────────────────────
        Route::middleware('web')
            ->post('/admin/logout/submit', [LoginController::class, 'logout'])
            ->name('admin.logout.submit');
    }
}
