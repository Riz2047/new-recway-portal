<?php

declare(strict_types=1);

use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\CompanyUserController;
use App\Http\Controllers\Customer\HistoryController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\ReviewerController;
use App\Http\Controllers\Customer\StatisticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
|
| All routes here require the user to be authenticated as a Customer.
| Middleware: customer.auth (checks auth + Customer role), otp.verified
|
*/

Route::group([
    'prefix'     => 'customer',
    'as'         => 'customer.',
    'middleware' => ['customer.auth', 'otp.verified'],
], function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Orders (Phase 3 + 4)
    Route::get('/orders',                [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create',                  [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders',                        [OrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/services',               [OrderController::class, 'getServices'])->name('orders.services');
    Route::post('/orders/fetch-form',             [OrderController::class, 'fetchForm'])->name('orders.fetch-form');
    Route::get('/orders/{id}',                    [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{id}/edit',               [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{id}',                    [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{id}/cancel',                 [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{id}/change-status',            [OrderController::class, 'changeStatus'])->name('orders.change-status');
    Route::post('/orders/{id}/upload-security-report',   [OrderController::class, 'uploadSecurityReport'])->name('orders.upload-security-report');

    // Phase 6 — Statistics
    Route::get('/statistics',        [StatisticsController::class, 'index'])->name('statistics');
    Route::post('/statistics/data',  [StatisticsController::class, 'data'])->name('statistics.data');
    Route::post('/statistics/export',[StatisticsController::class, 'export'])->name('statistics.export');

    // Phase 7 — History / Archived orders
    Route::get('/history', [HistoryController::class, 'index'])->name('history');

    // Phase 10 — Reviewers
    Route::get('/reviewers',              [ReviewerController::class, 'index'])->name('reviewers.index');
    Route::get('/reviewers/create',       [ReviewerController::class, 'create'])->name('reviewers.create');
    Route::post('/reviewers',             [ReviewerController::class, 'store'])->name('reviewers.store');
    Route::get('/reviewers/{id}/edit',    [ReviewerController::class, 'edit'])->name('reviewers.edit');
    Route::put('/reviewers/{id}',         [ReviewerController::class, 'update'])->name('reviewers.update');
    Route::delete('/reviewers/{id}',      [ReviewerController::class, 'destroy'])->name('reviewers.destroy');

    // Phase 10 — Notifications
    Route::get('/notifications',          [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/list',     [NotificationController::class, 'list'])->name('notifications.list');
    Route::post('/notifications/mark-read',[NotificationController::class, 'markRead'])->name('notifications.mark-read');

    // Phase 9 — Company users management
    Route::get('/company-users',               [CompanyUserController::class, 'index'])->name('company-users');
    Route::post('/company-users/toggle-status',[CompanyUserController::class, 'toggleStatus'])->name('company-users.toggle');
    Route::post('/company-users/update',       [CompanyUserController::class, 'updateStaff'])->name('company-users.update');

    // Phase 8 — Account settings
    Route::get('/account',                [AccountController::class, 'edit'])->name('account');
    Route::put('/account',                [AccountController::class, 'update'])->name('account.update');
    Route::post('/account/billing',       [AccountController::class, 'updateBilling'])->name('account.billing');
    Route::post('/account/email-settings',      [AccountController::class, 'updateEmailSettings'])->name('account.email-settings');
    Route::post('/account/toggle-email-status', [AccountController::class, 'toggleEmailStatus'])->name('account.toggle-email-status');
});
