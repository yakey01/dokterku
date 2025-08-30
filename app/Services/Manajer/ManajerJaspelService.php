<?php

namespace App\Services\Manajer;

use App\Models\Jaspel;
use App\Models\User;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManajerJaspelService
{
    private const CACHE_TTL = 600; // 10 minutes
    private const LONG_CACHE_TTL = 1800; // 30 minutes
    
    /**
     * Get comprehensive JASPEL summary and analytics
     */
    public function getJaspelAnalytics(int $month = null, int $year = null): array
    {
        try {
            $month = $month ?? now()->month;
            $year = $year ?? now()->year;
            
            $cacheKey = "manajer_jaspel_analytics_{$year}_{$month}";
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($month, $year) {
                // Get validated JASPEL records for the period
                $monthlyJaspel = Jaspel::with(['user.role', 'tindakan.jenisTindakan'])
                    ->whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->get();
                
                $totalJaspel = $monthlyJaspel->sum('total_jaspel');
                $transactionCount = $monthlyJaspel->count();
                $uniqueRecipients = $monthlyJaspel->unique('user_id')->count();
                
                // Previous month comparison
                $prevMonth = $month == 1 ? 12 : $month - 1;
                $prevYear = $month == 1 ? $year - 1 : $year;
                $prevJaspel = Jaspel::whereMonth('tanggal', $prevMonth)
                    ->whereYear('tanggal', $prevYear)
                    ->where('status_validasi', 'disetujui')
                    ->sum('total_jaspel');
                
                $growth = $prevJaspel > 0 ? (($totalJaspel - $prevJaspel) / $prevJaspel) * 100 : 0;
                
                // Distribution by type
                $jaspelByType = $monthlyJaspel->groupBy('jenis_jaspel')
                    ->map(function ($group, $type) use ($totalJaspel) {
                        $typeTotal = $group->sum('total_jaspel');
                        return [
                            'type' => ucwords(str_replace('_', ' ', $type)),
                            'total' => (float) $typeTotal,
                            'formatted' => 'Rp ' . number_format($typeTotal, 0, ',', '.'),
                            'percentage' => $totalJaspel > 0 ? round(($typeTotal / $totalJaspel) * 100, 1) : 0,
                            'count' => $group->count(),
                            'avg_per_transaction' => $group->count() > 0 ? round($typeTotal / $group->count(), 0) : 0
                        ];
                    })
                    ->sortByDesc('total')
                    ->values();
                
                // Distribution by role/department
                $jaspelByRole = $monthlyJaspel->groupBy(function ($jaspel) {
                    return $jaspel->user->role?->name ?: 'Unknown';
                })
                ->map(function ($group, $role) use ($totalJaspel) {
                    $roleTotal = $group->sum('total_jaspel');
                    return [
                        'role' => $role,
                        'total' => (float) $roleTotal,
                        'formatted' => 'Rp ' . number_format($roleTotal, 0, ',', '.'),
                        'percentage' => $totalJaspel > 0 ? round(($roleTotal / $totalJaspel) * 100, 1) : 0,
                        'count' => $group->count(),
                        'unique_recipients' => $group->unique('user_id')->count(),
                        'avg_per_person' => $group->unique('user_id')->count() > 0 ? 
                            round($roleTotal / $group->unique('user_id')->count(), 0) : 0
                    ];
                })
                ->sortByDesc('total')
                ->values();
                
                // Daily distribution
                $dailyDistribution = $monthlyJaspel->groupBy(function ($jaspel) {
                    return $jaspel->tanggal->format('Y-m-d');
                })
                ->map(function ($group, $date) {
                    return [
                        'date' => $date,
                        'day_name' => Carbon::parse($date)->format('l'),
                        'total' => (float) $group->sum('total_jaspel'),
                        'formatted' => 'Rp ' . number_format($group->sum('total_jaspel'), 0, ',', '.'),
                        'count' => $group->count(),
                        'unique_recipients' => $group->unique('user_id')->count()
                    ];
                })
                ->sortBy('date')
                ->values();
                
                return [
                    'success' => true,
                    'message' => 'JASPEL analytics retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => $month,
                            'year' => $year,
                            'label' => Carbon::create($year, $month)->format('F Y')
                        ],
                        'summary' => [
                            'total_jaspel' => (float) $totalJaspel,
                            'formatted_total' => 'Rp ' . number_format($totalJaspel, 0, ',', '.'),
                            'transaction_count' => $transactionCount,
                            'unique_recipients' => $uniqueRecipients,
                            'avg_per_transaction' => $transactionCount > 0 ? round($totalJaspel / $transactionCount, 0) : 0,
                            'avg_per_recipient' => $uniqueRecipients > 0 ? round($totalJaspel / $uniqueRecipients, 0) : 0,
                            'growth_percentage' => round($growth, 2),
                            'growth_status' => $growth > 0 ? 'increase' : ($growth < 0 ? 'decrease' : 'stable')
                        ],
                        'distribution' => [
                            'by_type' => $jaspelByType,
                            'by_role' => $jaspelByRole,
                            'daily' => $dailyDistribution
                        ]
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting JASPEL analytics', [
                'error' => $e->getMessage(),
                'month' => $month,
                'year' => $year
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve JASPEL analytics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Get doctor ranking by JASPEL performance
     */
    public function getDoctorRanking(int $month = null, int $year = null, int $limit = 20): array
    {
        try {
            $month = $month ?? now()->month;
            $year = $year ?? now()->year;
            $limit = min($limit, 50); // Max 50 items
            
            $cacheKey = "manajer_jaspel_doctor_ranking_{$year}_{$month}_{$limit}";
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($month, $year, $limit) {
                // Get doctors with their JASPEL performance
                $doctorPerformance = User::with(['role', 'dokter'])
                    ->whereHas('role', function ($query) {
                        $query->where('name', 'dokter');
                    })
                    ->where('is_active', true)
                    ->get()
                    ->map(function ($user) use ($month, $year) {
                        // JASPEL earnings for the period
                        $jaspelRecords = Jaspel::where('user_id', $user->id)
                            ->whereMonth('tanggal', $month)
                            ->whereYear('tanggal', $year)
                            ->where('status_validasi', 'disetujui')
                            ->get();
                        
                        $totalJaspel = $jaspelRecords->sum('total_jaspel');
                        $transactionCount = $jaspelRecords->count();
                        
                        // Get procedure count
                        $procedureCount = Tindakan::where('dokter_id', optional($user->dokter)->id)
                            ->whereMonth('tanggal_tindakan', $month)
                            ->whereYear('tanggal_tindakan', $year)
                            ->count();
                        
                        // Calculate JASPEL per procedure efficiency
                        $efficiency = $procedureCount > 0 ? $totalJaspel / $procedureCount : 0;
                        
                        // Get JASPEL by type breakdown
                        $jaspelByType = $jaspelRecords->groupBy('jenis_jaspel')
                            ->map(function ($group, $type) {
                                return [
                                    'type' => ucwords(str_replace('_', ' ', $type)),
                                    'total' => (float) $group->sum('total_jaspel'),
                                    'count' => $group->count()
                                ];
                            })
                            ->values();
                        
                        // Previous month comparison
                        $prevMonth = $month == 1 ? 12 : $month - 1;
                        $prevYear = $month == 1 ? $year - 1 : $year;
                        $prevJaspel = Jaspel::where('user_id', $user->id)
                            ->whereMonth('tanggal', $prevMonth)
                            ->whereYear('tanggal', $prevYear)
                            ->where('status_validasi', 'disetujui')
                            ->sum('total_jaspel');
                        
                        $growth = $prevJaspel > 0 ? (($totalJaspel - $prevJaspel) / $prevJaspel) * 100 : 0;
                        
                        return [
                            'user_id' => $user->id,
                            'doctor_id' => optional($user->dokter)->id,
                            'name' => $user->name,
                            'specialization' => optional($user->dokter)->spesialisasi ?: 'Umum',
                            'jaspel_metrics' => [
                                'total_jaspel' => (float) $totalJaspel,
                                'formatted_jaspel' => 'Rp ' . number_format($totalJaspel, 0, ',', '.'),
                                'transaction_count' => $transactionCount,
                                'procedure_count' => $procedureCount,
                                'avg_per_transaction' => $transactionCount > 0 ? round($totalJaspel / $transactionCount, 0) : 0,
                                'avg_per_procedure' => $procedureCount > 0 ? round($totalJaspel / $procedureCount, 0) : 0,
                                'efficiency_score' => round($efficiency, 0),
                                'growth_percentage' => round($growth, 1),
                                'growth_status' => $growth > 0 ? 'increase' : ($growth < 0 ? 'decrease' : 'stable')
                            ],
                            'jaspel_by_type' => $jaspelByType,
                            'performance_score' => $this->calculateJaspelPerformanceScore($totalJaspel, $transactionCount, $procedureCount, $efficiency)
                        ];
                    })
                    ->sortByDesc('jaspel_metrics.total_jaspel')
                    ->take($limit)
                    ->values();
                
                // Calculate statistics
                $totalDoctors = $doctorPerformance->count();
                $totalJaspelAll = $doctorPerformance->sum('jaspel_metrics.total_jaspel');
                $avgJaspelPerDoctor = $totalDoctors > 0 ? $totalJaspelAll / $totalDoctors : 0;
                $topPerformer = $doctorPerformance->first();
                
                return [
                    'success' => true,
                    'message' => 'Doctor JASPEL ranking retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => $month,
                            'year' => $year,
                            'label' => Carbon::create($year, $month)->format('F Y')
                        ],
                        'rankings' => $doctorPerformance,
                        'summary' => [
                            'total_doctors' => $totalDoctors,
                            'total_jaspel_earned' => (float) $totalJaspelAll,
                            'formatted_total_jaspel' => 'Rp ' . number_format($totalJaspelAll, 0, ',', '.'),
                            'avg_jaspel_per_doctor' => round($avgJaspelPerDoctor, 0),
                            'top_performer' => $topPerformer ? [
                                'name' => $topPerformer['name'],
                                'total_jaspel' => $topPerformer['jaspel_metrics']['total_jaspel'],
                                'formatted_jaspel' => $topPerformer['jaspel_metrics']['formatted_jaspel']
                            ] : null,
                            'performance_distribution' => [
                                'high_performers' => $doctorPerformance->where('jaspel_metrics.total_jaspel', '>=', $avgJaspelPerDoctor * 1.5)->count(),
                                'average_performers' => $doctorPerformance->whereBetween('jaspel_metrics.total_jaspel', [$avgJaspelPerDoctor * 0.5, $avgJaspelPerDoctor * 1.5])->count(),
                                'below_average' => $doctorPerformance->where('jaspel_metrics.total_jaspel', '<', $avgJaspelPerDoctor * 0.5)->count()
                            ]
                        ]
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting doctor JASPEL ranking', [
                'error' => $e->getMessage(),
                'month' => $month,
                'year' => $year
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve doctor JASPEL ranking',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Get monthly JASPEL distribution analytics
     */
    public function getMonthlyDistribution(int $months = 6): array
    {
        try {
            $months = min($months, 12); // Max 12 months
            $cacheKey = "manajer_jaspel_monthly_distribution_{$months}";
            
            return Cache::remember($cacheKey, self::LONG_CACHE_TTL, function () use ($months) {
                $trends = [];
                
                for ($i = $months - 1; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    
                    // Monthly JASPEL data
                    $monthlyJaspel = Jaspel::whereYear('tanggal', $date->year)
                        ->whereMonth('tanggal', $date->month)
                        ->where('status_validasi', 'disetujui')
                        ->get();
                    
                    $totalJaspel = $monthlyJaspel->sum('total_jaspel');
                    $transactionCount = $monthlyJaspel->count();
                    $uniqueRecipients = $monthlyJaspel->unique('user_id')->count();
                    
                    // Distribution by type
                    $typeDistribution = $monthlyJaspel->groupBy('jenis_jaspel')
                        ->map(function ($group, $type) {
                            return [
                                'type' => ucwords(str_replace('_', ' ', $type)),
                                'total' => (float) $group->sum('total_jaspel'),
                                'count' => $group->count()
                            ];
                        })
                        ->sortByDesc('total')
                        ->values();
                    
                    $trends[] = [
                        'month' => $date->format('M'),
                        'year' => $date->year,
                        'label' => $date->format('M Y'),
                        'total_jaspel' => (float) $totalJaspel,
                        'formatted_total' => 'Rp ' . number_format($totalJaspel, 0, ',', '.'),
                        'transaction_count' => $transactionCount,
                        'unique_recipients' => $uniqueRecipients,
                        'avg_per_transaction' => $transactionCount > 0 ? round($totalJaspel / $transactionCount, 0) : 0,
                        'avg_per_recipient' => $uniqueRecipients > 0 ? round($totalJaspel / $uniqueRecipients, 0) : 0,
                        'type_distribution' => $typeDistribution
                    ];
                }
                
                // Calculate overall trends
                $avgMonthlyJaspel = collect($trends)->avg('total_jaspel');
                $avgMonthlyTransactions = collect($trends)->avg('transaction_count');
                $jaspelTrend = $this->calculateTrend($trends, 'total_jaspel');
                $transactionTrend = $this->calculateTrend($trends, 'transaction_count');
                
                return [
                    'success' => true,
                    'message' => 'Monthly JASPEL distribution retrieved successfully',
                    'data' => [
                        'trends' => $trends,
                        'summary' => [
                            'avg_monthly_jaspel' => round($avgMonthlyJaspel, 0),
                            'avg_monthly_transactions' => round($avgMonthlyTransactions, 0),
                            'months_analyzed' => $months,
                            'jaspel_trend' => $jaspelTrend,
                            'transaction_trend' => $transactionTrend,
                            'total_period_jaspel' => (float) collect($trends)->sum('total_jaspel'),
                            'total_period_transactions' => collect($trends)->sum('transaction_count')
                        ]
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting monthly JASPEL distribution', [
                'error' => $e->getMessage(),
                'months' => $months
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve monthly JASPEL distribution',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * JASPEL calculator functionality
     */
    public function calculateJaspel(int $tindakanId, array $parameters = []): array
    {
        try {
            // Get tindakan details
            $tindakan = Tindakan::with(['jenisTindakan', 'dokter.user'])
                ->find($tindakanId);
            
            if (!$tindakan) {
                return [
                    'success' => false,
                    'message' => 'Tindakan not found',
                    'error' => 'Invalid tindakan ID'
                ];
            }
            
            $jenisTindakan = $tindakan->jenisTindakan;
            if (!$jenisTindakan) {
                return [
                    'success' => false,
                    'message' => 'Jenis tindakan not found',
                    'error' => 'Tindakan has no associated jenis_tindakan'
                ];
            }
            
            // Base JASPEL calculation
            $baseJaspel = $jenisTindakan->tarif_jaspel ?? 0;
            $multiplier = $parameters['multiplier'] ?? 1;
            $additionalFee = $parameters['additional_fee'] ?? 0;
            $deduction = $parameters['deduction'] ?? 0;
            
            // Calculate final JASPEL
            $calculatedJaspel = ($baseJaspel * $multiplier) + $additionalFee - $deduction;
            $finalJaspel = max(0, $calculatedJaspel); // Ensure non-negative
            
            // Determine JASPEL type based on parameters or tindakan
            $jaspelType = $parameters['jenis_jaspel'] ?? $this->determineJaspelType($tindakan);
            
            return [
                'success' => true,
                'message' => 'JASPEL calculated successfully',
                'data' => [
                    'tindakan' => [
                        'id' => $tindakan->id,
                        'nama_tindakan' => $jenisTindakan->nama_tindakan,
                        'tarif_jaspel' => (float) $baseJaspel,
                        'dokter' => $tindakan->dokter?->user?->name ?: 'Unknown'
                    ],
                    'calculation' => [
                        'base_jaspel' => (float) $baseJaspel,
                        'multiplier' => (float) $multiplier,
                        'additional_fee' => (float) $additionalFee,
                        'deduction' => (float) $deduction,
                        'calculated_amount' => (float) $calculatedJaspel,
                        'final_amount' => (float) $finalJaspel,
                        'formatted_amount' => 'Rp ' . number_format($finalJaspel, 0, ',', '.')
                    ],
                    'jaspel_details' => [
                        'jenis_jaspel' => $jaspelType,
                        'user_id' => $tindakan->dokter?->user_id,
                        'tindakan_id' => $tindakan->id,
                        'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d')
                    ],
                    'validation_rules' => [
                        'min_amount' => 0,
                        'requires_approval' => $finalJaspel > 1000000, // Amounts > 1M require approval
                        'approval_threshold' => 1000000
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error calculating JASPEL', [
                'error' => $e->getMessage(),
                'tindakan_id' => $tindakanId,
                'parameters' => $parameters
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to calculate JASPEL',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Get recent JASPEL transactions with filtering
     */
    public function getRecentTransactions(int $limit = 20, array $filters = []): array
    {
        try {
            $limit = min($limit, 100); // Max 100 items
            
            $query = Jaspel::with(['user', 'tindakan.jenisTindakan', 'validasiBy'])
                ->where('status_validasi', 'disetujui');
            
            // Apply filters
            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
            
            if (!empty($filters['jenis_jaspel'])) {
                $query->where('jenis_jaspel', $filters['jenis_jaspel']);
            }
            
            if (!empty($filters['date_from'])) {
                $query->whereDate('tanggal', '>=', $filters['date_from']);
            }
            
            if (!empty($filters['date_to'])) {
                $query->whereDate('tanggal', '<=', $filters['date_to']);
            }
            
            if (!empty($filters['min_amount'])) {
                $query->where('total_jaspel', '>=', $filters['min_amount']);
            }
            
            $recentTransactions = $query->orderBy('validasi_at', 'desc')
                ->take($limit)
                ->get()
                ->map(function ($jaspel) {
                    return [
                        'id' => $jaspel->id,
                        'user' => [
                            'id' => $jaspel->user->id,
                            'name' => $jaspel->user->name,
                            'role' => $jaspel->user->role?->name ?: 'Unknown'
                        ],
                        'jenis_jaspel' => ucwords(str_replace('_', ' ', $jaspel->jenis_jaspel)),
                        'total_jaspel' => (float) $jaspel->total_jaspel,
                        'formatted_jaspel' => 'Rp ' . number_format($jaspel->total_jaspel, 0, ',', '.'),
                        'tindakan' => $jaspel->tindakan ? [
                            'id' => $jaspel->tindakan->id,
                            'nama_tindakan' => $jaspel->tindakan->jenisTindakan?->nama_tindakan ?: 'N/A'
                        ] : null,
                        'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                        'validated_at' => $jaspel->validasi_at?->format('Y-m-d H:i:s'),
                        'validated_by' => $jaspel->validasiBy?->name
                    ];
                });
            
            return [
                'success' => true,
                'message' => 'Recent JASPEL transactions retrieved successfully',
                'data' => [
                    'transactions' => $recentTransactions,
                    'filters_applied' => $filters,
                    'total_found' => $recentTransactions->count(),
                    'summary' => [
                        'total_amount' => (float) $recentTransactions->sum('total_jaspel'),
                        'formatted_total' => 'Rp ' . number_format($recentTransactions->sum('total_jaspel'), 0, ',', '.'),
                        'avg_amount' => $recentTransactions->count() > 0 ? 
                            round($recentTransactions->avg('total_jaspel'), 0) : 0,
                        'unique_recipients' => $recentTransactions->unique('user.id')->count()
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error getting recent JASPEL transactions', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'limit' => $limit
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve recent JASPEL transactions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Helper method to calculate JASPEL performance score
     */
    private function calculateJaspelPerformanceScore(float $totalJaspel, int $transactionCount, int $procedureCount, float $efficiency): float
    {
        // Weighted scoring system
        $jaspelScore = min(($totalJaspel / 5000000) * 40, 40); // Max 40 points for JASPEL amount (per 5M)
        $volumeScore = min(($transactionCount / 20) * 30, 30); // Max 30 points for transaction volume (per 20 transactions)
        $procedureScore = min(($procedureCount / 50) * 20, 20); // Max 20 points for procedure count (per 50 procedures)
        $efficiencyScore = min(($efficiency / 100000) * 10, 10); // Max 10 points for efficiency (per 100K per procedure)
        
        return round($jaspelScore + $volumeScore + $procedureScore + $efficiencyScore, 1);
    }
    
    /**
     * Helper method to calculate trend
     */
    private function calculateTrend(array $trends, string $metric): array
    {
        if (count($trends) < 2) {
            return ['direction' => 'stable', 'percentage' => 0];
        }
        
        $latest = end($trends)[$metric];
        $previous = prev($trends)[$metric];
        
        if ($previous == 0) {
            return ['direction' => 'stable', 'percentage' => 0];
        }
        
        $change = (($latest - $previous) / $previous) * 100;
        
        return [
            'direction' => $change > 2 ? 'up' : ($change < -2 ? 'down' : 'stable'),
            'percentage' => round(abs($change), 1)
        ];
    }
    
    /**
     * Helper method to determine JASPEL type
     */
    private function determineJaspelType(Tindakan $tindakan): string
    {
        // Logic to determine JASPEL type based on tindakan properties
        $jenisTindakan = $tindakan->jenisTindakan;
        
        if (!$jenisTindakan) {
            return 'jaspel_umum';
        }
        
        // You can expand this logic based on your business rules
        $namaTindakan = strtolower($jenisTindakan->nama_tindakan);
        
        if (str_contains($namaTindakan, 'operasi') || str_contains($namaTindakan, 'bedah')) {
            return 'jaspel_operasi';
        }
        
        if (str_contains($namaTindakan, 'konsultasi') || str_contains($namaTindakan, 'pemeriksaan')) {
            return 'jaspel_konsultasi';
        }
        
        if (str_contains($namaTindakan, 'laboratorium') || str_contains($namaTindakan, 'lab')) {
            return 'jaspel_laboratorium';
        }
        
        return 'jaspel_umum';
    }
}