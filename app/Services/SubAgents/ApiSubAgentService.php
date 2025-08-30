<?php

namespace App\Services\SubAgents;

use App\Core\Base\BaseService;
use App\Services\JaspelReportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Exception;

/**
 * API Sub-Agent Service
 * 
 * Specialized service for handling external API integrations
 * with rate limiting, caching, and performance monitoring
 */
class ApiSubAgentService extends BaseService
{
    protected string $cachePrefix = 'api_subagent_';
    protected int $defaultCacheTtl = 300; // 5 minutes
    protected int $rateLimit = 100; // requests per minute
    protected array $endpointConfigs = [];

    protected JaspelReportService $jaspelService;

    public function __construct(JaspelReportService $jaspelService)
    {
        $this->jaspelService = $jaspelService;
        $this->initializeEndpointConfigs();
    }

    /**
     * Initialize endpoint configurations
     */
    protected function initializeEndpointConfigs(): void
    {
        $this->endpointConfigs = [
            'jaspel_reports' => [
                'cache_ttl' => 300,
                'rate_limit' => 60,
                'auth_required' => true,
                'roles_allowed' => ['bendahara', 'admin', 'manajer']
            ],
            'jaspel_summary' => [
                'cache_ttl' => 600,
                'rate_limit' => 30,
                'auth_required' => true,
                'roles_allowed' => ['bendahara', 'admin']
            ],
            'jaspel_export' => [
                'cache_ttl' => 60,
                'rate_limit' => 10,
                'auth_required' => true,
                'roles_allowed' => ['bendahara']
            ]
        ];
    }

