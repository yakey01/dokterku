<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
        'location_type',
        'unit_kerja',
        'allowed_shifts',
        'working_hours',
        'tolerance_settings',
        'contact_person',
        'contact_phone',
        'require_photo',
        'strict_geofence',
        'gps_accuracy_required',
        // Individual tolerance fields for better performance
        'late_tolerance_minutes',
        'early_departure_tolerance_minutes',
        'break_time_minutes',
        'overtime_threshold_minutes',
        'checkin_before_shift_minutes',
        'checkout_after_shift_minutes',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'require_photo' => 'boolean',
        'strict_geofence' => 'boolean',
        'allowed_shifts' => 'array',
        'working_hours' => 'array',
        'tolerance_settings' => 'array',
        // Individual tolerance fields with validation
        'late_tolerance_minutes' => 'integer',
        'early_departure_tolerance_minutes' => 'integer',
        'break_time_minutes' => 'integer',
        'overtime_threshold_minutes' => 'integer',
        'checkin_before_shift_minutes' => 'integer',
        'checkout_after_shift_minutes' => 'integer',
    ];

    /**
     * Relationship with Users (paramedis assigned to this location)
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'work_location_id');
    }

    /**
     * Relationship with creator (admin who created this location)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship with attendances at this location
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get location type label
     */
    public function getLocationTypeLabelAttribute(): string
    {
        return match($this->location_type) {
            'main_office' => '🏢 Kantor Pusat',
            'branch_office' => '🏪 Kantor Cabang',
            'project_site' => '🚧 Lokasi Proyek',
            'mobile_location' => '📱 Lokasi Mobile',
            'client_office' => '🤝 Kantor Klien',
            default => '📍 Lokasi Kerja',
        };
    }

    /**
     * Check if coordinates are within geofence
     */
    public function isWithinGeofence(float $latitude, float $longitude, ?float $accuracy = null): bool
    {
        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance($latitude, $longitude);
        
        // Add GPS accuracy tolerance if provided (max 50 meters)
        $effectiveRadius = $this->radius_meters;
        if ($accuracy) {
            $effectiveRadius += min($accuracy, 50);
        }
        
        return $distance <= $effectiveRadius;
    }

    /**
     * Calculate distance between two coordinates in meters
     */
    public function calculateDistance(float $lat2, float $lon2): float
    {
        $lat1 = (float) $this->latitude;
        $lon1 = (float) $this->longitude;
        
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * Get Google Maps URL
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        return "https://maps.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Get coordinates as array
     */
    public function getCoordinatesAttribute(): array
    {
        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
        ];
    }

    /**
     * Get location for Filament Google Maps
     */
    public function getLocationAttribute(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Set location from Filament Google Maps
     */
    public function setLocationAttribute(?array $location): void
    {
        if (is_array($location)) {
            $this->attributes['latitude'] = $location['lat'] ?? null;
            $this->attributes['longitude'] = $location['lng'] ?? null;
            unset($this->attributes['location']);
        }
    }

    /**
     * Scope for active locations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for location type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('location_type', $type);
    }

    /**
     * Get formatted radius
     */
    public function getFormattedRadiusAttribute(): string
    {
        if ($this->radius_meters >= 1000) {
            return number_format($this->radius_meters / 1000, 1) . ' km';
        }
        return $this->radius_meters . ' m';
    }

    /**
     * Check if shift is allowed at this location
     */
    public function isShiftAllowed(string $shift): bool
    {
        if (empty($this->allowed_shifts)) {
            return true; // All shifts allowed if not specified
        }
        
        return in_array($shift, $this->allowed_shifts);
    }

    /**
     * Get working hours for specific day
     */
    public function getWorkingHours(?string $day = null): ?array
    {
        if (empty($this->working_hours)) {
            return null;
        }
        
        if ($day) {
            return $this->working_hours[$day] ?? null;
        }
        
        return $this->working_hours;
    }

    /**
     * Get tolerance settings (backward compatibility + new individual fields)
     */
    public function getToleranceSettings(): array
    {
        return [
            'late_tolerance_minutes' => $this->late_tolerance_minutes ?? 15,
            'early_departure_tolerance_minutes' => $this->early_departure_tolerance_minutes ?? 15,
            'break_time_minutes' => $this->break_time_minutes ?? 60,
            'overtime_threshold_minutes' => $this->overtime_threshold_minutes ?? 480,
        ];
    }
    
    /**
     * Validation rules for tolerance settings
     */
    public static function getToleranceValidationRules(): array
    {
        return [
            'late_tolerance_minutes' => 'integer|min:0|max:60',
            'early_departure_tolerance_minutes' => 'integer|min:0|max:60', 
            'break_time_minutes' => 'integer|min:15|max:120',
            'overtime_threshold_minutes' => 'integer|min:420|max:600', // 7-10 hours
        ];
    }
    
    /**
     * Get formatted shift time with tolerance
     */
    public function getShiftTimeWithTolerance(string $shiftStart, string $shiftEnd): array
    {
        $startTime = \Carbon\Carbon::createFromFormat('H:i', $shiftStart);
        $endTime = \Carbon\Carbon::createFromFormat('H:i', $shiftEnd);
        
        $checkInStart = $startTime->copy()->subMinutes($this->late_tolerance_minutes ?? 15);
        $checkInEnd = $startTime->copy()->addMinutes($this->late_tolerance_minutes ?? 15);
        $checkOutStart = $endTime->copy()->subMinutes($this->early_departure_tolerance_minutes ?? 15);
        
        return [
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
            'check_in_allowed_from' => $checkInStart->format('H:i'),
            'check_in_allowed_until' => $checkInEnd->format('H:i'),
            'check_out_allowed_from' => $checkOutStart->format('H:i'),
            'late_tolerance' => $this->late_tolerance_minutes ?? 15,
            'early_departure_tolerance' => $this->early_departure_tolerance_minutes ?? 15,
        ];
    }
    
    /**
     * Get tolerance status description
     */
    public function getToleranceStatusDescription(int $minutes, string $type): string
    {
        return match($type) {
            'late' => match(true) {
                $minutes === 0 => '⚡ Tidak ada toleransi - harus tepat waktu',
                $minutes <= 5 => '🟢 Toleransi ketat - disiplin tinggi',
                $minutes <= 15 => '🟡 Toleransi normal - standar perusahaan',
                $minutes <= 30 => '🟠 Toleransi longgar - fleksibel',
                default => '🔴 Toleransi sangat longgar - perlu review'
            },
            'early' => match(true) {
                $minutes === 0 => '⚡ Harus sampai jam selesai shift',
                $minutes <= 10 => '🟢 Toleransi minimal',
                $minutes <= 20 => '🟡 Toleransi standar',
                default => '🟠 Toleransi fleksibel'
            },
            default => 'Pengaturan toleransi'
        };
    }


    /**
     * Assignment histories for this location
     */
    public function assignmentHistories(): HasMany
    {
        return $this->hasMany(AssignmentHistory::class);
    }

    /**
     * Get location validations
     */
    public function locationValidations(): HasMany
    {
        return $this->hasMany(\App\Models\LocationValidation::class);
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($workLocation) {
            // When soft deleting, also deactivate the location
            if (!$workLocation->isForceDeleting()) {
                $workLocation->is_active = false;
                $workLocation->save();
            }
        });

        static::restoring(function ($workLocation) {
            // When restoring, reactivate the location
            $workLocation->is_active = true;
            $workLocation->save();
        });
    }

    /**
     * Check if location can be safely deleted
     */
    public function canBeDeleted(): array
    {
        $assignedUsersCount = $this->users()->count();
        $attendancesCount = $this->attendances()->count();
        $locationValidationsCount = 0;
        
        // Check location validations if the relationship exists
        try {
            $locationValidationsCount = $this->locationValidations()->count();
        } catch (\Exception $e) {
            // Relationship doesn't exist or table doesn't exist
        }

        $canDelete = $assignedUsersCount === 0 && $attendancesCount === 0 && $locationValidationsCount === 0;

        return [
            'can_delete' => $canDelete,
            'assigned_users' => $assignedUsersCount,
            'attendances' => $attendancesCount,
            'location_validations' => $locationValidationsCount,
            'blocking_reason' => !$canDelete ? $this->getBlockingReason($assignedUsersCount, $attendancesCount, $locationValidationsCount) : null
        ];
    }

    /**
     * Get blocking reason for deletion
     */
    protected function getBlockingReason(int $users, int $attendances, int $validations): string
    {
        $reasons = [];
        
        if ($users > 0) {
            $reasons[] = "{$users} assigned user(s)";
        }
        
        if ($attendances > 0) {
            $reasons[] = "{$attendances} attendance record(s)";
        }
        
        if ($validations > 0) {
            $reasons[] = "{$validations} location validation(s)";
        }
        
        return 'Has ' . implode(', ', $reasons);
    }

    /**
     * Check if location requires photo verification
     */
    public function requiresPhoto(): bool
    {
        return $this->require_photo;
    }

    /**
     * Get location capacity utilization
     */
    public function getCapacityUtilization(int $optimalCapacity = 50): array
    {
        $currentCount = $this->users()->count();
        $percentage = $optimalCapacity > 0 ? round(($currentCount / $optimalCapacity) * 100, 1) : 0;
        
        return [
            'current_users' => $currentCount,
            'optimal_capacity' => $optimalCapacity,
            'utilization_percentage' => $percentage,
            'status' => $this->getCapacityStatus($percentage)
        ];
    }

    /**
     * Get capacity status label
     */
    private function getCapacityStatus(float $percentage): string
    {
        if ($percentage >= 90) return 'over_capacity';
        if ($percentage >= 75) return 'high_utilization';
        if ($percentage >= 50) return 'optimal';
        if ($percentage >= 25) return 'low_utilization';
        return 'under_utilized';
    }
}