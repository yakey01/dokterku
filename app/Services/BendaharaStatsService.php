<?php

namespace App\Services;

use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\Jaspel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Exception;

class BendaharaStatsService
{
    protected int $cacheMinutes = 15; // Cache for 15 minutes
    protected int $dailyStatsCacheMinutes = 360; // Cache daily stats for 6 hours
    
    /**
     * Get comprehensive stats for bendahara dashboard
     */
    public function getDashboardStats(int $userId = null): array
    {
        try {
            $userId = $userId ?? Auth::id();
            $cacheKey = "bendahara_stats_{$userId}";
            
            return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($userId) {
                $today = Carbon::today();
                $yesterday = Carbon::yesterday();
                $thisMonth = Carbon::now()->startOfMonth();
                $lastMonth = Carbon::now()->subMonth()->startOfMonth();
                
                return [
                    'daily' => $this->getDailyStats($userId, $today, $yesterday),
                    'monthly' => $this->getMonthlyStats($userId, $thisMonth, $lastMonth),
                    'trends' => $this->getTrendAnalysis($userId),
                    'validation_summary' => $this->getValidationSummary($userId),
                    'financial_metrics' => $this->getFinancialMetrics($userId),
                    'cash_flow' => $this->getCashFlowAnalysis($userId),
                    'budget_tracking' => $this->getBudgetTracking($userId),
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get bendahara dashboard stats', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getEmptyStats();
        }
    }
    
    /**
     * Get daily financial statistics
     */
    protected function getDailyStats(int $userId, Carbon $today, Carbon $yesterday): array
    {
        try {
            $todayStats = $this->getFinancialStatsForDate($today);
            $yesterdayStats = $this->getFinancialStatsForDate($yesterday);
            
            return [
                'today' => $todayStats,
                'yesterday' => $yesterdayStats,
                'trends' => $this->calculateTrends($todayStats, $yesterdayStats),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get daily financial stats', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'today' => $this->getEmptyDayStats(),
                'yesterday' => $this->getEmptyDayStats(),
                'trends' => $this->getEmptyTrends(),
            ];
        }
    }
    
    /**
     * Get financial statistics for specific date with optimized caching
     */
    protected function getFinancialStatsForDate(Carbon $date): array
    {
        try {
            $cacheKey = "bendahara_daily_stats_{$date->format('Y-m-d')}";
            
            return Cache::remember($cacheKey, now()->addMinutes($this->dailyStatsCacheMinutes), function () use ($date) {
                // Use database-agnostic Eloquent queries instead of raw SQL
                $dateString = $date->format('Y-m-d');
                
                // Income stats
                $pendapatanStats = PendapatanHarian::where('tanggal_input', $dateString)
                    ->selectRaw('
                        SUM(nominal) as pendapatan_sum,
                        SUM(CASE WHEN status_validasi = "disetujui" THEN nominal ELSE 0 END) as pendapatan_approved,
                        SUM(CASE WHEN status_validasi = "pending" THEN nominal ELSE 0 END) as pendapatan_pending
                    ')
                    ->first();
                
                // Expense stats
                $pengeluaranStats = PengeluaranHarian::where('tanggal_input', $dateString)
                    ->selectRaw('
                        SUM(nominal) as pengeluaran_sum,
                        SUM(CASE WHEN status_validasi = "disetujui" THEN nominal ELSE 0 END) as pengeluaran_approved,
                        SUM(CASE WHEN status_validasi = "pending" THEN nominal ELSE 0 END) as pengeluaran_pending
                    ')
                    ->first();
                
                // Treatment stats  
                $tindakanStats = Tindakan::whereDate('tanggal_tindakan', $date)
                    ->selectRaw('
                        COUNT(*) as tindakan_count,
                        COUNT(CASE WHEN status = "pending" THEN 1 ELSE NULL END) as tindakan_pending
                    ')
                    ->first();
                
                // JASPEL stats - WORKFLOW COMPLIANCE: Only count petugas-input data
                $jaspelPending = Jaspel::inputByPetugasOnly()
                    ->whereDate('created_at', $date)
                    ->where('status_pembayaran', '!=', 'paid')
                    ->sum('total_jaspel');
                
                // Combine results
                $result = (object)[
                    'pendapatan_sum' => $pendapatanStats->pendapatan_sum ?? 0,
                    'pendapatan_approved' => $pendapatanStats->pendapatan_approved ?? 0,
                    'pendapatan_pending' => $pendapatanStats->pendapatan_pending ?? 0,
                    'pengeluaran_sum' => $pengeluaranStats->pengeluaran_sum ?? 0,
                    'pengeluaran_approved' => $pengeluaranStats->pengeluaran_approved ?? 0,
                    'pengeluaran_pending' => $pengeluaranStats->pengeluaran_pending ?? 0,
                    'tindakan_count' => $tindakanStats->tindakan_count ?? 0,
                    'tindakan_pending' => $tindakanStats->tindakan_pending ?? 0,
                    'jaspel_pending' => $jaspelPending ?? 0,
                    'net_income' => ($pendapatanStats->pendapatan_approved ?? 0) - ($pengeluaranStats->pengeluaran_approved ?? 0)
                ];
                
                $results = [$result];
                
                $result = $results[0] ?? null;
                
                if (!$result) {
                    return $this->getEmptyDayStats();
                }
                
                return [
                    'pendapatan_sum' => (float)$result->pendapatan_sum,
                    'pendapatan_approved' => (float)$result->pendapatan_approved,
                    'pendapatan_pending' => (float)$result->pendapatan_pending,
                    'pengeluaran_sum' => (float)$result->pengeluaran_sum,
                    'pengeluaran_approved' => (float)$result->pengeluaran_approved,
                    'pengeluaran_pending' => (float)$result->pengeluaran_pending,
                    'tindakan_count' => (int)$result->tindakan_count,
                    'tindakan_pending' => (int)$result->tindakan_pending,
                    'jaspel_pending' => (float)$result->jaspel_pending,
                    'net_income' => (float)$result->net_income,
                    'date' => $date->format('Y-m-d'),
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get financial stats for date', [
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyDayStats();
        }
    }
    
    /**
     * Calculate trends between two periods
     */
    protected function calculateTrends(array $current, array $previous): array
    {
        try {
            $trends = [];
            
            $metrics = [
                'pendapatan_sum', 'pendapatan_approved', 'pengeluaran_sum', 
                'pengeluaran_approved', 'net_income', 'tindakan_count'
            ];
            
            foreach ($metrics as $metric) {
                $currentValue = $current[$metric] ?? 0;
                $previousValue = $previous[$metric] ?? 0;
                
                if ($previousValue > 0) {
                    $percentage = (($currentValue - $previousValue) / $previousValue) * 100;
                } else {
                    $percentage = $currentValue > 0 ? 100 : 0;
                }
                
                $trends[$metric] = [
                    'current' => $currentValue,
                    'previous' => $previousValue,
                    'percentage' => round($percentage, 2),
                    'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'stable'),
                    'change' => $currentValue - $previousValue,
                ];
            }
            
            return $trends;
            
        } catch (Exception $e) {
            Log::error('Failed to calculate financial trends', [
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyTrends();
        }
    }
    
    /**
     * Get monthly statistics
     */
    protected function getMonthlyStats(int $userId, Carbon $thisMonth, Carbon $lastMonth): array
    {
        try {
            $thisMonthStats = $this->getFinancialStatsForMonth($thisMonth);
            $lastMonthStats = $this->getFinancialStatsForMonth($lastMonth);
            
            return [
                'this_month' => $thisMonthStats,
                'last_month' => $lastMonthStats,
                'trends' => $this->calculateTrends($thisMonthStats, $lastMonthStats),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get monthly financial stats', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'this_month' => $this->getEmptyDayStats(),
                'last_month' => $this->getEmptyDayStats(),
                'trends' => $this->getEmptyTrends(),
            ];
        }
    }
    
    /**
     * Get financial statistics for specific month
     */
    protected function getFinancialStatsForMonth(Carbon $month): array
    {
        try {
            $endOfMonth = $month->copy()->endOfMonth();
            
            // Income stats
            $pendapatanStats = PendapatanHarian::whereBetween('tanggal_input', 
                [$month->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->selectRaw('
                    SUM(nominal) as pendapatan_sum,
                    SUM(CASE WHEN status_validasi = "disetujui" THEN nominal ELSE 0 END) as pendapatan_approved,
                    SUM(CASE WHEN status_validasi = "pending" THEN nominal ELSE 0 END) as pendapatan_pending
                ')
                ->first();
            
            // Expense stats
            $pengeluaranStats = PengeluaranHarian::whereBetween('tanggal_input', 
                [$month->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->selectRaw('
                    SUM(nominal) as pengeluaran_sum,
                    SUM(CASE WHEN status_validasi = "disetujui" THEN nominal ELSE 0 END) as pengeluaran_approved,
                    SUM(CASE WHEN status_validasi = "pending" THEN nominal ELSE 0 END) as pengeluaran_pending
                ')
                ->first();
            
            // Treatment stats
            $tindakanStats = Tindakan::whereBetween('tanggal_tindakan', [$month, $endOfMonth])
                ->selectRaw('
                    COUNT(*) as tindakan_count,
                    COUNT(CASE WHEN status = "pending" THEN 1 ELSE NULL END) as tindakan_pending
                ')
                ->first();
            
            // JASPEL stats - WORKFLOW COMPLIANCE: Only count petugas-input data  
            $jaspelPending = Jaspel::inputByPetugasOnly()
                ->whereBetween('created_at', [$month, $endOfMonth])
                ->where('status_pembayaran', '!=', 'paid')
                ->sum('total_jaspel');
            
            return [
                'pendapatan_sum' => $pendapatanStats->pendapatan_sum ?? 0,
                'pendapatan_approved' => $pendapatanStats->pendapatan_approved ?? 0,
                'pendapatan_pending' => $pendapatanStats->pendapatan_pending ?? 0,
                'pengeluaran_sum' => $pengeluaranStats->pengeluaran_sum ?? 0,
                'pengeluaran_approved' => $pengeluaranStats->pengeluaran_approved ?? 0,
                'pengeluaran_pending' => $pengeluaranStats->pengeluaran_pending ?? 0,
                'tindakan_count' => $tindakanStats->tindakan_count ?? 0,
                'tindakan_pending' => $tindakanStats->tindakan_pending ?? 0,
                'jaspel_pending' => $jaspelPending,
                'net_income' => ($pendapatanStats->pendapatan_approved ?? 0) - ($pengeluaranStats->pengeluaran_approved ?? 0),
                'month' => $month->format('Y-m'),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get financial stats for month', [
                'month' => $month->format('Y-m'),
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyDayStats();
        }
    }
    
    /**
     * Get trend analysis for charts with optimized bulk queries
     */
    protected function getTrendAnalysis(int $userId): array
    {
        try {
            $cacheKey = "bendahara_trend_analysis_{$userId}";
            
            return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($userId) {
                $last7Days = $this->getBulkFinancialStatsForDateRange(7);
                $last30Days = $this->getBulkFinancialStatsForDateRange(30);
                
                return [
                    'last_7_days' => $last7Days->toArray(),
                    'last_30_days' => $last30Days->toArray(),
                    'charts' => [
                        'daily_income' => $last7Days->pluck('net_income')->toArray(),
                        'daily_pendapatan' => $last7Days->pluck('pendapatan_approved')->toArray(),
                        'daily_pengeluaran' => $last7Days->pluck('pengeluaran_approved')->toArray(),
                        'monthly_trend' => $last30Days->pluck('net_income')->toArray(),
                        'validation_queue' => $last7Days->pluck('tindakan_pending')->toArray(),
                    ],
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get financial trend analysis', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'last_7_days' => [],
                'last_30_days' => [],
                'charts' => [
                    'daily_income' => [0],
                    'daily_pendapatan' => [0],
                    'daily_pengeluaran' => [0],
                    'monthly_trend' => [0],
                    'validation_queue' => [0],
                ],
            ];
        }
    }
    
    /**
     * Get bulk financial statistics for date range with single query
     */
    protected function getBulkFinancialStatsForDateRange(int $days): Collection
    {
        try {
            $startDate = Carbon::today()->subDays($days - 1);
            $endDate = Carbon::today();
            
            // Generate date range
            $dateRange = collect();
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $dateRange->push($current->format('Y-m-d'));
                $current->addDay();
            }
            
            // Get all data in bulk queries
            $pendapatanStats = DB::table('pendapatan_harians')
                ->selectRaw('
                    tanggal_input as date, 
                    SUM(nominal) as pendapatan_sum,
                    SUM(CASE WHEN status_validasi = "disetujui" THEN nominal ELSE 0 END) as pendapatan_approved
                ')
                ->whereBetween('tanggal_input', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy('tanggal_input')
                ->get()
                ->keyBy('date');
            
            $pengeluaranStats = DB::table('pengeluaran_harian')
                ->selectRaw('
                    tanggal_input as date, 
                    SUM(nominal) as pengeluaran_sum,
                    SUM(CASE WHEN status_validasi = "disetujui" THEN nominal ELSE 0 END) as pengeluaran_approved
                ')
                ->whereBetween('tanggal_input', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy('tanggal_input')
                ->get()
                ->keyBy('date');
            
            // Use a more database-agnostic approach for grouping by date
            $tindakanData = Tindakan::whereDate('tanggal_tindakan', '>=', $startDate)
                ->whereDate('tanggal_tindakan', '<=', $endDate)
                ->get()
                ->groupBy(function($item) {
                    return $item->tanggal_tindakan->format('Y-m-d');
                });
            
            $tindakanStats = collect();
            foreach ($tindakanData as $date => $items) {
                $tindakanStats->put($date, (object)[
                    'date' => $date,
                    'tindakan_count' => $items->count(),
                    'tindakan_pending' => $items->where('status', 'pending')->count()
                ]);
            }
            
            // Combine data for each date
            return $dateRange->map(function ($date) use ($pendapatanStats, $pengeluaranStats, $tindakanStats) {
                $pendapatanSum = $pendapatanStats->get($date)?->pendapatan_sum ?? 0;
                $pendapatanApproved = $pendapatanStats->get($date)?->pendapatan_approved ?? 0;
                $pengeluaranSum = $pengeluaranStats->get($date)?->pengeluaran_sum ?? 0;
                $pengeluaranApproved = $pengeluaranStats->get($date)?->pengeluaran_approved ?? 0;
                $tindakanCount = $tindakanStats->get($date)?->tindakan_count ?? 0;
                $tindakanPending = $tindakanStats->get($date)?->tindakan_pending ?? 0;
                
                return [
                    'pendapatan_sum' => (float)$pendapatanSum,
                    'pendapatan_approved' => (float)$pendapatanApproved,
                    'pengeluaran_sum' => (float)$pengeluaranSum,
                    'pengeluaran_approved' => (float)$pengeluaranApproved,
                    'tindakan_count' => (int)$tindakanCount,
                    'tindakan_pending' => (int)$tindakanPending,
                    'net_income' => (float)($pendapatanApproved - $pengeluaranApproved),
                    'date' => $date,
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get bulk financial stats for date range', [
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            
            return collect();
        }
    }
    
    /**
     * Get validation summary
     */
    protected function getValidationSummary(int $userId): array
    {
        try {
            $pendingValidations = 0;
            $approvedToday = 0;
            $rejectedToday = 0;
            
            $models = [
                ['model' => Tindakan::class, 'status_field' => 'status'],
                ['model' => PendapatanHarian::class, 'status_field' => 'status_validasi'],
                ['model' => PengeluaranHarian::class, 'status_field' => 'status_validasi'],
            ];
            
            foreach ($models as $modelConfig) {
                $model = $modelConfig['model'];
                $statusField = $modelConfig['status_field'];
                
                $pendingValidations += $model::where($statusField, 'pending')->count();
                
                $approvedToday += $model::where($statusField, 'disetujui')
                    ->whereDate('updated_at', Carbon::today())
                    ->count();
                
                $rejectedToday += $model::where($statusField, 'ditolak')
                    ->whereDate('updated_at', Carbon::today())
                    ->count();
            }
            
            // Get queue urgency levels
            $urgentValidations = Tindakan::where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subHours(24))
                ->count();
            
            return [
                'pending_validations' => $pendingValidations,
                'urgent_validations' => $urgentValidations,
                'approved_today' => $approvedToday,
                'rejected_today' => $rejectedToday,
                'total_today' => $approvedToday + $rejectedToday,
                'approval_rate' => ($approvedToday + $rejectedToday) > 0 ? 
                    round(($approvedToday / ($approvedToday + $rejectedToday)) * 100, 2) : 0,
                'queue_health' => $this->calculateQueueHealth($pendingValidations, $urgentValidations),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get validation summary', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'pending_validations' => 0,
                'urgent_validations' => 0,
                'approved_today' => 0,
                'rejected_today' => 0,
                'total_today' => 0,
                'approval_rate' => 0,
                'queue_health' => 'good',
            ];
        }
    }
    
    /**
     * Calculate queue health status
     */
    protected function calculateQueueHealth(int $pending, int $urgent): string
    {
        if ($urgent > 10) return 'critical';
        if ($pending > 50) return 'poor';
        if ($pending > 20) return 'warning';
        return 'good';
    }
    
    /**
     * Get financial performance metrics
     */
    protected function getFinancialMetrics(int $userId): array
    {
        try {
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            
            $currentMonthStats = $this->getFinancialStatsForMonth($thisMonth);
            $lastMonthStats = $this->getFinancialStatsForMonth($lastMonth);
            
            // Calculate growth rates
            $incomeGrowth = $lastMonthStats['pendapatan_approved'] > 0 ? 
                (($currentMonthStats['pendapatan_approved'] - $lastMonthStats['pendapatan_approved']) / $lastMonthStats['pendapatan_approved']) * 100 : 0;
            
            $expenseGrowth = $lastMonthStats['pengeluaran_approved'] > 0 ? 
                (($currentMonthStats['pengeluaran_approved'] - $lastMonthStats['pengeluaran_approved']) / $lastMonthStats['pengeluaran_approved']) * 100 : 0;
            
            return [
                'income_growth' => round($incomeGrowth, 2),
                'expense_growth' => round($expenseGrowth, 2),
                'profit_margin' => $currentMonthStats['pendapatan_approved'] > 0 ? 
                    round(($currentMonthStats['net_income'] / $currentMonthStats['pendapatan_approved']) * 100, 2) : 0,
                'expense_ratio' => $currentMonthStats['pendapatan_approved'] > 0 ? 
                    round(($currentMonthStats['pengeluaran_approved'] / $currentMonthStats['pendapatan_approved']) * 100, 2) : 0,
                'financial_health' => $this->calculateFinancialHealth($currentMonthStats),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get financial metrics', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'income_growth' => 0,
                'expense_growth' => 0,
                'profit_margin' => 0,
                'expense_ratio' => 0,
                'financial_health' => 'unknown',
            ];
        }
    }
    
    /**
     * Calculate financial health status
     */
    protected function calculateFinancialHealth(array $stats): string
    {
        $profitMargin = $stats['pendapatan_approved'] > 0 ? 
            ($stats['net_income'] / $stats['pendapatan_approved']) * 100 : 0;
        
        if ($profitMargin >= 20) return 'excellent';
        if ($profitMargin >= 10) return 'good';
        if ($profitMargin >= 0) return 'fair';
        return 'poor';
    }
    
    /**
     * Get cash flow analysis
     */
    protected function getCashFlowAnalysis(int $userId): array
    {
        try {
            $last30Days = $this->getBulkFinancialStatsForDateRange(30);
            
            $totalIncome = $last30Days->sum('pendapatan_approved');
            $totalExpenses = $last30Days->sum('pengeluaran_approved');
            $netCashFlow = $totalIncome - $totalExpenses;
            
            // Calculate weekly averages
            $weeklyAverage = $last30Days->chunk(7)->map(function ($week) {
                return $week->sum('net_income');
            });
            
            return [
                'total_income_30d' => $totalIncome,
                'total_expenses_30d' => $totalExpenses,
                'net_cash_flow_30d' => $netCashFlow,
                'weekly_averages' => $weeklyAverage->toArray(),
                'cash_flow_trend' => $this->calculateCashFlowTrend($last30Days),
                'projected_monthly' => $this->projectMonthlyCashFlow($last30Days),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get cash flow analysis', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_income_30d' => 0,
                'total_expenses_30d' => 0,
                'net_cash_flow_30d' => 0,
                'weekly_averages' => [0, 0, 0, 0],
                'cash_flow_trend' => 'stable',
                'projected_monthly' => 0,
            ];
        }
    }
    
    /**
     * Calculate cash flow trend
     */
    protected function calculateCashFlowTrend(Collection $data): string
    {
        if ($data->count() < 7) return 'stable';
        
        $recent = $data->slice(-7)->avg('net_income');
        $previous = $data->slice(-14, 7)->avg('net_income');
        
        if ($recent > $previous * 1.1) return 'improving';
        if ($recent < $previous * 0.9) return 'declining';
        return 'stable';
    }
    
    /**
     * Project monthly cash flow
     */
    protected function projectMonthlyCashFlow(Collection $data): float
    {
        $dailyAverage = $data->avg('net_income');
        $daysInMonth = Carbon::now()->daysInMonth;
        return $dailyAverage * $daysInMonth;
    }
    
    /**
     * Get budget tracking metrics
     */
    protected function getBudgetTracking(int $userId): array
    {
        try {
            $thisMonth = Carbon::now()->startOfMonth();
            $currentStats = $this->getFinancialStatsForMonth($thisMonth);
            
            // These could be configurable budget targets
            $monthlyIncomeTarget = 50000000; // 50M IDR
            $monthlyExpenseLimit = 40000000; // 40M IDR
            
            $incomeProgress = $monthlyIncomeTarget > 0 ? 
                ($currentStats['pendapatan_approved'] / $monthlyIncomeTarget) * 100 : 0;
            
            $expenseProgress = $monthlyExpenseLimit > 0 ? 
                ($currentStats['pengeluaran_approved'] / $monthlyExpenseLimit) * 100 : 0;
            
            return [
                'monthly_income_target' => $monthlyIncomeTarget,
                'monthly_expense_limit' => $monthlyExpenseLimit,
                'income_progress' => round($incomeProgress, 2),
                'expense_progress' => round($expenseProgress, 2),
                'target_achievement' => $this->calculateTargetAchievement($incomeProgress),
                'expense_status' => $this->calculateExpenseStatus($expenseProgress),
                'days_remaining' => Carbon::now()->endOfMonth()->diffInDays(Carbon::now()),
                'daily_target_needed' => $this->calculateDailyTargetNeeded($currentStats, $monthlyIncomeTarget),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get budget tracking', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'monthly_income_target' => 0,
                'monthly_expense_limit' => 0,
                'income_progress' => 0,
                'expense_progress' => 0,
                'target_achievement' => 'poor',
                'expense_status' => 'good',
                'days_remaining' => 0,
                'daily_target_needed' => 0,
            ];
        }
    }
    
    /**
     * Calculate target achievement level
     */
    protected function calculateTargetAchievement(float $progress): string
    {
        if ($progress >= 100) return 'exceeded';
        if ($progress >= 80) return 'excellent';
        if ($progress >= 60) return 'good';
        if ($progress >= 40) return 'fair';
        return 'poor';
    }
    
    /**
     * Calculate expense status
     */
    protected function calculateExpenseStatus(float $progress): string
    {
        if ($progress >= 100) return 'over_budget';
        if ($progress >= 90) return 'warning';
        if ($progress >= 70) return 'caution';
        return 'good';
    }
    
    /**
     * Calculate daily target needed
     */
    protected function calculateDailyTargetNeeded(array $currentStats, float $monthlyTarget): float
    {
        $remaining = $monthlyTarget - $currentStats['pendapatan_approved'];
        $daysRemaining = Carbon::now()->endOfMonth()->diffInDays(Carbon::now());
        
        return $daysRemaining > 0 ? $remaining / $daysRemaining : 0;
    }
    
    /**
     * Clear cache for bendahara stats
     */
    public function clearStatsCache(int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        Cache::forget("bendahara_stats_{$userId}");
        Cache::forget("bendahara_trend_analysis_{$userId}");
    }
    
    /**
     * Get empty stats structure
     */
    protected function getEmptyStats(): array
    {
        return [
            'daily' => [
                'today' => $this->getEmptyDayStats(),
                'yesterday' => $this->getEmptyDayStats(),
                'trends' => $this->getEmptyTrends(),
            ],
            'monthly' => [
                'this_month' => $this->getEmptyDayStats(),
                'last_month' => $this->getEmptyDayStats(),
                'trends' => $this->getEmptyTrends(),
            ],
            'trends' => [
                'last_7_days' => [],
                'last_30_days' => [],
                'charts' => [
                    'daily_income' => [0],
                    'daily_pendapatan' => [0],
                    'daily_pengeluaran' => [0],
                    'monthly_trend' => [0],
                    'validation_queue' => [0],
                ],
            ],
            'validation_summary' => [
                'pending_validations' => 0,
                'urgent_validations' => 0,
                'approved_today' => 0,
                'rejected_today' => 0,
                'total_today' => 0,
                'approval_rate' => 0,
                'queue_health' => 'good',
            ],
            'financial_metrics' => [
                'income_growth' => 0,
                'expense_growth' => 0,
                'profit_margin' => 0,
                'expense_ratio' => 0,
                'financial_health' => 'unknown',
            ],
            'cash_flow' => [
                'total_income_30d' => 0,
                'total_expenses_30d' => 0,
                'net_cash_flow_30d' => 0,
                'weekly_averages' => [0, 0, 0, 0],
                'cash_flow_trend' => 'stable',
                'projected_monthly' => 0,
            ],
            'budget_tracking' => [
                'monthly_income_target' => 0,
                'monthly_expense_limit' => 0,
                'income_progress' => 0,
                'expense_progress' => 0,
                'target_achievement' => 'poor',
                'expense_status' => 'good',
                'days_remaining' => 0,
                'daily_target_needed' => 0,
            ],
        ];
    }
    
    /**
     * Get empty day stats structure
     */
    protected function getEmptyDayStats(): array
    {
        return [
            'pendapatan_sum' => 0,
            'pendapatan_approved' => 0,
            'pendapatan_pending' => 0,
            'pengeluaran_sum' => 0,
            'pengeluaran_approved' => 0,
            'pengeluaran_pending' => 0,
            'tindakan_count' => 0,
            'tindakan_pending' => 0,
            'jaspel_pending' => 0,
            'net_income' => 0,
        ];
    }
    
    /**
     * Get empty trends structure
     */
    protected function getEmptyTrends(): array
    {
        $metrics = [
            'pendapatan_sum', 'pendapatan_approved', 'pengeluaran_sum', 
            'pengeluaran_approved', 'net_income', 'tindakan_count'
        ];
        $trends = [];
        
        foreach ($metrics as $metric) {
            $trends[$metric] = [
                'current' => 0,
                'previous' => 0,
                'percentage' => 0,
                'direction' => 'stable',
                'change' => 0,
            ];
        }
        
        return $trends;
    }
}