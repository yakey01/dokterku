<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Income;
use App\Models\Expense;
use Carbon\Carbon;

class MLInsightsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'ML Insights';
    protected static ?string $title = 'Machine Learning Insights';
    protected static ?string $slug = 'ml-insights';
    protected static ?int $navigationSort = 11;
    protected static string $view = 'filament.petugas.pages.ml-insights';

    public ?array $data = [];
    public $selectedPeriod = 'month';
    public $insights = [];

    public function mount(): void
    {
        $this->loadInsights();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Analisis Periode')
                    ->description('Pilih periode untuk analisis ML')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('selectedPeriod')
                                    ->label('Periode Analisis')
                                    ->options([
                                        'week' => 'Mingguan',
                                        'month' => 'Bulanan',
                                        'quarter' => 'Triwulan',
                                        'year' => 'Tahunan',
                                    ])
                                    ->default('month')
                                    ->reactive()
                                    ->afterStateUpdated(fn () => $this->loadInsights()),
                                DatePicker::make('start_date')
                                    ->label('Tanggal Mulai')
                                    ->default(now()->startOfMonth())
                                    ->reactive()
                                    ->afterStateUpdated(fn () => $this->loadInsights()),
                                DatePicker::make('end_date')
                                    ->label('Tanggal Akhir')
                                    ->default(now()->endOfMonth())
                                    ->reactive()
                                    ->afterStateUpdated(fn () => $this->loadInsights()),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public function loadInsights(): void
    {
        $this->insights = [
            'patient_trends' => $this->getPatientTrends(),
            'revenue_analysis' => $this->getRevenueAnalysis(),
            'expense_patterns' => $this->getExpensePatterns(),
            'medical_action_stats' => $this->getMedicalActionStats(),
            'predictions' => $this->getPredictions(),
        ];
    }

    private function getPatientTrends(): array
    {
        $period = $this->selectedPeriod;
        $startDate = now()->startOf($period);
        $endDate = now()->endOf($period);
        
        $patients = Pasien::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        return [
            'total' => $patients->sum('count'),
            'average_daily' => $patients->avg('count'),
            'trend' => $patients->pluck('count')->toArray(),
            'dates' => $patients->pluck('date')->toArray(),
        ];
    }

    private function getRevenueAnalysis(): array
    {
        $period = $this->selectedPeriod;
        $startDate = now()->startOf($period);
        $endDate = now()->endOf($period);
        
        $income = Income::whereBetween('tanggal', [$startDate, $endDate])
            ->where('status', 'diterima')
            ->sum('jumlah');
            
        $expense = Expense::whereBetween('tanggal', [$startDate, $endDate])
            ->where('status', 'disetujui')
            ->sum('jumlah');
            
        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'net_profit' => $income - $expense,
            'profit_margin' => $income > 0 ? (($income - $expense) / $income) * 100 : 0,
        ];
    }

    private function getExpensePatterns(): array
    {
        $period = $this->selectedPeriod;
        $startDate = now()->startOf($period);
        $endDate = now()->endOf($period);
        
        $expenses = Expense::whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('kategori, SUM(jumlah) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get();
            
        return [
            'by_category' => $expenses->pluck('total', 'kategori')->toArray(),
            'total_expense' => $expenses->sum('total'),
            'top_category' => $expenses->first()?->kategori ?? 'N/A',
        ];
    }

    private function getMedicalActionStats(): array
    {
        $period = $this->selectedPeriod;
        $startDate = now()->startOf($period);
        $endDate = now()->endOf($period);
        
        $actions = Tindakan::whereBetween('tanggal_tindakan', [$startDate, $endDate]);
        
        return [
            'total_actions' => $actions->count(),
            'completed' => $actions->where('status', 'selesai')->count(),
            'scheduled' => $actions->where('status', 'jadwal')->count(),
            'cancelled' => $actions->where('status', 'batal')->count(),
            'completion_rate' => $actions->count() > 0 ? 
                ($actions->where('status', 'selesai')->count() / $actions->count()) * 100 : 0,
        ];
    }

    private function getPredictions(): array
    {
        // Simple prediction based on historical data
        $lastMonthPatients = Pasien::whereMonth('created_at', now()->subMonth()->month)->count();
        $currentMonthPatients = Pasien::whereMonth('created_at', now()->month)->count();
        
        $growthRate = $lastMonthPatients > 0 ? (($currentMonthPatients - $lastMonthPatients) / $lastMonthPatients) * 100 : 0;
        
        return [
            'next_month_patients' => max(0, round($currentMonthPatients * (1 + ($growthRate / 100)))),
            'growth_rate' => $growthRate,
            'trend' => $growthRate > 0 ? 'Meningkat' : ($growthRate < 0 ? 'Menurun' : 'Stabil'),
            'confidence' => abs($growthRate) < 10 ? 'Tinggi' : (abs($growthRate) < 25 ? 'Sedang' : 'Rendah'),
        ];
    }

    public function getViewData(): array
    {
        return [
            'insights' => $this->insights,
            'selectedPeriod' => $this->selectedPeriod,
        ];
    }
}
