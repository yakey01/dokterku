<?php

namespace App\Services\Manajer;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\JumlahPasienHarian;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Jaspel;
use App\Models\ManagerApproval;
use App\Models\StrategicGoal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ManajerDashboardService
{
    /**
     * Get comprehensive today statistics for dashboard
     */
    public function getTodayStats(): array
    {
        return Cache::remember('manajer.today_stats', 300, function () { // 5 minutes cache
            try {
                $today = Carbon::today();

                // Revenue - validated only
                $todayRevenue = Pendapatan::whereDate('tanggal', $today)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0;

                // Expenses - validated only
                $todayExpenses = Pengeluaran::whereDate('tanggal', $today)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0;

                // Patient counts - approved only
                $patientData = JumlahPasienHarian::whereDate('tanggal', $today)
                    ->where('status_validasi', 'approved')
                    ->selectRaw('
                        COALESCE(SUM(pasien_umum), 0) as total_umum,
                        COALESCE(SUM(pasien_bpjs), 0) as total_bpjs
                    ')
                    ->first();

                // Doctors on duty today
                $doctorsOnDuty = User::whereHas('roles', function ($query) {
                        $query->where('name', 'dokter');
                    })
                    ->whereHas('attendances', function ($query) use ($today) {
                        $query->whereDate('date', $today)
                            ->whereNotNull('check_in_time');
                    })
                    ->count();

                // Average doctor fee calculation
                $avgDoctorFee = Jaspel::whereHas('user.roles', function ($query) {
                        $query->where('name', 'dokter');
                    })
                    ->whereMonth('created_at', $today->month)
                    ->whereYear('created_at', $today->year)
                    ->where('status_validasi', 'disetujui')
                    ->avg('nominal') ?? 0;

                return [
                    'success' => true,
                    'data' => [
                        'revenue' => (int) $todayRevenue,
                        'expenses' => (int) $todayExpenses,
                        'profit' => (int) ($todayRevenue - $todayExpenses),
                        'generalPatients' => (int) ($patientData->total_umum ?? 0),
                        'bpjsPatients' => (int) ($patientData->total_bpjs ?? 0),
                        'totalPatients' => (int) (($patientData->total_umum ?? 0) + ($patientData->total_bpjs ?? 0)),
                        'avgDoctorFee' => (int) $avgDoctorFee,
                        'doctorsOnDuty' => $doctorsOnDuty,
                        'date' => $today->toDateString(),
                        'formatted_date' => $today->format('d M Y'),
                        'revenue_growth' => $this->calculateGrowthPercentage('revenue', $todayRevenue),
                        'expense_growth' => $this->calculateGrowthPercentage('expenses', $todayExpenses)
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('ManajerDashboardService::getTodayStats failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal mengambil statistik hari ini',
                    'data' => [
                        'revenue' => 0,
                        'expenses' => 0,
                        'profit' => 0,
                        'generalPatients' => 0,
                        'bpjsPatients' => 0,
                        'totalPatients' => 0,
                        'avgDoctorFee' => 0,
                        'doctorsOnDuty' => 0
                    ]
                ];
            }
        });
    }

    /**
     * Get recent financial transactions for validation
     */
    public function getRecentTransactions(int $limit = 10): array
    {
        return Cache::remember("manajer.recent_transactions.{$limit}", 300, function () use ($limit) {
            try {
                // Recent revenue entries (pending validation)
                $recentRevenue = Pendapatan::with(['user:id,name'])
                    ->where('status_validasi', 'menunggu')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit / 2)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'type' => 'Pendapatan',
                            'category' => $item->kategori ?? 'Umum',
                            'amount' => (int) $item->nominal,
                            'staff' => $item->user->name ?? 'Unknown',
                            'date' => $item->tanggal,
                            'time' => $item->created_at->format('H:i'),
                            'status' => 'pending',
                            'description' => $item->keterangan ?? '-'
                        ];
                    });

                // Recent expense entries (pending validation)
                $recentExpenses = Pengeluaran::with(['user:id,name'])
                    ->where('status_validasi', 'menunggu')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit / 2)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'type' => 'Pengeluaran',
                            'category' => $item->kategori ?? 'Umum',
                            'amount' => -(int) $item->nominal, // Negative for expenses
                            'staff' => $item->user->name ?? 'Unknown',
                            'date' => $item->tanggal,
                            'time' => $item->created_at->format('H:i'),
                            'status' => 'pending',
                            'description' => $item->keterangan ?? '-'
                        ];
                    });

                // Merge and sort by creation time
                $allTransactions = $recentRevenue->merge($recentExpenses)
                    ->sortByDesc('time')
                    ->take($limit)
                    ->values()
                    ->toArray();

                return [
                    'success' => true,
                    'data' => $allTransactions,
                    'count' => count($allTransactions)
                ];

            } catch (\Exception $e) {
                Log::error('ManajerDashboardService::getRecentTransactions failed', [
                    'error' => $e->getMessage(),
                    'limit' => $limit
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal mengambil transaksi terbaru',
                    'data' => []
                ];
            }
        });
    }

    /**
     * Get pending approvals requiring manager attention
     */
    public function getPendingApprovals(): array
    {
        return Cache::remember('manajer.pending_approvals', 60, function () { // 1 minute cache - more frequent updates
            try {
                $pendingApprovals = [];

                // Manager Approval records
                $managerApprovals = ManagerApproval::where('status', 'pending')
                    ->with(['user:id,name'])
                    ->orderBy('priority', 'desc')
                    ->orderBy('created_at', 'asc')
                    ->limit(10)
                    ->get();

                foreach ($managerApprovals as $approval) {
                    $pendingApprovals[] = [
                        'id' => $approval->id,
                        'type' => $approval->type,
                        'title' => $approval->title,
                        'description' => $approval->description,
                        'priority' => $approval->priority,
                        'priority_label' => $this->getPriorityLabel($approval->priority),
                        'amount' => $approval->amount ? (int) $approval->amount : null,
                        'requested_by' => $approval->user->name ?? 'System',
                        'created_at' => $approval->created_at->format('Y-m-d H:i:s'),
                        'waiting_hours' => $approval->created_at->diffInHours(now()),
                        'urgency_level' => $this->calculateUrgencyLevel($approval)
                    ];
                }

                // High-value financial transactions awaiting approval
                $highValueTransactions = Pendapatan::where('status_validasi', 'menunggu')
                    ->where('nominal', '>', 5000000) // > 5M IDR
                    ->with(['user:id,name'])
                    ->orderBy('nominal', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($highValueTransactions as $transaction) {
                    $pendingApprovals[] = [
                        'id' => "revenue_{$transaction->id}",
                        'type' => 'high_value_revenue',
                        'title' => "Pendapatan Tinggi: Rp " . number_format($transaction->nominal, 0, ',', '.'),
                        'description' => $transaction->keterangan ?? 'Transaksi pendapatan dengan nominal tinggi',
                        'priority' => 3,
                        'priority_label' => 'High',
                        'amount' => (int) $transaction->nominal,
                        'requested_by' => $transaction->user->name ?? 'Unknown',
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                        'waiting_hours' => $transaction->created_at->diffInHours(now()),
                        'urgency_level' => 'high'
                    ];
                }

                // Sort by priority and urgency
                usort($pendingApprovals, function ($a, $b) {
                    if ($a['priority'] === $b['priority']) {
                        return $b['waiting_hours'] <=> $a['waiting_hours']; // Longer waiting = higher priority
                    }
                    return $b['priority'] <=> $a['priority']; // Higher priority first
                });

                return [
                    'success' => true,
                    'data' => array_slice($pendingApprovals, 0, 15), // Max 15 items
                    'count' => count($pendingApprovals),
                    'high_priority_count' => collect($pendingApprovals)->where('priority', '>=', 3)->count(),
                    'overdue_count' => collect($pendingApprovals)->where('waiting_hours', '>', 24)->count()
                ];

            } catch (\Exception $e) {
                Log::error('ManajerDashboardService::getPendingApprovals failed', [
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal mengambil daftar persetujuan pending',
                    'data' => []
                ];
            }
        });
    }

    /**
     * Get monthly financial overview
     */
    public function getMonthlyFinanceOverview(): array
    {
        return Cache::remember('manajer.monthly_finance', 600, function () { // 10 minutes cache
            try {
                $currentMonth = Carbon::now();
                $lastMonth = Carbon::now()->subMonth();

                // Current month data
                $currentRevenue = Pendapatan::whereMonth('tanggal', $currentMonth->month)
                    ->whereYear('tanggal', $currentMonth->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0;

                $currentExpenses = Pengeluaran::whereMonth('tanggal', $currentMonth->month)
                    ->whereYear('tanggal', $currentMonth->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0;

                // Last month data for comparison
                $lastRevenue = Pendapatan::whereMonth('tanggal', $lastMonth->month)
                    ->whereYear('tanggal', $lastMonth->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0;

                $lastExpenses = Pengeluaran::whereMonth('tanggal', $lastMonth->month)
                    ->whereYear('tanggal', $lastMonth->year)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0;

                // Calculate profit and margins
                $currentProfit = $currentRevenue - $currentExpenses;
                $lastProfit = $lastRevenue - $lastExpenses;
                $profitMargin = $currentRevenue > 0 ? ($currentProfit / $currentRevenue) * 100 : 0;

                return [
                    'success' => true,
                    'data' => [
                        'current_month' => [
                            'revenue' => (int) $currentRevenue,
                            'expenses' => (int) $currentExpenses,
                            'profit' => (int) $currentProfit,
                            'profit_margin' => round($profitMargin, 2),
                            'month' => $currentMonth->format('M Y')
                        ],
                        'last_month' => [
                            'revenue' => (int) $lastRevenue,
                            'expenses' => (int) $lastExpenses,
                            'profit' => (int) $lastProfit,
                            'month' => $lastMonth->format('M Y')
                        ],
                        'growth' => [
                            'revenue' => $this->calculatePercentageGrowth($lastRevenue, $currentRevenue),
                            'expenses' => $this->calculatePercentageGrowth($lastExpenses, $currentExpenses),
                            'profit' => $this->calculatePercentageGrowth($lastProfit, $currentProfit)
                        ],
                        'targets' => [
                            'revenue_target' => $this->getRevenueTarget($currentMonth),
                            'expense_budget' => $this->getExpenseBudget($currentMonth),
                            'revenue_achievement' => $this->calculateTargetAchievement($currentRevenue, $currentMonth, 'revenue'),
                            'expense_efficiency' => $this->calculateBudgetEfficiency($currentExpenses, $currentMonth)
                        ]
                    ]
                ];

            } catch (\Exception $e) {
                Log::error('ManajerDashboardService::getMonthlyFinanceOverview failed', [
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal mengambil overview keuangan bulanan',
                    'data' => []
                ];
            }
        });
    }

    /**
     * Calculate growth percentage compared to previous period
     */
    private function calculateGrowthPercentage(string $type, float $currentValue): float
    {
        try {
            $yesterday = Carbon::yesterday();
            
            if ($type === 'revenue') {
                $previousValue = Pendapatan::whereDate('tanggal', $yesterday)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0;
            } else {
                $previousValue = Pengeluaran::whereDate('tanggal', $yesterday)
                    ->where('status_validasi', 'disetujui')
                    ->sum('nominal') ?? 0;
            }

            return $this->calculatePercentageGrowth($previousValue, $currentValue);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate percentage growth between two values
     */
    private function calculatePercentageGrowth(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }

    /**
     * Get priority label for approval items
     */
    private function getPriorityLabel(int $priority): string
    {
        return match ($priority) {
            1 => 'Low',
            2 => 'Normal',
            3 => 'High',
            4 => 'Urgent',
            5 => 'Critical',
            default => 'Normal'
        };
    }

    /**
     * Calculate urgency level based on approval characteristics
     */
    private function calculateUrgencyLevel($approval): string
    {
        $waitingHours = $approval->created_at->diffInHours(now());
        
        if ($approval->priority >= 4 || $waitingHours > 48) {
            return 'critical';
        } elseif ($approval->priority >= 3 || $waitingHours > 24) {
            return 'high';
        } elseif ($waitingHours > 8) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get revenue target for given month
     */
    private function getRevenueTarget(Carbon $month): int
    {
        // Try to get from strategic goals
        $goal = StrategicGoal::where('category', 'financial')
            ->where('type', 'revenue_target')
            ->whereYear('target_date', $month->year)
            ->whereMonth('target_date', $month->month)
            ->first();

        return $goal ? (int) $goal->target_value : 100000000; // Default 100M IDR
    }

    /**
     * Get expense budget for given month
     */
    private function getExpenseBudget(Carbon $month): int
    {
        // Try to get from strategic goals
        $goal = StrategicGoal::where('category', 'financial')
            ->where('type', 'expense_budget')
            ->whereYear('target_date', $month->year)
            ->whereMonth('target_date', $month->month)
            ->first();

        return $goal ? (int) $goal->target_value : 35000000; // Default 35M IDR
    }

    /**
     * Calculate target achievement percentage
     */
    private function calculateTargetAchievement(float $actual, Carbon $month, string $type): float
    {
        $target = $type === 'revenue' ? $this->getRevenueTarget($month) : $this->getExpenseBudget($month);
        return $target > 0 ? round(($actual / $target) * 100, 2) : 0;
    }

    /**
     * Calculate budget efficiency percentage
     */
    private function calculateBudgetEfficiency(float $actualExpense, Carbon $month): float
    {
        $budget = $this->getExpenseBudget($month);
        if ($budget <= 0) return 0;
        
        $efficiency = (($budget - $actualExpense) / $budget) * 100;
        return round($efficiency, 2);
    }
}