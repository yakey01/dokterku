<?php

namespace App\Services;

use App\Models\User;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use App\Models\WorkLocation;
use App\Models\ShiftTemplate;
use App\Services\AttendanceToleranceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckInValidationService
{
    /**
     * Comprehensive check-in validation with modular checks
     */
    public function validateCheckIn(User $user, float $latitude, float $longitude, ?float $accuracy, Carbon $date): array
    {
        // 1. Check existing attendance (with multi-shift support)
        $existingAttendance = $this->checkExistingAttendance($user->id, $date);
        if ($existingAttendance['has_attendance']) {
            return $this->formatRejection('ALREADY_CHECKED_IN', $existingAttendance['message'], $existingAttendance);
        }

        // 2. Validate user permissions
        $userPermission = $this->validateUserPermission($user);
        if (!$userPermission['allowed']) {
            return $this->formatRejection('USER_NOT_ALLOWED', $userPermission['message'], $userPermission);
        }

        // 3. Find and validate shift (pass shift sequence from attendance check)
        $shiftSequence = $existingAttendance['shift_sequence'] ?? 1;
        $shiftValidation = $this->validateShift($user, $date, $shiftSequence);
        if (!$shiftValidation['valid']) {
            return $this->formatRejection($shiftValidation['code'], $shiftValidation['message'], $shiftValidation);
        }

        // 4. Validate check-in time window
        $timeValidation = $this->validateCheckInWindow($shiftValidation['shift'], Carbon::now(), $user);
        if (!$timeValidation['valid']) {
            return $this->formatRejection($timeValidation['code'], $timeValidation['message'], $timeValidation);
        }

        // 5. Validate GPS location
        $locationValidation = $this->validateLocation($user, $latitude, $longitude, $accuracy);
        if (!$locationValidation['valid']) {
            return $this->formatRejection($locationValidation['code'], $locationValidation['message'], $locationValidation);
        }

        // 6. Calculate logical timer
        $timerData = $this->calculateLogicalTimer($shiftValidation['shift'], Carbon::now());

        // 7. Prepare metadata
        $metadata = $this->prepareCheckInMetadata(
            $user,
            $shiftValidation,
            $timeValidation,
            $locationValidation,
            $timerData
        );

        // Build response message based on shift sequence
        $message = $timeValidation['is_late'] 
            ? 'Check-in berhasil (terlambat)' 
            : 'Check-in berhasil';
        
        if ($existingAttendance['is_additional_shift'] ?? false) {
            $message .= ' - Shift ke-' . $shiftSequence;
            if ($existingAttendance['is_overtime'] ?? false) {
                $message .= ' (Lembur)';
            }
        }

        return [
            'valid' => true,
            'code' => $timeValidation['is_late'] ? 'VALID_BUT_LATE' : 'VALID',
            'message' => $message,
            'data' => [
                'shift' => $shiftValidation['shift'],
                'jadwal_jaga' => $shiftValidation['jadwal_jaga'],
                'work_location' => $locationValidation['work_location'],
                'logical_time_in' => $timerData['logical_time_in'],
                'is_late' => $timeValidation['is_late'],
                'metadata' => $metadata,
                'multishift_info' => [
                    'shift_sequence' => $shiftSequence,
                    'is_additional_shift' => $existingAttendance['is_additional_shift'] ?? false,
                    'is_overtime' => $existingAttendance['is_overtime'] ?? false,
                    'previous_attendance_id' => $existingAttendance['previous_attendance']->id ?? null,
                    'gap_minutes' => $existingAttendance['gap_minutes'] ?? null
                ]
            ]
        ];
    }

    /**
     * 1. Check existing attendance for the day (with multi-shift support)
     */
    private function checkExistingAttendance(int $userId, Carbon $date): array
    {
        // Get all attendances for today
        $attendances = Attendance::where('user_id', $userId)
            ->whereDate('date', $date)
            ->orderBy('time_in', 'desc')
            ->get();

        if ($attendances->isEmpty()) {
            return ['has_attendance' => false, 'shift_sequence' => 1];
        }

        $lastAttendance = $attendances->first();

        // If last attendance has no checkout, can't check in again
        if (!$lastAttendance->time_out) {
            return [
                'has_attendance' => true,
                'message' => 'Anda masih dalam status check-in. Silakan check-out terlebih dahulu',
                'attendance' => $lastAttendance
            ];
        }

        // Check if multi-shift is enabled
        if (!config('attendance.multishift.enabled')) {
            return [
                'has_attendance' => true,
                'message' => 'Anda sudah menyelesaikan presensi hari ini',
                'attendance' => $lastAttendance
            ];
        }

        // Check max shifts per day
        $maxShifts = config('attendance.multishift.max_shifts_per_day', 3);
        if ($attendances->count() >= $maxShifts) {
            return [
                'has_attendance' => true,
                'message' => "Anda sudah mencapai batas maksimal {$maxShifts} shift per hari",
                'attendance' => $lastAttendance
            ];
        }

        // Check minimum gap between shifts
        $timeSinceLastCheckout = Carbon::parse($lastAttendance->time_out)->diffInMinutes(Carbon::now());
        $minGap = config('attendance.multishift.min_gap_between_shifts', 60);
        
        if ($timeSinceLastCheckout < $minGap) {
            $remainingMinutes = $minGap - $timeSinceLastCheckout;
            return [
                'has_attendance' => true,
                'message' => "Anda harus menunggu {$remainingMinutes} menit lagi sebelum check-in shift berikutnya",
                'attendance' => $lastAttendance
            ];
        }

        // Check maximum gap between shifts
        $maxGap = config('attendance.multishift.max_gap_between_shifts', 720);
        if ($maxGap && $timeSinceLastCheckout > $maxGap) {
            return [
                'has_attendance' => true,
                'message' => "Waktu istirahat terlalu lama. Maksimal gap antar shift adalah {$maxGap} menit",
                'attendance' => $lastAttendance
            ];
        }

        // Allow check-in for next shift
        return [
            'has_attendance' => false,
            'shift_sequence' => $attendances->count() + 1,
            'previous_attendance' => $lastAttendance,
            'gap_minutes' => $timeSinceLastCheckout,
            'is_additional_shift' => true,
            'is_overtime' => $attendances->count() >= config('attendance.multishift.overtime_after_shifts', 2)
        ];
    }

    /**
     * 2. Validate user has permission to check in
     */
    private function validateUserPermission(User $user): array
    {
        // Check if user is active
        if (!$user->is_active) {
            return [
                'allowed' => false,
                'message' => 'Akun Anda tidak aktif. Hubungi administrator'
            ];
        }

        // Check if user has attendance permission based on Spatie roles
        $allowedRoles = config('attendance.allowed_roles', [
            'perawat', 'bidan', 'analis', 'paramedis', 
            'paramedis-lainnya', 'nonparamedis', 'petugas',
            'dokter', 'dokter-gigi'
        ]);

        // Check using Spatie roles - handle both array and single role
        try {
            $hasRole = false;
            $userRoles = $user->getRoleNames();
            
            // Check if user has any of the allowed roles
            foreach ($allowedRoles as $role) {
                if ($userRoles->contains($role)) {
                    $hasRole = true;
                    break;
                }
            }
            
            if (!$hasRole) {
                return [
                    'allowed' => false,
                    'message' => 'Role Anda tidak memiliki akses presensi. Role saat ini: ' . $userRoles->implode(', ')
                ];
            }
        } catch (\Exception $e) {
            // Fallback: Allow for now if role check fails
            Log::warning('Role check failed for user ' . $user->id . ': ' . $e->getMessage());
        }

        return ['allowed' => true];
    }

    /**
     * 3. Validate user has active shift for today (with multi-shift support)
     */
    private function validateShift(User $user, Carbon $date, int $shiftSequence = 1): array
    {
        // Get all schedules for today
        $jadwalJagas = JadwalJaga::where(function($query) use ($user) {
                $query->where('pegawai_id', $user->id)
                      ->orWhere('user_id', $user->id);
            })
            ->whereDate('tanggal_jaga', $date)
            ->with(['shiftTemplate'])
            ->orderBy('shift_sequence')
            ->get();

        if ($jadwalJagas->isEmpty()) {
            return [
                'valid' => false,
                'code' => 'NO_SCHEDULE',
                'message' => 'Anda tidak memiliki jadwal jaga hari ini'
            ];
        }

        // For multi-shift, find the appropriate schedule
        $jadwalJaga = null;
        $currentTime = Carbon::now();
        
        foreach ($jadwalJagas as $jadwal) {
            if (!$jadwal->shiftTemplate) continue;
            
            // Check if this shift hasn't been used yet
            $shiftUsed = Attendance::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->where('jadwal_jaga_id', $jadwal->id)
                ->exists();
            
            if ($shiftUsed) continue;
            
            // Check if current time is appropriate for this shift
            $jamMasuk = $jadwal->shiftTemplate->jam_masuk;
            if ($jamMasuk instanceof Carbon) {
                $jamMasuk = $jamMasuk->format('H:i:s');
            } elseif (strpos($jamMasuk, ' ') !== false) {
                // If it contains a date, extract just the time
                $jamMasuk = Carbon::parse($jamMasuk)->format('H:i:s');
            }
            $shiftStart = Carbon::parse($date->format('Y-m-d') . ' ' . $jamMasuk);
            
            // Use unified attendance tolerance service
            $toleranceService = new AttendanceToleranceService();
            $toleranceData = $toleranceService->getCheckinTolerance($user);
            $toleranceEarly = $toleranceData['early'];
            $windowStart = $shiftStart->copy()->subMinutes($toleranceEarly);
            
            // DEVELOPMENT MODE: Allow early access to shifts for testing
            if (in_array(config('app.env'), ['local', 'development', 'dev'])) {
                // For development, allow access to any unused shift regardless of time
                $jadwalJaga = $jadwal;
                break;
            } else {
                // Production mode: Check time window
                if ($currentTime->greaterThanOrEqualTo($windowStart)) {
                    $jadwalJaga = $jadwal;
                    break;
                }
            }
        }

        if (!$jadwalJaga) {
            // Check if all shifts have been used
            // FIXED: Count unique jadwal_jaga_id instead of total attendance records
            $usedShifts = Attendance::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->whereNotNull('jadwal_jaga_id')
                ->distinct('jadwal_jaga_id')
                ->count('jadwal_jaga_id');
            
            if ($usedShifts >= $jadwalJagas->count()) {
                return [
                    'valid' => false,
                    'code' => 'ALL_SHIFTS_COMPLETED',
                    'message' => 'Semua shift hari ini sudah selesai'
                ];
            }
            
            return [
                'valid' => false,
                'code' => 'NO_AVAILABLE_SHIFT',
                'message' => 'Tidak ada shift yang tersedia untuk waktu ini'
            ];
        }

        // Get shift details
        $shift = $jadwalJaga->shiftTemplate;
        
        // Get work location (if exists in jadwal_jaga or use first active)
        $workLocation = null;
        if (isset($jadwalJaga->work_location_id)) {
            $workLocation = WorkLocation::find($jadwalJaga->work_location_id);
        }
        if (!$workLocation) {
            $workLocation = WorkLocation::where('is_active', true)->first();
        }
        
        return [
            'valid' => true,
            'jadwal_jaga' => $jadwalJaga,
            'shift' => $shift,
            'work_location' => $workLocation
        ];
    }

    /**
     * 4. Validate check-in is within allowed time window
     */
    private function validateCheckInWindow(ShiftTemplate $shift, Carbon $currentTime, User $user = null): array
    {
        // DEVELOPMENT MODE: Bypass time validation for testing
        if (in_array(config('app.env'), ['local', 'development', 'dev'])) {
            return [
                'valid' => true,
                'is_late' => false,
                'shift_start' => '00:00:00',
                'actual_time' => $currentTime->format('H:i:s'),
                'window' => [
                    'start' => '00:00:00',
                    'end' => '23:59:59'
                ],
                'development_mode' => true
            ];
        }
        
        $today = $currentTime->format('Y-m-d');
        
        // Handle different formats of shift times
        $startTime = $shift->jam_masuk;
        if ($startTime instanceof Carbon) {
            $startTime = $startTime->format('H:i:s');
        } elseif (strpos($startTime, ' ') !== false) {
            // If it already contains a date, extract just the time
            $startTime = Carbon::parse($startTime)->format('H:i:s');
        }
        
        $endTime = $shift->jam_pulang ?? $shift->jam_keluar;
        if ($endTime instanceof Carbon) {
            $endTime = $endTime->format('H:i:s');
        } elseif (strpos($endTime, ' ') !== false) {
            // If it already contains a date, extract just the time
            $endTime = Carbon::parse($endTime)->format('H:i:s');
        }
        
        $shiftStart = Carbon::parse($today . ' ' . $startTime);
        $shiftEnd = Carbon::parse($today . ' ' . $endTime);
        
        // Handle overnight shifts
        if ($shiftEnd->lessThan($shiftStart)) {
            $shiftEnd->addDay();
        }

        // Get tolerance settings from AttendanceToleranceService (with fallback to config)
        if ($user) {
            $toleranceService = app(AttendanceToleranceService::class);
            $toleranceData = $toleranceService->getCheckinTolerance($user, $currentTime);
            $toleranceEarly = $toleranceData['early'];
            $toleranceLate = $toleranceData['late'];
        } else {
            // Fallback to config for backward compatibility
            $toleranceEarly = config('attendance.check_in_tolerance_early', 30); // minutes
            $toleranceLate = config('attendance.check_in_tolerance_late', 60); // minutes
        }

        // Calculate window
        $windowStart = $shiftStart->copy()->subMinutes($toleranceEarly);
        $windowEnd = $shiftStart->copy()->addMinutes($toleranceLate);

        // Check if current time is within window
        if ($currentTime->lessThan($windowStart)) {
            $minutesEarly = $currentTime->diffInMinutes($windowStart);
            return [
                'valid' => false,
                'code' => 'TOO_EARLY',
                'message' => "Check-in terlalu awal. Silakan check-in mulai pukul {$windowStart->format('H:i')} ({$minutesEarly} menit lagi)",
                'window_start' => $windowStart->format('H:i:s'),
                'current_time' => $currentTime->format('H:i:s')
            ];
        }

        if ($currentTime->greaterThan($windowEnd)) {
            $minutesLate = $windowEnd->diffInMinutes($currentTime);
            return [
                'valid' => false,
                'code' => 'TOO_LATE',
                'message' => "Check-in sudah ditutup. Batas check-in adalah pukul {$windowEnd->format('H:i')} ({$minutesLate} menit yang lalu)",
                'window_end' => $windowEnd->format('H:i:s'),
                'current_time' => $currentTime->format('H:i:s')
            ];
        }

        // Check if late (after shift start but within tolerance)
        $isLate = $currentTime->greaterThan($shiftStart);

        return [
            'valid' => true,
            'is_late' => $isLate,
            'shift_start' => $shiftStart->format('H:i:s'),
            'actual_time' => $currentTime->format('H:i:s'),
            'window' => [
                'start' => $windowStart->format('H:i:s'),
                'end' => $windowEnd->format('H:i:s')
            ]
        ];
    }

    /**
     * 5. Validate GPS location is within work area
     */
    private function validateLocation(User $user, float $latitude, float $longitude, ?float $accuracy): array
    {
        // Check GPS accuracy if provided
        $maxAccuracy = config('attendance.max_gps_accuracy', 50); // meters
        if ($accuracy && $accuracy > $maxAccuracy) {
            return [
                'valid' => false,
                'code' => 'GPS_NOT_ACCURATE',
                'message' => "Akurasi GPS tidak mencukupi ({$accuracy}m). Maksimal {$maxAccuracy}m",
                'accuracy' => $accuracy
            ];
        }

        // Get user's work locations
        $workLocations = WorkLocation::where('is_active', true)->get();
        
        if ($workLocations->isEmpty()) {
            // Fallback: allow check-in if no work locations defined
            Log::warning('No active work locations defined for validation');
            return [
                'valid' => true,
                'work_location' => null,
                'distance' => null,
                'warning' => 'No work locations configured'
            ];
        }

        // Find nearest valid location
        $nearestLocation = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($workLocations as $location) {
            $distance = $this->calculateDistance(
                $latitude, 
                $longitude,
                $location->latitude,
                $location->longitude
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestLocation = $location;
            }

            // Check if within radius
            $allowedRadius = $location->radius_meters ?? $location->radius ?? config('attendance.default_location_radius', 100);
            if ($distance <= $allowedRadius) {
                return [
                    'valid' => true,
                    'work_location' => $location,
                    'distance' => round($distance, 2),
                    'allowed_radius' => $allowedRadius
                ];
            }
        }

        return [
            'valid' => false,
            'code' => 'OUTSIDE_WORK_AREA',
            'message' => "Anda berada " . round($minDistance) . "m dari lokasi kerja terdekat. Maksimal radius " . ($nearestLocation->radius_meters ?? $nearestLocation->radius ?? 100) . "m",
            'nearest_location' => $nearestLocation,
            'distance' => round($minDistance, 2)
        ];
    }

    /**
     * 6. Calculate logical timer based on shift rules
     */
    private function calculateLogicalTimer(ShiftTemplate $shift, Carbon $actualCheckIn): array
    {
        $today = $actualCheckIn->format('Y-m-d');
        
        // Handle different formats of shift times
        $startTime = $shift->jam_masuk;
        if ($startTime instanceof Carbon) {
            $startTime = $startTime->format('H:i:s');
        } elseif (strpos($startTime, ' ') !== false) {
            // If it already contains a date, extract just the time
            $startTime = Carbon::parse($startTime)->format('H:i:s');
        }
        
        $shiftStart = Carbon::parse($today . ' ' . $startTime);
        
        // Logical timer starts at shift start if check-in is early
        // Otherwise starts at actual check-in time
        $logicalTimeIn = $actualCheckIn->lessThan($shiftStart) 
            ? $shiftStart 
            : $actualCheckIn;

        return [
            'actual_time_in' => $actualCheckIn->format('H:i:s'),
            'shift_start' => $shiftStart->format('H:i:s'),
            'logical_time_in' => $logicalTimeIn->format('H:i:s'),
            'is_early' => $actualCheckIn->lessThan($shiftStart),
            'early_minutes' => $actualCheckIn->lessThan($shiftStart) 
                ? $actualCheckIn->diffInMinutes($shiftStart) 
                : 0
        ];
    }

    /**
     * 7. Prepare comprehensive metadata for storage
     */
    private function prepareCheckInMetadata(
        User $user,
        array $shiftData,
        array $timeData,
        array $locationData,
        array $timerData
    ): array {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role
            ],
            'shift' => [
                'id' => $shiftData['shift']->id,
                'name' => $shiftData['shift']->nama_shift,
                'start' => $shiftData['shift']->jam_masuk,
                'end' => $shiftData['shift']->jam_pulang ?? $shiftData['shift']->jam_keluar
            ],
            'validation' => [
                'time' => $timeData,
                'location' => [
                    'work_location_id' => $locationData['work_location']->id ?? null,
                    'work_location_name' => $locationData['work_location']->name ?? null,
                    'distance' => $locationData['distance'] ?? null,
                    'accuracy' => $locationData['accuracy'] ?? null
                ],
                'timer' => $timerData
            ],
            'timestamp' => Carbon::now()->toIso8601String()
        ];
    }

    /**
     * Format rejection response
     */
    private function formatRejection(string $code, string $message, array $data = []): array
    {
        return [
            'valid' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => Carbon::now()->toIso8601String()
        ];
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}