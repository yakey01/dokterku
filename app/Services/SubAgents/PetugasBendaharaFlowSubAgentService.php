<?php

namespace App\Services\SubAgents;

use App\Core\Base\BaseService;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use App\Models\User;
use App\Models\Tindakan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * Petugas-Bendahara Flow Sub-Agent Service
 * 
 * Specialized service for tracking and managing data flow from Petugas to Bendahara
 * Ensures proper workflow compliance and data integrity in financial validation
 */
class PetugasBendaharaFlowSubAgentService extends BaseService
{
    protected string $cachePrefix = 'petugas_bendahara_flow_';
    protected int $defaultCacheTtl = 300; // 5 minutes

    /**
     * Analyze complete petugas to bendahara data flow
     */
    public function analyzeDataFlow(): array
    {
        $cacheKey = $this->cachePrefix . 'flow_analysis';
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () {
            $startTime = microtime(true);
            
            try {
                Log::info('PetugasBendaharaFlowSubAgent: Starting data flow analysis');

                // 1. Analyze Petugas Input Data
                $petugasAnalysis = $this->analyzePetugasInput();
                
                // 2. Analyze Bendahara Validation Data  
                $bendaharaAnalysis = $this->analyzeBendaharaValidation();
                
                // 3. Analyze Data Flow Gaps
                $flowGaps = $this->analyzeDataFlowGaps();
                
                // 4. Analyze Jaspel Generation Sources
                $jaspelSources = $this->analyzeJaspelSources();

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                
                $analysis = [
                    'analysis_timestamp' => Carbon::now()->toISOString(),
                    'execution_time_ms' => $executionTime,
                    'petugas_input' => $petugasAnalysis,
                    'bendahara_validation' => $bendaharaAnalysis,
                    'flow_gaps' => $flowGaps,
                    'jaspel_sources' => $jaspelSources,
                    'compliance_score' => $this->calculateComplianceScore($petugasAnalysis, $bendaharaAnalysis, $flowGaps),
                    'recommendations' => $this->generateFlowRecommendations($petugasAnalysis, $bendaharaAnalysis, $flowGaps)
                ];

                Log::info('PetugasBendaharaFlowSubAgent: Data flow analysis completed', [
                    'compliance_score' => $analysis['compliance_score'],
                    'execution_time_ms' => $executionTime
                ]);

                return $analysis;

            } catch (Exception $e) {
                Log::error('PetugasBendaharaFlowSubAgent: Data flow analysis failed', [
                    'error' => $e->getMessage()
                ]);

                return [
                    'error' => $e->getMessage(),
                    'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ];
            }
        });
    }

