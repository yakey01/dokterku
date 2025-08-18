<?php

namespace App\Constants;

/**
 * Centralized validation status constants
 * This ensures consistency across the entire application
 */
class ValidationStatus
{
    // Primary status values (use these everywhere)
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const REVISION = 'need_revision';
    public const CANCELLED = 'cancelled';
    
    // Legacy status values (for backward compatibility during migration)
    public const LEGACY_APPROVED = 'disetujui';
    public const LEGACY_REJECTED = 'ditolak';
    
    /**
     * Get all valid status values
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::REJECTED,
            self::REVISION,
            self::CANCELLED,
        ];
    }
    
    /**
     * Get approved statuses (including legacy)
     * Use this during migration period
     */
    public static function approvedStatuses(): array
    {
        return [
            self::APPROVED,
            self::LEGACY_APPROVED, // Remove after migration
        ];
    }
    
    /**
     * Get rejected statuses (including legacy)
     */
    public static function rejectedStatuses(): array
    {
        return [
            self::REJECTED,
            self::LEGACY_REJECTED, // Remove after migration
        ];
    }
    
    /**
     * Get status labels for display
     */
    public static function labels(): array
    {
        return [
            self::PENDING => 'Menunggu Validasi',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::REVISION => 'Perlu Revisi',
            self::CANCELLED => 'Dibatalkan',
        ];
    }
    
    /**
     * Get status colors for UI
     */
    public static function colors(): array
    {
        return [
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::REVISION => 'info',
            self::CANCELLED => 'gray',
        ];
    }
    
    /**
     * Normalize legacy status to new format
     */
    public static function normalize(string $status): string
    {
        return match($status) {
            self::LEGACY_APPROVED => self::APPROVED,
            self::LEGACY_REJECTED => self::REJECTED,
            default => $status,
        };
    }
    
    /**
     * Check if status is approved (handles legacy)
     */
    public static function isApproved(?string $status): bool
    {
        return in_array($status, self::approvedStatuses());
    }
    
    /**
     * Check if status is rejected (handles legacy)
     */
    public static function isRejected(?string $status): bool
    {
        return in_array($status, self::rejectedStatuses());
    }
}