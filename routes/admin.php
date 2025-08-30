<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| World-class admin route organization with enhanced security,
| activity logging, and proper access control.
|
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])
        ->name('dashboard');
    
    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Settings\UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Settings\UserManagementController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Settings\UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}', [\App\Http\Controllers\Settings\UserManagementController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [\App\Http\Controllers\Settings\UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [\App\Http\Controllers\Settings\UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [\App\Http\Controllers\Settings\UserManagementController::class, 'destroy'])->name('destroy');
        
        // Bulk operations
        Route::post('/bulk-action', [\App\Http\Controllers\Settings\UserManagementController::class, 'bulkAction'])->name('bulk');
    });
    
    // Role & Permission Management - REMOVED: Using Filament RoleResource instead
    // Legacy routes removed to prevent conflict with filament.admin.resources.roles.*
    // All role management now handled by Filament admin panel at /admin/roles
    
    // System Settings - TEMPORARILY DISABLED: Missing SettingsController
    // Route::prefix('settings')->name('settings.')->group(function () {
    //     Route::get('/', 'SettingsController@index')->name('index');
    //     Route::put('/general', 'SettingsController@updateGeneral')->name('general');
    //     Route::put('/security', 'SettingsController@updateSecurity')->name('security');
    //     Route::put('/email', 'SettingsController@updateEmail')->name('email');
    // });
    
    // Reports & Analytics - TEMPORARILY DISABLED: Missing ReportsController
    // Route::prefix('reports')->name('reports.')->group(function () {
    //     Route::get('/', 'ReportsController@index')->name('index');
    //     Route::get('/users', 'ReportsController@users')->name('users');
    //     Route::get('/activity', 'ReportsController@activity')->name('activity');
    //     Route::get('/performance', 'ReportsController@performance')->name('performance');
    //     Route::post('/export', 'ReportsController@export')->name('export');
    // });
    
    // Audit Logs - TEMPORARILY DISABLED: Missing AuditController
    // Route::prefix('audit')->name('audit.')->group(function () {
    //     Route::get('/', 'AuditController@index')->name('index');
    //     Route::get('/{audit}', 'AuditController@show')->name('show');
    // });
    
    // Attendance Recap Detail
    Route::get('/attendance-recap/detail', [\App\Http\Controllers\Admin\AttendanceRecapDetailController::class, 'show'])
        ->name('attendance-recap.detail');
});