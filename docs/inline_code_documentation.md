# Inline Code Documentation - Critical Code Sections

## Overview
This document provides comprehensive inline documentation for critical code sections of the Dokterku application, focusing on core business logic, performance optimizations, and complex workflows.

## Core Services Documentation

### 1. Cache Service Implementation

#### File: `app/Services/CacheService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Services\LoggingService;

/**
 * CacheService - Comprehensive caching layer for Dokterku application
 * 
 * Provides multi-layer caching with different cache types, TTL management,
 * and tag-based invalidation for optimal performance.
 * 
 * Cache Types:
 * - Model: Entity-level caching for database models
 * - Query: SQL query result caching
 * - View: Rendered view content caching
 * - API: API response caching
 * - Dashboard: Dashboard statistics caching
 * - Report: Report generation result caching
 * - Statistics: Aggregated statistics caching
 * 
 * @package App\Services
 * @author Dokterku Development Team
 * @version 2.0.0
 */
class CacheService
{
    /**
     * Cache key prefixes for different cache types
     * 
     * Prefixes prevent key collisions between different cache types
     * and enable efficient cache management and invalidation.
     */
    private const CACHE_PREFIXES = [
        'model' => 'model:',
        'query' => 'query:',
        'view' => 'view:',
        'api' => 'api:',
        'dashboard' => 'dashboard:',
        'report' => 'report:',
        'statistics' => 'stats:',
    ];

    /**
     * Cache TTL (Time To Live) values in seconds
     * 
     * Different cache types have different TTL values based on
     * data volatility and business requirements.
     */
    private const MODEL_CACHE_TTL = 1800;      // 30 minutes for model data
    private const QUERY_CACHE_TTL = 900;       // 15 minutes for query results
    private const VIEW_CACHE_TTL = 3600;       // 1 hour for view content
    private const API_CACHE_TTL = 300;         // 5 minutes for API responses
    private const DASHBOARD_CACHE_TTL = 600;   // 10 minutes for dashboard data
    private const REPORT_CACHE_TTL = 7200;     // 2 hours for reports
    private const STATISTICS_TTL = 1800;       // 30 minutes for statistics

    /**
     * Cache tags for organized cache invalidation
     * 
     * Tags allow bulk invalidation of related cache entries
     * without affecting unrelated cached data.
     */
    private const CACHE_TAGS = [
        'model' => ['model', 'data'],
        'query' => ['query', 'database'],
        'view' => ['view', 'ui'],
        'api' => ['api', 'response'],
        'dashboard' => ['dashboard', 'statistics'],
        'report' => ['report', 'analytics'],
        'statistics' => ['statistics', 'metrics'],
    ];

