<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class StrategicGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'period',
        'start_date',
        'end_date',
        'target_value',
        'current_value',
        'unit',
        'status',
        'success_criteria',
        'priority',
        'created_by',
        'assigned_to',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'success_criteria' => 'array',
        'completed_at' => 'datetime',
        'priority' => 'integer',
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Accessors & Mutators
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_value == 0) {
            return 0;
        }
        
        return min(100, ($this->current_value / $this->target_value) * 100);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'completed' => 'info',
            'paused' => 'warning',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date) {
            return 0;
        }
        
        return max(0, Carbon::now()->diffInDays($this->end_date, false));
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->end_date && Carbon::now()->gt($this->end_date) && $this->status !== 'completed';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeOverdue($query)
    {
        return $query->where('end_date', '<', Carbon::now())
                     ->where('status', '!=', 'completed');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '<=', 3);
    }

    // Business Logic Methods
    public function updateProgress(float $newValue, ?string $notes = null): bool
    {
        $this->current_value = $newValue;
        
        if ($notes) {
            $this->notes = $notes;
        }
        
        // Auto-complete if target reached
        if ($newValue >= $this->target_value && $this->status === 'active') {
            $this->status = 'completed';
            $this->completed_at = Carbon::now();
        }
        
        return $this->save();
    }

    public function calculateScore(): int
    {
        $progressScore = min(100, $this->progress_percentage);
        $timeScore = $this->is_overdue ? 0 : 100;
        $priorityScore = (11 - $this->priority) * 10; // Higher priority = higher score
        
        return round(($progressScore * 0.6) + ($timeScore * 0.2) + ($priorityScore * 0.2));
    }

    // Static Methods
    public static function getCategoryOptions(): array
    {
        return [
            'financial' => '💰 Financial',
            'operational' => '⚙️ Operational', 
            'quality' => '⭐ Quality',
            'growth' => '📈 Growth',
            'staff' => '👥 Staff',
            'patient_satisfaction' => '😊 Patient Satisfaction',
        ];
    }

    public static function getPeriodOptions(): array
    {
        return [
            'monthly' => '📅 Monthly',
            'quarterly' => '📆 Quarterly',
            'yearly' => '🗓️ Yearly',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'draft' => '📝 Draft',
            'active' => '🟢 Active',
            'completed' => '✅ Completed',
            'paused' => '⏸️ Paused',
            'cancelled' => '❌ Cancelled',
        ];
    }
}