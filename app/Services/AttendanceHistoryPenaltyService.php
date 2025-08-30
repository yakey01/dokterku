<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk menangani logic history presensi dengan penalty
 * Khusus untuk skenario check-in tanpa check-out yang melewati toleransi
 */
class AttendanceHistoryPenaltyService
{
    protected AttendanceToleranceService $toleranceService;
    
    public function __construct(AttendanceToleranceService $toleranceService)
    {
        $this->toleranceService = $toleranceService;
    }
    
    /**
     * Implementasi logic untuk skenario Dr. Yaya:
     * - Jadwal Jaga: 17:30 - 18:30
     * - Check-in: Berhasil masuk
     * - Tidak check-out dan melewati toleransi
     * - History: Harus tercatat 1 menit kerja
     */
    public function handleToleranceExceededScenario(int $attendanceId): array
    {
        $attendance = Attendance::with(['user', 'jadwalJaga.shiftTemplate'])->find($attendanceId);
        
        if (!$attendance || !$attendance->user) {
            return ['success' => false, 'message' => 'Attendance not found'];
        }
        
        $user = $attendance->user;
        $now = Carbon::now('Asia/Jakarta');
        
        // 1. Parse waktu check-in
        $checkInTime = $this->parseCheckInTime($attendance);
        
        // 2. Tentukan shift end time dari jadwal jaga
        $shiftEndTime = $this->determineShiftEndTime($attendance, $checkInTime);
        
        // 3. Get tolerance setting untuk user
        $toleranceData = $this->toleranceService->getCheckoutTolerance($user, $attendance->date);
        $lateTolerance = $toleranceData['late'] ?? 60; // Default 60 menit
        
        // 4. Hitung maximum checkout time
        $maxCheckoutTime = $shiftEndTime->copy()->addMinutes($lateTolerance);
        
        // 5. Cek apakah sudah melewati toleransi
        if ($now->gt($maxCheckoutTime)) {
            return $this->applyPenaltyLogic($attendance, $checkInTime, $maxCheckoutTime, $toleranceData);
        }
        
        return [
            'success' => false, 
            'message' => 'Belum melewati batas toleransi',
            'remaining_minutes' => $now->diffInMinutes($maxCheckoutTime)
        ];
    }
    
    /**
     * Apply 1-minute penalty logic seperti skenario Dr. Yaya
     */
    protected function applyPenaltyLogic(
        Attendance $attendance, 
        Carbon $checkInTime, 
        Carbon $maxCheckoutTime,
        array $toleranceData
    ): array {
        $now = Carbon::now('Asia/Jakarta');
        
        // ⚡ CORE LOGIC: Auto-checkout dengan 1 menit penalty
        $penaltyCheckoutTime = $checkInTime->copy()->addMinute();
        
        // Ensure tidak melebihi waktu sekarang
        if ($penaltyCheckoutTime->gt($now)) {
            $penaltyCheckoutTime = $now->copy();
        }
        
        // Update attendance record
        $attendance->time_out = $penaltyCheckoutTime->format('H:i:s');
        $attendance->logical_time_out = $penaltyCheckoutTime->format('H:i:s');
        $attendance->logical_work_minutes = 1; // 🎯 1 MENIT PENALTY
        
        // Enhanced metadata untuk audit trail
        $attendance->check_out_metadata = array_merge(
            $attendance->check_out_metadata ?? [],
            [
                'auto_closed' => true,
                'auto_close_reason' => 'exceeded_checkout_tolerance',
                'penalty_applied' => true,
                'penalty_work_minutes' => 1,
                'tolerance_minutes' => $toleranceData['late'],
                'max_checkout_time' => $maxCheckoutTime->format('Y-m-d H:i:s'),
                'auto_closed_at' => $now->format('Y-m-d H:i:s'),
                'exceeded_by_minutes' => $now->diffInMinutes($maxCheckoutTime),
                'tolerance_source' => $toleranceData['source'],
                'scenario_type' => 'dr_yaya_case', // Identifier untuk skenario ini
            ]
        );
        
        // Update notes
        $attendance->notes = ($attendance->notes ? $attendance->notes . ' | ' : '') 
            . "Auto-closed: Exceeded checkout tolerance (1 minute work time penalty)";
        
        $attendance->save();
        
        // Log untuk tracking
        Log::warning('Applied 1-minute penalty for tolerance violation', [
            'attendance_id' => $attendance->id,
            'user_id' => $attendance->user_id,
            'user_name' => $attendance->user->name,
            'check_in' => $checkInTime->format('H:i:s'),
            'auto_check_out' => $penaltyCheckoutTime->format('H:i:s'),
            'penalty_minutes' => 1,
            'exceeded_by_minutes' => $now->diffInMinutes($maxCheckoutTime),
            'scenario' => 'dr_yaya_tolerance_exceeded'
        ]);
        
        return [
            'success' => true,
            'message' => 'Penalty 1 menit berhasil diterapkan',
            'data' => [
                'attendance_id' => $attendance->id,
                'check_in_time' => $checkInTime->format('H:i'),
                'auto_check_out_time' => $penaltyCheckoutTime->format('H:i'),
                'work_duration_minutes' => 1,
                'work_duration_formatted' => '1 menit',
                'penalty_reason' => 'Melewati batas toleransi checkout',
                'exceeded_by_minutes' => $now->diffInMinutes($maxCheckoutTime),
                'tolerance_source' => $toleranceData['source']
            ]
        ];
    }
    