    /**
     * Handle jaspel reports API request with rate limiting and caching
     */
    public function handleJaspelReportsApi(string $role = 'semua', array $filters = [], ?int $userId = null): JsonResponse
    {
        $endpoint = 'jaspel_reports';
        $clientKey = $this->generateClientKey($userId, request()->ip());
        
        try {
            // Check rate limiting
            if (!$this->checkRateLimit($endpoint, $clientKey)) {
                return $this->rateLimitResponse();
            }

            // Generate cache key
            $cacheKey = $this->generateApiCacheKey($endpoint, $role, $filters, $userId);
            
            // Get cached response or execute
            $data = Cache::remember($cacheKey, $this->endpointConfigs[$endpoint]['cache_ttl'], function () use ($role, $filters) {
                $startTime = microtime(true);
                
                Log::info('ApiSubAgent: Executing jaspel reports query', [
                    'role' => $role,
                    'filters' => $filters
                ]);

                $reportData = $this->jaspelService->getValidatedJaspelByRole($role, $filters);
                $summaryStats = $this->jaspelService->getRoleSummaryStats($filters);
                
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                
                return [
                    'data' => $reportData->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'email' => $item->email,
                            'role' => $item->role_name,
                            'role_display' => $item->role_display_name ?? ucfirst($item->role_name),
                            'total_tindakan' => (int) $item->total_tindakan,
                            'total_jaspel' => (float) $item->total_jaspel,
                            'avg_jaspel' => $item->total_tindakan > 0 ? round($item->total_jaspel / $item->total_tindakan, 2) : 0,
                            'last_validation' => $item->last_validation ? Carbon::parse($item->last_validation)->toISOString() : null,
                            'first_validation' => $item->first_validation ?? null
                        ];
                    }),
                    'summary' => $summaryStats,
                    'metadata' => [
                        'role_filter' => $role,
                        'total_records' => $reportData->count(),
                        'execution_time_ms' => $executionTime,
                        'generated_at' => Carbon::now()->toISOString(),
                        'cache_key' => $cacheKey
                    ]
                ];
            });

            // Track API usage
            $this->trackApiUsage($endpoint, $clientKey, true);

            Log::info('ApiSubAgent: Jaspel reports API response successful', [
                'role' => $role,
                'records_returned' => count($data['data']),
                'cache_hit' => Cache::has($cacheKey)
            ]);

            return response()->json([
                'success' => true,
                'data' => $data['data'],
                'summary' => $data['summary'],
                'metadata' => $data['metadata']
            ]);

        } catch (Exception $e) {
            $this->trackApiUsage($endpoint, $clientKey, false, $e->getMessage());
            
            Log::error('ApiSubAgent: Jaspel reports API failed', [
                'error' => $e->getMessage(),
                'role' => $role,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Gagal mengambil data laporan jaspel',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Handle jaspel user detail API request
     */
    public function handleJaspelUserDetailApi(int $userId, array $filters = []): JsonResponse
    {
        $endpoint = 'jaspel_summary';
        $clientKey = $this->generateClientKey($userId, request()->ip());
        
        try {
            // Check rate limiting
            if (!$this->checkRateLimit($endpoint, $clientKey)) {
                return $this->rateLimitResponse();
            }

            $cacheKey = $this->generateApiCacheKey($endpoint, 'user_' . $userId, $filters);
            
            $data = Cache::remember($cacheKey, $this->endpointConfigs[$endpoint]['cache_ttl'], function () use ($userId, $filters) {
                $startTime = microtime(true);
                
                $userDetail = $this->jaspelService->getJaspelSummaryByUser($userId, $filters);
                
                if (empty($userDetail)) {
                    return null;
                }

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                
                return [
                    'user' => [
                        'id' => $userDetail['user']->id,
                        'name' => $userDetail['user']->name,
                        'email' => $userDetail['user']->email,
                        'role' => $userDetail['user']->role->name ?? 'unknown',
                        'role_display' => $userDetail['user']->role->display_name ?? 'Unknown'
                    ],
                    'summary' => [
                        'total_tindakan' => (int) ($userDetail['summary']->total_tindakan ?? 0),
                        'total_jaspel' => (float) ($userDetail['summary']->total_jaspel ?? 0),
                        'avg_jaspel' => (float) ($userDetail['summary']->avg_jaspel ?? 0),
                        'jaspel_tindakan' => (float) ($userDetail['summary']->jaspel_tindakan ?? 0),
                        'jaspel_shift' => (float) ($userDetail['summary']->jaspel_shift ?? 0),
                        'first_validation' => $userDetail['summary']->first_validation ? Carbon::parse($userDetail['summary']->first_validation)->toISOString() : null,
                        'last_validation' => $userDetail['summary']->last_validation ? Carbon::parse($userDetail['summary']->last_validation)->toISOString() : null
                    ],
                    'period' => $userDetail['period'],
                    'performance' => $userDetail['performance'] ?? [],
                    'metadata' => [
                        'execution_time_ms' => $executionTime,
                        'generated_at' => Carbon::now()->toISOString()
                    ]
                ];
            });

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'error' => 'User tidak ditemukan atau tidak memiliki data jaspel'
                ], 404);
            }

            $this->trackApiUsage($endpoint, $clientKey, true);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            $this->trackApiUsage($endpoint, $clientKey, false, $e->getMessage());
            
            Log::error('ApiSubAgent: User detail API failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Gagal mengambil detail user jaspel'
            ], 500);
        }
    }

    /**
     * Handle jaspel export API request
     */
    public function handleJaspelExportApi(string $format, string $role = 'semua', array $filters = []): JsonResponse
    {
        $endpoint = 'jaspel_export';
        $clientKey = $this->generateClientKey(auth()->id(), request()->ip());
        
        try {
            // Check rate limiting (stricter for exports)
            if (!$this->checkRateLimit($endpoint, $clientKey)) {
                return $this->rateLimitResponse();
            }

            // Validate format
            if (!in_array($format, ['excel', 'pdf', 'csv'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Format tidak valid. Gunakan: excel, pdf, atau csv'
                ], 400);
            }

            $startTime = microtime(true);
            
            // Prepare export data
            $exportData = $this->jaspelService->prepareExportData($role, $filters);
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Generate file (simplified - in real implementation would create actual files)
            $filename = 'laporan_jaspel_' . $role . '_' . Carbon::now()->format('Ymd_His') . '.' . $format;
            
            $this->trackApiUsage($endpoint, $clientKey, true);
            
            Log::info('ApiSubAgent: Export API successful', [
                'format' => $format,
                'role' => $role,
                'records_count' => count($exportData['data']),
                'execution_time_ms' => $executionTime
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => url('storage/exports/' . $filename),
                    'filename' => $filename,
                    'format' => $format,
                    'records_count' => count($exportData['data']),
                    'file_size_estimate' => $this->estimateFileSize($exportData, $format),
                    'expires_at' => Carbon::now()->addHours(24)->toISOString()
                ],
                'metadata' => [
                    'execution_time_ms' => $executionTime,
                    'generated_at' => Carbon::now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            $this->trackApiUsage($endpoint, $clientKey, false, $e->getMessage());
            
            Log::error('ApiSubAgent: Export API failed', [
                'error' => $e->getMessage(),
                'format' => $format,
                'role' => $role
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Gagal melakukan export data jaspel'
            ], 500);
        }
    }

    /**
     * Check rate limiting for endpoint
     */
    protected function checkRateLimit(string $endpoint, string $clientKey): bool
    {
        $config = $this->endpointConfigs[$endpoint] ?? [];
        $limit = $config['rate_limit'] ?? $this->rateLimit;
        
        $rateLimitKey = "api_rate_limit:{$endpoint}:{$clientKey}";
        
        return RateLimiter::attempt($rateLimitKey, $limit, function () {
            // Rate limit passed
        }, 60); // 1 minute window
    }

    /**
     * Generate client key for rate limiting
     */
    protected function generateClientKey(?int $userId, string $ip): string
    {
        return $userId ? "user_{$userId}" : "ip_{$ip}";
    }

    /**
     * Generate API cache key
     */
    protected function generateApiCacheKey(string $endpoint, string $identifier, array $filters = [], ?int $userId = null): string
    {
        $keyParts = [
            $this->cachePrefix,
            $endpoint,
            $identifier,
            $userId ?? 'guest',
            md5(json_encode($filters))
        ];
        
        return implode('_', $keyParts);
    }

    /**
     * Rate limit response
     */
    protected function rateLimitResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Rate limit exceeded',
            'message' => 'Terlalu banyak permintaan. Coba lagi dalam beberapa menit.'
        ], 429);
    }

    /**
     * Track API usage for analytics
     */
    protected function trackApiUsage(string $endpoint, string $clientKey, bool $success, ?string $error = null): void
    {
        $logData = [
            'endpoint' => $endpoint,
            'client_key' => $clientKey,
            'success' => $success,
            'timestamp' => Carbon::now()->toISOString(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 200)
        ];

        if ($error) {
            $logData['error'] = $error;
        }

        // Log to specific API channel
        Log::channel('api')->info('API SubAgent usage tracked', $logData);
        
        // Store usage metrics in cache for analytics
        $metricsKey = "api_metrics:{$endpoint}:" . Carbon::now()->format('Y-m-d-H');
        $currentMetrics = Cache::get($metricsKey, ['requests' => 0, 'successes' => 0, 'errors' => 0]);
        
        $currentMetrics['requests']++;
        if ($success) {
            $currentMetrics['successes']++;
        } else {
            $currentMetrics['errors']++;
        }
        
        Cache::put($metricsKey, $currentMetrics, 3600); // 1 hour
    }

    /**
     * Get API performance metrics
     */
    public function getApiPerformanceMetrics(string $period = 'today'): array
    {
        try {
            $metrics = [];
            
            // Calculate date range based on period
            $startDate = match($period) {
                'today' => Carbon::today(),
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                default => Carbon::today()
            };
            
            // Collect hourly metrics
            $hours = [];
            for ($hour = $startDate->copy(); $hour <= Carbon::now(); $hour->addHour()) {
                $metricsKey = "api_metrics:jaspel_reports:" . $hour->format('Y-m-d-H');
                $hourlyData = Cache::get($metricsKey, ['requests' => 0, 'successes' => 0, 'errors' => 0]);
                $hours[] = [
                    'hour' => $hour->format('Y-m-d H:00'),
                    'requests' => $hourlyData['requests'],
                    'success_rate' => $hourlyData['requests'] > 0 ? round(($hourlyData['successes'] / $hourlyData['requests']) * 100, 2) : 0,
                    'errors' => $hourlyData['errors']
                ];
            }
            
            // Calculate overall metrics
            $totalRequests = array_sum(array_column($hours, 'requests'));
            $totalErrors = array_sum(array_column($hours, 'errors'));
            $overallSuccessRate = $totalRequests > 0 ? round((($totalRequests - $totalErrors) / $totalRequests) * 100, 2) : 0;
            
            return [
                'period' => $period,
                'overview' => [
                    'total_requests' => $totalRequests,
                    'success_rate' => $overallSuccessRate,
                    'total_errors' => $totalErrors,
                    'average_requests_per_hour' => count($hours) > 0 ? round($totalRequests / count($hours), 2) : 0
                ],
                'hourly_breakdown' => $hours,
                'endpoints' => [
                    'jaspel_reports' => [
                        'cache_ttl' => $this->endpointConfigs['jaspel_reports']['cache_ttl'],
                        'rate_limit' => $this->endpointConfigs['jaspel_reports']['rate_limit'],
                        'status' => 'active'
                    ],
                    'jaspel_summary' => [
                        'cache_ttl' => $this->endpointConfigs['jaspel_summary']['cache_ttl'],
                        'rate_limit' => $this->endpointConfigs['jaspel_summary']['rate_limit'],
                        'status' => 'active'
                    ],
                    'jaspel_export' => [
                        'cache_ttl' => $this->endpointConfigs['jaspel_export']['cache_ttl'],
                        'rate_limit' => $this->endpointConfigs['jaspel_export']['rate_limit'],
                        'status' => 'active'
                    ]
                ]
            ];

        } catch (Exception $e) {
            Log::error('ApiSubAgent: Performance metrics collection failed', [
                'error' => $e->getMessage(),
                'period' => $period
            ]);

            return [
                'error' => 'Gagal mengambil metrics API',
                'period' => $period,
                'timestamp' => Carbon::now()->toISOString()
            ];
        }
    }

    /**
     * Validate API request authentication and authorization
     */
    public function validateApiAccess(string $endpoint, ?int $userId = null, array $requiredRoles = []): array
    {
        $config = $this->endpointConfigs[$endpoint] ?? [];
        
        // Check if authentication required
        if ($config['auth_required'] ?? true) {
            if (!$userId || !auth()->check()) {
                return [
                    'valid' => false,
                    'error' => 'Authentication required',
                    'code' => 401
                ];
            }
        }

        // Check role authorization
        $allowedRoles = $requiredRoles ?: ($config['roles_allowed'] ?? []);
        if (!empty($allowedRoles)) {
            $user = auth()->user();
            if (!$user || !$user->hasAnyRole($allowedRoles)) {
                return [
                    'valid' => false,
                    'error' => 'Insufficient permissions',
                    'code' => 403,
                    'required_roles' => $allowedRoles
                ];
            }
        }

        return [
            'valid' => true,
            'user_id' => $userId,
            'roles' => auth()->user()?->getRoleNames()->toArray() ?? []
        ];
    }

    /**
     * Clear API-related cache
     */
    public function clearApiCache(array $endpoints = []): void
    {
        $endpointsTolear = empty($endpoints) ? array_keys($this->endpointConfigs) : $endpoints;
        
        foreach ($endpointsTolear as $endpoint) {
            Cache::forget($this->cachePrefix . $endpoint . '_*');
        }
        
        Log::info('ApiSubAgent: Cache cleared for endpoints', [
            'endpoints' => $endpointsTolear
        ]);
    }

    /**
     * Estimate export file size
     */
    protected function estimateFileSize(array $data, string $format): string
    {
        $recordCount = count($data['data']);
        $avgRecordSize = match($format) {
            'csv' => 150,    // bytes per record
            'excel' => 200,  // bytes per record  
            'pdf' => 300,    // bytes per record
            default => 200
        };
        
        $estimatedBytes = $recordCount * $avgRecordSize;
        
        if ($estimatedBytes < 1024) {
            return $estimatedBytes . ' B';
        } elseif ($estimatedBytes < 1048576) {
            return round($estimatedBytes / 1024, 1) . ' KB';
        } else {
            return round($estimatedBytes / 1048576, 1) . ' MB';
        }
    }

    /**
     * Get API health status
     */
    public function getApiHealthStatus(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test database connectivity through service
            $testQuery = $this->jaspelService->getValidatedJaspelByRole('semua', []);
            $dbResponsive = true;
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Check cache status
            $cacheResponsive = true;
            try {
                Cache::put('api_health_test', 'ok', 10);
                $cacheTest = Cache::get('api_health_test');
                $cacheResponsive = $cacheTest === 'ok';
            } catch (Exception $e) {
                $cacheResponsive = false;
            }
            
            // Determine overall health
            $healthy = $dbResponsive && $cacheResponsive && $responseTime < 1000;
            
            return [
                'status' => $healthy ? 'healthy' : 'degraded',
                'timestamp' => Carbon::now()->toISOString(),
                'response_time_ms' => $responseTime,
                'components' => [
                    'database' => [
                        'status' => $dbResponsive ? 'up' : 'down',
                        'response_time_ms' => $responseTime
                    ],
                    'cache' => [
                        'status' => $cacheResponsive ? 'up' : 'down',
                        'driver' => Cache::getStore() instanceof \Illuminate\Cache\FileStore ? 'file' : 'redis'
                    ],
                    'jaspel_service' => [
                        'status' => 'up',
                        'test_records' => $testQuery->count()
                    ]
                ],
                'endpoints' => array_keys($this->endpointConfigs)
            ];

        } catch (Exception $e) {
            Log::error('ApiSubAgent: Health check failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'unhealthy',
                'timestamp' => Carbon::now()->toISOString(),
                'error' => $e->getMessage(),
                'components' => [
                    'database' => ['status' => 'unknown'],
                    'cache' => ['status' => 'unknown'],
                    'jaspel_service' => ['status' => 'down']
                ]
            ];
        }
    }
}