<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Performance Monitoring",
 *     description="API performance monitoring and metrics"
 * )
 */
class PerformanceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v2/performance/metrics",
     *     summary="Get current performance metrics",
     *     tags={"Performance Monitoring"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="timeframe",
     *         in="query",
     *         description="Time frame for metrics (1h, 6h, 24h, 7d)",
     *         @OA\Schema(type="string", enum={"1h", "6h", "24h", "7d"}, default="1h")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Performance metrics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="total_requests", type="integer", example=1250),
     *                     @OA\Property(property="avg_response_time", type="number", format="float", example=245.67),
     *                     @OA\Property(property="error_rate", type="number", format="float", example=2.4),
     *                     @OA\Property(property="slow_requests", type="integer", example=15)
     *                 ),
     *                 @OA\Property(property="endpoints", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="endpoint", type="string", example="GET /api/v2/dashboards/paramedis"),
     *                         @OA\Property(property="requests", type="integer", example=125),
     *                         @OA\Property(property="avg_response_time", type="number", format="float", example=180.5),
     *                         @OA\Property(property="error_rate", type="number", format="float", example=1.2)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function metrics(Request $request): JsonResponse
    {
        // Ensure user has admin access
        $this->authorize('viewPerformanceMetrics');
        
        $timeframe = $request->query('timeframe', '1h');
        
        $metrics = $this->getPerformanceMetrics($timeframe);
        
        return response()->json([
            'success' => true,
            'data' => $metrics,
            'meta' => [
                'timeframe' => $timeframe,
                'generated_at' => now()->toISOString()
            ]
        ]);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v2/performance/realtime",
     *     summary="Get real-time performance statistics",
     *     tags={"Performance Monitoring"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Real-time statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_load", type="object",
     *                     @OA\Property(property="requests_per_minute", type="number", format="float", example=45.2),
     *                     @OA\Property(property="avg_response_time", type="number", format="float", example=245.67),
     *                     @OA\Property(property="active_connections", type="integer", example=12)
     *                 ),
     *                 @OA\Property(property="system_health", type="object",
     *                     @OA\Property(property="status", type="string", example="healthy"),
     *                     @OA\Property(property="memory_usage_percent", type="number", format="float", example=68.5),
     *                     @OA\Property(property="cpu_usage_percent", type="number", format="float", example=25.3)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function realtime(Request $request): JsonResponse
    {
        $this->authorize('viewPerformanceMetrics');
        
        $stats = Cache::get('api_performance_stats', []);
        $systemHealth = $this->getSystemHealth();
        
        return response()->json([
            'success' => true,
            'data' => [
                'current_load' => [
                    'requests_per_minute' => $this->calculateRequestsPerMinute(),
                    'avg_response_time' => $stats['avg_response_time'] ?? 0,
                    'active_connections' => $this->getActiveConnections()
                ],
                'system_health' => $systemHealth,
                'performance_stats' => $stats
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'refresh_interval' => 30 // seconds
            ]
        ]);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v2/performance/slow-queries",
     *     summary="Get slow database queries report",
     *     tags={"Performance Monitoring"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="threshold",
     *         in="query",
     *         description="Minimum query time in milliseconds",
     *         @OA\Schema(type="integer", default=1000)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slow queries report retrieved successfully"
     *     )
     * )
     */
    public function slowQueries(Request $request): JsonResponse
    {
        $this->authorize('viewPerformanceMetrics');
        
        $threshold = $request->query('threshold', 1000); // 1 second default
        
        $slowQueries = $this->getSlowQueries($threshold);
        
        return response()->json([
            'success' => true,
            'data' => [
                'threshold_ms' => $threshold,
                'slow_queries' => $slowQueries,
                'total_slow_queries' => count($slowQueries)
            ],
            'meta' => [
                'generated_at' => now()->toISOString()
            ]
        ]);
    }
    
    /**
     * @OA\Post(
     *     path="/api/v2/performance/alerts/configure",
     *     summary="Configure performance alert thresholds",
     *     tags={"Performance Monitoring"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="response_time_warning", type="integer", example=2000),
     *             @OA\Property(property="response_time_critical", type="integer", example=5000),
     *             @OA\Property(property="memory_warning_mb", type="integer", example=64),
     *             @OA\Property(property="memory_critical_mb", type="integer", example=128),
     *             @OA\Property(property="error_rate_threshold", type="number", format="float", example=5.0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alert thresholds configured successfully"
     *     )
     * )
     */
    public function configureAlerts(Request $request): JsonResponse
    {
        $this->authorize('configurePerformanceAlerts');
        
        $validated = $request->validate([
            'response_time_warning' => 'integer|min:100|max:30000',
            'response_time_critical' => 'integer|min:1000|max:60000',
            'memory_warning_mb' => 'integer|min:1|max:1024',
            'memory_critical_mb' => 'integer|min:10|max:2048',
            'error_rate_threshold' => 'numeric|min:0|max:100'
        ]);
        
        // Store thresholds in cache
        Cache::put('performance_alert_thresholds', $validated, 86400); // 24 hours
        
        return response()->json([
            'success' => true,
            'message' => 'Performance alert thresholds configured successfully',
            'data' => $validated
        ]);
    }
    
    /**
     * Get performance metrics for specified timeframe
     */
    private function getPerformanceMetrics(string $timeframe): array
    {
        $hours = match($timeframe) {
            '1h' => 1,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            default => 1
        };
        
        $metrics = [];
        $endpointStats = [];
        
        // Collect metrics from cache
        for ($i = 0; $i < $hours; $i++) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $cacheKey = 'performance_metrics_' . $hour;
            $hourlyMetrics = Cache::get($cacheKey, []);
            
            foreach ($hourlyMetrics as $metric) {
                $metrics[] = $metric;
                
                $endpoint = $metric['method'] . ' ' . $metric['uri'];
                if (!isset($endpointStats[$endpoint])) {
                    $endpointStats[$endpoint] = [
                        'requests' => 0,
                        'total_response_time' => 0,
                        'errors' => 0
                    ];
                }
                
                $endpointStats[$endpoint]['requests']++;
                $endpointStats[$endpoint]['total_response_time'] += $metric['response_time_ms'];
                
                if ($metric['status_code'] >= 400) {
                    $endpointStats[$endpoint]['errors']++;
                }
            }
        }
        
        // Calculate summary statistics
        $totalRequests = count($metrics);
        $totalResponseTime = array_sum(array_column($metrics, 'response_time_ms'));
        $totalErrors = count(array_filter($metrics, fn($m) => $m['status_code'] >= 400));
        $slowRequests = count(array_filter($metrics, fn($m) => $m['response_time_ms'] > 2000));
        
        // Format endpoint statistics
        $formattedEndpoints = [];
        foreach ($endpointStats as $endpoint => $stats) {
            $formattedEndpoints[] = [
                'endpoint' => $endpoint,
                'requests' => $stats['requests'],
                'avg_response_time' => $stats['requests'] > 0 ? 
                    round($stats['total_response_time'] / $stats['requests'], 2) : 0,
                'error_rate' => $stats['requests'] > 0 ? 
                    round(($stats['errors'] / $stats['requests']) * 100, 2) : 0
            ];
        }
        
        // Sort by request count
        usort($formattedEndpoints, fn($a, $b) => $b['requests'] <=> $a['requests']);
        
        return [
            'summary' => [
                'total_requests' => $totalRequests,
                'avg_response_time' => $totalRequests > 0 ? 
                    round($totalResponseTime / $totalRequests, 2) : 0,
                'error_rate' => $totalRequests > 0 ? 
                    round(($totalErrors / $totalRequests) * 100, 2) : 0,
                'slow_requests' => $slowRequests
            ],
            'endpoints' => array_slice($formattedEndpoints, 0, 20) // Top 20 endpoints
        ];
    }
    
    /**
     * Calculate requests per minute
     */
    private function calculateRequestsPerMinute(): float
    {
        $cacheKey = 'performance_metrics_' . now()->format('Y-m-d-H');
        $hourlyMetrics = Cache::get($cacheKey, []);
        
        // Get metrics from last minute
        $lastMinute = now()->subMinute();
        $recentMetrics = array_filter($hourlyMetrics, function($metric) use ($lastMinute) {
            return Carbon::parse($metric['timestamp'])->gte($lastMinute);
        });
        
        return count($recentMetrics);
    }
    
    /**
     * Get active database connections
     */
    private function getActiveConnections(): int
    {
        try {
            $result = DB::select('SHOW STATUS LIKE "Threads_connected"');
            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get system health metrics
     */
    private function getSystemHealth(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        return [
            'status' => 'healthy', // TODO: Implement health check logic
            'memory_usage_bytes' => $memoryUsage,
            'memory_usage_percent' => $memoryLimit > 0 ? 
                round(($memoryUsage / $memoryLimit) * 100, 2) : 0,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        if ($memoryLimit === '-1') {
            return 0; // Unlimited
        }
        
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }
    
    /**
     * Get slow database queries
     */
    private function getSlowQueries(int $thresholdMs): array
    {
        // This would require slow query log analysis
        // For now, return cached slow queries if available
        return Cache::get('slow_queries_cache', []);
    }
}