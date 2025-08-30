<?php

namespace App\Filament\Bendahara\Widgets;

use Filament\Widgets\Widget;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use Illuminate\Support\Facades\Cache;

class ModernFinancialMetricsWidget extends Widget
{
    protected static string $view = 'filament.bendahara.widgets.modern-financial-metrics';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getFinancialSummary(): array
    {
        return Cache::remember('bendahara_financial_summary', now()->addMinutes(5), function () {
            $currentMonth = now();
            $lastMonth = now()->subMonth();
            
            // Current month data
            $currentPendapatan = Pendapatan::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $currentPengeluaran = Pengeluaran::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum('nominal');
                
            $currentJaspel = Jaspel::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum('nominal');

            // Last month for comparison
            $lastPendapatan = Pendapatan::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->where('status_validasi', 'disetujui')
                ->sum('nominal');
                
            $lastPengeluaran = Pengeluaran::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->sum('nominal');
                
            $lastJaspel = Jaspel::whereMonth('tanggal', $lastMonth->month)
                ->whereYear('tanggal', $lastMonth->year)
                ->sum('nominal');

            return [
                'current' => [
                    'revenue' => $currentPendapatan,
                    'expenses' => $currentPengeluaran,
                    'jaspel' => $currentJaspel,
                    'net_income' => $currentPendapatan - $currentPengeluaran - $currentJaspel,
                ],
                'previous' => [
                    'revenue' => $lastPendapatan,
                    'expenses' => $lastPengeluaran,
                    'jaspel' => $lastJaspel,
                    'net_income' => $lastPendapatan - $lastPengeluaran - $lastJaspel,
                ],
                'growth' => [
                    'revenue' => $this->calculateGrowth($currentPendapatan, $lastPendapatan),
                    'expenses' => $this->calculateGrowth($currentPengeluaran, $lastPengeluaran),
                    'jaspel' => $this->calculateGrowth($currentJaspel, $lastJaspel),
                    'net_income' => $this->calculateGrowth(
                        $currentPendapatan - $currentPengeluaran - $currentJaspel,
                        $lastPendapatan - $lastPengeluaran - $lastJaspel
                    ),
                ],
            ];
        });
    }
    
    private function calculateGrowth($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    public function formatCurrency($amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    
    public function formatGrowth($growth): string
    {
        if ($growth > 0) {
            return '+' . $growth . '%';
        }
        return $growth . '%';
    }
}