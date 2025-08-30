<?php

namespace App\Services;

use App\Enums\TelegramNotificationType;
use App\Jobs\EnhancedTelegramNotificationJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * NotificationDispatcherService
 * 
 * Intelligent notification routing system that handles cross-role communication
 * and smart notification dispatch based on user roles, notification types, and context.
 */
class NotificationDispatcherService
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Main dispatch method - determines the best notification strategy
     */
    public function dispatch(string $notificationType, array $data, array $options = []): array
    {
        Log::info('NotificationDispatcherService::dispatch called', [
            'type' => $notificationType,
            'options' => $options,
        ]);

        $dispatchRules = $this->getDispatchRules($notificationType, $data);
        $results = [];

        foreach ($dispatchRules as $rule) {
            $ruleResults = $this->executeDispatchRule($rule, $notificationType, $data, $options);
            $results = array_merge($results, $ruleResults);
        }

        return $results;
    }

    /**
     * Get dispatch rules based on notification type and context
     */
    protected function getDispatchRules(string $notificationType, array $data): array
    {
        $enum = TelegramNotificationType::tryFrom($notificationType);
        if (!$enum) {
            return [];
        }

        return match ($enum) {
            // Financial notifications - cross-role patterns
            TelegramNotificationType::VALIDASI_DISETUJUI => [
                ['type' => 'cross_role', 'targets' => $this->getValidationApprovalTargets($data)],
                ['type' => 'role_broadcast', 'roles' => ['manajer', 'admin']],
            ],

            TelegramNotificationType::PENDAPATAN,
            TelegramNotificationType::PENGELUARAN => [
                ['type' => 'role_broadcast', 'roles' => ['bendahara', 'manajer', 'admin']],
                ['type' => 'conditional', 'condition' => 'high_amount', 'roles' => ['admin']],
            ],

            // Medical notifications - staff coordination
            TelegramNotificationType::PASIEN => [
                ['type' => 'medical_staff', 'include_dokter' => true, 'include_paramedis' => true],
                ['type' => 'role_broadcast', 'roles' => ['petugas', 'admin']],
            ],

            TelegramNotificationType::TINDAKAN_BARU => [
                ['type' => 'medical_coordination', 'data' => $data],
                ['type' => 'financial_notification', 'roles' => ['bendahara']],
            ],

            // Jaspel notifications - financial coordination
            TelegramNotificationType::JASPEL_SELESAI => [
                ['type' => 'target_dokter', 'dokter_id' => $data['dokter_id'] ?? null],
                ['type' => 'role_broadcast', 'roles' => ['manajer', 'bendahara']],
            ],

            TelegramNotificationType::JASPEL_DOKTER_READY => [
                ['type' => 'target_dokter', 'dokter_id' => $data['dokter_id'] ?? null],
                ['type' => 'role_broadcast', 'roles' => ['bendahara', 'admin']],
            ],

            // Attendance notifications - operational coordination
            TelegramNotificationType::PRESENSI_DOKTER => [
                ['type' => 'operational_staff', 'exclude_self' => true],
                ['type' => 'role_broadcast', 'roles' => ['manajer', 'admin']],
            ],

            TelegramNotificationType::PRESENSI_PARAMEDIS => [
                ['type' => 'medical_coordination', 'data' => $data],
                ['type' => 'role_broadcast', 'roles' => ['manajer']],
            ],

            // Schedule notifications - all relevant staff
            TelegramNotificationType::JADWAL_JAGA_UPDATE => [
                ['type' => 'affected_staff', 'data' => $data],
                ['type' => 'role_broadcast', 'roles' => ['manajer', 'admin']],
            ],

            TelegramNotificationType::SHIFT_ASSIGNMENT => [
                ['type' => 'target_user', 'user_id' => $data['user_id'] ?? null],
                ['type' => 'operational_staff'],
                ['type' => 'role_broadcast', 'roles' => ['manajer']],
            ],

            // Leave requests - management chain
            TelegramNotificationType::CUTI_REQUEST => [
                ['type' => 'management_approval', 'data' => $data],
                ['type' => 'role_broadcast', 'roles' => ['admin']],
            ],

            // Emergency alerts - all critical staff
            TelegramNotificationType::EMERGENCY_ALERT => [
                ['type' => 'emergency_broadcast'],
            ],

            // System notifications - administrative staff
            TelegramNotificationType::SISTEM_MAINTENANCE => [
                ['type' => 'all_users'],
            ],

            TelegramNotificationType::BACKUP_GAGAL => [
                ['type' => 'role_broadcast', 'roles' => ['admin']],
            ],

            // Approval requests - management workflow
            TelegramNotificationType::APPROVAL_REQUEST => [
                ['type' => 'approval_chain', 'data' => $data],
            ],

            // Reports - management and oversight
            TelegramNotificationType::LAPORAN_SHIFT => [
                ['type' => 'shift_coordination', 'data' => $data],
                ['type' => 'role_broadcast', 'roles' => ['manajer', 'admin']],
            ],

            TelegramNotificationType::REKAP_HARIAN,
            TelegramNotificationType::REKAP_MINGGUAN => [
                ['type' => 'role_broadcast', 'roles' => ['manajer', 'bendahara', 'admin']],
            ],

            // Default patterns
            default => [
                ['type' => 'role_broadcast', 'roles' => ['admin']],
            ],
        };
    }

    /**
     * Execute a specific dispatch rule
     */
    protected function executeDispatchRule(array $rule, string $notificationType, array $data, array $options): array
    {
        $results = [];

        switch ($rule['type']) {
            case 'cross_role':
                $results = $this->executeCrossRoleDispatch($rule['targets'], $notificationType, $data, $options);
                break;

            case 'role_broadcast':
                $results = $this->executeRoleBroadcast($rule['roles'], $notificationType, $data, $options);
                break;

            case 'target_dokter':
                $results = $this->executeTargetDokter($rule['dokter_id'], $notificationType, $data, $options);
                break;

            case 'target_user':
                $results = $this->executeTargetUser($rule['user_id'], $notificationType, $data, $options);
                break;

            case 'medical_staff':
                $results = $this->executeMedicalStaffDispatch($rule, $notificationType, $data, $options);
                break;

            case 'medical_coordination':
                $results = $this->executeMedicalCoordination($data, $notificationType, $options);
                break;

            case 'financial_notification':
                $results = $this->executeFinancialNotification($rule['roles'], $notificationType, $data, $options);
                break;

            case 'operational_staff':
                $results = $this->executeOperationalStaffDispatch($rule, $notificationType, $data, $options);
                break;

            case 'emergency_broadcast':
                $results = $this->executeEmergencyBroadcast($notificationType, $data, $options);
                break;

            case 'all_users':
                $results = $this->executeAllUsersDispatch($notificationType, $data, $options);
                break;

            case 'management_approval':
                $results = $this->executeManagementApproval($data, $notificationType, $options);
                break;

            case 'approval_chain':
                $results = $this->executeApprovalChain($data, $notificationType, $options);
                break;

            case 'shift_coordination':
                $results = $this->executeShiftCoordination($data, $notificationType, $options);
                break;

            case 'affected_staff':
                $results = $this->executeAffectedStaffDispatch($data, $notificationType, $options);
                break;

            case 'conditional':
                $results = $this->executeConditionalDispatch($rule, $notificationType, $data, $options);
                break;
        }

        return $results;
    }

    /**
     * Cross-role communication (e.g., bendahara → dokter)
     */
    protected function executeCrossRoleDispatch(array $targets, string $notificationType, array $data, array $options): array
    {
        $results = [];

        foreach ($targets as $target) {
            if (isset($target['user_id'])) {
                // Specific user notification
                $result = $this->queueNotificationToUser($target['user_id'], $notificationType, $data, $options);
                $results["user_{$target['user_id']}"] = $result;
            } elseif (isset($target['role'])) {
                // Role-based notification
                $result = $this->queueNotificationToRole($target['role'], $notificationType, $data, $options);
                $results[$target['role']] = $result;
            }
        }

        return $results;
    }

    /**
     * Broadcast to specific roles
     */
    protected function executeRoleBroadcast(array $roles, string $notificationType, array $data, array $options): array
    {
        $results = [];

        foreach ($roles as $role) {
            $result = $this->queueNotificationToRole($role, $notificationType, $data, $options);
            $results[$role] = $result;
        }

        return $results;
    }

    /**
     * Target specific dokter
     */
    protected function executeTargetDokter(?int $dokterId, string $notificationType, array $data, array $options): array
    {
        if (!$dokterId) {
            return [];
        }

        // Find dokter's user
        $dokter = \App\Models\Dokter::find($dokterId);
        if (!$dokter || !$dokter->user_id) {
            Log::warning("Dokter not found or no user_id for dokter_id: {$dokterId}");
            return [];
        }

        $result = $this->queueNotificationToUser($dokter->user_id, $notificationType, $data, $options);
        return ["dokter_{$dokterId}" => $result];
    }

    /**
     * Target specific user
     */
    protected function executeTargetUser(?int $userId, string $notificationType, array $data, array $options): array
    {
        if (!$userId) {
            return [];
        }

        $result = $this->queueNotificationToUser($userId, $notificationType, $data, $options);
        return ["user_{$userId}" => $result];
    }

    /**
     * Medical staff coordination
     */
    protected function executeMedicalStaffDispatch(array $rule, string $notificationType, array $data, array $options): array
    {
        $roles = [];
        
        if ($rule['include_dokter'] ?? false) {
            $roles[] = 'dokter';
        }
        
        if ($rule['include_paramedis'] ?? false) {
            $roles[] = 'paramedis';
        }

        return $this->executeRoleBroadcast($roles, $notificationType, $data, $options);
    }

    /**
     * Medical coordination based on tindakan data
     */
    protected function executeMedicalCoordination(array $data, string $notificationType, array $options): array
    {
        $results = [];

        // Notify relevant dokter
        if (isset($data['dokter_id'])) {
            $results = array_merge($results, $this->executeTargetDokter($data['dokter_id'], $notificationType, $data, $options));
        }

        // Notify relevant paramedis
        if (isset($data['paramedis_id'])) {
            $paramedis = \App\Models\Paramedis::find($data['paramedis_id']);
            if ($paramedis && $paramedis->user_id) {
                $result = $this->queueNotificationToUser($paramedis->user_id, $notificationType, $data, $options);
                $results["paramedis_{$data['paramedis_id']}"] = $result;
            }
        }

        return $results;
    }

    /**
     * Financial notifications
     */
    protected function executeFinancialNotification(array $roles, string $notificationType, array $data, array $options): array
    {
        return $this->executeRoleBroadcast($roles, $notificationType, $data, $options);
    }

    /**
     * Operational staff dispatch
     */
    protected function executeOperationalStaffDispatch(array $rule, string $notificationType, array $data, array $options): array
    {
        $roles = ['petugas', 'paramedis'];
        
        if (!($rule['exclude_self'] ?? false)) {
            $roles[] = 'dokter';
        }

        return $this->executeRoleBroadcast($roles, $notificationType, $data, $options);
    }

    /**
     * Emergency broadcast to all critical roles
     */
    protected function executeEmergencyBroadcast(string $notificationType, array $data, array $options): array
    {
        $emergencyRoles = ['admin', 'manajer', 'dokter', 'paramedis'];
        return $this->executeRoleBroadcast($emergencyRoles, $notificationType, $data, $options);
    }

    /**
     * Notify all users
     */
    protected function executeAllUsersDispatch(string $notificationType, array $data, array $options): array
    {
        $allRoles = ['admin', 'manajer', 'bendahara', 'petugas', 'dokter', 'paramedis', 'non_paramedis'];
        return $this->executeRoleBroadcast($allRoles, $notificationType, $data, $options);
    }

    /**
     * Management approval chain
     */
    protected function executeManagementApproval(array $data, string $notificationType, array $options): array
    {
        return $this->executeRoleBroadcast(['manajer', 'admin'], $notificationType, $data, $options);
    }

    /**
     * Approval chain workflow
     */
    protected function executeApprovalChain(array $data, string $notificationType, array $options): array
    {
        $roles = ['manajer'];
        
        // Add bendahara for financial approvals
        if (isset($data['amount']) || isset($data['financial'])) {
            $roles[] = 'bendahara';
        }
        
        $roles[] = 'admin'; // Admin as final approver

        return $this->executeRoleBroadcast($roles, $notificationType, $data, $options);
    }

    /**
     * Shift coordination
     */
    protected function executeShiftCoordination(array $data, string $notificationType, array $options): array
    {
        // Notify current and next shift staff
        $roles = ['dokter', 'paramedis', 'petugas'];
        return $this->executeRoleBroadcast($roles, $notificationType, $data, $options);
    }

    /**
     * Affected staff dispatch
     */
    protected function executeAffectedStaffDispatch(array $data, string $notificationType, array $options): array
    {
        $results = [];

        // If specific user is affected
        if (isset($data['user_id'])) {
            $result = $this->queueNotificationToUser($data['user_id'], $notificationType, $data, $options);
            $results["affected_user_{$data['user_id']}"] = $result;
        }

        // If specific role is affected
        if (isset($data['affected_role'])) {
            $result = $this->queueNotificationToRole($data['affected_role'], $notificationType, $data, $options);
            $results["affected_role_{$data['affected_role']}"] = $result;
        }

        return $results;
    }

    /**
     * Conditional dispatch based on data conditions
     */
    protected function executeConditionalDispatch(array $rule, string $notificationType, array $data, array $options): array
    {
        $condition = $rule['condition'];
        $shouldDispatch = false;

        switch ($condition) {
            case 'high_amount':
                $amount = $data['amount'] ?? 0;
                $threshold = $options['high_amount_threshold'] ?? 1000000; // 1 million
                $shouldDispatch = $amount >= $threshold;
                break;

            case 'critical_time':
                $hour = now()->hour;
                $shouldDispatch = $hour < 6 || $hour > 22; // Outside normal hours
                break;

            case 'weekend':
                $shouldDispatch = now()->isWeekend();
                break;
        }

        if ($shouldDispatch && isset($rule['roles'])) {
            return $this->executeRoleBroadcast($rule['roles'], $notificationType, $data, $options);
        }

        return [];
    }

    /**
     * Get validation approval targets based on data
     */
    protected function getValidationApprovalTargets(array $data): array
    {
        $targets = [];

        // Notify the dokter if tindakan is approved
        if (isset($data['dokter_id'])) {
            $dokter = \App\Models\Dokter::find($data['dokter_id']);
            if ($dokter && $dokter->user_id) {
                $targets[] = ['user_id' => $dokter->user_id];
            }
        }

        // Notify the paramedis if involved
        if (isset($data['paramedis_id'])) {
            $paramedis = \App\Models\Paramedis::find($data['paramedis_id']);
            if ($paramedis && $paramedis->user_id) {
                $targets[] = ['user_id' => $paramedis->user_id];
            }
        }

        // Notify the input user (petugas)
        if (isset($data['input_by'])) {
            $targets[] = ['user_id' => $data['input_by']];
        }

        return $targets;
    }

    /**
     * Queue notification to specific role
     */
    protected function queueNotificationToRole(string $role, string $notificationType, array $data, array $options): bool
    {
        $priority = $options['priority'] ?? 'normal';
        $delay = $options['delay'] ?? 0;

        try {
            EnhancedTelegramNotificationJob::dispatch($role, null, $notificationType, $data)
                ->onQueue($this->getQueueByPriority($priority))
                ->delay($delay);

            Log::info("Queued notification to role: {$role}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue notification to role {$role}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Queue notification to specific user
     */
    protected function queueNotificationToUser(int $userId, string $notificationType, array $data, array $options): bool
    {
        $priority = $options['priority'] ?? 'normal';
        $delay = $options['delay'] ?? 0;

        try {
            EnhancedTelegramNotificationJob::dispatch(null, $userId, $notificationType, $data)
                ->onQueue($this->getQueueByPriority($priority))
                ->delay($delay);

            Log::info("Queued notification to user: {$userId}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue notification to user {$userId}: " . $e->getMessage());
            return false;
        }
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
     * Get notification statistics
     */
    public function getDispatchStats(): array
    {
        return [
            'total_rules' => count(TelegramNotificationType::cases()),
            'telegram_stats' => $this->telegramService->getNotificationStats(),
        ];
    }
}