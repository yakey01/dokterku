<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'location_id',
        'work_location_id',
        'jadwal_jaga_id',
        'shift_id',
        'shift_sequence',
        'previous_attendance_id',
        'gap_from_previous_minutes',
        'date',
        'time_in',
        'time_out',
        'logical_time_in',
        'logical_time_out',
        'logical_work_minutes',
        'shift_start',
        'shift_end',
        'next_shift_start',
        'next_shift_id',
        'latlon_in',
        'latlon_out',
        'location_name_in',
        'location_name_out',
        'device_info',
        'device_id', // Reference to user_devices.device_id
        'device_fingerprint', // For security validation
        'photo_in',
        'photo_out',
        'notes',
        'status',
        'check_in_rejection_code',
        'check_in_rejection_reason',
        'check_in_metadata',
        'check_out_metadata',
        // Enhanced GPS fields
        'latitude',
        'longitude',
        'accuracy',
        'checkout_latitude',
        'checkout_longitude', 
        'checkout_accuracy',
        'location_validated',
        'is_additional_shift',
        'is_overtime_shift',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'time_in' => 'datetime:H:i:s',
        'time_out' => 'datetime:H:i:s',
        'logical_time_in' => 'datetime:H:i:s',
        'logical_time_out' => 'datetime:H:i:s',
        'shift_start' => 'datetime:H:i:s',
        'shift_end' => 'datetime:H:i:s',
        'next_shift_start' => 'datetime:H:i:s',
        'logical_work_minutes' => 'integer',
        'shift_sequence' => 'integer',
        'gap_from_previous_minutes' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'checkout_latitude' => 'decimal:8',
        'checkout_longitude' => 'decimal:8',
        'accuracy' => 'float',
        'checkout_accuracy' => 'float',
        'location_validated' => 'boolean',
        'is_additional_shift' => 'boolean',
        'is_overtime_shift' => 'boolean',
        'check_in_metadata' => 'array',
        'check_out_metadata' => 'array',
    ];

    /**
     * Relationship dengan User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship dengan UserDevice
     */
    public function userDevice(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id', 'device_id');
    }

    /**
     * Relationship dengan Location (legacy)
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Relationship dengan WorkLocation untuk enhanced geofencing
     */
    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'work_location_id');
    }

    /**
     * Relationship dengan JadwalJaga untuk validasi schedule
     */
    public function jadwalJaga(): BelongsTo
    {
        return $this->belongsTo(JadwalJaga::class, 'jadwal_jaga_id');
    }

    /**
     * Relationship dengan ShiftTemplate
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_id');
    }

    /**
     * Relationship dengan previous attendance (for multi-shift)
     */
    public function previousAttendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'previous_attendance_id');
    }

    /**
     * Relationship dengan next shift template
     */
    public function nextShift(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'next_shift_id');
    }

    /**
     * Get all attendances for the same day (multi-shift)
     */
    public function sameDayAttendances()
    {
        return self::where('user_id', $this->user_id)
            ->where('date', $this->date)
            ->orderBy('shift_sequence');
    }

    /**
     * Get latitude from latlon_in
     */
    public function getLatitudeInAttribute(): ?float
    {
        if (!$this->latlon_in) return null;
        $coords = explode(',', $this->latlon_in);
        return isset($coords[0]) ? (float) $coords[0] : null;
    }

    /**
     * Get longitude from latlon_in
     */
    public function getLongitudeInAttribute(): ?float
    {
        if (!$this->latlon_in) return null;
        $coords = explode(',', $this->latlon_in);
        return isset($coords[1]) ? (float) $coords[1] : null;
    }

    /**
     * Get latitude from latlon_out
     */
    public function getLatitudeOutAttribute(): ?float
    {
        if (!$this->latlon_out) return null;
        $coords = explode(',', $this->latlon_out);
        return isset($coords[0]) ? (float) $coords[0] : null;
    }

    /**
     * Get longitude from latlon_out
     */
    public function getLongitudeOutAttribute(): ?float
    {
        if (!$this->latlon_out) return null;
        $coords = explode(',', $this->latlon_out);
        return isset($coords[1]) ? (float) $coords[1] : null;
    }

    /**
     * Check if user already checked in today
     */
    public static function hasCheckedInToday(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->where('date', Carbon::today())
            ->exists();
    }

    /**
     * Get today's attendance for user
     */
    public static function getTodayAttendance(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('date', Carbon::today())
            ->orderByDesc('time_in')
            ->first();
    }

    /**
     * Get all attendances for today (ascending by time_in)
     */
    public static function getTodayAttendances(int $userId)
    {
        return self::where('user_id', $userId)
            ->where('date', Carbon::today())
            ->orderBy('time_in')
            ->get();
    }

    /**
     * Check if user can check out (has checked in but not out)
     */
    public function canCheckOut(): bool
    {
        return $this->time_in && !$this->time_out;
    }

    /**
     * Check if user has completed check-out
     */
    public function hasCheckedOut(): bool
    {
        return $this->time_in && $this->time_out;
    }

    /**
     * Check if user can check in for a new day
     */
    public static function canCheckInNewDay(int $userId): bool
    {
        $todayAttendance = self::getTodayAttendance($userId);
        
        // Can check in if no attendance today, or if completed previous day's check-out
        return !$todayAttendance || $todayAttendance->hasCheckedOut();
    }

    /**
     * Get attendance status for today
     */
    public static function getTodayStatus(int $userId): array
    {
        $attendance = self::getTodayAttendance($userId);
        
        if (!$attendance) {
            return [
                'status' => 'not_checked_in',
                'message' => 'Belum check-in hari ini',
                'can_check_in' => true,
                'can_check_out' => false,
                'attendance' => null
            ];
        }
        
        if ($attendance->canCheckOut()) {
            return [
                'status' => 'checked_in',
                'message' => 'Sudah check-in, belum check-out',
                'can_check_in' => false,
                'can_check_out' => true,
                'attendance' => $attendance
            ];
        }
        
        if ($attendance->hasCheckedOut()) {
            return [
                'status' => 'completed',
                'message' => 'Check-in dan check-out sudah selesai',
                'can_check_in' => false,
                'can_check_out' => false,
                'attendance' => $attendance
            ];
        }
        
        return [
            'status' => 'unknown',
            'message' => 'Status tidak diketahui',
            'can_check_in' => false,
            'can_check_out' => false,
            'attendance' => $attendance
        ];
    }

    /**
     * Calculate work duration in minutes using enhanced logic
     * Priority: logical_work_minutes > enhanced calculation > fallback calculation
     */
    public function getWorkDurationAttribute(): ?int
    {
        // Priority 1: Use logical_work_minutes if set (for penalty cases)
        if ($this->logical_work_minutes !== null) {
            return $this->logical_work_minutes;
        }
        
        // Priority 2: Enhanced calculation with shift boundaries and break times
        $enhancedDuration = $this->getEnhancedWorkDurationAttribute();
        if ($enhancedDuration !== null) {
            return $enhancedDuration;
        }
        
        // Priority 3: Fallback to simple calculation
        return $this->getSimpleWorkDurationAttribute();
    }

    /**
     * Enhanced work duration calculation following the 5-step algorithm
     * Step 1: Get shift_start and shift_end
     * Step 2: Get actual check_in and check_out
     * Step 3: Calculate effective_start = max(check_in, shift_start)
     * Step 4: Calculate effective_end = min(check_out, shift_end)
     * Step 5: Apply break time deductions
     */
    public function getEnhancedWorkDurationAttribute(): ?int
    {
        // Must have check-in and check-out times
        if (!$this->time_in || !$this->time_out) {
            return null;
        }

        try {
            // Step 1: Get shift boundaries
            $shiftBoundaries = $this->getShiftBoundaries();
            if (!$shiftBoundaries) {
                return null; // No shift information available
            }

            // Step 2: Parse actual check-in/out times
            $actualCheckIn = $this->getParsedTimeIn();
            $actualCheckOut = $this->getParsedTimeOut();

            if (!$actualCheckIn || !$actualCheckOut) {
                return null;
            }

            // Step 3: Calculate effective start time (max of check-in and shift start)
            $effectiveStart = $actualCheckIn->greaterThan($shiftBoundaries['shift_start']) 
                ? $actualCheckIn 
                : $shiftBoundaries['shift_start'];

            // Step 4: Calculate effective end time (min of check-out and shift end)
            $effectiveEnd = $actualCheckOut->lessThan($shiftBoundaries['shift_end']) 
                ? $actualCheckOut 
                : $shiftBoundaries['shift_end'];

            // Step 5: Calculate raw duration and apply break deductions
            // Handle case where effective end is before effective start
            if ($effectiveEnd->lessThan($effectiveStart)) {
                // Check if this is truly an overnight shift by examining the original shift times
                $shiftBoundaries = $this->getShiftBoundaries();
                if ($shiftBoundaries && $shiftBoundaries['shift_end']->lessThan($shiftBoundaries['shift_start'])) {
                    // This is an overnight shift, add a day to effective end
                    $effectiveEnd->addDay();
                } else {
                    // User checked out before shift started, no effective work time
                    return 0;
                }
            }
            
            $rawDuration = $effectiveStart->diffInMinutes($effectiveEnd);
            if ($rawDuration <= 0) {
                return 0; // No effective work time
            }

            // Apply break time deductions
            $breakMinutes = $this->calculateBreakTimeDeduction($effectiveStart, $effectiveEnd);
            $finalDuration = max(0, $rawDuration - $breakMinutes);

            // Additional validation for overnight shifts
            if ($shiftBoundaries['shift_end']->lessThan($shiftBoundaries['shift_start'])) {
                // This is an overnight shift, validate the calculation
                $expectedRawDuration = $shiftBoundaries['shift_start']->diffInMinutes($shiftBoundaries['shift_end']);
                if ($rawDuration > $expectedRawDuration * 1.5) {
                    // Duration seems too long, recalculate more carefully
                    \Log::warning('Overnight shift duration validation triggered', [
                        'attendance_id' => $this->id,
                        'raw_duration' => $rawDuration,
                        'expected_max' => $expectedRawDuration * 1.5
                    ]);
                }
            }

            // Log if duration seems unusual
            if ($finalDuration > 1440) {
                \Log::warning('Unusually long enhanced work duration detected', [
                    'attendance_id' => $this->id,
                    'user_id' => $this->user_id,
                    'duration_minutes' => $finalDuration,
                    'raw_duration' => $rawDuration,
                    'break_minutes' => $breakMinutes,
                    'effective_start' => $effectiveStart->format('Y-m-d H:i:s'),
                    'effective_end' => $effectiveEnd->format('Y-m-d H:i:s')
                ]);
            }

            return $finalDuration;

        } catch (\Exception $e) {
            \Log::error('Error in enhanced work duration calculation', [
                'attendance_id' => $this->id,
                'error' => $e->getMessage(),
                'time_in' => $this->time_in,
                'time_out' => $this->time_out
            ]);

            return null; // Fall back to simple calculation
        }
    }

    /**
     * Simple work duration calculation (original logic as fallback)
     */
    public function getSimpleWorkDurationAttribute(): ?int
    {
        if (!$this->time_in || !$this->time_out) return null;
        
        try {
            $timeIn = $this->getParsedTimeIn();
            $timeOut = $this->getParsedTimeOut();
            
            if (!$timeIn || !$timeOut) return null;
            
            // Handle overnight shift checkout for simple calculation too
            if ($timeOut->lessThan($timeIn)) {
                $timeOut->addDay();
            }
            
            return max(0, $timeIn->diffInMinutes($timeOut));
            
        } catch (\Exception $e) {
            \Log::error('Error in simple work duration calculation', [
                'attendance_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Get formatted work duration
     */
    public function getFormattedWorkDurationAttribute(): ?string
    {
        $duration = $this->work_duration;
        if ($duration === null) return null;
        if ($duration <= 0) return '0j 0m'; // Handle zero or negative duration
        
        $hours = intval($duration / 60);
        $minutes = $duration % 60;
        
        return sprintf('%dj %dm', $hours, $minutes);
    }

    /**
     * Get target work duration from shift template (in minutes)
     */
    public function getTargetWorkDurationAttribute(): ?int
    {
        // Check if attendance has jadwal_jaga relationship
        if ($this->jadwalJaga && $this->jadwalJaga->shiftTemplate) {
            $shift = $this->jadwalJaga->shiftTemplate;
            $jamMasuk = \Carbon\Carbon::parse($shift->jam_masuk);
            $jamPulang = \Carbon\Carbon::parse($shift->jam_pulang);
            
            // Handle overnight shifts
            if ($jamPulang->lt($jamMasuk)) {
                $jamPulang->addDay();
            }
            
            return $jamPulang->diffInMinutes($jamMasuk);
        }
        
        // Default 8 hours if no shift template
        return 8 * 60; // 480 minutes
    }

    /**
     * Get shortfall minutes (kekurangan menit)
     * Positive = kekurangan, Negative = kelebihan, 0 = pas target
     */
    public function getShortfallMinutesAttribute(): int
    {
        $target = $this->target_work_duration;
        $actual = $this->work_duration ?? 0;
        
        return max(0, $target - $actual); // Only show shortfall, not excess
    }

    /**
     * Get formatted shortfall
     */
    public function getFormattedShortfallAttribute(): string
    {
        $shortfall = $this->shortfall_minutes;
        
        if ($shortfall <= 0) {
            return 'Target tercapai';
        }
        
        $hours = intval($shortfall / 60);
        $minutes = $shortfall % 60;
        
        return sprintf('Kurang %dj %dm', $hours, $minutes);
    }

    /**
     * Get shift boundaries for this attendance record
     */
    public function getShiftBoundaries(): ?array
    {
        $shift = $this->getShiftTemplate();
        if (!$shift) {
            \Log::debug('No shift template found for attendance', ['attendance_id' => $this->id]);
            return null;
        }

        $date = $this->date ? Carbon::parse($this->date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');

        try {
            $shiftStart = Carbon::parse($date . ' ' . $shift->jam_masuk);
            $shiftEnd = Carbon::parse($date . ' ' . $shift->jam_pulang);

            // Handle overnight shifts
            if ($shiftEnd->lessThan($shiftStart)) {
                $shiftEnd->addDay();
            }

            return [
                'shift_start' => $shiftStart,
                'shift_end' => $shiftEnd,
                'shift_template' => $shift,
            ];

        } catch (\Exception $e) {
            \Log::error('Error parsing shift boundaries', [
                'attendance_id' => $this->id,
                'shift_id' => $shift->id,
                'shift_jam_masuk' => $shift->jam_masuk ?? 'NULL',
                'shift_jam_pulang' => $shift->jam_pulang ?? 'NULL',
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get shift template for this attendance record
     */
    public function getShiftTemplate(): ?\App\Models\ShiftTemplate
    {
        // Try to get from direct relationship first
        if ($this->shift) {
            return $this->shift;
        }

        // Try to get from jadwal jaga relationship
        if ($this->jadwalJaga && $this->jadwalJaga->shiftTemplate) {
            return $this->jadwalJaga->shiftTemplate;
        }

        return null;
    }

    /**
     * Get parsed time_in as Carbon instance
     */
    public function getParsedTimeIn(): ?\Carbon\Carbon
    {
        if (!$this->time_in) {
            return null;
        }

        try {
            // If full datetime format
            if (strlen($this->time_in) > 8) {
                return Carbon::parse($this->time_in);
            }

            // If time-only format, combine with date
            $date = $this->date ? Carbon::parse($this->date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
            return Carbon::parse($date . ' ' . $this->time_in);

        } catch (\Exception $e) {
            \Log::error('Error parsing time_in', [
                'attendance_id' => $this->id,
                'time_in' => $this->time_in,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get parsed time_out as Carbon instance
     */
    public function getParsedTimeOut(): ?\Carbon\Carbon
    {
        if (!$this->time_out) {
            return null;
        }

        try {
            // If full datetime format
            if (strlen($this->time_out) > 8) {
                return Carbon::parse($this->time_out);
            }

            // If time-only format, combine with date
            $date = $this->date ? Carbon::parse($this->date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
            $timeOut = Carbon::parse($date . ' ' . $this->time_out);

            // Handle overnight shift checkout
            $timeIn = $this->getParsedTimeIn();
            if ($timeIn && $timeOut->lessThan($timeIn)) {
                $timeOut->addDay();
            }

            return $timeOut;

        } catch (\Exception $e) {
            \Log::error('Error parsing time_out', [
                'attendance_id' => $this->id,
                'time_out' => $this->time_out,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get effective start time (max of check-in and shift start)
     */
    public function getEffectiveStartTimeAttribute(): ?\Carbon\Carbon
    {
        $boundaries = $this->getShiftBoundaries();
        $checkIn = $this->getParsedTimeIn();

        if (!$boundaries || !$checkIn) {
            return $checkIn; // Fallback to actual check-in
        }

        return $checkIn->greaterThan($boundaries['shift_start']) 
            ? $checkIn 
            : $boundaries['shift_start'];
    }

    /**
     * Get effective end time (min of check-out and shift end)
     */
    public function getEffectiveEndTimeAttribute(): ?\Carbon\Carbon
    {
        $boundaries = $this->getShiftBoundaries();
        $checkOut = $this->getParsedTimeOut();

        if (!$boundaries || !$checkOut) {
            return $checkOut; // Fallback to actual check-out
        }

        return $checkOut->lessThan($boundaries['shift_end']) 
            ? $checkOut 
            : $boundaries['shift_end'];
    }

    /**
     * Calculate break time deduction for the work period
     */
    public function calculateBreakTimeDeduction(\Carbon\Carbon $effectiveStart, \Carbon\Carbon $effectiveEnd): int
    {
        $shift = $this->getShiftTemplate();
        if (!$shift || !$shift->break_duration_minutes || $shift->break_duration_minutes <= 0) {
            return 0; // No break time configured
        }

        try {
            return $shift->calculateBreakOverlapMinutes($effectiveStart, $effectiveEnd);
        } catch (\Exception $e) {
            \Log::error('Error calculating break time deduction', [
                'attendance_id' => $this->id,
                'shift_id' => $shift->id,
                'error' => $e->getMessage()
            ]);
            return 0; // Fallback to no break deduction
        }
    }

    /**
     * Get break time deduction for this attendance in minutes
     */
    public function getBreakTimeDeductionAttribute(): int
    {
        $effectiveStart = $this->effective_start_time;
        $effectiveEnd = $this->effective_end_time;

        if (!$effectiveStart || !$effectiveEnd) {
            return 0;
        }

        return $this->calculateBreakTimeDeduction($effectiveStart, $effectiveEnd);
    }

    /**
     * Get attendance percentage (work duration / effective shift duration * 100%)
     */
    public function getAttendancePercentageAttribute(): float
    {
        $shift = $this->getShiftTemplate();
        if (!$shift) {
            return 0.0; // No shift information
        }

        $effectiveShiftDuration = $shift->effective_shift_duration;
        if ($effectiveShiftDuration <= 0) {
            return 0.0; // Invalid shift duration
        }

        $actualWorkDuration = $this->work_duration ?? 0;
        
        // Calculate percentage, capped at 100%
        $percentage = ($actualWorkDuration / $effectiveShiftDuration) * 100;
        return min(100.0, max(0.0, round($percentage, 1)));
    }

    /**
     * Get detailed work duration breakdown
     */
    public function getWorkDurationBreakdownAttribute(): array
    {
        $shift = $this->getShiftTemplate();
        $effectiveStart = $this->effective_start_time;
        $effectiveEnd = $this->effective_end_time;
        $actualCheckIn = $this->getParsedTimeIn();
        $actualCheckOut = $this->getParsedTimeOut();

        $breakdown = [
            'actual_check_in' => $actualCheckIn?->format('H:i'),
            'actual_check_out' => $actualCheckOut?->format('H:i'),
            'effective_start' => $effectiveStart?->format('H:i'),
            'effective_end' => $effectiveEnd?->format('H:i'),
            'raw_duration_minutes' => 0,
            'break_deduction_minutes' => 0,
            'final_duration_minutes' => $this->work_duration ?? 0,
            'attendance_percentage' => $this->attendance_percentage,
        ];

        if ($shift) {
            $breakdown['shift_name'] = $shift->nama_shift;
            $breakdown['shift_start'] = $shift->jam_masuk_format;
            $breakdown['shift_end'] = $shift->jam_pulang_format;
            $breakdown['shift_duration_minutes'] = $shift->total_shift_duration;
            $breakdown['shift_effective_duration_minutes'] = $shift->effective_shift_duration;
            $breakdown['shift_break_minutes'] = $shift->break_duration_minutes ?? 0;
        }

        if ($effectiveStart && $effectiveEnd) {
            $breakdown['raw_duration_minutes'] = $effectiveStart->diffInMinutes($effectiveEnd);
            $breakdown['break_deduction_minutes'] = $this->break_time_deduction;
        }

        return $breakdown;
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope untuk filter berdasarkan user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope untuk attendance hari ini
     */
    public function scopeToday($query)
    {
        return $query->where('date', Carbon::today());
    }

    /**
     * Scope untuk attendance bulan ini
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('date', Carbon::now()->year)
            ->whereMonth('date', Carbon::now()->month);
    }
}