<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - World Class Organization
|--------------------------------------------------------------------------
|
| World-class API route organization with versioning, proper middleware,
| and RESTful conventions.
|
*/

// API Version 2 Routes
Route::prefix('v2')->middleware(['auth:sanctum', 'throttle:api'])->name('api.v2.')->group(function () {
    
    // Authentication
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', 'Api\V2\AuthController@login')->withoutMiddleware('auth:sanctum');
        Route::post('/logout', 'Api\V2\AuthController@logout');
        Route::get('/user', 'Api\V2\AuthController@user');
    });
    
    // Dashboards
    Route::prefix('dashboards')->name('dashboards.')->group(function () {
        Route::get('/paramedis', [\App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'index']);
        Route::get('/paramedis/jaspel', [\App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getJaspel']);
        Route::get('/paramedis/attendance', [\App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getAttendance']);
        Route::get('/paramedis/schedules', [\App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getSchedules']);
        
        Route::get('/dokter', [\App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController::class, 'index']);
        Route::get('/nonparamedis', [\App\Http\Controllers\Api\V2\Dashboards\NonParamedisDashboardController::class, 'index']);
    });
    
    // Jaspel
    Route::prefix('jaspel')->name('jaspel.')->group(function () {
        Route::get('/mobile-data', 'Api\V2\Jaspel\JaspelController@getMobileJaspelData');
        Route::get('/summary', 'Api\V2\Jaspel\JaspelController@getSummary');
        Route::get('/details/{id}', 'Api\V2\Jaspel\JaspelController@getDetails');
    });
    
    // Jadwal Jaga (Schedules)
    Route::prefix('jadwal-jaga')->name('jadwal.')->group(function () {
        Route::get('/', 'Api\V2\JadwalJagaController@index');
        Route::get('/user/{userId}', 'Api\V2\JadwalJagaController@getUserSchedules');
        Route::get('/current', 'Api\V2\JadwalJagaController@current');
        Route::post('/validate-checkin', 'Api\V2\JadwalJagaController@validateCheckin');
        Route::get('/today', 'Api\V2\JadwalJagaController@today');
        Route::get('/week', 'Api\V2\JadwalJagaController@week');
        Route::get('/duration', 'Api\V2\JadwalJagaController@duration');
        Route::get('/monthly', 'Api\V2\JadwalJagaController@getMonthlySchedules');
    });
    
    // Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::post('/checkin', 'Api\V2\AttendanceController@checkIn');
        Route::post('/checkout', 'Api\V2\AttendanceController@checkOut');
        Route::get('/status', 'Api\V2\AttendanceController@getStatus');
        Route::get('/history', 'Api\V2\AttendanceController@getHistory');
        Route::get('/summary', 'Api\V2\AttendanceController@getSummary');
    });
    
    // Work Locations
    Route::get('/locations/work-locations', 'Api\V2\LocationController@getWorkLocations')
        ->withoutMiddleware('auth:sanctum');
    
    // Petugas
    Route::prefix('petugas')->name('petugas.')->group(function () {
        Route::apiResource('pasien', 'Api\V2\Petugas\PasienController');
        Route::apiResource('tindakan', 'Api\V2\Petugas\TindakanController');
        Route::get('/dashboard', 'Api\V2\Petugas\DashboardController@index');
    });
    
    // Bendahara
    Route::prefix('bendahara')->middleware('role:bendahara')->name('bendahara.')->group(function () {
        Route::get('/dashboard-stats', 'Api\V2\Bendahara\DashboardController@getStats');
        Route::get('/financial-overview', 'Api\V2\Bendahara\FinancialController@getOverview');
        Route::post('/generate-report', 'Api\V2\Bendahara\ReportController@generate');
        Route::get('/validation-queue', 'Api\V2\Bendahara\ValidationController@getQueue');
        Route::post('/bulk-validation', 'Api\V2\Bendahara\ValidationController@bulkValidate');
        Route::get('/cash-flow-analysis', 'Api\V2\Bendahara\CashFlowController@analyze');
        Route::get('/budget-tracking', 'Api\V2\Bendahara\BudgetController@track');
    });
    
    // Admin
    Route::prefix('admin')->middleware('role:admin')->name('admin.')->group(function () {
        Route::get('/dashboard-stats', 'Api\V2\Admin\DashboardController@getStats');
        Route::apiResource('users', 'Api\V2\Admin\UserController');
        Route::apiResource('roles', 'Api\V2\Admin\RoleController');
        Route::get('/activity-logs', 'Api\V2\Admin\ActivityLogController@index');
        Route::get('/system-health', 'Api\V2\Admin\SystemHealthController@check');
    });
    
    // Mobile Dashboard
    Route::prefix('mobile')->name('mobile.')->group(function () {
        Route::get('/jaspel-summary', 'Api\V2\Mobile\JaspelController@getSummary');
        Route::get('/dashboard/{role}', 'Api\V2\Mobile\DashboardController@getByRole');
    });
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::post('/generate', 'Api\V2\ReportController@generate');
        Route::get('/templates', 'Api\V2\ReportController@getTemplates');
        Route::get('/download/{report}', 'Api\V2\ReportController@download');
    });
});

// API Version 1 Routes (Legacy - to be deprecated)
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->name('api.v1.')->group(function () {
    // Keep legacy routes here for backward compatibility
});