<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Backend\Auth\ForgotPasswordController;
use App\Http\Controllers\Backend\Auth\LoginController;
use App\Http\Controllers\Backend\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StaffRoutingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerDynamicStaffRoutes();
    }

    /**
     * Register dynamic staff authentication routes.
     */
    protected function registerDynamicStaffRoutes(): void
    {
        $staffLoginRoute = config('settings.staff_login_route', 'staff/login');

        Route::middleware(['web', 'guest'])->group(function () use ($staffLoginRoute) {
            // Dynamic login routes
            Route::get($staffLoginRoute, [LoginController::class, 'showLoginForm'])->name('staff.login');
            Route::post($staffLoginRoute, [LoginController::class, 'login'])
                ->middleware(['recaptcha:login', 'throttle:20,1'])->name('staff.login.submit');

            // Password reset routes (keeping these at standard locations)
            Route::prefix('staff')->name('staff.')->group(function () {
                Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
                Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
                    ->middleware('throttle:20,1')->name('password.reset.submit');
                Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
                Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
                    ->middleware(['recaptcha:forgot_password', 'throttle:20,1'])->name('password.email');
            });
        });

        // Staff logout route (always at the standard location)
        Route::middleware('web')->post('/staff/logout/submit', [LoginController::class, 'logout'])->name('staff.logout.submit');
    }
}
