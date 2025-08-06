<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Performance monitoring middleware for API endpoints
 */
class PerformanceMonitoringMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $startDbQueryCount = $this->getDbQueryCount();
        
        // Generate unique request ID
        $requestId = uniqid('req_', true);
        $request->attributes->set('request_id', $requestId);
        
        // Process request
        $response = $next($request);
        
        // Calculate metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endDbQueryCount = $this->getDbQueryCount();
        
        $metrics = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
            'user_id' => $request->user()?->id,
            'status_code' => $response->getStatusCode(),
            'response_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_usage_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'db_queries' => $endDbQueryCount - $startDbQueryCount,
            'timestamp' => now()->toISOString(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip()
        ];
        
        // Log performance metrics
        $this->logPerformanceMetrics($metrics);
        
        // Store in cache for real-time monitoring
        $this->cachePerformanceMetrics($metrics);
        
        // Add performance headers to response
        $response->headers->set('X-Response-Time', $metrics['response_time_ms'] . 'ms');
        $response->headers->set('X-Memory-Usage', $metrics['memory_usage_mb'] . 'MB');
        $response->headers->set('X-DB-Queries', $metrics['db_queries']);
        $response->headers->set('X-Request-ID', $requestId);
        
        // Alert on performance issues
        $this->checkPerformanceThresholds($metrics);
        
        return $response;
    }
    
    /**
     * Get current database query count
     */
    private function getDbQueryCount(): int
    {
        return count(DB::getQueryLog());
    }
    
    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(array $metrics): void
    {
        $logLevel = $this->determineLogLevel($metrics);
        
        Log::log($logLevel, 'API Performance Metrics', $metrics);
        
        // Log to dedicated performance log channel if configured
        if (config('logging.channels.performance')) {
            Log::channel('performance')->info('API Request', $metrics);
        }
    }
    
    /**
     * Determine appropriate log level based on performance
     */
    private function determineLogLevel(array $metrics): string
    {
        // Critical performance issues
        if ($metrics['response_time_ms'] > 5000 || $metrics['db_queries'] > 50) {
            return 'error';
        }
        
        // Warning level for slow requests
        if ($metrics['response_time_ms'] > 2000 || $metrics['db_queries'] > 20) {
            return 'warning';
        }
        
        // Info level for normal requests
        return 'info';
    }
    
    /**
     * Cache performance metrics for real-time monitoring
     */
    private function cachePerformanceMetrics(array $metrics): void
    {
        $cacheKey = 'performance_metrics_' . date('Y-m-d-H');
        
        // Get existing metrics for this hour
        $hourlyMetrics = Cache::get($cacheKey, []);
        $hourlyMetrics[] = $metrics;
        
        // Keep only last 1000 requests per hour
        if (count($hourlyMetrics) > 1000) {
            $hourlyMetrics = array_slice($hourlyMetrics, -1000);
        }
        
        // Cache for 2 hours
        Cache::put($cacheKey, $hourlyMetrics, 7200);
        
        // Update real-time stats
        $this->updateRealTimeStats($metrics);
    }
    
    /**
     * Update real-time performance statistics
     */
    private function updateRealTimeStats(array $metrics): void
    {
        $statsKey = 'api_performance_stats';
        $stats = Cache::get($statsKey, [
            'total_requests' => 0,
            'avg_response_time' => 0,
            'slow_requests' => 0,
            'error_rate' => 0,
            'total_errors' => 0,
            'last_updated' => now()->toISOString()
        ]);
        
        // Update counters
        $stats['total_requests']++;
        
        // Update average response time (rolling average)
        $stats['avg_response_time'] = (
            ($stats['avg_response_time'] * ($stats['total_requests'] - 1)) + 
            $metrics['response_time_ms']
        ) / $stats['total_requests'];
        
        // Count slow requests (> 2 seconds)
        if ($metrics['response_time_ms'] > 2000) {
            $stats['slow_requests']++;
        }
        
        // Count errors
        if ($metrics['status_code'] >= 400) {
            $stats['total_errors']++;
        }
        
        // Calculate error rate
        $stats['error_rate'] = ($stats['total_errors'] / $stats['total_requests']) * 100;
        
        $stats['last_updated'] = now()->toISOString();
        
        // Cache for 1 hour
        Cache::put($statsKey, $stats, 3600);
    }
    
    /**
     * Check performance thresholds and alert if necessary
     */
    private function checkPerformanceThresholds(array $metrics): void
    {
        $thresholds = config('performance.thresholds', [
            'response_time_critical' => 5000, // 5 seconds
            'response_time_warning' => 2000,  // 2 seconds
            'memory_critical' => 128,         // 128MB
            'memory_warning' => 64,           // 64MB
            'db_queries_critical' => 50,
            'db_queries_warning' => 20
        ]);
        
        $alerts = [];
        
        // Check response time
        if ($metrics['response_time_ms'] > $thresholds['response_time_critical']) {
            $alerts[] = "CRITICAL: Response time {$metrics['response_time_ms']}ms exceeds threshold";
        } elseif ($metrics['response_time_ms'] > $thresholds['response_time_warning']) {
            $alerts[] = "WARNING: Response time {$metrics['response_time_ms']}ms is slow";
        }
        
        // Check memory usage
        if ($metrics['memory_usage_mb'] > $thresholds['memory_critical']) {
            $alerts[] = "CRITICAL: Memory usage {$metrics['memory_usage_mb']}MB exceeds threshold";
        } elseif ($metrics['memory_usage_mb'] > $thresholds['memory_warning']) {
            $alerts[] = "WARNING: Memory usage {$metrics['memory_usage_mb']}MB is high";
        }
        
        // Check database queries
        if ($metrics['db_queries'] > $thresholds['db_queries_critical']) {
            $alerts[] = "CRITICAL: Database queries {$metrics['db_queries']} exceeds threshold";
        } elseif ($metrics['db_queries'] > $thresholds['db_queries_warning']) {
            $alerts[] = "WARNING: Database queries {$metrics['db_queries']} is high";
        }
        
        // Send alerts if any
        if (!empty($alerts)) {
            $this->sendPerformanceAlert($metrics, $alerts);
        }
    }
    
    /**
     * Send performance alert
     */
    private function sendPerformanceAlert(array $metrics, array $alerts): void
    {
        $alertData = [
            'timestamp' => now()->toISOString(),
            'request_id' => $metrics['request_id'],
            'endpoint' => $metrics['method'] . ' ' . $metrics['uri'],
            'user_id' => $metrics['user_id'],
            'alerts' => $alerts,
            'metrics' => $metrics
        ];
        
        // Log critical alert
        Log::error('Performance Alert', $alertData);
        
        // TODO: Integrate with notification system (Slack, email, etc.)
        // TODO: Integrate with monitoring tools (New Relic, DataDog, etc.)
    }
}