    /**
     * Parse check-in time dengan handling berbagai format
     */
    protected function parseCheckInTime(Attendance $attendance): Carbon
    {
        $dateString = $attendance->date instanceof Carbon 
            ? $attendance->date->format('Y-m-d') 
            : $attendance->date;
        
        if ($attendance->time_in instanceof Carbon) {
            return $attendance->time_in->setTimezone('Asia/Jakarta');
        } elseif (strpos($attendance->time_in, '-') !== false) {
            return Carbon::parse($attendance->time_in, 'Asia/Jakarta');
        } else {
            return Carbon::parse($dateString . ' ' . $attendance->time_in, 'Asia/Jakarta');
        }
    }
    
    /**
     * Tentukan shift end time dari jadwal jaga atau fallback
     */
    protected function determineShiftEndTime(Attendance $attendance, Carbon $checkInTime): Carbon
    {
        $dateString = $attendance->date instanceof Carbon 
            ? $attendance->date->format('Y-m-d') 
            : $attendance->date;
        
        // Prioritas: shift_end di attendance
        if ($attendance->shift_end) {
            if ($attendance->shift_end instanceof Carbon) {
                return Carbon::parse($dateString . ' ' . $attendance->shift_end->format('H:i:s'), 'Asia/Jakarta');
            } else {
                return Carbon::parse($dateString . ' ' . $attendance->shift_end, 'Asia/Jakarta');
            }
        }
        
        // Fallback: jadwal jaga
        if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
            $shift = $attendance->jadwalJaga->shiftTemplate;
            if ($shift->jam_pulang) {
                return Carbon::parse($dateString . ' ' . $shift->jam_pulang, 'Asia/Jakarta');
            }
        }
        
        // Default fallback: check-in + 8 jam (sesuai skenario Dr. Yaya: 17:30-18:30 = 1 jam, tapi sistem default 8 jam)
        return $checkInTime->copy()->addHours(8);
    }
    
    /**
     * Get detailed history untuk attendance dengan penalty
     */
    public function getPenaltyAttendanceHistory(int $userId, array $filters = []): array
    {
        $query = Attendance::where('user_id', $userId)
            ->whereNotNull('logical_work_minutes')
            ->where('logical_work_minutes', '<=', 5) // Attendance dengan penalty (≤ 5 menit)
            ->with(['jadwalJaga.shiftTemplate']);
        
        // Filter by date if specified
        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }
        
        $attendances = $query->orderBy('date', 'desc')->get();
        
        return $attendances->map(function ($attendance) {
            $metadata = $attendance->check_out_metadata ?? [];
            
            return [
                'id' => $attendance->id,
                'date' => $attendance->date->format('Y-m-d'),
                'day_name' => $attendance->date->format('l'),
                'time_in' => $attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i') : null,
                'time_out' => $attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i') : null,
                'work_duration_minutes' => $attendance->logical_work_minutes ?? 0,
                'work_duration_formatted' => ($attendance->logical_work_minutes ?? 0) . ' menit',
                'shift_schedule' => $this->getShiftScheduleInfo($attendance),
                'penalty_info' => [
                    'is_penalty' => true,
                    'penalty_reason' => $metadata['auto_close_reason'] ?? 'Unknown',
                    'exceeded_by_minutes' => $metadata['exceeded_by_minutes'] ?? 0,
                    'tolerance_minutes' => $metadata['tolerance_minutes'] ?? 60,
                    'auto_closed_at' => $metadata['auto_closed_at'] ?? null,
                    'tolerance_source' => $metadata['tolerance_source'] ?? 'Default'
                ],
                'notes' => $attendance->notes,
                'status' => $this->determinePenaltyStatus($attendance)
            ];
        })->toArray();
    }
    
    /**
     * Get shift schedule info for display
     */
    protected function getShiftScheduleInfo(Attendance $attendance): array
    {
        if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
            $shift = $attendance->jadwalJaga->shiftTemplate;
            return [
                'shift_name' => $shift->nama_shift ?? 'Shift Umum',
                'scheduled_start' => $shift->jam_masuk ? Carbon::parse($shift->jam_masuk)->format('H:i') : null,
                'scheduled_end' => $shift->jam_pulang ? Carbon::parse($shift->jam_pulang)->format('H:i') : null,
                'shift_duration' => $shift->jam_masuk && $shift->jam_pulang ? 
                    Carbon::parse($shift->jam_masuk)->diffInHours(Carbon::parse($shift->jam_pulang)) . ' jam' : null
            ];
        }
        
        return [
            'shift_name' => 'Tidak ada jadwal',
            'scheduled_start' => null,
            'scheduled_end' => null,
            'shift_duration' => null
        ];
    }
    
    /**
     * Determine penalty status for display
     */
    protected function determinePenaltyStatus(Attendance $attendance): string
    {
        $workMinutes = $attendance->logical_work_minutes ?? 0;
        
        if ($workMinutes == 1) {
            return 'Penalty 1 Menit';
        } elseif ($workMinutes <= 5) {
            return "Penalty {$workMinutes} Menit";
        }
        
        return 'Incomplete Checkout';
    }
}