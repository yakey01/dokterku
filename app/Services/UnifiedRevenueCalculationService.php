<?php

namespace App\Services;

use App\Models\PendapatanHarian;
use App\Models\Pendapatan;
use App\Models\Jaspel;
use App\Models\Tindakan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UnifiedRevenueCalculationService
{
    /**
     * Calculate total revenue for a specific date
     * Handles status inconsistencies and prevents double counting
     */
    public function calculateTotalRevenue(string $date): float
    {
        $carbonDate = Carbon::parse($date);
        
        Log::info('UnifiedRevenueCalculation: Starting calculation', [
            'date' => $date,
            'carbon_date' => $carbonDate->toDateString()
        ]);
        
        // Primary source: PendapatanHarian (daily revenue entries)
        $pendapatanHarianTotal = PendapatanHarian::whereDate('tanggal_input', $carbonDate)
            ->whereIn('status_validasi', ['approved', 'disetujui']) // Handle both status formats
            ->sum('nominal');
            
        // Secondary source: Pendapatan (if PendapatanHarian is empty)
        $pendapatanTotal = Pendapatan::whereDate('tanggal', $carbonDate)
            ->whereIn('status_validasi', ['approved', 'disetujui'])
            ->sum('nominal');
            
        // Tertiary source: Validated Jaspel (simple aggregation without complex deduplication)
        $jaspelTotal = Jaspel::whereDate('tanggal', $carbonDate)
            ->whereIn('status_validasi', ['approved', 'disetujui'])
            ->sum('nominal');
            
        // Use primary source if available, otherwise fallback chain
        $finalRevenue = 0;
        $source = '';
        
        if ($pendapatanHarianTotal > 0) {
            $finalRevenue = $pendapatanHarianTotal;
            $source = 'PendapatanHarian';
        } elseif ($pendapatanTotal > 0) {
            $finalRevenue = $pendapatanTotal;
            $source = 'Pendapatan';
        } else {
            $finalRevenue = $jaspelTotal;
            $source = 'Jaspel';
        }
        
        Log::info('UnifiedRevenueCalculation: Completed', [
            'date' => $date,
            'final_revenue' => $finalRevenue,
            'source' => $source,
            'pendapatan_harian' => $pendapatanHarianTotal,
            'pendapatan' => $pendapatanTotal,
            'jaspel' => $jaspelTotal
        ]);
        
        return $finalRevenue;
    }
    
    /**
     * Calculate monthly revenue with proper aggregation
     */
    public function calculateMonthlyRevenue(int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        // Primary: PendapatanHarian
        $pendapatanHarianMonthly = PendapatanHarian::whereBetween('tanggal_input', [$startDate, $endDate])
            ->whereIn('status_validasi', ['approved', 'disetujui'])
            ->sum('nominal');
            
        // Secondary: Pendapatan
        $pendapatanMonthly = Pendapatan::whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('status_validasi', ['approved', 'disetujui'])
            ->sum('nominal');
            
        // Tertiary: Jaspel (simple aggregation)
        $jaspelMonthly = Jaspel::whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('status_validasi', ['approved', 'disetujui'])
            ->sum('nominal');
        
        $finalRevenue = max($pendapatanHarianMonthly, $pendapatanMonthly) + $jaspelMonthly;
        
        return [
            'total_revenue' => $finalRevenue,
            'sources' => [
                'pendapatan_harian' => $pendapatanHarianMonthly,
                'pendapatan' => $pendapatanMonthly,
                'jaspel' => $jaspelMonthly
            ],
            'period' => [
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]
        ];
    }
    
    /**
     * Standardize validation status values
     */
    public function getApprovedStatuses(): array
    {
        return ['approved', 'disetujui'];
    }
    
    /**
     * Calculate accurate manager dashboard revenue
     */
    public function getManagerDashboardRevenue(?string $date = null): array
    {
        $date = $date ?: now()->toDateString();
        $carbonDate = Carbon::parse($date);
        
        // Today's revenue
        $todayRevenue = $this->calculateTotalRevenue($date);
        
        // Monthly revenue
        $monthlyData = $this->calculateMonthlyRevenue($carbonDate->month, $carbonDate->year);
        
        // Previous month for comparison
        $lastMonth = $carbonDate->copy()->subMonth();
        $lastMonthData = $this->calculateMonthlyRevenue($lastMonth->month, $lastMonth->year);
        
        // Calculate percentage change
        $revenueChange = 0;
        if ($lastMonthData['total_revenue'] > 0) {
            $revenueChange = (($monthlyData['total_revenue'] - $lastMonthData['total_revenue']) / $lastMonthData['total_revenue']) * 100;
        }
        
        return [
            'today' => [
                'revenue' => $todayRevenue,
                'date' => $date
            ],
            'monthly' => [
                'revenue' => $monthlyData['total_revenue'],
                'change_percentage' => round($revenueChange, 2),
                'sources' => $monthlyData['sources'],
                'period' => $monthlyData['period']
            ],
            'comparison' => [
                'current_month' => $monthlyData['total_revenue'],
                'last_month' => $lastMonthData['total_revenue'],
                'change' => $monthlyData['total_revenue'] - $lastMonthData['total_revenue']
            ]
        ];
    }
}