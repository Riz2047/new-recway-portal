<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Backend\Auth\ForgotPasswordController;
use App\Http\Controllers\Backend\Auth\LoginController;
use App\Http\Controllers\Backend\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AdminRoutingServiceProvider extends ServiceProvider
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
        $this->registerDynamicAdminRoutes();
    }

    /**
     * Register dynamic admin authentication routes.
     */
    protected function registerDynamicAdminRoutes(): void
    {
        $adminLoginRoute = config('settings.admin_login_route', 'admin/login');

        Route::middleware(['web', 'guest'])->group(function () use ($adminLoginRoute) {
            // Dynamic login routes
            Route::get($adminLoginRoute, [LoginController::class, 'showLoginForm'])->name('admin.login');
            Route::post($adminLoginRoute, [LoginController::class, 'login'])
                ->middleware(['recaptcha:login', 'throttle:20,1'])->name('admin.login.submit');

            // Password reset routes (keeping these at standard locations)
            Route::prefix('admin')->name('admin.')->group(function () {
                Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
                Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
                    ->middleware('throttle:20,1')->name('password.reset.submit');
                Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
                Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
                    ->middleware(['recaptcha:forgot_password', 'throttle:20,1'])->name('password.email');
            });
        });

        // Admin logout route (always at the standard location)
        Route::middleware('web')->post('/admin/logout/submit', [LoginController::class, 'logout'])->name('admin.logout.submit');
    }
}
