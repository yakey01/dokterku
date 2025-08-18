<?php

namespace App\Traits;

use App\Constants\ValidationStatus;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Trait for models that have validation workflow with audit logging
 */
trait ValidatesWithAudit
{
    /**
     * Boot the trait
     */
    public static function bootValidatesWithAudit()
    {
        // Log when validation status changes
        static::updating(function ($model) {
            if ($model->isDirty('status_validasi')) {
                $oldStatus = $model->getOriginal('status_validasi');
                $newStatus = $model->status_validasi;
                
                // Normalize statuses for comparison
                $oldStatusNormalized = ValidationStatus::normalize($oldStatus);
                $newStatusNormalized = ValidationStatus::normalize($newStatus);
                
                // Only log if the normalized status actually changed
                if ($oldStatusNormalized !== $newStatusNormalized) {
                    static::logValidationChange($model, $oldStatus, $newStatus);
                }
            }
        });
        
        // Set validation metadata when status changes to approved/rejected
        static::updating(function ($model) {
            if ($model->isDirty('status_validasi')) {
                $newStatus = ValidationStatus::normalize($model->status_validasi);
                
                if (in_array($newStatus, [ValidationStatus::APPROVED, ValidationStatus::REJECTED])) {
                    $model->validasi_by = Auth::id();
                    $model->validasi_at = now();
                }
            }
        });
    }
    
    /**
     * Log validation status change
     */
    protected static function logValidationChange($model, $oldStatus, $newStatus)
    {
        $userId = Auth::id();
        $userName = Auth::user() ? Auth::user()->name : 'System';
        
        // Log to audit log table
        AuditLog::create([
            'user_id' => $userId,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'event' => 'validation_status_changed',
            'old_values' => json_encode(['status_validasi' => $oldStatus]),
            'new_values' => json_encode(['status_validasi' => $newStatus]),
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        // Log to application log
        Log::info('Validation status changed', [
            'model' => class_basename($model),
            'model_id' => $model->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userName,
            'changed_by_id' => $userId,
            'timestamp' => now()->toIso8601String(),
        ]);
        
        // Log critical financial validations
        if (in_array(class_basename($model), ['JumlahPasienHarian', 'Jaspel', 'Tindakan'])) {
            Log::channel('financial')->info('Financial validation status changed', [
                'model' => class_basename($model),
                'model_id' => $model->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'amount' => $model->jaspel_rupiah ?? $model->nominal ?? $model->tarif ?? 0,
                'changed_by' => $userName,
                'changed_by_id' => $userId,
            ]);
        }
    }
    
    /**
     * Scope for pending validation
     */
    public function scopePending($query)
    {
        return $query->where('status_validasi', ValidationStatus::PENDING);
    }
    
    /**
     * Scope for approved records
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status_validasi', ValidationStatus::approvedStatuses());
    }
    
    /**
     * Scope for rejected records
     */
    public function scopeRejected($query)
    {
        return $query->whereIn('status_validasi', ValidationStatus::rejectedStatuses());
    }
    
    /**
     * Check if record is approved
     */
    public function getIsApprovedAttribute(): bool
    {
        return ValidationStatus::isApproved($this->status_validasi);
    }
    
    /**
     * Check if record is rejected
     */
    public function getIsRejectedAttribute(): bool
    {
        return ValidationStatus::isRejected($this->status_validasi);
    }
    
    /**
     * Check if record is pending
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status_validasi === ValidationStatus::PENDING;
    }
    
    /**
     * Get validation status label
     */
    public function getStatusValidasiLabelAttribute(): string
    {
        return ValidationStatus::labels()[$this->status_validasi] ?? $this->status_validasi;
    }
    
    /**
     * Get validation status color
     */
    public function getStatusValidasiColorAttribute(): string
    {
        return ValidationStatus::colors()[$this->status_validasi] ?? 'gray';
    }
}