    /**
     * Analyze petugas input data and patterns
     */
    protected function analyzePetugasInput(): array
    {
        try {
            // Get all petugas users
            $petugasUsers = User::whereHas('role', function ($q) {
                $q->where('name', 'petugas');
            })->get();

            $inputAnalysis = [
                'petugas_users' => [
                    'total' => $petugasUsers->count(),
                    'active' => $petugasUsers->where('is_active', true)->count(),
                    'users' => $petugasUsers->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'active' => $user->is_active,
                            'last_login' => $user->last_login_at
                        ];
                    })
                ],
                'input_activities' => []
            ];

            // Analyze input activities by each petugas
            foreach ($petugasUsers as $petugas) {
                $pendapatanCount = Pendapatan::where('input_by', $petugas->id)->count();
                $pengeluaranCount = Pengeluaran::where('input_by', $petugas->id)->count();
                $jaspelCount = Jaspel::where('input_by', $petugas->id)->count();
                $tindakanCount = Tindakan::where('input_by', $petugas->id)->count();

                $inputAnalysis['input_activities'][] = [
                    'petugas' => [
                        'id' => $petugas->id,
                        'name' => $petugas->name,
                        'email' => $petugas->email
                    ],
                    'inputs' => [
                        'pendapatan' => $pendapatanCount,
                        'pengeluaran' => $pengeluaranCount,
                        'jaspel' => $jaspelCount,
                        'tindakan' => $tindakanCount,
                        'total' => $pendapatanCount + $pengeluaranCount + $jaspelCount + $tindakanCount
                    ]
                ];
            }

            return $inputAnalysis;

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analyze bendahara validation patterns
     */
    protected function analyzeBendaharaValidation(): array
    {
        try {
            // Get all bendahara users
            $bendaharaUsers = User::whereHas('role', function ($q) {
                $q->where('name', 'bendahara');
            })->get();

            $validationAnalysis = [
                'bendahara_users' => [
                    'total' => $bendaharaUsers->count(),
                    'active' => $bendaharaUsers->where('is_active', true)->count(),
                    'users' => $bendaharaUsers->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'active' => $user->is_active
                        ];
                    })
                ],
                'validation_activities' => []
            ];

            // Analyze validation activities by each bendahara
            foreach ($bendaharaUsers as $bendahara) {
                $pendapatanValidated = Pendapatan::where('validasi_by', $bendahara->id)->count();
                $pengeluaranValidated = Pengeluaran::where('validasi_by', $bendahara->id)->count();
                $jaspelValidated = Jaspel::where('validasi_by', $bendahara->id)->count();

                $validationAnalysis['validation_activities'][] = [
                    'bendahara' => [
                        'id' => $bendahara->id,
                        'name' => $bendahara->name,
                        'email' => $bendahara->email
                    ],
                    'validations' => [
                        'pendapatan' => $pendapatanValidated,
                        'pengeluaran' => $pengeluaranValidated,
                        'jaspel' => $jaspelValidated,
                        'total' => $pendapatanValidated + $pengeluaranValidated + $jaspelValidated
                    ]
                ];
            }

            return $validationAnalysis;

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analyze gaps in the petugas → bendahara workflow
     */
    protected function analyzeDataFlowGaps(): array
    {
        try {
            // Check for data that bypasses proper workflow
            $jaspelWithoutPetugasInput = Jaspel::whereNotIn('input_by', function ($query) {
                $query->select('users.id')
                      ->from('users')
                      ->join('roles', 'users.role_id', '=', 'roles.id')
                      ->where('roles.name', 'petugas');
            })->count();

            $jaspelWithPetugasInput = Jaspel::whereIn('input_by', function ($query) {
                $query->select('users.id')
                      ->from('users')
                      ->join('roles', 'users.role_id', '=', 'roles.id')
                      ->where('roles.name', 'petugas');
            })->count();

            // Check validation workflow compliance
            $pendingValidations = [
                'pendapatan' => Pendapatan::where('status_validasi', 'pending')->count(),
                'pengeluaran' => Pengeluaran::where('status_validasi', 'pending')->count(),
                'jaspel' => Jaspel::where('status_validasi', 'pending')->count()
            ];

            $approvedWithoutBendahara = Jaspel::where('status_validasi', 'disetujui')
                ->whereNotIn('validasi_by', function ($query) {
                    $query->select('users.id')
                          ->from('users')
                          ->join('roles', 'users.role_id', '=', 'roles.id')
                          ->where('roles.name', 'bendahara');
                })->count();

            return [
                'workflow_compliance' => [
                    'jaspel_from_petugas' => $jaspelWithPetugasInput,
                    'jaspel_not_from_petugas' => $jaspelWithoutPetugasInput,
                    'compliance_percentage' => $jaspelWithPetugasInput + $jaspelWithoutPetugasInput > 0 
                        ? round(($jaspelWithPetugasInput / ($jaspelWithPetugasInput + $jaspelWithoutPetugasInput)) * 100, 2) 
                        : 0
                ],
                'validation_workflow' => [
                    'pending_validations' => $pendingValidations,
                    'total_pending' => array_sum($pendingValidations),
                    'approved_without_bendahara' => $approvedWithoutBendahara
                ],
                'workflow_issues' => [
                    'bypassed_petugas_input' => $jaspelWithoutPetugasInput > 0,
                    'bypassed_bendahara_validation' => $approvedWithoutBendahara > 0,
                    'stale_pending_validations' => array_sum($pendingValidations) > 0
                ]
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analyze jaspel generation sources and patterns
     */
    protected function analyzeJaspelSources(): array
    {
        try {
            // Analyze jaspel generation sources
            $jaspelBySource = DB::table('jaspel')
                ->join('users as input_users', 'jaspel.input_by', '=', 'input_users.id')
                ->join('roles as input_roles', 'input_users.role_id', '=', 'input_roles.id')
                ->select([
                    'input_roles.name as input_role',
                    'input_roles.display_name as input_role_display',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_jaspel) as total_amount'),
                    DB::raw('AVG(total_jaspel) as avg_amount')
                ])
                ->groupBy('input_roles.id', 'input_roles.name', 'input_roles.display_name')
                ->orderBy('count', 'desc')
                ->get();

            // Check jaspel linked to tindakan
            $jaspelWithTindakan = Jaspel::whereNotNull('tindakan_id')->count();
            $jaspelWithoutTindakan = Jaspel::whereNull('tindakan_id')->count();

            // Check validation sources
            $validationSources = DB::table('jaspel')
                ->join('users as validator_users', 'jaspel.validasi_by', '=', 'validator_users.id')
                ->join('roles as validator_roles', 'validator_users.role_id', '=', 'validator_roles.id')
                ->where('jaspel.status_validasi', 'disetujui')
                ->select([
                    'validator_roles.name as validator_role',
                    DB::raw('COUNT(*) as validated_count'),
                    DB::raw('SUM(total_jaspel) as validated_amount')
                ])
                ->groupBy('validator_roles.id', 'validator_roles.name')
                ->get();

            return [
                'input_sources' => $jaspelBySource->toArray(),
                'tindakan_linkage' => [
                    'with_tindakan' => $jaspelWithTindakan,
                    'without_tindakan' => $jaspelWithoutTindakan,
                    'linkage_percentage' => $jaspelWithTindakan + $jaspelWithoutTindakan > 0
                        ? round(($jaspelWithTindakan / ($jaspelWithTindakan + $jaspelWithoutTindakan)) * 100, 2)
                        : 0
                ],
                'validation_sources' => $validationSources->toArray(),
                'data_integrity_score' => $this->calculateDataIntegrityScore($jaspelBySource, $validationSources)
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create proper petugas input for testing the workflow
     */
    public function createTestPetugasInput(int $petugasUserId): array
    {
        try {
            $petugas = User::find($petugasUserId);
            
            if (!$petugas || !$petugas->hasRole('petugas')) {
                return [
                    'success' => false,
                    'error' => 'User bukan petugas atau tidak ditemukan'
                ];
            }

            DB::transaction(function () use ($petugas) {
                // Create sample pendapatan
                Pendapatan::create([
                    'nama' => 'Test Pendapatan dari ' . $petugas->name,
                    'nominal' => 500000,
                    'kategori' => 'tindakan_medis',
                    'keterangan' => 'Testing petugas → bendahara flow',
                    'tanggal' => Carbon::today(),
                    'input_by' => $petugas->id,
                    'status_validasi' => 'pending'
                ]);

                // Create sample pengeluaran
                Pengeluaran::create([
                    'nama' => 'Test Pengeluaran dari ' . $petugas->name,
                    'nominal' => 150000,
                    'kategori' => 'operasional',
                    'keterangan' => 'Testing petugas → bendahara flow',
                    'tanggal' => Carbon::today(),
                    'input_by' => $petugas->id,
                    'status_validasi' => 'pending'
                ]);

                // Create sample jaspel
                Jaspel::create([
                    'user_id' => $petugas->id,
                    'jenis_jaspel' => 'tindakan',
                    'nominal' => 250000,
                    'total_jaspel' => 250000,
                    'tanggal' => Carbon::today(),
                    'input_by' => $petugas->id,
                    'status_validasi' => 'pending'
                ]);
            });

            // Clear related caches
            $this->clearFlowCache();

            Log::info('PetugasBendaharaFlowSubAgent: Test data created successfully', [
                'petugas_id' => $petugas->id,
                'petugas_name' => $petugas->name
            ]);

            return [
                'success' => true,
                'message' => 'Test data berhasil dibuat untuk workflow petugas → bendahara',
                'petugas' => [
                    'id' => $petugas->id,
                    'name' => $petugas->name,
                    'email' => $petugas->email
                ],
                'created_records' => [
                    'pendapatan' => 1,
                    'pengeluaran' => 1,
                    'jaspel' => 1
                ]
            ];

        } catch (Exception $e) {
            Log::error('PetugasBendaharaFlowSubAgent: Test data creation failed', [
                'error' => $e->getMessage(),
                'petugas_id' => $petugasUserId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Track petugas input activities for bendahara validation
     */
    public function trackPetugasInputActivities(array $dateRange = []): array
    {
        try {
            $startDate = $dateRange['start'] ?? Carbon::now()->startOfMonth();
            $endDate = $dateRange['end'] ?? Carbon::now()->endOfMonth();

            // Get input activities by date
            $dailyInputs = DB::table('pendapatan')
                ->select([
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as pendapatan_count'),
                    DB::raw('SUM(nominal) as pendapatan_total')
                ])
                ->whereHas('inputBy.role', function ($q) {
                    $q->where('name', 'petugas');
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'desc')
                ->get();

            // Get validation activities by date
            $dailyValidations = DB::table('jaspel')
                ->select([
                    DB::raw('DATE(validasi_at) as date'),
                    DB::raw('COUNT(*) as validated_count'),
                    DB::raw('SUM(total_jaspel) as validated_total')
                ])
                ->whereNotNull('validasi_at')
                ->whereHas('validasiBy.role', function ($q) {
                    $q->where('name', 'bendahara');
                })
                ->whereBetween('validasi_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(validasi_at)'))
                ->orderBy('date', 'desc')
                ->get();

            return [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'days' => $startDate->diffInDays($endDate) + 1
                ],
                'daily_inputs' => $dailyInputs->toArray(),
                'daily_validations' => $dailyValidations->toArray(),
                'summary' => [
                    'total_input_days' => $dailyInputs->count(),
                    'total_validation_days' => $dailyValidations->count(),
                    'avg_inputs_per_day' => $dailyInputs->count() > 0 ? 
                        round($dailyInputs->avg('pendapatan_count'), 2) : 0,
                    'avg_validations_per_day' => $dailyValidations->count() > 0 ? 
                        round($dailyValidations->avg('validated_count'), 2) : 0
                ]
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get workflow performance metrics
     */
    public function getWorkflowPerformanceMetrics(): array
    {
        try {
            // Input to validation time analysis
            $avgValidationTime = DB::table('jaspel')
                ->whereNotNull('validasi_at')
                ->whereNotNull('created_at')
                ->selectRaw('AVG(JULIANDAY(validasi_at) - JULIANDAY(created_at)) as avg_days')
                ->value('avg_days');

            // Current pending items
            $pendingItems = [
                'pendapatan' => Pendapatan::where('status_validasi', 'pending')->count(),
                'pengeluaran' => Pengeluaran::where('status_validasi', 'pending')->count(),
                'jaspel' => Jaspel::where('status_validasi', 'pending')->count()
            ];

            // Validation throughput
            $validationThroughput = [
                'daily_avg' => Jaspel::where('status_validasi', 'disetujui')
                    ->where('validasi_at', '>=', Carbon::now()->subDays(30))
                    ->count() / 30,
                'monthly_total' => Jaspel::where('status_validasi', 'disetujui')
                    ->where('validasi_at', '>=', Carbon::now()->startOfMonth())
                    ->count()
            ];

            return [
                'processing_efficiency' => [
                    'avg_validation_time_days' => round($avgValidationTime ?? 0, 2),
                    'pending_backlog' => array_sum($pendingItems),
                    'pending_breakdown' => $pendingItems
                ],
                'throughput_metrics' => $validationThroughput,
                'workflow_health' => [
                    'healthy' => $avgValidationTime < 3 && array_sum($pendingItems) < 50,
                    'bottleneck_detected' => array_sum($pendingItems) > 100,
                    'rapid_processing' => $avgValidationTime < 1
                ]
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear workflow-related caches
     */
    public function clearFlowCache(): void
    {
        $patterns = [
            $this->cachePrefix . '*',
            'db_subagent_*',
            'api_subagent_*'
        ];

        foreach ($patterns as $pattern) {
            Cache::flush(); // In production, use more specific clearing
        }

        Log::info('PetugasBendaharaFlowSubAgent: Flow cache cleared');
    }

    /**
     * Helper methods for analysis calculations
     */
    protected function calculateComplianceScore(array $petugasAnalysis, array $bendaharaAnalysis, array $flowGaps): float
    {
        $scores = [];
        
        // Score petugas input activity
        $petugasInputs = collect($petugasAnalysis['input_activities'] ?? [])->sum('inputs.total');
        $scores['petugas_activity'] = $petugasInputs > 0 ? 100 : 0;
        
        // Score workflow compliance
        $workflowCompliance = $flowGaps['workflow_compliance']['compliance_percentage'] ?? 0;
        $scores['workflow_compliance'] = $workflowCompliance;
        
        // Score validation activity
        $bendaharaValidations = collect($bendaharaAnalysis['validation_activities'] ?? [])->sum('validations.total');
        $scores['bendahara_activity'] = $bendaharaValidations > 0 ? 100 : 0;

        return round(array_sum($scores) / count($scores), 2);
    }

    protected function generateFlowRecommendations(array $petugasAnalysis, array $bendaharaAnalysis, array $flowGaps): array
    {
        $recommendations = [];

        // Check petugas input activity
        $totalInputs = collect($petugasAnalysis['input_activities'] ?? [])->sum('inputs.total');
        if ($totalInputs === 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'input',
                'issue' => 'No petugas input detected',
                'action' => 'Create test petugas input or verify existing data sources',
                'impact' => 'Bendahara has no data to validate'
            ];
        }

        // Check workflow gaps
        $nonPetugasJaspel = $flowGaps['workflow_compliance']['jaspel_not_from_petugas'] ?? 0;
        if ($nonPetugasJaspel > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'workflow',
                'issue' => $nonPetugasJaspel . ' jaspel records not from petugas input',
                'action' => 'Review jaspel generation sources and ensure proper workflow',
                'impact' => 'Workflow compliance reduced'
            ];
        }

        // Check validation bottlenecks
        $pendingTotal = $flowGaps['validation_workflow']['total_pending'] ?? 0;
        if ($pendingTotal > 50) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'validation',
                'issue' => $pendingTotal . ' pending validations in backlog',
                'action' => 'Process pending validations or increase bendahara capacity',
                'impact' => 'Validation bottleneck affecting system performance'
            ];
        }

        return $recommendations;
    }

    protected function calculateDataIntegrityScore(object $jaspelBySource, object $validationSources): float
    {
        // Simple integrity scoring based on data source compliance
        $petugasInputs = $jaspelBySource->where('input_role', 'petugas')->first();
        $bendaharaValidations = $validationSources->where('validator_role', 'bendahara')->first();
        
        $score = 0;
        
        if ($petugasInputs && $petugasInputs->count > 0) {
            $score += 50; // Petugas input present
        }
        
        if ($bendaharaValidations && $bendaharaValidations->validated_count > 0) {
            $score += 50; // Bendahara validation present
        }

        return $score;
    }
}