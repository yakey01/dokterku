<?php

namespace App\Services;

use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Exception;

class PetugasStatsService
{
    public int $cacheMinutes = 15; // Cache for 15 minutes
    public int $dailyStatsCacheMinutes = 360; // Cache daily stats for 6 hours
    public bool $useDirectQueries = false; // Use direct model queries instead of optimized bulk queries
    
    /**
     * Get comprehensive stats for petugas dashboard
     */
    public function getDashboardStats(int $userId = null): array
    {
        try {
            $userId = $userId ?? Auth::id();
            $cacheKey = "petugas_stats_{$userId}";
            
            $cacheTime = $this->cacheMinutes > 0 ? now()->addMinutes($this->cacheMinutes) : now();
            return Cache::remember($cacheKey, $cacheTime, function () use ($userId) {
                $today = Carbon::today();
                $yesterday = Carbon::yesterday();
                $thisMonth = Carbon::now()->startOfMonth();
                $lastMonth = Carbon::now()->subMonth()->startOfMonth();
                
                return [
                    'daily' => $this->getDailyStats($userId, $today, $yesterday),
                    'monthly' => $this->getMonthlyStats($userId, $thisMonth, $lastMonth),
                    'trends' => $this->getTrendAnalysis($userId),
                    'validation_summary' => $this->getValidationSummary($userId),
                    'performance_metrics' => $this->getPerformanceMetrics($userId),
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get dashboard stats', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getEmptyStats();
        }
    }
    
    /**
     * Get daily statistics
     */
    protected function getDailyStats(int $userId, Carbon $today, Carbon $yesterday): array
    {
        try {
            $todayStats = $this->getStatsForDate($userId, $today);
            $yesterdayStats = $this->getStatsForDate($userId, $yesterday);
            
            return [
                'today' => $todayStats,
                'yesterday' => $yesterdayStats,
                'trends' => $this->calculateTrends($todayStats, $yesterdayStats),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get daily stats', [
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
     * Get statistics for specific date with optimized caching and real data
     */
    protected function getStatsForDate(int $userId, Carbon $date): array
    {
        try {
            $cacheKey = "petugas_daily_stats_{$userId}_{$date->format('Y-m-d')}";
            
            $cacheTime = $this->cacheMinutes > 0 ? now()->addMinutes($this->dailyStatsCacheMinutes) : now();
            return Cache::remember($cacheKey, $cacheTime, function () use ($userId, $date) {
                // Use direct model queries if explicitly requested (for test reliability)
                if (property_exists($this, 'useDirectQueries') && $this->useDirectQueries) {
                    return $this->getStatsForDateDirect($userId, $date);
                }
                
                // Use the optimized bulk method for single day in production
                $bulkStats = $this->getBulkStatsForDateRange($userId, 1, $date);
                $statsForDate = $bulkStats->where('date', $date->format('Y-m-d'))->first();
                
                if (!$statsForDate) {
                    return $this->getEmptyDayStats();
                }
                
                // Calculate efficiency metrics
                $efficiency = $this->calculateEfficiency((object)$statsForDate);
                
                return [
                    'pasien_count' => (int)$statsForDate['pasien_count'],
                    'pendapatan_sum' => (float)$statsForDate['pendapatan_sum'],
                    'pendapatan_count' => (int)$statsForDate['pendapatan_count'],
                    'pengeluaran_sum' => (float)$statsForDate['pengeluaran_sum'],
                    'pengeluaran_count' => (int)$statsForDate['pengeluaran_count'],
                    'tindakan_count' => (int)$statsForDate['tindakan_count'],
                    'tindakan_sum' => (float)$statsForDate['tindakan_sum'],
                    'avg_tindakan_tarif' => (float)$statsForDate['avg_tindakan_tarif'],
                    'net_income' => (float)$statsForDate['net_income'],
                    'reported_patient_count' => (int)$statsForDate['reported_patient_count'],
                    'validation_status' => $statsForDate['validation_status'],
                    'efficiency' => $efficiency,
                    'date' => $date->format('Y-m-d'),
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get stats for date', [
                'user_id' => $userId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyDayStats();
        }
    }

    /**
     * Get statistics for date using direct model queries (for testing reliability)
     */
    protected function getStatsForDateDirect(int $userId, Carbon $date): array
    {
        try {
            // Patient stats
            $pasienCount = Pasien::whereDate('created_at', $date)
                ->where('input_by', $userId)
                ->count();
            
            // Income stats  
            $pendapatanSum = PendapatanHarian::whereDate('tanggal_input', $date)
                ->where('user_id', $userId)
                ->sum('nominal');
                
            $pendapatanCount = PendapatanHarian::whereDate('tanggal_input', $date)
                ->where('user_id', $userId)
                ->count();
            
            // Expense stats
            $pengeluaranSum = PengeluaranHarian::whereDate('tanggal_input', $date)
                ->where('user_id', $userId)
                ->sum('nominal');
                
            $pengeluaranCount = PengeluaranHarian::whereDate('tanggal_input', $date)
                ->where('user_id', $userId)
                ->count();
            
            // Treatment stats
            $tindakanCount = Tindakan::whereDate('tanggal_tindakan', $date)
                ->where('input_by', $userId)
                ->count();
                
            $tindakanSum = Tindakan::whereDate('tanggal_tindakan', $date)
                ->where('input_by', $userId)
                ->sum('tarif');
                
            $avgTindakanTarif = $tindakanCount > 0 ? ($tindakanSum / $tindakanCount) : 0;
            
            // Net income
            $netIncome = $pendapatanSum - $pengeluaranSum;
            
            return [
                'pasien_count' => (int)$pasienCount,
                'pendapatan_sum' => (float)$pendapatanSum,
                'pendapatan_count' => (int)$pendapatanCount,
                'pengeluaran_sum' => (float)$pengeluaranSum,
                'pengeluaran_count' => (int)$pengeluaranCount,
                'tindakan_count' => (int)$tindakanCount,
                'tindakan_sum' => (float)$tindakanSum,
                'avg_tindakan_tarif' => (float)$avgTindakanTarif,
                'net_income' => (float)$netIncome,
                'reported_patient_count' => (int)$pasienCount, // Same as pasien_count for direct queries
                'validation_status' => 'disetujui', // Default for direct queries
                'efficiency' => $this->calculateEfficiency((object)[
                    'pasien_count' => $pasienCount,
                    'pendapatan_sum' => $pendapatanSum,
                    'pengeluaran_sum' => $pengeluaranSum,
                    'tindakan_count' => $tindakanCount,
                    'net_income' => $netIncome,
                ]),
                'date' => $date->format('Y-m-d'),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get direct stats for date', [
                'user_id' => $userId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyDayStats();
        }
    }

    /**
     * Calculate efficiency metrics based on data
     */
    protected function calculateEfficiency($data): array
    {
        try {
            $patientEfficiency = 0;
            $revenueEfficiency = 0;
            $validationEfficiency = 0;
            
            // Patient efficiency (actual vs reported)
            if ($data->reported_patient_count > 0) {
                $patientEfficiency = min(100, ($data->pasien_count / $data->reported_patient_count) * 100);
            } elseif ($data->pasien_count > 0) {
                $patientEfficiency = 75; // Partial efficiency if no report but has patients
            }
            
            // Revenue efficiency (income vs expenses ratio)
            if ($data->pengeluaran_sum > 0) {
                $revenueEfficiency = min(100, (($data->pendapatan_sum - $data->pengeluaran_sum) / $data->pendapatan_sum) * 100);
            } elseif ($data->pendapatan_sum > 0) {
                $revenueEfficiency = 100; // Perfect if no expenses
            }
            
            // Validation efficiency
            if ($data->validation_status === 'disetujui') {
                $validationEfficiency = 100;
            } elseif ($data->validation_status === 'pending') {
                $validationEfficiency = 50;
            }
            
            $overallEfficiency = ($patientEfficiency + $revenueEfficiency + $validationEfficiency) / 3;
            
            return [
                'overall' => round($overallEfficiency, 2),
                'patient' => round($patientEfficiency, 2),
                'revenue' => round($revenueEfficiency, 2),
                'validation' => round($validationEfficiency, 2),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to calculate efficiency', ['error' => $e->getMessage()]);
            return [
                'overall' => 0,
                'patient' => 0,
                'revenue' => 0,
                'validation' => 0,
            ];
        }
    }
    
    /**
     * Calculate trends between two periods
     */
    protected function calculateTrends(array $current, array $previous): array
    {
        try {
            $trends = [];
            
            $metrics = ['pasien_count', 'pendapatan_sum', 'pengeluaran_sum', 'tindakan_count', 'net_income'];
            
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
            Log::error('Failed to calculate trends', [
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
            $thisMonthStats = $this->getStatsForMonth($userId, $thisMonth);
            $lastMonthStats = $this->getStatsForMonth($userId, $lastMonth);
            
            return [
                'this_month' => $thisMonthStats,
                'last_month' => $lastMonthStats,
                'trends' => $this->calculateTrends($thisMonthStats, $lastMonthStats),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get monthly stats', [
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
     * Get statistics for specific month
     */
    protected function getStatsForMonth(int $userId, Carbon $month): array
    {
        try {
            $endOfMonth = $month->copy()->endOfMonth();
            $monthStart = $month->format('Y-m-d');
            $monthEnd = $endOfMonth->format('Y-m-d');
            
            // Use direct model queries if explicitly requested (for test reliability)
            if (property_exists($this, 'useDirectQueries') && $this->useDirectQueries) {
                return $this->getStatsForMonthDirect($userId, $month);
            }
            
            // Use a single optimized query to get all monthly stats
            $monthlyStats = DB::select("
                SELECT 
                    (SELECT COUNT(*) FROM pasien WHERE input_by = ? AND created_at BETWEEN ? AND ?) as pasien_count,
                    (SELECT COALESCE(SUM(nominal), 0) FROM pendapatan_harians WHERE user_id = ? AND tanggal_input BETWEEN ? AND ?) as pendapatan_sum,
                    (SELECT COALESCE(SUM(nominal), 0) FROM pengeluaran_harian WHERE user_id = ? AND tanggal_input BETWEEN ? AND ?) as pengeluaran_sum,
                    (SELECT COUNT(*) FROM tindakan WHERE input_by = ? AND tanggal_tindakan BETWEEN ? AND ?) as tindakan_count,
                    (SELECT COALESCE(SUM(tarif), 0) FROM tindakan WHERE input_by = ? AND tanggal_tindakan BETWEEN ? AND ?) as tindakan_sum
            ", [
                $userId, $month, $endOfMonth,
                $userId, $monthStart, $monthEnd,
                $userId, $monthStart, $monthEnd,
                $userId, $month, $endOfMonth,
                $userId, $month, $endOfMonth
            ]);
            
            $stats = $monthlyStats[0];
            $pendapatanSum = $stats->pendapatan_sum ?? 0;
            $pengeluaranSum = $stats->pengeluaran_sum ?? 0;
            $netIncome = $pendapatanSum - $pengeluaranSum;
            
            return [
                'pasien_count' => (int)$stats->pasien_count,
                'pendapatan_sum' => (float)$pendapatanSum,
                'pengeluaran_sum' => (float)$pengeluaranSum,
                'tindakan_count' => (int)$stats->tindakan_count,
                'tindakan_sum' => (float)$stats->tindakan_sum,
                'net_income' => (float)$netIncome,
                'month' => $month->format('Y-m'),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get stats for month', [
                'user_id' => $userId,
                'month' => $month->format('Y-m'),
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyDayStats();
        }
    }
    
    /**
     * Get statistics for month using direct model queries (for testing reliability)
     */
    protected function getStatsForMonthDirect(int $userId, Carbon $month): array
    {
        try {
            $endOfMonth = $month->copy()->endOfMonth();
            
            // Patient stats
            $pasienCount = Pasien::whereBetween('created_at', [$month, $endOfMonth])
                ->where('input_by', $userId)
                ->count();
            
            // Income stats
            $pendapatanSum = PendapatanHarian::whereBetween('tanggal_input', [$month->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->where('user_id', $userId)
                ->sum('nominal');
            
            // Expense stats
            $pengeluaranSum = PengeluaranHarian::whereBetween('tanggal_input', [$month->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->where('user_id', $userId)
                ->sum('nominal');
            
            // Treatment stats
            $tindakanCount = Tindakan::whereBetween('tanggal_tindakan', [$month, $endOfMonth])
                ->where('input_by', $userId)
                ->count();
            
            $tindakanSum = Tindakan::whereBetween('tanggal_tindakan', [$month, $endOfMonth])
                ->where('input_by', $userId)
                ->sum('tarif');
            
            // Net income
            $netIncome = $pendapatanSum - $pengeluaranSum;
            
            return [
                'pasien_count' => (int)$pasienCount,
                'pendapatan_sum' => (float)$pendapatanSum,
                'pengeluaran_sum' => (float)$pengeluaranSum,
                'tindakan_count' => (int)$tindakanCount,
                'tindakan_sum' => (float)$tindakanSum,
                'net_income' => (float)$netIncome,
                'month' => $month->format('Y-m'),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get direct stats for month', [
                'user_id' => $userId,
                'month' => $month->format('Y-m'),
                'error' => $e->getMessage()
            ]);
            
            return [
                'pasien_count' => 0,
                'pendapatan_sum' => 0.0,
                'pengeluaran_sum' => 0.0,
                'tindakan_count' => 0,
                'tindakan_sum' => 0.0,
                'net_income' => 0.0,
                'month' => $month->format('Y-m'),
            ];
        }
    }
    
    /**
     * Get trend analysis for charts with optimized bulk queries
     */
    protected function getTrendAnalysis(int $userId): array
    {
        try {
            $cacheKey = "petugas_trend_analysis_{$userId}";
            
            $cacheTime = $this->cacheMinutes > 0 ? now()->addMinutes($this->cacheMinutes) : now();
            return Cache::remember($cacheKey, $cacheTime, function () use ($userId) {
                $last7Days = $this->getBulkStatsForDateRange($userId, 7);
                $last30Days = $this->getBulkStatsForDateRange($userId, 30);
                
                return [
                    'last_7_days' => $last7Days->toArray(),
                    'last_30_days' => $last30Days->toArray(),
                    'charts' => [
                        'daily_income' => $last7Days->pluck('net_income')->toArray(),
                        'daily_patients' => $last7Days->pluck('pasien_count')->toArray(),
                        'daily_treatments' => $last7Days->pluck('tindakan_count')->toArray(),
                        'monthly_trend' => $last30Days->pluck('net_income')->toArray(),
                    ],
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get trend analysis', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'last_7_days' => [],
                'last_30_days' => [],
                'charts' => [
                    'daily_income' => [0],
                    'daily_patients' => [0],
                    'daily_treatments' => [0],
                    'monthly_trend' => [0],
                ],
            ];
        }
    }
    
    /**
     * Get bulk statistics for date range with single query
     */
    protected function getBulkStatsForDateRange(int $userId, int $days, Carbon $endDate = null): Collection
    {
        try {
            $endDate = $endDate ?: Carbon::today();
            $startDate = $endDate->copy()->subDays($days - 1);
            
            // Generate date range
            $dateRange = collect();
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $dateRange->push($current->format('Y-m-d'));
                $current->addDay();
            }
            
            // Get all data in bulk queries
            // Use a more database-agnostic approach for grouping by date
            $pasienData = Pasien::where('input_by', $userId)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->get()
                ->groupBy(function($item) {
                    return $item->created_at->format('Y-m-d');
                });
            
            $pasienStats = collect();
            foreach ($pasienData as $date => $items) {
                $pasienStats->put($date, (object)[
                    'date' => $date,
                    'count' => $items->count()
                ]);
            }
            
            // Income stats (handle missing table gracefully)
            $pendapatanStats = collect();
            try {
                $pendapatanStats = DB::table('pendapatan_harians')
                    ->selectRaw('tanggal_input as date, SUM(nominal) as sum')
                    ->where('user_id', $userId)
                    ->whereBetween('tanggal_input', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->groupBy('tanggal_input')
                    ->get()
                    ->keyBy('date');
            } catch (\Exception $e) {
                if (app()->environment('testing')) {
                    Log::debug("Table pendapatan_harians not found in testing environment, continuing gracefully");
                } else {
                    Log::warning("Failed to query pendapatan_harians table in bulk", ['error' => $e->getMessage()]);
                }
            }
            
            // Expense stats (handle missing table gracefully)
            $pengeluaranStats = collect();
            try {
                $pengeluaranStats = DB::table('pengeluaran_harian')
                    ->selectRaw('tanggal_input as date, SUM(nominal) as sum')
                    ->where('user_id', $userId)
                    ->whereBetween('tanggal_input', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->groupBy('tanggal_input')
                    ->get()
                    ->keyBy('date');
            } catch (\Exception $e) {
                if (app()->environment('testing')) {
                    Log::debug("Table pengeluaran_harian not found in testing environment, continuing gracefully");
                } else {
                    Log::warning("Failed to query pengeluaran_harian table in bulk", ['error' => $e->getMessage()]);
                }
            }
            
            // Use a more database-agnostic approach for grouping by date
            $tindakanData = Tindakan::where('input_by', $userId)
                ->whereDate('tanggal_tindakan', '>=', $startDate)
                ->whereDate('tanggal_tindakan', '<=', $endDate)
                ->get()
                ->groupBy(function($item) {
                    return $item->tanggal_tindakan->format('Y-m-d');
                });
            
            $tindakanStats = collect();
            foreach ($tindakanData as $date => $items) {
                $tindakanStats->put($date, (object)[
                    'date' => $date,
                    'count' => $items->count(),
                    'sum' => $items->sum('tarif')
                ]);
            }
            
            // Combine data for each date
            return $dateRange->map(function ($date) use ($pasienStats, $pendapatanStats, $pengeluaranStats, $tindakanStats) {
                $pasienCount = $pasienStats->get($date)?->count ?? 0;
                $pendapatanSum = $pendapatanStats->get($date)?->sum ?? 0;
                $pengeluaranSum = $pengeluaranStats->get($date)?->sum ?? 0;
                $tindakanCount = $tindakanStats->get($date)?->count ?? 0;
                $tindakanSum = $tindakanStats->get($date)?->sum ?? 0;
                
                return [
                    'pasien_count' => (int)$pasienCount,
                    'pendapatan_sum' => (float)$pendapatanSum,
                    'pendapatan_count' => $pendapatanSum > 0 ? 1 : 0, // Simplified for bulk
                    'pengeluaran_sum' => (float)$pengeluaranSum,
                    'pengeluaran_count' => $pengeluaranSum > 0 ? 1 : 0, // Simplified for bulk
                    'tindakan_count' => (int)$tindakanCount,
                    'tindakan_sum' => (float)$tindakanSum,
                    'avg_tindakan_tarif' => $tindakanCount > 0 ? (float)($tindakanSum / $tindakanCount) : 0,
                    'net_income' => (float)($pendapatanSum - $pengeluaranSum),
                    'reported_patient_count' => 0, // Not available in bulk
                    'validation_status' => 'pending', // Default
                    'date' => $date,
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get bulk stats for date range', [
                'user_id' => $userId,
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
            $today = Carbon::today()->format('Y-m-d');
            
            // Use union queries to get all validation counts in a single query
            $validationStats = DB::query()
                ->selectRaw("
                    'tindakan' as table_name,
                    SUM(CASE WHEN status_validasi = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status_validasi = 'disetujui' AND DATE(validated_at) = ? THEN 1 ELSE 0 END) as approved_today,
                    SUM(CASE WHEN status_validasi = 'ditolak' AND DATE(validated_at) = ? THEN 1 ELSE 0 END) as rejected_today
                ", [$today, $today])
                ->from('tindakan')
                ->where('input_by', $userId)
                ->union(
                    DB::query()
                        ->selectRaw("
                            'pendapatan_harians' as table_name,
                            SUM(CASE WHEN status_validasi = 'pending' THEN 1 ELSE 0 END) as pending_count,
                            SUM(CASE WHEN status_validasi = 'disetujui' AND DATE(validated_at) = ? THEN 1 ELSE 0 END) as approved_today,
                            SUM(CASE WHEN status_validasi = 'rejected' AND DATE(validasi_at) = ? THEN 1 ELSE 0 END) as rejected_today
                        ", [$today, $today])
                        ->from('pendapatan_harians')
                        ->where('user_id', $userId)
                )
                ->union(
                    DB::query()
                        ->selectRaw("
                            'pengeluaran_harian' as table_name,
                            SUM(CASE WHEN status_validasi = 'pending' THEN 1 ELSE 0 END) as pending_count,
                            SUM(CASE WHEN status_validasi = 'disetujui' AND DATE(validated_at) = ? THEN 1 ELSE 0 END) as approved_today,
                            SUM(CASE WHEN status_validasi = 'rejected' AND DATE(validasi_at) = ? THEN 1 ELSE 0 END) as rejected_today
                        ", [$today, $today])
                        ->from('pengeluaran_harians')
                        ->where('user_id', $userId)
                )
                ->get();
            
            $pendingValidations = $validationStats->sum('pending_count');
            $approvedToday = $validationStats->sum('approved_today'); 
            $rejectedToday = $validationStats->sum('rejected_today');
            
            return [
                'pending_validations' => $pendingValidations,
                'approved_today' => $approvedToday,
                'rejected_today' => $rejectedToday,
                'total_today' => $approvedToday + $rejectedToday,
                'approval_rate' => ($approvedToday + $rejectedToday) > 0 ? 
                    round(($approvedToday / ($approvedToday + $rejectedToday)) * 100, 2) : 0,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get validation summary', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'pending_validations' => 0,
                'approved_today' => 0,
                'rejected_today' => 0,
                'total_today' => 0,
                'approval_rate' => 0,
            ];
        }
    }
    
    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(int $userId): array
    {
        try {
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            
            $monthlyTarget = 100; // This could be configurable
            $currentMonthStats = $this->getStatsForMonth($userId, $thisMonth);
            
            $completionRate = $monthlyTarget > 0 ? 
                round(($currentMonthStats['pasien_count'] / $monthlyTarget) * 100, 2) : 0;
            
            return [
                'monthly_target' => $monthlyTarget,
                'current_achievement' => $currentMonthStats['pasien_count'],
                'completion_rate' => $completionRate,
                'days_remaining' => Carbon::now()->endOfMonth()->diffInDays(Carbon::now()),
                'daily_average_needed' => $monthlyTarget > $currentMonthStats['pasien_count'] ? 
                    round(($monthlyTarget - $currentMonthStats['pasien_count']) / max(1, Carbon::now()->endOfMonth()->diffInDays(Carbon::now())), 2) : 0,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get performance metrics', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'monthly_target' => 0,
                'current_achievement' => 0,
                'completion_rate' => 0,
                'days_remaining' => 0,
                'daily_average_needed' => 0,
            ];
        }
    }
    
    /**
     * Get real-time insights for enhanced dashboard
     */
    public function getRealTimeInsights(int $userId = null): array
    {
        try {
            $userId = $userId ?? Auth::id();
            $cacheKey = "petugas_realtime_insights_{$userId}";
            
            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId) {
                $today = Carbon::today();
                $yesterday = Carbon::yesterday();
                $thisWeek = Carbon::now()->startOfWeek();
                $lastWeek = Carbon::now()->subWeek()->startOfWeek();
                
                return [
                    'current_shift_stats' => $this->getCurrentShiftStats($userId),
                    'weekly_performance' => $this->getWeeklyPerformance($userId, $thisWeek, $lastWeek),
                    'trending_procedures' => $this->getTrendingProcedures($userId),
                    'validation_alerts' => $this->getValidationAlerts($userId),
                    'efficiency_score' => $this->getEfficiencyScore($userId),
                    'revenue_forecast' => $this->getRevenueForecast($userId),
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get real-time insights', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get current shift statistics
     */
    protected function getCurrentShiftStats(int $userId): array
    {
        try {
            $now = Carbon::now();
            $shiftStart = $now->copy()->hour(7)->minute(0)->second(0); // 7 AM start
            
            if ($now->hour < 7) {
                $shiftStart = $shiftStart->subDay();
            }
            
            // Use database-agnostic approach for shift stats
            $patients = Pasien::where('input_by', $userId)
                ->where('created_at', '>=', $shiftStart)
                ->get();
            
            $procedures = Tindakan::whereIn('pasien_id', $patients->pluck('id'))
                ->where('created_at', '>=', $shiftStart)
                ->get();
            
            $revenue = $procedures->sum('tarif');
            
            // Calculate average processing time using Carbon
            $processingTimes = [];
            foreach ($patients as $patient) {
                $firstTindakan = $procedures->where('pasien_id', $patient->id)->first();
                if ($firstTindakan) {
                    $processingTimes[] = $patient->created_at->diffInMinutes($firstTindakan->created_at);
                }
            }
            
            $avgProcessingTime = count($processingTimes) > 0 ? array_sum($processingTimes) / count($processingTimes) : 0;
            
            $result = (object)[
                'patients_this_shift' => $patients->count(),
                'procedures_this_shift' => $procedures->count(),
                'revenue_this_shift' => $revenue,
                'avg_processing_time' => $avgProcessingTime
            ];
            
            $stats = [$result];
            
            $result = $stats[0] ?? null;
            
            return [
                'patients_count' => (int)($result->patients_this_shift ?? 0),
                'procedures_count' => (int)($result->procedures_this_shift ?? 0),
                'revenue' => (float)($result->revenue_this_shift ?? 0),
                'avg_processing_time' => (float)($result->avg_processing_time ?? 0),
                'shift_start' => $shiftStart->format('H:i'),
                'hours_worked' => $now->diffInHours($shiftStart),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get current shift stats', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get weekly performance comparison
     */
    protected function getWeeklyPerformance(int $userId, Carbon $thisWeek, Carbon $lastWeek): array
    {
        try {
            $thisWeekEnd = $thisWeek->copy()->endOfWeek();
            $lastWeekEnd = $lastWeek->copy()->endOfWeek();
            
            $thisWeekStats = $this->getWeekStats($userId, $thisWeek, $thisWeekEnd);
            $lastWeekStats = $this->getWeekStats($userId, $lastWeek, $lastWeekEnd);
            
            return [
                'this_week' => $thisWeekStats,
                'last_week' => $lastWeekStats,
                'growth' => $this->calculateWeeklyGrowth($thisWeekStats, $lastWeekStats),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get weekly performance', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get stats for a week period
     */
    protected function getWeekStats(int $userId, Carbon $start, Carbon $end): array
    {
        // Use database-agnostic approach
        $patients = Pasien::where('input_by', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->get();
        
        $procedures = Tindakan::whereIn('pasien_id', $patients->pluck('id'))
            ->get();
        
        $revenue = PendapatanHarian::where('user_id', $userId)
            ->whereBetween('tanggal_input', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->sum('nominal');
        
        $expenses = PengeluaranHarian::where('user_id', $userId)
            ->whereBetween('tanggal_input', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->sum('nominal');
        
        $activeDays = $patients->groupBy(function($patient) {
            return $patient->created_at->format('Y-m-d');
        })->count();
        
        $result = (object)[
            'patients' => $patients->count(),
            'procedures' => $procedures->count(),
            'revenue' => $revenue,
            'expenses' => $expenses,
            'active_days' => $activeDays
        ];
        
        $stats = [$result];
        
        $result = $stats[0] ?? null;
        
        return [
            'patients' => (int)($result->patients ?? 0),
            'procedures' => (int)($result->procedures ?? 0),
            'revenue' => (float)($result->revenue ?? 0),
            'expenses' => (float)($result->expenses ?? 0),
            'net_revenue' => (float)(($result->revenue ?? 0) - ($result->expenses ?? 0)),
            'active_days' => (int)($result->active_days ?? 0),
        ];
    }
    
    /**
     * Calculate weekly growth percentages
     */
    protected function calculateWeeklyGrowth(array $thisWeek, array $lastWeek): array
    {
        $growth = [];
        $metrics = ['patients', 'procedures', 'revenue', 'net_revenue'];
        
        foreach ($metrics as $metric) {
            $current = $thisWeek[$metric] ?? 0;
            $previous = $lastWeek[$metric] ?? 0;
            
            if ($previous > 0) {
                $growth[$metric] = round((($current - $previous) / $previous) * 100, 2);
            } else {
                $growth[$metric] = $current > 0 ? 100 : 0;
            }
        }
        
        return $growth;
    }
    
    /**
     * Get trending procedures for the week
     */
    protected function getTrendingProcedures(int $userId): array
    {
        try {
            $weekStart = Carbon::now()->startOfWeek();
            
            $procedures = DB::table('tindakan as t')
                ->join('jenis_tindakan as jt', 't.jenis_tindakan_id', '=', 'jt.id')
                ->select([
                    'jt.nama_tindakan',
                    DB::raw('COUNT(t.id) as frequency'),
                    DB::raw('SUM(t.tarif) as total_revenue'),
                    DB::raw('AVG(t.tarif) as avg_tarif'),
                    DB::raw('COUNT(DISTINCT t.pasien_id) as unique_patients')
                ])
                ->where('t.input_by', $userId)
                ->whereDate('t.tanggal_tindakan', '>=', $weekStart)
                ->groupBy('jt.id', 'jt.nama_tindakan')
                ->orderBy('frequency', 'desc')
                ->orderBy('total_revenue', 'desc')
                ->limit(5)
                ->get()
                ->toArray();
            
            return array_map(function($proc) {
                return [
                    'name' => $proc->nama_tindakan,
                    'frequency' => (int)$proc->frequency,
                    'total_revenue' => (float)$proc->total_revenue,
                    'avg_tarif' => (float)$proc->avg_tarif,
                    'unique_patients' => (int)$proc->unique_patients,
                ];
            }, $procedures);
            
        } catch (Exception $e) {
            Log::error('Failed to get trending procedures', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get validation alerts
     */
    protected function getValidationAlerts(int $userId): array
    {
        try {
            $alerts = [];
            
            // Check pending validations
            $pendingCount = Tindakan::where('input_by', $userId)
                ->where('status_validasi', 'pending')
                ->where('created_at', '>=', Carbon::now()->subDays(3))
                ->count();
            
            if ($pendingCount > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Anda memiliki {$pendingCount} tindakan yang menunggu validasi",
                    'action_url' => '/petugas/tindakans?filter[status_validasi]=pending',
                    'priority' => 'medium'
                ];
            }
            
            // Check rejected items
            $rejectedCount = Tindakan::where('input_by', $userId)
                ->where('status_validasi', 'rejected')
                ->whereDate('updated_at', Carbon::today())
                ->count();
            
            if ($rejectedCount > 0) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => "{$rejectedCount} tindakan ditolak hari ini. Perlu tindak lanjut.",
                    'action_url' => '/petugas/tindakans?filter[status_validasi]=rejected',
                    'priority' => 'high'
                ];
            }
            
            return $alerts;
            
        } catch (Exception $e) {
            Log::error('Failed to get validation alerts', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get overall efficiency score
     */
    protected function getEfficiencyScore(int $userId): array
    {
        try {
            $today = Carbon::today();
            $todayStats = $this->getStatsForDate($userId, $today);
            
            $efficiency = $todayStats['efficiency'] ?? ['overall' => 0];
            
            // Get efficiency trend for last 7 days
            $weeklyEfficiency = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $dayStats = $this->getStatsForDate($userId, $date);
                $weeklyEfficiency[] = $dayStats['efficiency']['overall'] ?? 0;
            }
            
            $avgWeeklyEfficiency = array_sum($weeklyEfficiency) / count($weeklyEfficiency);
            
            return [
                'current' => $efficiency['overall'],
                'weekly_average' => round($avgWeeklyEfficiency, 2),
                'trend' => $weeklyEfficiency,
                'breakdown' => $efficiency,
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get efficiency score', ['error' => $e->getMessage()]);
            return ['current' => 0, 'weekly_average' => 0, 'trend' => [], 'breakdown' => []];
        }
    }
    
    /**
     * Get revenue forecast based on trends
     */
    protected function getRevenueForecast(int $userId): array
    {
        try {
            // Get last 30 days revenue data
            $revenueData = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $dayStats = $this->getStatsForDate($userId, $date);
                $revenueData[] = $dayStats['net_income'] ?? 0;
            }
            
            // Simple linear regression for next 7 days forecast
            $forecast = $this->calculateRevenueForecast($revenueData);
            
            return [
                'next_7_days' => $forecast,
                'projected_monthly' => array_sum($forecast) * 4.3, // Approximate monthly
                'confidence' => $this->calculateForecastConfidence($revenueData),
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get revenue forecast', ['error' => $e->getMessage()]);
            return ['next_7_days' => [], 'projected_monthly' => 0, 'confidence' => 0];
        }
    }
    
    /**
     * Calculate simple revenue forecast using linear trend
     */
    protected function calculateRevenueForecast(array $data): array
    {
        $n = count($data);
        if ($n < 7) return array_fill(0, 7, 0);
        
        // Calculate trend (simple moving average slope)
        $recent = array_slice($data, -7);
        $older = array_slice($data, -14, 7);
        
        $recentAvg = array_sum($recent) / 7;
        $olderAvg = array_sum($older) / 7;
        
        $trend = ($recentAvg - $olderAvg) / 7; // Daily trend
        
        $forecast = [];
        $lastValue = end($data);
        
        for ($i = 1; $i <= 7; $i++) {
            $forecast[] = max(0, $lastValue + ($trend * $i));
        }
        
        return $forecast;
    }
    
    /**
     * Calculate forecast confidence based on data stability
     */
    protected function calculateForecastConfidence(array $data): int
    {
        if (count($data) < 7) return 0;
        
        $recent = array_slice($data, -7);
        $mean = array_sum($recent) / count($recent);
        
        // Calculate coefficient of variation
        $variance = 0;
        foreach ($recent as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance /= count($recent);
        $stdDev = sqrt($variance);
        
        $cv = $mean > 0 ? ($stdDev / $mean) : 1;
        
        // Convert to confidence percentage (lower variation = higher confidence)
        $confidence = max(0, min(100, (1 - $cv) * 100));
        
        return (int)round($confidence);
    }
    
    /**
     * Clear cache for specific user
     */
    public function clearStatsCache(int $userId): void
    {
        $cacheKeys = [
            "petugas_stats_{$userId}",
            "petugas_realtime_insights_{$userId}",
            "petugas_trend_analysis_{$userId}"
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
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
                    'daily_patients' => [0],
                    'daily_treatments' => [0],
                    'monthly_trend' => [0],
                ],
            ],
            'validation_summary' => [
                'pending_validations' => 0,
                'approved_today' => 0,
                'rejected_today' => 0,
                'total_today' => 0,
                'approval_rate' => 0,
            ],
            'performance_metrics' => [
                'monthly_target' => 0,
                'current_achievement' => 0,
                'completion_rate' => 0,
                'days_remaining' => 0,
                'daily_average_needed' => 0,
            ],
        ];
    }
    
    /**
     * Get empty day stats structure
     */
    protected function getEmptyDayStats(): array
    {
        return [
            'pasien_count' => 0,
            'pendapatan_sum' => 0,
            'pengeluaran_sum' => 0,
            'tindakan_count' => 0,
            'tindakan_sum' => 0,
            'net_income' => 0,
        ];
    }
    
    /**
     * Get empty trends structure
     */
    protected function getEmptyTrends(): array
    {
        $metrics = ['pasien_count', 'pendapatan_sum', 'pengeluaran_sum', 'tindakan_count', 'net_income'];
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