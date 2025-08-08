<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class JadwalJaga extends Model
{
    protected $fillable = [
        'tanggal_jaga',
        'shift_template_id',
        'pegawai_id',
        'unit_instalasi', // Keep for backward compatibility
        'unit_kerja', // New field
        'peran',
        'status_jaga',
        'keterangan',
        'jam_jaga_custom', // Custom start time override
    ];

    protected $casts = [
        'tanggal_jaga' => 'date', // Re-enabled to fix Carbon format issues
        'jam_jaga_custom' => 'datetime:H:i',
    ];

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pegawai_id');
    }

    // For FullCalendar integration
    public function getStartAttribute(): string
    {
        $tanggalJaga = $this->tanggal_jaga instanceof Carbon 
            ? $this->tanggal_jaga 
            : Carbon::parse($this->tanggal_jaga);
            
        return $tanggalJaga->format('Y-m-d') . 'T' . ($this->shiftTemplate->jam_masuk ?? '08:00');
    }

    public function getEndAttribute(): string
    {
        $endDate = $this->tanggal_jaga instanceof Carbon 
            ? $this->tanggal_jaga 
            : Carbon::parse($this->tanggal_jaga);
        $endTime = $this->shiftTemplate->jam_pulang ?? '16:00';
        
        // Handle overnight shifts
        if ($this->shiftTemplate && $this->shiftTemplate->jam_pulang < $this->shiftTemplate->jam_masuk) {
            $endDate = $endDate->addDay();
        }
        
        return $endDate->format('Y-m-d') . 'T' . $endTime;
    }

    /**
     * Get effective start time (custom or from template)
     */
    public function getEffectiveStartTimeAttribute(): string
    {
        if ($this->jam_jaga_custom) {
            return \Carbon\Carbon::parse($this->jam_jaga_custom)->format('H:i');
        }
        
        return $this->shiftTemplate->jam_masuk_format;
    }

    /**
     * Get effective end time from template
     */
    public function getEffectiveEndTimeAttribute(): string
    {
        return $this->shiftTemplate->jam_pulang_format;
    }

    /**
     * Get formatted time range for display
     */
    public function getJamShiftAttribute(): string
    {
        if ($this->jam_jaga_custom) {
            $customTime = \Carbon\Carbon::parse($this->jam_jaga_custom)->format('H:i');
            $endTime = $this->effective_end_time;
            return "{$customTime} - {$endTime}";
        }
        
        return "{$this->effective_start_time} - {$this->effective_end_time}";
    }

    /**
     * Check if this schedule is for today and if it's still valid to be created
     */
    public function isValidForToday(): bool
    {
        $today = \Carbon\Carbon::today('Asia/Jakarta');
        $currentTime = \Carbon\Carbon::now('Asia/Jakarta');
        
        if (!$this->tanggal_jaga->isSameDay($today)) {
            return true; // Future dates are always valid
        }
        
        // For today, check against effective start time
        if ($this->jam_jaga_custom) {
            $customTime = \Carbon\Carbon::parse($this->jam_jaga_custom);
            $scheduleStart = $today->copy()->setHour($customTime->hour)->setMinute($customTime->minute);
        } else {
            $shiftStartTime = \Carbon\Carbon::parse($this->shiftTemplate->jam_masuk);
            $scheduleStart = $today->copy()->setHour($shiftStartTime->hour)->setMinute($shiftStartTime->minute);
        }
        
        return $currentTime->lessThan($scheduleStart);
    }

    public function getTitleAttribute(): string
    {
        return $this->pegawai->name . ' (' . $this->shiftTemplate->nama_shift . ')';
    }

    public function getColorAttribute(): string
    {
        return match($this->status_jaga) {
            'Aktif' => '#10b981', // green
            'Cuti' => '#f59e0b',  // amber
            'Izin' => '#ef4444',  // red
            'OnCall' => '#3b82f6', // blue
            default => '#6b7280'   // gray
        };
    }

    // Conflict validation - check for same shift on same day (allow different shifts)
    public function hasConflict(): bool
    {
        return static::where('pegawai_id', $this->pegawai_id)
            ->where('tanggal_jaga', $this->tanggal_jaga)
            ->where('shift_template_id', $this->shift_template_id) // Only conflict if same shift
            ->where('id', '!=', $this->id ?? 0)
            ->exists();
    }

    // Check if user can be assigned to this shift
    public function canAssignStaff(): bool
    {
        // Allow multiple shifts per day, but not the same shift
        return !$this->hasConflict();
    }

    // Get other shifts for this staff on the same day
    public function getOtherShiftsOnSameDay()
    {
        return static::where('pegawai_id', $this->pegawai_id)
            ->where('tanggal_jaga', $this->tanggal_jaga)
            ->where('shift_template_id', '!=', $this->shift_template_id)
            ->where('id', '!=', $this->id ?? 0)
            ->with('shiftTemplate')
            ->get();
    }

    // Get available staff based on unit type
    public static function getAvailableStaffForUnit($unit_kerja)
    {
        if ($unit_kerja === 'Dokter Jaga') {
            // Get users with dokter role using legacy role relationship
            return User::whereHas('role', function ($query) {
                $query->where('name', 'dokter');
            })->get();
        } else {
            // Get users with non-dokter roles (petugas, paramedis, etc.)
            return User::whereHas('role', function ($query) {
                $query->whereIn('name', ['petugas', 'paramedis', 'bendahara', 'admin']);
            })->get();
        }
    }

    /**
     * Boot method to handle model events for cache invalidation
     */
    protected static function boot()
    {
        parent::boot();

        // Clear dashboard cache when schedule is created, updated, or deleted
        static::created(function ($jadwal) {
            self::clearDashboardCacheForUser($jadwal->pegawai_id);
        });

        static::updated(function ($jadwal) {
            self::clearDashboardCacheForUser($jadwal->pegawai_id);
        });

        static::deleted(function ($jadwal) {
            self::clearDashboardCacheForUser($jadwal->pegawai_id);
        });
    }

    /**
     * Clear dashboard cache for a specific user
     */
    protected static function clearDashboardCacheForUser($userId)
    {
        if (!$userId) return;
        
        // Clear all dashboard-related cache keys for the user
        $cacheKeys = [
            "dokter_dashboard_stats_{$userId}",
            "paramedis_dashboard_stats_{$userId}",
            "user_dashboard_cache_{$userId}",
            "schedule_cache_{$userId}",
            "attendance_status_{$userId}"
        ];
        
        // Clear jadwal jaga cache for all months/years
        $currentYear = now()->year;
        $currentMonth = now()->month;
        
        // Clear current month/year
        $cacheKeys[] = "jadwal_jaga_{$userId}_{$currentMonth}_{$currentYear}";
        
        // Clear previous month
        $prevMonth = $currentMonth - 1;
        $prevYear = $currentYear;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear = $currentYear - 1;
        }
        $cacheKeys[] = "jadwal_jaga_{$userId}_{$prevMonth}_{$prevYear}";
        
        // Clear next month
        $nextMonth = $currentMonth + 1;
        $nextYear = $currentYear;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear = $currentYear + 1;
        }
        $cacheKeys[] = "jadwal_jaga_{$userId}_{$nextMonth}_{$nextYear}";

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        \Log::info("🗑️ Cleared dashboard cache for user {$userId} due to schedule change", [
            'cleared_keys' => $cacheKeys,
            'schedule_id' => 'unknown'
        ]);
    }
}
