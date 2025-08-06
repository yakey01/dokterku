<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class UserSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'schedule_name',
        'schedule_type',
        'day_of_week',
        'schedule_date',
        'check_in_time',
        'check_out_time',
        'work_duration_minutes',
        'effective_from',
        'effective_until',
        'is_recurring',
        'status',
        'exceptions',
        'notes',
        'work_location',
        'role_context',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_recurring' => 'boolean',
        'exceptions' => 'array'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(AttendanceViolation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->where('schedule_type', 'daily')
              ->orWhere(function ($sq) use ($date) {
                  $sq->where('schedule_type', 'weekly')
                     ->where('day_of_week', strtolower($date->format('l')));
              })
              ->orWhere(function ($sq) use ($date) {
                  $sq->where('schedule_type', 'custom')
                     ->where('schedule_date', $date->format('Y-m-d'));
              });
        })->where('effective_from', '<=', $date)
          ->where(function ($q) use ($date) {
              $q->whereNull('effective_until')
                ->orWhere('effective_until', '>=', $date);
          });
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper Methods
    public function isValidForDate(Carbon $date): bool
    {
        // Check if schedule is active
        if ($this->status !== 'active') {
            return false;
        }

        // Check effective date range
        if ($date->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_until && $date->gt($this->effective_until)) {
            return false;
        }

        // Check schedule type match
        switch ($this->schedule_type) {
            case 'daily':
                return true;
            case 'weekly':
                return strtolower($date->format('l')) === $this->day_of_week;
            case 'custom':
                return $date->format('Y-m-d') === $this->schedule_date->format('Y-m-d');
            default:
                return false;
        }
    }

    public function getCheckInDateTime(Carbon $date): Carbon
    {
        return $date->copy()->setTimeFromTimeString($this->check_in_time);
    }

    public function getCheckOutDateTime(Carbon $date): Carbon
    {
        return $date->copy()->setTimeFromTimeString($this->check_out_time);
    }

    public function isExceptionDate(Carbon $date): bool
    {
        if (!$this->exceptions) {
            return false;
        }

        return in_array($date->format('Y-m-d'), $this->exceptions);
    }

    public function getWorkDurationMinutes(): int
    {
        if ($this->work_duration_minutes) {
            return $this->work_duration_minutes;
        }

        $checkIn = Carbon::createFromFormat('H:i', $this->check_in_time);
        $checkOut = Carbon::createFromFormat('H:i', $this->check_out_time);

        return $checkOut->diffInMinutes($checkIn);
    }
}