<?php

/**
 * OPTIMIZED LEADERBOARD QUERIES FOR DATABASE CONSISTENCY FIXES
 * 
 * These are the recommended Laravel/Eloquent implementations to fix:
 * 1. Missing patient data in leaderboard
 * 2. 56% attendance rate calculation issues  
 * 3. Dr. Aji missing data problems
 */

use App\Models\User;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OptimizedLeaderboardQueries
{
    /**
     * SOLUTION 1: Add patient count calculation to leaderboard
     * Fixes the missing total_patients field issue
     */
    public function getTopDoctorsWithPatientCounts(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $workingDays = $this->getWorkingDaysInMonth($currentMonth, $currentYear);
        
        // Get doctors with attendance + patient counts in single optimized query
        $topDoctors = User::whereIn('role_id', [6, 8])
            ->where('is_active', true)
            ->with('role')
            ->whereHas('dokter', function($query) {
                $query->where('aktif', true);
            })
            ->withCount([
                'attendances as total_attendance' => function ($query) use ($currentMonth, $currentYear) {
                    $query->whereMonth('date', $currentMonth)
                          ->whereYear('date', $currentYear)
                          ->where('status', 'present')
                          ->whereNotNull('time_out');
                }
            ])
            ->with(['dokter' => function($query) use ($currentMonth, $currentYear) {
                // Load doctor record with tindakan counts
                $query->withCount([
                    'tindakan as total_patients' => function($q) use ($currentMonth, $currentYear) {
                        $q->whereMonth('tanggal_tindakan', $currentMonth)
                          ->whereYear('tanggal_tindakan', $currentYear)
                          ->distinct('pasien_id');
                    },
                    'tindakan as total_tindakan' => function($q) use ($currentMonth, $currentYear) {
                        $q->whereMonth('tanggal_tindakan', $currentMonth)
                          ->whereYear('tanggal_tindakan', $currentYear);
                    }
                ]);
            }])
            ->with(['attendances' => function ($query) use ($currentMonth, $currentYear) {
                $query->whereMonth('date', $currentMonth)
                      ->whereYear('date', $currentYear)
                      ->where('status', 'present')
                      ->whereNotNull('time_out')
                      ->select('user_id', 'date', 'time_in', 'time_out');
            }])
            ->get()
            ->map(function ($doctor) use ($workingDays) {
                // Calculate total hours (existing logic)
                $totalHours = $doctor->attendances->reduce(function ($carry, $attendance) {
                    if (strpos($attendance->time_in, '-') !== false) {
                        $checkIn = Carbon::parse($attendance->time_in);
                        $checkOut = Carbon::parse($attendance->time_out);
                    } else {
                        $checkIn = Carbon::parse($attendance->date . ' ' . $attendance->time_in);
                        $checkOut = Carbon::parse($attendance->date . ' ' . $attendance->time_out);
                    }
                    
                    if ($checkOut->lt($checkIn)) {
                        $checkOut->addDay();
                    }
                    
                    return $carry + $checkIn->diffInHours($checkOut);
                }, 0);
                
                // Calculate attendance rate
                $attendanceRate = $workingDays > 0 
                    ? round(($doctor->total_attendance / $workingDays) * 100, 1)
                    : 0;
                
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'role' => $doctor->role->name ?? 'dokter',
                    'attendance_rate' => min($attendanceRate, 100),
                    'level' => $this->calculateLevel($attendanceRate, $doctor->total_attendance),
                    'xp' => $this->calculateXP($doctor->total_attendance, $totalHours, $attendanceRate),
                    'total_days' => $doctor->total_attendance,
                    'total_hours' => $totalHours,
                    // ✅ FIX: Add missing patient count
                    'total_patients' => $doctor->dokter->total_patients ?? 0,
                    'total_tindakan' => $doctor->dokter->total_tindakan ?? 0,
                    'avatar' => $doctor->avatar_url ?? null,
                    'department' => $doctor->department ?? 'Umum',
                    'streak_days' => $this->calculateStreak($doctor->id),
                ];
            })
            ->sortByDesc('attendance_rate')
            ->take(3)
            ->values();
        
        return $topDoctors->toArray();
    }

    /**
     * SOLUTION 2: Standardized attendance calculation
     * Fixes the 56% calculation discrepancy
     */
    public function getStandardizedAttendanceRate(int $userId, int $month, int $year): float
    {
        // Use consistent calculation: distinct dates with time_out / working days (exclude weekends)
        $attendanceDays = Attendance::where('user_id', $userId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereNotNull('time_out')  // ✅ FIX: Require completed attendance
            ->distinct('date')
            ->count();
        
        $workingDays = $this->getWorkingDaysInMonth($month, $year);
        
        return $workingDays > 0 ? round(($attendanceDays / $workingDays) * 100, 1) : 0;
    }

    /**
     * SOLUTION 3: Optimized working days calculation
     * Ensures consistency across all calculations
     */
    private function getWorkingDaysInMonth(int $month, int $year): int
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $workingDays = 0;
        while ($startDate <= $endDate) {
            // ✅ STANDARDIZED: Exclude weekends (Saturday and Sunday)
            if (!$startDate->isWeekend()) {
                $workingDays++;
            }
            $startDate->addDay();
        }
        
        return $workingDays;
    }

    /**
     * SOLUTION 4: Direct patient count query for debugging Dr. Aji issue
     */
    public function getPatientCountsForAllDoctors(int $month, int $year): array
    {
        return User::whereIn('role_id', [6, 8])
            ->where('is_active', true)
            ->with(['dokter' => function($query) use ($month, $year) {
                $query->where('aktif', true)
                      ->with(['tindakan' => function($q) use ($month, $year) {
                          $q->whereMonth('tanggal_tindakan', $month)
                            ->whereYear('tanggal_tindakan', $year)
                            ->with('pasien:id,nama');
                      }]);
            }])
            ->get()
            ->map(function ($user) {
                $dokter = $user->dokter;
                if (!$dokter) {
                    return [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'dokter_id' => null,
                        'dokter_name' => null,
                        'total_patients' => 0,
                        'total_tindakan' => 0,
                        'issue' => 'No dokter record'
                    ];
                }
                
                $tindakan = $dokter->tindakan;
                $uniquePatients = $tindakan->pluck('pasien_id')->unique()->count();
                
                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'dokter_id' => $dokter->id,
                    'dokter_name' => $dokter->nama_lengkap,
                    'total_patients' => $uniquePatients,
                    'total_tindakan' => $tindakan->count(),
                    'patients_list' => $tindakan->pluck('pasien.nama')->unique()->values(),
                    'issue' => $uniquePatients === 0 ? 'No patients this month' : null
                ];
            })
            ->sortByDesc('total_patients')
            ->values()
            ->toArray();
    }

    /**
     * SOLUTION 5: Database integrity check
     * Identifies orphaned records causing data issues
     */
    public function checkDatabaseIntegrity(): array
    {
        $issues = [];
        
        // Check for orphaned doctor users
        $orphanedUsers = User::whereIn('role_id', [6, 8])
            ->where('is_active', true)
            ->whereDoesntHave('dokter', function($q) {
                $q->where('aktif', true);
            })
            ->get(['id', 'name']);
        
        if ($orphanedUsers->count() > 0) {
            $issues['orphaned_users'] = [
                'count' => $orphanedUsers->count(),
                'records' => $orphanedUsers->toArray(),
                'solution' => 'Create corresponding dokter records or update user roles'
            ];
        }
        
        // Check for orphaned dokter records  
        $orphanedDokters = Dokter::where('aktif', true)
            ->whereDoesntHave('user', function($q) {
                $q->where('is_active', true);
            })
            ->get(['id', 'nama_lengkap', 'user_id']);
        
        if ($orphanedDokters->count() > 0) {
            $issues['orphaned_dokters'] = [
                'count' => $orphanedDokters->count(), 
                'records' => $orphanedDokters->toArray(),
                'solution' => 'Create corresponding user records or link to existing users'
            ];
        }
        
        // Check for invalid tindakan references
        $invalidTindakan = Tindakan::whereDoesntHave('dokter', function($q) {
                $q->where('aktif', true);
            })
            ->orWhereDoesntHave('pasien')
            ->count();
        
        if ($invalidTindakan > 0) {
            $issues['invalid_tindakan'] = [
                'count' => $invalidTindakan,
                'solution' => 'Fix foreign key references in tindakan table'
            ];
        }
        
        return $issues;
    }

    /**
     * SOLUTION 6: Bulk attendance rate calculation with debugging info
     * Shows different calculation methods for comparison
     */
    public function compareAttendanceCalculationMethods(int $month, int $year): array
    {
        $doctors = User::whereIn('role_id', [6, 8])->where('is_active', true)->get();
        
        return $doctors->map(function ($doctor) use ($month, $year) {
            // Method 1: Leaderboard style (with time_out, exclude weekends)
            $completedAttendance = Attendance::where('user_id', $doctor->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->whereNotNull('time_out')
                ->count();
            
            $workingDaysWeekends = $this->getWorkingDaysInMonth($month, $year);
            $leaderboardRate = $workingDaysWeekends > 0 ? ($completedAttendance / $workingDaysWeekends) * 100 : 0;
            
            // Method 2: Presensi API style (distinct dates, Monday-Saturday)  
            $distinctDates = Attendance::where('user_id', $doctor->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->distinct('date')
                ->count();
            
            $workingDaysMonToSat = $this->getWorkingDaysMonToSat($month, $year);
            $presensiRate = $workingDaysMonToSat > 0 ? ($distinctDates / $workingDaysMonToSat) * 100 : 0;
            
            return [
                'user_id' => $doctor->id,
                'name' => $doctor->name,
                'method_1_leaderboard' => round($leaderboardRate, 1),
                'method_2_presensi' => round($presensiRate, 1),
                'difference' => round(abs($leaderboardRate - $presensiRate), 1),
                'completed_attendance' => $completedAttendance,
                'distinct_dates' => $distinctDates,
                'working_days_exclude_weekends' => $workingDaysWeekends,
                'working_days_mon_sat' => $workingDaysMonToSat,
                'is_56_percent' => (abs($presensiRate - 56) < 2 || abs($leaderboardRate - 56) < 2),
            ];
        })->sortByDesc('difference')->values()->toArray();
    }

    /**
     * Helper: Working days Monday-Saturday (exclude Sunday only)
     */
    private function getWorkingDaysMonToSat(int $month, int $year): int
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $workingDays = 0;
        while ($startDate <= $endDate) {
            // Exclude Sunday only (dayOfWeek 0)
            if ($startDate->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $startDate->addDay();
        }
        
        return $workingDays;
    }

    // ... existing helper methods (calculateLevel, calculateXP, calculateStreak, etc.)
}

/**
 * IMPLEMENTATION EXAMPLE FOR LeaderboardController.php
 * 
 * Replace the existing getTopDoctors() method with:
 */

/*
public function getTopDoctors(): JsonResponse
{
    try {
        $optimizer = new OptimizedLeaderboardQueries();
        $leaderboard = $optimizer->getTopDoctorsWithPatientCounts();
        
        // Add ranking positions
        $rankedLeaderboard = collect($leaderboard)->map(function ($doctor, $index) {
            $doctor['rank'] = $index + 1;
            $doctor['badge'] = $this->getBadgeForRank($index + 1);
            return $doctor;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $rankedLeaderboard,
                'month' => Carbon::now()->format('F Y'),
                'working_days' => $optimizer->getWorkingDaysInMonth(Carbon::now()->month, Carbon::now()->year),
                'last_updated' => Carbon::now()->toIso8601String(),
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Leaderboard Error: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch leaderboard',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
}
*/