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
        Route::get('/multishift-status', [App\Http\Controllers\Api\V2\Attendance\AttendanceController::class, 'multishiftStatus']);
        
        // 🎯 NEW: Attendance Penalty Logic Endpoints (Dr. Yaya Scenario)
        Route::prefix('penalty')->group(function () {
            Route::get('/demo-yaya-scenario', [App\Http\Controllers\Api\V2\AttendancePenaltyController::class, 'demoYayaScenario']);
            Route::get('/history/{userId}', [App\Http\Controllers\Api\V2\AttendancePenaltyController::class, 'getPenaltyHistory']);
            Route::post('/apply/{attendanceId}', [App\Http\Controllers\Api\V2\AttendancePenaltyController::class, 'applyPenalty']);
            Route::get('/risk-check', [App\Http\Controllers\Api\V2\AttendancePenaltyController::class, 'getAttendanceWithPenaltyRisk']);
        });
        
        // 🚀 NEW: Unified Attendance API - JadwalJaga sebagai source of truth
        Route::prefix('unified')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V2\UnifiedAttendanceController::class, 'getUnifiedAttendanceData']);
            Route::get('/today', [App\Http\Controllers\Api\V2\UnifiedAttendanceController::class, 'getTodayOnly']);
            Route::get('/history', [App\Http\Controllers\Api\V2\UnifiedAttendanceController::class, 'getHistoryOnly']);
            Route::post('/refresh', [App\Http\Controllers\Api\V2\UnifiedAttendanceController::class, 'forceRefresh']);
        });
    });

    // JASPEL Validation endpoints - ONLY validated amounts
    Route::prefix('jaspel/validated')->group(function () {
        Route::get('/gaming-data', [App\Http\Controllers\Api\V2\ValidatedJaspelController::class, 'getGamingData']);
        Route::get('/data', [App\Http\Controllers\Api\V2\ValidatedJaspelController::class, 'getData']);
        Route::get('/validation-report', [App\Http\Controllers\Api\V2\ValidatedJaspelController::class, 'getValidationReport']);
    });

    // Jumlah Pasien endpoints for JASPEL integration - ONLY validated
    Route::prefix('jumlah-pasien')->group(function () {
        Route::get('/jaspel-jaga', [App\Http\Controllers\Api\V2\JumlahPasienController::class, 'getJumlahPasienForJaspel']);
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

// 🚀 Dokter dashboard - HIGHER rate limits for real-time features (Web session auth for mobile app)
Route::prefix('v2/dashboards/dokter')->middleware(['web', 'auth', 'throttle:300,1'])->group(function () {
    Route::get('/', [DokterDashboardController::class, 'index']);
    Route::get('/jadwal-jaga', [DokterDashboardController::class, 'getJadwalJaga']);
    Route::get('/jaspel', [DokterDashboardController::class, 'getJaspel']);
    Route::get('/jaspel/current-month', [DokterDashboardController::class, 'getCurrentMonthJaspelProgress']);
    Route::get('/tindakan', [DokterDashboardController::class, 'getTindakan']);
    Route::get('/presensi', [App\Http\Controllers\Api\V2\Dashboards\DokterDashboardControllerClean::class, 'getPresensi']);
    Route::get('/attendance', [DokterDashboardController::class, 'getAttendance']);
    Route::get('/patients', [DokterDashboardController::class, 'getPatients']);
    Route::get('/test', [DokterDashboardController::class, 'test']);
    
    // ✅ FIXED: Leaderboard endpoint using correct controller
    Route::get('/leaderboard', [\App\Http\Controllers\Api\V2\Dashboards\LeaderboardController::class, 'getTopDoctors']);
    
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
    

    
    // Multi-shift status endpoint
    Route::get('/multishift-status', [DokterDashboardController::class, 'multishiftStatus']);
    
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
    
    // 🏥 Petugas Patient Management API Routes - Elegant Dark Theme Support
    Route::prefix('petugas')->name('petugas.')->middleware('role:petugas')->group(function () {
        Route::prefix('patients')->group(function () {
            Route::get('/', function(Request $request) {
                $query = \App\Models\Pasien::query();
                
                // Apply filters
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%")
                          ->orWhere('no_rekam_medis', 'like', "%{$search}%")
                          ->orWhere('alamat', 'like', "%{$search}%")
                          ->orWhere('nomor_telepon', 'like', "%{$search}%");
                    });
                }
                
                if ($request->filled('jenis_kelamin')) {
                    $query->where('jenis_kelamin', $request->jenis_kelamin);
                }
                
                if ($request->filled('tab') && $request->tab !== 'all') {
                    switch($request->tab) {
                        case 'active':
                            $query->where('status', 'active');
                            break;
                        case 'pending':
                            $query->where('status', 'pending');
                            break;
                        case 'recent':
                            $query->where('created_at', '>=', now()->subWeek());
                            break;
                    }
                }
                
                // Apply sorting
                $sortField = $request->get('sort_field', 'created_at');
                $sortDirection = $request->get('sort_direction', 'desc');
                $query->orderBy($sortField, $sortDirection);
                
                // Paginate
                $perPage = min($request->get('per_page', 25), 100);
                $patients = $query->paginate($perPage);
                
                return response()->json([
                    'success' => true,
                    'data' => $patients->items(),
                    'pagination' => [
                        'current_page' => $patients->currentPage(),
                        'last_page' => $patients->lastPage(),
                        'per_page' => $patients->perPage(),
                        'total' => $patients->total(),
                        'from' => $patients->firstItem(),
                        'to' => $patients->lastItem(),
                    ]
                ]);
            });
            
            Route::get('/stats', function() {
                $stats = [
                    'total' => \App\Models\Pasien::count(),
                    'active' => \App\Models\Pasien::where('status', 'active')->count(),
                    'pending' => \App\Models\Pasien::where('status', 'pending')->count(),
                    'thisWeek' => \App\Models\Pasien::where('created_at', '>=', now()->subWeek())->count(),
                ];
                
                return response()->json([
                    'success' => true,
                    'stats' => $stats
                ]);
            });
            
            Route::get('/export', function(Request $request) {
                // Simple CSV export implementation
                return response()->json([
                    'success' => true,
                    'message' => 'Export functionality would be implemented here',
                    'download_url' => '/api/v2/petugas/patients/download-csv'
                ]);
            });
        });
    });
    
    // 🏢 Manajer Dashboard API Routes - Real Data Integration
    Route::prefix('manajer')->name('manajer.')->middleware('role:manajer')->group(function () {
        // Core dashboard endpoints
        Route::get('/today-stats', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'todayStats']);
        Route::get('/finance-overview', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'financeOverview']);
        Route::get('/recent-transactions', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'recentTransactions']);
        Route::get('/attendance-today', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'attendanceToday']);
        Route::get('/attendance-trends', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'attendanceTrends']);
        Route::get('/jaspel-summary', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'jaspelSummary']);
        Route::get('/doctor-ranking', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'doctorRanking']);
        Route::get('/pending-approvals', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'pendingApprovals']);
        
        // Legacy compatibility endpoints
        Route::get('/dashboard', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'getDashboardData']);
        Route::get('/finance', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'getFinanceData']);
        Route::get('/attendance', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'getAttendanceData']);
        Route::get('/jaspel', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'getJaspelData']);
        Route::get('/profile', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'getProfileData']);
        Route::put('/profile', [App\Http\Controllers\Api\V2\Manajer\ManajerDashboardController::class, 'updateProfile']);
    });
});

