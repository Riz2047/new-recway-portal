<?php

declare(strict_types=1);

use App\Http\Controllers\Backend\ActionLogController;
use App\Http\Controllers\Backend\Auth\ScreenshotGeneratorLoginController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\EmailTemplateController;
use App\Http\Controllers\Backend\LocaleController;
use App\Http\Controllers\Backend\MediaController;
use App\Http\Controllers\Backend\ModuleController;
use App\Http\Controllers\Backend\PermissionController;
use App\Http\Controllers\Backend\PostController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\ServiceCategoryController;
use App\Http\Controllers\Backend\ServiceTypeController;
use App\Http\Controllers\Backend\StatusController;
use App\Http\Controllers\Backend\PlaceController;
use App\Http\Controllers\Backend\TermController;
use App\Http\Controllers\Backend\TranslationController;
use App\Http\Controllers\Backend\UserLoginAsController;
use App\Http\Controllers\Backend\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * Admin routes.
 */
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth', 'role:Admin']], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('roles', RoleController::class);
    Route::delete('roles/delete/bulk-delete', [RoleController::class, 'bulkDelete'])->name('roles.bulk-delete');

    // Permissions Routes.
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->name('permissions.show');

    // Modules Routes.
    // Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
    // Route::post('/modules/toggle-status/{module}', [ModuleController::class, 'toggleStatus'])->name('modules.toggle-status');
    // Route::post('/modules/upload', [ModuleController::class, 'store'])->name('modules.store');
    // Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.delete');

    // Settings Routes.
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');

    // Translation Routes.
    Route::get('/translations', [TranslationController::class, 'index'])->name('translations.index');
    Route::post('/translations', [TranslationController::class, 'update'])->name('translations.update');
    Route::post('/translations/create', [TranslationController::class, 'create'])->name('translations.create');

    // Login as & Switch back.
    Route::resource('users', UserController::class);
    Route::delete('users/delete/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete');
    Route::get('users/{id}/login-as', [UserLoginAsController::class, 'loginAs'])->name('users.login-as');
    Route::post('users/switch-back', [UserLoginAsController::class, 'switchBack'])->name('users.switch-back');

    // Admins Routes.
    Route::resource('admins', App\Http\Controllers\Backend\AdminController::class);
    Route::delete('admins/delete/bulk-delete', [App\Http\Controllers\Backend\AdminController::class, 'bulkDelete'])->name('admins.bulk-delete');

    // Staff Routes.
    Route::resource('staff', App\Http\Controllers\Backend\StaffController::class)->except(['show']);
    Route::delete('staff/delete/bulk-delete', [App\Http\Controllers\Backend\StaffController::class, 'bulkDelete'])->name('staff.bulk-delete');

    // Staff Category Routes.
    Route::resource('staff-category', App\Http\Controllers\Backend\StaffCategoryController::class);
    Route::delete('staff-category/delete/bulk-delete', [App\Http\Controllers\Backend\StaffCategoryController::class, 'bulkDelete'])->name('staff-category.bulk-delete');

    // Customer Routes.
    Route::resource('customers', App\Http\Controllers\Backend\CustomerController::class);
    Route::get('customers/get-departments', [App\Http\Controllers\Backend\CustomerController::class, 'getDepartments'])->name('customers.get-departments');
    Route::get('customers/get-parent-data', [App\Http\Controllers\Backend\CustomerController::class, 'getParentCustomerData'])->name('customers.get-parent-data');
    Route::get('customers/{id}/tab-data', [App\Http\Controllers\Backend\CustomerController::class, 'getTabData'])->name('customers.tab-data');

    // Service Category Routes.
    Route::resource('service-category', ServiceCategoryController::class);
    Route::delete('service-category/delete/bulk-delete', [ServiceCategoryController::class, 'bulkDelete'])->name('service-category.bulk-delete');

    // Status Routes (nested under service categories)
    Route::prefix('service-category/{serviceCategory}/status')->name('status.')->group(function () {
        Route::get('/', [StatusController::class, 'index'])->name('index');
        Route::get('/create', [StatusController::class, 'create'])->name('create');
        Route::post('/', [StatusController::class, 'store'])->name('store');
        Route::get('/{status}/edit', [StatusController::class, 'edit'])->name('edit');
        Route::put('/{status}', [StatusController::class, 'update'])->name('update');
        Route::delete('/{status}', [StatusController::class, 'destroy'])->name('destroy');
    });

    // Service Type Routes
    Route::prefix('service-types')->name('service-types.')->group(function () {
        Route::get('/category/{categoryId}', [ServiceTypeController::class, 'index'])->name('index');
        Route::post('/', [ServiceTypeController::class, 'store'])->name('store');
        Route::get('/customers', [ServiceTypeController::class, 'getCustomers'])->name('customers');
        Route::put('/{id}', [ServiceTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [ServiceTypeController::class, 'destroy'])->name('destroy');
    });

    // Place Routes.
    Route::resource('place', PlaceController::class);
    Route::delete('place/delete/bulk-delete', [PlaceController::class, 'bulkDelete'])->name('place.bulk-delete');

    Route::resource('email-templates', EmailTemplateController::class)->except(['show']);

    // Action Log Routes.
    Route::get('/action-log', [ActionLogController::class, 'index'])->name('actionlog.index');

    // Posts/Pages Routes - Dynamic post types.
    // Route::get('/posts/{postType?}', [PostController::class, 'index'])->name('posts.index');
    // Route::get('/posts/{postType}/create', [PostController::class, 'create'])->name('posts.create');
    // Route::post('/posts/{postType}', [PostController::class, 'store'])->name('posts.store');
    // Route::get('/posts/{postType}/{post}', [PostController::class, 'show'])->name('posts.show');
    // Route::get('/posts/{postType}/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    // Route::put('/posts/{postType}/{post}', [PostController::class, 'update'])->name('posts.update');
    // Route::delete('/posts/{postType}/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    // Route::delete('/posts/{postType}/delete/bulk-delete', [PostController::class, 'bulkDelete'])->name('posts.bulk-delete');

    // Terms Routes (Categories, Tags, etc.).
    // Route::get('/terms/{taxonomy}', [TermController::class, 'index'])->name('terms.index');
    // Route::get('/terms/{taxonomy}/{term}/edit', [TermController::class, 'edit'])->name('terms.edit');
    // Route::post('/terms/{taxonomy}', [TermController::class, 'store'])->name('terms.store');
    // Route::put('/terms/{taxonomy}/{term}', [TermController::class, 'update'])->name('terms.update');
    // Route::delete('/terms/{taxonomy}/{term}', [TermController::class, 'destroy'])->name('terms.destroy');
    // Route::delete('/terms/{taxonomy}/delete/bulk-delete', [TermController::class, 'bulkDelete'])->name('terms.bulk-delete');

    // Media Routes.
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('/api', [MediaController::class, 'api'])->name('api');
        Route::post('/', [MediaController::class, 'store'])->name('store')->middleware('check.upload.limits');
        Route::get('/upload-limits', [MediaController::class, 'getUploadLimits'])->name('upload-limits');
        Route::delete('/{id}', [MediaController::class, 'destroy'])->name('destroy');
        Route::delete('/', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
    });

    // Editor Upload Route.
    Route::post('/editor/upload', [App\Http\Controllers\Backend\EditorController::class, 'upload'])->name('editor.upload');

    // AI Content Generation Routes.
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/providers', [App\Http\Controllers\Backend\AiContentController::class, 'getProviders'])->name('providers');
        Route::post('/generate-content', [App\Http\Controllers\Backend\AiContentController::class, 'generateContent'])->name('generate-content');
    });

});

