<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController;
use App\Http\Controllers\Api\V2\HospitalLocationController;

/*
|--------------------------------------------------------------------------
| API V2 Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for version 2.
| These routes are loaded by the RouteServiceProvider.
|
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'login']);
    Route::post('/refresh', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'refresh']);
});

// Work locations (public for GPS validation)
Route::get('/locations/work-locations', [\App\Http\Controllers\Api\V2\WorkLocationController::class, 'v2Locations']);
Route::post('/locations/validate-position', [\App\Http\Controllers\Api\V2\WorkLocationController::class, 'validatePosition']);

// Hospital location (public for map display)
Route::get('/hospital/location', [HospitalLocationController::class, 'getLocation']);
Route::get('/hospital/locations', [HospitalLocationController::class, 'getAllLocations']);
Route::get('/hospital/location/{id}', [HospitalLocationController::class, 'getLocationById']);

// Server time (public for time validation)
Route::get('/server-time', function () {
    return response()->json([
        'success' => true,
        'message' => 'Server time retrieved successfully',
        'data' => [
            'current_time' => now()->setTimezone('Asia/Jakarta')->toISOString(),
            'timezone' => 'Asia/Jakarta',
            'timestamp' => now()->timestamp
        ]
    ]);
});

// System information (public)
Route::prefix('system')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is healthy',
            'data' => [
                'status' => 'ok',
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'database' => 'connected',
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    });

    Route::get('/version', function () {
        return response()->json([
            'success' => true,
            'message' => 'API version information',
            'data' => [
                'api_version' => '2.0',
                'laravel_version' => app()->version(),
                'release_date' => '2025-07-15',
                'features' => [
                    'authentication' => '✓',
                    'attendance' => '✓',
                    'dashboards' => '✓',
                    'role_based_access' => '✓',
                    'mobile_optimization' => '✓',
                    'offline_sync' => 'pending',
                    'push_notifications' => 'pending',
                ],
            ],
            'meta' => [
                'version' => '2.0',
                'timestamp' => now()->toISOString(),
                'request_id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        ]);
    });
});

// Protected routes (authentication required)
Route::middleware(['auth:sanctum', App\Http\Middleware\Api\ApiResponseHeadersMiddleware::class])->group(function () {
    
    // Admin API endpoints
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        Route::apiResource('jadwal-jagas', \App\Http\Controllers\Api\V2\Admin\AdminJadwalJagaController::class);
    });
    
    // Authentication endpoints
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'logout']);
        Route::post('/logout-all', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'logoutAll']);
        Route::get('/me', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'me']);
        Route::put('/profile', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'updateProfile']);
        Route::post('/change-password', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'changePassword']);
        
        // Enhanced authentication features
        Route::get('/sessions', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'getSessions']);
        Route::delete('/sessions/{session_id}', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'endSession']);
        
        // Biometric authentication
        Route::prefix('biometric')->group(function () {
            Route::post('/setup', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'setupBiometric']);
            Route::post('/verify', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'verifyBiometric']);
            Route::get('/', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'getBiometrics']);
            Route::delete('/{biometric_type}', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'removeBiometric']);
        });
    });

    // Device management endpoints
    Route::prefix('devices')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'getDevices']);
        Route::post('/register', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'registerDevice']);
        Route::delete('/{device_id}', [App\Http\Controllers\Api\V2\Auth\AuthController::class, 'revokeDevice']);
    });

    // Attendance endpoints with rate limiting
    Route::prefix('attendance')->middleware([App\Http\Middleware\Api\ApiRateLimitMiddleware::class . ':attendance'])->group(function () {
        Route::post('/checkin', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'checkin']);
        Route::post('/checkout', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'checkout']);
        Route::get('/today', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'today']);
        Route::get('/history', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'history']);
        Route::get('/statistics', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'statistics']);
    });

    // Dashboard endpoints
    Route::prefix('dashboards')->group(function () {
        // Paramedis dashboard
        Route::prefix('paramedis')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'index']);
            Route::get('/jaspel', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getJaspel']);
            Route::get('/attendance', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getAttendance']);
            Route::get('/schedules', [App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController::class, 'getSchedules']);
        });
    });
});

// Dokter dashboard - using web session authentication (outside protected routes)
Route::prefix('v2/dashboards/dokter')->middleware(['web'])->group(function () {
    Route::get('/', [DokterDashboardController::class, 'index']);
    Route::get('/jadwal-jaga', [DokterDashboardController::class, 'getJadwalJaga']);
    Route::get('/jaspel', [DokterDashboardController::class, 'getJaspel']);
    Route::get('/tindakan', [DokterDashboardController::class, 'getTindakan']);
    Route::get('/presensi', [DokterDashboardController::class, 'getPresensi']);
    Route::get('/attendance', [DokterDashboardController::class, 'getAttendance']);
    Route::get('/patients', [DokterDashboardController::class, 'getPatients']);
    Route::get('/test', [DokterDashboardController::class, 'test']);
    
    // Schedule endpoints
    Route::get('/schedules', [DokterDashboardController::class, 'schedules']);
    Route::get('/weekly-schedules', [DokterDashboardController::class, 'getWeeklySchedule']);
    Route::get('/igd-schedules', [DokterDashboardController::class, 'getIgdSchedules']);
    
    // Work location endpoints
    Route::post('/refresh-work-location', [DokterDashboardController::class, 'refreshWorkLocation']);
    Route::get('/work-location/status', [DokterDashboardController::class, 'getWorkLocationStatus']);
    Route::post('/work-location/check-and-assign', [DokterDashboardController::class, 'checkAndAssignWorkLocation']);
    
    // Attendance check-in/out endpoints
    Route::post('/checkin', [DokterDashboardController::class, 'checkIn']);
    Route::post('/checkout', [DokterDashboardController::class, 'checkOut']);
    

    
    // Test endpoint for authentication debugging
    Route::get('/auth-test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Basic test endpoint working',
            'data' => [
                'timestamp' => now()->toISOString(),
                'request_method' => request()->method(),
                'request_url' => request()->url(),
                'headers' => request()->headers->all(),
                'user' => auth()->check() ? auth()->user()->only(['id', 'name', 'email']) : null,
            ]
        ]);
    });
    
    // Debug endpoint untuk jadwal jaga
    Route::get('/debug-schedule', [DokterDashboardController::class, 'debugSchedule']);
});
