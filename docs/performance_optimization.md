# Performance Optimization Documentation

## Overview
The Dokterku application implements comprehensive performance optimization strategies to ensure fast response times, efficient resource usage, and scalable architecture. This document outlines all implemented optimizations and their usage.

## Architecture Overview

### Performance Optimization Stack
1. **Cache Service** - Multi-layer caching system
2. **Query Optimization** - Database query performance improvements
3. **Response Caching** - HTTP response caching middleware
4. **Database Indexing** - Optimized database indexes
5. **Eager Loading** - Relationship preloading
6. **Model Caching** - Automated model-level caching

## Cache Service Implementation

### Core Cache Service
Location: `app/Services/CacheService.php`

The CacheService provides multiple cache types with different TTL configurations:

```php
use App\Services\CacheService;

$cacheService = new CacheService($loggingService);

// Cache model queries
$result = $cacheService->cacheModelQuery('patient_list', function() {
    return Pasien::with('tindakan')->get();
}, 3600);

// Cache dashboard data
$stats = $cacheService->cacheDashboard('daily_stats', function() {
    return [
        'patients' => Pasien::count(),
        'procedures' => Tindakan::count(),
        'revenue' => Pendapatan::sum('jumlah'),
    ];
});
```

### Cache Types and TTL Configuration

| Cache Type | TTL | Use Case |
|------------|-----|----------|
| **Model Cache** | 30 minutes | Model queries and relationships |
| **Query Cache** | 30 minutes | Raw database queries |
| **View Cache** | 15 minutes | Rendered view fragments |
| **API Cache** | 5 minutes | API response caching |
| **Dashboard Cache** | 1 hour | Dashboard statistics |
| **Report Cache** | 24 hours | Financial reports |
| **Statistics Cache** | 24 hours | Aggregated statistics |

### Cache Tags for Organized Management

```php
// Cache tags for different data types
'model' => 'model_cache',
'query' => 'query_cache',
'view' => 'view_cache',
'api' => 'api_cache',
'dashboard' => 'dashboard_cache',
'report' => 'report_cache',
'statistics' => 'statistics_cache'
```

## Model-Level Caching

### Cacheable Trait
Location: `app/Traits/Cacheable.php`

All models use the `Cacheable` trait for automatic caching:

```php
use App\Traits\Cacheable;

class Pasien extends Model
{
    use Cacheable;
    
    protected int $customCacheTtl = 3600; // 1 hour
    
    // Cached relationships
    public function tindakan()
    {
        return $this->hasMany(Tindakan::class);
    }
    
    // Cached computed attributes
    public function getUmurAttribute()
    {
        return $this->cacheAttribute('umur', function() {
            return $this->tanggal_lahir?->age;
        });
    }
    
    // Cached statistics
    public static function getCachedStats()
    {
        return static::cacheStatistics('patient_stats', function() {
            return [
                'total_count' => static::count(),
                'male_count' => static::where('jenis_kelamin', 'L')->count(),
                'female_count' => static::where('jenis_kelamin', 'P')->count(),
            ];
        });
    }
}
```

### Cache Methods Available

| Method | Purpose | TTL |
|--------|---------|-----|
| `cacheQuery()` | Cache query results | Custom/Default |
| `cacheAttribute()` | Cache computed attributes | Custom/Default |
| `cacheRelation()` | Cache relationships | Custom/Default |
| `cacheCount()` | Cache count queries | Custom/Default |
| `cacheStatistics()` | Cache aggregated data | 24 hours |
| `cacheCollection()` | Cache model collections | Custom/Default |

## Query Optimization Service

### Query Optimization Service
Location: `app/Services/QueryOptimizationService.php`

Provides automatic query optimization with eager loading:

```php
use App\Services\QueryOptimizationService;

$optimizer = new QueryOptimizationService($loggingService);

// Optimize a query with eager loading
$query = Tindakan::query();
$optimizedQuery = $optimizer->optimizeQuery($query, Tindakan::class);

// Optimize count queries
$count = $optimizer->optimizeCountQuery($query, Tindakan::class);

// Optimize paginated queries
$paginatedQuery = $optimizer->optimizePaginatedQuery($query, Tindakan::class, 1, 15);

// Optimize search queries
$searchQuery = $optimizer->optimizeSearchQuery($query, Tindakan::class, 'search term');
```

### Automatic Eager Loading Relationships

```php
// Pre-configured eager loading per model
private const EAGER_LOAD_RELATIONSHIPS = [
    'Pasien' => ['tindakan', 'tindakan.jenisTindakan', 'tindakan.dokter'],
    'Tindakan' => ['pasien', 'jenisTindakan', 'dokter', 'pendapatan', 'jaspel'],
    'Pendapatan' => ['tindakan', 'tindakan.pasien', 'inputBy', 'validasiBy'],
    'Pengeluaran' => ['inputBy', 'validasiBy'],
    'Dokter' => ['user', 'tindakan', 'tindakan.pasien', 'inputBy'],
    'User' => ['role', 'customRole', 'dokter', 'pegawai'],
];
```

### Query Optimization Features

