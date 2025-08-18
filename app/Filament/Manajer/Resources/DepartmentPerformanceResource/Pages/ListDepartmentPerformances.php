<?php

namespace App\Filament\Manajer\Resources\DepartmentPerformanceResource\Pages;

use App\Filament\Manajer\Resources\DepartmentPerformanceResource;
use App\Models\DepartmentPerformanceMetric;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListDepartmentPerformances extends ListRecords
{
    protected static string $resource = DepartmentPerformanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('auto_calculate')
                ->label('🔄 Auto Calculate')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->action(function () {
                    // Auto-calculate metrics from existing data
                    $this->calculateFinancialMetrics();
                    $this->calculateOperationalMetrics();
                    $this->calculateStaffMetrics();
                    
                    $this->notify('success', 'Performance metrics auto-calculated from real data!');
                }),
                
            Actions\CreateAction::make()
                ->label('📊 Add Metric')
                ->icon('heroicon-o-plus-circle')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['recorded_by'] = Auth::id();
                    return $data;
                }),
        ];
    }

    public function getTitle(): string
    {
        return '📊 Department Performance Dashboard';
    }

    public function getSubheading(): ?string
    {
        $kpiCount = DepartmentPerformanceMetric::kpiOnly()->currentMonth()->count();
        $avgScore = DepartmentPerformanceMetric::currentMonth()->avg('score');
        
        return "Active KPIs: {$kpiCount} | Average Score: " . number_format($avgScore, 1);
    }

    private function calculateFinancialMetrics(): void
    {
        // Calculate real financial metrics from existing data
        $currentMonth = now();
        
        // Revenue metrics
        $totalRevenue = \App\Models\PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');

        $totalExpenses = \App\Models\PengeluaranHarian::whereMonth('tanggal_input', $currentMonth->month)
            ->whereYear('tanggal_input', $currentMonth->year)
            ->sum('nominal');

        $profitMargin = $totalRevenue > 0 ? (($totalRevenue - $totalExpenses) / $totalRevenue) * 100 : 0;

        // Create/Update financial metrics
        DepartmentPerformanceMetric::updateOrCreate([
            'department' => 'financial',
            'metric_name' => 'Monthly Revenue',
            'measurement_date' => $currentMonth->toDateString(),
            'period_type' => 'monthly',
        ], [
            'metric_value' => $totalRevenue,
            'metric_unit' => 'IDR',
            'target_value' => 50000000, // 50M target
            'is_kpi' => true,
            'score' => min(100, ($totalRevenue / 50000000) * 100),
            'recorded_by' => Auth::id(),
        ]);

        DepartmentPerformanceMetric::updateOrCreate([
            'department' => 'financial',
            'metric_name' => 'Profit Margin',
            'measurement_date' => $currentMonth->toDateString(),
            'period_type' => 'monthly',
        ], [
            'metric_value' => $profitMargin,
            'metric_unit' => 'percentage',
            'target_value' => 25, // 25% target
            'is_kpi' => true,
            'score' => min(100, ($profitMargin / 25) * 100),
            'recorded_by' => Auth::id(),
        ]);
    }

    private function calculateOperationalMetrics(): void
    {
        $currentMonth = now();
        
        // Patient count
        $totalPatients = \App\Models\JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
            ->whereYear('tanggal', $currentMonth->year)
            ->sum(\Illuminate\Support\Facades\DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));

        // Procedure count
        $totalProcedures = \App\Models\Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
            ->whereYear('tanggal_tindakan', $currentMonth->year)
            ->count();

        DepartmentPerformanceMetric::updateOrCreate([
            'department' => 'medical',
            'metric_name' => 'Monthly Patient Count',
            'measurement_date' => $currentMonth->toDateString(),
            'period_type' => 'monthly',
        ], [
            'metric_value' => $totalPatients,
            'metric_unit' => 'count',
            'target_value' => 500, // 500 patients target
            'is_kpi' => true,
            'score' => min(100, ($totalPatients / 500) * 100),
            'recorded_by' => Auth::id(),
        ]);

        DepartmentPerformanceMetric::updateOrCreate([
            'department' => 'medical',
            'metric_name' => 'Monthly Procedures',
            'measurement_date' => $currentMonth->toDateString(),
            'period_type' => 'monthly',
        ], [
            'metric_value' => $totalProcedures,
            'metric_unit' => 'count',
            'target_value' => 1000, // 1000 procedures target
            'is_kpi' => true,
            'score' => min(100, ($totalProcedures / 1000) * 100),
            'recorded_by' => Auth::id(),
        ]);
    }

    private function calculateStaffMetrics(): void
    {
        $currentMonth = now();
        
        // Staff efficiency
        $totalStaff = \App\Models\Pegawai::where('aktif', true)->count();
        $totalDoctors = \App\Models\Dokter::where('aktif', true)->count();
        $totalProcedures = \App\Models\Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
            ->whereYear('tanggal_tindakan', $currentMonth->year)
            ->count();

        $proceduresPerStaff = $totalStaff > 0 ? $totalProcedures / $totalStaff : 0;
        $proceduresPerDoctor = $totalDoctors > 0 ? $totalProcedures / $totalDoctors : 0;

        DepartmentPerformanceMetric::updateOrCreate([
            'department' => 'administrative',
            'metric_name' => 'Staff Efficiency',
            'measurement_date' => $currentMonth->toDateString(),
            'period_type' => 'monthly',
        ], [
            'metric_value' => $proceduresPerStaff,
            'metric_unit' => 'ratio',
            'target_value' => 20, // 20 procedures per staff target
            'is_kpi' => true,
            'score' => min(100, ($proceduresPerStaff / 20) * 100),
            'recorded_by' => Auth::id(),
        ]);

        DepartmentPerformanceMetric::updateOrCreate([
            'department' => 'medical',
            'metric_name' => 'Doctor Productivity',
            'measurement_date' => $currentMonth->toDateString(),
            'period_type' => 'monthly',
        ], [
            'metric_value' => $proceduresPerDoctor,
            'metric_unit' => 'ratio',
            'target_value' => 50, // 50 procedures per doctor target
            'is_kpi' => true,
            'score' => min(100, ($proceduresPerDoctor / 50) * 100),
            'recorded_by' => Auth::id(),
        ]);
    }
}