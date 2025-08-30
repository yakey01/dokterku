<?php

namespace App\Services;

use App\Models\SystemMetric;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;

class SystemMonitoringService
{
    // Collect all system metrics
    public function collectAllMetrics()
    {
        try {
            $this->collectSystemMetrics();
            $this->collectDatabaseMetrics();
            $this->collectCacheMetrics();
            $this->collectQueueMetrics();
            $this->collectStorageMetrics();
            $this->collectPerformanceMetrics();
            $this->collectSecurityMetrics();
            $this->collectApplicationMetrics();
            
            Log::info('System metrics collected successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to collect system metrics: ' . $e->getMessage());
            return false;
        }
    }

    // Collect system-level metrics
    private function collectSystemMetrics()
    {
        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryPercent = ($memoryUsage / $memoryLimit) * 100;
        
        SystemMetric::recordSystemMetric(
            'memory_usage',
            $memoryPercent,
            [
                'used_bytes' => $memoryUsage,
                'limit_bytes' => $memoryLimit,
                'formatted_used' => $this->formatBytes($memoryUsage),
                'formatted_limit' => $this->formatBytes($memoryLimit),
            ],
            85 // Alert if memory usage > 85%
        );

        // Peak memory usage
        $peakMemory = memory_get_peak_usage(true);
        $peakPercent = ($peakMemory / $memoryLimit) * 100;
        
        SystemMetric::recordSystemMetric(
            'peak_memory_usage',
            $peakPercent,
            [
                'peak_bytes' => $peakMemory,
                'formatted_peak' => $this->formatBytes($peakMemory),
            ],
            90 // Alert if peak memory > 90%
        );

        // Load average (Unix systems)
        if (function_exists('sys_getloadavg')) {
            $loadAvg = sys_getloadavg();
            SystemMetric::recordSystemMetric(
                'load_average_1m',
                $loadAvg[0],
                [
                    '1min' => $loadAvg[0],
                    '5min' => $loadAvg[1],
                    '15min' => $loadAvg[2],
                ],
                4.0 // Alert if load > 4.0
            );
        }

        // Disk usage
        $diskTotal = disk_total_space(storage_path());
        $diskFree = disk_free_space(storage_path());
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = ($diskUsed / $diskTotal) * 100;
        
        SystemMetric::recordSystemMetric(
            'disk_usage',
            $diskPercent,
            [
                'total_bytes' => $diskTotal,
                'used_bytes' => $diskUsed,
                'free_bytes' => $diskFree,
                'formatted_total' => $this->formatBytes($diskTotal),
                'formatted_used' => $this->formatBytes($diskUsed),
                'formatted_free' => $this->formatBytes($diskFree),
            ],
            80 // Alert if disk usage > 80%
        );
    }

