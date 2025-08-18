<?php

namespace App\Filament\Manajer\Pages;

use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class DebugCharts extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    
    protected static string $view = 'filament.manajer.pages.debug-charts';
    
    protected static ?string $title = '🔧 Debug Charts';
    
    protected static ?string $navigationLabel = '🔧 Debug Charts';
    
    protected static ?int $navigationSort = 99;
    
    protected static ?string $navigationGroup = '📊 Dashboard & Analytics';
    
    protected static ?string $slug = 'debug-charts';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true; // Always show in navigation for debugging
    }
    
    public function mount(): void
    {
        // Initialize debug data
    }
    
    public function getFinancialTrends(): array
    {
        $months = [];
        $revenue = [];
        $expenses = [];
        $netProfit = [];
        $profitMargin = [];
        
        // Generate test data if no real data exists
        $hasData = PendapatanHarian::exists();
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            if ($hasData) {
                $monthlyRevenue = PendapatanHarian::whereMonth('tanggal_input', $date->month)
                    ->whereYear('tanggal_input', $date->year)
                    ->sum('nominal') ?? 0;
                    
                $monthlyExpenses = PengeluaranHarian::whereMonth('tanggal_input', $date->month)
                    ->whereYear('tanggal_input', $date->year)
                    ->sum('nominal') ?? 0;
            } else {
                // Generate test data
                $monthlyRevenue = rand(10000000, 50000000);
                $monthlyExpenses = rand(5000000, 30000000);
            }
            
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
            'has_real_data' => $hasData,
        ];
    }
}