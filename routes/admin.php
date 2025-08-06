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

Route::middleware(['auth', 'role:admin', 'log.activity'])->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])
        ->name('dashboard');
    
    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', 'UserManagementController@index')->name('index');
        Route::get('/create', 'UserManagementController@create')->name('create');
        Route::post('/', 'UserManagementController@store')->name('store');
        Route::get('/{user}', 'UserManagementController@show')->name('show');
        Route::get('/{user}/edit', 'UserManagementController@edit')->name('edit');
        Route::put('/{user}', 'UserManagementController@update')->name('update');
        Route::delete('/{user}', 'UserManagementController@destroy')->name('destroy');
        
        // Bulk operations
        Route::post('/bulk-action', 'UserManagementController@bulkAction')->name('bulk');
    });
    
    // Role & Permission Management
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', 'RoleController@index')->name('index');
        Route::get('/create', 'RoleController@create')->name('create');
        Route::post('/', 'RoleController@store')->name('store');
        Route::get('/{role}', 'RoleController@show')->name('show');
        Route::get('/{role}/edit', 'RoleController@edit')->name('edit');
        Route::put('/{role}', 'RoleController@update')->name('update');
        Route::delete('/{role}', 'RoleController@destroy')->name('destroy');
    });
    
    // System Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', 'SettingsController@index')->name('index');
        Route::put('/general', 'SettingsController@updateGeneral')->name('general');
        Route::put('/security', 'SettingsController@updateSecurity')->name('security');
        Route::put('/email', 'SettingsController@updateEmail')->name('email');
    });
    
    // Reports & Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', 'ReportsController@index')->name('index');
        Route::get('/users', 'ReportsController@users')->name('users');
        Route::get('/activity', 'ReportsController@activity')->name('activity');
        Route::get('/performance', 'ReportsController@performance')->name('performance');
        Route::post('/export', 'ReportsController@export')->name('export');
    });
    
    // Audit Logs
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', 'AuditController@index')->name('index');
        Route::get('/{audit}', 'AuditController@show')->name('show');
    });
});