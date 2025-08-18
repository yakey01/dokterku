<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\JumlahPasienHarian;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Admin Dashboard Service
 * 
 * Handles dashboard data aggregation, caching, and analytics
 * with optimized queries and performance monitoring.
 */
class AdminDashboardService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * Get comprehensive dashboard statistics
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('admin.dashboard.stats', self::CACHE_TTL, function () {
            return [
                'user_stats' => $this->getUserStats(),
                'financial_stats' => $this->getFinancialStats(),
                'medical_stats' => $this->getMedicalStats(),
                'system_stats' => $this->getSystemStats(),
                'recent_activities' => $this->getRecentActivities(),
                'performance_metrics' => $this->getPerformanceMetrics()
            ];
        });
    }

    /**
     * Get user-related statistics
     *
     * @return array
     */
    public function getUserStats(): array
    {
        return Cache::remember('admin.dashboard.user_stats', self::CACHE_TTL, function () {
            $totalUsers = User::count();
            $activeUsers = User::whereNotNull('email_verified_at')->count();
            $recentUsers = User::where('created_at', '>=', now()->subDays(7))->count();

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $totalUsers - $activeUsers,
                'recent_users' => $recentUsers,
                'growth_rate' => $this->calculateGrowthRate('users'),
                'users_by_role' => $this->getUsersByRole(),
                'user_registration_trend' => $this->getUserRegistrationTrend()
            ];
        });
    }

    /**
     * Get financial statistics with trends
     *
     * @return array
     */
    public function getFinancialStats(): array
    {
        return Cache::remember('admin.dashboard.financial_stats', self::CACHE_TTL, function () {
            $totalIncome = Pendapatan::sum('jumlah') ?? 0;
            $totalExpenses = Pengeluaran::sum('jumlah') ?? 0;
            $pendingApprovals = $this->getPendingApprovals();

            $monthlyIncome = PendapatanHarian::whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah') ?? 0;

            $monthlyExpenses = PengeluaranHarian::whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah') ?? 0;

            return [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_profit' => $totalIncome - $totalExpenses,
                'monthly_income' => $monthlyIncome,
                'monthly_expenses' => $monthlyExpenses,
                'monthly_profit' => $monthlyIncome - $monthlyExpenses,
                'pending_approvals' => $pendingApprovals,
                'income_trend' => $this->getIncomeTrend(),
                'expense_trend' => $this->getExpenseTrend(),
                'profitability_ratio' => $totalExpenses > 0 ? round(($totalIncome / $totalExpenses) * 100, 2) : 0
            ];
        });
    }

    /**
     * Get medical/clinical statistics
     *
     * @return array
     */
    public function getMedicalStats(): array
    {
        return Cache::remember('admin.dashboard.medical_stats', self::CACHE_TTL, function () {
            $totalPatients = Pasien::count();
            $totalProcedures = Tindakan::count();
            $recentPatients = Pasien::where('created_at', '>=', now()->subDays(7))->count();
            $recentProcedures = Tindakan::where('created_at', '>=', now()->subDays(7))->count();

            $todayPatients = JumlahPasienHarian::whereDate('tanggal', today())->sum('jumlah_pasien') ?? 0;
            $monthlyPatients = JumlahPasienHarian::whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah_pasien') ?? 0;

            return [
                'total_patients' => $totalPatients,
                'total_procedures' => $totalProcedures,
                'recent_patients' => $recentPatients,
                'recent_procedures' => $recentProcedures,
                'today_patients' => $todayPatients,
                'monthly_patients' => $monthlyPatients,
                'patient_growth_rate' => $this->calculateGrowthRate('patients'),
                'procedure_growth_rate' => $this->calculateGrowthRate('procedures'),
                'popular_procedures' => $this->getPopularProcedures(),
                'patient_trend' => $this->getPatientTrend()
            ];
        });
    }

    /**
     * Get system health and performance statistics
     *
     * @return array
     */
    public function getSystemStats(): array
    {
        return Cache::remember('admin.dashboard.system_stats', self::CACHE_TTL, function () {
            return [
                'database_size' => $this->getDatabaseSize(),
                'cache_hit_rate' => $this->getCacheHitRate(),
                'average_response_time' => $this->getAverageResponseTime(),
                'error_rate' => $this->getErrorRate(),
                'uptime' => $this->getSystemUptime(),
                'disk_usage' => $this->getDiskUsage(),
                'memory_usage' => $this->getMemoryUsage()
            ];
        });
    }

    /**
     * Get recent activities across the system
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentActivities(int $limit = 10): Collection
    {
        return Cache::remember("admin.dashboard.recent_activities.{$limit}", self::CACHE_TTL, function () use ($limit) {
            $activities = collect();

            // Recent users
            $recentUsers = User::with('role')
                ->latest()
                ->take($limit / 2)
                ->get()
                ->map(function ($user) {
                    return [
                        'type' => 'user_created',
                        'description' => "User {$user->name} was created",
                        'timestamp' => $user->created_at,
                        'icon' => 'user-plus',
                        'color' => 'success'
                    ];
                });

            // Recent procedures
            $recentProcedures = Tindakan::with(['pasien', 'jenisTindakan'])
                ->latest()
                ->take($limit / 2)
                ->get()
                ->map(function ($tindakan) {
                    return [
                        'type' => 'procedure_created',
                        'description' => "Procedure " . ($tindakan->jenisTindakan->nama ?? 'Unknown') . " for " . ($tindakan->pasien->nama ?? 'Unknown Patient'),
                        'timestamp' => $tindakan->created_at,
                        'icon' => 'medical-cross',
                        'color' => 'info'
                    ];
                });

            return $activities->merge($recentUsers)
                ->merge($recentProcedures)
                ->sortByDesc('timestamp')
                ->take($limit)
                ->values();
        });
    }

    /**
     * Get performance metrics for monitoring
     *
     * @return array
     */
    public function getPerformanceMetrics(): array
    {
        return Cache::remember('admin.dashboard.performance_metrics', self::CACHE_TTL, function () {
            return [
                'queries_per_second' => $this->getQueriesPerSecond(),
                'average_query_time' => $this->getAverageQueryTime(),
                'slow_queries_count' => $this->getSlowQueriesCount(),
                'cache_efficiency' => $this->getCacheEfficiency(),
                'api_response_times' => $this->getApiResponseTimes()
            ];
        });
    }

    /**
     * Get users grouped by role
     *
     * @return array
     */
    private function getUsersByRole(): array
    {
        return User::select('role_id')
            ->selectRaw('count(*) as count')
            ->with('role:id,name')
            ->groupBy('role_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->role->name ?? 'Unknown' => $item->count];
            })
            ->toArray();
    }

    /**
     * Calculate growth rate for various metrics
     *
     * @param string $metric
     * @return float
     */
    private function calculateGrowthRate(string $metric): float
    {
        $currentMonth = now()->month;
        $previousMonth = now()->subMonth()->month;
        $year = now()->year;

        switch ($metric) {
            case 'users':
                $current = User::whereMonth('created_at', $currentMonth)->whereYear('created_at', $year)->count();
                $previous = User::whereMonth('created_at', $previousMonth)->whereYear('created_at', $year)->count();
                break;
            case 'patients':
                $current = Pasien::whereMonth('created_at', $currentMonth)->whereYear('created_at', $year)->count();
                $previous = Pasien::whereMonth('created_at', $previousMonth)->whereYear('created_at', $year)->count();
                break;
            case 'procedures':
                $current = Tindakan::whereMonth('created_at', $currentMonth)->whereYear('created_at', $year)->count();
                $previous = Tindakan::whereMonth('created_at', $previousMonth)->whereYear('created_at', $year)->count();
                break;
            default:
                return 0;
        }

        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get pending financial approvals
     *
     * @return int
     */
    private function getPendingApprovals(): int
    {
        $pendingIncome = Pendapatan::where('status', 'pending')->count();
        $pendingExpenses = Pengeluaran::where('status', 'pending')->count();
        
        return $pendingIncome + $pendingExpenses;
    }

    /**
     * Get income trend for the last 12 months
     *
     * @return array
     */
    private function getIncomeTrend(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $amount = PendapatanHarian::whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->sum('jumlah') ?? 0;
            
            $months[] = [
                'month' => $date->format('M Y'),
                'amount' => $amount
            ];
        }
        
        return $months;
    }

    /**
     * Get expense trend for the last 12 months
     *
     * @return array
     */
    private function getExpenseTrend(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $amount = PengeluaranHarian::whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->sum('jumlah') ?? 0;
            
            $months[] = [
                'month' => $date->format('M Y'),
                'amount' => $amount
            ];
        }
        
        return $months;
    }

    /**
     * Get user registration trend for the last 12 months
     *
     * @return array
     */
    private function getUserRegistrationTrend(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = User::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $months[] = [
                'month' => $date->format('M Y'),
                'count' => $count
            ];
        }
        
        return $months;
    }

    /**
     * Get patient visit trend for the last 30 days
     *
     * @return array
     */
    private function getPatientTrend(): array
    {
        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = JumlahPasienHarian::whereDate('tanggal', $date->toDateString())
                ->sum('jumlah_pasien') ?? 0;
            
            $days[] = [
                'date' => $date->format('M d'),
                'count' => $count
            ];
        }
        
        return $days;
    }

    /**
     * Get most popular procedures
     *
     * @param int $limit
     * @return array
     */
    private function getPopularProcedures(int $limit = 5): array
    {
        return Tindakan::select('jenis_tindakan_id')
            ->selectRaw('count(*) as count')
            ->with('jenisTindakan:id,nama')
            ->groupBy('jenis_tindakan_id')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->jenisTindakan->nama ?? 'Unknown',
                    'count' => $item->count
                ];
            })
            ->toArray();
    }

    // System monitoring methods (mock implementations)
    private function getDatabaseSize(): string
    {
        return '2.4 GB';
    }

    private function getCacheHitRate(): float
    {
        return 94.5;
    }

    private function getAverageResponseTime(): int
    {
        return 250; // milliseconds
    }

    private function getErrorRate(): float
    {
        return 0.02;
    }

    private function getSystemUptime(): string
    {
        return '99.8%';
    }

    private function getDiskUsage(): array
    {
        return ['used' => 65, 'total' => 100, 'unit' => 'GB'];
    }

    private function getMemoryUsage(): array
    {
        return ['used' => 4.2, 'total' => 8, 'unit' => 'GB'];
    }

    private function getQueriesPerSecond(): int
    {
        return 45;
    }

    private function getAverageQueryTime(): float
    {
        return 12.5; // milliseconds
    }

    private function getSlowQueriesCount(): int
    {
        return 3;
    }

    private function getCacheEfficiency(): float
    {
        return 96.2;
    }

    private function getApiResponseTimes(): array
    {
        return [
            'average' => 180,
            'p95' => 350,
            'p99' => 500
        ];
    }

    /**
     * Clear all dashboard caches
     *
     * @return void
     */
    public function clearCache(): void
    {
        $cacheKeys = [
            'admin.dashboard.stats',
            'admin.dashboard.user_stats',
            'admin.dashboard.financial_stats',
            'admin.dashboard.medical_stats',
            'admin.dashboard.system_stats',
            'admin.dashboard.performance_metrics'
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear recent activities with different limits
        for ($i = 5; $i <= 20; $i += 5) {
            Cache::forget("admin.dashboard.recent_activities.{$i}");
        }
    }

    /**
     * Get real-time dashboard data (no caching)
     *
     * @return array
     */
    public function getRealtimeDashboardStats(): array
    {
        // Temporarily clear cache to get fresh data
        $this->clearCache();
        
        return $this->getDashboardStats();
    }
}