/**
 * Staff routes (share controllers/views with admin).
 */
Route::group(['prefix' => 'staff', 'as' => 'staff.', 'middleware' => ['auth', 'role:Manager,User,Moderator,Manager with statistics']], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('roles', RoleController::class);
    Route::delete('roles/delete/bulk-delete', [RoleController::class, 'bulkDelete'])->name('roles.bulk-delete');
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->name('permissions.show');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
    Route::get('/translations', [TranslationController::class, 'index'])->name('translations.index');
    Route::post('/translations', [TranslationController::class, 'update'])->name('translations.update');
    Route::post('/translations/create', [TranslationController::class, 'create'])->name('translations.create');
    Route::resource('users', UserController::class);
    Route::delete('users/delete/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete');
    Route::get('users/{id}/login-as', [UserLoginAsController::class, 'loginAs'])->name('users.login-as');
    Route::post('users/switch-back', [UserLoginAsController::class, 'switchBack'])->name('users.switch-back');
    Route::resource('admins', App\Http\Controllers\Backend\AdminController::class);
    Route::delete('admins/delete/bulk-delete', [App\Http\Controllers\Backend\AdminController::class, 'bulkDelete'])->name('admins.bulk-delete');
    Route::resource('staff', App\Http\Controllers\Backend\StaffController::class)->except(['show']);
    Route::delete('staff/delete/bulk-delete', [App\Http\Controllers\Backend\StaffController::class, 'bulkDelete'])->name('staff.bulk-delete');
    Route::resource('staff-category', App\Http\Controllers\Backend\StaffCategoryController::class);
    Route::delete('staff-category/delete/bulk-delete', [App\Http\Controllers\Backend\StaffCategoryController::class, 'bulkDelete'])->name('staff-category.bulk-delete');
    Route::resource('customers', App\Http\Controllers\Backend\CustomerController::class);
    Route::get('customers/get-departments', [App\Http\Controllers\Backend\CustomerController::class, 'getDepartments'])->name('customers.get-departments');
    Route::get('customers/get-parent-data', [App\Http\Controllers\Backend\CustomerController::class, 'getParentCustomerData'])->name('customers.get-parent-data');
    Route::get('customers/{id}/tab-data', [App\Http\Controllers\Backend\CustomerController::class, 'getTabData'])->name('customers.tab-data');
    Route::resource('service-category', ServiceCategoryController::class);
    Route::delete('service-category/delete/bulk-delete', [ServiceCategoryController::class, 'bulkDelete'])->name('service-category.bulk-delete');
    Route::prefix('service-category/{serviceCategory}/status')->name('status.')->group(function () {
        Route::get('/', [StatusController::class, 'index'])->name('index');
        Route::get('/create', [StatusController::class, 'create'])->name('create');
        Route::post('/', [StatusController::class, 'store'])->name('store');
        Route::get('/{status}/edit', [StatusController::class, 'edit'])->name('edit');
        Route::put('/{status}', [StatusController::class, 'update'])->name('update');
        Route::delete('/{status}', [StatusController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('service-types')->name('service-types.')->group(function () {
        Route::get('/category/{categoryId}', [ServiceTypeController::class, 'index'])->name('index');
        Route::post('/', [ServiceTypeController::class, 'store'])->name('store');
        Route::get('/customers', [ServiceTypeController::class, 'getCustomers'])->name('customers');
        Route::put('/{id}', [ServiceTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [ServiceTypeController::class, 'destroy'])->name('destroy');
    });
    Route::resource('place', PlaceController::class);
    Route::delete('place/delete/bulk-delete', [PlaceController::class, 'bulkDelete'])->name('place.bulk-delete');

    Route::resource('email-templates', EmailTemplateController::class)->except(['show']);

    Route::get('/action-log', [ActionLogController::class, 'index'])->name('actionlog.index');
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('/api', [MediaController::class, 'api'])->name('api');
        Route::post('/', [MediaController::class, 'store'])->name('store')->middleware('check.upload.limits');
        Route::get('/upload-limits', [MediaController::class, 'getUploadLimits'])->name('upload-limits');
        Route::delete('/{id}', [MediaController::class, 'destroy'])->name('destroy');
        Route::delete('/', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
    });
    Route::post('/editor/upload', [App\Http\Controllers\Backend\EditorController::class, 'upload'])->name('editor.upload');
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/providers', [App\Http\Controllers\Backend\AiContentController::class, 'getProviders'])->name('providers');
        Route::post('/generate-content', [App\Http\Controllers\Backend\AiContentController::class, 'generateContent'])->name('generate-content');
    });
});

/**
 * Profile routes.
 */
Route::group(['prefix' => 'profile', 'as' => 'profile.', 'middleware' => ['auth']], function () {
    Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/update', [ProfileController::class, 'update'])->name('update');
    Route::put('/update-additional', [ProfileController::class, 'updateAdditional'])->name('update.additional');
});

Route::get('/locale/{lang}', [LocaleController::class, 'switch'])->name('locale.switch');
Route::get('/screenshot-login/{email}', [ScreenshotGeneratorLoginController::class, 'login'])->middleware('web')->name('screenshot.login');
Route::get('/demo-preview', fn () => view('demo.preview'))->name('demo.preview');
