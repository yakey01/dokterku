<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Jaspel;
use Illuminate\Support\Facades\Cache;

class BendaharaDashboardComponent extends Component
{
    public function getFinancialSummary(): array
    {
        return Cache::remember('bendahara_financial_summary', now()->addMinutes(5), function () {
            $currentMonth = now();
            $lastMonth = now()->subMonth();
            
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

    public function getValidationMetrics(): array
    {
        return Cache::remember('bendahara_validation_metrics', now()->addMinutes(3), function () {
            $pending = [
                'pendapatan' => Pendapatan::where('status_validasi', 'pending')->count(),
                'pengeluaran' => Pengeluaran::where('status_validasi', 'pending')->count(),
                'jaspel' => Jaspel::where('status_validasi', 'pending')->count(),
            ];
            
            $approved = [
                'pendapatan' => Pendapatan::where('status_validasi', 'disetujui')->count(),
                'pengeluaran' => Pengeluaran::where('status_validasi', 'disetujui')->count(),
                'jaspel' => Jaspel::where('status_validasi', 'disetujui')->count(),
            ];

            return [
                'pending' => $pending,
                'approved' => $approved,
                'total_pending' => array_sum($pending),
                'total_approved' => array_sum($approved),
            ];
        });
    }

    public function getMonthlyTrends(): array
    {
        return Cache::remember('bendahara_monthly_trends', now()->addMinutes(10), function () {
            $trends = [];
            $labels = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $labels[] = $date->format('M Y');
                
                $monthlyPendapatan = Pendapatan::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal');
                    
                $monthlyPengeluaran = Pengeluaran::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum('nominal');
                    
                $monthlyJaspel = Jaspel::whereMonth('tanggal', $date->month)
                    ->whereYear('tanggal', $date->year)
                    ->sum('nominal');
                
                $trends['revenue'][] = $monthlyPendapatan;
                $trends['expenses'][] = $monthlyPengeluaran;
                $trends['jaspel'][] = $monthlyJaspel;
                $trends['net_income'][] = $monthlyPendapatan - $monthlyPengeluaran - $monthlyJaspel;
            }
            
            return [
                'labels' => $labels,
                'data' => $trends,
            ];
        });
    }

    public function getRecentActivities(): array
    {
        return Cache::remember('bendahara_recent_activities', now()->addMinutes(2), function () {
            $activities = collect();
            
            $recentPendapatan = Pendapatan::with(['inputBy'])
                ->latest('updated_at')
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'revenue',
                        'title' => $item->nama_pendapatan,
                        'amount' => $item->nominal,
                        'status' => $item->status_validasi,
                        'date' => $item->updated_at,
                        'user' => $item->inputBy->name ?? 'System',
                    ];
                });
            
            $recentPengeluaran = Pengeluaran::with(['inputBy'])
                ->latest('updated_at')
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'expense',
                        'title' => $item->nama_pengeluaran,
                        'amount' => $item->nominal,
                        'status' => $item->status_validasi ?? 'approved',
                        'date' => $item->updated_at,
                        'user' => $item->inputBy->name ?? 'System',
                    ];
                });
            
            return $activities->merge($recentPendapatan)
                ->merge($recentPengeluaran)
                ->sortByDesc('date')
                ->take(6)
                ->values()
                ->toArray();
        });
    }

    private function calculateGrowth($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function render()
    {
        $financial = $this->getFinancialSummary();
        $validation = $this->getValidationMetrics();
        $trends = $this->getMonthlyTrends();
        $activities = $this->getRecentActivities();

        return view('livewire.bendahara-dashboard-component', compact(
            'financial', 'validation', 'trends', 'activities'
        ));
    }
}