    // Collect database metrics
    private function collectDatabaseMetrics()
    {
        $start = microtime(true);
        
        // Connection test
        try {
            DB::connection()->getPdo();
            $connectionTime = (microtime(true) - $start) * 1000; // ms
            
            SystemMetric::recordDatabaseMetric(
                'connection_time',
                $connectionTime,
                ['database' => config('database.default')],
                1000 // Alert if connection time > 1000ms
            );
        } catch (\Exception $e) {
            SystemMetric::recordDatabaseMetric(
                'connection_time',
                9999,
                ['error' => $e->getMessage()],
                1000
            );
        }

        // Query performance test
        $queryStart = microtime(true);
        try {
            $result = DB::select('SELECT COUNT(*) as count FROM users');
            $queryTime = (microtime(true) - $queryStart) * 1000;
            
            SystemMetric::recordDatabaseMetric(
                'query_time',
                $queryTime,
                ['query' => 'SELECT COUNT(*) FROM users', 'result' => $result[0]->count],
                500 // Alert if query time > 500ms
            );
        } catch (\Exception $e) {
            SystemMetric::recordDatabaseMetric(
                'query_time',
                9999,
                ['error' => $e->getMessage()],
                500
            );
        }

        // Database size (adapt to current connection)
        try {
            $defaultConnection = config('database.default');
            $dbSize = 0;
            
            if ($defaultConnection === 'mysql') {
                $dbName = config('database.connections.mysql.database');
                $sizeResult = DB::select("
                    SELECT 
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                    FROM information_schema.tables 
                    WHERE table_schema = ?
                ", [$dbName]);
                $dbSize = $sizeResult[0]->size_mb ?? 0;
            } elseif ($defaultConnection === 'sqlite') {
                // For SQLite, get file size
                $dbPath = config('database.connections.sqlite.database');
                if (file_exists($dbPath)) {
                    $dbSize = round(filesize($dbPath) / 1024 / 1024, 2);
                }
            }
            
            SystemMetric::recordDatabaseMetric(
                'database_size',
                $dbSize,
                ['database' => $defaultConnection, 'unit' => 'MB'],
                1000 // Alert if database > 1GB
            );
        } catch (\Exception $e) {
            Log::warning('Could not collect database size metric: ' . $e->getMessage());
        }

        // Active connections
        try {
            $connections = DB::select('SHOW STATUS WHERE Variable_name = "Threads_connected"');
            $activeConnections = $connections[0]->Value ?? 0;
            
            SystemMetric::recordDatabaseMetric(
                'active_connections',
                $activeConnections,
                ['connections' => $activeConnections],
                50 // Alert if connections > 50
            );
        } catch (\Exception $e) {
            Log::warning('Could not collect database connections metric: ' . $e->getMessage());
        }
    }

    // Collect cache metrics
    private function collectCacheMetrics()
    {
        $start = microtime(true);
        
        // Cache response time
        try {
            $testKey = 'system_monitor_test_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            $cacheTime = (microtime(true) - $start) * 1000;
            
            SystemMetric::recordCacheMetric(
                'cache_response_time',
                $cacheTime,
                [
                    'driver' => config('cache.default'),
                    'test_successful' => $retrieved === $testValue,
                ],
                100 // Alert if cache response > 100ms
            );
        } catch (\Exception $e) {
            SystemMetric::recordCacheMetric(
                'cache_response_time',
                9999,
                ['error' => $e->getMessage()],
                100
            );
        }

        // Redis metrics (if using Redis)
        if (config('cache.default') === 'redis') {
            try {
                $redis = Redis::connection();
                $info = $redis->info();
                
                SystemMetric::recordCacheMetric(
                    'redis_memory_usage',
                    $info['used_memory'] / 1024 / 1024, // MB
                    [
                        'used_memory_human' => $info['used_memory_human'],
                        'connected_clients' => $info['connected_clients'],
                        'total_commands_processed' => $info['total_commands_processed'],
                    ],
                    512 // Alert if Redis memory > 512MB
                );
            } catch (\Exception $e) {
                Log::warning('Could not collect Redis metrics: ' . $e->getMessage());
            }
        }
    }

    // Collect queue metrics
    private function collectQueueMetrics()
    {
        try {
            // Failed jobs count
            $failedJobs = DB::table('failed_jobs')->count();
            
            SystemMetric::recordQueueMetric(
                'failed_jobs',
                $failedJobs,
                ['table' => 'failed_jobs'],
                10 // Alert if failed jobs > 10
            );

            // Recent jobs (if jobs table exists)
            if (DB::getSchemaBuilder()->hasTable('jobs')) {
                $pendingJobs = DB::table('jobs')->count();
                
                SystemMetric::recordQueueMetric(
                    'pending_jobs',
                    $pendingJobs,
                    ['table' => 'jobs'],
                    100 // Alert if pending jobs > 100
                );
            }
        } catch (\Exception $e) {
            Log::warning('Could not collect queue metrics: ' . $e->getMessage());
        }
    }

    // Collect storage metrics
    private function collectStorageMetrics()
    {
        $disks = ['local', 'public'];
        
        foreach ($disks as $disk) {
            try {
                $storage = Storage::disk($disk);
                $path = $storage->path('');
                
                // Check if path exists
                if (!file_exists($path)) {
                    continue;
                }
                
                $size = $this->getDirectorySize($path);
                
                SystemMetric::recordStorageMetric(
                    "storage_usage_{$disk}",
                    $size / 1024 / 1024, // MB
                    [
                        'disk' => $disk,
                        'path' => $path,
                        'formatted_size' => $this->formatBytes($size),
                    ],
                    1024 // Alert if storage > 1GB
                );
            } catch (\Exception $e) {
                Log::warning("Could not collect storage metrics for disk {$disk}: " . $e->getMessage());
            }
        }
    }

    // Collect performance metrics
    private function collectPerformanceMetrics()
    {
        // Response time test
        $start = microtime(true);
        try {
            // Test a simple route or endpoint
            $response = file_get_contents(url('/'));
            $responseTime = (microtime(true) - $start) * 1000;
            
            SystemMetric::recordPerformanceMetric(
                'response_time',
                $responseTime,
                [
                    'url' => url('/'),
                    'response_size' => strlen($response),
                ],
                2000 // Alert if response time > 2000ms
            );
        } catch (\Exception $e) {
            SystemMetric::recordPerformanceMetric(
                'response_time',
                9999,
                ['error' => $e->getMessage()],
                2000
            );
        }

        // Opcache status (if available)
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            if ($opcache) {
                $hitRate = $opcache['opcache_statistics']['opcache_hit_rate'];
                
                SystemMetric::recordPerformanceMetric(
                    'opcache_hit_rate',
                    $hitRate,
                    [
                        'hits' => $opcache['opcache_statistics']['hits'],
                        'misses' => $opcache['opcache_statistics']['misses'],
                        'memory_usage' => $opcache['memory_usage'],
                    ],
                    70 // Alert if hit rate < 70% (inverted logic)
                );
            }
        }
    }

