<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    protected $fillable = [
        'nama_shift',
        'jam_masuk',
        'jam_pulang',
        'break_duration_minutes',
        'break_start_time',
        'break_end_time',
        'is_break_flexible',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_break_flexible' => 'boolean',
        'break_duration_minutes' => 'integer',
    ];

    public function jadwalJagas(): HasMany
    {
        return $this->hasMany(JadwalJaga::class);
    }

    /**
     * Get jam_masuk as time string only
     */
    public function getJamMasukAttribute($value): string
    {
        // If value is already a time string (HH:MM:SS), return it
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return $value;
        }
        // If it contains a date, extract just the time
        return \Carbon\Carbon::parse($value)->format('H:i:s');
    }

    /**
     * Get jam_pulang as time string only
     */
    public function getJamPulangAttribute($value): string
    {
        // If value is already a time string (HH:MM:SS), return it
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return $value;
        }
        // If it contains a date, extract just the time
        return \Carbon\Carbon::parse($value)->format('H:i:s');
    }

    public function getDurasiAttribute(): string
    {
        $masuk = \Carbon\Carbon::parse($this->jam_masuk);
        $pulang = \Carbon\Carbon::parse($this->jam_pulang);
        
        // Handle overnight shifts
        if ($pulang->lessThan($masuk)) {
            $pulang->addDay();
        }
        
        $durasi = $pulang->diff($masuk);
        return $durasi->format('%h jam %i menit');
    }

    /**
     * Get formatted time for display (HH:MM only)
     */
    public function getJamMasukFormatAttribute(): string
    {
        return \Carbon\Carbon::parse($this->jam_masuk)->format('H:i');
    }

    /**
     * Get formatted time for display (HH:MM only)
     */
    public function getJamPulangFormatAttribute(): string
    {
        return \Carbon\Carbon::parse($this->jam_pulang)->format('H:i');
    }

    /**
     * Get formatted shift display for dropdowns
     */
    public function getShiftDisplayAttribute(): string
    {
        return "{$this->nama_shift} ({$this->jam_masuk_format} - {$this->jam_pulang_format})";
    }

    /**
     * Get shift duration in hours (for API consistency)
     */
    public function getDurasiJamAttribute(): float
    {
        $masuk = \Carbon\Carbon::parse($this->jam_masuk);
        $pulang = \Carbon\Carbon::parse($this->jam_pulang);
        
        // Handle overnight shifts
        if ($pulang->lessThan($masuk)) {
            $pulang->addDay();
        }
        
        return $pulang->diffInHours($masuk, true); // true = return as float
    }

    /**
     * Get effective shift duration in minutes (excluding breaks)
     */
    public function getEffectiveShiftDurationAttribute(): int
    {
        $totalDuration = $this->getTotalShiftDurationAttribute();
        return max(0, $totalDuration - ($this->break_duration_minutes ?? 0));
    }

    /**
     * Get total shift duration in minutes (including breaks)
     */
    public function getTotalShiftDurationAttribute(): int
    {
        $masuk = \Carbon\Carbon::parse($this->jam_masuk);
        $pulang = \Carbon\Carbon::parse($this->jam_pulang);
        
        // Handle overnight shifts
        if ($pulang->lessThan($masuk)) {
            $pulang->addDay();
        }
        
        return $pulang->diffInMinutes($masuk);
    }

    /**
     * Get break start time as Carbon instance for a specific date
     */
    public function getBreakStartTimeForDate(string $date): ?\Carbon\Carbon
    {
        if (!$this->break_start_time) {
            return null;
        }
        
        return \Carbon\Carbon::parse($date . ' ' . $this->break_start_time);
    }

    /**
     * Get break end time as Carbon instance for a specific date
     */
    public function getBreakEndTimeForDate(string $date): ?\Carbon\Carbon
    {
        if (!$this->break_end_time) {
            // Calculate from break_start_time + break_duration_minutes if no explicit end time
            $breakStart = $this->getBreakStartTimeForDate($date);
            if ($breakStart && $this->break_duration_minutes) {
                return $breakStart->copy()->addMinutes($this->break_duration_minutes);
            }
            return null;
        }
        
        $breakEnd = \Carbon\Carbon::parse($date . ' ' . $this->break_end_time);
        
        // Handle overnight break (rare but possible)
        $breakStart = $this->getBreakStartTimeForDate($date);
        if ($breakStart && $breakEnd->lessThan($breakStart)) {
            $breakEnd->addDay();
        }
        
        return $breakEnd;
    }

    /**
     * Check if break time overlaps with given time range
     */
    public function hasBreakOverlap(\Carbon\Carbon $startTime, \Carbon\Carbon $endTime): bool
    {
        if (!$this->break_duration_minutes || $this->break_duration_minutes <= 0) {
            return false;
        }

        // For flexible breaks, assume break happens within the work period
        if ($this->is_break_flexible) {
            return true; // Break time will be deducted from total work time
        }

        // For fixed breaks, check actual overlap
        $breakStart = $this->getBreakStartTimeForDate($startTime->format('Y-m-d'));
        $breakEnd = $this->getBreakEndTimeForDate($startTime->format('Y-m-d'));

        if (!$breakStart || !$breakEnd) {
            return false;
        }

        // Check if break time overlaps with work time
        return $breakStart->lessThan($endTime) && $breakEnd->greaterThan($startTime);
    }

    /**
     * Calculate overlap minutes between break time and work period
     */
    public function calculateBreakOverlapMinutes(\Carbon\Carbon $startTime, \Carbon\Carbon $endTime): int
    {
        if (!$this->hasBreakOverlap($startTime, $endTime)) {
            return 0;
        }

        // For flexible breaks, return the full break duration (up to work duration)
        if ($this->is_break_flexible) {
            $workDuration = $startTime->diffInMinutes($endTime);
            return min($this->break_duration_minutes, $workDuration);
        }

        // For fixed breaks, calculate actual overlap
        $breakStart = $this->getBreakStartTimeForDate($startTime->format('Y-m-d'));
        $breakEnd = $this->getBreakEndTimeForDate($startTime->format('Y-m-d'));

        if (!$breakStart || !$breakEnd) {
            return 0;
        }

        // Calculate overlap between [startTime, endTime] and [breakStart, breakEnd]
        $overlapStart = $startTime->greaterThan($breakStart) ? $startTime : $breakStart;
        $overlapEnd = $endTime->lessThan($breakEnd) ? $endTime : $breakEnd;

        return max(0, $overlapStart->diffInMinutes($overlapEnd));
    }
}
