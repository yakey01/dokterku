<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V3\UnifiedDashboardController;
use App\Http\Controllers\Api\V3\DashboardStatsController;
use App\Http\Controllers\Api\V3\DashboardActionController;
use App\Http\Controllers\Api\V3\DashboardExportController;

/**
 * API Version 3 Routes - Unified Dashboard System
 * 
 * This file defines the new unified dashboard API routes that replace
 * the fragmented dashboard implementations across multiple controllers.
 * 
 * Design Principles:
 * - Single source of truth for dashboard functionality
 * - Role-based access control through middleware
 * - Consistent URL patterns and response structures
 * - Optimized for performance with intelligent caching
 * - RESTful design with clear endpoint purposes
 * 
 * Route Structure:
 * /api/v3/dashboard/* - Main dashboard endpoints
 * /api/v3/dashboard/stats/* - Statistics and analytics
 * /api/v3/dashboard/actions/* - User actions (checkin/checkout)
 * /api/v3/dashboard/export/* - Data export functionality
 */

/*
|--------------------------------------------------------------------------
| API V3 Routes - Unified Dashboard System
|--------------------------------------------------------------------------
|
| These routes replace the fragmented dashboard implementations with a
| unified, role-aware system. All routes require authentication and
| automatically adapt content based on user roles and permissions.
|
*/

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Core Dashboard Routes
    |--------------------------------------------------------------------------
    |
    | Main dashboard endpoints providing comprehensive dashboard data
    | for all user roles through a single, unified interface.
    |
    */
    
    Route::prefix('dashboard')->name('api.v3.dashboard.')->group(function () {
        
        // Main dashboard endpoint - replaces all role-specific dashboard controllers
        Route::get('/', [UnifiedDashboardController::class, 'index'])
            ->name('index')
            ->middleware('throttle:60,1'); // Higher frequency for main dashboard
        
        // User metrics and KPIs
        Route::get('/metrics', [UnifiedDashboardController::class, 'metrics'])
            ->name('metrics');
        
        // Attendance data and status
        Route::get('/attendance', [UnifiedDashboardController::class, 'attendance'])
            ->name('attendance')
            ->middleware('throttle:30,1'); // Real-time data, higher frequency
        
        // Schedule information
        Route::get('/schedule', [UnifiedDashboardController::class, 'schedule'])
            ->name('schedule');
        
        // Financial overview (permission-based access)
        Route::get('/financial', [UnifiedDashboardController::class, 'financial'])
            ->name('financial')
            ->middleware('permission:view_financial_data');
        
        // Management statistics (management roles only)
        Route::get('/management', [UnifiedDashboardController::class, 'management'])
            ->name('management')
            ->middleware('role:admin|manajer|bendahara');
        
        // Quick actions available to user
        Route::get('/quick-actions', [UnifiedDashboardController::class, 'quickActions'])
            ->name('quick-actions');
        
        // Cache management
        Route::post('/refresh', [UnifiedDashboardController::class, 'refresh'])
            ->name('refresh')
            ->middleware('throttle:10,1'); // Limited refresh rate
        
        // System health check
        Route::get('/health', [UnifiedDashboardController::class, 'health'])
            ->name('health')
            ->withoutMiddleware('auth:sanctum'); // Public health check
    });
    
    /*
    |--------------------------------------------------------------------------
    | Dashboard Statistics Routes
    |--------------------------------------------------------------------------
    |
    | Detailed statistics and analytics endpoints for different
    | time periods and data breakdowns.
    |
    */
    
    Route::prefix('dashboard/stats')->name('api.v3.dashboard.stats.')->group(function () {
        
        // Daily statistics
        Route::get('/daily', [DashboardStatsController::class, 'daily'])
            ->name('daily');
        
        // Weekly statistics
        Route::get('/weekly', [DashboardStatsController::class, 'weekly'])
            ->name('weekly');
        
        // Monthly statistics
        Route::get('/monthly', [DashboardStatsController::class, 'monthly'])
            ->name('monthly');
        
        // Custom period statistics
        Route::get('/period', [DashboardStatsController::class, 'period'])
            ->name('period');
        
        // Comparative statistics
        Route::get('/compare', [DashboardStatsController::class, 'compare'])
            ->name('compare');
        
        // Trend analysis
        Route::get('/trends', [DashboardStatsController::class, 'trends'])
            ->name('trends');
        
        // Team/department statistics (management only)
        Route::get('/team', [DashboardStatsController::class, 'team'])
            ->name('team')
            ->middleware('role:admin|manajer');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Dashboard Action Routes
    |--------------------------------------------------------------------------
    |
    | User actions that can be performed from the dashboard,
    | such as attendance check-in/out, status updates, etc.
    |
    */
    
    Route::prefix('dashboard/actions')->name('api.v3.dashboard.actions.')->group(function () {
        
        // Attendance actions
        Route::post('/checkin', [DashboardActionController::class, 'checkIn'])
            ->name('checkin')
            ->middleware('throttle:5,1'); // Prevent spam checkins
        
        Route::post('/checkout', [DashboardActionController::class, 'checkOut'])
            ->name('checkout')
            ->middleware('throttle:5,1'); // Prevent spam checkouts
        
        // Status updates
        Route::post('/status', [DashboardActionController::class, 'updateStatus'])
            ->name('status');
        
        // Work location updates
        Route::post('/location', [DashboardActionController::class, 'updateLocation'])
            ->name('location');
        
        // Break management
        Route::post('/break/start', [DashboardActionController::class, 'startBreak'])
            ->name('break.start');
        
        Route::post('/break/end', [DashboardActionController::class, 'endBreak'])
            ->name('break.end');
        
        // Emergency actions
        Route::post('/emergency', [DashboardActionController::class, 'emergency'])
            ->name('emergency')
            ->middleware('throttle:3,1'); // Limited emergency calls
    });
    
    /*
    |--------------------------------------------------------------------------
    | Dashboard Export Routes
    |--------------------------------------------------------------------------
    |
    | Data export functionality for dashboard information
    | in various formats (PDF, Excel, CSV).
    |
    */
    
    Route::prefix('dashboard/export')->name('api.v3.dashboard.export.')->group(function () {
        
        // Export dashboard summary
        Route::get('/summary/{format}', [DashboardExportController::class, 'summary'])
            ->name('summary')
            ->where('format', 'pdf|excel|csv')
            ->middleware('throttle:5,60'); // Limit exports per hour
        
        // Export attendance data
        Route::get('/attendance/{format}', [DashboardExportController::class, 'attendance'])
            ->name('attendance')
            ->where('format', 'pdf|excel|csv')
            ->middleware('throttle:5,60');
        
        // Export financial data (permission required)
        Route::get('/financial/{format}', [DashboardExportController::class, 'financial'])
            ->name('financial')
            ->where('format', 'pdf|excel|csv')
            ->middleware(['permission:export_financial_data', 'throttle:3,60']);
        
        // Export management reports (management only)
        Route::get('/management/{format}', [DashboardExportController::class, 'management'])
            ->name('management')
            ->where('format', 'pdf|excel|csv')
            ->middleware(['role:admin|manajer|bendahara', 'throttle:3,60']);
    });
});

