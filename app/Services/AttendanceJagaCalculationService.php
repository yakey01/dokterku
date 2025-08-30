<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AttendanceJagaCalculationService
{
    /**
     * Calculate comprehensive attendance metrics for a user
     */
    public function calculateUserAttendanceMetrics(int $userId, int $month, int $year): array
    {
        $cacheKey = "attendance_jaga_metrics_{$userId}_{$month}_{$year}";

        return Cache::remember($cacheKey, 3600, function () use ($userId, $month, $year) {
            $user = User::with('role')->find($userId);
            if (! $user) {
                return $this->getEmptyMetrics();
            }

            // Get scheduled shifts for the period
            $scheduledShifts = $this->getScheduledShifts($userId, $month, $year);

            // Get actual attendances for the period
            $attendances = $this->getAttendances($userId, $month, $year);

            // Calculate metrics
            return [
                'user_id' => $userId,
                'staff_name' => $user->name,
                'profession' => $this->getUserProfession($user),
                'position' => $user->role?->display_name ?? 'Unknown',
                'scheduled_shifts_count' => $scheduledShifts->count(),
                'attended_shifts_count' => $attendances->count(),
                'total_scheduled_hours' => $this->calculateTotalScheduledHours($scheduledShifts),
                'total_working_hours' => $this->calculateTotalWorkingHours($attendances),
                'total_shortfall_minutes' => $this->calculateTotalShortfall($scheduledShifts, $attendances),
                'average_check_in' => $this->calculateAverageCheckIn($attendances),
                'average_check_out' => $this->calculateAverageCheckOut($attendances),
                'attendance_percentage' => $this->calculateAttendancePercentage($scheduledShifts, $attendances),
                'schedule_compliance_rate' => $this->calculateScheduleCompliance($scheduledShifts, $attendances),
                'gps_validation_rate' => $this->calculateGpsValidationRate($attendances),
                'punctuality_score' => $this->calculatePunctualityScore($scheduledShifts, $attendances),
                'overtime_hours' => $this->calculateOvertimeHours($attendances),
                'break_compliance_rate' => $this->calculateBreakCompliance($attendances),
                'month' => $month,
                'year' => $year,
            ];
        });
    }

    /**
     * Get scheduled shifts for user in given period
     */
    private function getScheduledShifts(int $userId, int $month, int $year): Collection
    {
        return JadwalJaga::with(['shiftTemplate'])
            ->where('pegawai_id', $userId)
            ->whereMonth('tanggal_jaga', $month)
            ->whereYear('tanggal_jaga', $year)
            ->orderBy('tanggal_jaga')
            ->get();
    }

    /**
     * Get attendances for user in given period
     */
    private function getAttendances(int $userId, int $month, int $year): Collection
    {
        return Attendance::with(['jadwalJaga.shiftTemplate'])
            ->where('user_id', $userId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereNotNull('time_in')
            ->orderBy('date')
            ->get();
    }

    /**
     * Calculate total scheduled hours
     */
    private function calculateTotalScheduledHours(Collection $scheduledShifts): float
    {
        $totalMinutes = 0;

        foreach ($scheduledShifts as $shift) {
            if (! $shift->shiftTemplate) {
                continue;
            }

            $shiftMinutes = $this->getShiftDurationMinutes($shift->shiftTemplate);
            $totalMinutes += $shiftMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculate total working hours from attendances
     */
    private function calculateTotalWorkingHours(Collection $attendances): float
    {
        $totalMinutes = $attendances->sum(function ($attendance) {
            return $attendance->work_duration ?? 0;
        });

        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculate total shortfall minutes
     */
    private function calculateTotalShortfall(Collection $scheduledShifts, Collection $attendances): int
    {
        $totalShortfall = 0;

        // Group attendances by date for easier lookup
        $attendancesByDate = $attendances->keyBy('date');

        foreach ($scheduledShifts as $shift) {
            $shiftDate = $shift->tanggal_jaga->format('Y-m-d');
            $attendance = $attendancesByDate->get($shiftDate);

            if (! $attendance || ! $shift->shiftTemplate) {
                // No attendance = full shift duration as shortfall
                $scheduledMinutes = $this->getShiftDurationMinutes($shift->shiftTemplate);
                $totalShortfall += $scheduledMinutes;

                continue;
            }

            $scheduledMinutes = $this->getShiftDurationMinutes($shift->shiftTemplate);
            $actualMinutes = $attendance->work_duration ?? 0;

            if ($scheduledMinutes > $actualMinutes) {
                $totalShortfall += ($scheduledMinutes - $actualMinutes);
            }
        }

        return round($totalShortfall);
    }

    /**
     * Calculate average check-in time
     */
    private function calculateAverageCheckIn(Collection $attendances): ?string
    {
        if ($attendances->isEmpty()) {
            return null;
        }

        $checkinTimes = $attendances->map(function ($attendance) {
            if (! $attendance->time_in) {
                return null;
            }

            try {
                $time = Carbon::parse($attendance->time_in);

                return $time->hour * 3600 + $time->minute * 60 + $time->second;
            } catch (\Exception $e) {
                return null;
            }
        })->filter();

        if ($checkinTimes->isEmpty()) {
            return null;
        }

        $averageSeconds = $checkinTimes->avg();

        return gmdate('H:i', $averageSeconds);
    }

    /**
     * Calculate average check-out time
     */
    private function calculateAverageCheckOut(Collection $attendances): ?string
    {
        if ($attendances->isEmpty()) {
            return null;
        }

        $checkoutTimes = $attendances->map(function ($attendance) {
            if (! $attendance->time_out) {
                return null;
            }

            try {
                $time = Carbon::parse($attendance->time_out);

                return $time->hour * 3600 + $time->minute * 60 + $time->second;
            } catch (\Exception $e) {
                return null;
            }
        })->filter();

        if ($checkoutTimes->isEmpty()) {
            return null;
        }

        $averageSeconds = $checkoutTimes->avg();

        return gmdate('H:i', $averageSeconds);
    }

    /**
     * Calculate attendance percentage
     */
    private function calculateAttendancePercentage(Collection $scheduledShifts, Collection $attendances): float
    {
        if ($scheduledShifts->isEmpty()) {
            return 0.0;
        }

        $attendancePercentage = ($attendances->count() / $scheduledShifts->count()) * 100;

        return round($attendancePercentage, 1);
    }

    /**
     * Calculate schedule compliance rate
     */
    private function calculateScheduleCompliance(Collection $scheduledShifts, Collection $attendances): float
    {
        if ($scheduledShifts->isEmpty()) {
            return 0.0;
        }

        $compliantShifts = 0;
        $attendancesByDate = $attendances->keyBy('date');

        foreach ($scheduledShifts as $shift) {
            $shiftDate = $shift->tanggal_jaga->format('Y-m-d');
            $attendance = $attendancesByDate->get($shiftDate);

            if (! $attendance || ! $shift->shiftTemplate) {
                continue;
            }

            if ($this->isShiftCompliant($shift, $attendance)) {
                $compliantShifts++;
            }
        }

        return round(($compliantShifts / $scheduledShifts->count()) * 100, 1);
    }

    /**
     * Check if a shift is compliant (within grace periods)
     */
    private function isShiftCompliant(JadwalJaga $shift, Attendance $attendance): bool
    {
        if (! $attendance->time_in || ! $attendance->time_out) {
            return false;
        }

        try {
            $scheduledStart = Carbon::parse($shift->shiftTemplate->jam_masuk);
            $scheduledEnd = Carbon::parse($shift->shiftTemplate->jam_pulang);
            $actualStart = Carbon::parse($attendance->time_in);
            $actualEnd = Carbon::parse($attendance->time_out);

            // Grace periods: 15 minutes late for start, 15 minutes early for end
            $startCompliant = $actualStart->diffInMinutes($scheduledStart, false) <= 15;
            $endCompliant = $actualEnd->diffInMinutes($scheduledEnd, false) >= -15;

            return $startCompliant && $endCompliant;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Calculate GPS validation rate
     */
    private function calculateGpsValidationRate(Collection $attendances): float
    {
        if ($attendances->isEmpty()) {
            return 0.0;
        }

        $validGpsCount = $attendances->where('is_location_valid_in', true)->count();

        return round(($validGpsCount / $attendances->count()) * 100, 1);
    }

    /**
     * Calculate punctuality score
     */
    private function calculatePunctualityScore(Collection $scheduledShifts, Collection $attendances): float
    {
        if ($attendances->isEmpty()) {
            return 0.0;
        }

        $punctualCount = 0;
        $attendancesByDate = $attendances->keyBy('date');

        foreach ($scheduledShifts as $shift) {
            $shiftDate = $shift->tanggal_jaga->format('Y-m-d');
            $attendance = $attendancesByDate->get($shiftDate);

            if (! $attendance || ! $shift->shiftTemplate) {
                continue;
            }

            try {
                $scheduledStart = Carbon::parse($shift->shiftTemplate->jam_masuk);
                $actualStart = Carbon::parse($attendance->time_in);

                // Punctual = on time or early (within 5 minutes grace)
                if ($actualStart->diffInMinutes($scheduledStart, false) <= 5) {
                    $punctualCount++;
                }
            } catch (\Exception $e) {
                // Skip invalid times
            }
        }

        return $attendances->count() > 0 ?
            round(($punctualCount / $attendances->count()) * 100, 1) : 0.0;
    }

    /**
     * Calculate overtime hours
     */
    private function calculateOvertimeHours(Collection $attendances): float
    {
        $overtimeMinutes = 0;

        foreach ($attendances as $attendance) {
            if (! $attendance->jadwalJaga?->shiftTemplate) {
                continue;
            }

            $scheduledMinutes = $this->getShiftDurationMinutes($attendance->jadwalJaga->shiftTemplate);
            $actualMinutes = $attendance->work_duration ?? 0;

            if ($actualMinutes > $scheduledMinutes) {
                $overtimeMinutes += ($actualMinutes - $scheduledMinutes);
            }
        }

        return round($overtimeMinutes / 60, 2);
    }

    /**
     * Calculate break compliance rate
     */
    private function calculateBreakCompliance(Collection $attendances): float
    {
        // For now, return 100% as break tracking is not fully implemented
        // This can be enhanced when break time tracking is added
        return 100.0;
    }

    /**
     * Get shift duration in minutes
     */
    private function getShiftDurationMinutes(ShiftTemplate $shiftTemplate): int
    {
        try {
            $start = Carbon::parse($shiftTemplate->jam_masuk);
            $end = Carbon::parse($shiftTemplate->jam_pulang);

            // Handle overnight shifts
            if ($end->lessThan($start)) {
                $end->addDay();
            }

            $durationMinutes = $start->diffInMinutes($end);

            // Subtract break time if configured
            if ($shiftTemplate->break_duration_minutes) {
                $durationMinutes -= $shiftTemplate->break_duration_minutes;
            }

            return max(0, $durationMinutes);

        } catch (\Exception $e) {
            return 480; // Default 8 hours
        }
    }

    /**
     * Get user profession from role or jadwal jaga
     */
    private function getUserProfession(User $user): string
    {
        // First try to get from recent jadwal jaga
        $recentJadwal = JadwalJaga::where('pegawai_id', $user->id)
            ->whereNotNull('peran')
            ->orderBy('tanggal_jaga', 'desc')
            ->first();

        if ($recentJadwal && $recentJadwal->peran) {
            return $recentJadwal->peran;
        }

        // Fallback to role-based mapping
        $roleName = $user->role?->name ?? '';

        return match ($roleName) {
            'dokter' => 'Dokter',
            'paramedis', 'perawat' => 'Paramedis',
            'petugas', 'admin', 'bendahara', 'manajer' => 'NonParamedis',
            default => 'Unknown'
        };
    }

    /**
     * Get empty metrics structure
     */
    private function getEmptyMetrics(): array
    {
        return [
            'user_id' => null,
            'staff_name' => null,
            'profession' => 'Unknown',
            'position' => 'Unknown',
            'scheduled_shifts_count' => 0,
            'attended_shifts_count' => 0,
            'total_scheduled_hours' => 0.0,
            'total_working_hours' => 0.0,
            'total_shortfall_minutes' => 0,
            'average_check_in' => null,
            'average_check_out' => null,
            'attendance_percentage' => 0.0,
            'schedule_compliance_rate' => 0.0,
            'gps_validation_rate' => 0.0,
            'punctuality_score' => 0.0,
            'overtime_hours' => 0.0,
            'break_compliance_rate' => 0.0,
            'month' => now()->month,
            'year' => now()->year,
        ];
    }

    /**
     * Get attendance status based on percentage
     */
    public function getAttendanceStatus(float $percentage): string
    {
        return match (true) {
            $percentage >= 95 => 'excellent',
            $percentage >= 85 => 'good',
            $percentage >= 75 => 'average',
            default => 'poor'
        };
    }

    /**
     * Clear cache for user
     */
    public function clearUserCache(int $userId): void
    {
        $currentYear = now()->year;
        $patterns = [
            "attendance_jaga_metrics_{$userId}_*",
            "attendance_recap_cache_{$userId}_*",
        ];

        // Clear for current and previous year
        for ($year = $currentYear - 1; $year <= $currentYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                Cache::forget("attendance_jaga_metrics_{$userId}_{$month}_{$year}");
            }
        }
    }
}
