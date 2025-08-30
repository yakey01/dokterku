<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * EnhancedTelegramNotificationJob
 * 
 * Enhanced queue job for sending Telegram notifications with:
 * - Role-based and user-specific targeting
 * - Retry mechanisms with exponential backoff
 * - Fallback strategies
 * - Comprehensive error handling and logging
 */
class EnhancedTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?string $role;
    public ?int $userId;
    public string $notificationType;
    public array $data;
    public array $options;
    
    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        ?string $role = null,
        ?int $userId = null,
        string $notificationType = '',
        array $data = [],
        array $options = []
    ) {
        $this->role = $role;
        $this->userId = $userId;
        $this->notificationType = $notificationType;
        $this->data = $data;
        $this->options = $options;

        // Set queue based on priority
        $priority = $options['priority'] ?? 'normal';
        $this->onQueue($this->getQueueByPriority($priority));

        Log::info('EnhancedTelegramNotificationJob created', [
            'role' => $this->role,
            'user_id' => $this->userId,
            'notification_type' => $this->notificationType,
            'priority' => $priority,
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegramService): void
    {
        Log::info('EnhancedTelegramNotificationJob handling', [
            'role' => $this->role,
            'user_id' => $this->userId,
            'notification_type' => $this->notificationType,
            'attempt' => $this->attempts(),
        ]);

        try {
            $success = false;

            if ($this->userId) {
                // Send to specific user
                $success = $this->handleUserNotification($telegramService);
            } elseif ($this->role) {
                // Send to role
                $success = $this->handleRoleNotification($telegramService);
            } else {
                throw new Exception('Either role or user_id must be specified');
            }

            if (!$success) {
                throw new Exception('Notification sending failed');
            }

            Log::info('EnhancedTelegramNotificationJob completed successfully', [
                'role' => $this->role,
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
            ]);

        } catch (Exception $e) {
            Log::error('EnhancedTelegramNotificationJob failed', [
                'role' => $this->role,
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle user-specific notification
     */
    protected function handleUserNotification(TelegramService $telegramService): bool
    {
        Log::info('Handling user notification', [
            'user_id' => $this->userId,
            'notification_type' => $this->notificationType,
        ]);

        // Validate routing first
        $validation = $telegramService->validateNotificationRouting($this->userId, $this->notificationType);
        
        if (!$validation['valid']) {
            Log::warning('Notification routing validation failed', [
                'user_id' => $this->userId,
                'reason' => $validation['reason'],
            ]);

            // Don't retry validation failures
            $this->fail(new Exception('Notification routing validation failed: ' . $validation['reason']));
            return false;
        }

        return $telegramService->sendNotificationToUser($this->userId, $this->notificationType, $this->data);
    }

    /**
     * Handle role-based notification
     */
    protected function handleRoleNotification(TelegramService $telegramService): bool
    {
        Log::info('Handling role notification', [
            'role' => $this->role,
            'notification_type' => $this->notificationType,
        ]);

        return $telegramService->sendNotificationToRole(
            $this->role, 
            $this->notificationType, 
            $telegramService->formatNotificationMessage($this->notificationType, $this->data),
            $this->data['exclude_user_id'] ?? null
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('EnhancedTelegramNotificationJob permanently failed', [
            'role' => $this->role,
            'user_id' => $this->userId,
            'notification_type' => $this->notificationType,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'data' => $this->data,
        ]);

        // Execute fallback strategy
        $this->executeFallbackStrategy($exception);
    }

    /**
     * Execute fallback strategy when notification fails
     */
    protected function executeFallbackStrategy(Exception $exception): void
    {
        try {
            // Strategy 1: Log to system for manual follow-up
            $this->logFailureForManualFollowup($exception);

            // Strategy 2: Try alternative notification method if configured
            if ($this->options['enable_fallback'] ?? true) {
                $this->tryAlternativeNotification();
            }

            // Strategy 3: Create system alert for critical notifications
            if ($this->isCriticalNotification()) {
                $this->createSystemAlert($exception);
            }

        } catch (Exception $fallbackException) {
            Log::error('Fallback strategy also failed', [
                'original_error' => $exception->getMessage(),
                'fallback_error' => $fallbackException->getMessage(),
            ]);
        }
    }

    /**
     * Log failure for manual follow-up
     */
    protected function logFailureForManualFollowup(Exception $exception): void
    {
        Log::critical('MANUAL FOLLOW-UP REQUIRED: Telegram notification failed', [
            'role' => $this->role,
            'user_id' => $this->userId,
            'notification_type' => $this->notificationType,
            'data' => $this->data,
            'error' => $exception->getMessage(),
            'timestamp' => now()->toISOString(),
            'requires_manual_action' => true,
        ]);
    }

    /**
     * Try alternative notification method (e.g., email, SMS)
     */
    protected function tryAlternativeNotification(): void
    {
        // This could be expanded to include email notifications, SMS, etc.
        Log::info('Alternative notification methods not implemented yet', [
            'role' => $this->role,
            'user_id' => $this->userId,
        ]);

        // TODO: Implement email fallback
        // TODO: Implement SMS fallback
        // TODO: Implement in-app notification fallback
    }

    /**
     * Create system alert for critical notifications
     */
    protected function createSystemAlert(Exception $exception): void
    {
        // For critical notifications, create a system alert that admins can see
        try {
            // This could create a database record, send email to admins, etc.
            Log::alert('CRITICAL NOTIFICATION FAILED', [
                'role' => $this->role,
                'user_id' => $this->userId,
                'notification_type' => $this->notificationType,
                'error' => $exception->getMessage(),
                'data' => $this->data,
                'requires_immediate_attention' => true,
            ]);

            // TODO: Implement admin alert system
            // TODO: Create notification_failures table to track failed notifications
            // TODO: Add admin dashboard to show failed notifications

        } catch (Exception $e) {
            Log::emergency('Failed to create system alert for critical notification failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if this is a critical notification that requires immediate attention
     */
    protected function isCriticalNotification(): bool
    {
        $criticalTypes = [
            'emergency_alert',
            'backup_gagal',
            'sistem_maintenance',
            'approval_request',
        ];

        return in_array($this->notificationType, $criticalTypes) ||
               ($this->options['critical'] ?? false);
    }

    /**
     * Get queue name based on priority
     */
    protected function getQueueByPriority(string $priority): string
    {
        return match ($priority) {
            'high', 'emergency' => 'telegram-high',
            'low' => 'telegram-low',
            default => 'telegram',
        };
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        // Exponential backoff: 10s, 30s, 90s
        return [10, 30, 90];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        $tags = ['telegram-notification', $this->notificationType];

        if ($this->role) {
            $tags[] = "role:{$this->role}";
        }

        if ($this->userId) {
            $tags[] = "user:{$this->userId}";
        }

        if ($this->isCriticalNotification()) {
            $tags[] = 'critical';
        }

        return $tags;
    }

    /**
     * Get the unique ID for the job (prevent duplicate notifications)
     */
    public function uniqueId(): string
    {
        $parts = [
            $this->notificationType,
            $this->role ?? 'no-role',
            $this->userId ?? 'no-user',
            md5(json_encode($this->data)),
        ];

        return implode('-', $parts);
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public function uniqueFor(): int
    {
        return 60; // Prevent duplicate notifications for 1 minute
    }
}