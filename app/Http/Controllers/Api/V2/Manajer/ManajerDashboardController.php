<?php

namespace App\Http\Controllers\Api\V2\Manajer;

use App\Http\Controllers\Controller;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\User;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Models\Attendance;
use App\Models\DokterPresensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManajerDashboardController extends Controller
{
    /**
     * Get comprehensive dashboard data for manager
     */
    public function getDashboardData(Request $request)
    {
        try {
            $currentMonth = now();
            $lastMonth = now()->subMonth();
            
            // Financial KPIs
            $currentRevenue = PendapatanHarian::whereMonth('tanggal_input', $currentMonth->month)
                ->whereYear('tanggal_input', $currentMonth->year)
                ->where('validation_status', 'approved')
                ->sum('nominal');
                
            $currentExpenses = PengeluaranHarian::whereMonth('tanggal_input', $currentMonth->month)
                ->whereYear('tanggal_input', $currentMonth->year)
                ->where('validation_status', 'approved')
                ->sum('nominal');
                
            $netProfit = $currentRevenue - $currentExpenses;
            $profitMargin = $currentRevenue > 0 ? ($netProfit / $currentRevenue) * 100 : 0;
            
            // Patient statistics
            $totalPatients = JumlahPasienHarian::whereMonth('tanggal', $currentMonth->month)
                ->whereYear('tanggal', $currentMonth->year)
                ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
                
            // Staff statistics
            $totalStaff = Pegawai::count();
            $totalDoctors = Dokter::count();
            $activeStaff = Attendance::whereDate('date', today())
                ->where('check_in', '!=', null)
                ->distinct('user_id')
                ->count();
            
            // Procedure statistics
            $totalProcedures = Tindakan::whereMonth('tanggal_tindakan', $currentMonth->month)
                ->whereYear('tanggal_tindakan', $currentMonth->year)
                ->count();
            
            // Top procedures
            $topProcedures = Tindakan::select('jenis_tindakan_id', DB::raw('COUNT(*) as total'))
                ->with('jenisTindakan')
                ->whereMonth('tanggal_tindakan', $currentMonth->month)
                ->whereYear('tanggal_tindakan', $currentMonth->year)
                ->groupBy('jenis_tindakan_id')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->jenisTindakan->nama ?? 'Unknown',
                        'count' => $item->total,
                        'percentage' => 0 // Will calculate later
                    ];
                });
            
            // Calculate percentages for top procedures
            if ($totalProcedures > 0) {
                $topProcedures = $topProcedures->map(function ($proc) use ($totalProcedures) {
                    $proc['percentage'] = round(($proc['count'] / $totalProcedures) * 100, 1);
                    return $proc;
                });
            }
            
            // Financial trends (last 6 months)
            $financialTrends = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthRevenue = PendapatanHarian::whereMonth('tanggal_input', $date->month)
                    ->whereYear('tanggal_input', $date->year)
                    ->where('validation_status', 'approved')
                    ->sum('nominal');
                    
                $monthExpenses = PengeluaranHarian::whereMonth('tanggal_input', $date->month)
                    ->whereYear('tanggal_input', $date->year)
                    ->where('validation_status', 'approved')
                    ->sum('nominal');
                    
                $financialTrends[] = [
                    'month' => $date->format('M Y'),
                    'revenue' => (float) $monthRevenue,
                    'expenses' => (float) $monthExpenses,
                    'profit' => (float) ($monthRevenue - $monthExpenses)
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'kpi' => [
                        'revenue' => $currentRevenue,
                        'expenses' => $currentExpenses,
                        'net_profit' => $netProfit,
                        'profit_margin' => round($profitMargin, 2),
                        'total_patients' => $totalPatients,
                        'total_staff' => $totalStaff,
                        'active_staff' => $activeStaff,
                        'total_doctors' => $totalDoctors,
                        'total_procedures' => $totalProcedures,
                    ],
                    'financial_trends' => $financialTrends,
                    'top_procedures' => $topProcedures,
                    'quick_stats' => [
                        'pending_approvals' => $this->getPendingApprovals(),
                        'today_revenue' => $this->getTodayRevenue(),
                        'staff_attendance_rate' => $this->getAttendanceRate(),
                        'bed_occupancy' => $this->getBedOccupancy(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get financial management data
     */
    public function getFinanceData(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            
            // Income details
            $incomeByCategory = PendapatanHarian::select('kategori', DB::raw('SUM(nominal) as total'))
                ->whereMonth('tanggal_input', $month)
                ->whereYear('tanggal_input', $year)
                ->where('validation_status', 'approved')
                ->groupBy('kategori')
                ->get();
            
            // Expense details
            $expenseByCategory = PengeluaranHarian::select('kategori', DB::raw('SUM(nominal) as total'))
                ->whereMonth('tanggal_input', $month)
                ->whereYear('tanggal_input', $year)
                ->where('validation_status', 'approved')
                ->groupBy('kategori')
                ->get();
            
            // Daily cash flow
            $dailyCashFlow = [];
            $daysInMonth = Carbon::create($year, $month)->daysInMonth;
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::create($year, $month, $day);
                
                $dayIncome = PendapatanHarian::whereDate('tanggal_input', $date)
                    ->where('validation_status', 'approved')
                    ->sum('nominal');
                    
                $dayExpense = PengeluaranHarian::whereDate('tanggal_input', $date)
                    ->where('validation_status', 'approved')
                    ->sum('nominal');
                    
                $dailyCashFlow[] = [
                    'date' => $date->format('d'),
                    'income' => (float) $dayIncome,
                    'expense' => (float) $dayExpense,
                    'net' => (float) ($dayIncome - $dayExpense)
                ];
            }
            
            // Pending validations
            $pendingIncome = PendapatanHarian::where('validation_status', 'pending')->count();
            $pendingExpense = PengeluaranHarian::where('validation_status', 'pending')->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Finance data retrieved successfully',
                'data' => [
                    'income_by_category' => $incomeByCategory,
                    'expense_by_category' => $expenseByCategory,
                    'daily_cash_flow' => $dailyCashFlow,
                    'pending_validations' => [
                        'income' => $pendingIncome,
                        'expense' => $pendingExpense,
                        'total' => $pendingIncome + $pendingExpense
                    ],
                    'summary' => [
                        'total_income' => $incomeByCategory->sum('total'),
                        'total_expense' => $expenseByCategory->sum('total'),
                        'net_profit' => $incomeByCategory->sum('total') - $expenseByCategory->sum('total')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve finance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get attendance data
     */
    public function getAttendanceData(Request $request)
    {
        try {
            $date = $request->input('date', today());
            
            // Today's attendance
            $presentToday = Attendance::whereDate('date', $date)
                ->where('check_in', '!=', null)
                ->with('user')
                ->get()
                ->map(function ($attendance) {
                    return [
                        'id' => $attendance->id,
                        'user' => [
                            'id' => $attendance->user->id,
                            'name' => $attendance->user->name,
                            'role' => $attendance->user->role->name ?? 'Unknown',
                        ],
                        'check_in' => $attendance->check_in,
                        'check_out' => $attendance->check_out,
                        'status' => $attendance->check_out ? 'completed' : 'active',
                        'duration' => $this->calculateDuration($attendance->check_in, $attendance->check_out),
                    ];
                });
            
            // Attendance by department
            $attendanceByDepartment = User::select('role_id', DB::raw('COUNT(*) as total'))
                ->with('role')
                ->whereIn('id', function ($query) use ($date) {
                    $query->select('user_id')
                        ->from('attendances')
                        ->whereDate('date', $date)
                        ->where('check_in', '!=', null);
                })
                ->groupBy('role_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'department' => $item->role->name ?? 'Unknown',
                        'present' => $item->total,
                        'total' => User::where('role_id', $item->role_id)->count(),
                    ];
                });
            
            // Weekly attendance trend
            $weeklyTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $trendDate = now()->subDays($i);
                $presentCount = Attendance::whereDate('date', $trendDate)
                    ->where('check_in', '!=', null)
                    ->distinct('user_id')
                    ->count();
                    
                $weeklyTrend[] = [
                    'date' => $trendDate->format('D'),
                    'day' => $trendDate->format('d'),
                    'present' => $presentCount,
                ];
            }
            
            // Late arrivals
            $lateArrivals = Attendance::whereDate('date', $date)
                ->whereTime('check_in', '>', '08:00:00')
                ->with('user')
                ->get()
                ->map(function ($attendance) {
                    return [
                        'user' => $attendance->user->name,
                        'check_in' => $attendance->check_in,
                        'late_by' => Carbon::parse($attendance->check_in)->diffInMinutes(Carbon::parse($attendance->date . ' 08:00:00')),
                    ];
                });
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance data retrieved successfully',
                'data' => [
                    'present_today' => $presentToday,
                    'attendance_by_department' => $attendanceByDepartment,
                    'weekly_trend' => $weeklyTrend,
                    'late_arrivals' => $lateArrivals,
                    'summary' => [
                        'total_present' => $presentToday->count(),
                        'total_staff' => User::count(),
                        'attendance_rate' => round(($presentToday->count() / max(User::count(), 1)) * 100, 2),
                        'on_time' => $presentToday->count() - $lateArrivals->count(),
                        'late' => $lateArrivals->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get Jaspel (service fee) data
     */
    public function getJaspelData(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            
            // Top earners
            $topEarners = Jaspel::select('user_id', DB::raw('SUM(nominal) as total'))
                ->with('user')
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->groupBy('user_id')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->user_id,
                        'name' => $item->user->name ?? 'Unknown',
                        'role' => $item->user->roles->first()?->name ?? $item->user->role?->name ?? 'Unknown',
                        'total' => (float) $item->total,
                        'procedures' => Tindakan::where('user_id', $item->user_id)
                            ->whereMonth('tanggal_tindakan', now()->month)
                            ->whereYear('tanggal_tindakan', now()->year)
                            ->count(),
                    ];
                });
            
            // Jaspel by role
            $jaspelByRole = User::select('role_id', DB::raw('SUM(jaspel.nominal) as total'))
                ->join('jaspel', 'users.id', '=', 'jaspel.user_id')
                ->with('role')
                ->whereMonth('jaspel.tanggal', $month)
                ->whereYear('jaspel.tanggal', $year)
                ->whereNotNull('role_id')
                ->groupBy('role_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'role' => $item->role->name ?? 'Unknown',
                        'total' => (float) $item->total,
                        'percentage' => 0, // Will calculate later
                    ];
                });
            
            // Calculate percentages
            $totalJaspel = $jaspelByRole->sum('total');
            if ($totalJaspel > 0) {
                $jaspelByRole = $jaspelByRole->map(function ($item) use ($totalJaspel) {
                    $item['percentage'] = round(($item['total'] / $totalJaspel) * 100, 2);
                    return $item;
                });
            }
            
            // Monthly trend
            $monthlyTrend = [];
            for ($i = 5; $i >= 0; $i--) {
                $trendDate = now()->subMonths($i);
                $monthTotal = Jaspel::whereMonth('tanggal', $trendDate->month)
                    ->whereYear('tanggal', $trendDate->year)
                    ->sum('nominal');
                    
                $monthlyTrend[] = [
                    'month' => $trendDate->format('M'),
                    'total' => (float) $monthTotal,
                ];
            }
            
            // Daily distribution
            $dailyDistribution = [];
            $daysInMonth = Carbon::create($year, $month)->daysInMonth;
            
            for ($day = 1; $day <= min($daysInMonth, 7); $day++) {
                $date = Carbon::create($year, $month, $day);
                $dayTotal = Jaspel::whereDate('tanggal', $date)->sum('nominal');
                
                $dailyDistribution[] = [
                    'date' => $date->format('d'),
                    'total' => (float) $dayTotal,
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Jaspel data retrieved successfully',
                'data' => [
                    'top_earners' => $topEarners,
                    'by_role' => $jaspelByRole,
                    'monthly_trend' => $monthlyTrend,
                    'daily_distribution' => $dailyDistribution,
                    'summary' => [
                        'total_jaspel' => $totalJaspel,
                        'average_per_staff' => round($totalJaspel / max($topEarners->count(), 1), 2),
                        'total_procedures' => Tindakan::whereMonth('tanggal_tindakan', $month)
                            ->whereYear('tanggal_tindakan', $year)
                            ->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Jaspel data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get user profile data
     */
    public function getProfileData(Request $request)
    {
        try {
            $user = $request->user() ?? auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Get user details with role
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? $user->role?->name ?? 'Unknown',
                'phone' => $user->phone ?? '',
                'address' => $user->address ?? '',
                'joined_date' => $user->created_at->format('Y-m-d'),
                'avatar' => $user->avatar_url ?? null,
            ];
            
            // Get user's recent activities
            $recentActivities = [];
            
            // Recent logins (if you have a login log table)
            // For now, we'll show recent approvals as activities
            $recentApprovals = PendapatanHarian::where('validation_by', $user->id)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'approval',
                        'description' => 'Approved income entry: Rp ' . number_format($item->nominal, 0, ',', '.'),
                        'timestamp' => $item->updated_at->diffForHumans(),
                    ];
                });
            
            $recentActivities = array_merge($recentActivities, $recentApprovals->toArray());
            
            // Get user statistics
            $statistics = [
                'approvals_this_month' => PendapatanHarian::where('validation_by', $user->id)
                    ->whereMonth('updated_at', now()->month)
                    ->count() + 
                    PengeluaranHarian::where('validation_by', $user->id)
                    ->whereMonth('updated_at', now()->month)
                    ->count(),
                'total_approvals' => PendapatanHarian::where('validation_by', $user->id)->count() + 
                    PengeluaranHarian::where('validation_by', $user->id)->count(),
                'last_login' => $user->last_login_at ?? now(),
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Profile data retrieved successfully',
                'data' => [
                    'user' => $userData,
                    'recent_activities' => $recentActivities,
                    'statistics' => $statistics,
                    'preferences' => [
                        'theme' => 'light',
                        'language' => 'id',
                        'notifications' => true,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|max:20',
                'address' => 'sometimes|string|max:500',
            ]);
            
            $user->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $user
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Helper methods
    
    private function getPendingApprovals()
    {
        return PendapatanHarian::where('validation_status', 'pending')->count() +
               PengeluaranHarian::where('validation_status', 'pending')->count();
    }
    
    private function getTodayRevenue()
    {
        return PendapatanHarian::whereDate('tanggal_input', today())
            ->where('validation_status', 'approved')
            ->sum('nominal');
    }
    
    private function getAttendanceRate()
    {
        $present = Attendance::whereDate('date', today())
            ->where('check_in', '!=', null)
            ->distinct('user_id')
            ->count();
            
        $totalStaff = User::count();
        
        return $totalStaff > 0 ? round(($present / $totalStaff) * 100, 2) : 0;
    }
    
    private function getBedOccupancy()
    {
        // This is a placeholder - implement based on your bed management system
        return rand(60, 95); // Mock data
    }
    
    private function calculateDuration($checkIn, $checkOut)
    {
        if (!$checkIn || !$checkOut) {
            return null;
        }
        
        $start = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);
        
        $hours = $end->diffInHours($start);
        $minutes = $end->diffInMinutes($start) % 60;
        
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}