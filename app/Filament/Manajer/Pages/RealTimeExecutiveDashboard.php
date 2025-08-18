<?php

namespace App\Filament\Manajer\Pages;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\StrategicGoal;
use App\Models\DepartmentPerformanceMetric;
use App\Models\ManagerApproval;
use App\Models\User;
use App\Models\Dokter;
use App\Models\Pegawai;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Filament\Actions\Action;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Carbon\Carbon;

class RealTimeExecutiveDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    
    protected static string $view = 'filament.manajer.pages.real-time-executive-dashboard';
    
    protected static ?string $title = 'Executive Dashboard';
    
    protected static ?string $navigationLabel = 'Real-Time Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = '📊 Executive Dashboard';
    
    protected static ?string $slug = 'real-time-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
    
    public function mount(): void
    {
        // Initialize real-time dashboard data
    }
    
    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('🔄 Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->size(ActionSize::Small)
                ->action(fn () => redirect()->to(request()->url())),
                
            Action::make('auto_calculate_kpis')
                ->label('📊 Calculate KPIs')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->size(ActionSize::Small)
                ->action(function () {
                    $this->autoCalculateKPIs();
                    $this->notify('success', '📊 KPIs auto-calculated from real data!');
                }),
        ];
    }
    
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    
    // Real Financial Data (No Mock)
    public function getFinancialKPIs(): array
    {
        $currentMonth = now();
        $lastMonth = now()->subMonth();
        
        // Current month real data
        $currentRevenue = PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal') ?? 0;
            
        $currentExpenses = PengeluaranHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal') ?? 0;
            
        $currentPatients = JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->sum(DB::raw('COALESCE(jumlah_pasien_umum, 0) + COALESCE(jumlah_pasien_bpjs, 0)')) ?? 0;
            
        $currentProcedures = Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
            ->whereYear('tanggal_tindakan', $currentMonth->year)
            ->count();

        $currentJaspel = Jaspel::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->where('status_validasi', 'approved')
            ->sum('nominal') ?? 0;
        
        // Last month real data
        $lastRevenue = PendapatanHarian::whereMonth('tanggal_input', $lastMonth->month)
            ->whereYear('tanggal_input', $lastMonth->year)
            ->sum('nominal') ?? 0;
            
        $lastExpenses = PengeluaranHarian::whereMonth('tanggal_input', $lastMonth->month)
            ->whereYear('tanggal_input', $lastMonth->year)
            ->sum('nominal') ?? 0;
            
        $lastPatients = JumlahPasienHarian::whereMonth('tanggal', $lastMonth->month)
            ->whereYear('tanggal', $lastMonth->year)
            ->sum(DB::raw('COALESCE(jumlah_pasien_umum, 0) + COALESCE(jumlah_pasien_bpjs, 0)')) ?? 0;
        
        // Calculate metrics
        $netProfit = $currentRevenue - $currentExpenses;
        $lastNetProfit = $lastRevenue - $lastExpenses;
        $profitMargin = $currentRevenue > 0 ? ($netProfit / $currentRevenue) * 100 : 0;
        $avgRevenuePerPatient = $currentPatients > 0 ? $currentRevenue / $currentPatients : 0;
        
        return [
            'current' => [
                'revenue' => $currentRevenue,
                'expenses' => $currentExpenses,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin,
                'patients' => $currentPatients,
                'procedures' => $currentProcedures,
                'jaspel_paid' => $currentJaspel,
                'avg_revenue_per_patient' => $avgRevenuePerPatient,
            ],
            'last_month' => [
                'revenue' => $lastRevenue,
                'expenses' => $lastExpenses,
                'net_profit' => $lastNetProfit,
                'patients' => $lastPatients,
            ],
            'changes' => [
                'revenue' => $this->calculatePercentageChange($currentRevenue, $lastRevenue),
                'expenses' => $this->calculatePercentageChange($currentExpenses, $lastExpenses),
                'net_profit' => $this->calculatePercentageChange($netProfit, $lastNetProfit),
                'patients' => $this->calculatePercentageChange($currentPatients, $lastPatients),
            ],
        ];
    }
    
    // Real Strategic Data
    public function getStrategicMetrics(): array
    {
        $activeGoals = StrategicGoal::active()->count();
        $completedGoals = StrategicGoal::where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->count();
        $overdueGoals = StrategicGoal::overdue()->count();
        
        $avgProgress = StrategicGoal::active()->get()->avg('progress_percentage') ?? 0;
        
        $goalsByCategory = StrategicGoal::active()
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get()
            ->mapWithKeys(function ($item) {
                return [StrategicGoal::getCategoryOptions()[$item->category] => $item->count];
            });
        
        return [
            'active_goals' => $activeGoals,
            'completed_this_month' => $completedGoals,
            'overdue_goals' => $overdueGoals,
            'average_progress' => $avgProgress,
            'goals_by_category' => $goalsByCategory->toArray(),
        ];
    }
    
    // Real Staff Performance Data
    public function getStaffPerformance(): array
    {
        $currentMonth = now();
        
        $totalStaff = Pegawai::where('aktif', true)->count();
        $totalDoctors = Dokter::where('aktif', true)->count();
        
        // Real attendance data
        $attendanceData = DB::table('attendances')
            ->whereMonth('date', $currentMonth->month)
            ->whereYear('date', $currentMonth->year)
            ->selectRaw('
                COUNT(*) as total_attendances,
                COUNT(CASE WHEN status = "present" THEN 1 END) as present_count,
                COUNT(CASE WHEN status = "late" THEN 1 END) as late_count,
                COUNT(CASE WHEN status = "absent" THEN 1 END) as absent_count
            ')
            ->first();
        
        $attendanceRate = $attendanceData && $attendanceData->total_attendances > 0 
            ? ($attendanceData->present_count / $attendanceData->total_attendances) * 100 
            : 0;
        
        // Doctor productivity
        $doctorProcedures = Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
            ->whereYear('tanggal_tindakan', $currentMonth->year)
            ->whereNotNull('dokter_id')
            ->count();
            
        $avgProceduresPerDoctor = $totalDoctors > 0 ? $doctorProcedures / $totalDoctors : 0;
        
        return [
            'total_staff' => $totalStaff,
            'total_doctors' => $totalDoctors,
            'attendance_rate' => $attendanceRate,
            'procedures_this_month' => $doctorProcedures,
            'avg_procedures_per_doctor' => $avgProceduresPerDoctor,
            'late_arrivals' => $attendanceData->late_count ?? 0,
            'absent_count' => $attendanceData->absent_count ?? 0,
        ];
    }
    
    // Real Approval Workflow Data
    public function getApprovalMetrics(): array
    {
        $pendingApprovals = ManagerApproval::pending()->count();
        $urgentApprovals = ManagerApproval::urgent()->pending()->count();
        $overdueApprovals = ManagerApproval::overdue()->count();
        $approvedToday = ManagerApproval::whereDate('approved_at', now()->toDateString())->count();
        $totalPendingValue = ManagerApproval::pending()->sum('amount') ?? 0;
        
        // Average approval time
        $avgApprovalTime = ManagerApproval::whereNotNull('approved_at')
            ->whereMonth('created_at', now()->month)
            ->selectRaw('AVG(JULIANDAY(approved_at) - JULIANDAY(created_at)) as avg_days')
            ->value('avg_days') ?? 0;
        
        return [
            'pending_count' => $pendingApprovals,
            'urgent_count' => $urgentApprovals,
            'overdue_count' => $overdueApprovals,
            'approved_today' => $approvedToday,
            'total_pending_value' => $totalPendingValue,
            'avg_approval_time_days' => round($avgApprovalTime, 1),
        ];
    }
    
    // Real Department Performance
    public function getDepartmentScores(): array
    {
        $departments = ['medical', 'administrative', 'financial', 'support'];
        $scores = [];
        
        foreach ($departments as $dept) {
            $scores[$dept] = DepartmentPerformanceMetric::calculateDepartmentScore($dept);
        }
        
        return $scores;
    }
    
    // 6-Month Financial Trends (Real Data)
    public function getFinancialTrends(): array
    {
        $months = [];
        $revenue = [];
        $expenses = [];
        $netProfit = [];
        $profitMargin = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $monthlyRevenue = PendapatanHarian::whereMonth('tanggal_input', $date->month)
                ->whereYear('tanggal_input', $date->year)
                ->sum('nominal') ?? 0;
                
            $monthlyExpenses = PengeluaranHarian::whereMonth('tanggal_input', $date->month)
                ->whereYear('tanggal_input', $date->year)
                ->sum('nominal') ?? 0;
                
            $monthlyNetProfit = $monthlyRevenue - $monthlyExpenses;
            $monthlyProfitMargin = $monthlyRevenue > 0 ? ($monthlyNetProfit / $monthlyRevenue) * 100 : 0;
            
            $revenue[] = (float) $monthlyRevenue;
            $expenses[] = (float) $monthlyExpenses;
            $netProfit[] = (float) $monthlyNetProfit;
            $profitMargin[] = round((float) $monthlyProfitMargin, 2);
        }
        
        return [
            'months' => $months,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
        ];
    }
    
    // Real Top Performers
    public function getTopPerformers(): array
    {
        // Top doctors by procedure count
        $topDoctors = Tindakan::whereMonth('tanggal_tindakan', now()->month)
            ->whereYear('tanggal_tindakan', now()->year)
            ->whereNotNull('dokter_id')
            ->select('dokter_id', DB::raw('COUNT(*) as procedure_count'))
            ->with('dokter')
            ->groupBy('dokter_id')
            ->orderBy('procedure_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->dokter->nama_lengkap ?? 'Unknown',
                    'count' => $item->procedure_count,
                    'type' => 'Doctor',
                ];
            });

        // Top paramedis by activity
        $topParamedis = Tindakan::whereMonth('tanggal_tindakan', now()->month)
            ->whereYear('tanggal_tindakan', now()->year)
            ->whereNotNull('paramedis_id')
            ->select('paramedis_id', DB::raw('COUNT(*) as activity_count'))
            ->with('paramedis')
            ->groupBy('paramedis_id')
            ->orderBy('activity_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->paramedis->nama_lengkap ?? 'Unknown',
                    'count' => $item->activity_count,
                    'type' => 'Paramedis',
                ];
            });

        return [
            'top_doctors' => $topDoctors->toArray(),
            'top_paramedis' => $topParamedis->toArray(),
        ];
    }
    
    // Real Alert Summary
    public function getCriticalAlerts(): array
    {
        $overdueGoals = StrategicGoal::overdue()->count();
        $urgentApprovals = ManagerApproval::urgent()->pending()->count();
        $lowPerformingDepartments = DepartmentPerformanceMetric::kpiOnly()
            ->currentMonth()
            ->where('score', '<', 60)
            ->count();
        
        $pendingValidations = Jaspel::where('status_validasi', 'pending')->count();
        $highValuePending = ManagerApproval::highValue()->pending()->count();
        
        return [
            'overdue_goals' => $overdueGoals,
            'urgent_approvals' => $urgentApprovals,
            'low_performing_depts' => $lowPerformingDepartments,
            'pending_validations' => $pendingValidations,
            'high_value_pending' => $highValuePending,
        ];
    }
    
    // Auto-calculate KPIs from real data
    public function autoCalculateKPIs(): void
    {
        $currentDate = now()->toDateString();
        $userId = auth()->id();
        
        // Financial KPIs
        $revenue = PendapatanHarian::whereMonth('tanggal_input', now()->month)->sum('nominal') ?? 0;
        $expenses = PengeluaranHarian::whereMonth('tanggal_input', now()->month)->sum('nominal') ?? 0;
        $profitMargin = $revenue > 0 ? (($revenue - $expenses) / $revenue) * 100 : 0;
        
        DepartmentPerformanceMetric::updateOrCreate([
            'department' => 'financial',
            'metric_name' => 'Monthly Revenue',
            'measurement_date' => $currentDate,
            'period_type' => 'monthly',
        ], [
            'metric_value' => $revenue,
            'metric_unit' => 'IDR',
            'target_value' => 50000000,
            'is_kpi' => true,
            'score' => min(100, ($revenue / 50000000) * 100),
            'trend' => $this->calculateTrend($revenue, 'financial', 'Monthly Revenue'),
            'recorded_by' => $userId,
        ]);

        DepartmentPerformanceMetric::updateOrCreate([
            'department' => 'financial',
            'metric_name' => 'Profit Margin',
            'measurement_date' => $currentDate,
            'period_type' => 'monthly',
        ], [
            'metric_value' => $profitMargin,
            'metric_unit' => 'percentage',
            'target_value' => 25,
            'is_kpi' => true,
            'score' => min(100, ($profitMargin / 25) * 100),
            'trend' => $this->calculateTrend($profitMargin, 'financial', 'Profit Margin'),
            'recorded_by' => $userId,
        ]);
        
        // Operational KPIs
        $patients = JumlahPasienHarian::whereMonth('tanggal', now()->month)
            ->sum(DB::raw('COALESCE(jumlah_pasien_umum, 0) + COALESCE(jumlah_pasien_bpjs, 0)')) ?? 0;
        $procedures = Tindakan::whereMonth('tanggal_tindakan', now()->month)->count();
        
        DepartmentPerformanceMetric::updateOrCreate([
            'department' => 'medical',
            'metric_name' => 'Patient Count',
            'measurement_date' => $currentDate,
            'period_type' => 'monthly',
        ], [
            'metric_value' => $patients,
            'metric_unit' => 'count',
            'target_value' => 500,
            'is_kpi' => true,
            'score' => min(100, ($patients / 500) * 100),
            'trend' => $this->calculateTrend($patients, 'medical', 'Patient Count'),
            'recorded_by' => $userId,
        ]);
    }
    
    private function calculateTrend(float $currentValue, string $department, string $metricName): string
    {
        $lastMonth = now()->subMonth();
        $previousValue = DepartmentPerformanceMetric::where('department', $department)
            ->where('metric_name', $metricName)
            ->whereMonth('measurement_date', $lastMonth->month)
            ->whereYear('measurement_date', $lastMonth->year)
            ->value('metric_value');
        
        if (!$previousValue || $previousValue == 0) {
            return 'stable';
        }
        
        $changePercentage = (($currentValue - $previousValue) / $previousValue) * 100;
        
        if ($changePercentage > 5) {
            return 'improving';
        } elseif ($changePercentage < -5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
    
    private function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
}