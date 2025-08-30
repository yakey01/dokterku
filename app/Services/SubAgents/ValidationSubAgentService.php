<?php

namespace App\Services\SubAgents;

use App\Core\Base\BaseService;
use App\Models\Jaspel;
use App\Models\User;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * Validation Sub-Agent Service
 * 
 * Specialized service for precise data validation and cross-reference verification
 * Implements cermat (meticulous) validation with comprehensive data integrity checks
 */
class ValidationSubAgentService extends BaseService
{
    protected string $cachePrefix = 'validation_subagent_';
    protected int $defaultCacheTtl = 180; // 3 minutes for validation data
    
    /**
     * Perform cermat (meticulous) validation of user's jaspel data
     */
    public function performCermatJaspelValidation(int $userId): array
    {
        $startTime = microtime(true);
        
        Log::info('ValidationSubAgent: Starting cermat validation', [
            'user_id' => $userId,
            'validation_type' => 'comprehensive_jaspel_audit'
        ]);

        try {
            $user = User::with('role')->find($userId);
            
            if (!$user) {
                return [
                    'valid' => false,
                    'error' => 'User tidak ditemukan',
                    'user_id' => $userId
                ];
            }

            // 1. Direct Jaspel Data Validation
            $jaspelValidation = $this->validateJaspelRecords($userId);
            
            // 2. Cross-Reference with Tindakan
            $tindakanValidation = $this->validateTindakanCrossReference($userId);
            
            // 3. Cross-Reference with Jumlah Pasien Harian
            $pasienValidation = $this->validateJumlahPasienCrossReference($userId);
            
            // 4. Financial Calculation Validation
            $financialValidation = $this->validateFinancialCalculations($userId);
            
            // 5. Data Integrity Checks
            $integrityValidation = $this->validateDataIntegrity($userId);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $validationResult = [
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role->name ?? 'unknown'
                ],
                'validations' => [
                    'jaspel_records' => $jaspelValidation,
                    'tindakan_cross_ref' => $tindakanValidation,
                    'pasien_cross_ref' => $pasienValidation,
                    'financial_calc' => $financialValidation,
                    'data_integrity' => $integrityValidation
                ],
                'summary' => [
                    'total_checks' => 5,
                    'passed_checks' => $this->countPassedValidations([
                        $jaspelValidation, $tindakanValidation, $pasienValidation, 
                        $financialValidation, $integrityValidation
                    ]),
                    'execution_time_ms' => $executionTime,
                    'validation_score' => $this->calculateValidationScore([
                        $jaspelValidation, $tindakanValidation, $pasienValidation, 
                        $financialValidation, $integrityValidation
                    ])
                ],
                'recommendations' => $this->generateValidationRecommendations([
                    $jaspelValidation, $tindakanValidation, $pasienValidation, 
                    $financialValidation, $integrityValidation
                ])
            ];

            Log::info('ValidationSubAgent: Cermat validation completed', [
                'user_id' => $userId,
                'validation_score' => $validationResult['summary']['validation_score'],
                'execution_time_ms' => $executionTime
            ]);

            return $validationResult;

        } catch (Exception $e) {
            Log::error('ValidationSubAgent: Cermat validation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'error' => 'Validation gagal: ' . $e->getMessage(),
                'user_id' => $userId,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Validate jaspel records for consistency and accuracy
     */
    protected function validateJaspelRecords(int $userId): array
    {
        try {
            $jaspelRecords = Jaspel::where('user_id', $userId)
                ->where('status_validasi', 'disetujui')
                ->whereNull('deleted_at')
                ->get();

            $totalCalculated = $jaspelRecords->sum('total_jaspel');
            $totalDatabase = DB::table('jaspel')
                ->where('user_id', $userId)
                ->where('status_validasi', 'disetujui')
                ->whereNull('deleted_at')
                ->sum('total_jaspel');

            $validation = [
                'passed' => abs($totalCalculated - $totalDatabase) < 0.01, // Allow for floating point precision
                'total_records' => $jaspelRecords->count(),
                'total_calculated' => $totalCalculated,
                'total_database' => $totalDatabase,
                'discrepancy' => $totalCalculated - $totalDatabase,
                'validation_dates' => [
                    'earliest' => $jaspelRecords->min('validasi_at'),
                    'latest' => $jaspelRecords->max('validasi_at')
                ],
                'status_breakdown' => [
                    'disetujui' => $jaspelRecords->where('status_validasi', 'disetujui')->count(),
                    'pending' => Jaspel::where('user_id', $userId)->where('status_validasi', 'pending')->count(),
                    'ditolak' => Jaspel::where('user_id', $userId)->where('status_validasi', 'ditolak')->count()
                ]
            ];

            return $validation;

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate cross-reference with tindakan data
     */
    protected function validateTindakanCrossReference(int $userId): array
    {
        try {
            // Check if user has tindakan records
            $tindakanCount = Tindakan::where('dokter_id', $userId)->count();
            
            // Check jaspel records linked to tindakan
            $jaspelWithTindakan = Jaspel::where('user_id', $userId)
                ->whereNotNull('tindakan_id')
                ->where('status_validasi', 'disetujui')
                ->count();

            $jaspelWithoutTindakan = Jaspel::where('user_id', $userId)
                ->whereNull('tindakan_id')
                ->where('status_validasi', 'disetujui')
                ->count();

            return [
                'passed' => true, // Cross-reference is informational, not mandatory
                'tindakan_records' => $tindakanCount,
                'jaspel_with_tindakan' => $jaspelWithTindakan,
                'jaspel_without_tindakan' => $jaspelWithoutTindakan,
                'coverage_ratio' => $tindakanCount > 0 ? round(($jaspelWithTindakan / $tindakanCount) * 100, 2) : 0,
                'note' => $tindakanCount === 0 ? 'No tindakan records found - jaspel may be from other sources' : 'Tindakan cross-reference available'
            ];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate cross-reference with jumlah pasien harian
     */
    protected function validateJumlahPasienCrossReference(int $userId): array
    {
        try {
            $pasienRecords = JumlahPasienHarian::where('user_id', $userId)->get();
            $pasienWithJaspel = $pasienRecords->where('jaspel_rupiah', '>', 0);
            $totalJaspelFromPasien = $pasienWithJaspel->sum('jaspel_rupiah');

            return [
                'passed' => true, // Cross-reference is informational
                'pasien_records' => $pasienRecords->count(),
                'pasien_with_jaspel' => $pasienWithJaspel->count(),
                'total_jaspel_from_pasien' => $totalJaspelFromPasien,
                'average_jaspel_per_day' => $pasienWithJaspel->count() > 0 ? 
                    round($totalJaspelFromPasien / $pasienWithJaspel->count(), 2) : 0,
                'note' => $pasienRecords->count() === 0 ? 
                    'No jumlah pasien harian records found' : 
                    'Jumlah pasien cross-reference available'
            ];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate financial calculations and consistency
     */
    protected function validateFinancialCalculations(int $userId): array
    {
        try {
            $jaspelRecords = Jaspel::where('user_id', $userId)
                ->where('status_validasi', 'disetujui')
                ->whereNull('deleted_at')
                ->get();

            // Check for calculation consistency
            $calculations = [];
            $inconsistencies = [];

            foreach ($jaspelRecords as $record) {
                // Check if total_jaspel matches nominal (if applicable)
                if ($record->nominal > 0 && abs($record->total_jaspel - $record->nominal) > 0.01) {
                    $inconsistencies[] = [
                        'jaspel_id' => $record->id,
                        'nominal' => $record->nominal,
                        'total_jaspel' => $record->total_jaspel,
                        'difference' => $record->total_jaspel - $record->nominal
                    ];
                }

                $calculations[] = [
                    'id' => $record->id,
                    'jenis' => $record->jenis_jaspel,
                    'nominal' => $record->nominal,
                    'total' => $record->total_jaspel,
                    'tanggal' => $record->tanggal
                ];
            }

            return [
                'passed' => count($inconsistencies) === 0,
                'total_records_checked' => $jaspelRecords->count(),
                'inconsistencies_found' => count($inconsistencies),
                'inconsistencies' => $inconsistencies,
                'summary' => [
                    'total_nominal' => $jaspelRecords->sum('nominal'),
                    'total_jaspel' => $jaspelRecords->sum('total_jaspel'),
                    'average_per_record' => $jaspelRecords->count() > 0 ? 
                        round($jaspelRecords->avg('total_jaspel'), 2) : 0
                ]
            ];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate overall data integrity
     */
    protected function validateDataIntegrity(int $userId): array
    {
        try {
            $user = User::find($userId);
            
            // Check user relationships
            $hasRole = $user->role_id !== null;
            $isActive = $user->is_active;
            $hasValidRole = $user->role !== null;
            
            // Check jaspel data integrity
            $jaspelRecords = Jaspel::where('user_id', $userId)->get();
            $orphanedJaspel = $jaspelRecords->filter(function ($jaspel) {
                return $jaspel->validasi_by && !User::find($jaspel->validasi_by);
            });

            // Check temporal consistency
            $futureJaspel = $jaspelRecords->filter(function ($jaspel) {
                return $jaspel->tanggal > now();
            });

            $validationBeforeCreation = $jaspelRecords->filter(function ($jaspel) {
                return $jaspel->validasi_at && $jaspel->validasi_at < $jaspel->created_at;
            });

            return [
                'passed' => $hasRole && $isActive && $hasValidRole && 
                           $orphanedJaspel->count() === 0 && 
                           $futureJaspel->count() === 0 && 
                           $validationBeforeCreation->count() === 0,
                'user_integrity' => [
                    'has_role' => $hasRole,
                    'is_active' => $isActive,
                    'valid_role' => $hasValidRole
                ],
                'jaspel_integrity' => [
                    'total_records' => $jaspelRecords->count(),
                    'orphaned_records' => $orphanedJaspel->count(),
                    'future_dated' => $futureJaspel->count(),
                    'invalid_validation_dates' => $validationBeforeCreation->count()
                ],
                'data_quality_score' => $this->calculateDataQualityScore([
                    'user_valid' => $hasRole && $isActive && $hasValidRole,
                    'no_orphans' => $orphanedJaspel->count() === 0,
                    'no_future_dates' => $futureJaspel->count() === 0,
                    'valid_timestamps' => $validationBeforeCreation->count() === 0
                ])
            ];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Compare jaspel calculation methods for discrepancy analysis
     */
    public function analyzeJaspelCalculationDiscrepancy(int $userId): array
    {
        $cacheKey = $this->cachePrefix . 'discrepancy_analysis_' . $userId;
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($userId) {
            try {
                $user = User::find($userId);
                
                if (!$user) {
                    return ['error' => 'User tidak ditemukan'];
                }

                // Method 1: Direct sum
                $directSum = Jaspel::where('user_id', $userId)
                    ->where('status_validasi', 'disetujui')
                    ->sum('total_jaspel');

                // Method 2: Query with join (as used in service)
                $querySum = DB::table('users')
                    ->select([DB::raw('COALESCE(SUM(jaspel.total_jaspel), 0) as total_jaspel')])
                    ->leftJoin('jaspel', function ($join) {
                        $join->on('users.id', '=', 'jaspel.user_id')
                             ->where('jaspel.status_validasi', 'disetujui')
                             ->whereNull('jaspel.deleted_at');
                    })
                    ->where('users.id', $userId)
                    ->first();

                // Method 3: Aggregation with grouping (as used in DatabaseSubAgent)
                $aggregateSum = User::select([
                        'users.id',
                        DB::raw('COALESCE(SUM(jaspel.total_jaspel), 0) as total_jaspel')
                    ])
                    ->leftJoin('jaspel', function ($join) {
                        $join->on('users.id', '=', 'jaspel.user_id')
                             ->where('jaspel.status_validasi', 'disetujui')
                             ->whereNull('jaspel.deleted_at');
                    })
                    ->where('users.id', $userId)
                    ->groupBy('users.id')
                    ->first();

                // Method 4: Repository pattern (alternative approach)
                $repositoryData = Jaspel::where('user_id', $userId)
                    ->where('status_validasi', 'disetujui')
                    ->whereNull('deleted_at')
                    ->get();
                $repositorySum = $repositoryData->sum('total_jaspel');

                $analysis = [
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'calculation_methods' => [
                        'direct_sum' => [
                            'value' => $directSum,
                            'formatted' => 'Rp ' . number_format($directSum, 0, ',', '.'),
                            'method' => 'Jaspel::where()->sum()'
                        ],
                        'query_join' => [
                            'value' => $querySum->total_jaspel ?? 0,
                            'formatted' => 'Rp ' . number_format($querySum->total_jaspel ?? 0, 0, ',', '.'),
                            'method' => 'DB::table()->leftJoin()->sum()'
                        ],
                        'aggregate_group' => [
                            'value' => $aggregateSum->total_jaspel ?? 0,
                            'formatted' => 'Rp ' . number_format($aggregateSum->total_jaspel ?? 0, 0, ',', '.'),
                            'method' => 'User::leftJoin()->groupBy()->sum()'
                        ]
                    ],
                    'discrepancy_analysis' => [
                        'all_methods_match' => $this->allValuesMatch([
                            $directSum, 
                            $querySum->total_jaspel ?? 0, 
                            $aggregateSum->total_jaspel ?? 0
                        ]),
                        'max_difference' => $this->getMaxDifference([
                            $directSum, 
                            $querySum->total_jaspel ?? 0, 
                            $aggregateSum->total_jaspel ?? 0
                        ]),
                        'recommended_value' => $directSum, // Most reliable method
                        'precision_issues' => $this->checkPrecisionIssues([
                            $directSum, 
                            $querySum->total_jaspel ?? 0, 
                            $aggregateSum->total_jaspel ?? 0
                        ])
                    ]
                ];

                return $analysis;

            } catch (Exception $e) {
                return [
                    'passed' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Perform system-wide cermat validation
     */
    public function performSystemWideValidation(): array
    {
        $startTime = microtime(true);
        
        try {
            // Get all users with jaspel data
            $usersWithJaspel = User::whereHas('jaspel', function ($query) {
                $query->where('status_validasi', 'disetujui');
            })->with('role')->get();

            $validationResults = [];
            $overallStats = [
                'total_users_checked' => $usersWithJaspel->count(),
                'users_passed' => 0,
                'users_with_issues' => 0,
                'total_discrepancies_found' => 0
            ];

            foreach ($usersWithJaspel as $user) {
                $userValidation = $this->performCermatJaspelValidation($user->id);
                
                if ($userValidation['valid'] && $userValidation['summary']['validation_score'] >= 80) {
                    $overallStats['users_passed']++;
                } else {
                    $overallStats['users_with_issues']++;
                }

                $validationResults[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_role' => $user->role->name ?? 'unknown',
                    'validation_score' => $userValidation['summary']['validation_score'] ?? 0,
                    'issues_found' => $userValidation['summary']['total_checks'] - $userValidation['summary']['passed_checks'],
                    'total_jaspel' => $userValidation['validations']['jaspel_records']['total_calculated'] ?? 0
                ];
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'validation_completed' => true,
                'overall_stats' => $overallStats,
                'user_validations' => $validationResults,
                'execution_time_ms' => $executionTime,
                'system_health_score' => round(($overallStats['users_passed'] / max(1, $overallStats['total_users_checked'])) * 100, 2)
            ];

        } catch (Exception $e) {
            Log::error('ValidationSubAgent: System-wide validation failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'validation_completed' => false,
                'error' => $e->getMessage(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Helper methods for validation calculations
     */
    protected function countPassedValidations(array $validations): int
    {
        return count(array_filter($validations, fn($v) => $v['passed'] ?? false));
    }

    protected function calculateValidationScore(array $validations): float
    {
        $passed = $this->countPassedValidations($validations);
        $total = count($validations);
        
        return $total > 0 ? round(($passed / $total) * 100, 2) : 0;
    }

    protected function generateValidationRecommendations(array $validations): array
    {
        $recommendations = [];
        
        foreach ($validations as $key => $validation) {
            if (!($validation['passed'] ?? false)) {
                $recommendations[] = "Review " . str_replace('_', ' ', $key) . ": " . ($validation['error'] ?? 'Validation failed');
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = 'All validations passed - data integrity confirmed';
        }

        return $recommendations;
    }

    protected function allValuesMatch(array $values, float $tolerance = 0.01): bool
    {
        if (count($values) < 2) return true;
        
        $first = $values[0];
        foreach ($values as $value) {
            if (abs($value - $first) > $tolerance) {
                return false;
            }
        }
        
        return true;
    }

    protected function getMaxDifference(array $values): float
    {
        if (empty($values)) return 0;
        
        return max($values) - min($values);
    }

    protected function checkPrecisionIssues(array $values): bool
    {
        return $this->getMaxDifference($values) > 0.01;
    }

    protected function calculateDataQualityScore(array $checks): float
    {
        $passed = count(array_filter($checks));
        $total = count($checks);
        
        return $total > 0 ? round(($passed / $total) * 100, 2) : 0;
    }
}