1. **Eager Loading**: Automatic relationship preloading
2. **Index Hints**: Database index usage optimization
3. **Select Optimization**: Only select necessary columns
4. **Count Optimization**: Efficient count queries for large datasets
5. **Cursor Pagination**: For large datasets (>100 pages)
6. **Full-Text Search**: When available, uses MySQL FULLTEXT indexes

## Response Caching Middleware

### Cache Response Middleware
Location: `app/Http/Middleware/CacheResponseMiddleware.php`

Automatically caches HTTP responses based on URL patterns:

```php
// Cache patterns and TTL
private const CACHE_PATTERNS = [
    'api' => [
        'pattern' => '/^\/api\//',
        'ttl' => 300, // 5 minutes
        'methods' => ['GET'],
    ],
    'dashboard' => [
        'pattern' => '/dashboard/',
        'ttl' => 900, // 15 minutes
        'methods' => ['GET'],
    ],
    'reports' => [
        'pattern' => '/reports/',
        'ttl' => 1800, // 30 minutes
        'methods' => ['GET'],
    ],
];
```

### Usage in Routes

```php
// Apply to specific routes
Route::middleware(['cache.response'])->group(function () {
    Route::get('/api/patients', [PatientController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Or apply to route groups
Route::prefix('api')->middleware(['cache.response'])->group(function () {
    // All API routes will be cached
});
```

## Database Indexing Strategy

### Index Migration
Location: `database/migrations/2025_07_15_add_database_indexes_for_performance.php`

Comprehensive database indexing for optimal query performance:

#### Single Column Indexes
```sql
-- Patient table indexes
CREATE INDEX idx_pasien_created_at ON pasien(created_at);
CREATE INDEX idx_pasien_jenis_kelamin ON pasien(jenis_kelamin);
CREATE INDEX idx_pasien_nama ON pasien(nama);

-- Tindakan table indexes
CREATE INDEX idx_tindakan_pasien_id ON tindakan(pasien_id);
CREATE INDEX idx_tindakan_dokter_id ON tindakan(dokter_id);
CREATE INDEX idx_tindakan_status ON tindakan(status);
```

#### Composite Indexes
```sql
-- Common query pattern indexes
CREATE INDEX idx_tindakan_pasien_tanggal ON tindakan(pasien_id, tanggal_tindakan);
CREATE INDEX idx_tindakan_dokter_tanggal ON tindakan(dokter_id, tanggal_tindakan);
CREATE INDEX idx_pendapatan_status_created ON pendapatan(status, created_at);
```

#### Full-Text Search Indexes
```sql
-- For MySQL full-text search
ALTER TABLE pasien ADD FULLTEXT idx_pasien_fulltext (nama, no_rekam_medis, alamat);
ALTER TABLE dokter ADD FULLTEXT idx_dokter_fulltext (nama, spesialisasi);
ALTER TABLE users ADD FULLTEXT idx_users_fulltext (name, email);
```

### Index Coverage by Table

| Table | Single Indexes | Composite Indexes | Full-Text |
|-------|---------------|-------------------|-----------|
| `pasien` | 7 | 1 | 1 |
| `tindakan` | 11 | 5 | 0 |
| `pendapatan` | 8 | 4 | 0 |
| `pengeluaran` | 7 | 3 | 0 |
| `dokter` | 7 | 2 | 1 |
| `users` | 7 | 3 | 1 |
| `audit_logs` | 7 | 4 | 0 |

## Performance Monitoring

### Automatic Performance Logging

All performance optimizations are automatically logged:

```php
// Performance metrics logged:
- Cache hit/miss ratios
- Query execution times
- Memory usage
- Database query counts
- API response times
- Search operation performance
```

### Performance Log Analysis

```php
// View performance logs
$performanceLogs = DB::table('performance_logs')
    ->where('operation', 'cache_access')
    ->where('created_at', '>=', Carbon::now()->subHours(24))
    ->get();

// Analyze slow operations
$slowOperations = DB::table('performance_logs')
    ->where('duration', '>', 1.0)
    ->orderBy('duration', 'desc')
    ->take(100)
    ->get();
```

## Cache Management

### Cache Warm-up

```php
// Warm up cache with common queries
$cacheService = app(CacheService::class);
$warmedData = $cacheService->warmUp();

// Warm up model-specific cache
$patientCache = Pasien::warmUpCache();
$tindakanCache = Tindakan::warmUpCache();
```

### Cache Invalidation

```php
// Invalidate specific cache
$cacheService->forget('patient_list');

// Invalidate by tag
$cacheService->flushTag('dashboard');

// Invalidate model cache
$patient = Pasien::find(1);
$patient->invalidateModelCache();

// Clear all cache
$cacheService->flushAll();
```

### Cache Statistics

```php
// Get cache statistics
$stats = $cacheService->getStats();

// Get model cache statistics
$modelStats = Pasien::getCacheStats();
```

## Bulk Operations Optimization

### Optimized Bulk Operations

