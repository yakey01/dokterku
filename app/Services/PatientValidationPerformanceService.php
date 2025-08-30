<?php

namespace App\Services;

use App\Models\JumlahPasienHarian;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Patient Validation Performance Service
 * 
 * Monitors and analyzes patient validation workflow performance
 * Provides metrics for bendahara efficiency and SLA tracking
 */
class PatientValidationPerformanceService
{
    /**
     * Get comprehensive validation performance metrics
     */
    public function getValidationMetrics(): array
    {
        return Cache::remember('patient_validation_metrics', 600, function () { // 10 minutes cache
            try {
                $today = Carbon::today();
                $thisMonth = Carbon::now();
                
                // Basic validation statistics
                $totalRecords = JumlahPasienHarian::count();
                $pendingCount = JumlahPasienHarian::where('status_validasi', 'pending')->count();
                $approvedCount = JumlahPasienHarian::where('status_validasi', 'approved')->count();
                $rejectedCount = JumlahPasienHarian::where('status_validasi', 'rejected')->count();
                
                // Validation speed analysis
                $avgValidationHours = JumlahPasienHarian::whereNotNull('validasi_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, validasi_at)) as avg_hours')
                    ->value('avg_hours') ?? 0;
                
                // Today's validation activity
                $todayValidations = JumlahPasienHarian::whereDate('validasi_at', $today)->count();
                $todayPending = JumlahPasienHarian::whereDate('created_at', $today)
                    ->where('status_validasi', 'pending')
                    ->count();
                
                // Monthly validation performance
                $monthlyApproved = JumlahPasienHarian::whereMonth('validasi_at', $thisMonth->month)
                    ->whereYear('validasi_at', $thisMonth->year)
                    ->where('status_validasi', 'approved')
                    ->count();
                
                $monthlyRejected = JumlahPasienHarian::whereMonth('validasi_at', $thisMonth->month)
                    ->whereYear('validasi_at', $thisMonth->year)
                    ->where('status_validasi', 'rejected')
                    ->count();
                
                // Validator performance
                $validatorStats = User::whereHas('validatedPatients')
                    ->withCount(['validatedPatients as total_validations'])
                    ->withAvg('validatedPatients as avg_validation_hours', 'TIMESTAMPDIFF(HOUR, created_at, validasi_at)')
                    ->get()
                    ->map(function ($user) {
                        return [
                            'name' => $user->name,
                            'total_validations' => $user->total_validations,
                            'avg_hours' => round($user->avg_validation_hours ?? 0, 2)
                        ];
                    });
                
                // Patient volume analysis
                $avgPatientsPerEntry = JumlahPasienHarian::where('status_validasi', 'approved')
                    ->selectRaw('AVG(jumlah_pasien_umum + jumlah_pasien_bpjs) as avg_patients')
                    ->value('avg_patients') ?? 0;
                
                $totalValidatedPatients = JumlahPasienHarian::where('status_validasi', 'approved')
                    ->selectRaw('SUM(jumlah_pasien_umum + jumlah_pasien_bpjs) as total')
                    ->value('total') ?? 0;
                
                // SLA performance (target: < 24 hours)
                $slaCompliant = JumlahPasienHarian::whereNotNull('validasi_at')
                    ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, validasi_at) <= 24')
                    ->count();
                
                $totalValidated = JumlahPasienHarian::whereNotNull('validasi_at')->count();
                $slaCompliance = $totalValidated > 0 ? ($slaCompliant / $totalValidated) * 100 : 0;
                
                return [
                    'success' => true,
                    'data' => [
                        'overview' => [
                            'total_records' => $totalRecords,
                            'pending_count' => $pendingCount,
                            'approved_count' => $approvedCount,
                            'rejected_count' => $rejectedCount,
                            'approval_rate' => $totalRecords > 0 ? round(($approvedCount / $totalRecords) * 100, 2) : 0
                        ],
                        'performance' => [
                            'avg_validation_hours' => round($avgValidationHours, 2),
                            'sla_compliance_percentage' => round($slaCompliance, 2),
                            'today_validations' => $todayValidations,
                            'today_pending' => $todayPending,
                            'monthly_approved' => $monthlyApproved,
                            'monthly_rejected' => $monthlyRejected
                        ],
                        'volume_analysis' => [
                            'avg_patients_per_entry' => round($avgPatientsPerEntry, 1),
                            'total_validated_patients' => (int) $totalValidatedPatients,
                            'avg_daily_patients' => $approvedCount > 0 ? round($totalValidatedPatients / $approvedCount, 1) : 0
                        ],
                        'validator_performance' => $validatorStats->toArray(),
                        'health_score' => $this->calculateHealthScore([
                            'sla_compliance' => $slaCompliance,
                            'approval_rate' => $totalRecords > 0 ? ($approvedCount / $totalRecords) * 100 : 0,
                            'pending_ratio' => $totalRecords > 0 ? ($pendingCount / $totalRecords) * 100 : 0
                        ]),
                        'last_updated' => now()->toISOString()
                    ]
                ];
                
            } catch (\Exception $e) {
                Log::error('PatientValidationPerformanceService: Failed to get metrics', [
                    'error' => $e->getMessage()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to get validation performance metrics',
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Get real-time validation queue analysis
     */
    public function getValidationQueue(): array
    {
        try {
            $pendingRecords = JumlahPasienHarian::where('status_validasi', 'pending')
                ->with(['inputBy', 'dokter'])
                ->orderBy('created_at', 'asc')
                ->get();
            
            $queueAnalysis = [
                'total_pending' => $pendingRecords->count(),
                'total_patients_pending' => $pendingRecords->sum(function($record) {
                    return $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs;
                }),
                'avg_wait_hours' => $pendingRecords->avg(function($record) {
                    return $record->created_at->diffInHours(now());
                }),
                'oldest_pending_hours' => $pendingRecords->max(function($record) {
                    return $record->created_at->diffInHours(now());
                }),
                'by_date' => $pendingRecords->groupBy(function($record) {
                    return $record->tanggal->toDateString();
                })->map->count(),
                'by_doctor' => $pendingRecords->groupBy('dokter_id')->map(function($group) {
                    $doctor = $group->first()->dokter;
                    return [
                        'doctor_name' => $doctor?->nama_lengkap ?? 'Unknown',
                        'count' => $group->count(),
                        'total_patients' => $group->sum(fn($r) => $r->jumlah_pasien_umum + $r->jumlah_pasien_bpjs)
                    ];
                })->values(),
                'priority_items' => $pendingRecords->filter(function($record) {
                    $totalPatients = $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs;
                    $waitHours = $record->created_at->diffInHours(now());
                    return $totalPatients > 50 || $waitHours > 24 || $record->jaspel_rupiah > 500000;
                })->map(function($record) {
                    return [
                        'id' => $record->id,
                        'date' => $record->tanggal->toDateString(),
                        'doctor' => $record->dokter?->nama_lengkap ?? 'Unknown',
                        'total_patients' => $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs,
                        'jaspel_amount' => $record->jaspel_rupiah,
                        'wait_hours' => $record->created_at->diffInHours(now()),
                        'priority_reason' => $this->getPriorityReason($record)
                    ];
                })->values()
            ];
            
            return [
                'success' => true,
                'data' => $queueAnalysis,
                'recommendations' => $this->getQueueRecommendations($queueAnalysis)
            ];
            
        } catch (\Exception $e) {
            Log::error('PatientValidationPerformanceService: Failed to get validation queue', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get validation queue analysis',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate overall validation workflow health score
     */
    private function calculateHealthScore(array $metrics): array
    {
        $slaWeight = 0.4;
        $approvalWeight = 0.3;
        $pendingWeight = 0.3;
        
        $slaScore = min(100, $metrics['sla_compliance']);
        $approvalScore = min(100, $metrics['approval_rate']);
        $pendingScore = max(0, 100 - $metrics['pending_ratio']); // Lower pending ratio = higher score
        
        $overallScore = ($slaScore * $slaWeight) + ($approvalScore * $approvalWeight) + ($pendingScore * $pendingWeight);
        
        return [
            'overall_score' => round($overallScore, 2),
            'rating' => $this->getHealthRating($overallScore),
            'components' => [
                'sla_score' => round($slaScore, 2),
                'approval_score' => round($approvalScore, 2), 
                'pending_score' => round($pendingScore, 2)
            ]
        ];
    }

    /**
     * Get health rating based on score
     */
    private function getHealthRating(float $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 80 => 'good',
            $score >= 70 => 'fair',
            $score >= 60 => 'needs_improvement',
            default => 'critical'
        };
    }

    /**
     * Get priority reason for validation item
     */
    private function getPriorityReason(JumlahPasienHarian $record): string
    {
        $reasons = [];
        $totalPatients = $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs;
        $waitHours = $record->created_at->diffInHours(now());
        
        if ($totalPatients > 50) {
            $reasons[] = "High volume ({$totalPatients} patients)";
        }
        
        if ($waitHours > 24) {
            $reasons[] = "Overdue ({$waitHours}h waiting)";
        }
        
        if ($record->jaspel_rupiah > 500000) {
            $reasons[] = "High JASPEL (Rp " . number_format($record->jaspel_rupiah) . ")";
        }
        
        return implode(', ', $reasons) ?: 'Standard';
    }

    /**
     * Get actionable recommendations for validation queue
     */
    private function getQueueRecommendations(array $queueData): array
    {
        $recommendations = [];
        
        if ($queueData['total_pending'] > 10) {
            $recommendations[] = [
                'type' => 'workflow',
                'priority' => 'high',
                'message' => "High pending count ({$queueData['total_pending']} items) - Consider bulk validation"
            ];
        }
        
        if ($queueData['avg_wait_hours'] > 24) {
            $recommendations[] = [
                'type' => 'sla',
                'priority' => 'urgent', 
                'message' => "SLA breach - Average wait time " . round($queueData['avg_wait_hours'], 1) . " hours"
            ];
        }
        
        if ($queueData['oldest_pending_hours'] > 48) {
            $recommendations[] = [
                'type' => 'urgent',
                'priority' => 'critical',
                'message' => "Critical delay - Oldest item waiting " . round($queueData['oldest_pending_hours'], 1) . " hours"
            ];
        }
        
        if (count($queueData['priority_items']) > 0) {
            $recommendations[] = [
                'type' => 'priority',
                'priority' => 'high',
                'message' => count($queueData['priority_items']) . " high-priority items need immediate attention"
            ];
        }
        
        return $recommendations;
    }

    /**
     * Get daily validation summary for dashboard widget
     */
    public function getDailyValidationSummary(): array
    {
        return Cache::remember('daily_validation_summary', 300, function () { // 5 minutes cache
            try {
                $today = Carbon::today();
                
                return [
                    'success' => true,
                    'data' => [
                        'date' => $today->toDateString(),
                        'formatted_date' => $today->format('d M Y'),
                        'entries_today' => JumlahPasienHarian::whereDate('created_at', $today)->count(),
                        'validations_today' => JumlahPasienHarian::whereDate('validasi_at', $today)->count(),
                        'approved_today' => JumlahPasienHarian::whereDate('validasi_at', $today)
                            ->where('status_validasi', 'approved')
                            ->count(),
                        'rejected_today' => JumlahPasienHarian::whereDate('validasi_at', $today)
                            ->where('status_validasi', 'rejected')
                            ->count(),
                        'total_patients_validated_today' => JumlahPasienHarian::whereDate('validasi_at', $today)
                            ->where('status_validasi', 'approved')
                            ->selectRaw('SUM(jumlah_pasien_umum + jumlah_pasien_bpjs) as total')
                            ->value('total') ?? 0,
                        'total_jaspel_approved_today' => JumlahPasienHarian::whereDate('validasi_at', $today)
                            ->where('status_validasi', 'approved')
                            ->sum('jaspel_rupiah') ?? 0,
                        'pending_count' => JumlahPasienHarian::where('status_validasi', 'pending')->count(),
                        'efficiency_score' => $this->calculateDailyEfficiency($today)
                    ]
                ];
                
            } catch (\Exception $e) {
                Log::error('PatientValidationPerformanceService: Failed to get daily summary', [
                    'error' => $e->getMessage()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to get daily validation summary'
                ];
            }
        });
    }

    /**
     * Calculate daily validation efficiency score
     */
    private function calculateDailyEfficiency(Carbon $date): float
    {
        $entriesCreated = JumlahPasienHarian::whereDate('created_at', $date)->count();
        $validationsCompleted = JumlahPasienHarian::whereDate('validasi_at', $date)->count();
        
        if ($entriesCreated == 0) {
            return 100; // No entries to validate = perfect efficiency
        }
        
        // Calculate efficiency as percentage of same-day validation
        $efficiency = ($validationsCompleted / $entriesCreated) * 100;
        return min(100, $efficiency); // Cap at 100%
    }

    /**
     * Trigger performance alert if metrics are poor
     */
    public function checkPerformanceAlerts(): array
    {
        $metrics = $this->getValidationMetrics();
        
        if (!$metrics['success']) {
            return ['alerts' => []];
        }
        
        $alerts = [];
        $data = $metrics['data'];
        
        // Check SLA compliance
        if ($data['performance']['sla_compliance_percentage'] < 80) {
            $alerts[] = [
                'type' => 'sla_breach',
                'severity' => 'high',
                'message' => 'SLA compliance below 80% - Validation taking too long',
                'metric' => $data['performance']['sla_compliance_percentage'] . '%',
                'action' => 'Consider bulk validation or auto-approval rules'
            ];
        }
        
        // Check pending backlog
        if ($data['overview']['pending_count'] > 20) {
            $alerts[] = [
                'type' => 'backlog',
                'severity' => 'medium',
                'message' => 'High pending count may cause delays',
                'metric' => $data['overview']['pending_count'] . ' pending items',
                'action' => 'Recommend bulk approval for standard items'
            ];
        }
        
        // Check daily efficiency
        $dailySummary = $this->getDailyValidationSummary();
        if ($dailySummary['success'] && $dailySummary['data']['efficiency_score'] < 50) {
            $alerts[] = [
                'type' => 'efficiency',
                'severity' => 'medium', 
                'message' => 'Daily validation efficiency below 50%',
                'metric' => round($dailySummary['data']['efficiency_score'], 1) . '%',
                'action' => 'Focus on same-day validation completion'
            ];
        }
        
        return ['alerts' => $alerts];
    }
}