/*
|--------------------------------------------------------------------------
| Legacy Route Compatibility
|--------------------------------------------------------------------------
|
| Temporary routes that redirect legacy dashboard endpoints to the new
| unified system. These will be deprecated and removed in future versions.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Legacy V2 dashboard redirects
    Route::redirect('/v2/dashboards/dokter', '/v3/dashboard', 301);
    Route::redirect('/v2/dashboards/paramedis', '/v3/dashboard', 301);
    Route::redirect('/v2/dashboards/manajer', '/v3/dashboard', 301);
    
    // Legacy role-specific endpoints
    Route::get('/legacy/admin/dashboard', function () {
        return redirect('/api/v3/dashboard')->setStatusCode(301);
    })->name('legacy.admin.dashboard');
    
    Route::get('/legacy/manajer/dashboard', function () {
        return redirect('/api/v3/dashboard')->setStatusCode(301);
    })->name('legacy.manajer.dashboard');
    
    Route::get('/legacy/petugas/dashboard', function () {
        return redirect('/api/v3/dashboard')->setStatusCode(301);
    })->name('legacy.petugas.dashboard');
});

/*
|--------------------------------------------------------------------------
| API Documentation & Info Routes
|--------------------------------------------------------------------------
|
| Routes providing API documentation and version information
| for the unified dashboard system.
|
*/

Route::get('/dashboard/info', function () {
    return response()->json([
        'api_version' => 'v3',
        'name' => 'Unified Dashboard API',
        'description' => 'Single API serving all dashboard functionality across user roles',
        'features' => [
            'Role-based content delivery',
            'Intelligent caching',
            'Real-time updates',
            'Performance optimization',
            'Unified authentication',
            'Export functionality',
        ],
        'endpoints' => [
            'main' => '/api/v3/dashboard',
            'metrics' => '/api/v3/dashboard/metrics',
            'attendance' => '/api/v3/dashboard/attendance',
            'financial' => '/api/v3/dashboard/financial',
            'management' => '/api/v3/dashboard/management',
            'actions' => '/api/v3/dashboard/actions/*',
            'exports' => '/api/v3/dashboard/export/*',
        ],
        'deprecated_endpoints' => [
            '/api/v2/dashboards/dokter',
            '/api/v2/dashboards/paramedis',
            '/api/v2/dashboards/manajer',
            'All role-specific dashboard controllers',
        ],
        'migration_guide' => url('/docs/dashboard-api-v3-migration'),
        'documentation' => url('/docs/api/v3/dashboard'),
    ]);
})->name('api.v3.dashboard.info');

/*
|--------------------------------------------------------------------------
| Rate Limiting Configuration
|--------------------------------------------------------------------------
|
| Custom rate limiting for different types of dashboard operations:
| - Main dashboard: 60 requests/minute (real-time updates)
| - Attendance: 30 requests/minute (frequent status checks)
| - Actions: 5 requests/minute (prevent spam)
| - Exports: 5 requests/hour (resource intensive)
| - Cache refresh: 10 requests/minute (prevent abuse)
|
*/