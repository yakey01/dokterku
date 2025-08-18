<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaderboardController extends Controller
{
    /**
     * Get top 3 doctors by attendance rate for current month
     */
    public function getTopDoctors(): JsonResponse
    {
        try {
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            $workingDays = $this->getWorkingDaysInMonth($currentMonth, $currentYear);
            
            // Get doctors with their attendance stats
            // Role IDs: 6 = dokter, 8 = dokter_gigi (only doctors, not paramedis)
            $topDoctors = User::whereIn('role_id', [6, 8])
                ->where('is_active', true)
                ->with('role')
                ->withCount([
                    'attendances as total_attendance' => function ($query) use ($currentMonth, $currentYear) {
                        $query->whereMonth('date', $currentMonth)
                              ->whereYear('date', $currentYear)
                              ->where('status', 'present')
                              ->whereNotNull('time_out');
                    }
                ])
                ->with(['attendances' => function ($query) use ($currentMonth, $currentYear) {
                    $query->whereMonth('date', $currentMonth)
                          ->whereYear('date', $currentYear)
                          ->where('status', 'present')
                          ->whereNotNull('time_out')
                          ->select('user_id', 'date', 'time_in', 'time_out');
                }])
                ->get()
                ->map(function ($doctor) use ($workingDays) {
                    // Calculate total hours from attendances
                    $totalHours = $doctor->attendances->reduce(function ($carry, $attendance) {
                        // Handle both datetime and time-only formats
                        // If time_in and time_out contain dates, use them directly
                        // Otherwise, combine with the attendance date
                        if (strpos($attendance->time_in, '-') !== false) {
                            // Full datetime format
                            $checkIn = Carbon::parse($attendance->time_in);
                            $checkOut = Carbon::parse($attendance->time_out);
                        } else {
                            // Time-only format, combine with date
                            $checkIn = Carbon::parse($attendance->date . ' ' . $attendance->time_in);
                            $checkOut = Carbon::parse($attendance->date . ' ' . $attendance->time_out);
                        }
                        
                        // ✅ CRITICAL FIX: Correct parameter order and overnight shift handling
                        if ($checkOut->lt($checkIn)) {
                            $checkOut->addDay(); // Handle overnight shifts
                        }
                        
                        $hours = $checkIn->diffInHours($checkOut); // ✅ FIXED: Correct parameter order
                        
                        return $carry + $hours;
                    }, 0);
                    
                    // Calculate attendance rate
                    $attendanceRate = $workingDays > 0 
                        ? round(($doctor->total_attendance / $workingDays) * 100, 1)
                        : 0;
                    
                    // Calculate level based on attendance rate and experience
                    $level = $this->calculateLevel($attendanceRate, $doctor->total_attendance);
                    
                    // Calculate XP (experience points)
                    $xp = $this->calculateXP($doctor->total_attendance, $totalHours, $attendanceRate);
                    
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'role' => $doctor->role->name ?? 'dokter',
                        'attendance_rate' => min($attendanceRate, 100), // Cap at 100%
                        'level' => $level,
                        'xp' => $xp,
                        'total_days' => $doctor->total_attendance,
                        'total_hours' => $totalHours,
                        'avatar' => $doctor->avatar_url ?? null,
                        'department' => $doctor->department ?? 'Umum',
                        'streak_days' => $this->calculateStreak($doctor->id),
                    ];
                })
                ->sortByDesc('attendance_rate')
                ->take(3)
                ->values();
            
            // Add ranking positions
            $leaderboard = $topDoctors->map(function ($doctor, $index) {
                $doctor['rank'] = $index + 1;
                $doctor['badge'] = $this->getBadgeForRank($index + 1);
                return $doctor;
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'leaderboard' => $leaderboard,
                    'month' => Carbon::now()->format('F Y'),
                    'working_days' => $workingDays,
                    'last_updated' => Carbon::now()->toIso8601String(),
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Leaderboard Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leaderboard',
                'error' => $e->getMessage() // Temporarily show error for debugging
            ], 500);
        }
    }
    
    /**
     * Calculate doctor level based on attendance and experience
     */
    private function calculateLevel(float $attendanceRate, int $totalDays): int
    {
        // Level calculation based on attendance rate and consistency
        $baseLevel = floor($attendanceRate / 10); // 0-10 based on percentage
        
        // Bonus levels for consistency
        if ($totalDays >= 20) {
            $baseLevel = min($baseLevel + 1, 10);
        }
        
        return max(1, min($baseLevel, 10)); // Ensure level is between 1-10
    }
    
    /**
     * Calculate experience points
     */
    private function calculateXP(int $totalDays, int $totalHours, float $attendanceRate): int
    {
        $baseXP = $totalDays * 100; // 100 XP per day
        $hoursBonus = $totalHours * 10; // 10 XP per hour
        $rateBonus = floor($attendanceRate * 20); // Up to 2000 XP for perfect attendance
        
        return $baseXP + $hoursBonus + $rateBonus;
    }
    
    /**
     * Calculate attendance streak
     */
    private function calculateStreak(int $userId): int
    {
        $streak = 0;
        $currentDate = Carbon::today();
        
        // Check backwards for consecutive attendance days
        for ($i = 0; $i < 30; $i++) {
            $date = $currentDate->copy()->subDays($i);
            
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }
            
            $hasAttendance = Attendance::where('user_id', $userId)
                ->whereDate('check_in_time', $date)
                ->whereNotNull('check_out_time')
                ->exists();
            
            if ($hasAttendance) {
                $streak++;
            } else {
                // Break on first missing day (excluding today if not checked in yet)
                if ($i > 0) {
                    break;
                }
            }
        }
        
        return $streak;
    }
    
    /**
     * Get working days in month (excluding weekends)
     */
    private function getWorkingDaysInMonth(int $month, int $year): int
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $workingDays = 0;
        while ($startDate <= $endDate) {
            if (!$startDate->isWeekend()) {
                $workingDays++;
            }
            $startDate->addDay();
        }
        
        return $workingDays;
    }
    
    /**
     * Get badge emoji/icon for rank
     */
    private function getBadgeForRank(int $rank): string
    {
        return match($rank) {
            1 => '👑', // Gold crown for 1st
            2 => '🥈', // Silver medal for 2nd
            3 => '🥉', // Bronze medal for 3rd
            default => '⭐'
        };
    }
}