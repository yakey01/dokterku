<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceToleranceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_name',
        'scope_type',
        'scope_value',
        'check_in_early_tolerance',
        'check_in_late_tolerance',
        'check_out_early_tolerance',
        'check_out_late_tolerance',
        'require_schedule_match',
        'allow_early_checkin',
        'allow_late_checkin',
        'allow_early_checkout',
        'allow_late_checkout',
        'allow_emergency_override',
        'emergency_override_roles',
        'emergency_override_duration',
        'weekend_different_tolerance',
        'weekend_check_in_tolerance',
        'weekend_check_out_tolerance',
        'holiday_different_tolerance',
        'holiday_check_in_tolerance',
        'holiday_check_out_tolerance',
        'is_active',
        'priority',
        'description',
        'additional_rules',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'require_schedule_match' => 'boolean',
        'allow_early_checkin' => 'boolean',
        'allow_late_checkin' => 'boolean',
        'allow_early_checkout' => 'boolean',
        'allow_late_checkout' => 'boolean',
        'allow_emergency_override' => 'boolean',
        'emergency_override_roles' => 'array',
        'weekend_different_tolerance' => 'boolean',
        'holiday_different_tolerance' => 'boolean',
        'is_active' => 'boolean',
        'additional_rules' => 'array'
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function scopeForScope($query, string $scopeType, string $scopeValue = null)
    {
        $query = $query->where('scope_type', $scopeType);
        
        if ($scopeValue !== null) {
            $query = $query->where('scope_value', $scopeValue);
        }
        
        return $query;
    }

    // Helper Methods
    public function appliesToUser(User $user): bool
    {
        switch ($this->scope_type) {
            case 'global':
                return true;
            case 'role':
                return $user->roles->pluck('name')->contains($this->scope_value);
            case 'user':
                return $user->id == $this->scope_value;
            case 'location':
                // You might need to add location logic based on your system
                return true;
            default:
                return false;
        }
    }

    public function getToleranceForAction(string $action, bool $isWeekend = false, bool $isHoliday = false): array
    {
        $tolerance = [];

        if ($isWeekend && $this->weekend_different_tolerance) {
            switch ($action) {
                case 'checkin':
                    $tolerance['early'] = $this->weekend_check_in_tolerance ?? $this->check_in_early_tolerance;
                    $tolerance['late'] = $this->weekend_check_in_tolerance ?? $this->check_in_late_tolerance;
                    break;
                case 'checkout':
                    $tolerance['early'] = $this->weekend_check_out_tolerance ?? $this->check_out_early_tolerance;
                    $tolerance['late'] = $this->weekend_check_out_tolerance ?? $this->check_out_late_tolerance;
                    break;
            }
        } elseif ($isHoliday && $this->holiday_different_tolerance) {
            switch ($action) {
                case 'checkin':
                    $tolerance['early'] = $this->holiday_check_in_tolerance ?? $this->check_in_early_tolerance;
                    $tolerance['late'] = $this->holiday_check_in_tolerance ?? $this->check_in_late_tolerance;
                    break;
                case 'checkout':
                    $tolerance['early'] = $this->holiday_check_out_tolerance ?? $this->check_out_early_tolerance;
                    $tolerance['late'] = $this->holiday_check_out_tolerance ?? $this->check_out_late_tolerance;
                    break;
            }
        } else {
            switch ($action) {
                case 'checkin':
                    $tolerance['early'] = $this->check_in_early_tolerance;
                    $tolerance['late'] = $this->check_in_late_tolerance;
                    break;
                case 'checkout':
                    $tolerance['early'] = $this->check_out_early_tolerance;
                    $tolerance['late'] = $this->check_out_late_tolerance;
                    break;
            }
        }

        return $tolerance;
    }

    public function canOverrideEmergency(User $user): bool
    {
        if (!$this->allow_emergency_override) {
            return false;
        }

        if (!$this->emergency_override_roles) {
            return false;
        }

        return $user->roles->pluck('name')->intersect($this->emergency_override_roles)->isNotEmpty();
    }
}