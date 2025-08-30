<?php

namespace App\Http\Controllers\Api\V2\Manajer;

use App\Http\Controllers\Controller;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\User;
use App\Models\Dokter;
use App\Models\Attendance;
use App\Models\ManagerApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ManajerDashboardController extends Controller
{
    public function __construct()
    {
        // Role-based authentication is handled by route middleware
        // 'role:manajer' middleware ensures only managers can access these endpoints
    }

    /**
     * Get today's key statistics for dashboard overview
     */
    public function todayStats(Request $request)
    {
        try {
            $cacheKey = 'manajer_today_stats_' . now()->format('Y-m-d');
            
            return Cache::remember($cacheKey, 900, function () {
                $today = now();
                
                // FIXED: SQLite-compatible query using date ranges instead of MONTH()/YEAR()
                $monthStart = $today->startOfMonth()->format('Y-m-d');
                $monthEnd = $today->endOfMonth()->format('Y-m-d');
                
                $financialData = DB::select("
                    SELECT 
                        COALESCE(today_revenue.nominal, latest_revenue.nominal, month_revenue.nominal, 0) as revenue,
                        COALESCE(today_expenses.nominal, latest_expenses.nominal, month_expenses.nominal, 0) as expenses
                    FROM (SELECT 1 as dummy) as base
                    LEFT JOIN (
                        SELECT SUM(nominal) as nominal 
                        FROM pendapatan_harians 
                        WHERE tanggal_input = ? AND status_validasi = 'approved'
                    ) as today_revenue ON 1=1
                    LEFT JOIN (
                        SELECT SUM(nominal) as nominal 
                        FROM pendapatan_harians 
                        WHERE status_validasi = 'approved' AND nominal > 0
                        AND tanggal_input = (
                            SELECT MAX(tanggal_input) 
                            FROM pendapatan_harians 
                            WHERE status_validasi = 'approved' AND nominal > 0
                        )
                    ) as latest_revenue ON today_revenue.nominal IS NULL OR today_revenue.nominal = 0
                    LEFT JOIN (
                        SELECT SUM(nominal) as nominal 
                        FROM pendapatan_harians 
                        WHERE tanggal_input BETWEEN ? AND ? 
                        AND status_validasi = 'approved'
                    ) as month_revenue ON (today_revenue.nominal IS NULL OR today_revenue.nominal = 0) 
                        AND (latest_revenue.nominal IS NULL OR latest_revenue.nominal = 0)
                    LEFT JOIN (
                        SELECT SUM(nominal) as nominal 
                        FROM pengeluaran_harians 
                        WHERE tanggal_input = ? AND status_validasi = 'approved'
                    ) as today_expenses ON 1=1
                    LEFT JOIN (
                        SELECT SUM(nominal) as nominal 
                        FROM pengeluaran_harians 
                        WHERE status_validasi = 'approved' AND nominal > 0
                        AND tanggal_input = (
                            SELECT MAX(tanggal_input) 
                            FROM pengeluaran_harians 
                            WHERE status_validasi = 'approved' AND nominal > 0
                        )
                    ) as latest_expenses ON today_expenses.nominal IS NULL OR today_expenses.nominal = 0
                    LEFT JOIN (
                        SELECT SUM(nominal) as nominal 
                        FROM pengeluaran_harians 
                        WHERE tanggal_input BETWEEN ? AND ? 
                        AND status_validasi = 'approved'
                    ) as month_expenses ON (today_expenses.nominal IS NULL OR today_expenses.nominal = 0) 
                        AND (latest_expenses.nominal IS NULL OR latest_expenses.nominal = 0)
                ", [
                    $today->format('Y-m-d'), // today_revenue
                    $monthStart, $monthEnd, // month_revenue  
                    $today->format('Y-m-d'), // today_expenses
                    $monthStart, $monthEnd  // month_expenses
                ]);
                
                $todayRevenue = $financialData[0]->revenue ?? 0;
                $todayExpenses = $financialData[0]->expenses ?? 0;
                    
                // OPTIMIZED: Single query for patient and attendance data
                $operationalData = DB::select("
                    SELECT 
                        COALESCE(patients.total_patients, 0) as total_patients,
                        COALESCE(patients.total_umum, 0) as total_umum,
                        COALESCE(patients.total_bpjs, 0) as total_bpjs,
                        COALESCE(patients.record_count, 0) as record_count,
                        COALESCE(patients.avg_per_day, 0) as avg_per_day,
                        COALESCE(attendance.present_count, 0) as present_count,
                        COALESCE(staff.total_staff, 0) as total_staff
                    FROM (SELECT 1 as dummy) as base
                    LEFT JOIN (
                        SELECT 
                            SUM(jumlah_pasien_umum + jumlah_pasien_bpjs) as total_patients,
                            SUM(jumlah_pasien_umum) as total_umum,
                            SUM(jumlah_pasien_bpjs) as total_bpjs,
                            COUNT(*) as record_count,
                            AVG(jumlah_pasien_umum + jumlah_pasien_bpjs) as avg_per_day
                        FROM jumlah_pasien_harians 
                        WHERE status_validasi = 'approved'
                    ) as patients ON 1=1
                    LEFT JOIN (
                        SELECT COUNT(*) as present_count
                        FROM attendances 
                        WHERE date = ? AND time_in IS NOT NULL
                    ) as attendance ON 1=1
                    LEFT JOIN (
                        SELECT COUNT(*) as total_staff
                        FROM users 
                        WHERE is_active = 1
                    ) as staff ON 1=1
                ", [$today->format('Y-m-d')]);
                
                $opData = $operationalData[0];
                $todayPatients = $opData->total_patients;
                $patientBreakdown = (object) [
                    'total_umum' => $opData->total_umum,
                    'total_bpjs' => $opData->total_bpjs,
                    'record_count' => $opData->record_count,
                    'avg_per_day' => $opData->avg_per_day
                ];
                $todayAttendance = $opData->present_count;
                $totalStaff = $opData->total_staff;
                $attendanceRate = $totalStaff > 0 ? round(($todayAttendance / $totalStaff) * 100, 1) : 0;
                
                return response()->json([
                    'success' => true,
                    'message' => 'Today statistics retrieved successfully',
                    'data' => [
                        'revenue' => [
                            'amount' => (float) $todayRevenue,
                            'formatted' => 'Rp ' . number_format($todayRevenue, 0, ',', '.'),
                            'currency' => 'IDR'
                        ],
                        'expenses' => [
                            'amount' => (float) $todayExpenses,
                            'formatted' => 'Rp ' . number_format($todayExpenses, 0, ',', '.'),
                            'currency' => 'IDR'
                        ],
                        'profit' => [
                            'amount' => (float) ($todayRevenue - $todayExpenses),
                            'formatted' => 'Rp ' . number_format($todayRevenue - $todayExpenses, 0, ',', '.'),
                            'currency' => 'IDR'
                        ],
                        'patients' => [
                            'count' => (int) $todayPatients,
                            'general' => (int) ($patientBreakdown->total_umum ?? 0),
                            'bpjs' => (int) ($patientBreakdown->total_bpjs ?? 0),
                            'records_count' => (int) ($patientBreakdown->record_count ?? 0),
                            'avg_per_day' => round($patientBreakdown->avg_per_day ?? 0, 1),
                            'label' => 'Total Pasien Operasional',
                            'calculation_method' => 'cumulative_approved'
                        ],
                        'attendance' => [
                            'present' => (int) $todayAttendance,
                            'total' => (int) $totalStaff,
                            'rate' => $attendanceRate,
                            'formatted' => $attendanceRate . '%'
                        ]
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error retrieving today stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve today statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get monthly finance overview
     */
    public function financeOverview(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            
            $cacheKey = "manajer_finance_overview_{$year}_{$month}";
            
            return Cache::remember($cacheKey, 1200, function () use ($month, $year) {
                // OPTIMIZED: Single query for current and previous month data
                $prevMonth = Carbon::create($year, $month)->subMonth();
                
                // FIXED: Use SQLite-compatible date functions (strftime)
                $currentMonthStart = Carbon::create($year, $month, 1)->format('Y-m-d');
                $currentMonthEnd = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
                $prevMonthStart = $prevMonth->startOfMonth()->format('Y-m-d');
                $prevMonthEnd = $prevMonth->endOfMonth()->format('Y-m-d');
                
                $financialComparison = DB::select("
                    SELECT 
                        SUM(CASE WHEN tanggal BETWEEN ? AND ? THEN nominal ELSE 0 END) as current_revenue,
                        SUM(CASE WHEN tanggal BETWEEN ? AND ? THEN nominal ELSE 0 END) as prev_revenue,
                        COUNT(CASE WHEN tanggal BETWEEN ? AND ? THEN 1 END) as current_rev_count,
                        COUNT(CASE WHEN tanggal BETWEEN ? AND ? THEN 1 END) as prev_rev_count
                    FROM pendapatan 
                    WHERE status_validasi = 'disetujui'
                    AND (tanggal BETWEEN ? AND ? OR tanggal BETWEEN ? AND ?)
                    
                    UNION ALL
                    
                    SELECT 
                        SUM(CASE WHEN tanggal BETWEEN ? AND ? THEN nominal ELSE 0 END) as current_expenses,
                        SUM(CASE WHEN tanggal BETWEEN ? AND ? THEN nominal ELSE 0 END) as prev_expenses,
                        COUNT(CASE WHEN tanggal BETWEEN ? AND ? THEN 1 END) as current_exp_count,
                        COUNT(CASE WHEN tanggal BETWEEN ? AND ? THEN 1 END) as prev_exp_count
                    FROM pengeluaran 
                    WHERE status_validasi = 'disetujui'
                    AND (tanggal BETWEEN ? AND ? OR tanggal BETWEEN ? AND ?)
                ", [
                    $currentMonthStart, $currentMonthEnd, // current revenue
                    $prevMonthStart, $prevMonthEnd, // prev revenue
                    $currentMonthStart, $currentMonthEnd, // current revenue count
                    $prevMonthStart, $prevMonthEnd, // prev revenue count
                    $currentMonthStart, $currentMonthEnd, // revenue query filter current
                    $prevMonthStart, $prevMonthEnd, // revenue query filter prev
                    $currentMonthStart, $currentMonthEnd, // current expenses
                    $prevMonthStart, $prevMonthEnd, // prev expenses
                    $currentMonthStart, $currentMonthEnd, // current expenses count
                    $prevMonthStart, $prevMonthEnd, // prev expenses count
                    $currentMonthStart, $currentMonthEnd, // expenses query filter current
                    $prevMonthStart, $prevMonthEnd  // expenses query filter prev
                ]);
                
                $revenueData = $financialComparison[0] ?? (object)['current_revenue' => 0, 'prev_revenue' => 0];
                $expenseData = $financialComparison[1] ?? (object)['current_expenses' => 0, 'prev_expenses' => 0];
                
                $monthlyRevenue = $revenueData->current_revenue ?? 0;
                $monthlyExpenses = $expenseData->current_expenses ?? 0;
                $prevRevenue = $revenueData->prev_revenue ?? 0;
                $prevExpenses = $expenseData->prev_expenses ?? 0;
                    
                // Calculate growth
                $revenueGrowth = $prevRevenue > 0 ? (($monthlyRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;
                $expenseGrowth = $prevExpenses > 0 ? (($monthlyExpenses - $prevExpenses) / $prevExpenses) * 100 : 0;
                
                // Revenue by category
                $revenueByCategory = Pendapatan::select('kategori', DB::raw('SUM(nominal) as total'))
                    ->whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->groupBy('kategori')
                    ->orderBy('total', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'category' => $item->kategori ?: 'Lainnya',
                            'amount' => (float) $item->total,
                            'formatted' => 'Rp ' . number_format($item->total, 0, ',', '.')
                        ];
                    });
                    
                // Expenses by category
                $expensesByCategory = Pengeluaran::select('kategori', DB::raw('SUM(nominal) as total'))
                    ->whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->groupBy('kategori')
                    ->orderBy('total', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'category' => $item->kategori ?: 'Lainnya',
                            'amount' => (float) $item->total,
                            'formatted' => 'Rp ' . number_format($item->total, 0, ',', '.')
                        ];
                    });
                
                return response()->json([
                    'success' => true,
                    'message' => 'Finance overview retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => (int) $month,
                            'year' => (int) $year,
                            'label' => Carbon::create($year, $month)->format('F Y')
                        ],
                        'summary' => [
                            'revenue' => [
                                'current' => (float) $monthlyRevenue,
                                'previous' => (float) $prevRevenue,
                                'growth' => round($revenueGrowth, 2),
                                'formatted' => 'Rp ' . number_format($monthlyRevenue, 0, ',', '.')
                            ],
                            'expenses' => [
                                'current' => (float) $monthlyExpenses,
                                'previous' => (float) $prevExpenses,
                                'growth' => round($expenseGrowth, 2),
                                'formatted' => 'Rp ' . number_format($monthlyExpenses, 0, ',', '.')
                            ],
                            'profit' => [
                                'amount' => (float) ($monthlyRevenue - $monthlyExpenses),
                                'margin' => $monthlyRevenue > 0 ? round((($monthlyRevenue - $monthlyExpenses) / $monthlyRevenue) * 100, 2) : 0,
                                'formatted' => 'Rp ' . number_format($monthlyRevenue - $monthlyExpenses, 0, ',', '.')
                            ]
                        ],
                        'breakdown' => [
                            'revenue_by_category' => $revenueByCategory,
                            'expenses_by_category' => $expensesByCategory
                        ]
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error retrieving finance overview', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve finance overview',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get recent financial transactions
     */
    public function recentTransactions(Request $request)
    {
        try {
            $limit = min($request->input('limit', 10), 50); // Max 50 items
            
            // Recent approved revenue
            $recentRevenue = Pendapatan::with(['inputBy', 'validasiBy', 'tindakan'])
                ->where('status_validasi', 'disetujui')
                ->orderBy('validasi_at', 'desc')
                ->take($limit / 2)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'revenue',
                        'description' => $item->nama_pendapatan,
                        'category' => $item->kategori ?: 'Lainnya',
                        'amount' => (float) $item->nominal,
                        'formatted_amount' => 'Rp ' . number_format($item->nominal, 0, ',', '.'),
                        'date' => $item->tanggal->format('Y-m-d'),
                        'validated_at' => $item->validasi_at?->format('Y-m-d H:i:s'),
                        'validated_by' => $item->validasiBy?->name,
                        'input_by' => $item->inputBy?->name
                    ];
                });
                
            // Recent approved expenses
            $recentExpenses = Pengeluaran::with(['inputBy', 'validasiBy'])
                ->where('status_validasi', 'disetujui')
                ->orderBy('validasi_at', 'desc')
                ->take($limit / 2)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'expense',
                        'description' => $item->nama_pengeluaran,
                        'category' => $item->kategori ?: 'Lainnya',
                        'amount' => (float) $item->nominal,
                        'formatted_amount' => 'Rp ' . number_format($item->nominal, 0, ',', '.'),
                        'date' => $item->tanggal->format('Y-m-d'),
                        'validated_at' => $item->validasi_at?->format('Y-m-d H:i:s'),
                        'validated_by' => $item->validasiBy?->name,
                        'input_by' => $item->inputBy?->name
                    ];
                });
            
            // Combine and sort by validation date
            $transactions = $recentRevenue->concat($recentExpenses)
                ->sortByDesc('validated_at')
                ->take($limit)
                ->values();
            
            return response()->json([
                'success' => true,
                'message' => 'Recent transactions retrieved successfully',
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving recent transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent transactions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get today's attendance data
     */
    public function attendanceToday(Request $request)
    {
        try {
            $date = $request->input('date', now()->format('Y-m-d'));
            
            $cacheKey = "manajer_attendance_today_{$date}";
            
            return Cache::remember($cacheKey, 300, function () use ($date) {
                // Today's attendance with user details
                $todayAttendance = Attendance::with(['user.role'])
                    ->whereDate('date', $date)
                    ->whereNotNull('time_in')
                    ->orderBy('time_in')
                    ->get()
                    ->map(function ($attendance) {
                        $workDuration = $attendance->work_duration; // Uses the model's accessor
                        
                        return [
                            'id' => $attendance->id,
                            'user' => [
                                'id' => $attendance->user->id,
                                'name' => $attendance->user->name,
                                'role' => $attendance->user->role?->name ?: 'Unknown'
                            ],
                            'check_in' => $attendance->time_in?->format('H:i:s'),
                            'check_out' => $attendance->time_out?->format('H:i:s'),
                            'status' => $attendance->time_out ? 'completed' : 'active',
                            'duration_minutes' => $workDuration,
                            'duration_formatted' => $attendance->formatted_work_duration,
                            'is_late' => $attendance->time_in && $attendance->time_in->format('H:i:s') > '08:00:00'
                        ];
                    });
                
                // Attendance summary by role
                $attendanceByRole = $todayAttendance->groupBy('user.role')
                    ->map(function ($group, $role) {
                        return [
                            'role' => $role,
                            'present' => $group->count(),
                            'completed' => $group->where('status', 'completed')->count(),
                            'active' => $group->where('status', 'active')->count()
                        ];
                    })
                    ->values();
                
                $totalStaff = User::where('is_active', true)->count();
                $presentCount = $todayAttendance->count();
                $lateCount = $todayAttendance->where('is_late', true)->count();
                $completedCount = $todayAttendance->where('status', 'completed')->count();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Today attendance data retrieved successfully',
                    'data' => [
                        'date' => $date,
                        'summary' => [
                            'total_staff' => $totalStaff,
                            'present' => $presentCount,
                            'absent' => $totalStaff - $presentCount,
                            'late' => $lateCount,
                            'completed' => $completedCount,
                            'attendance_rate' => $totalStaff > 0 ? round(($presentCount / $totalStaff) * 100, 1) : 0
                        ],
                        'by_role' => $attendanceByRole,
                        'attendance_list' => $todayAttendance
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error retrieving today attendance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve today attendance data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    /**
     * Get monthly attendance trends
     */
    public function attendanceTrends(Request $request)
    {
        try {
            $months = min($request->input('months', 6), 12); // Max 12 months
            
            $cacheKey = "manajer_attendance_trends_{$months}";
            
            return Cache::remember($cacheKey, 900, function () use ($months) {
                $trends = [];
                
                for ($i = $months - 1; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    
                    // Monthly attendance statistics
                    $monthlyAttendance = Attendance::whereYear('date', $date->year)
                        ->whereMonth('date', $date->month)
                        ->whereNotNull('time_in')
                        ->get();
                    
                    $totalWorkDays = $date->daysInMonth;
                    $totalStaff = User::where('is_active', true)->count();
                    $expectedAttendance = $totalStaff * $totalWorkDays;
                    
                    $actualAttendance = $monthlyAttendance->count();
                    $avgDailyAttendance = $totalWorkDays > 0 ? $actualAttendance / $totalWorkDays : 0;
                    $attendanceRate = $expectedAttendance > 0 ? ($actualAttendance / $expectedAttendance) * 100 : 0;
                    
                    // Late arrivals
                    $lateArrivals = $monthlyAttendance->filter(function ($attendance) {
                        return $attendance->time_in && $attendance->time_in->format('H:i:s') > '08:00:00';
                    })->count();
                    
                    $trends[] = [
                        'month' => $date->format('M'),
                        'year' => $date->year,
                        'label' => $date->format('M Y'),
                        'total_attendance' => $actualAttendance,
                        'avg_daily_attendance' => round($avgDailyAttendance, 1),
                        'attendance_rate' => round($attendanceRate, 1),
                        'late_arrivals' => $lateArrivals,
                        'work_days' => $totalWorkDays
                    ];
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Attendance trends retrieved successfully',
                    'data' => [
                        'trends' => $trends,
                        'summary' => [
                            'avg_attendance_rate' => round(collect($trends)->avg('attendance_rate'), 1),
                            'total_staff' => $totalStaff,
                            'months_analyzed' => $months
                        ]
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error retrieving attendance trends', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attendance trends',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get JASPEL summary and statistics
     */
    public function jaspelSummary(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            
            $cacheKey = "manajer_jaspel_summary_{$year}_{$month}";
            
            return Cache::remember($cacheKey, 600, function () use ($month, $year) {
                // Monthly validated JASPEL
                $monthlyJaspel = Jaspel::with(['user.role'])
                    ->whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->where('status_validasi', 'disetujui')
                    ->get();
                
                $totalJaspel = $monthlyJaspel->sum('total_jaspel');
                
                // Top earners
                $topEarners = $monthlyJaspel->groupBy('user_id')
                    ->map(function ($group) {
                        $user = $group->first()->user;
                        $total = $group->sum('total_jaspel');
                        
                        return [
                            'user_id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role?->name ?: 'Unknown',
                            'total_jaspel' => (float) $total,
                            'formatted_jaspel' => 'Rp ' . number_format($total, 0, ',', '.'),
                            'transaction_count' => $group->count()
                        ];
                    })
                    ->sortByDesc('total_jaspel')
                    ->take(10)
                    ->values();
                
                // JASPEL by role
                $jaspelByRole = $monthlyJaspel->groupBy(function ($jaspel) {
                        return $jaspel->user->role?->name ?: 'Unknown';
                    })
                    ->map(function ($group, $role) use ($totalJaspel) {
                        $roleTotal = $group->sum('total_jaspel');
                        
                        return [
                            'role' => $role,
                            'total' => (float) $roleTotal,
                            'formatted' => 'Rp ' . number_format($roleTotal, 0, ',', '.'),
                            'percentage' => $totalJaspel > 0 ? round(($roleTotal / $totalJaspel) * 100, 1) : 0,
                            'count' => $group->count(),
                            'avg_per_transaction' => $group->count() > 0 ? round($roleTotal / $group->count(), 0) : 0
                        ];
                    })
                    ->sortByDesc('total')
                    ->values();
                
                // Recent JASPEL transactions
                $recentJaspel = Jaspel::with(['user', 'tindakan'])
                    ->where('status_validasi', 'disetujui')
                    ->whereMonth('tanggal', $month)
                    ->whereYear('tanggal', $year)
                    ->orderBy('validasi_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($jaspel) {
                        return [
                            'id' => $jaspel->id,
                            'user' => [
                                'id' => $jaspel->user->id,
                                'name' => $jaspel->user->name
                            ],
                            'jenis_jaspel' => $jaspel->jenis_jaspel,
                            'total_jaspel' => (float) $jaspel->total_jaspel,
                            'formatted_jaspel' => 'Rp ' . number_format($jaspel->total_jaspel, 0, ',', '.'),
                            'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                            'validated_at' => $jaspel->validasi_at?->format('Y-m-d H:i:s')
                        ];
                    });
                
                return response()->json([
                    'success' => true,
                    'message' => 'JASPEL summary retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => (int) $month,
                            'year' => (int) $year,
                            'label' => Carbon::create($year, $month)->format('F Y')
                        ],
                        'summary' => [
                            'total_jaspel' => (float) $totalJaspel,
                            'formatted_total' => 'Rp ' . number_format($totalJaspel, 0, ',', '.'),
                            'transaction_count' => $monthlyJaspel->count(),
                            'unique_recipients' => $monthlyJaspel->unique('user_id')->count(),
                            'avg_per_transaction' => $monthlyJaspel->count() > 0 ? round($totalJaspel / $monthlyJaspel->count(), 0) : 0
                        ],
                        'top_earners' => $topEarners,
                        'by_role' => $jaspelByRole,
                        'recent_transactions' => $recentJaspel
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error retrieving JASPEL summary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve JASPEL summary',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    /**
     * Get doctor performance ranking
     */
    public function doctorRanking(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            $limit = min($request->input('limit', 10), 50);
            
            $cacheKey = "manajer_doctor_ranking_{$year}_{$month}_{$limit}";
            
            return Cache::remember($cacheKey, 900, function () use ($month, $year, $limit) {
                // Get doctors with their performance metrics
                $doctorStats = User::with(['role', 'dokter'])
                    ->whereHas('role', function ($query) {
                        $query->where('name', 'dokter');
                    })
                    ->where('is_active', true)
                    ->get()
                    ->map(function ($user) use ($month, $year) {
                        // Patient count from JumlahPasienHarian
                        $patientData = JumlahPasienHarian::where('dokter_id', optional($user->dokter)->id)
                            ->whereMonth('tanggal', $month)
                            ->whereYear('tanggal', $year)
                            ->where('status_validasi', 'approved')
                            ->get();
                        
                        $totalPatients = $patientData->sum(function ($item) {
                            return $item->jumlah_pasien_umum + $item->jumlah_pasien_bpjs;
                        });
                        
                        // JASPEL earnings
                        $totalJaspel = Jaspel::where('user_id', $user->id)
                            ->whereMonth('tanggal', $month)
                            ->whereYear('tanggal', $year)
                            ->where('status_validasi', 'disetujui')
                            ->sum('total_jaspel');
                        
                        // Attendance rate
                        $totalWorkDays = Carbon::create($year, $month)->daysInMonth;
                        $attendanceDays = Attendance::where('user_id', $user->id)
                            ->whereMonth('date', $month)
                            ->whereYear('date', $year)
                            ->whereNotNull('time_in')
                            ->count();
                        
                        $attendanceRate = $totalWorkDays > 0 ? ($attendanceDays / $totalWorkDays) * 100 : 0;
                        
                        // Procedure count
                        $procedureCount = Tindakan::where('dokter_id', optional($user->dokter)->id)
                            ->whereMonth('tanggal_tindakan', $month)
                            ->whereYear('tanggal_tindakan', $year)
                            ->count();
                        
                        return [
                            'user_id' => $user->id,
                            'doctor_id' => optional($user->dokter)->id,
                            'name' => $user->name,
                            'specialization' => optional($user->dokter)->spesialisasi ?: 'Umum',
                            'metrics' => [
                                'total_patients' => (int) $totalPatients,
                                'total_jaspel' => (float) $totalJaspel,
                                'formatted_jaspel' => 'Rp ' . number_format($totalJaspel, 0, ',', '.'),
                                'attendance_rate' => round($attendanceRate, 1),
                                'attendance_days' => (int) $attendanceDays,
                                'procedure_count' => (int) $procedureCount,
                                'avg_patients_per_day' => $attendanceDays > 0 ? round($totalPatients / $attendanceDays, 1) : 0
                            ],
                            'performance_score' => $this->calculatePerformanceScore($totalPatients, $totalJaspel, $attendanceRate, $procedureCount)
                        ];
                    })
                    ->sortByDesc('performance_score')
                    ->take($limit)
                    ->values();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Doctor ranking retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => (int) $month,
                            'year' => (int) $year,
                            'label' => Carbon::create($year, $month)->format('F Y')
                        ],
                        'rankings' => $doctorStats,
                        'summary' => [
                            'total_doctors' => $doctorStats->count(),
                            'avg_performance_score' => round($doctorStats->avg('performance_score'), 1),
                            'total_patients_treated' => $doctorStats->sum('metrics.total_patients'),
                            'total_jaspel_earned' => $doctorStats->sum('metrics.total_jaspel')
                        ]
                    ]
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error retrieving doctor ranking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve doctor ranking',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    /**
     * Get items pending manager approval
     */
    public function pendingApprovals(Request $request)
    {
        try {
            // Items from ManagerApproval table
            $managerApprovals = ManagerApproval::with(['requestedBy', 'reference'])
                ->where('status', 'pending')
                ->orderBy('priority')
                ->orderBy('created_at')
                ->get()
                ->map(function ($approval) {
                    return [
                        'id' => $approval->id,
                        'type' => 'manager_approval',
                        'approval_type' => $approval->approval_type,
                        'title' => $approval->title,
                        'description' => $approval->description,
                        'amount' => $approval->amount ? (float) $approval->amount : null,
                        'formatted_amount' => $approval->formatted_amount,
                        'priority' => $approval->priority,
                        'priority_color' => $approval->priority_color,
                        'requester' => [
                            'id' => $approval->requestedBy?->id,
                            'name' => $approval->requestedBy?->name,
                            'role' => $approval->requester_role
                        ],
                        'created_at' => $approval->created_at->format('Y-m-d H:i:s'),
                        'required_by' => $approval->required_by?->format('Y-m-d'),
                        'is_overdue' => $approval->is_overdue,
                        'days_until_due' => $approval->days_until_due
                    ];
                });
                
            // Pending financial validations that might need manager attention
            $pendingRevenue = Pendapatan::with(['inputBy'])
                ->where('status_validasi', 'pending')
                ->where('nominal', '>', 1000000) // High-value items
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'revenue_validation',
                        'title' => $item->nama_pendapatan,
                        'description' => 'High-value revenue requires validation',
                        'amount' => (float) $item->nominal,
                        'formatted_amount' => 'Rp ' . number_format($item->nominal, 0, ',', '.'),
                        'category' => $item->kategori,
                        'priority' => $item->nominal > 5000000 ? 'high' : 'medium',
                        'requester' => [
                            'id' => $item->inputBy?->id,
                            'name' => $item->inputBy?->name,
                            'role' => 'Input Staff'
                        ],
                        'date' => $item->tanggal->format('Y-m-d'),
                        'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                        'is_overdue' => $item->created_at->diffInDays(now()) > 3
                    ];
                });
                
            $pendingExpenses = Pengeluaran::with(['inputBy'])
                ->where('status_validasi', 'pending')
                ->where('nominal', '>', 1000000) // High-value items
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'expense_validation',
                        'title' => $item->nama_pengeluaran,
                        'description' => 'High-value expense requires validation',
                        'amount' => (float) $item->nominal,
                        'formatted_amount' => 'Rp ' . number_format($item->nominal, 0, ',', '.'),
                        'category' => $item->kategori,
                        'priority' => $item->nominal > 5000000 ? 'high' : 'medium',
                        'requester' => [
                            'id' => $item->inputBy?->id,
                            'name' => $item->inputBy?->name,
                            'role' => 'Input Staff'
                        ],
                        'date' => $item->tanggal->format('Y-m-d'),
                        'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                        'is_overdue' => $item->created_at->diffInDays(now()) > 3
                    ];
                });
                
            // Combine all pending items
            $allPendingItems = $managerApprovals
                ->concat($pendingRevenue)
                ->concat($pendingExpenses)
                ->sortBy(function ($item) {
                    // Sort by priority and urgency
                    $priorityOrder = ['urgent' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
                    $priority = $priorityOrder[$item['priority']] ?? 4;
                    $overdue = $item['is_overdue'] ?? false ? 0 : 1;
                    
                    return ($priority * 10) + $overdue;
                })
                ->values();
            
            // Summary statistics
            $totalPending = $allPendingItems->count();
            $urgentCount = $allPendingItems->where('priority', 'urgent')->count();
            $highValueCount = $allPendingItems->where('amount', '>', 5000000)->count();
            $overdueCount = $allPendingItems->where('is_overdue', true)->count();
            $totalValue = $allPendingItems->whereNotNull('amount')->sum('amount');
            
            return response()->json([
                'success' => true,
                'message' => 'Pending approvals retrieved successfully',
                'data' => [
                    'items' => $allPendingItems,
                    'summary' => [
                        'total_pending' => $totalPending,
                        'urgent_items' => $urgentCount,
                        'high_value_items' => $highValueCount,
                        'overdue_items' => $overdueCount,
                        'total_value' => (float) $totalValue,
                        'formatted_total_value' => 'Rp ' . number_format($totalValue, 0, ',', '.')
                    ],
                    'breakdown' => [
                        'manager_approvals' => $managerApprovals->count(),
                        'revenue_validations' => $pendingRevenue->count(),
                        'expense_validations' => $pendingExpenses->count()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving pending approvals', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pending approvals',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    // ============================================
    // HELPER METHODS
    // ============================================
    
    /**
     * Calculate performance score for doctor ranking
     */
    private function calculatePerformanceScore($patients, $jaspel, $attendanceRate, $procedures)
    {
        // Weighted scoring system
        $patientScore = min(($patients / 100) * 30, 30); // Max 30 points for patients
        $jaspelScore = min(($jaspel / 1000000) * 25, 25); // Max 25 points for JASPEL (per million)
        $attendanceScore = ($attendanceRate / 100) * 25; // Max 25 points for attendance
        $procedureScore = min(($procedures / 50) * 20, 20); // Max 20 points for procedures
        
        return round($patientScore + $jaspelScore + $attendanceScore + $procedureScore, 1);
    }
    
    /**
     * Get count of items pending approval (legacy method for compatibility)
     */
    private function getPendingApprovals()
    {
        $managerApprovals = ManagerApproval::where('status', 'pending')->count();
        $pendingRevenue = Pendapatan::where('status_validasi', 'pending')
            ->where('nominal', '>', 1000000)->count(); // High-value items
        $pendingExpenses = Pengeluaran::where('status_validasi', 'pending')
            ->where('nominal', '>', 1000000)->count(); // High-value items
            
        return $managerApprovals + $pendingRevenue + $pendingExpenses;
    }
    
    /**
     * Get today's validated revenue (legacy method for compatibility)
     */
    private function getTodayRevenue()
    {
        return Pendapatan::whereDate('tanggal', today())
            ->where('status_validasi', 'disetujui')
            ->sum('nominal');
    }
    
    /**
     * Get today's attendance rate (legacy method for compatibility)
     */
    private function getAttendanceRate()
    {
        $present = Attendance::whereDate('date', today())
            ->whereNotNull('time_in')
            ->distinct('user_id')
            ->count();
            
        $totalStaff = User::where('is_active', true)->count();
        
        return $totalStaff > 0 ? round(($present / $totalStaff) * 100, 2) : 0;
    }
    
    /**
     * Get bed occupancy rate (placeholder - implement based on your bed management system)
     */
    private function getBedOccupancy()
    {
        // This should be implemented based on your actual bed management system
        // For now, returning a reasonable mock value
        return rand(65, 88);
    }
    
    /**
     * Calculate duration between two times (legacy helper)
     */
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
    
    // ============================================
    // LEGACY COMPATIBILITY METHODS
    // ============================================
    
    /**
     * Get comprehensive dashboard data (legacy method name for backward compatibility)
     */
    public function getDashboardData(Request $request)
    {
        return $this->todayStats($request);
    }
    
    /**
     * Get finance data (legacy method name for backward compatibility)
     */
    public function getFinanceData(Request $request)
    {
        return $this->financeOverview($request);
    }
    
    /**
     * Get attendance data (legacy method name for backward compatibility)
     */
    public function getAttendanceData(Request $request)
    {
        return $this->attendanceToday($request);
    }
    
    /**
     * Get JASPEL data (legacy method name for backward compatibility)
     */
    public function getJaspelData(Request $request)
    {
        return $this->jaspelSummary($request);
    }
    
    /**
     * Get profile data (legacy method name for backward compatibility)
     */
    public function getProfileData(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Profile data retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->getPrimaryRoleName() ?: 'manajer',
                        'phone' => $user->phone ?? '',
                        'joined_date' => $user->created_at->format('Y-m-d'),
                        'last_login' => $user->last_login_at?->format('Y-m-d H:i:s')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving profile data', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Update profile (simplified version)
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|max:20',
            ]);
            
            $user->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating profile', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
