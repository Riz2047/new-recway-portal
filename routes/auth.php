<?php

declare(strict_types=1);

/**
 * Frontend auth imports.
 */
use App\Http\Controllers\Auth\RegisterController as UserRegisterController;
use App\Http\Controllers\Auth\LoginController as UserLoginController;
use App\Http\Controllers\Auth\ForgotPasswordController as UserForgotPasswordController;
use App\Http\Controllers\Auth\VerificationController as UserVerificationController;
use App\Http\Controllers\Auth\ResetPasswordController as UserResetPasswordController;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Authentication related routes.
|
*/

// Public User authentication routes with reCAPTCHA middleware.
Route::group(['middleware' => 'guest'], function () {
    // Registration Routes.
    Route::get('register', [UserRegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [UserRegisterController::class, 'register'])
        ->middleware(['recaptcha:registration', 'throttle:20,1']);

    // Login Routes.
    Route::get('login', [UserLoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [UserLoginController::class, 'login'])
        ->middleware(['recaptcha:login', 'throttle:20,1']);

    // Password Reset Routes.
    Route::get('password/reset', [UserForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email', [UserForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware(['recaptcha:forgot_password', 'throttle:20,1'])->name('password.email');
    Route::get('password/reset/{token}', [UserResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [UserResetPasswordController::class, 'reset'])
        ->middleware('throttle:20,1')->name('password.update');

    // Email Verification Routes.
    Route::get('email/verify', [UserVerificationController::class, 'show'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [UserVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('email/resend', [UserVerificationController::class, 'resend'])
        ->middleware('throttle:20,1')->name('verification.resend');
});

// User Logout Route.
Route::post('logout', [UserLoginController::class, 'logout'])->name('logout');

// Admin authentication routes are now handled by AdminRoutingServiceProvider
// to support dynamic admin login URLs