    // Collect security metrics
    private function collectSecurityMetrics()
    {
        // Recent failed logins (if audit logs exist)
        try {
            $failedLogins = DB::table('audit_logs')
                ->where('action', 'login_failed')
                ->where('created_at', '>=', now()->subHour())
                ->count();
            
            SystemMetric::recordSecurityMetric(
                'failed_logins_1h',
                $failedLogins,
                ['period' => '1 hour'],
                10 // Alert if failed logins > 10 per hour
            );
        } catch (\Exception $e) {
            Log::warning('Could not collect security metrics: ' . $e->getMessage());
        }

        // SSL certificate check
        try {
            $url = config('app.url');
            if (str_starts_with($url, 'https://')) {
                $host = parse_url($url, PHP_URL_HOST);
                $context = stream_context_create([
                    'ssl' => [
                        'capture_peer_cert' => true,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                
                $stream = stream_socket_client("ssl://{$host}:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
                if ($stream) {
                    $cert = stream_context_get_params($stream)['options']['ssl']['peer_certificate'];
                    $certInfo = openssl_x509_parse($cert);
                    $expiryDate = $certInfo['validTo_time_t'];
                    $daysUntilExpiry = ($expiryDate - time()) / 86400;
                    
                    SystemMetric::recordSecurityMetric(
                        'ssl_certificate_days',
                        $daysUntilExpiry,
                        [
                            'host' => $host,
                            'expiry_date' => date('Y-m-d', $expiryDate),
                            'issuer' => $certInfo['issuer']['CN'] ?? 'Unknown',
                        ],
                        30 // Alert if SSL expires in < 30 days
                    );
                    
                    fclose($stream);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not collect SSL certificate metrics: ' . $e->getMessage());
        }
    }

    // Collect application metrics
    private function collectApplicationMetrics()
    {
        // User activity
        try {
            $activeUsers = DB::table('users')
                ->where('last_login_at', '>=', now()->subHour())
                ->count();
            
            SystemMetric::recordApplicationMetric(
                'active_users_1h',
                $activeUsers,
                ['period' => '1 hour'],
                null // No alert threshold
            );
        } catch (\Exception $e) {
            Log::warning('Could not collect user activity metrics: ' . $e->getMessage());
        }

        // Total users
        try {
            $totalUsers = DB::table('users')->count();
            
            SystemMetric::recordApplicationMetric(
                'total_users',
                $totalUsers,
                ['table' => 'users'],
                null // No alert threshold
            );
        } catch (\Exception $e) {
            Log::warning('Could not collect total users metric: ' . $e->getMessage());
        }

        // Session count
        try {
            $activeSessions = DB::table('sessions')
                ->where('last_activity', '>=', now()->subHour()->timestamp)
                ->count();
            
            SystemMetric::recordApplicationMetric(
                'active_sessions',
                $activeSessions,
                ['period' => '1 hour'],
                null // No alert threshold
            );
        } catch (\Exception $e) {
            Log::warning('Could not collect session metrics: ' . $e->getMessage());
        }
    }

    // Helper methods
    private function parseMemoryLimit($limit)
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;
        
        switch ($last) {
            case 'g':
                $limit *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $limit *= 1024 * 1024;
                break;
            case 'k':
                $limit *= 1024;
                break;
        }
        
        return $limit;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function getDirectorySize($directory)
    {
        $size = 0;
        
        if (is_dir($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }
        
        return $size;
    }
}