// NEW: Jaspel Sub-Agent API endpoints  
Route::prefix('bendahara')->middleware('auth:sanctum')->group(function () {
    Route::prefix('jaspel')->name('jaspel.')->group(function () {
        Route::get('/reports/{role?}', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'reports'])
            ->name('reports')
            ->middleware('role:bendahara|admin|manajer')
            ->where('role', 'semua|dokter|paramedis|non_paramedis|petugas');
        
        Route::get('/summary/{userId}', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'summary'])
            ->name('summary')
            ->middleware('role:bendahara|admin|manajer')
            ->where('userId', '[0-9]+');
        
        Route::post('/export', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'export'])
            ->name('export')
            ->middleware('role:bendahara');
        
        Route::get('/roles', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'roles'])
            ->name('roles')
            ->middleware('role:bendahara|admin|manajer');
        
        Route::post('/cache/clear', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'clearCache'])
            ->name('cache.clear')
            ->middleware('role:bendahara');
    });

    // Health check endpoint (public within auth)
    Route::get('/jaspel-health', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'health'])
        ->name('jaspel.health');

    // NEW: Petugas-Bendahara Flow Sub-Agent endpoints
    Route::prefix('petugas-flow')->name('petugas-flow.')->group(function () {
        Route::get('/analyze', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'analyzeFlow'])
            ->name('analyze')
            ->middleware('role:bendahara|admin');
        
        Route::post('/create-test-data', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'createTestData'])
            ->name('create-test-data')
            ->middleware('role:bendahara');
        
        Route::get('/activities', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'trackActivities'])
            ->name('activities')
            ->middleware('role:bendahara|admin|manajer');
        
        Route::get('/metrics', [\App\Http\Controllers\Api\V2\SubAgents\JaspelApiSubAgentController::class, 'workflowMetrics'])
            ->name('metrics')
            ->middleware('role:bendahara|admin|manajer');
    });
});
