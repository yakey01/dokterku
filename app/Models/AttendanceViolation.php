<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'user_schedule_id',
        'violation_type',
        'action_type',
        'attempted_at',
        'scheduled_at',
        'tolerance_minutes',
        'violation_minutes',
        'severity',
        'is_excused',
        'location_attempted',
        'latitude',
        'longitude',
        'reason',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'is_emergency_override',
        'overridden_by',
        'override_reason'
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'resolved_at' => 'datetime',
        'is_excused' => 'boolean',
        'is_emergency_override' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function userSchedule(): BelongsTo
    {
        return $this->belongsTo(UserSchedule::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByViolationType($query, string $type)
    {
        return $query->where('violation_type', $type);
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'auto_resolved']);
    }

    public function getSeverityColor(): string
    {
        return match($this->severity) {
            'minor' => 'success',
            'moderate' => 'warning',
            'major' => 'danger',
            'critical' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'auto_resolved' => 'secondary',
            default => 'secondary'
        };
    }

    public function getViolationDescription(): string
    {
        $baseDescription = match($this->violation_type) {
            'no_schedule' => 'No schedule found',
            'late_checkin' => 'Late check-in',
            'early_checkin' => 'Early check-in',
            'late_checkout' => 'Late check-out',
            'early_checkout' => 'Early check-out',
            'missed_checkin' => 'Missed check-in',
            'missed_checkout' => 'Missed check-out',
            'outside_tolerance' => 'Outside tolerance window',
            default => 'Unknown violation'
        };

        if ($this->violation_minutes > 0) {
            $baseDescription .= " ({$this->violation_minutes} minutes)";
        }

        return $baseDescription;
    }
}