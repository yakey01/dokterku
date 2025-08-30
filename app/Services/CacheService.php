<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Services\LoggingService;

class CacheService
{
    private LoggingService $loggingService;
    
    // Cache configuration - OPTIMIZED for loop prevention
    private const DEFAULT_TTL = 3600; // 1 hour
    private const QUERY_CACHE_TTL = 1800; // 30 minutes
    private const VIEW_CACHE_TTL = 900; // 15 minutes
    private const API_CACHE_TTL = 900; // 15 minutes (increased from 5 min)
    private const DASHBOARD_CACHE_TTL = 1200; // 20 minutes for dashboard stability
    private const LONG_CACHE_TTL = 86400; // 24 hours
    
    // Cache tags for organized cache management
    private const CACHE_TAGS = [
        'model' => 'model_cache',
        'query' => 'query_cache',
        'view' => 'view_cache',
        'api' => 'api_cache',
        'dashboard' => 'dashboard_cache',
        'report' => 'report_cache',
        'statistics' => 'statistics_cache',
    ];
    
    // Cache key prefixes
    private const CACHE_PREFIXES = [
        'model' => 'model:',
        'query' => 'query:',
        'view' => 'view:',
        'api' => 'api:',
        'dashboard' => 'dashboard:',
        'report' => 'report:',
        'statistics' => 'stats:',
    ];
    
    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }
    
    /**
     * Cache a model query result
     */
    public function cacheModelQuery(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['model'] . $key;
        $ttl = $ttl ?? self::QUERY_CACHE_TTL;
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['model']);
    }
    
    /**
     * Cache a database query result
     */
    public function cacheQuery(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['query'] . $key;
        $ttl = $ttl ?? self::QUERY_CACHE_TTL;
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['query']);
    }
    
    /**
     * Cache a view fragment
     */
    public function cacheView(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['view'] . $key;
        $ttl = $ttl ?? self::VIEW_CACHE_TTL;
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['view']);
    }
    
    /**
     * Cache an API response
     */
    public function cacheApiResponse(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['api'] . $key;
        $ttl = $ttl ?? self::API_CACHE_TTL;
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['api']);
    }
    
    /**
     * Cache dashboard data
     */
    public function cacheDashboard(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['dashboard'] . $key;
        $ttl = $ttl ?? self::DASHBOARD_CACHE_TTL; // Use dashboard-specific TTL
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['dashboard']);
    }
    
    /**
     * Cache report data
     */
    public function cacheReport(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['report'] . $key;
        $ttl = $ttl ?? self::LONG_CACHE_TTL;
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['report']);
    }
    
    /**
     * Cache statistics data
     */
    public function cacheStatistics(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['statistics'] . $key;
        $ttl = $ttl ?? self::LONG_CACHE_TTL;
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['statistics']);
    }
    
    /**
     * Core cache remember method with performance logging
     */
    private function remember(string $key, int $ttl, callable $callback, string $tag): mixed
    {
        $startTime = microtime(true);
        
        try {
            // Check if cache is enabled
            if (!config('cache.enabled', true)) {
                return $callback();
            }
            
            // Try to get from cache
            $result = Cache::remember($key, $ttl, function() use ($callback, $key, $startTime) {
                $this->loggingService->logPerformance(
                    'cache_miss',
                    microtime(true) - $startTime,
                    ['cache_key' => $key, 'action' => 'generating'],
                    'info'
                );
                
                return $callback();
            });
            
            $duration = microtime(true) - $startTime;
            
            // Log cache hit/miss
            $this->loggingService->logPerformance(
                'cache_access',
                $duration,
                [
                    'cache_key' => $key,
                    'cache_tag' => $tag,
                    'hit' => Cache::has($key),
                    'size' => $this->estimateSize($result),
                ],
                $duration > 0.1 ? 'warning' : 'info'
            );
            
            return $result;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Cache operation failed',
                $e,
                ['cache_key' => $key, 'cache_tag' => $tag],
                'error'
            );
            
            // Return callback result directly on cache failure
            return $callback();
        }
    }
    
    /**
     * Smart cache warming for dashboard loop prevention
     */
    public function warmDashboardCache(): void
    {
        try {
            $today = now();
            
            // Pre-warm today stats cache
            $this->cacheDashboard("manajer_today_stats_{$today->format('Y-m-d')}", function() use ($today) {
                // This will be populated by actual controller logic
                return null;
            });
            
            // Pre-warm finance overview cache
            $this->cacheDashboard("manajer_finance_overview_{$today->year}_{$today->month}", function() use ($today) {
                return null;
            });
            
            // Pre-warm attendance cache
            $this->cacheDashboard("manajer_attendance_today_{$today->format('Y-m-d')}", function() use ($today) {
                return null;
            });
            
        } catch (\Exception $e) {
            Log::error('Cache warming failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Selective cache invalidation for loop prevention
     */
    public function smartInvalidate(string $changeType, array $affectedData = []): void
    {
        try {
            switch ($changeType) {
                case 'financial_update':
                    // Only invalidate today stats if significant change
                    if (isset($affectedData['amount']) && $affectedData['amount'] > 100000) {
                        $this->forget("manajer_today_stats_" . now()->format('Y-m-d'), 'dashboard');
                    }
                    break;
                    
                case 'attendance_update':
                    // Only invalidate if status change affects count
                    if (isset($affectedData['status_change']) && $affectedData['status_change']) {
                        $this->forget("manajer_attendance_today_" . now()->format('Y-m-d'), 'dashboard');
                    }
                    break;
                    
                case 'patient_update':
                    // Patient data changes less frequently
                    $this->forget("manajer_today_stats_" . now()->format('Y-m-d'), 'dashboard');
                    break;
                    
                default:
                    // Conservative approach for unknown changes
                    $this->flushTag('dashboard');
            }
            
        } catch (\Exception $e) {
            Log::error('Smart cache invalidation failed', [
                'change_type' => $changeType,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Invalidate cache by key
     */
    public function forget(string $key, string $type = 'model'): bool
    {
        $cacheKey = self::CACHE_PREFIXES[$type] . $key;
        
        try {
            $result = Cache::forget($cacheKey);
            
            $this->loggingService->logActivity(
                'cache_invalidated',
                null,
                ['cache_key' => $cacheKey, 'success' => $result],
                'Cache invalidated: ' . $key
            );
            
            return $result;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Cache invalidation failed',
                $e,
                ['cache_key' => $cacheKey],
                'error'
            );
            
            return false;
        }
    }

    /**
     * Invalidate cache by tag
     */
    public function invalidateByTag(string $tag): bool
    {
        try {
            if (isset(self::CACHE_TAGS[$tag])) {
                $cacheTag = self::CACHE_TAGS[$tag];
                $result = Cache::tags($cacheTag)->flush();
                
                $this->loggingService->logActivity(
                    'cache_tag_invalidated',
                    null,
                    ['cache_tag' => $tag, 'success' => $result],
                    'Cache tag invalidated: ' . $tag
                );
                
                return $result;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Cache tag invalidation failed',
                $e,
                ['cache_tag' => $tag],
                'error'
            );
            
            return false;
        }
    }
    
    /**
     * Invalidate cache by tag (fallback for non-Redis stores)
     */
    public function flushTag(string $tag): bool
    {
        try {
            if (!isset(self::CACHE_TAGS[$tag])) {
                throw new \InvalidArgumentException("Invalid cache tag: {$tag}");
            }
            
            $cacheTag = self::CACHE_TAGS[$tag];
            
            // For Redis cache store
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $pattern = "*{$cacheTag}*";
                $keys = Redis::keys($pattern);
                
                if (!empty($keys)) {
                    Redis::del($keys);
                }
            } else {
                // For non-Redis stores, flush all cache as fallback
                // since tags are not supported on file/array drivers
                $this->loggingService->logActivity(
                    'cache_tag_flush_fallback',
                    null,
                    ['cache_tag' => $cacheTag, 'action' => 'flush_all'],
                    'Cache tags not supported, flushing all cache instead'
                );
                
                Cache::flush();
            }
            
            $this->loggingService->logActivity(
                'cache_tag_flushed',
                null,
                ['cache_tag' => $cacheTag],
                'Cache tag flushed: ' . $tag
            );
            
            return true;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Cache tag flush failed',
                $e,
                ['cache_tag' => $tag],
                'error'
            );
            
            return false;
        }
    }
    
    /**
     * Flush all cache
     */
    public function flushAll(): bool
    {
        try {
            Cache::flush();
            
            $this->loggingService->logActivity(
                'cache_flushed_all',
                null,
                [],
                'All cache flushed'
            );
            
            return true;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Cache flush all failed',
                $e,
                [],
                'error'
            );
            
            return false;
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        try {
            $stats = [
                'enabled' => config('cache.enabled', true),
                'driver' => config('cache.default'),
                'tags' => self::CACHE_TAGS,
                'prefixes' => self::CACHE_PREFIXES,
                'ttl_config' => [
                    'default' => self::DEFAULT_TTL,
                    'query' => self::QUERY_CACHE_TTL,
                    'view' => self::VIEW_CACHE_TTL,
                    'api' => self::API_CACHE_TTL,
                    'long' => self::LONG_CACHE_TTL,
                ],
            ];
            
            // Add Redis-specific stats if available
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $stats['redis'] = [
                    'info' => Redis::info(),
                    'memory' => Redis::info('memory'),
                    'keyspace' => Redis::info('keyspace'),
                ];
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Cache stats retrieval failed',
                $e,
                [],
                'error'
            );
            
            return ['error' => 'Unable to retrieve cache stats'];
        }
    }
    
    /**
     * Warm up cache with common queries
     */
    public function warmUp(): array
    {
        $warmed = [];
        
        try {
            // Warm up common dashboard queries
            $warmed['dashboard'] = $this->warmUpDashboard();
            
            // Warm up model counts
            $warmed['model_counts'] = $this->warmUpModelCounts();
            
            // Warm up user statistics
            $warmed['user_stats'] = $this->warmUpUserStats();
            
            // Warm up financial summaries
            $warmed['financial_summaries'] = $this->warmUpFinancialSummaries();
            
            $this->loggingService->logActivity(
                'cache_warmed_up',
                null,
                ['warmed_keys' => array_keys($warmed)],
                'Cache warmed up successfully'
            );
            
            return $warmed;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Cache warm up failed',
                $e,
                [],
                'error'
            );
            
            return ['error' => 'Cache warm up failed'];
        }
    }
    
    /**
     * Warm up dashboard cache
     */
    private function warmUpDashboard(): array
    {
        $warmed = [];
        
        // Today's statistics
        $warmed['today_stats'] = $this->cacheDashboard('today_stats', function() {
            $today = Carbon::today();
            
            return [
                'pasien_count' => \App\Models\Pasien::whereDate('created_at', $today)->count(),
                'tindakan_count' => \App\Models\Tindakan::whereDate('created_at', $today)->count(),
                'pendapatan' => \App\Models\Pendapatan::whereDate('created_at', $today)->sum('jumlah'),
                'pengeluaran' => \App\Models\Pengeluaran::whereDate('created_at', $today)->sum('jumlah'),
            ];
        });
        
        // This month's statistics
        $warmed['month_stats'] = $this->cacheDashboard('month_stats', function() {
            $startOfMonth = Carbon::now()->startOfMonth();
            
            return [
                'pasien_count' => \App\Models\Pasien::where('created_at', '>=', $startOfMonth)->count(),
                'tindakan_count' => \App\Models\Tindakan::where('created_at', '>=', $startOfMonth)->count(),
                'pendapatan' => \App\Models\Pendapatan::where('created_at', '>=', $startOfMonth)->sum('jumlah'),
                'pengeluaran' => \App\Models\Pengeluaran::where('created_at', '>=', $startOfMonth)->sum('jumlah'),
            ];
        });
        
        return $warmed;
    }
    
    /**
     * Warm up model counts
     */
    private function warmUpModelCounts(): array
    {
        $warmed = [];
        
        $models = [
            'pasien' => \App\Models\Pasien::class,
            'dokter' => \App\Models\Dokter::class,
            'tindakan' => \App\Models\Tindakan::class,
            'pendapatan' => \App\Models\Pendapatan::class,
            'pengeluaran' => \App\Models\Pengeluaran::class,
            'users' => \App\Models\User::class,
        ];
        
        foreach ($models as $name => $model) {
            $warmed[$name] = $this->cacheModelQuery("count_{$name}", function() use ($model) {
                return $model::count();
            });
        }
        
        return $warmed;
    }
    
    /**
     * Warm up user statistics
     */
    private function warmUpUserStats(): array
    {
        $warmed = [];
        
        $warmed['users_by_role'] = $this->cacheStatistics('users_by_role', function() {
            return \App\Models\User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray();
        });
        
        $warmed['active_users'] = $this->cacheStatistics('active_users', function() {
            return \App\Models\User::where('last_login_at', '>=', Carbon::now()->subDays(30))
                ->count();
        });
        
        return $warmed;
    }
    
    /**
     * Warm up financial summaries
     */
    private function warmUpFinancialSummaries(): array
    {
        $warmed = [];
        
        $warmed['monthly_revenue'] = $this->cacheReport('monthly_revenue', function() {
            return \App\Models\Pendapatan::select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(jumlah) as total')
                )
                ->where('created_at', '>=', Carbon::now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get()
                ->toArray();
        });
        
        $warmed['monthly_expenses'] = $this->cacheReport('monthly_expenses', function() {
            return \App\Models\Pengeluaran::select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(jumlah) as total')
                )
                ->where('created_at', '>=', Carbon::now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get()
                ->toArray();
        });
        
        return $warmed;
    }
    
    /**
     * Cache model with automatic invalidation
     */
    public function cacheModelWithInvalidation(Model $model, string $key, callable $callback, ?int $ttl = null): mixed
    {
        $modelClass = get_class($model);
        $cacheKey = "model:{$modelClass}:{$key}";
        
        return $this->remember($cacheKey, $ttl ?? self::DEFAULT_TTL, $callback, self::CACHE_TAGS['model']);
    }
    
    /**
     * Invalidate cache for a specific model
     */
    public function invalidateModelCache(Model $model): bool
    {
        $modelClass = get_class($model);
        $pattern = "model:{$modelClass}:*";
        
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Redis::keys($pattern);
                
                if (!empty($keys)) {
                    Redis::del($keys);
                }
            }
            
            $this->loggingService->logActivity(
                'model_cache_invalidated',
                $model,
                ['model_class' => $modelClass],
                'Model cache invalidated for: ' . class_basename($modelClass)
            );
            
            return true;
            
        } catch (\Exception $e) {
            $this->loggingService->logError(
                'Model cache invalidation failed',
                $e,
                ['model_class' => $modelClass],
                'error'
            );
            
            return false;
        }
    }
    
    /**
     * Estimate cache entry size
     */
    private function estimateSize($data): int
    {
        return strlen(serialize($data));
    }
    
    /**
     * Get cache key for a model
     */
    public function getModelCacheKey(Model $model, string $suffix = ''): string
    {
        $modelClass = get_class($model);
        $key = "model:{$modelClass}:{$model->getKey()}";
        
        if ($suffix) {
            $key .= ":{$suffix}";
        }
        
        return $key;
    }
    
    /**
     * Cache with dependencies
     */
    public function cacheWithDependencies(string $key, array $dependencies, callable $callback, ?int $ttl = null): mixed
    {
        $dependencyHash = md5(serialize($dependencies));
        $cacheKey = "{$key}:{$dependencyHash}";
        
        return $this->remember($cacheKey, $ttl ?? self::DEFAULT_TTL, $callback, self::CACHE_TAGS['query']);
    }
    
    /**
     * Batch cache operations
     */
    public function batchCache(array $operations): array
    {
        $results = [];
        
        foreach ($operations as $operation) {
            $type = $operation['type'] ?? 'model';
            $key = $operation['key'];
            $callback = $operation['callback'];
            $ttl = $operation['ttl'] ?? null;
            
            switch ($type) {
                case 'model':
                    $results[$key] = $this->cacheModelQuery($key, $callback, $ttl);
                    break;
                case 'query':
                    $results[$key] = $this->cacheQuery($key, $callback, $ttl);
                    break;
                case 'view':
                    $results[$key] = $this->cacheView($key, $callback, $ttl);
                    break;
                case 'api':
                    $results[$key] = $this->cacheApiResponse($key, $callback, $ttl);
                    break;
                case 'dashboard':
                    $results[$key] = $this->cacheDashboard($key, $callback, $ttl);
                    break;
                case 'report':
                    $results[$key] = $this->cacheReport($key, $callback, $ttl);
                    break;
                case 'statistics':
                    $results[$key] = $this->cacheStatistics($key, $callback, $ttl);
                    break;
                default:
                    $results[$key] = $this->remember($key, $ttl ?? self::DEFAULT_TTL, $callback, self::CACHE_TAGS['model']);
            }
        }
        
        return $results;
    }
}