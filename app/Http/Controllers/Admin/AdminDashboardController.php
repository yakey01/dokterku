<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardService;
use App\DTOs\Admin\AdminDashboardStatsDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Admin Dashboard Controller
 * 
 * Refactored controller with service layer implementation,
 * enhanced error handling, and optimized performance.
 */
class AdminDashboardController extends Controller
{
    private AdminDashboardService $dashboardService;

    public function __construct(AdminDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display admin dashboard with comprehensive statistics
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            // Get dashboard statistics through service layer
            $dashboardData = $this->dashboardService->getDashboardStats();
            $statsDTO = AdminDashboardStatsDTO::fromArray($dashboardData);
            
            // Get additional data for dashboard
            $recentUsers = $this->dashboardService->getRecentActivities(5)
                ->filter(fn($activity) => $activity['type'] === 'user_created');
            
            $recentProcedures = $this->dashboardService->getRecentActivities(5)
                ->filter(fn($activity) => $activity['type'] === 'procedure_created');
            
            // Log dashboard access
            Log::info('Admin dashboard accessed', [
                'admin_id' => auth()->id(),
                'admin_email' => auth()->user()->email,
                'timestamp' => now()->toISOString()
            ]);
            
            return view('admin.dashboard', [
                'stats' => $statsDTO->toArray(),
                'recent_users' => $recentUsers,
                'recent_procedures' => $recentProcedures,
                'dashboard_summary' => $statsDTO->getSummary(),
                'growth_indicators' => $statsDTO->getGrowthIndicators(),
                'critical_alerts' => $statsDTO->getCriticalAlerts(),
                'performance_score' => $statsDTO->getPerformanceScore(),
                'last_updated' => $statsDTO->lastUpdated
            ]);
            
        } catch (Exception $e) {
            Log::error('Admin dashboard error', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback to basic stats if service fails
            return view('admin.dashboard', [
                'stats' => $this->getBasicStats(),
                'recent_users' => collect(),
                'recent_procedures' => collect(),
                'error' => 'Dashboard data temporarily unavailable'
            ]);
        }
    }
    
    /**
     * Get real-time dashboard data (AJAX endpoint)
     *
     * @return JsonResponse
     */
    public function getData(): JsonResponse
    {
        try {
            $dashboardData = $this->dashboardService->getRealtimeDashboardStats();
            $statsDTO = AdminDashboardStatsDTO::fromArray($dashboardData);
            
            return response()->json([
                'success' => true,
                'data' => $statsDTO->toArray(),
                'summary' => $statsDTO->getSummary(),
                'alerts' => $statsDTO->getCriticalAlerts(),
                'performance_score' => $statsDTO->getPerformanceScore(),
                'is_fresh' => $statsDTO->isFresh()
            ]);
            
        } catch (Exception $e) {
            Log::error('Admin dashboard API error', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Refresh dashboard cache
     *
     * @return JsonResponse
     */
    public function refreshCache(): JsonResponse
    {
        try {
            $this->dashboardService->clearCache();
            
            Log::info('Admin dashboard cache refreshed', [
                'admin_id' => auth()->id(),
                'admin_email' => auth()->user()->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard cache refreshed successfully'
            ]);
            
        } catch (Exception $e) {
            Log::error('Dashboard cache refresh failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache'
            ], 500);
        }
    }
    
    /**
     * Get system health status
     *
     * @return JsonResponse
     */
    public function getSystemHealth(): JsonResponse
    {
        try {
            $dashboardData = $this->dashboardService->getDashboardStats();
            $systemStats = $dashboardData['system_stats'] ?? [];
            $performanceMetrics = $dashboardData['performance_metrics'] ?? [];
            
            return response()->json([
                'success' => true,
                'system_health' => $systemStats,
                'performance' => $performanceMetrics,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system health data'
            ], 500);
        }
    }
    
    /**
     * Fallback method for basic statistics
     *
     * @return array
     */
    private function getBasicStats(): array
    {
        try {
            return [
                'user_stats' => [
                    'total_users' => \App\Models\User::count(),
                    'active_users' => \App\Models\User::whereNotNull('email_verified_at')->count()
                ],
                'financial_stats' => [
                    'total_income' => \App\Models\Pendapatan::sum('jumlah') ?? 0,
                    'total_expenses' => \App\Models\Pengeluaran::sum('jumlah') ?? 0
                ],
                'medical_stats' => [
                    'total_patients' => \App\Models\Pasien::count(),
                    'total_procedures' => \App\Models\Tindakan::count()
                ],
                'last_updated' => now()->toISOString()
            ];
        } catch (Exception $e) {
            return [
                'error' => 'Unable to fetch basic statistics',
                'last_updated' => now()->toISOString()
            ];
        }
    }
}