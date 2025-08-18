<?php

namespace App\Http\Controllers\Api\V2\Manajer;

use App\Http\Controllers\Controller;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\Dokter;
use App\Models\DokterPresensi;
use App\Models\UangDuduk;
use App\Models\StrategicGoal;
use App\Models\DepartmentPerformanceMetric;
use App\Models\ManagerApproval;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ManagerDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:manajer']);
    }

    /**
     * Get main dashboard summary data
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $cacheKey = "manager_dashboard_summary_{$date}";
        
        $data = Cache::remember($cacheKey, 300, function () use ($date) {
            $carbonDate = Carbon::parse($date);
            
            // Today's financial data
            $todayRevenue = PendapatanHarian::whereDate('tanggal_input', $date)->sum('nominal') ?? 0;
            $todayExpenses = PengeluaranHarian::whereDate('tanggal_input', $date)->sum('nominal') ?? 0;
            
            // Today's patient data
            $patientData = JumlahPasienHarian::whereDate('tanggal', $date)->first();
            $patientsUmum = $patientData->jumlah_pasien_umum ?? 0;
            $patientsBpjs = $patientData->jumlah_pasien_bpjs ?? 0;
            $totalPatients = $patientsUmum + $patientsBpjs;
            
            // Today's JASPEL data
            $avgJaspelDoctor = Jaspel::whereDate('tanggal', $date)
                ->where('jenis_jaspel', 'dokter_umum')
                ->where('status_validasi', 'approved')
                ->avg('nominal') ?? 0;
            
            $totalJaspelToday = Jaspel::whereDate('tanggal', $date)
                ->where('status_validasi', 'approved')
                ->sum('nominal') ?? 0;
                
            // Doctors on duty today
            $doctorsOnDuty = DokterPresensi::whereDate('tanggal', $date)
                ->with('dokter')
                ->get()
                ->map(function ($presensi) {
                    $uangDuduk = UangDuduk::where('dokter_id', $presensi->dokter_id)
                        ->whereDate('tanggal', $presensi->tanggal)
                        ->sum('nominal') ?? 0;
                        
                    return [
                        'id' => $presensi->dokter_id,
                        'nama' => $presensi->dokter->nama_lengkap ?? 'Unknown',
                        'shift' => $presensi->shift ?? 'Unknown',
                        'uang_duduk' => $uangDuduk,
                        'status' => $presensi->status ?? 'present',
                    ];
                });
            
            // Monthly comparison
            $monthlyRevenue = PendapatanHarian::whereMonth('tanggal_input', $carbonDate->month)
                ->whereYear('tanggal_input', $carbonDate->year)
                ->sum('nominal') ?? 0;
                
            $monthlyExpenses = PengeluaranHarian::whereMonth('tanggal_input', $carbonDate->month)
                ->whereYear('tanggal_input', $carbonDate->year)
                ->sum('nominal') ?? 0;
            
            // Previous month for comparison
            $lastMonth = $carbonDate->copy()->subMonth();
            $lastMonthRevenue = PendapatanHarian::whereMonth('tanggal_input', $lastMonth->month)
                ->whereYear('tanggal_input', $lastMonth->year)
                ->sum('nominal') ?? 0;
            
            $revenueChange = $this->calculatePercentageChange($monthlyRevenue, $lastMonthRevenue);
            
            return [
                'date' => $date,
                'financial' => [
                    'today_revenue' => $todayRevenue,
                    'today_expenses' => $todayExpenses,
                    'today_profit' => $todayRevenue - $todayExpenses,
                    'monthly_revenue' => $monthlyRevenue,
                    'monthly_expenses' => $monthlyExpenses,
                    'revenue_change_percent' => $revenueChange,
                ],
                'patients' => [
                    'today_total' => $totalPatients,
                    'today_umum' => $patientsUmum,
                    'today_bpjs' => $patientsBpjs,
                    'avg_revenue_per_patient' => $totalPatients > 0 ? $todayRevenue / $totalPatients : 0,
                ],
                'jaspel' => [
                    'avg_doctor_jaspel_today' => $avgJaspelDoctor,
                    'total_jaspel_today' => $totalJaspelToday,
                    'avg_jaspel_per_patient' => $totalPatients > 0 ? $totalJaspelToday / $totalPatients : 0,
                ],
                'staff' => [
                    'doctors_on_duty' => $doctorsOnDuty,
                    'total_doctors_today' => $doctorsOnDuty->count(),
                    'total_uang_duduk_today' => $doctorsOnDuty->sum('uang_duduk'),
                ],
                'updated_at' => now()->toISOString(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Get analytics charts data
     */
    public function getAnalyticsData(Request $request): JsonResponse
    {
        $period = $request->get('period', '7'); // days
        $cacheKey = "manager_analytics_{$period}";
        
        $data = Cache::remember($cacheKey, 600, function () use ($period) {
            $startDate = now()->subDays((int) $period);
            $endDate = now();
            
            // Revenue vs Expenses trend
            $financialTrend = [];
            $patientTrend = [];
            
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dayRevenue = PendapatanHarian::whereDate('tanggal_input', $date->toDateString())->sum('nominal') ?? 0;
                $dayExpenses = PengeluaranHarian::whereDate('tanggal_input', $date->toDateString())->sum('nominal') ?? 0;
                
                $patientData = JumlahPasienHarian::whereDate('tanggal', $date->toDateString())->first();
                $dayPatients = ($patientData->jumlah_pasien_umum ?? 0) + ($patientData->jumlah_pasien_bpjs ?? 0);
                
                $financialTrend[] = [
                    'date' => $date->format('Y-m-d'),
                    'revenue' => $dayRevenue,
                    'expenses' => $dayExpenses,
                    'profit' => $dayRevenue - $dayExpenses,
                ];
                
                $patientTrend[] = [
                    'date' => $date->format('Y-m-d'),
                    'umum' => $patientData->jumlah_pasien_umum ?? 0,
                    'bpjs' => $patientData->jumlah_pasien_bpjs ?? 0,
                    'total' => $dayPatients,
                ];
            }
            
            // Expense breakdown by category
            $expenseBreakdown = PengeluaranHarian::whereBetween('tanggal_input', [$startDate, $endDate])
                ->select('jenis_pengeluaran', DB::raw('SUM(nominal) as total'))
                ->groupBy('jenis_pengeluaran')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => $item->jenis_pengeluaran,
                        'amount' => $item->total,
                    ];
                });
            
            return [
                'financial_trend' => $financialTrend,
                'patient_trend' => $patientTrend,
                'expense_breakdown' => $expenseBreakdown,
                'period_days' => $period,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get staff performance data
     */
    public function getStaffPerformance(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month'); // month, week, today
        $cacheKey = "manager_staff_performance_{$period}";
        
        $data = Cache::remember($cacheKey, 300, function () use ($period) {
            $query = $this->getDateQuery($period);
            
            // Top performing doctors
            $topDoctors = Tindakan::whereBetween('tanggal_tindakan', $query)
                ->whereNotNull('dokter_id')
                ->select('dokter_id', DB::raw('COUNT(*) as procedure_count'), DB::raw('SUM(tarif) as total_revenue'))
                ->with('dokter')
                ->groupBy('dokter_id')
                ->orderBy('procedure_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->dokter_id,
                        'name' => $item->dokter->nama_lengkap ?? 'Unknown',
                        'procedures' => $item->procedure_count,
                        'revenue' => $item->total_revenue,
                        'avg_per_procedure' => $item->procedure_count > 0 ? $item->total_revenue / $item->procedure_count : 0,
                    ];
                });
            
            // JASPEL distribution
            $jaspelDistribution = Jaspel::whereBetween('tanggal', $query)
                ->where('status_validasi', 'approved')
                ->select('jenis_jaspel', DB::raw('SUM(nominal) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('jenis_jaspel')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => $item->jenis_jaspel,
                        'total_amount' => $item->total,
                        'count' => $item->count,
                        'average' => $item->count > 0 ? $item->total / $item->count : 0,
                    ];
                });
            
            // Staff efficiency metrics
            $totalProcedures = Tindakan::whereBetween('tanggal_tindakan', $query)->count();
            $totalDoctors = Dokter::where('aktif', true)->count();
            $efficiency = $totalDoctors > 0 ? $totalProcedures / $totalDoctors : 0;
            
            return [
                'top_doctors' => $topDoctors,
                'jaspel_distribution' => $jaspelDistribution,
                'efficiency_metrics' => [
                    'total_procedures' => $totalProcedures,
                    'total_doctors' => $totalDoctors,
                    'procedures_per_doctor' => round($efficiency, 2),
                ],
                'period' => $period,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get validation insights and status
     */
    public function getValidationInsights(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $cacheKey = "manager_validation_insights_{$period}";
        
        $data = Cache::remember($cacheKey, 180, function () use ($period) {
            $query = $this->getDateQuery($period);
            
            // Validation status summary
            $validationSummary = [
                'jaspel' => [
                    'pending' => Jaspel::whereBetween('tanggal', $query)->where('status_validasi', 'pending')->count(),
                    'approved' => Jaspel::whereBetween('tanggal', $query)->where('status_validasi', 'approved')->count(),
                    'rejected' => Jaspel::whereBetween('tanggal', $query)->where('status_validasi', 'rejected')->count(),
                ],
                'tindakan' => [
                    'pending' => Tindakan::whereBetween('tanggal_tindakan', $query)->where('status_validasi', 'pending')->count(),
                    'approved' => Tindakan::whereBetween('tanggal_tindakan', $query)->where('status_validasi', 'approved')->count(),
                    'rejected' => Tindakan::whereBetween('tanggal_tindakan', $query)->where('status_validasi', 'rejected')->count(),
                ],
            ];
            
            // Deviation detection
            $deviations = $this->detectDeviations($query);
            
            // Missing inputs detection
            $missingInputs = $this->detectMissingInputs($query);
            
            // Overclaim detection
            $overclaims = $this->detectOverclaims($query);
            
            return [
                'validation_summary' => $validationSummary,
                'deviations' => $deviations,
                'missing_inputs' => $missingInputs,
                'overclaims' => $overclaims,
                'insights' => $this->generateInsights($validationSummary, $deviations),
                'period' => $period,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get real-time JASPEL calculations
     */
    public function getJaspelCalculations(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $patientThreshold = $request->get('patient_threshold', 10);
        
        $data = Cache::remember("jaspel_calc_{$date}_{$patientThreshold}", 300, function () use ($date, $patientThreshold) {
            $patientData = JumlahPasienHarian::whereDate('tanggal', $date)->first();
            $totalPatients = ($patientData->jumlah_pasien_umum ?? 0) + ($patientData->jumlah_pasien_bpjs ?? 0);
            
            // JASPEL calculation based on patient count
            $jaspelEligible = $totalPatients >= $patientThreshold;
            $jaspelMultiplier = $jaspelEligible ? 1.0 : 0.8; // Reduced JASPEL if below threshold
            
            // Calculate JASPEL for each doctor type
            $jaspelRates = [
                'dokter_umum' => 200000 * $jaspelMultiplier,
                'dokter_spesialis' => 300000 * $jaspelMultiplier,
                'paramedis' => 100000 * $jaspelMultiplier,
                'administrasi' => 50000 * $jaspelMultiplier,
            ];
            
            // Patient type bonuses
            $bpjsBonus = ($patientData->jumlah_pasien_bpjs ?? 0) * 5000; // 5K per BPJS patient
            $umumBonus = ($patientData->jumlah_pasien_umum ?? 0) * 3000; // 3K per Umum patient
            
            return [
                'date' => $date,
                'patient_data' => [
                    'total' => $totalPatients,
                    'umum' => $patientData->jumlah_pasien_umum ?? 0,
                    'bpjs' => $patientData->jumlah_pasien_bpjs ?? 0,
                    'threshold_met' => $jaspelEligible,
                    'threshold' => $patientThreshold,
                ],
                'jaspel_rates' => $jaspelRates,
                'bonuses' => [
                    'bpjs_bonus' => $bpjsBonus,
                    'umum_bonus' => $umumBonus,
                    'total_bonus' => $bpjsBonus + $umumBonus,
                ],
                'total_estimated_jaspel' => array_sum($jaspelRates) + $bpjsBonus + $umumBonus,
                'multiplier_applied' => $jaspelMultiplier,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get approval workflow data
     */
    public function getApprovalWorkflows(Request $request): JsonResponse
    {
        $approvals = ManagerApproval::with(['requestedBy', 'approvedBy'])
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->get('priority'), fn($q, $priority) => $q->where('priority', $priority))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json([
            'success' => true,
            'data' => $approvals,
            'summary' => [
                'pending' => ManagerApproval::pending()->count(),
                'urgent' => ManagerApproval::urgent()->pending()->count(),
                'overdue' => ManagerApproval::overdue()->count(),
                'total_pending_value' => ManagerApproval::pending()->sum('amount') ?? 0,
            ],
        ]);
    }

    /**
     * Get strategic goals and KPIs
     */
    public function getStrategicKPIs(Request $request): JsonResponse
    {
        $goals = StrategicGoal::with(['createdBy', 'assignedTo'])
            ->when($request->get('category'), fn($q, $category) => $q->where('category', $category))
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('priority', 'asc')
            ->get()
            ->map(function ($goal) {
                return [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'category' => $goal->category,
                    'progress_percentage' => $goal->progress_percentage,
                    'current_value' => $goal->current_value,
                    'target_value' => $goal->target_value,
                    'unit' => $goal->unit,
                    'status' => $goal->status,
                    'priority' => $goal->priority,
                    'days_remaining' => $goal->days_remaining,
                    'is_overdue' => $goal->is_overdue,
                    'assigned_to' => $goal->assignedTo?->name,
                ];
            });
            
        $kpiMetrics = DepartmentPerformanceMetric::kpiOnly()
            ->currentMonth()
            ->get()
            ->groupBy('department')
            ->map(function ($metrics, $department) {
                return [
                    'department' => $department,
                    'score' => $metrics->avg('score'),
                    'metrics_count' => $metrics->count(),
                    'trend' => $metrics->first()->trend ?? 'stable',
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => [
                'strategic_goals' => $goals,
                'kpi_metrics' => $kpiMetrics->values(),
                'summary' => [
                    'active_goals' => StrategicGoal::active()->count(),
                    'completed_this_month' => StrategicGoal::where('status', 'completed')->whereMonth('completed_at', now()->month)->count(),
                    'overdue_goals' => StrategicGoal::overdue()->count(),
                    'avg_progress' => StrategicGoal::active()->get()->avg('progress_percentage') ?? 0,
                ],
            ],
        ]);
    }

    /**
     * Export dashboard data
     */
    public function exportData(Request $request): JsonResponse
    {
        $format = $request->get('format', 'pdf');
        $date = $request->get('date', now()->toDateString());
        
        try {
            // Generate export data
            $exportData = [
                'summary' => $this->getDashboardSummary($request)->getData()->data,
                'analytics' => $this->getAnalyticsData($request)->getData()->data,
                'validations' => $this->getValidationInsights($request)->getData()->data,
                'export_date' => $date,
                'export_format' => $format,
                'generated_by' => auth()->user()->name,
                'generated_at' => now()->toISOString(),
            ];
            
            // Here you would implement actual PDF/Excel generation
            // For now, return the data structure
            
            return response()->json([
                'success' => true,
                'message' => 'Export data prepared successfully',
                'data' => $exportData,
                'download_url' => route('manager.download-export', ['format' => $format, 'date' => $date]),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh dashboard data (clear cache)
     */
    public function refreshData(): JsonResponse
    {
        $patterns = [
            'manager_dashboard_summary_*',
            'manager_analytics_*',
            'manager_staff_performance_*',
            'manager_validation_insights_*',
            'jaspel_calc_*',
        ];
        
        foreach ($patterns as $pattern) {
            Cache::flush(); // Simple approach for now
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Dashboard data refreshed successfully',
            'timestamp' => now()->toISOString(),
        ]);
    }

    // Helper methods
    private function getDateQuery(string $period): array
    {
        return match ($period) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'week' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'quarter' => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
            default => [now()->subDays((int) $period)->toDateString(), now()->toDateString()],
        };
    }

    private function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function detectDeviations(array $dateRange): array
    {
        // Detect unusual patterns in data
        $avgDailyRevenue = PendapatanHarian::whereBetween('tanggal_input', $dateRange)->avg('nominal') ?? 0;
        
        $deviations = PendapatanHarian::whereBetween('tanggal_input', $dateRange)
            ->where(function ($query) use ($avgDailyRevenue) {
                $query->where('nominal', '>', $avgDailyRevenue * 1.5) // 50% above average
                      ->orWhere('nominal', '<', $avgDailyRevenue * 0.5); // 50% below average
            })
            ->get()
            ->map(function ($item) use ($avgDailyRevenue) {
                return [
                    'date' => $item->tanggal_input,
                    'amount' => $item->nominal,
                    'average' => $avgDailyRevenue,
                    'deviation_percent' => $avgDailyRevenue > 0 ? (($item->nominal - $avgDailyRevenue) / $avgDailyRevenue) * 100 : 0,
                    'type' => $item->nominal > $avgDailyRevenue ? 'spike' : 'dip',
                ];
            });
            
        return $deviations->toArray();
    }

    private function detectMissingInputs(array $dateRange): array
    {
        // Detect missing daily inputs
        $missingInputs = [];
        $startDate = Carbon::parse($dateRange[0]);
        $endDate = Carbon::parse($dateRange[1]);
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $hasRevenue = PendapatanHarian::whereDate('tanggal_input', $date->toDateString())->exists();
            $hasExpenses = PengeluaranHarian::whereDate('tanggal_input', $date->toDateString())->exists();
            $hasPatients = JumlahPasienHarian::whereDate('tanggal', $date->toDateString())->exists();
            
            if (!$hasRevenue || !$hasExpenses || !$hasPatients) {
                $missingInputs[] = [
                    'date' => $date->toDateString(),
                    'missing_revenue' => !$hasRevenue,
                    'missing_expenses' => !$hasExpenses,
                    'missing_patients' => !$hasPatients,
                    'severity' => (!$hasRevenue && !$hasExpenses && !$hasPatients) ? 'high' : 'medium',
                ];
            }
        }
        
        return $missingInputs;
    }

    private function detectOverclaims(array $dateRange): array
    {
        // Detect potential JASPEL overclaims
        return Jaspel::whereBetween('tanggal', $dateRange)
            ->where('nominal', '>', 500000) // Above 500K threshold
            ->with(['user', 'tindakan'])
            ->get()
            ->map(function ($jaspel) {
                return [
                    'id' => $jaspel->id,
                    'user_name' => $jaspel->user->name ?? 'Unknown',
                    'amount' => $jaspel->nominal,
                    'date' => $jaspel->tanggal,
                    'type' => $jaspel->jenis_jaspel,
                    'status' => $jaspel->status_validasi,
                    'risk_level' => $jaspel->nominal > 1000000 ? 'high' : 'medium',
                ];
            })
            ->toArray();
    }

    private function generateInsights(array $validationSummary, array $deviations): array
    {
        $insights = [];
        
        // Validation backlog insight
        $totalPending = $validationSummary['jaspel']['pending'] + $validationSummary['tindakan']['pending'];
        if ($totalPending > 10) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'High Validation Backlog',
                'message' => "There are {$totalPending} pending validations requiring attention.",
                'action' => 'Review validation queue',
                'priority' => 'medium',
            ];
        }
        
        // Revenue deviation insight
        if (count($deviations) > 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Revenue Pattern Detected',
                'message' => count($deviations) . ' unusual revenue patterns detected.',
                'action' => 'Review financial data',
                'priority' => 'low',
            ];
        }
        
        return $insights;
    }
}