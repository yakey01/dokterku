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
        'date' => 'date',
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
     * Calculate work duration in minutes
     */
    public function getWorkDurationAttribute(): ?int
    {
        if (!$this->time_in || !$this->time_out) return null;
        
        // Parse times - handle both datetime and time-only formats
        try {
            // If full datetime format
            if (strlen($this->time_in) > 8) {
                $timeIn = Carbon::parse($this->time_in);
                $timeOut = Carbon::parse($this->time_out);
            } else {
                // If time-only format (HH:MM:SS), use today's date
                $today = $this->date ?? Carbon::today()->format('Y-m-d');
                $timeIn = Carbon::parse($today . ' ' . $this->time_in);
                $timeOut = Carbon::parse($today . ' ' . $this->time_out);
                
                // Handle overnight shift (checkout next day)
                if ($timeOut->lt($timeIn)) {
                    $timeOut->addDay();
                }
            }
            
            // Calculate duration (always positive)
            $duration = $timeOut->diffInMinutes($timeIn);
            
            // Log if duration seems unusual (> 24 hours)
            if ($duration > 1440) {
                \Log::warning('Unusually long work duration detected', [
                    'attendance_id' => $this->id,
                    'user_id' => $this->user_id,
                    'duration_minutes' => $duration,
                    'time_in' => $this->time_in,
                    'time_out' => $this->time_out
                ]);
            }
            
            return $duration;
            
        } catch (\Exception $e) {
            \Log::error('Error calculating work duration', [
                'attendance_id' => $this->id,
                'error' => $e->getMessage(),
                'time_in' => $this->time_in,
                'time_out' => $this->time_out
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