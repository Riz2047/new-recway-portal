<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Customer\Auth\ForgotPasswordController;
use App\Http\Controllers\Customer\Auth\LoginController;
use App\Http\Controllers\Customer\Auth\OtpVerificationController;
use App\Http\Controllers\Customer\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CustomerRoutingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerCustomerAuthRoutes();
    }

    protected function registerCustomerAuthRoutes(): void
    {
        // Base URL — customer login page (no guest guard; controller handles redirect for authenticated users)
        Route::middleware('web')
            ->get('/', [LoginController::class, 'showLoginForm'])
            ->name('customer.login');

        Route::middleware(['web', 'throttle:20,1'])
            ->post('/customer/login', [LoginController::class, 'login'])
            ->name('customer.login.submit');

        // Password reset
        Route::middleware('web')->prefix('customer')->name('customer.')->group(function () {
            Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
            Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
                ->middleware('throttle:20,1')->name('password.email');
            Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
            Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
                ->middleware('throttle:20,1')->name('password.reset.submit');
        });

        // OTP verification (web only — NOT guest-guarded because user has pending session but is not yet Auth::check())
        Route::middleware('web')->prefix('customer/otp')->name('customer.otp.')->group(function () {
            Route::get('/', [OtpVerificationController::class, 'showForm'])->name('show');
            Route::post('/', [OtpVerificationController::class, 'verify'])->name('verify')
                ->middleware('throttle:10,1');
            Route::post('/resend', [OtpVerificationController::class, 'resend'])->name('resend')
                ->middleware('throttle:5,1');
            Route::get('/cancel', [OtpVerificationController::class, 'cancel'])->name('cancel');
        });

        // Logout
        Route::middleware('web')
            ->post('/customer/logout', [LoginController::class, 'logout'])
            ->name('customer.logout');
    }
}
