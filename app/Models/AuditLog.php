<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'action',
        'model_type',
        'model_id',
        'description',
        'properties',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'session_id',
        'model_data',
    ];

    protected $casts = [
        'properties' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'model_data' => 'array',
    ];

    // Action constants
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_PASSWORD_RESET = 'password_reset';
    public const ACTION_ROLE_CHANGED = 'role_changed';
    public const ACTION_PERMISSION_GRANTED = 'permission_granted';
    public const ACTION_PERMISSION_REVOKED = 'permission_revoked';
    public const ACTION_SYSTEM_SETTING_CHANGED = 'system_setting_changed';
    public const ACTION_FEATURE_FLAG_TOGGLED = 'feature_flag_toggled';
    public const ACTION_MAINTENANCE_MODE_TOGGLED = 'maintenance_mode_toggled';
    public const ACTION_BULK_OPERATION = 'bulk_operation';
    public const ACTION_DATA_EXPORT = 'data_export';
    public const ACTION_DATA_IMPORT = 'data_import';
    public const ACTION_BACKUP_CREATED = 'backup_created';
    public const ACTION_BACKUP_RESTORED = 'backup_restored';
    public const ACTION_SECURITY_EVENT = 'security_event';
    public const ACTION_VALIDATION_STATUS_CHANGED = 'validation_status_changed';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }
        return null;
    }

    /**
     * Get the human-readable model name
     */
    public function getModelNameAttribute(): string
    {
        if (!$this->model_type) {
            return 'N/A';
        }

        $className = class_basename($this->model_type);
        return ucfirst(str_replace('_', ' ', snake_case($className)));
    }

    /**
     * Get the action description
     */
    public function getActionDescriptionAttribute(): string
    {
        $descriptions = [
            self::ACTION_CREATED => 'Created',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_DELETED => 'Deleted',
            self::ACTION_LOGIN => 'Logged in',
            self::ACTION_LOGOUT => 'Logged out',
            self::ACTION_PASSWORD_RESET => 'Reset password',
            self::ACTION_ROLE_CHANGED => 'Changed role',
            self::ACTION_PERMISSION_GRANTED => 'Granted permission',
            self::ACTION_PERMISSION_REVOKED => 'Revoked permission',
            self::ACTION_SYSTEM_SETTING_CHANGED => 'Changed system setting',
            self::ACTION_FEATURE_FLAG_TOGGLED => 'Toggled feature flag',
            self::ACTION_MAINTENANCE_MODE_TOGGLED => 'Toggled maintenance mode',
            self::ACTION_BULK_OPERATION => 'Performed bulk operation',
            self::ACTION_DATA_EXPORT => 'Exported data',
            self::ACTION_DATA_IMPORT => 'Imported data',
            self::ACTION_BACKUP_CREATED => 'Created backup',
            self::ACTION_BACKUP_RESTORED => 'Restored backup',
            self::ACTION_SECURITY_EVENT => 'Security event',
            self::ACTION_VALIDATION_STATUS_CHANGED => 'Changed validation status',
            self::ACTION_APPROVED => 'Approved',
            self::ACTION_REJECTED => 'Rejected',
        ];

        return $descriptions[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    /**
     * Get risk level based on action
     */
    public function getRiskLevelAttribute(): string
    {
        $highRiskActions = [
            self::ACTION_DELETED,
            self::ACTION_ROLE_CHANGED,
            self::ACTION_PERMISSION_GRANTED,
            self::ACTION_PERMISSION_REVOKED,
            self::ACTION_SYSTEM_SETTING_CHANGED,
            self::ACTION_MAINTENANCE_MODE_TOGGLED,
            self::ACTION_BULK_OPERATION,
            self::ACTION_BACKUP_RESTORED,
            self::ACTION_SECURITY_EVENT,
        ];

        $mediumRiskActions = [
            self::ACTION_UPDATED,
            self::ACTION_PASSWORD_RESET,
            self::ACTION_FEATURE_FLAG_TOGGLED,
            self::ACTION_DATA_EXPORT,
            self::ACTION_DATA_IMPORT,
            self::ACTION_BACKUP_CREATED,
            self::ACTION_VALIDATION_STATUS_CHANGED,
            self::ACTION_APPROVED,
            self::ACTION_REJECTED,
        ];

        if (in_array($this->action, $highRiskActions)) {
            return 'high';
        } elseif (in_array($this->action, $mediumRiskActions)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Scope for high-risk activities
     */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->whereIn('action', [
            self::ACTION_DELETED,
            self::ACTION_ROLE_CHANGED,
            self::ACTION_PERMISSION_GRANTED,
            self::ACTION_PERMISSION_REVOKED,
            self::ACTION_SYSTEM_SETTING_CHANGED,
            self::ACTION_MAINTENANCE_MODE_TOGGLED,
            self::ACTION_BULK_OPERATION,
            self::ACTION_BACKUP_RESTORED,
            self::ACTION_SECURITY_EVENT,
        ]);
    }

    /**
     * Scope for recent activities
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope by user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by model type
     */
    public function scopeByModelType(Builder $query, string $modelType): Builder
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Log an audit event
     */
    public static function log(string $action, $model = null, array $oldValues = [], array $newValues = []): self
    {
        $user = auth()->user();
        $request = request();

        return static::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
    }

    /**
     * Log authentication events
     */
    public static function logAuth(string $action, array $metadata = []): self
    {
        return static::log($action, null, [], $metadata);
    }

    /**
     * Log system events
     */
    public static function logSystem(string $action, array $metadata = []): self
    {
        return static::log($action, null, [], $metadata);
    }

    /**
     * Log security events
     */
    public static function logSecurity(string $event, array $metadata = []): self
    {
        return static::log(self::ACTION_SECURITY_EVENT, null, [], array_merge($metadata, ['event' => $event]));
    }

    /**
     * Get activity summary for a time period
     */
    public static function getActivitySummary(Carbon $startDate, Carbon $endDate): array
    {
        $query = static::whereBetween('created_at', [$startDate, $endDate]);

        return [
            'total_activities' => $query->count(),
            'unique_users' => $query->distinct('user_id')->count('user_id'),
            'high_risk_activities' => $query->highRisk()->count(),
            'top_actions' => $query->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'action')
                ->toArray(),
            'top_users' => $query->selectRaw('user_id, COUNT(*) as count')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->with('user:id,name,email')
                ->get()
                ->pluck('count', 'user.name')
                ->toArray(),
        ];
    }

    /**
     * Clean up old audit logs
     */
    public static function cleanup(int $daysToKeep = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        return static::where('created_at', '<', $cutoffDate)->delete();
    }
}