```php
// Bulk insert optimization
$optimizer = new QueryOptimizationService($loggingService);
$result = $optimizer->optimizeBulkOperation('insert', Pasien::class, $data);

// Bulk update optimization
$result = $optimizer->optimizeBulkOperation('update', Pasien::class, $data);

// Bulk delete optimization
$result = $optimizer->optimizeBulkOperation('delete', Pasien::class, $ids);
```

### Bulk Operation Features

1. **Chunked Processing**: Process large datasets in chunks
2. **Disabled Events**: Skip model events for performance
3. **Batch Queries**: Use database batch operations
4. **Memory Management**: Efficient memory usage patterns

## API Response Optimization

### Response Caching Headers

```php
// Automatic cache headers
X-Cache: HIT/MISS
X-Cache-TTL: 300
Cache-Control: public, max-age=300
ETag: md5-hash-of-content
Last-Modified: timestamp
```

### API Performance Features

1. **Conditional Requests**: ETag and Last-Modified support
2. **Response Compression**: Automatic gzip compression
3. **Cache Invalidation**: Smart cache invalidation on updates
4. **Rate Limiting**: Prevent API abuse

## Configuration

### Environment Variables

```env
# Cache Configuration
CACHE_DRIVER=redis
CACHE_PREFIX=dokterku_
CACHE_ENABLED=true

# Performance Settings
LOG_PERFORMANCE=true
CACHE_QUERIES=true
CACHE_VIEWS=true
CACHE_RESPONSES=true

# Database Optimization
DB_SLOW_QUERY_TIME=2.0
DB_LOG_QUERIES=true
```

### Cache Configuration

```php
// config/cache.php
'enabled' => env('CACHE_ENABLED', true),
'default' => env('CACHE_DRIVER', 'file'),
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'prefix' => env('CACHE_PREFIX', 'dokterku_'),
    ],
],
```

## Performance Best Practices

### 1. Query Optimization
- Use eager loading for relationships
- Implement proper database indexes
- Use query builders efficiently
- Avoid N+1 query problems

### 2. Cache Strategy
- Cache frequently accessed data
- Use appropriate TTL values
- Implement cache invalidation
- Monitor cache hit ratios

### 3. Database Design
- Normalize data appropriately
- Use proper data types
- Implement foreign key constraints
- Regular database maintenance

### 4. Application Architecture
- Use service layers for business logic
- Implement proper error handling
- Use queues for heavy operations
- Monitor application performance

## Monitoring and Alerts

### Performance Metrics to Monitor

1. **Cache Hit Ratio**: Should be > 80%
2. **Database Query Time**: Should be < 1 second
3. **API Response Time**: Should be < 500ms
4. **Memory Usage**: Monitor for memory leaks
5. **CPU Usage**: Monitor for performance bottlenecks

### Alert Thresholds

```php
// Performance alert thresholds
'slow_query_threshold' => 2.0, // seconds
'cache_miss_threshold' => 0.2, // 20% miss rate
'memory_usage_threshold' => 0.8, // 80% memory usage
'response_time_threshold' => 1.0, // 1 second
```

## Troubleshooting Performance Issues

### Common Performance Issues

1. **Slow Database Queries**
   - Check query execution plans
   - Verify indexes are being used
   - Optimize query structure

2. **Cache Misses**
   - Review cache TTL settings
   - Check cache invalidation logic
   - Monitor cache storage usage

3. **Memory Leaks**
   - Review model relationships
   - Check for circular references
   - Monitor memory usage patterns

4. **High CPU Usage**
   - Profile application code
   - Check for infinite loops
   - Review query optimization

### Debug Tools

```php
// Enable query logging
DB::enableQueryLog();

// Check cache status
$cacheService->getStats();

// Analyze query performance
$optimizer->analyzeQueryPerformance($query, Pasien::class);

// Monitor memory usage
memory_get_usage(true);
```

## Future Enhancements

### Planned Optimizations

1. **Redis Cluster**: For horizontal scaling
2. **CDN Integration**: For static asset caching
3. **Database Sharding**: For large datasets
4. **Microservices**: For service-specific optimization
5. **GraphQL**: For efficient data fetching

### Performance Roadmap

1. **Phase 1**: Basic caching and indexing ✅
2. **Phase 2**: Advanced query optimization ✅
3. **Phase 3**: Response caching and monitoring ✅
4. **Phase 4**: Horizontal scaling (planned)
5. **Phase 5**: Advanced analytics (planned)

## Conclusion

The Dokterku application implements a comprehensive performance optimization strategy that includes:

- **Multi-layer caching** with appropriate TTL configurations
- **Database indexing** for optimal query performance
- **Query optimization** with eager loading and smart query building
- **Response caching** for improved user experience
- **Performance monitoring** with detailed logging and analytics

This implementation ensures the application can handle increasing loads while maintaining fast response times and efficient resource usage.

## Commands Reference

```bash
# Cache management commands
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Database optimization
php artisan migrate
php artisan db:seed

# Performance monitoring
php artisan dokterku:cleanup-logs
php artisan queue:work

# Cache warm-up (custom command)
php artisan cache:warm-up
```

For more information, refer to the individual service documentation and the Laravel caching documentation.