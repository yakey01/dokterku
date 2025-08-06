<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSchedule;
use App\Models\AttendanceToleranceSetting;
use App\Models\AttendanceViolation;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ScheduleAttendanceService
{
    /**
     * Check if user can perform attendance action (check-in/check-out)
     */
    public function canPerformAttendance(User $user, string $action, Carbon $datetime = null): array
    {
        $datetime = $datetime ?? now();
        
        // Get user's active schedule for this date
        $schedule = $this->getUserScheduleForDate($user, $datetime);
        
        if (!$schedule) {
            return [
                'allowed' => false,
                'reason' => 'no_schedule',
                'message' => 'Anda tidak memiliki jadwal kerja untuk hari ini. Silakan hubungi administrator untuk mengatur jadwal kerja Anda.',
                'schedule' => null,
                'tolerance' => null,
                'scheduled_time' => null,
                'tolerance_window' => null,
                'violation_minutes' => 0
            ];
        }

        // Get applicable tolerance settings
        $tolerance = $this->getToleranceSettings($user, $schedule, $datetime);
        
        // Validate attendance timing
        $validation = $this->validateAttendanceTiming($schedule, $action, $datetime, $tolerance);
        
        return [
            'allowed' => $validation['allowed'],
            'reason' => $validation['reason'] ?? null,
            'message' => $validation['message'] ?? 'Attendance allowed',
            'schedule' => $schedule,
            'tolerance' => $tolerance,
            'scheduled_time' => $validation['scheduled_time'] ?? null,
            'tolerance_window' => $validation['tolerance_window'] ?? null,
            'violation_minutes' => $validation['violation_minutes'] ?? null
        ];
    }

    /**
     * Get user's active schedule for a specific date
     */
    public function getUserScheduleForDate(User $user, Carbon $date): ?UserSchedule
    {
        // Cache key for performance
        $cacheKey = "user_schedule_{$user->id}_{$date->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user, $date) {
            return UserSchedule::active()
                ->forUser($user->id)
                ->forDate($date)
                ->where(function ($query) use ($date) {
                    $query->where('is_recurring', true)
                          ->orWhere('schedule_date', $date->format('Y-m-d'));
                })
                ->orderBy('priority', 'desc') // Higher priority schedules first
                ->first();
        });
    }

    /**
     * Get applicable tolerance settings for user
     */
    public function getToleranceSettings(User $user, UserSchedule $schedule, Carbon $datetime): AttendanceToleranceSetting
    {
        $cacheKey = "tolerance_settings_{$user->id}_{$schedule->id}";
        
        return Cache::remember($cacheKey, 1800, function () use ($user, $schedule) {
            // Get all applicable tolerance settings, ordered by priority
            $settings = AttendanceToleranceSetting::active()
                ->byPriority()
                ->get()
                ->filter(function ($setting) use ($user) {
                    return $setting->appliesToUser($user);
                });

            // Return the highest priority setting, or create default if none found
            return $settings->first() ?? $this->getDefaultToleranceSetting();
        });
    }

    /**
     * Validate attendance timing against schedule and tolerance
     */
    protected function validateAttendanceTiming(UserSchedule $schedule, string $action, Carbon $datetime, AttendanceToleranceSetting $tolerance): array
    {
        $isWeekend = $datetime->isWeekend();
        $isHoliday = $this->isHoliday($datetime);
        
        // Get scheduled time for the action
        $scheduledTime = $action === 'checkin' 
            ? $schedule->getCheckInDateTime($datetime)
            : $schedule->getCheckOutDateTime($datetime);

        // Get tolerance settings for this action
        $toleranceSettings = $tolerance->getToleranceForAction($action, $isWeekend, $isHoliday);
        
        // Calculate tolerance window
        $earlyWindow = $scheduledTime->copy()->subMinutes($toleranceSettings['early']);
        $lateWindow = $scheduledTime->copy()->addMinutes($toleranceSettings['late']);
        
        // Check if current time is within tolerance
        if ($datetime->between($earlyWindow, $lateWindow)) {
            return [
                'allowed' => true,
                'scheduled_time' => $scheduledTime,
                'tolerance_window' => [
                    'early' => $earlyWindow,
                    'late' => $lateWindow
                ]
            ];
        }

        // Calculate violation
        $violationMinutes = 0;
        $violationType = '';
        
        if ($datetime->lt($earlyWindow)) {
            $violationMinutes = $earlyWindow->diffInMinutes($datetime);
            $violationType = $action === 'checkin' ? 'early_checkin' : 'early_checkout';
            $canProceed = $action === 'checkin' ? $tolerance->allow_early_checkin : $tolerance->allow_early_checkout;
        } else {
            $violationMinutes = $datetime->diffInMinutes($lateWindow);
            $violationType = $action === 'checkin' ? 'late_checkin' : 'late_checkout';
            $canProceed = $action === 'checkin' ? $tolerance->allow_late_checkin : $tolerance->allow_late_checkout;
        }

        return [
            'allowed' => $canProceed,
            'reason' => $violationType,
            'message' => $this->getViolationMessage($violationType, $violationMinutes),
            'scheduled_time' => $scheduledTime,
            'tolerance_window' => [
                'early' => $earlyWindow,
                'late' => $lateWindow
            ],
            'violation_minutes' => $violationMinutes
        ];
    }

    /**
     * Record attendance with schedule validation
     */
    public function recordAttendance(User $user, string $action, array $data, bool $emergencyOverride = false): array
    {
        $datetime = isset($data['datetime']) ? Carbon::parse($data['datetime']) : now();
        
        // Check if attendance is allowed
        $validation = $this->canPerformAttendance($user, $action, $datetime);
        
        if (!$validation['allowed'] && !$emergencyOverride) {
            // Record violation
            $this->recordViolation($user, $action, $validation, $data);
            
            return [
                'success' => false,
                'message' => $validation['message'],
                'violation_recorded' => true
            ];
        }

        // Record the attendance
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'user_schedule_id' => $validation['schedule']->id ?? null,
            'date' => $datetime->format('Y-m-d'),
            $action === 'checkin' ? 'time_in' : 'time_out' => $datetime,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'location_name' => $data['location_name'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_emergency_override' => $emergencyOverride
        ]);

        // If there was a violation but emergency override was used
        if (!$validation['allowed'] && $emergencyOverride) {
            $this->recordViolation($user, $action, $validation, $data, $attendance->id, true);
        }

        return [
            'success' => true,
            'attendance' => $attendance,
            'message' => 'Attendance recorded successfully'
        ];
    }

    /**
     * Record attendance violation
     */
    protected function recordViolation(User $user, string $action, array $validation, array $data, int $attendanceId = null, bool $isOverride = false): void
    {
        AttendanceViolation::create([
            'user_id' => $user->id,
            'attendance_id' => $attendanceId,
            'user_schedule_id' => $validation['schedule']->id ?? null,
            'violation_type' => $validation['reason'],
            'action_type' => $action,
            'attempted_at' => isset($data['datetime']) ? Carbon::parse($data['datetime']) : now(),
            'scheduled_at' => $validation['scheduled_time'] ?? null,
            'tolerance_minutes' => $this->calculateToleranceMinutes($validation),
            'violation_minutes' => $validation['violation_minutes'] ?? 0,
            'severity' => $this->calculateViolationSeverity($validation['violation_minutes'] ?? 0),
            'location_attempted' => $data['location_name'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'reason' => $data['reason'] ?? null,
            'is_emergency_override' => $isOverride,
            'overridden_by' => $isOverride ? auth()->id() : null,
            'override_reason' => $isOverride ? ($data['override_reason'] ?? 'Emergency override') : null
        ]);
    }

    /**
     * Get default tolerance setting
     */
    protected function getDefaultToleranceSetting(): AttendanceToleranceSetting
    {
        return new AttendanceToleranceSetting([
            'setting_name' => 'Default System Settings',
            'scope_type' => 'global',
            'check_in_early_tolerance' => 15,
            'check_in_late_tolerance' => 15,
            'check_out_early_tolerance' => 30,
            'check_out_late_tolerance' => 30,
            'require_schedule_match' => true,
            'allow_early_checkin' => true,
            'allow_late_checkin' => true,
            'allow_early_checkout' => false,
            'allow_late_checkout' => true,
        ]);
    }

    /**
     * Check if date is a holiday
     */
    protected function isHoliday(Carbon $date): bool
    {
        // You can implement holiday checking logic here
        // This could be from a database table, external API, or configuration
        return false;
    }

    /**
     * Get violation message
     */
    protected function getViolationMessage(string $violationType, int $violationMinutes): string
    {
        $messages = [
            'early_checkin' => "⏰ Check-in terlalu awal! Anda mencoba check-in {$violationMinutes} menit sebelum jadwal yang ditetapkan. Silakan tunggu hingga waktu check-in yang diperbolehkan.",
            'late_checkin' => "⏰ Check-in terlambat! Anda terlambat {$violationMinutes} menit dari batas waktu toleransi. Silakan hubungi atasan Anda jika ini adalah situasi darurat.",
            'early_checkout' => "⏰ Check-out terlalu awal! Anda mencoba check-out {$violationMinutes} menit sebelum jadwal berakhir. Pastikan Anda telah menyelesaikan tugas harian Anda terlebih dahulu.",
            'late_checkout' => "⏰ Check-out terlambat! Anda melewati {$violationMinutes} menit dari batas waktu normal. Terima kasih atas dedikasi kerja Anda.",
            'no_schedule' => "📅 Tidak ada jadwal kerja untuk hari ini. Silakan hubungi administrator untuk mengatur jadwal kerja Anda atau pastikan Anda memiliki jadwal yang aktif.",
            'location_mismatch' => "📍 Lokasi Anda tidak sesuai dengan lokasi kerja yang ditetapkan. Pastikan Anda berada di area kerja yang benar sebelum melakukan check-in/check-out.",
            'weekend_not_allowed' => "📅 Check-in di akhir pekan tidak diperbolehkan sesuai kebijakan perusahaan. Jika ini adalah situasi darurat, silakan hubungi supervisor Anda.",
            'holiday_not_allowed' => "🎉 Check-in pada hari libur tidak diperbolehkan. Jika Anda perlu bekerja pada hari libur, silakan koordinasikan dengan atasan terlebih dahulu."
        ];

        return $messages[$violationType] ?? '⚠️ Terjadi pelanggaran aturan presensi. Silakan hubungi administrator untuk bantuan lebih lanjut.';
    }

    /**
     * Calculate violation severity
     */
    protected function calculateViolationSeverity(int $violationMinutes): string
    {
        if ($violationMinutes <= 5) return 'minor';
        if ($violationMinutes <= 15) return 'moderate';
        if ($violationMinutes <= 30) return 'major';
        return 'critical';
    }

    /**
     * Calculate tolerance minutes for violation record
     */
    protected function calculateToleranceMinutes(array $validation): int
    {
        if (!isset($validation['tolerance_window'])) {
            return 0;
        }

        $window = $validation['tolerance_window'];
        $scheduled = $validation['scheduled_time'];
        
        return $window['early']->diffInMinutes($scheduled) + $scheduled->diffInMinutes($window['late']);
    }
}