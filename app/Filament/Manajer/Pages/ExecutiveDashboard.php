<?php

namespace App\Filament\Manajer\Pages;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
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

class ExecutiveDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    
    protected static string $view = 'filament.manajer.pages.executive-dashboard';
    
    protected static ?string $title = 'Executive Dashboard';
    
    protected static ?string $navigationLabel = 'Executive Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = '📊 Dashboard & Analytics';
    
    protected static ?string $slug = 'dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
    
    public function mount(): void
    {
        // Initialize executive dashboard data
    }
    
    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->size(ActionSize::Small)
                ->action(fn () => redirect()->to(request()->url())),
                
            Action::make('export')
                ->label('Export Executive Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->size(ActionSize::Small)
                ->action(function () {
                    $this->notify('success', 'Executive report export initiated');
                }),
        ];
    }
    
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    
    public function getExecutiveKPIs(): array
    {
        $currentMonth = now();
        $lastMonth = now()->subMonth();
        
        // Current month financial data
        $currentRevenue = PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');
            
        $currentExpenses = PengeluaranHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');
            
        $currentPatients = JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
        $currentProcedures = Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
            ->whereYear('tanggal_tindakan', $currentMonth->year)
            ->count();
        
        // Last month data for comparison
        $lastRevenue = PendapatanHarian::whereMonth('tanggal_input', $lastMonth->month)
            ->whereYear('tanggal_input', $lastMonth->year)
            ->sum('nominal');
            
        $lastExpenses = PengeluaranHarian::whereMonth('tanggal_input', $lastMonth->month)
            ->whereYear('tanggal_input', $lastMonth->year)
            ->sum('nominal');
            
        $lastPatients = JumlahPasienHarian::whereMonth('tanggal', $lastMonth->month)
            ->whereYear('tanggal', $lastMonth->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
            
        $lastProcedures = Tindakan::whereMonth('tanggal_tindakan', $lastMonth->month)
            ->whereYear('tanggal_tindakan', $lastMonth->year)
            ->count();
        
        // Calculate metrics
        $netProfit = $currentRevenue - $currentExpenses;
        $lastNetProfit = $lastRevenue - $lastExpenses;
        $profitMargin = $currentRevenue > 0 ? ($netProfit / $currentRevenue) * 100 : 0;
        
        return [
            'current' => [
                'revenue' => $currentRevenue,
                'expenses' => $currentExpenses,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin,
                'patients' => $currentPatients,
                'procedures' => $currentProcedures,
                'avg_revenue_per_patient' => $currentPatients > 0 ? $currentRevenue / $currentPatients : 0,
            ],
            'last_month' => [
                'revenue' => $lastRevenue,
                'expenses' => $lastExpenses,
                'net_profit' => $lastNetProfit,
                'patients' => $lastPatients,
                'procedures' => $lastProcedures,
            ],
            'changes' => [
                'revenue' => $this->calculatePercentageChange($currentRevenue, $lastRevenue),
                'expenses' => $this->calculatePercentageChange($currentExpenses, $lastExpenses),
                'net_profit' => $this->calculatePercentageChange($netProfit, $lastNetProfit),
                'patients' => $this->calculatePercentageChange($currentPatients, $lastPatients),
                'procedures' => $this->calculatePercentageChange($currentProcedures, $lastProcedures),
            ],
        ];
    }
    
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
        
        // Ensure arrays are not empty and have valid data
        if (empty($months)) {
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            $revenue = [0, 0, 0, 0, 0, 0];
            $expenses = [0, 0, 0, 0, 0, 0];
            $netProfit = [0, 0, 0, 0, 0, 0];
            $profitMargin = [0, 0, 0, 0, 0, 0];
        }
        
        return [
            'months' => $months,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
        ];
    }
    
    public function getOperationalMetrics(): array
    {
        $currentMonth = now();
        
        // Staff efficiency metrics
        $totalStaff = Pegawai::count();
        $totalDoctors = Dokter::count();
        $activeProcedures = Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
            ->whereYear('tanggal_tindakan', $currentMonth->year)
            ->count();
            
        $avgProceduresPerDoctor = $totalDoctors > 0 ? $activeProcedures / $totalDoctors : 0;
        
        // Patient satisfaction metrics (placeholder - would need patient feedback system)
        $patientSatisfactionScore = 4.2; // Mock data - replace with actual patient feedback
        
        return [
            'total_staff' => $totalStaff,
            'total_doctors' => $totalDoctors,
            'procedures_this_month' => $activeProcedures,
            'avg_procedures_per_doctor' => round($avgProceduresPerDoctor, 1),
            'patient_satisfaction' => $patientSatisfactionScore,
            'staff_utilization' => min(100, ($activeProcedures / max(1, $totalStaff * 20)) * 100), // Mock calculation
        ];
    }
    
    public function getPerformanceIndicators(): array
    {
        $currentMonth = now();
        
        // Revenue targets (configurable)
        $monthlyRevenueTarget = 50000000; // 50M IDR
        $monthlyPatientTarget = 500;
        
        $currentRevenue = PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');
            
        $currentPatients = JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
        
        return [
            'revenue_target' => $monthlyRevenueTarget,
            'revenue_achieved' => $currentRevenue,
            'revenue_progress' => min(100, ($currentRevenue / $monthlyRevenueTarget) * 100),
            'patient_target' => $monthlyPatientTarget,
            'patient_achieved' => $currentPatients,
            'patient_progress' => min(100, ($currentPatients / $monthlyPatientTarget) * 100),
        ];
    }
    
    public function getStrategicInsights(): array
    {
        // Top performing procedures
        $topProcedures = Tindakan::select('jenis_tindakan_id', DB::raw('COUNT(*) as total_count'))
            ->with('jenisTindakan')
            ->whereMonth('tanggal_tindakan', now()->month)
            ->whereYear('tanggal_tindakan', now()->year)
            ->groupBy('jenis_tindakan_id')
            ->orderBy('total_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->jenisTindakan->nama ?? 'Unknown',
                    'count' => $item->total_count,
                ];
            })
            ->toArray();
            
        // Revenue by service type (mock data - would need proper service categorization)
        $revenueByService = [
            ['service' => 'General Medicine', 'revenue' => 25000000, 'percentage' => 40],
            ['service' => 'Dental Care', 'revenue' => 18750000, 'percentage' => 30],
            ['service' => 'Specialist Care', 'revenue' => 12500000, 'percentage' => 20],
            ['service' => 'Laboratory', 'revenue' => 6250000, 'percentage' => 10],
        ];
        
        return [
            'top_procedures' => $topProcedures,
            'revenue_by_service' => $revenueByService,
        ];
    }
    
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 2);
    }
}