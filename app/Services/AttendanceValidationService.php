<?php

namespace App\Services;

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AttendanceValidationService
{
    /**
     * Validate user's schedule for today
     */
    public function validateSchedule(User $user, Carbon $date = null): array
    {
        $date = $date ? $date->copy()->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta')->startOfDay();
        $currentTime = Carbon::now('Asia/Jakarta');
        
        // Get user's schedule for today
        $jadwalJaga = JadwalJaga::where('pegawai_id', $user->id)
            ->whereDate('tanggal_jaga', $date)
            ->with('shiftTemplate')
            ->first();
        
        if (!$jadwalJaga) {
            \Log::warning('Schedule validation failed - no schedule found', [
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'user_role' => $user->role ?? 'unknown',
                'user_name' => $user->name
            ]);
            
            return [
                'valid' => false,
                'message' => 'Anda tidak memiliki jadwal jaga hari ini. Hubungi admin untuk informasi lebih lanjut.',
                'code' => 'NO_SCHEDULE'
            ];
        }
        
        // Check status with case insensitive comparison and debug logging
        if (strtolower((string) $jadwalJaga->status_jaga) !== 'aktif') {
            \Log::warning('Schedule validation failed - inactive status', [
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'jadwal_jaga_id' => $jadwalJaga->id,
                'status_jaga' => $jadwalJaga->status_jaga,
                'status_lowercase' => strtolower($jadwalJaga->status_jaga)
            ]);
            
            return [
                'valid' => false,
                'message' => "Jadwal jaga Anda hari ini berstatus '{$jadwalJaga->status_jaga}'. Hubungi admin untuk informasi lebih lanjut.",
                'code' => 'SCHEDULE_INACTIVE',
                'schedule_status' => $jadwalJaga->status_jaga
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Jadwal jaga valid',
            'code' => 'VALID_SCHEDULE',
            'jadwal_jaga' => $jadwalJaga
        ];
    }
    
    /**
     * Validate user's work location and geofencing with enhanced diagnostics
     */
    public function validateWorkLocation(User $user, float $latitude, float $longitude, ?float $accuracy = null): array
    {
        // Always refresh user relationship to get latest work location data
        $user->load(['workLocation', 'location']);
        
        // Clear any cached work location data
        Cache::forget("user_work_location_{$user->id}");
        
        // Get fresh work location data (force refresh from database)
        $workLocation = WorkLocation::where('id', $user->work_location_id)
            ->where('is_active', true)
            ->first();
        
        \Log::info('Work location validation debug', [
            'user_id' => $user->id,
            'user_work_location_id' => $user->work_location_id,
            'work_location_found' => $workLocation ? $workLocation->id : 'none',
            'work_location_active' => $workLocation ? $workLocation->is_active : 'n/a'
        ]);
        
        if (!$workLocation) {
            // Double-check by loading fresh user data
            $freshUser = User::find($user->id);
            if ($freshUser && $freshUser->work_location_id) {
                $workLocation = WorkLocation::find($freshUser->work_location_id);
            }
            
            // Fallback to legacy location if work location not set
            if (!$workLocation) {
                $location = $user->location;
                if (!$location) {
                    // Log for debugging
                    \Log::warning('User has no work location assigned', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'work_location_id' => $user->work_location_id,
                        'location_id' => $user->location_id
                    ]);
                    
                    \Log::warning('Work location validation failed - no location assigned', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'work_location_id' => $user->work_location_id,
                        'location_id' => $user->location_id
                    ]);
                    
                    return [
                        'valid' => false,
                        'message' => 'Anda belum memiliki lokasi kerja yang ditetapkan. Hubungi admin untuk pengaturan.',
                        'code' => 'NO_WORK_LOCATION'
                    ];
                }
                
                // Use legacy location validation
                if (!$location->isWithinGeofence($latitude, $longitude)) {
                    $distance = $location->getDistanceFrom($latitude, $longitude);
                    return [
                        'valid' => false,
                        'message' => "Anda berada di luar area kerja yang diizinkan. Jarak Anda dari lokasi kerja adalah " . round($distance) . " meter, sedangkan radius yang diizinkan adalah " . $location->radius . " meter.",
                        'code' => 'OUTSIDE_GEOFENCE',
                        'data' => [
                            'distance' => round($distance),
                            'allowed_radius' => $location->radius,
                            'location_name' => $location->name
                        ]
                    ];
                }
                
                return [
                    'valid' => true,
                    'message' => 'Lokasi valid (menggunakan lokasi lama)',
                    'code' => 'VALID_LEGACY_LOCATION',
                    'location' => $location
                ];
            }
        }
        
        // Validate work location is active
        if (!$workLocation->is_active) {
            return [
                'valid' => false,
                'message' => 'Lokasi kerja Anda sedang tidak aktif. Hubungi admin untuk informasi lebih lanjut.',
                'code' => 'WORK_LOCATION_INACTIVE'
            ];
        }
        
        // Validate geofencing with WorkLocation
        if (!$workLocation->isWithinGeofence($latitude, $longitude, $accuracy)) {
            $distance = $workLocation->calculateDistance($latitude, $longitude);
            
            return [
                'valid' => false,
                'message' => "Anda berada di luar area kerja yang diizinkan. Jarak Anda dari lokasi kerja adalah " . round($distance) . " meter, sedangkan radius yang diizinkan adalah " . $workLocation->radius_meters . " meter.",
                'code' => 'OUTSIDE_GEOFENCE',
                'data' => [
                    'distance' => round($distance),
                    'allowed_radius' => $workLocation->radius_meters,
                    'location_name' => $workLocation->name,
                    'strict_geofence' => $workLocation->strict_geofence,
                    'gps_accuracy_required' => $workLocation->gps_accuracy_required
                ]
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Lokasi kerja valid',
            'code' => 'VALID_WORK_LOCATION',
            'work_location' => $workLocation
        ];
    }
    
    /**
     * Validate shift time for check-in with enhanced tolerance settings
     */
    public function validateShiftTime(JadwalJaga $jadwalJaga, Carbon $currentTime = null): array
    {
        $currentTime = $currentTime ? $currentTime->copy()->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        $shiftTemplate = $jadwalJaga->shiftTemplate;
        
        if (!$shiftTemplate) {
            // FALLBACK: Use default schedule if no shift template
            \Log::warning('No shift template found for jadwal', [
                'jadwal_id' => $jadwalJaga->id,
                'pegawai_id' => $jadwalJaga->pegawai_id,
                'tanggal' => $jadwalJaga->tanggal_jaga
            ]);
            
            // Try to auto-fix by assigning default shift template
            $defaultTemplate = \App\Models\ShiftTemplate::where('id', 14)->first();
            if ($defaultTemplate) {
                $jadwalJaga->shift_template_id = 14;
                $jadwalJaga->save();
                $shiftTemplate = $defaultTemplate;
                
                \Log::info('Auto-assigned default shift template ID 14', [
                    'jadwal_id' => $jadwalJaga->id
                ]);
            } else {
                // Use hardcoded fallback times as last resort
                return $this->validateWithFallbackTimes($jadwalJaga, $currentTime);
            }
        }
        
        // Get shift start and end times with flexible parsing
        // Handle both H:i format and full datetime format
        try {
            if (strlen((string) $shiftTemplate->jam_masuk) > 5) {
                // Full datetime format
                $shiftStart = Carbon::parse($shiftTemplate->jam_masuk)->setTimezone('Asia/Jakarta');
            } else {
                // H:i format
                $shiftStart = Carbon::createFromFormat('H:i', $shiftTemplate->jam_masuk, 'Asia/Jakarta');
            }
        } catch (Exception $e) {
            $shiftStart = Carbon::parse($shiftTemplate->jam_masuk)->setTimezone('Asia/Jakarta');
        }
        
        try {
            $jamKeluar = $shiftTemplate->jam_pulang ?? $shiftTemplate->jam_keluar ?? null;
            if (!$jamKeluar) {
                return [
                    'valid' => false,
                    'message' => 'Jam keluar shift tidak ditemukan. Hubungi admin untuk informasi lebih lanjut.',
                    'code' => 'NO_SHIFT_END_TIME'
                ];
            }
            
            if (strlen((string) $jamKeluar) > 5) {
                // Full datetime format
                $shiftEnd = Carbon::parse($jamKeluar)->setTimezone('Asia/Jakarta');
            } else {
                // H:i format
                $shiftEnd = Carbon::createFromFormat('H:i', $jamKeluar, 'Asia/Jakarta');
            }
        } catch (Exception $e) {
            $shiftEnd = Carbon::parse($jamKeluar)->setTimezone('Asia/Jakarta');
        }
        
        // Get work location tolerance settings with enhanced configuration
        $user = $jadwalJaga->pegawai;
        $workLocation = $user->workLocation;
        
        // ENHANCED: Always prioritize admin settings from JSON, with individual fields as backup
        $lateToleranceMinutes = null;
        $checkInBeforeShiftMinutes = null;
        
        if ($workLocation) {
            // PRIORITY 1: Use JSON admin settings if available
            if (is_array($workLocation->tolerance_settings)) {
                $ts = $workLocation->tolerance_settings;
                $lateToleranceMinutes = isset($ts['late_tolerance_minutes']) ? (int) $ts['late_tolerance_minutes'] : null;
                $checkInBeforeShiftMinutes = isset($ts['checkin_before_shift_minutes']) ? (int) $ts['checkin_before_shift_minutes'] : null;
            }
            
            // PRIORITY 2: Fallback to individual fields if JSON not available
            if ($lateToleranceMinutes === null) {
                $lateToleranceMinutes = $workLocation->late_tolerance_minutes;
            }
            if ($checkInBeforeShiftMinutes === null) {
                $checkInBeforeShiftMinutes = $workLocation->checkin_before_shift_minutes;
            }
        }
        
        // PRIORITY 3: Final defaults if nothing is set
        $lateToleranceMinutes = $lateToleranceMinutes ?? 15;
        $checkInBeforeShiftMinutes = $checkInBeforeShiftMinutes ?? 30;

        // Log tolerance values for transparency
        \Log::info('Tolerance settings used for validation', [
            'user_id' => $user->id,
            'work_location_id' => $workLocation ? $workLocation->id : null,
            'late_tolerance_minutes' => $lateToleranceMinutes,
            'checkin_before_shift_minutes' => $checkInBeforeShiftMinutes,
            'source' => $workLocation && is_array($workLocation->tolerance_settings) ? 'admin_json_settings' : 'individual_fields_or_defaults'
        ]);

        // Global-only policy: do not apply per-user overrides
        
        // ADMIN-CONTROLLED: Check-in window based on admin settings
        $checkInEarliestTime = $shiftStart->copy()->subMinutes($checkInBeforeShiftMinutes);
        $checkInLatestTime = $shiftStart->copy()->addMinutes($lateToleranceMinutes); // Use admin tolerance
        
        // Check if current time is within allowed check-in window
        $currentTimeOnly = Carbon::createFromFormat('H:i:s', $currentTime->copy()->setTimezone('Asia/Jakarta')->format('H:i:s'), 'Asia/Jakarta');
        
        // Too early check
        if ($currentTimeOnly->lt($checkInEarliestTime)) {
            return [
                'valid' => false,
                'message' => "Terlalu awal untuk check-in. Anda dapat check-in mulai pukul {$checkInEarliestTime->format('H:i')} ({$checkInBeforeShiftMinutes} menit sebelum shift dimulai) hingga pukul {$checkInLatestTime->format('H:i')} ({$lateToleranceMinutes} menit setelah shift dimulai).",
                'code' => 'TOO_EARLY',
                'data' => [
                    'shift_start' => $shiftStart->format('H:i'),
                    'check_in_earliest' => $checkInEarliestTime->format('H:i'),
                    'check_in_latest' => $checkInLatestTime->format('H:i'),
                    'current_time' => $currentTimeOnly->format('H:i'),
                    'tolerance_settings' => [
                        'late_tolerance_minutes' => $lateToleranceMinutes,
                        'checkin_before_shift_minutes' => $checkInBeforeShiftMinutes
                    ]
                ]
            ];
        }
        
        // ADMIN-CONTROLLED: Late check-in validation based on tolerance settings
        if ($currentTimeOnly->gt($checkInLatestTime)) {
            $lateMinutes = $shiftStart->diffInMinutes($currentTimeOnly); // FIXED: Correct order for positive result
            
            // Check if exceeds admin tolerance
            if ($lateMinutes > $lateToleranceMinutes) {
                return [
                    'valid' => false,
                    'message' => "Check-in terlalu terlambat ({$lateMinutes} menit). Batas maksimal toleransi adalah {$lateToleranceMinutes} menit setelah jam shift ({$shiftStart->format('H:i')}). Hubungi supervisor untuk approval manual.",
                    'code' => 'TOO_LATE',
                    'data' => [
                        'shift_start' => $shiftStart->format('H:i'),
                        'late_minutes' => $lateMinutes,
                        'max_tolerance_minutes' => $lateToleranceMinutes,
                        'current_time' => $currentTimeOnly->format('H:i'),
                        'policy' => $lateToleranceMinutes == 0 ? 'strict' : 'tolerant'
                    ]
                ];
            }
            
            // Late but within tolerance
            return [
                'valid' => true,
                'message' => "Check-in terlambat {$lateMinutes} menit dari jadwal shift ({$shiftStart->format('H:i')}). Status: Terlambat namun dalam batas toleransi {$lateToleranceMinutes} menit.",
                'code' => 'VALID_BUT_LATE',
                'data' => [
                    'shift_start' => $shiftStart->format('H:i'),
                    'late_minutes' => $lateMinutes,
                    'tolerance_minutes' => $lateToleranceMinutes,
                    'within_tolerance' => true,
                    'status' => 'late_within_tolerance'
                ]
            ];
        }
        
        // On-time or early (within allowed window)
        $earlyMinutes = $currentTimeOnly->lt($shiftStart) ? $shiftStart->diffInMinutes($currentTimeOnly) : 0;
        
        return [
            'valid' => true,
            'message' => $earlyMinutes > 0 
                ? "Check-in berhasil {$earlyMinutes} menit sebelum shift dimulai. Status: Tepat waktu." 
                : 'Check-in tepat waktu.',
            'code' => 'ON_TIME',
            'data' => [
                'shift_start' => $shiftStart->format('H:i'),
                'shift_end' => $shiftEnd->format('H:i'),
                'early_minutes' => $earlyMinutes,
                'policy' => $lateToleranceMinutes == 0 ? 'strict' : 'tolerant',
                'check_in_window' => [
                    'earliest' => $checkInEarliestTime->format('H:i'),
                    'latest' => $checkInLatestTime->format('H:i')
                ],
                'tolerance_settings' => [
                    'late_tolerance_minutes' => $lateToleranceMinutes,
                    'checkin_before_shift_minutes' => $checkInBeforeShiftMinutes
                ]
            ]
        ];
    }
    
    /**
     * Validate shift and location compatibility
     */
    public function validateShiftLocationCompatibility(JadwalJaga $jadwalJaga, WorkLocation $workLocation): array
    {
        $shiftTemplate = $jadwalJaga->shiftTemplate;
        
        if (!$shiftTemplate) {
            return [
                'valid' => true, // Allow check-in even without shift template
                'message' => 'Template shift tidak ditemukan, namun check-in diizinkan',
                'code' => 'NO_SHIFT_TEMPLATE_ALLOW'
            ];
        }
        
        // Check if shift is allowed at this work location
        if (!$workLocation->isShiftAllowed($shiftTemplate->nama_shift)) {
            return [
                'valid' => false,
                'message' => "Shift '{$shiftTemplate->nama_shift}' tidak diizinkan di lokasi '{$workLocation->name}'. Hubungi admin untuk informasi lebih lanjut.",
                'code' => 'SHIFT_NOT_ALLOWED',
                'data' => [
                    'shift_name' => $shiftTemplate->nama_shift,
                    'location_name' => $workLocation->name,
                    'allowed_shifts' => $workLocation->allowed_shifts
                ]
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Shift dan lokasi kompatibel',
            'code' => 'COMPATIBLE'
        ];
    }
    
    /**
     * Validate check-out request with WORK LOCATION TOLERANCE
     */
    public function validateCheckout(User $user, float $latitude, float $longitude, ?float $accuracy = null, Carbon $date = null): array
    {
        $date = $date ? $date->copy()->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta')->startOfDay();
        
        // 1. Check if user has an open attendance session (either today or recent)
        $attendance = \App\Models\Attendance::where('user_id', $user->id)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->first();
        
        if (!$attendance) {
            // Check today's specific attendance as fallback
            $attendance = \App\Models\Attendance::getTodayAttendance($user->id);
            
            if (!$attendance) {
                return [
                    'valid' => false,
                    'message' => 'Anda belum melakukan check-in. Silakan check-in terlebih dahulu.',
                    'code' => 'NOT_CHECKED_IN'
                ];
            }
        }
        
        // WORK LOCATION TOLERANCE: If there's an open session, allow checkout
        // This is the core principle - users can checkout anytime after check-in
        if ($attendance && $attendance->time_in && !$attendance->time_out) {
            \Log::info('WORK LOCATION TOLERANCE: Open session found, allowing checkout', [
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'check_in_time' => $attendance->time_in
            ]);
        }
        
        // 2. MULTIPLE CHECKOUT SUPPORT: Don't block if already checked out
        // Allow updating checkout time multiple times within the same shift
        if ($attendance->hasCheckedOut()) {
            // Log but don't block - allow multiple checkout
            \Log::info('MULTIPLE CHECKOUT: Updating existing checkout time', [
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'previous_checkout' => $attendance->time_out->format('H:i:s'),
                'work_duration' => $attendance->formatted_work_duration
            ]);
            
            // Don't return error - continue with validation to allow checkout update
            // This enables users to checkout multiple times in the same shift
        }
        
        // 3. WORK LOCATION TOLERANCE FOR CHECKOUT
        // For checkout, we apply more lenient validation - users can checkout from anywhere
        // after they have checked in. This is the core of work location tolerance.
        $locationValidation = $this->validateWorkLocation($user, $latitude, $longitude, $accuracy);
        
        if (!$locationValidation['valid']) {
            // WORK LOCATION TOLERANCE: Override location validation for checkout
            // Users who have checked in should be able to checkout from anywhere
            \Log::info('WORK LOCATION TOLERANCE: Overriding location validation for checkout', [
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'original_validation_code' => $locationValidation['code'],
                'original_message' => $locationValidation['message']
            ]);
            
            // Don't block checkout due to location - apply tolerance
            // Set valid to true but keep the message for information
            $locationValidation['valid'] = true;
            $locationValidation['original_code'] = $locationValidation['code'];
            $locationValidation['code'] = 'LOCATION_TOLERANCE_APPLIED';
            $locationValidation['message'] = 'Checkout diizinkan dengan toleransi lokasi';
        }
        
        // 4. Validate check-out time with tolerance settings
        $currentTime = Carbon::now('Asia/Jakarta');
        $currentWorkMinutes = $currentTime->diffInMinutes($attendance->time_in);
        
        // Get user's schedule and work location for tolerance settings
        $scheduleValidation = $this->validateSchedule($user, $date);
        if ($scheduleValidation['valid']) {
            $jadwalJaga = $scheduleValidation['jadwal_jaga'];
            $shiftTemplate = $jadwalJaga->shiftTemplate;
            
            if ($shiftTemplate) {
                // Handle flexible time format parsing for checkout validation
                try {
                    $jamKeluar = $shiftTemplate->jam_pulang ?? $shiftTemplate->jam_keluar ?? null;
                    if (!$jamKeluar) {
                        // Skip shift time validation if no end time
                        return [
                            'valid' => true,
                            'message' => 'Check-out diizinkan (tidak ada jam keluar yang ditetapkan)',
                            'code' => 'VALID_CHECKOUT_NO_END_TIME',
                            'attendance' => $attendance,
                            'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
                            'work_duration_minutes' => $currentWorkMinutes
                        ];
                    }
                    
                    if (strlen((string) $jamKeluar) > 5) {
                        // Full datetime format
                        $shiftEnd = Carbon::parse($jamKeluar)->setTimezone('Asia/Jakarta');
                    } else {
                        // H:i format
                        $shiftEnd = Carbon::createFromFormat('H:i', $jamKeluar, 'Asia/Jakarta');
                    }
                } catch (Exception $e) {
                    $shiftEnd = Carbon::parse($jamKeluar)->setTimezone('Asia/Jakarta');
                }
                
                $currentTimeOnly = Carbon::createFromFormat('H:i:s', $currentTime->copy()->setTimezone('Asia/Jakarta')->format('H:i:s'), 'Asia/Jakarta');
                
                // Get work location tolerance settings
                $workLocation = $user->workLocation;
                $earlyDepartureToleranceMinutes = $workLocation ? ($workLocation->early_departure_tolerance_minutes ?? null) : null;
                $checkoutAfterShiftMinutes = $workLocation ? ($workLocation->checkout_after_shift_minutes ?? null) : null;
                if (($earlyDepartureToleranceMinutes === null || $checkoutAfterShiftMinutes === null) && $workLocation && is_array($workLocation->tolerance_settings)) {
                    $ts = $workLocation->tolerance_settings;
                    if ($earlyDepartureToleranceMinutes === null && isset($ts['early_departure_tolerance_minutes'])) {
                        $earlyDepartureToleranceMinutes = (int) $ts['early_departure_tolerance_minutes'];
                    }
                    if ($checkoutAfterShiftMinutes === null && isset($ts['checkout_after_shift_minutes'])) {
                        $checkoutAfterShiftMinutes = (int) $ts['checkout_after_shift_minutes'];
                    }
                }
                // ADMIN-CONTROLLED: Use admin settings for checkout tolerance
                $earlyDepartureToleranceMinutes = $earlyDepartureToleranceMinutes ?? 15; // Admin controlled
                $checkoutAfterShiftMinutes = $checkoutAfterShiftMinutes ?? 60; // Admin controlled

                // Global-only policy: do not apply per-user overrides for checkout
                
                // FLEXIBLE CHECKOUT: Based on admin tolerance settings
                $checkoutEarliestTime = $shiftEnd->copy()->subMinutes($earlyDepartureToleranceMinutes); // Admin controlled early departure
                $checkoutLatestTime = $shiftEnd->copy()->addMinutes($checkoutAfterShiftMinutes); // Admin controlled late checkout
                
                // WORK LOCATION TOLERANCE: Skip "too early" validation if there's an open session
                // Users should be able to checkout anytime after check-in
                $hasOpenSession = $attendance && $attendance->time_in && !$attendance->time_out;
                
                // ADMIN-CONTROLLED: Early checkout validation based on tolerance
                if ($currentTimeOnly->lt($checkoutEarliestTime)) {
                    $earlyMinutes = $checkoutEarliestTime->diffInMinutes($currentTimeOnly);
                    
                    \Log::info('Early checkout attempt - checking admin tolerance', [
                        'user_id' => $user->id,
                        'early_minutes' => $earlyMinutes,
                        'tolerance_minutes' => $earlyDepartureToleranceMinutes
                    ]);
                    
                    return [
                        'valid' => false,
                        'message' => "Check-out terlalu awal. Anda mencoba check-out {$earlyMinutes} menit sebelum toleransi dimulai. Check-out paling awal: pukul {$checkoutEarliestTime->format('H:i')} ({$earlyDepartureToleranceMinutes} menit sebelum shift berakhir). Hubungi supervisor jika diperlukan.",
                        'code' => 'CHECKOUT_TOO_EARLY',
                        'data' => [
                            'shift_end' => $shiftEnd->format('H:i'),
                            'checkout_earliest' => $checkoutEarliestTime->format('H:i'),
                            'current_time' => $currentTimeOnly->format('H:i'),
                            'early_minutes' => $earlyMinutes,
                            'tolerance_minutes' => $earlyDepartureToleranceMinutes,
                            'policy' => $earlyDepartureToleranceMinutes == 0 ? 'strict' : 'tolerant'
                        ]
                    ];
                }
                
                // Check if checkout is too late (optional warning)
                if ($currentTimeOnly->gt($checkoutLatestTime)) {
                    $lateMinutes = $currentTimeOnly->diffInMinutes($shiftEnd);
                    // Still allow but with warning message
                    return [
                        'valid' => true,
                        'message' => "Check-out sangat terlambat ({$lateMinutes} menit setelah shift berakhir). Durasi kerja mungkin termasuk lembur.",
                        'code' => 'CHECKOUT_VERY_LATE',
                        'attendance' => $attendance,
                        'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
                        'work_duration_minutes' => $currentWorkMinutes,
                        'data' => [
                            'shift_end' => $shiftEnd->format('H:i'),
                            'late_minutes' => $lateMinutes,
                            'overtime_likely' => true
                        ]
                    ];
                }
                
                // Early departure within admin tolerance
                if ($currentTimeOnly->lt($shiftEnd)) {
                    $earlyMinutes = $shiftEnd->diffInMinutes($currentTimeOnly);
                    return [
                        'valid' => true,
                        'message' => "Check-out {$earlyMinutes} menit sebelum shift berakhir. Status: Dalam batas toleransi pulang awal ({$earlyDepartureToleranceMinutes} menit).",
                        'code' => 'CHECKOUT_EARLY_TOLERANCE',
                        'attendance' => $attendance,
                        'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
                        'work_duration_minutes' => $currentWorkMinutes,
                        'data' => [
                            'shift_end' => $shiftEnd->format('H:i'),
                            'early_minutes' => $earlyMinutes,
                            'tolerance_minutes' => $earlyDepartureToleranceMinutes,
                            'within_tolerance' => true
                        ]
                    ];
                }
            }
        }
        
        // 5. Tidak ada batas minimum durasi kerja untuk check-out
        
        return [
            'valid' => true,
            'message' => 'Check-out berhasil.',
            'code' => 'VALID_CHECKOUT',
            'attendance' => $attendance,
            'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
            'work_duration_minutes' => $currentWorkMinutes,
            'data' => [
                'checkout_time' => $currentTime->format('H:i:s'),
                'work_duration_minutes' => $currentWorkMinutes
            ]
        ];
    }

    /**
     * Create per-user tolerance override (cache-based, expires end of day)
     */
    public function createToleranceOverride(User $admin, User $targetUser, array $settings): array
    {
        if (!$admin->hasRole(['admin', 'super-admin'])) {
            return [
                'success' => false,
                'message' => 'Only administrators can create tolerance overrides.',
                'code' => 'INSUFFICIENT_PERMISSIONS'
            ];
        }
        $date = Carbon::now('Asia/Jakarta');
        $cacheKey = 'tolerance_override_' . $targetUser->id . '_' . $date->format('Y-m-d');
        $payload = [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'settings' => $settings,
            'created_at' => now()->toISOString(),
            'expires_at' => $date->copy()->endOfDay()->toISOString(),
        ];
        Cache::put($cacheKey, $payload, $date->diffInSeconds($date->copy()->endOfDay()));
        Log::info('Tolerance override created', ['admin' => $admin->id, 'user' => $targetUser->id, 'settings' => $settings]);
        return [
            'success' => true,
            'message' => 'Tolerance override created successfully.',
            'code' => 'TOLERANCE_OVERRIDE_CREATED',
            'override_data' => $payload,
        ];
    }

    /**
     * Check active tolerance override
     */
    public function hasActiveToleranceOverride(User $user): array
    {
        $date = Carbon::now('Asia/Jakarta');
        $cacheKey = 'tolerance_override_' . $user->id . '_' . $date->format('Y-m-d');
        $override = Cache::get($cacheKey);
        if (!$override) {
            return ['has_override' => false, 'override_data' => null];
        }
        $expiresAt = Carbon::parse($override['expires_at']);
        if ($expiresAt->isPast()) {
            Cache::forget($cacheKey);
            return ['has_override' => false, 'override_data' => null];
        }
        // Unwrap settings directly for easy use
        $data = $override['settings'] ?? [];
        return ['has_override' => true, 'override_data' => $data];
    }

    /**
     * Comprehensive validation for check-in with enhanced GPS diagnostics
     */
    public function validateCheckin(User $user, float $latitude, float $longitude, ?float $accuracy = null, Carbon $date = null): array
    {
        $validationResults = [];
        
        // 1. Validate schedule
        $scheduleValidation = $this->validateSchedule($user, $date);
        $validationResults['schedule'] = $scheduleValidation;
        
        if (!$scheduleValidation['valid']) {
            return [
                'valid' => false,
                'message' => $scheduleValidation['message'],
                'code' => $scheduleValidation['code'],
                'validations' => $validationResults
            ];
        }
        
        $jadwalJaga = $scheduleValidation['jadwal_jaga'];
        
        // 2. Validate work location with admin override support
        $locationValidation = $this->validateWorkLocationWithOverride($user, $latitude, $longitude, $accuracy);
        $validationResults['location'] = $locationValidation;
        
        if (!$locationValidation['valid']) {
            return [
                'valid' => false,
                'message' => $locationValidation['message'],
                'code' => $locationValidation['code'],
                'data' => $locationValidation['data'] ?? null,
                'validations' => $validationResults
            ];
        }
        
        // 3. Validate shift time
        $timeValidation = $this->validateShiftTime($jadwalJaga);
        $validationResults['time'] = $timeValidation;
        
        // 4. Validate shift-location compatibility if work location is available
        if (isset($locationValidation['work_location'])) {
            $compatibilityValidation = $this->validateShiftLocationCompatibility($jadwalJaga, $locationValidation['work_location']);
            $validationResults['compatibility'] = $compatibilityValidation;
            
            if (!$compatibilityValidation['valid']) {
                return [
                    'valid' => false,
                    'message' => $compatibilityValidation['message'],
                    'code' => $compatibilityValidation['code'],
                    'data' => $compatibilityValidation['data'] ?? null,
                    'validations' => $validationResults
                ];
            }
        }
        
        // Determine overall validity and message
        $isLate = $timeValidation['code'] === 'LATE_CHECKIN';
        $message = $isLate ? $timeValidation['message'] : 'Semua validasi berhasil - check-in diizinkan';
        
        return [
            'valid' => true,
            'message' => $message,
            'code' => $isLate ? 'VALID_BUT_LATE' : 'VALID',
            'jadwal_jaga' => $jadwalJaga,
            'work_location' => $locationValidation['work_location'] ?? $locationValidation['location'] ?? null,
            'validations' => $validationResults,
            'gps_diagnostics' => $locationValidation['gps_diagnostics'] ?? null,
        ];
    }

    /**
     * Get GPS troubleshooting tips based on diagnostic information
     */
    private function getGPSTroubleshootingTips(array $gpsDiagnostics): array
    {
        $tips = [];
        
        $locationAnalysis = $gpsDiagnostics['location_analysis'] ?? [];
        $coordinates = $gpsDiagnostics['coordinates'] ?? [];
        
        // Check for VPN/proxy issues
        $vpnAnalysis = $locationAnalysis['potential_vpn_proxy'] ?? [];
        if (($vpnAnalysis['risk_level'] ?? 'low') !== 'low') {
            $tips[] = [
                'type' => 'vpn_warning',
                'title' => '🔧 Matikan VPN/Proxy',
                'description' => 'Terdeteksi kemungkinan penggunaan VPN atau proxy. Matikan semua koneksi VPN dan coba lagi.',
                'priority' => 'high'
            ];
        }
        
        // Check for coordinate quality issues
        $coordinateQuality = $locationAnalysis['coordinate_quality'] ?? [];
        if (($coordinateQuality['quality'] ?? 'good') !== 'good') {
            $tips[] = [
                'type' => 'gps_quality',
                'title' => '📍 Perbaiki Sinyal GPS',
                'description' => 'Kualitas GPS tidak optimal. Pindah ke area terbuka dan pastikan location services aktif.',
                'priority' => 'medium'
            ];
        }
        
        // Check for accuracy issues
        $accuracy = $coordinates['accuracy_meters'] ?? null;
        if ($accuracy && $accuracy > 50) {
            $tips[] = [
                'type' => 'gps_accuracy',
                'title' => '🎯 Tingkatkan Akurasi GPS',
                'description' => sprintf('Akurasi GPS saat ini: %.0f meter. Tunggu beberapa saat untuk GPS lebih akurat.', $accuracy),
                'priority' => 'medium'
            ];
        }
        
        // Check for zero coordinates
        if ($locationAnalysis['is_zero_coordinates'] ?? false) {
            $tips[] = [
                'type' => 'location_permission',
                'title' => '⚠️ Aktifkan Izin Lokasi',
                'description' => 'Koordinat tidak terdeteksi. Pastikan browser/aplikasi memiliki izin akses lokasi.',
                'priority' => 'critical'
            ];
        }
        
        // Region-specific tips
        $region = $locationAnalysis['estimated_region'] ?? [];
        if (($region['is_far_from_expected_areas'] ?? false)) {
            $tips[] = [
                'type' => 'location_verification',
                'title' => '🌍 Verifikasi Lokasi',
                'description' => 'Lokasi Anda terdeteksi sangat jauh dari area kerja yang diharapkan. Hubungi admin jika ini adalah kesalahan.',
                'priority' => 'high'
            ];
        }
        
        // Default troubleshooting tips if no specific issues
        if (empty($tips)) {
            $tips[] = [
                'type' => 'general',
                'title' => '📱 Tips Umum GPS',
                'description' => 'Pastikan GPS aktif, di area terbuka, dan tunggu beberapa detik untuk akurasi yang lebih baik.',
                'priority' => 'low'
            ];
        }
        
        return $tips;
    }

    /**
     * Create admin override for GPS validation (for testing/troubleshooting)
     */
    public function createAdminGPSOverride(User $admin, User $targetUser, float $latitude, float $longitude, string $reason): array
    {
        // Only allow admin users to create overrides
        if (!$admin->hasRole(['admin', 'super-admin'])) {
            return [
                'success' => false,
                'message' => 'Only administrators can create GPS validation overrides.',
                'code' => 'INSUFFICIENT_PERMISSIONS'
            ];
        }
        
        $overrideData = [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'target_user_id' => $targetUser->id,
            'target_user_name' => $targetUser->name,
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
            'reason' => $reason,
            'created_at' => now()->toISOString(),
            'expires_at' => now()->addHours(24)->toISOString(), // Override expires in 24 hours
        ];
        
        // Store override in cache
        $cacheKey = "gps_override_{$targetUser->id}_" . now()->format('Y-m-d');
        Cache::put($cacheKey, $overrideData, now()->addHours(24));
        
        // Log the override creation
        Log::warning('Admin GPS validation override created', [
            'admin_id' => $admin->id,
            'target_user_id' => $targetUser->id,
            'reason' => $reason,
            'coordinates' => ['lat' => $latitude, 'lon' => $longitude],
        ]);
        
        return [
            'success' => true,
            'message' => 'GPS validation override created successfully.',
            'code' => 'OVERRIDE_CREATED',
            'override_data' => $overrideData
        ];
    }
    
    /**
     * Check if user has active admin GPS override
     */
    public function hasActiveGPSOverride(User $user, Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $cacheKey = "gps_override_{$user->id}_" . $date->format('Y-m-d');
        
        $override = Cache::get($cacheKey);
        
        if (!$override) {
            return [
                'has_override' => false,
                'override_data' => null
            ];
        }
        
        // Check if override is still valid
        $expiresAt = Carbon::parse($override['expires_at']);
        if ($expiresAt->isPast()) {
            Cache::forget($cacheKey);
            return [
                'has_override' => false,
                'override_data' => null
            ];
        }
        
        return [
            'has_override' => true,
            'override_data' => $override
        ];
    }

    /**
     * Fallback validation when no shift template is available
     */
    private function validateWithFallbackTimes(JadwalJaga $jadwalJaga, Carbon $currentTime): array
    {
        // Use default 08:00-16:00 schedule as fallback
        $shiftStart = Carbon::createFromFormat('H:i:s', '08:00:00', 'Asia/Jakarta');
        $shiftEnd = Carbon::createFromFormat('H:i:s', '16:00:00', 'Asia/Jakarta');
        
        $user = $jadwalJaga->pegawai;
        $workLocation = $user->workLocation;
        
        // Get tolerance settings
        $lateToleranceMinutes = 15;
        $checkInBeforeShiftMinutes = 30;
        
        if ($workLocation && is_array($workLocation->tolerance_settings)) {
            $ts = $workLocation->tolerance_settings;
            $lateToleranceMinutes = $ts['late_tolerance_minutes'] ?? 15;
            $checkInBeforeShiftMinutes = $ts['checkin_before_shift_minutes'] ?? 30;
        }
        
        // Calculate check-in window
        $checkInEarliestTime = $shiftStart->copy()->subMinutes($checkInBeforeShiftMinutes);
        $checkInLatestTime = $shiftStart->copy()->addMinutes($lateToleranceMinutes);
        
        $currentTimeOnly = Carbon::createFromFormat('H:i:s', $currentTime->format('H:i:s'), 'Asia/Jakarta');
        
        // Validation logic
        if ($currentTimeOnly->lt($checkInEarliestTime)) {
            return [
                'valid' => false,
                'message' => "Terlalu awal untuk check-in. Check-in mulai {$checkInEarliestTime->format('H:i')}",
                'code' => 'TOO_EARLY'
            ];
        }
        
        if ($currentTimeOnly->gt($checkInLatestTime)) {
            $lateMinutes = $shiftStart->diffInMinutes($currentTimeOnly);
            return [
                'valid' => false,
                'message' => "Check-in terlambat ({$lateMinutes} menit). Batas toleransi: {$lateToleranceMinutes} menit",
                'code' => 'TOO_LATE'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Check-in diperbolehkan (menggunakan jadwal default 08:00-16:00)',
            'code' => 'VALID_WITH_FALLBACK',
            'fallback_used' => true
        ];
    }

    /**
     * Enhanced work location validation with admin override support
     */
    public function validateWorkLocationWithOverride(User $user, float $latitude, float $longitude, ?float $accuracy = null): array
    {
        // Check for active admin override first
        $overrideCheck = $this->hasActiveGPSOverride($user);
        
        if ($overrideCheck['has_override']) {
            $overrideData = $overrideCheck['override_data'];
            
            Log::info('GPS validation bypassed due to admin override', [
                'user_id' => $user->id,
                'admin_id' => $overrideData['admin_id'],
                'reason' => $overrideData['reason'],
            ]);
            
            return [
                'valid' => true,
                'message' => 'GPS validation bypassed by admin override: ' . $overrideData['reason'],
                'code' => 'ADMIN_OVERRIDE_ACTIVE',
                'override_info' => [
                    'admin_name' => $overrideData['admin_name'],
                    'reason' => $overrideData['reason'],
                    'expires_at' => $overrideData['expires_at'],
                ]
            ];
        }
        
        // Proceed with normal validation if no override
        return $this->validateWorkLocation($user, $latitude, $longitude, $accuracy);
    }
}