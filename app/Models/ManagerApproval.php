<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class ManagerApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_type',
        'title',
        'description',
        'amount',
        'requester_role',
        'requested_by',
        'approved_by',
        'status',
        'priority',
        'justification',
        'approval_notes',
        'supporting_data',
        'required_by',
        'approved_at',
        'rejected_at',
        'auto_approved',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'supporting_data' => 'array',
        'required_by' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'auto_approved' => 'boolean',
    ];

    // Relationships
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Accessors & Mutators
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'escalated' => 'warning',
            'pending' => 'gray',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'gray',
            default => 'gray',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->required_by && Carbon::now()->gt($this->required_by) && $this->status === 'pending';
    }

    public function getDaysUntilDueAttribute(): int
    {
        if (!$this->required_by) {
            return 999;
        }
        
        return max(0, Carbon::now()->diffInDays($this->required_by, false));
    }

    public function getFormattedAmountAttribute(): string
    {
        if (!$this->amount) {
            return 'N/A';
        }
        
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeHighValue($query)
    {
        return $query->where('amount', '>', 500000);
    }

    public function scopeOverdue($query)
    {
        return $query->where('required_by', '<', Carbon::now())
                     ->where('status', 'pending');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('approval_type', $type);
    }

    // Business Logic Methods
    public function approve(User $manager, ?string $notes = null): bool
    {
        $this->status = 'approved';
        $this->approved_by = $manager->id;
        $this->approved_at = Carbon::now();
        
        if ($notes) {
            $this->approval_notes = $notes;
        }
        
        return $this->save();
    }

    public function reject(User $manager, string $reason): bool
    {
        $this->status = 'rejected';
        $this->approved_by = $manager->id;
        $this->rejected_at = Carbon::now();
        $this->approval_notes = $reason;
        
        return $this->save();
    }

    public function escalate(): bool
    {
        $this->status = 'escalated';
        return $this->save();
    }

    // Static Methods
    public static function getApprovalTypeOptions(): array
    {
        return [
            'financial' => '💰 Financial',
            'policy_override' => '📋 Policy Override',
            'staff_action' => '👥 Staff Action',
            'emergency' => '🚨 Emergency',
            'budget_adjustment' => '📊 Budget Adjustment',
        ];
    }

    public static function getPriorityOptions(): array
    {
        return [
            'urgent' => '🚨 Urgent',
            'high' => '🔴 High',
            'medium' => '🟡 Medium',
            'low' => '🟢 Low',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'pending' => '⏳ Pending',
            'approved' => '✅ Approved',
            'rejected' => '❌ Rejected',
            'escalated' => '⬆️ Escalated',
        ];
    }
}