    private LoggingService $logger;

    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Cache model query results with automatic key generation
     * 
     * This method provides model-level caching for database entities,
     * automatically managing cache keys, TTL, and invalidation tags.
     * 
     * @param string $key Cache key identifier
     * @param callable $callback Function that returns data to cache
     * @param int|null $ttl Custom TTL override (optional)
     * @return mixed Cached or fresh data from callback
     * 
     * @example
     * $patients = $cacheService->cacheModelQuery('patients_active', function() {
     *     return Pasien::where('is_active', true)->get();
     * });
     */
    public function cacheModelQuery(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['model'] . $key;
        $ttl = $ttl ?? self::MODEL_CACHE_TTL;
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['model']);
    }

    /**
     * Cache database query results with performance optimization
     * 
     * Specialized caching for database query results with shorter TTL
     * to balance performance and data freshness.
     * 
     * @param string $key Cache key identifier
     * @param callable $callback Function that returns query results
     * @param int|null $ttl Custom TTL override (optional)
     * @return mixed Cached or fresh query results
     */
    public function cacheQuery(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['query'] . $key;
        $ttl = $ttl ?? self::QUERY_CACHE_TTL;
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['query']);
    }

    /**
     * Cache statistical data with extended TTL
     * 
     * Statistics often involve complex calculations and can be cached
     * for longer periods as they typically don't change frequently.
     * 
     * @param string $key Cache key identifier
     * @param callable $callback Function that calculates statistics
     * @param int|null $ttl Custom TTL override (optional)
     * @return mixed Cached or fresh statistics data
     */
    public function cacheStatistics(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = self::CACHE_PREFIXES['statistics'] . $key;
        $ttl = $ttl ?? self::STATISTICS_TTL;
        
        return $this->remember($cacheKey, $ttl, $callback, self::CACHE_TAGS['statistics']);
    }

    /**
     * Internal cache remember implementation with error handling
     * 
     * Provides centralized cache operation with fallback to callback
     * execution if cache operations fail.
     * 
     * @param string $key Complete cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Fallback function
     * @param array $tags Cache tags for invalidation
     * @return mixed Cached or fresh data
     */
    private function remember(string $key, int $ttl, callable $callback, array $tags): mixed
    {
        try {
            // Attempt cache operation with tags if supported
            if (method_exists(Cache::getStore(), 'tags')) {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            }
            
            // Fallback to basic caching without tags
            return Cache::remember($key, $ttl, $callback);
            
        } catch (\Exception $e) {
            // Log cache failure and execute callback directly
            $this->logger->logError('Cache operation failed', [
                'key' => $key,
                'error' => $e->getMessage(),
                'fallback' => 'direct_execution'
            ]);
            
            return $callback();
        }
    }

    /**
     * Invalidate cache by tag pattern
     * 
     * Efficiently removes related cache entries using tag-based invalidation.
     * Falls back to pattern-based key deletion if tags are not supported.
     * 
     * @param string $tag Tag identifier for bulk invalidation
     * @return bool Success status of invalidation operation
     */
    public function invalidateByTag(string $tag): bool
    {
        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags([$tag])->flush();
                return true;
            }
            
            // Fallback: Clear cache keys by prefix pattern
            $this->flushByPattern($tag);
            return true;
            
        } catch (\Exception $e) {
            $this->logger->logError('Cache invalidation failed', [
                'tag' => $tag,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
```

### 2. Query Optimization Service

#### File: `app/Services/QueryOptimizationService.php`

```php
<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * QueryOptimizationService - Advanced database query optimization
 * 
 * Provides intelligent query optimization including eager loading,
 * relationship management, and N+1 query prevention for optimal
 * database performance in the Dokterku application.
 * 
 * Key Features:
 * - Automatic eager loading based on model relationships
 * - N+1 query detection and prevention
 * - Bulk operation optimization
 * - Index utilization guidance
 * - Query performance analysis
 * 
 * @package App\Services
 * @author Dokterku Development Team
 * @version 2.0.0
 */
class QueryOptimizationService
{
    /**
     * Predefined relationship mappings for eager loading
     * 
     * These mappings define which relationships should be automatically
     * loaded to prevent N+1 queries based on common usage patterns.
     */
    private const EAGER_LOAD_RELATIONSHIPS = [
        'Pasien' => [
            'tindakan',                    // Patient's medical procedures
            'tindakan.jenisTindakan',      // Procedure types
            'tindakan.dokter',             // Attending doctors
            'tindakan.pendapatan',         // Revenue records
        ],
        'Tindakan' => [
            'pasien',                      // Patient information
            'jenisTindakan',               // Procedure type details
            'dokter',                      // Doctor information
            'pendapatan',                  // Associated revenue
            'jaspel',                      // Service fees
        ],
        'Pendapatan' => [
            'tindakan',                    // Source procedure
            'tindakan.pasien',             // Patient context
            'inputBy',                     // User who created record
            'validatedBy',                 // User who validated record
        ],
        'Jaspel' => [
            'tindakan',                    // Source procedure
            'user',                        // Recipient user
            'tindakan.pasien',             // Patient context
        ],
    ];

    /**
     * Optimize query with intelligent eager loading
     * 
     * Automatically determines and applies optimal eager loading
     * based on the model type and common access patterns.
     * 
     * @param Builder $query Eloquent query builder
     * @param string $modelClass Model class name for optimization
     * @param array $additionalRelations Additional relations to load
     * @return Builder Optimized query with eager loading
     * 
     * @example
     * $optimizedQuery = $optimizer->optimizeQuery(
     *     Pasien::query(),
     *     'Pasien',
     *     ['tindakan.dokter.spesialisasi']
     * );
     */
    public function optimizeQuery(Builder $query, string $modelClass, array $additionalRelations = []): Builder
    {
        $modelName = class_basename($modelClass);
        
        // Get predefined relationships for the model
        $relationships = self::EAGER_LOAD_RELATIONSHIPS[$modelName] ?? [];
        
        // Merge with additional relationships
        $allRelationships = array_unique(array_merge($relationships, $additionalRelations));
        
        if (!empty($allRelationships)) {
            $query->with($allRelationships);
        }
        
        return $query;
    }

    /**
     * Optimize paginated queries for large datasets
     * 
     * Applies pagination-specific optimizations including cursor-based
     * pagination for large datasets and intelligent LIMIT/OFFSET handling.
     * 
     * @param Builder $query Base query builder
     * @param int $perPage Records per page
     * @param string $cursorColumn Column for cursor-based pagination
     * @param mixed $cursorValue Last value for cursor pagination
     * @return Builder Optimized paginated query
     */
    public function optimizePaginatedQuery(
        Builder $query, 
        int $perPage = 15, 
        string $cursorColumn = 'id',
        mixed $cursorValue = null
    ): Builder {
        // Use cursor-based pagination for better performance on large datasets
        if ($cursorValue !== null) {
            $query->where($cursorColumn, '>', $cursorValue);
        }
        
        // Apply intelligent ordering for consistent pagination
        if (!$this->hasOrderBy($query)) {
            $query->orderBy($cursorColumn, 'asc');
        }
        
        // Limit results with small buffer for better memory usage
        $query->limit($perPage + 1); // +1 to detect if there are more records
        
        return $query;
    }

    /**
     * Optimize bulk insert operations
     * 
     * Provides optimized bulk insertion with chunking, transaction management,
     * and memory efficiency for large dataset operations.
     * 
     * @param string $table Target table name
     * @param array $data Array of records to insert
     * @param int $chunkSize Records per chunk for memory optimization
     * @return array Statistics about the bulk operation
     * 
     * @example
     * $result = $optimizer->optimizeBulkInsert('pasien', $patientData, 500);
     * // Returns: ['inserted' => 1000, 'chunks' => 2, 'time' => 1.23]
     */
    public function optimizeBulkInsert(string $table, array $data, int $chunkSize = 1000): array
    {
        $startTime = microtime(true);
        $totalInserted = 0;
        $chunkCount = 0;
        
        // Process data in chunks for memory efficiency
        $chunks = array_chunk($data, $chunkSize);
        
        DB::beginTransaction();
        try {
            foreach ($chunks as $chunk) {
                DB::table($table)->insert($chunk);
                $totalInserted += count($chunk);
                $chunkCount++;
                
                // Force garbage collection for large operations
                if ($chunkCount % 10 === 0) {
                    gc_collect_cycles();
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Bulk insert failed: " . $e->getMessage());
        }
        
        $executionTime = microtime(true) - $startTime;
        
        return [
            'inserted' => $totalInserted,
            'chunks' => $chunkCount,
            'time' => $executionTime,
            'records_per_second' => $totalInserted / $executionTime,
        ];
    }

    /**
     * Analyze query performance and provide optimization suggestions
     * 
     * Executes query with EXPLAIN analysis and provides actionable
     * optimization recommendations based on execution plan.
     * 
     * @param Builder $query Query to analyze
     * @return array Performance analysis with recommendations
     */
    public function analyzeQueryPerformance(Builder $query): array
    {
        $startTime = microtime(true);
        
        // Get the raw SQL and bindings
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        // Execute EXPLAIN for query analysis
        $explainResult = null;
        try {
            $explainResult = DB::select("EXPLAIN " . $sql, $bindings);
        } catch (\Exception $e) {
            // EXPLAIN not supported or query invalid
        }
        
        // Execute the actual query to measure performance
        $result = $query->get();
        $executionTime = microtime(true) - $startTime;
        
        // Analyze and provide recommendations
        $recommendations = $this->generateOptimizationRecommendations(
            $sql, 
            $explainResult, 
            $executionTime, 
            $result->count()
        );
        
        return [
            'sql' => $sql,
            'execution_time' => $executionTime,
            'result_count' => $result->count(),
            'explain_plan' => $explainResult,
            'recommendations' => $recommendations,
            'performance_rating' => $this->rateQueryPerformance($executionTime, $result->count()),
        ];
    }

    /**
     * Generate optimization recommendations based on query analysis
     * 
     * @param string $sql SQL query string
     * @param array|null $explainResult EXPLAIN query result
     * @param float $executionTime Query execution time
     * @param int $resultCount Number of results returned
     * @return array Actionable optimization recommendations
     */
    private function generateOptimizationRecommendations(
        string $sql, 
        ?array $explainResult, 
        float $executionTime, 
        int $resultCount
    ): array {
        $recommendations = [];
        
        // Performance-based recommendations
        if ($executionTime > 1.0) {
            $recommendations[] = "Query execution time is high ({$executionTime}s). Consider adding indexes or optimizing WHERE clauses.";
        }
        
        if ($resultCount > 1000) {
            $recommendations[] = "Large result set ({$resultCount} records). Consider pagination or filtering to reduce memory usage.";
        }
        
        // SQL pattern analysis
        if (preg_match('/SELECT \* FROM/', $sql)) {
            $recommendations[] = "Avoid SELECT * queries. Specify only needed columns to reduce data transfer.";
        }
        
        if (preg_match('/WHERE.*LIKE.*%.*%/', $sql)) {
            $recommendations[] = "Full-text search detected. Consider using full-text indexes for better performance.";
        }
        
        if (preg_match_all('/JOIN/i', $sql) > 3) {
            $recommendations[] = "Multiple JOINs detected. Consider eager loading or breaking complex queries into smaller ones.";
        }
        
        return $recommendations;
    }

    /**
     * Rate query performance based on execution time and result count
     * 
     * @param float $executionTime Query execution time in seconds
     * @param int $resultCount Number of results returned
     * @return string Performance rating (excellent/good/fair/poor)
     */
    private function rateQueryPerformance(float $executionTime, int $resultCount): string
    {
        $timeRatio = $executionTime / max($resultCount / 1000, 0.1); // Normalize by result count
        
        if ($timeRatio < 0.1) return 'excellent';
        if ($timeRatio < 0.5) return 'good';
        if ($timeRatio < 1.0) return 'fair';
        return 'poor';
    }

    /**
     * Check if query has ORDER BY clause
     * 
     * @param Builder $query Query builder to check
     * @return bool True if query has ordering
     */
    private function hasOrderBy(Builder $query): bool
    {
        return !empty($query->getQuery()->orders);
    }
}
```

### 3. Cacheable Trait Implementation

#### File: `app/Traits/Cacheable.php`

```php
<?php

namespace App\Traits;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

/**
 * Cacheable Trait - Automatic model-level caching functionality
 * 
 * This trait provides automatic caching capabilities for Eloquent models,
 * including cache key generation, TTL management, and intelligent
 * cache invalidation based on model events.
 * 
 * Features:
 * - Automatic cache key generation based on model attributes
 * - Configurable TTL per model
 * - Event-based cache invalidation
 * - Statistics caching for aggregated data
 * - Relationship caching optimization
 * 
 * @package App\Traits
 * @author Dokterku Development Team
 * @version 2.0.0
 */
trait Cacheable
{
    /**
     * Default cache TTL in seconds (30 minutes)
     * Can be overridden in individual models using $customCacheTtl property
     */
    private const DEFAULT_CACHE_TTL = 1800;

    /**
     * Boot the cacheable trait
     * 
     * Registers model event listeners for automatic cache invalidation
     * when models are created, updated, or deleted.
     */
    protected static function bootCacheable(): void
    {
        // Invalidate cache when model is saved or deleted
        static::saved(function ($model) {
            $model->invalidateModelCache();
        });

        static::deleted(function ($model) {
            $model->invalidateModelCache();
        });
    }

    /**
     * Get cache TTL for this model instance
     * 
     * Checks for model-specific TTL configuration or falls back to default.
     * Models can override this by defining a $customCacheTtl property.
     * 
     * @return int Cache TTL in seconds
     */
    protected function getCacheTtl(): int
    {
        return property_exists($this, 'customCacheTtl') 
            ? $this->customCacheTtl 
            : self::DEFAULT_CACHE_TTL;
    }

    /**
     * Generate unique cache key for model instance
     * 
     * Creates a unique cache key based on model class, primary key,
     * and updated_at timestamp for automatic invalidation.
     * 
     * @param string $suffix Optional suffix for key differentiation
     * @return string Unique cache key
     */
    public function getCacheKey(string $suffix = ''): string
    {
        $baseKey = sprintf(
            '%s:%s:%s',
            strtolower(class_basename(static::class)),
            $this->getKey(),
            $this->updated_at?->timestamp ?? time()
        );

        return $suffix ? "{$baseKey}:{$suffix}" : $baseKey;
    }

    /**
     * Cache model statistics with automatic key generation
     * 
     * Provides a convenient way to cache aggregated statistics for models
     * with automatic invalidation when underlying data changes.
     * 
     * @param string $statsKey Unique identifier for statistics
     * @param callable $callback Function that calculates statistics
     * @param int|null $customTtl Custom TTL override
     * @return mixed Cached or fresh statistics data
     * 
     * @example
     * $stats = Pasien::cacheStatistics('patient_demographics', function() {
     *     return [
     *         'total' => Pasien::count(),
     *         'by_gender' => Pasien::groupBy('jenis_kelamin')->selectRaw('jenis_kelamin, count(*) as count')->get(),
     *         'avg_age' => Pasien::selectRaw('AVG(YEAR(CURDATE()) - YEAR(tanggal_lahir)) as avg_age')->value('avg_age')
     *     ];
     * });
     */
    public static function cacheStatistics(string $statsKey, callable $callback, ?int $customTtl = null): mixed
    {
        $cacheService = app(CacheService::class);
        
        $modelClass = strtolower(class_basename(static::class));
        $cacheKey = "statistics:{$modelClass}:{$statsKey}";
        
        return $cacheService->cacheStatistics($cacheKey, $callback, $customTtl);
    }

    /**
     * Cache model query results with relationship optimization
     * 
     * Automatically caches query results with intelligent relationship
     * loading to prevent N+1 queries.
     * 
     * @param string $queryKey Unique identifier for query
     * @param callable $queryCallback Function that returns query builder
     * @param array $relationships Relationships to eager load
     * @param int|null $customTtl Custom TTL override
     * @return mixed Cached or fresh query results
     */
    public static function cacheQuery(
        string $queryKey, 
        callable $queryCallback, 
        array $relationships = [], 
        ?int $customTtl = null
    ): mixed {
        $cacheService = app(CacheService::class);
        
        $modelClass = strtolower(class_basename(static::class));
        $cacheKey = "query:{$modelClass}:{$queryKey}";
        
        return $cacheService->cacheQuery($cacheKey, function() use ($queryCallback, $relationships) {
            $query = $queryCallback();
            
            if (!empty($relationships)) {
                $query = $query->with($relationships);
            }
            
            return $query->get();
        }, $customTtl);
    }

    /**
     * Warm up cache for commonly accessed data
     * 
     * Pre-loads frequently accessed data into cache to improve
     * application performance during peak usage periods.
     * 
     * @return array Cache warming results with statistics
     */
    public static function warmUpCache(): array
    {
        $modelClass = static::class;
        $results = [];
        
        // Cache total count
        $results['total_count'] = static::cacheStatistics('total_count', function() {
            return static::count();
        });
        
        // Cache recent records (last 7 days)
        $results['recent'] = static::cacheQuery('recent_records', function() {
            return static::query()->where('created_at', '>=', now()->subDays(7));
        });
        
        // Cache basic statistics if model has common fields
        if (method_exists(new $modelClass, 'getCreatedAtColumn')) {
            $results['daily_stats'] = static::cacheStatistics('daily_creation_stats', function() {
                return static::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->get();
            });
        }
        
        return $results;
    }

    /**
     * Get cache statistics for this model
     * 
     * Provides information about cache usage, hit rates, and performance
     * metrics for monitoring and optimization purposes.
     * 
     * @return array Cache statistics and metrics
     */
    public static function getCacheStats(): array
    {
        $modelClass = class_basename(static::class);
        
        return [
            'model' => $modelClass,
            'cache_enabled' => true,
            'default_ttl' => self::DEFAULT_CACHE_TTL,
            'cache_prefix' => strtolower($modelClass),
            'auto_invalidation' => true,
            'supports_statistics' => true,
            'supports_query_caching' => true,
        ];
    }

    /**
     * Invalidate all cache entries for this model
     * 
     * Removes all cached data related to this model instance,
     * including statistics, queries, and related data.
     * 
     * @return bool Success status of cache invalidation
     */
    public function invalidateModelCache(): bool
    {
        $cacheService = app(CacheService::class);
        $modelClass = strtolower(class_basename(static::class));
        
        // Invalidate model-specific caches
        $success = $cacheService->invalidateByTag('model');
        
        // Also invalidate statistics cache
        $success = $success && $cacheService->invalidateByTag('statistics');
        
        return $success;
    }

    /**
     * Cache sorted results with automatic optimization
     * 
     * Provides cached sorting with intelligent query optimization
     * for frequently accessed sorted data.
     * 
     * @param string $sortKey Unique identifier for sort configuration
     * @param string $column Column to sort by
     * @param string $direction Sort direction (asc/desc)
     * @param int $limit Maximum number of results
     * @return mixed Cached sorted results
     */
    public static function cacheSorted(
        string $sortKey, 
        string $column, 
        string $direction = 'asc', 
        int $limit = 100
    ): mixed {
        return static::cacheQuery("sorted_{$sortKey}_{$column}_{$direction}", function() use ($column, $direction, $limit) {
            return static::query()
                ->orderBy($column, $direction)
                ->limit($limit);
        });
    }
}
```

## Model Implementation Examples

### 4. Enhanced Pasien Model with Caching

#### File: `app/Models/Pasien.php` (Key sections)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Cacheable;
use App\Traits\LogsActivity;
use Carbon\Carbon;

/**
 * Pasien Model - Patient management with advanced caching
 * 
 * Represents patient records with optimized caching, activity logging,
 * and relationship management for the Dokterku clinic system.
 * 
 * Features:
 * - Automatic age calculation from birth date
 * - Cached statistics for dashboard performance
 * - Relationship optimization with medical procedures
 * - Activity logging for audit compliance
 * - Soft deletion for data retention
 * 
 * @property int $id Primary key
 * @property string $no_rekam_medis Medical record number (unique)
 * @property string $nama Patient full name
 * @property Carbon $tanggal_lahir Date of birth
 * @property string $jenis_kelamin Gender (L/P)
 * @property string $alamat Address
 * @property string $no_telepon Phone number
 * @property string $email Email address
 * @property Carbon $created_at Creation timestamp
 * @property Carbon $updated_at Last update timestamp
 * @property Carbon $deleted_at Soft deletion timestamp
 * 
 * @package App\Models
 * @author Dokterku Development Team
 * @version 2.0.0
 */
class Pasien extends Model
{
    use SoftDeletes, Cacheable, LogsActivity;

    protected $table = 'pasien';
    
    /**
     * Custom cache TTL for patient data (1 hour)
     * Patient data changes less frequently than other entities
     */
    protected int $customCacheTtl = 3600;

    protected $fillable = [
        'no_rekam_medis',
        'nama', 
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'no_telepon',
        'email',
        'pekerjaan',
        'status_pernikahan',
        'kontak_darurat_nama',
        'kontak_darurat_telepon'
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Calculate patient age from birth date
     * 
     * Automatically calculates current age in years based on tanggal_lahir.
     * Result is cached temporarily to avoid repeated calculations.
     * 
     * @return int Patient age in years
     */
    public function getUmurAttribute(): int
    {
        if (!$this->tanggal_lahir) {
            return 0;
        }

        return $this->tanggal_lahir->diffInYears(Carbon::now());
    }

    /**
     * Get count of patient's medical procedures (tindakan)
     * 
     * Cached count to avoid N+1 queries when displaying patient lists
     * with procedure counts.
     * 
     * @return int Number of tindakan for this patient
     */
    public function getTindakanCountAttribute(): int
    {
        return $this->cacheQuery('tindakan_count_' . $this->id, function() {
            return $this->tindakan()->count();
        }, [], 1800); // Cache for 30 minutes
    }

    /**
     * Get patient's most recent medical procedure
     * 
     * Cached relationship to optimize dashboard displays and
     * patient summary views.
     * 
     * @return \App\Models\Tindakan|null Most recent tindakan
     */
    public function getLastTindakanAttribute(): ?\App\Models\Tindakan
    {
        return $this->cacheQuery('last_tindakan_' . $this->id, function() {
            return $this->tindakan()->latest('tanggal_tindakan');
        }, ['jenisTindakan', 'dokter'], 900); // Cache for 15 minutes
    }

    /**
     * Relationship: Patient's medical procedures
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tindakan()
    {
        return $this->hasMany(Tindakan::class, 'pasien_id');
    }

    /**
     * Scope: Filter patients by gender
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $gender Gender filter (L/P)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGender($query, string $gender)
    {
        return $query->where('jenis_kelamin', $gender);
    }

    /**
     * Get cached patient statistics for dashboard
     * 
     * Provides comprehensive patient statistics with automatic caching
     * for dashboard performance optimization.
     * 
     * @return array Patient statistics with demographics and activity
     */
    public static function getCachedStats(): array
    {
        return static::cacheStatistics('patient_stats', function() {
            $totalCount = static::count();
            $maleCount = static::where('jenis_kelamin', 'L')->count();
            $femaleCount = static::where('jenis_kelamin', 'P')->count();
            $recentCount = static::where('created_at', '>=', Carbon::now()->subDays(7))->count();
            
            // Calculate average age (performance optimized)
            $avgAge = static::selectRaw('AVG(YEAR(CURDATE()) - YEAR(tanggal_lahir)) as avg_age')
                ->whereNotNull('tanggal_lahir')
                ->value('avg_age');
            
            return [
                'total_count' => $totalCount,
                'male_count' => $maleCount,
                'female_count' => $femaleCount,
                'recent_count' => $recentCount,
                'avg_age' => round($avgAge ?? 0, 1),
                'gender_distribution' => [
                    'male_percentage' => $totalCount > 0 ? round(($maleCount / $totalCount) * 100, 1) : 0,
                    'female_percentage' => $totalCount > 0 ? round(($femaleCount / $totalCount) * 100, 1) : 0,
                ],
                'activity_metrics' => [
                    'growth_rate' => static::calculateGrowthRate(),
                    'active_patients' => static::getActivePatientsCount(),
                ]
            ];
        }, 1800); // Cache for 30 minutes
    }

    /**
     * Calculate patient growth rate over last 30 days
     * 
     * @return float Growth rate percentage
     */
    private static function calculateGrowthRate(): float
    {
        $currentMonth = static::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $previousMonth = static::whereBetween('created_at', [
            Carbon::now()->subDays(60),
            Carbon::now()->subDays(30)
        ])->count();
        
        if ($previousMonth == 0) return 0;
        
        return round((($currentMonth - $previousMonth) / $previousMonth) * 100, 2);
    }

    /**
     * Get count of patients with recent activity (tindakan)
     * 
     * @return int Number of active patients
     */
    private static function getActivePatientsCount(): int
    {
        return static::whereHas('tindakan', function($query) {
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        })->count();
    }
}
```

## Performance Optimization Documentation

### 5. Cache Response Middleware

#### File: `app/Http/Middleware/CacheResponseMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Services\LoggingService;

/**
 * CacheResponseMiddleware - HTTP response caching for API endpoints
 * 
 * Provides intelligent HTTP response caching with different strategies
 * for different endpoint types, automatic cache invalidation, and
 * performance monitoring for the Dokterku application.
 * 
 * Features:
 * - Route-specific cache TTL configuration
 * - HTTP method-based caching (GET, HEAD only)
 * - User-specific cache isolation
 * - Automatic cache headers (ETag, Last-Modified)
 * - Performance monitoring and logging
 * 
 * @package App\Http\Middleware
 * @author Dokterku Development Team
 * @version 2.0.0
 */
class CacheResponseMiddleware
{
    /**
     * Cache configuration for different route patterns
     * 
     * Defines TTL and caching strategy for different API endpoints
     * based on data volatility and business requirements.
     */
    private const CACHE_PATTERNS = [
        'api' => [
            'pattern' => '/^\/api\//',
            'ttl' => 300,                    // 5 minutes for API responses
            'user_specific' => true,         // Separate cache per user
            'vary_headers' => ['Authorization', 'Accept']
        ],
        'dashboard' => [
            'pattern' => '/dashboard/',
            'ttl' => 900,                    // 15 minutes for dashboard data
            'user_specific' => true,
            'vary_headers' => ['Authorization']
        ],
        'reports' => [
            'pattern' => '/reports/',
            'ttl' => 3600,                   // 1 hour for reports
            'user_specific' => true,
            'vary_headers' => ['Authorization', 'Accept-Language']
        ],
        'static' => [
            'pattern' => '/\.(css|js|png|jpg|gif)$/',
            'ttl' => 86400,                  // 24 hours for static assets
            'user_specific' => false,
            'vary_headers' => []
        ]
    ];

    private LoggingService $logger;

    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle incoming request with response caching
     * 
     * Implements intelligent response caching based on route patterns,
     * HTTP methods, and user context for optimal performance.
     * 
     * @param Request $request Incoming HTTP request
     * @param Closure $next Next middleware in pipeline
     * @return Response HTTP response (cached or fresh)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only cache GET and HEAD requests
        if (!in_array($request->method(), ['GET', 'HEAD'])) {
            return $next($request);
        }

        // Determine cache configuration for this route
        $cacheConfig = $this->getCacheConfig($request);
        
        if (!$cacheConfig) {
            return $next($request);
        }

        // Generate cache key based on request and user context
        $cacheKey = $this->generateCacheKey($request, $cacheConfig);

        // Check for cached response
        $cachedResponse = $this->getCachedResponse($cacheKey);
        
        if ($cachedResponse) {
            return $this->createCachedResponse($cachedResponse, $request);
        }

        // Generate fresh response
        $response = $next($request);

        // Cache successful responses only
        if ($response->getStatusCode() === 200) {
            $this->cacheResponse($cacheKey, $response, $cacheConfig);
        }

        // Add cache headers to response
        return $this->addCacheHeaders($response, $cacheConfig);
    }

    /**
     * Determine cache configuration for request route
     * 
     * Matches request URL against predefined patterns to determine
     * appropriate caching strategy and TTL.
     * 
     * @param Request $request HTTP request
     * @return array|null Cache configuration or null if not cacheable
     */
    private function getCacheConfig(Request $request): ?array
    {
        $path = $request->getPathInfo();

        foreach (self::CACHE_PATTERNS as $name => $config) {
            if (preg_match($config['pattern'], $path)) {
                return array_merge($config, ['name' => $name]);
            }
        }

        return null;
    }

    /**
     * Generate unique cache key for request
     * 
     * Creates cache key based on URL, query parameters, user context,
     * and relevant headers for proper cache isolation.
     * 
     * @param Request $request HTTP request
     * @param array $cacheConfig Cache configuration
     * @return string Unique cache key
     */
    private function generateCacheKey(Request $request, array $cacheConfig): string
    {
        $keyParts = [
            'response_cache',
            $cacheConfig['name'],
            md5($request->getPathInfo()),
            md5($request->getQueryString() ?? '')
        ];

        // Add user-specific identifier if required
        if ($cacheConfig['user_specific'] && $request->user()) {
            $keyParts[] = 'user_' . $request->user()->id;
        }

        // Add relevant headers to cache key
        foreach ($cacheConfig['vary_headers'] as $header) {
            if ($request->hasHeader($header)) {
                $keyParts[] = md5($header . ':' . $request->header($header));
            }
        }

        return implode(':', $keyParts);
    }

    /**
     * Retrieve cached response data
     * 
     * @param string $cacheKey Cache key identifier
     * @return array|null Cached response data or null if not found
     */
    private function getCachedResponse(string $cacheKey): ?array
    {
        try {
            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            $this->logger->logError('Cache retrieval failed', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache response data with metadata
     * 
     * Stores response content along with headers and metadata
     * for complete response reconstruction.
     * 
     * @param string $cacheKey Cache key identifier
     * @param Response $response HTTP response to cache
     * @param array $cacheConfig Cache configuration
     * @return bool Success status
     */
    private function cacheResponse(string $cacheKey, Response $response, array $cacheConfig): bool
    {
        try {
            $cacheData = [
                'content' => $response->getContent(),
                'status_code' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
                'cached_at' => time(),
                'etag' => md5($response->getContent()),
            ];

            Cache::put($cacheKey, $cacheData, $cacheConfig['ttl']);
            
            // Log cache operation for monitoring
            $this->logger->logPerformance('Response cached', [
                'cache_key' => $cacheKey,
                'ttl' => $cacheConfig['ttl'],
                'content_size' => strlen($cacheData['content']),
                'pattern' => $cacheConfig['name']
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->logError('Response caching failed', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create HTTP response from cached data
     * 
     * Reconstructs complete HTTP response from cached data including
     * proper headers and cache validation.
     * 
     * @param array $cachedData Cached response data
     * @param Request $request Original request for conditional headers
     * @return Response Reconstructed HTTP response
     */
    private function createCachedResponse(array $cachedData, Request $request): Response
    {
        // Check for conditional request headers (304 Not Modified)
        if ($this->shouldReturn304($request, $cachedData)) {
            return response('', 304)
                ->header('ETag', $cachedData['etag'])
                ->header('X-Cache', 'HIT-304');
        }

        $response = response($cachedData['content'], $cachedData['status_code']);

        // Restore original headers
        foreach ($cachedData['headers'] as $name => $values) {
            $response->header($name, $values);
        }

        // Add cache-specific headers
        $response->header('X-Cache', 'HIT')
                ->header('X-Cache-Age', time() - $cachedData['cached_at'])
                ->header('ETag', $cachedData['etag']);

        return $response;
    }

    /**
     * Add appropriate cache headers to response
     * 
     * Sets HTTP cache headers for client-side caching optimization
     * and cache validation.
     * 
     * @param Response $response HTTP response
     * @param array $cacheConfig Cache configuration
     * @return Response Response with cache headers
     */
    private function addCacheHeaders(Response $response, array $cacheConfig): Response
    {
        $maxAge = $cacheConfig['ttl'];
        
        $response->header('Cache-Control', "public, max-age={$maxAge}")
                ->header('X-Cache', 'MISS')
                ->header('ETag', md5($response->getContent()))
                ->header('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT');

        // Add Vary headers for proper cache invalidation
        if (!empty($cacheConfig['vary_headers'])) {
            $response->header('Vary', implode(', ', $cacheConfig['vary_headers']));
        }

        return $response;
    }

    /**
     * Check if request should return 304 Not Modified
     * 
     * Evaluates conditional request headers to determine if
     * content has not changed since last request.
     * 
     * @param Request $request HTTP request with conditional headers
     * @param array $cachedData Cached response data
     * @return bool True if should return 304 status
     */
    private function shouldReturn304(Request $request, array $cachedData): bool
    {
        // Check If-None-Match header (ETag validation)
        if ($request->hasHeader('If-None-Match')) {
            $clientETag = trim($request->header('If-None-Match'), '"');
            return $clientETag === $cachedData['etag'];
        }

        // Check If-Modified-Since header
        if ($request->hasHeader('If-Modified-Since')) {
            $clientTime = strtotime($request->header('If-Modified-Since'));
            return $clientTime >= $cachedData['cached_at'];
        }

        return false;
    }
}
```

## Testing Documentation

### 6. Unit Test Example with Documentation

#### File: `tests/Unit/CacheServiceBasicTest.php` (Key sections)

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;
use App\Services\LoggingService;

/**
 * CacheServiceBasicTest - Unit tests for CacheService functionality
 * 
 * Comprehensive testing suite for CacheService class covering:
 * - Basic cache operations (store, retrieve, forget)
 * - Cache key management and prefixing
 * - TTL (Time To Live) handling
 * - Error handling and graceful degradation
 * - Performance validation
 * - Cache statistics and monitoring
 * 
 * @package Tests\Unit
 * @author Dokterku Development Team
 * @version 2.0.0
 */
class CacheServiceBasicTest extends TestCase
{
    private CacheService $cacheService;
    private LoggingService $loggingService;

    /**
     * Set up test environment for each test method
     * 
     * Initializes clean CacheService instance with mocked dependencies
     * and ensures cache is cleared for test isolation.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock LoggingService to avoid external dependencies
        $this->loggingService = $this->createMock(LoggingService::class);
        $this->cacheService = new CacheService($this->loggingService);
        
        // Clear cache before each test for isolation
        Cache::flush();
    }

    /**
     * Test basic model query caching functionality
     * 
     * Validates that:
     * - Data is correctly stored and retrieved from cache
     * - Cache hits return stored data without executing callback
     * - Cache keys are properly prefixed for model queries
     * 
     * @test
     */
    public function test_it_can_cache_model_query()
    {
        $key = 'test_model_query';
        $expectedValue = 'cached_result';
        
        // First call should execute callback and cache result
        $result = $this->cacheService->cacheModelQuery($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
        
        // Second call should return cached value without executing callback
        $cachedResult = $this->cacheService->cacheModelQuery($key, function() {
            $this->fail('Callback should not be executed for cache hit');
            return 'should_not_be_called';
        });
        
        $this->assertEquals($expectedValue, $cachedResult);
        
        // Verify cache key structure
        $this->assertTrue(Cache::has('model:' . $key));
    }

    /**
     * Test cache key prefixing for different cache types
     * 
     * Ensures different cache types use appropriate prefixes
     * to prevent key collisions and enable organized cache management.
     * 
     * @test
     */
    public function test_it_uses_correct_cache_prefixes()
    {
        $key = 'test_prefix';
        $value = 'test_value';
        
        // Test model cache prefix
        $this->cacheService->cacheModelQuery($key, function() use ($value) {
            return $value;
        });
        
        $this->assertTrue(Cache::has('model:' . $key));
        $this->assertFalse(Cache::has('query:' . $key));
        
        // Test query cache prefix
        $this->cacheService->cacheQuery($key, function() use ($value) {
            return $value;
        });
        
        $this->assertTrue(Cache::has('query:' . $key));
        
        // Verify values are correctly isolated by prefix
        $this->assertEquals($value, Cache::get('model:' . $key));
        $this->assertEquals($value, Cache::get('query:' . $key));
    }

    /**
     * Test cache failure graceful degradation
     * 
     * Validates that cache failures don't break application functionality
     * and that fallback mechanisms work correctly.
     * 
     * @test
     */
    public function test_it_handles_cache_failures_gracefully()
    {
        // Mock cache failure by making Cache::remember throw exception
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Cache failure'));
        
        $key = 'test_failure';
        $expectedValue = 'fallback_value';
        
        // Service should execute callback directly when cache fails
        $result = $this->cacheService->cacheModelQuery($key, function() use ($expectedValue) {
            return $expectedValue;
        });
        
        $this->assertEquals($expectedValue, $result);
        
        // Verify logging service was called to log the error
        $this->loggingService->expects($this->once())
            ->method('logError')
            ->with('Cache operation failed', $this->isType('array'));
    }

    /**
     * Test cache statistics retrieval
     * 
     * Validates that cache service provides comprehensive statistics
     * for monitoring and optimization purposes.
     * 
     * @test
     */
    public function test_it_can_get_cache_statistics()
    {
        $stats = $this->cacheService->getStats();
        
        // Verify statistics structure
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('tags', $stats);
        $this->assertArrayHasKey('prefixes', $stats);
        $this->assertArrayHasKey('ttl_config', $stats);
        
        // Verify statistics values
        $this->assertTrue($stats['enabled']);
        $this->assertIsString($stats['driver']);
        $this->assertIsArray($stats['tags']);
        $this->assertIsArray($stats['prefixes']);
        $this->assertIsArray($stats['ttl_config']);
    }

    /**
     * Test cache key forgetting functionality
     * 
     * Validates that individual cache entries can be removed
     * and that removal is properly verified.
     * 
     * @test
     */
    public function test_it_can_forget_cache_by_key()
    {
        $key = 'test_forget';
        $value = 'test_value';
        
        // Cache a value first
        $this->cacheService->cacheModelQuery($key, function() use ($value) {
            return $value;
        });
        
        // Verify it's cached
        $this->assertTrue(Cache::has('model:' . $key));
        
        // Forget the cache
        $result = $this->cacheService->forget($key);
        
        // Verify forget operation succeeded
        $this->assertTrue($result);
        $this->assertFalse(Cache::has('model:' . $key));
    }

    /**
     * Test performance requirements for cache operations
     * 
     * Ensures cache operations meet performance benchmarks
     * required for production use.
     * 
     * @test
     */
    public function test_cache_operations_meet_performance_requirements()
    {
        $iterations = 100;
        $key = 'performance_test';
        $value = str_repeat('test_data_', 100); // ~1KB of data
        
        // Test cache write performance
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->cacheService->cacheModelQuery($key . '_' . $i, function() use ($value, $i) {
                return $value . $i;
            });
        }
        
        $writeTime = microtime(true) - $startTime;
        
        // Test cache read performance
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->cacheService->cacheModelQuery($key . '_' . $i, function() {
                $this->fail('Should not execute callback for cache hit');
            });
        }
        
        $readTime = microtime(true) - $startTime;
        
        // Performance assertions (adjust based on environment)
        $this->assertLessThan(1.0, $writeTime, "Cache write operations too slow: {$writeTime}s");
        $this->assertLessThan(0.1, $readTime, "Cache read operations too slow: {$readTime}s");
        
        // Calculate operations per second
        $writeOpsPerSecond = $iterations / $writeTime;
        $readOpsPerSecond = $iterations / $readTime;
        
        $this->assertGreaterThan(100, $writeOpsPerSecond, "Cache write performance too low");
        $this->assertGreaterThan(1000, $readOpsPerSecond, "Cache read performance too low");
    }
}
```

## Conclusion

This inline documentation provides comprehensive coverage of critical code sections in the Dokterku application, focusing on:

- **Performance Optimization**: CacheService and QueryOptimizationService implementations
- **Model Enhancement**: Cacheable trait and enhanced Pasien model examples
- **Middleware Functionality**: Response caching middleware for API optimization
- **Testing Standards**: Unit testing examples with performance validation

Each code section includes detailed docblocks, implementation explanations, and usage examples to facilitate maintenance and future development. The documentation emphasizes performance optimization, error handling, and production readiness standards.