<?php

namespace App\Services\Manajer;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ManajerAttendanceService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const LONG_CACHE_TTL = 900; // 15 minutes
    
    /**
     * Get today's attendance analytics
     */
    public function getTodayAnalytics(): array
    {
        try {
            $cacheKey = 'manajer_attendance_today_' . now()->format('Y-m-d');
            
            return Cache::remember($cacheKey, self::CACHE_TTL, function () {
                $today = now();
                
                // Today's attendance with user details and departments
                $todayAttendance = Attendance::with(['user.role'])
                    ->whereDate('date', $today)
                    ->whereNotNull('time_in')
                    ->get();
                
                $totalStaff = User::where('is_active', true)->count();
                $presentCount = $todayAttendance->count();
                $absentCount = $totalStaff - $presentCount;
                $lateCount = $todayAttendance->filter(function ($attendance) {
                    return $attendance->time_in && $attendance->time_in->format('H:i:s') > '08:00:00';
                })->count();
                $completedCount = $todayAttendance->where('time_out', '!=', null)->count();
                $onGoingCount = $todayAttendance->where('time_out', null)->count();
                
                // Department breakdown
                $departmentBreakdown = $todayAttendance->groupBy(function ($attendance) {
                    return $attendance->user->role?->name ?: 'Unknown';
                })->map(function ($group, $department) use ($totalStaff) {
                    $deptStaffCount = User::whereHas('role', function ($q) use ($department) {
                        $q->where('name', $department);
                    })->where('is_active', true)->count();
                    
                    return [
                        'department' => $department,
                        'present' => $group->count(),
                        'total_staff' => $deptStaffCount,
                        'attendance_rate' => $deptStaffCount > 0 ? round(($group->count() / $deptStaffCount) * 100, 1) : 0,
                        'late_count' => $group->filter(function ($attendance) {
                            return $attendance->time_in && $attendance->time_in->format('H:i:s') > '08:00:00';
                        })->count(),
                        'completed_count' => $group->where('time_out', '!=', null)->count(),
                    ];
                })->sortByDesc('attendance_rate')->values();
                
                // Performance metrics
                $attendanceRate = $totalStaff > 0 ? round(($presentCount / $totalStaff) * 100, 1) : 0;
                $punctualityRate = $presentCount > 0 ? round((($presentCount - $lateCount) / $presentCount) * 100, 1) : 0;
                $completionRate = $presentCount > 0 ? round(($completedCount / $presentCount) * 100, 1) : 0;
                
                return [
                    'success' => true,
                    'message' => 'Today attendance analytics retrieved successfully',
                    'data' => [
                        'date' => $today->format('Y-m-d'),
                        'summary' => [
                            'total_staff' => $totalStaff,
                            'present' => $presentCount,
                            'absent' => $absentCount,
                            'late' => $lateCount,
                            'completed' => $completedCount,
                            'ongoing' => $onGoingCount,
                            'attendance_rate' => $attendanceRate,
                            'punctuality_rate' => $punctualityRate,
                            'completion_rate' => $completionRate,
                        ],
                        'department_breakdown' => $departmentBreakdown,
                        'performance_indicators' => [
                            'attendance_status' => $this->getPerformanceStatus($attendanceRate, 90),
                            'punctuality_status' => $this->getPerformanceStatus($punctualityRate, 85),
                            'completion_status' => $this->getPerformanceStatus($completionRate, 80),
                        ]
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting today attendance analytics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve today attendance analytics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Get attendance trends over specified months
     */
    public function getAttendanceTrends(int $months = 6): array
    {
        try {
            $months = min($months, 12); // Max 12 months
            $cacheKey = "manajer_attendance_trends_{$months}";
            
            return Cache::remember($cacheKey, self::LONG_CACHE_TTL, function () use ($months) {
                $trends = [];
                $totalStaff = User::where('is_active', true)->count();
                
                for ($i = $months - 1; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    
                    // Monthly attendance statistics
                    $monthlyAttendance = Attendance::whereYear('date', $date->year)
                        ->whereMonth('date', $date->month)
                        ->whereNotNull('time_in')
                        ->get();
                    
                    $totalWorkDays = $date->daysInMonth;
                    $expectedAttendance = $totalStaff * $totalWorkDays;
                    
                    $actualAttendance = $monthlyAttendance->count();
                    $avgDailyAttendance = $totalWorkDays > 0 ? $actualAttendance / $totalWorkDays : 0;
                    $attendanceRate = $expectedAttendance > 0 ? ($actualAttendance / $expectedAttendance) * 100 : 0;
                    
                    // Late arrivals
                    $lateArrivals = $monthlyAttendance->filter(function ($attendance) {
                        return $attendance->time_in && $attendance->time_in->format('H:i:s') > '08:00:00';
                    })->count();
                    
                    // Work duration analytics
                    $completedShifts = $monthlyAttendance->filter(function ($attendance) {
                        return $attendance->time_out !== null;
                    });
                    
                    $avgWorkDuration = $completedShifts->count() > 0 ? 
                        $completedShifts->avg('work_duration') : 0;
                    
                    $trends[] = [
                        'month' => $date->format('M'),
                        'year' => $date->year,
                        'label' => $date->format('M Y'),
                        'total_attendance' => $actualAttendance,
                        'avg_daily_attendance' => round($avgDailyAttendance, 1),
                        'attendance_rate' => round($attendanceRate, 1),
                        'late_arrivals' => $lateArrivals,
                        'punctuality_rate' => $actualAttendance > 0 ? 
                            round((($actualAttendance - $lateArrivals) / $actualAttendance) * 100, 1) : 0,
                        'avg_work_duration_minutes' => round($avgWorkDuration, 0),
                        'avg_work_duration_hours' => round($avgWorkDuration / 60, 1),
                        'work_days' => $totalWorkDays,
                        'completed_shifts' => $completedShifts->count(),
                        'completion_rate' => $actualAttendance > 0 ? 
                            round(($completedShifts->count() / $actualAttendance) * 100, 1) : 0
                    ];
                }
                
                // Calculate trend indicators
                $avgAttendanceRate = collect($trends)->avg('attendance_rate');
                $avgPunctualityRate = collect($trends)->avg('punctuality_rate');
                $attendanceTrend = $this->calculateTrend($trends, 'attendance_rate');
                $punctualityTrend = $this->calculateTrend($trends, 'punctuality_rate');
                
                return [
                    'success' => true,
                    'message' => 'Attendance trends retrieved successfully',
                    'data' => [
                        'trends' => $trends,
                        'summary' => [
                            'avg_attendance_rate' => round($avgAttendanceRate, 1),
                            'avg_punctuality_rate' => round($avgPunctualityRate, 1),
                            'total_staff' => $totalStaff,
                            'months_analyzed' => $months,
                            'attendance_trend' => $attendanceTrend,
                            'punctuality_trend' => $punctualityTrend,
                        ]
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting attendance trends', [
                'error' => $e->getMessage(),
                'months' => $months
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve attendance trends',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Get top and poor performers analysis
     */
    public function getPerformersAnalysis(int $month = null, int $year = null, int $limit = 10): array
    {
        try {
            $month = $month ?? now()->month;
            $year = $year ?? now()->year;
            $limit = min($limit, 50); // Max 50 items
            
            $cacheKey = "manajer_attendance_performers_{$year}_{$month}_{$limit}";
            
            return Cache::remember($cacheKey, self::LONG_CACHE_TTL, function () use ($month, $year, $limit) {
                // Get all active staff with their monthly attendance metrics
                $staffPerformance = User::with(['role'])
                    ->where('is_active', true)
                    ->get()
                    ->map(function ($user) use ($month, $year) {
                        $monthlyAttendance = Attendance::where('user_id', $user->id)
                            ->whereMonth('date', $month)
                            ->whereYear('date', $year)
                            ->whereNotNull('time_in')
                            ->get();
                        
                        $totalWorkDays = Carbon::create($year, $month)->daysInMonth;
                        $presentDays = $monthlyAttendance->count();
                        $lateDays = $monthlyAttendance->filter(function ($attendance) {
                            return $attendance->time_in && $attendance->time_in->format('H:i:s') > '08:00:00';
                        })->count();
                        $completedShifts = $monthlyAttendance->where('time_out', '!=', null)->count();
                        
                        // Calculate average work duration for completed shifts
                        $completedAttendance = $monthlyAttendance->where('time_out', '!=', null);
                        $avgWorkDuration = $completedAttendance->count() > 0 ? 
                            $completedAttendance->avg('work_duration') : 0;
                        
                        // Performance scoring
                        $attendanceScore = ($presentDays / $totalWorkDays) * 40; // 40% weight
                        $punctualityScore = $presentDays > 0 ? (($presentDays - $lateDays) / $presentDays) * 30 : 0; // 30% weight
                        $completionScore = $presentDays > 0 ? ($completedShifts / $presentDays) * 20 : 0; // 20% weight
                        $durationScore = $avgWorkDuration > 0 ? min(($avgWorkDuration / 480) * 10, 10) : 0; // 10% weight (based on 8 hours = 480 minutes)
                        
                        $totalScore = $attendanceScore + $punctualityScore + $completionScore + $durationScore;
                        
                        return [
                            'user_id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role?->name ?: 'Unknown',
                            'metrics' => [
                                'present_days' => $presentDays,
                                'total_work_days' => $totalWorkDays,
                                'attendance_rate' => round(($presentDays / $totalWorkDays) * 100, 1),
                                'late_days' => $lateDays,
                                'punctuality_rate' => $presentDays > 0 ? round((($presentDays - $lateDays) / $presentDays) * 100, 1) : 0,
                                'completed_shifts' => $completedShifts,
                                'completion_rate' => $presentDays > 0 ? round(($completedShifts / $presentDays) * 100, 1) : 0,
                                'avg_work_hours' => round($avgWorkDuration / 60, 1),
                                'total_work_hours' => round(($avgWorkDuration * $completedShifts) / 60, 1),
                            ],
                            'performance_score' => round($totalScore, 1),
                            'performance_grade' => $this->getPerformanceGrade($totalScore),
                            'trend_indicator' => $this->calculateUserTrend($user->id, $month, $year)
                        ];
                    })
                    ->sortByDesc('performance_score');
                
                $topPerformers = $staffPerformance->take($limit)->values();
                $poorPerformers = $staffPerformance->reverse()->take($limit)->values();
                
                // Department performance summary
                $departmentPerformance = $staffPerformance->groupBy('role')
                    ->map(function ($group, $role) {
                        return [
                            'department' => $role,
                            'staff_count' => $group->count(),
                            'avg_performance_score' => round($group->avg('performance_score'), 1),
                            'avg_attendance_rate' => round($group->avg('metrics.attendance_rate'), 1),
                            'avg_punctuality_rate' => round($group->avg('metrics.punctuality_rate'), 1),
                            'top_performer' => $group->sortByDesc('performance_score')->first()['name'] ?? 'N/A',
                        ];
                    })
                    ->sortByDesc('avg_performance_score')
                    ->values();
                
                return [
                    'success' => true,
                    'message' => 'Performers analysis retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => $month,
                            'year' => $year,
                            'label' => Carbon::create($year, $month)->format('F Y')
                        ],
                        'top_performers' => $topPerformers,
                        'poor_performers' => $poorPerformers,
                        'department_performance' => $departmentPerformance,
                        'summary' => [
                            'total_staff_analyzed' => $staffPerformance->count(),
                            'avg_overall_score' => round($staffPerformance->avg('performance_score'), 1),
                            'excellent_performers' => $staffPerformance->where('performance_score', '>=', 90)->count(),
                            'good_performers' => $staffPerformance->whereBetween('performance_score', [80, 89.9])->count(),
                            'average_performers' => $staffPerformance->whereBetween('performance_score', [70, 79.9])->count(),
                            'below_average_performers' => $staffPerformance->where('performance_score', '<', 70)->count(),
                        ]
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting performers analysis', [
                'error' => $e->getMessage(),
                'month' => $month,
                'year' => $year
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve performers analysis',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Get monthly attendance statistics
     */
    public function getMonthlyStatistics(int $month = null, int $year = null): array
    {
        try {
            $month = $month ?? now()->month;
            $year = $year ?? now()->year;
            
            $cacheKey = "manajer_attendance_monthly_{$year}_{$month}";
            
            return Cache::remember($cacheKey, self::LONG_CACHE_TTL, function () use ($month, $year) {
                $startDate = Carbon::create($year, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();
                
                // Monthly attendance data
                $monthlyAttendance = Attendance::whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->whereNotNull('time_in')
                    ->with(['user.role'])
                    ->get();
                
                $totalStaff = User::where('is_active', true)->count();
                $totalWorkDays = $startDate->daysInMonth;
                $expectedAttendance = $totalStaff * $totalWorkDays;
                
                // Basic statistics
                $actualAttendance = $monthlyAttendance->count();
                $uniqueUsers = $monthlyAttendance->unique('user_id')->count();
                $lateArrivals = $monthlyAttendance->filter(function ($attendance) {
                    return $attendance->time_in && $attendance->time_in->format('H:i:s') > '08:00:00';
                })->count();
                $completedShifts = $monthlyAttendance->where('time_out', '!=', null)->count();
                
                // Daily breakdown
                $dailyBreakdown = [];
                for ($day = 1; $day <= $totalWorkDays; $day++) {
                    $date = Carbon::create($year, $month, $day);
                    $dayAttendance = $monthlyAttendance->where('date', $date->format('Y-m-d'));
                    
                    $dailyBreakdown[] = [
                        'date' => $date->format('Y-m-d'),
                        'day_name' => $date->format('l'),
                        'present' => $dayAttendance->count(),
                        'late' => $dayAttendance->filter(function ($attendance) {
                            return $attendance->time_in && $attendance->time_in->format('H:i:s') > '08:00:00';
                        })->count(),
                        'completed' => $dayAttendance->where('time_out', '!=', null)->count(),
                        'attendance_rate' => $totalStaff > 0 ? round(($dayAttendance->count() / $totalStaff) * 100, 1) : 0,
                    ];
                }
                
                // Weekly patterns
                $weeklyPattern = $monthlyAttendance->groupBy(function ($attendance) {
                    return Carbon::parse($attendance->date)->format('l');
                })->map(function ($group, $dayName) use ($totalStaff) {
                    $weeksInMonth = Carbon::create($year, $month)->weeksInMonth();
                    $expectedForDay = $totalStaff * $weeksInMonth;
                    
                    return [
                        'day' => $dayName,
                        'total_attendance' => $group->count(),
                        'avg_attendance' => round($group->count() / $weeksInMonth, 1),
                        'attendance_rate' => $expectedForDay > 0 ? round(($group->count() / $expectedForDay) * 100, 1) : 0,
                        'late_count' => $group->filter(function ($attendance) {
                            return $attendance->time_in && $attendance->time_in->format('H:i:s') > '08:00:00';
                        })->count(),
                    ];
                })->values();
                
                return [
                    'success' => true,
                    'message' => 'Monthly attendance statistics retrieved successfully',
                    'data' => [
                        'period' => [
                            'month' => $month,
                            'year' => $year,
                            'label' => $startDate->format('F Y'),
                            'total_work_days' => $totalWorkDays
                        ],
                        'summary' => [
                            'total_staff' => $totalStaff,
                            'expected_attendance' => $expectedAttendance,
                            'actual_attendance' => $actualAttendance,
                            'unique_attendees' => $uniqueUsers,
                            'attendance_rate' => $expectedAttendance > 0 ? round(($actualAttendance / $expectedAttendance) * 100, 1) : 0,
                            'late_arrivals' => $lateArrivals,
                            'punctuality_rate' => $actualAttendance > 0 ? round((($actualAttendance - $lateArrivals) / $actualAttendance) * 100, 1) : 0,
                            'completed_shifts' => $completedShifts,
                            'completion_rate' => $actualAttendance > 0 ? round(($completedShifts / $actualAttendance) * 100, 1) : 0,
                            'avg_daily_attendance' => round($actualAttendance / $totalWorkDays, 1),
                        ],
                        'daily_breakdown' => $dailyBreakdown,
                        'weekly_pattern' => $weeklyPattern,
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error getting monthly attendance statistics', [
                'error' => $e->getMessage(),
                'month' => $month,
                'year' => $year
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to retrieve monthly attendance statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ];
        }
    }
    
    /**
     * Helper method to get performance status
     */
    private function getPerformanceStatus(float $value, float $target): string
    {
        if ($value >= $target) {
            return 'excellent';
        } elseif ($value >= $target * 0.9) {
            return 'good';
        } elseif ($value >= $target * 0.8) {
            return 'average';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Helper method to calculate trend
     */
    private function calculateTrend(array $trends, string $metric): array
    {
        if (count($trends) < 2) {
            return ['direction' => 'stable', 'percentage' => 0];
        }
        
        $latest = end($trends)[$metric];
        $previous = prev($trends)[$metric];
        
        if ($previous == 0) {
            return ['direction' => 'stable', 'percentage' => 0];
        }
        
        $change = (($latest - $previous) / $previous) * 100;
        
        return [
            'direction' => $change > 2 ? 'up' : ($change < -2 ? 'down' : 'stable'),
            'percentage' => round(abs($change), 1)
        ];
    }
    
    /**
     * Helper method to get performance grade
     */
    private function getPerformanceGrade(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
    
    /**
     * Helper method to calculate user trend indicator
     */
    private function calculateUserTrend(int $userId, int $month, int $year): string
    {
        try {
            // Compare current month with previous month
            $currentMonth = Attendance::where('user_id', $userId)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->whereNotNull('time_in')
                ->count();
            
            $prevMonth = $month == 1 ? 12 : $month - 1;
            $prevYear = $month == 1 ? $year - 1 : $year;
            
            $previousMonth = Attendance::where('user_id', $userId)
                ->whereMonth('date', $prevMonth)
                ->whereYear('date', $prevYear)
                ->whereNotNull('time_in')
                ->count();
            
            if ($previousMonth == 0) return 'stable';
            
            $change = ($currentMonth - $previousMonth) / $previousMonth;
            
            if ($change > 0.1) return 'improving';
            if ($change < -0.1) return 'declining';
            return 'stable';
            
        } catch (\Exception $e) {
            Log::warning('Error calculating user trend', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 'stable';
        }
    }
}