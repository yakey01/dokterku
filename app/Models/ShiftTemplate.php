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
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
