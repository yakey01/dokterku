<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardServiceInterface;
use App\DTOs\Dashboard\DashboardDataDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Unified Dashboard Controller
 * 
 * Single controller serving all dashboard functionality across user roles.
 * Replaces 25+ fragmented dashboard controllers with a unified, 
 * role-aware implementation.
 * 
 * Features:
 * - Role-based content delivery
 * - Intelligent caching
 * - Consistent API responses
 * - Performance optimization
 * - Comprehensive error handling
 */
class UnifiedDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboardService
    ) {
        $this->middleware(['auth:sanctum']);
    }
    
    /**
     * Get comprehensive dashboard data for authenticated user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $cacheKey = $this->dashboardService->getCacheKey($user, 'main');
            
            // Check cache first for performance
            $dashboardData = Cache::remember($cacheKey, 300, function () use ($user, $request) {
                return $this->dashboardService->getDashboardData($user, $request);
            });
            
            // Filter sensitive data based on user permissions
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
            $filteredData = $dashboardData->filterByPermissions($userPermissions);
            
            Log::info('Dashboard data served', [
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first(),
                'sections' => $filteredData->getAvailableSections(),
                'cache_key' => $cacheKey,
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $filteredData->toArray(),
                'meta' => [
                    'api_version' => 'v3',
                    'cached' => Cache::has($cacheKey),
                    'cache_ttl' => 300,
                    'generated_at' => now()->toISOString(),
                    'user_role' => $user->getRoleNames()->first(),
                    'available_sections' => $filteredData->getAvailableSections(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Dashboard error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get user metrics for specified period
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function metrics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period' => 'string|in:today,week,month,quarter,year',
            'date' => 'date_format:Y-m-d',
            'include_trends' => 'boolean',
            'include_comparisons' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $user = $request->user();
            $period = $request->get('period', 'today');
            $cacheKey = $this->dashboardService->getCacheKey($user, "metrics_{$period}");
            
            $metrics = Cache::remember($cacheKey, 180, function () use ($user, $period) {
                return $this->dashboardService->getUserMetrics($user, $period);
            });
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
                'meta' => [
                    'period' => $period,
                    'user_role' => $user->getRoleNames()->first(),
                    'cached' => Cache::has($cacheKey),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Metrics error', [
                'user_id' => $request->user()?->id,
                'period' => $request->get('period'),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load metrics data',
            ], 500);
        }
    }
    
    /**
     * Get attendance data and status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function attendance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'date_format:Y-m-d',
            'include_history' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $user = $request->user();
            $date = $request->get('date', now()->toDateString());
            $cacheKey = $this->dashboardService->getCacheKey($user, "attendance_{$date}");
            
            $attendanceData = Cache::remember($cacheKey, 60, function () use ($user, $date) {
                return $this->dashboardService->getAttendanceData($user, $date);
            });
            
            return response()->json([
                'success' => true,
                'data' => $attendanceData,
                'meta' => [
                    'date' => $date,
                    'user_role' => $user->getRoleNames()->first(),
                    'real_time' => true,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Attendance data error', [
                'user_id' => $request->user()?->id,
                'date' => $request->get('date'),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance data',
            ], 500);
        }
    }
    
    /**
     * Get schedule information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function schedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'date_format:Y-m-d',
            'end_date' => 'date_format:Y-m-d|after_or_equal:start_date',
            'view' => 'string|in:week,month,day',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $user = $request->user();
            $startDate = $request->get('start_date', now()->startOfWeek()->toDateString());
            $endDate = $request->get('end_date', now()->endOfWeek()->toDateString());
            $cacheKey = $this->dashboardService->getCacheKey($user, "schedule_{$startDate}_{$endDate}");
            
            $scheduleData = Cache::remember($cacheKey, 600, function () use ($user, $startDate, $endDate) {
                return $this->dashboardService->getScheduleData($user, $startDate, $endDate);
            });
            
            return response()->json([
                'success' => true,
                'data' => $scheduleData,
                'meta' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'user_role' => $user->getRoleNames()->first(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Schedule data error', [
                'user_id' => $request->user()?->id,
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date'),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load schedule data',
            ], 500);
        }
    }
    
    /**
     * Get financial overview (for authorized users)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function financial(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has permission to view financial data
        if (!$this->dashboardService->canAccessFeature($user, 'financial_overview')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to access financial data',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'period' => 'string|in:today,week,month,quarter,year',
            'include_breakdown' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $period = $request->get('period', 'month');
            $cacheKey = $this->dashboardService->getCacheKey($user, "financial_{$period}");
            
            $financialData = Cache::remember($cacheKey, 300, function () use ($user, $period) {
                return $this->dashboardService->getFinancialOverview($user, $period);
            });
            
            return response()->json([
                'success' => true,
                'data' => $financialData,
                'meta' => [
                    'period' => $period,
                    'user_role' => $user->getRoleNames()->first(),
                    'access_level' => 'authorized',
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Financial data error', [
                'user_id' => $user->id,
                'period' => $request->get('period'),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load financial data',
            ], 500);
        }
    }
    
    /**
     * Get management statistics (for management roles)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function management(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has management role
        if (!$this->dashboardService->canAccessFeature($user, 'management_dashboard')) {
            return response()->json([
                'success' => false,
                'message' => 'Management access required',
            ], 403);
        }
        
        try {
            $filters = $request->only(['department', 'team', 'period', 'status']);
            $cacheKey = $this->dashboardService->getCacheKey($user, 'management_' . md5(serialize($filters)));
            
            $managementStats = Cache::remember($cacheKey, 180, function () use ($user, $filters) {
                return $this->dashboardService->getManagementStats($user, $filters);
            });
            
            return response()->json([
                'success' => true,
                'data' => $managementStats,
                'meta' => [
                    'filters_applied' => $filters,
                    'user_role' => $user->getRoleNames()->first(),
                    'access_level' => 'management',
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Management data error', [
                'user_id' => $user->id,
                'filters' => $request->only(['department', 'team', 'period', 'status']),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load management data',
            ], 500);
        }
    }
    
    /**
     * Get quick actions available to user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function quickActions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $cacheKey = $this->dashboardService->getCacheKey($user, 'quick_actions');
            
            $quickActions = Cache::remember($cacheKey, 900, function () use ($user) {
                return $this->dashboardService->getQuickActions($user);
            });
            
            return response()->json([
                'success' => true,
                'data' => $quickActions,
                'meta' => [
                    'user_role' => $user->getRoleNames()->first(),
                    'total_actions' => count($quickActions),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Quick actions error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quick actions',
            ], 500);
        }
    }
    
    /**
     * Refresh dashboard cache
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $section = $request->get('section', null); // null = refresh all
            
            $refreshed = $this->dashboardService->invalidateCache($user, $section);
            
            Log::info('Dashboard cache refreshed', [
                'user_id' => $user->id,
                'section' => $section ?? 'all',
                'success' => $refreshed,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard cache refreshed successfully',
                'data' => [
                    'section_refreshed' => $section ?? 'all',
                    'timestamp' => now()->toISOString(),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Cache refresh error', [
                'user_id' => $request->user()?->id,
                'section' => $request->get('section'),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache',
            ], 500);
        }
    }
    
    /**
     * Get dashboard health status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function health(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $health = [
                'status' => 'healthy',
                'user_authenticated' => true,
                'user_role' => $user->getRoleNames()->first(),
                'permissions_count' => $user->getAllPermissions()->count(),
                'cache_status' => Cache::store()->getStore() instanceof \Illuminate\Cache\RedisStore ? 'redis' : 'file',
                'api_version' => 'v3',
                'response_time_ms' => round(microtime(true) * 1000, 2),
                'timestamp' => now()->toISOString(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $health,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}