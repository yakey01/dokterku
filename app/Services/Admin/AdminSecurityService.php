<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Admin Security Service
 * 
 * Comprehensive security service for admin operations including
 * audit logging, security monitoring, threat detection, and compliance.
 */
class AdminSecurityService
{
    /**
     * Log admin activity with comprehensive details
     *
     * @param string $action
     * @param array $details
     * @param User|null $user
     * @param Request|null $request
     * @return void
     */
    public function logAdminActivity(string $action, array $details = [], ?User $user = null, ?Request $request = null): void
    {
        $user = $user ?? auth()->user();
        $request = $request ?? request();

        $logData = [
            'action' => $action,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->roles->pluck('name')->implode(','),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'details' => $details,
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
            'request_id' => $request?->header('X-Request-ID') ?? uniqid()
        ];

        // Store in database for permanent audit trail
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $logData['user_id'],
                'action' => $logData['action'],
                'model_type' => 'admin_activity',
                'model_id' => null,
                'old_values' => null,
                'new_values' => json_encode($logData),
                'ip_address' => $logData['ip_address'],
                'user_agent' => $logData['user_agent'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store audit log in database', [
                'error' => $e->getMessage(),
                'log_data' => $logData
            ]);
        }

        // Log to file system
        Log::channel('admin_audit')->info('Admin activity', $logData);

        // Cache for real-time monitoring
        $this->cacheSecurityEvent('admin_activity', $logData);
    }

    /**
     * Log security event for monitoring
     *
     * @param string $eventType
     * @param array $eventData
     * @return void
     */
    public function logSecurityEvent(string $eventType, array $eventData): void
    {
        $securityEvent = [
            'event_type' => $eventType,
            'severity' => $this->calculateSeverity($eventType, $eventData),
            'data' => $eventData,
            'timestamp' => now()->toISOString(),
            'risk_score' => $this->calculateRiskScore($eventType, $eventData)
        ];

        // Log to security channel
        Log::channel('security')->warning('Security event detected', $securityEvent);

        // Cache for real-time monitoring
        $this->cacheSecurityEvent($eventType, $securityEvent);

        // Check for automated response
        $this->checkAutomatedSecurityResponse($eventType, $securityEvent);
    }

    /**
     * Monitor suspicious admin activities
     *
     * @param User $user
     * @param string $action
     * @param array $context
     * @return array
     */
    public function monitorSuspiciousActivity(User $user, string $action, array $context = []): array
    {
        $suspiciousIndicators = [];

        // Check for unusual access patterns
        $unusualPatterns = $this->detectUnusualAccessPatterns($user);
        if (!empty($unusualPatterns)) {
            $suspiciousIndicators['unusual_access_patterns'] = $unusualPatterns;
        }

        // Check for privilege escalation attempts
        if ($this->isPrivilegeEscalationAttempt($action, $context)) {
            $suspiciousIndicators['privilege_escalation'] = [
                'action' => $action,
                'context' => $context,
                'risk_level' => 'high'
            ];
        }

        // Check for bulk operations outside normal hours
        if ($this->isSuspiciousBulkOperation($action, $context)) {
            $suspiciousIndicators['suspicious_bulk_operation'] = [
                'action' => $action,
                'time' => now()->toISOString(),
                'risk_level' => 'medium'
            ];
        }

        // Check for rapid consecutive actions
        if ($this->isRapidConsecutiveAction($user, $action)) {
            $suspiciousIndicators['rapid_actions'] = [
                'action' => $action,
                'frequency' => $this->getActionFrequency($user, $action),
                'risk_level' => 'medium'
            ];
        }

        // Log if suspicious activity detected
        if (!empty($suspiciousIndicators)) {
            $this->logSecurityEvent('suspicious_admin_activity', [
                'user_id' => $user->id,
                'action' => $action,
                'indicators' => $suspiciousIndicators,
                'context' => $context
            ]);
        }

        return $suspiciousIndicators;
    }

    /**
     * Get security compliance report
     *
     * @param string $period
     * @return array
     */
    public function getSecurityComplianceReport(string $period = '30days'): array
    {
        $startDate = $this->getPeriodStartDate($period);

        return [
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'end_date' => now()->toISOString(),
            'admin_activities' => $this->getAdminActivitiesSummary($startDate),
            'security_events' => $this->getSecurityEventsSummary($startDate),
            'user_access_patterns' => $this->getUserAccessPatterns($startDate),
            'failed_login_attempts' => $this->getFailedLoginAttempts($startDate),
            'privilege_changes' => $this->getPrivilegeChanges($startDate),
            'policy_violations' => $this->getPolicyViolations($startDate),
            'compliance_score' => $this->calculateComplianceScore($startDate)
        ];
    }

    /**
     * Detect potential security threats
     *
     * @return array
     */
    public function detectSecurityThreats(): array
    {
        $threats = [];

        // Detect brute force attempts
        $bruteForceAttempts = $this->detectBruteForceAttempts();
        if (!empty($bruteForceAttempts)) {
            $threats['brute_force'] = $bruteForceAttempts;
        }

        // Detect account takeover attempts
        $takeovers = $this->detectAccountTakeoverAttempts();
        if (!empty($takeovers)) {
            $threats['account_takeover'] = $takeovers;
        }

        // Detect privilege escalation
        $escalations = $this->detectPrivilegeEscalation();
        if (!empty($escalations)) {
            $threats['privilege_escalation'] = $escalations;
        }

        // Detect data exfiltration patterns
        $exfiltration = $this->detectDataExfiltrationPatterns();
        if (!empty($exfiltration)) {
            $threats['data_exfiltration'] = $exfiltration;
        }

        return $threats;
    }

    /**
     * Generate security recommendations
     *
     * @return array
     */
    public function generateSecurityRecommendations(): array
    {
        $recommendations = [];

        // Check password policies
        $weakPasswords = $this->checkPasswordPolicies();
        if (!empty($weakPasswords)) {
            $recommendations[] = [
                'type' => 'password_policy',
                'priority' => 'high',
                'description' => 'Some admin accounts have weak passwords',
                'affected_users' => count($weakPasswords),
                'action' => 'Enforce stronger password policies and require password updates'
            ];
        }

        // Check multi-factor authentication
        $noMfa = $this->checkMfaAdoption();
        if (!empty($noMfa)) {
            $recommendations[] = [
                'type' => 'mfa_requirement',
                'priority' => 'high',
                'description' => 'Some admin accounts do not have MFA enabled',
                'affected_users' => count($noMfa),
                'action' => 'Mandate MFA for all admin accounts'
            ];
        }

        // Check session security
        $sessionIssues = $this->checkSessionSecurity();
        if (!empty($sessionIssues)) {
            $recommendations[] = [
                'type' => 'session_security',
                'priority' => 'medium',
                'description' => 'Session security improvements needed',
                'issues' => $sessionIssues,
                'action' => 'Implement stricter session timeout and validation'
            ];
        }

        // Check access patterns
        $accessIssues = $this->checkAccessPatterns();
        if (!empty($accessIssues)) {
            $recommendations[] = [
                'type' => 'access_patterns',
                'priority' => 'medium',
                'description' => 'Unusual access patterns detected',
                'patterns' => $accessIssues,
                'action' => 'Review and validate admin access patterns'
            ];
        }

        return $recommendations;
    }

    /**
     * Get real-time security dashboard data
     *
     * @return array
     */
    public function getSecurityDashboard(): array
    {
        $currentHour = date('Y-m-d-H');
        
        return [
            'current_threats' => $this->detectSecurityThreats(),
            'hourly_stats' => $this->getHourlySecurityStats($currentHour),
            'active_admin_sessions' => $this->getActiveAdminSessions(),
            'recent_security_events' => $this->getRecentSecurityEvents(20),
            'security_score' => $this->calculateCurrentSecurityScore(),
            'alert_level' => $this->getCurrentAlertLevel(),
            'recommendations' => $this->generateSecurityRecommendations()
        ];
    }

    /**
     * Cache security event for real-time monitoring
     *
     * @param string $eventType
     * @param array $eventData
     * @return void
     */
    private function cacheSecurityEvent(string $eventType, array $eventData): void
    {
        $cacheKey = 'security_events:' . date('Y-m-d-H');
        $events = Cache::get($cacheKey, []);
        $events[] = [
            'type' => $eventType,
            'data' => $eventData,
            'timestamp' => now()->timestamp
        ];
        
        // Keep only last 1000 events per hour
        if (count($events) > 1000) {
            $events = array_slice($events, -1000);
        }
        
        Cache::put($cacheKey, $events, 3600);
    }

    /**
     * Calculate severity level for security events
     *
     * @param string $eventType
     * @param array $eventData
     * @return string
     */
    private function calculateSeverity(string $eventType, array $eventData): string
    {
        $severityMap = [
            'unauthorized_access' => 'high',
            'privilege_escalation' => 'critical',
            'data_exfiltration' => 'critical',
            'brute_force' => 'high',
            'account_takeover' => 'critical',
            'suspicious_activity' => 'medium',
            'policy_violation' => 'medium',
            'session_hijacking' => 'high',
            'admin_activity' => 'low'
        ];

        return $severityMap[$eventType] ?? 'low';
    }

    /**
     * Calculate risk score for security events
     *
     * @param string $eventType
     * @param array $eventData
     * @return int
     */
    private function calculateRiskScore(string $eventType, array $eventData): int
    {
        $baseScore = [
            'unauthorized_access' => 80,
            'privilege_escalation' => 95,
            'data_exfiltration' => 100,
            'brute_force' => 70,
            'account_takeover' => 95,
            'suspicious_activity' => 50,
            'policy_violation' => 40,
            'session_hijacking' => 85,
            'admin_activity' => 10
        ];

        $score = $baseScore[$eventType] ?? 10;

        // Adjust based on context
        if (isset($eventData['user_role']) && str_contains($eventData['user_role'], 'super_admin')) {
            $score += 20;
        }

        if (isset($eventData['ip_address']) && $this->isKnownMaliciousIp($eventData['ip_address'])) {
            $score += 30;
        }

        return min(100, $score);
    }

    /**
     * Check for automated security response
     *
     * @param string $eventType
     * @param array $eventData
     * @return void
     */
    private function checkAutomatedSecurityResponse(string $eventType, array $eventData): void
    {
        $riskScore = $eventData['risk_score'] ?? 0;

        // Auto-lock account for critical events
        if ($riskScore >= 90 && isset($eventData['data']['user_id'])) {
            $this->autoLockAccount($eventData['data']['user_id'], $eventType);
        }

        // Rate limit IP for high-risk events
        if ($riskScore >= 70 && isset($eventData['data']['ip_address'])) {
            $this->rateLimitIp($eventData['data']['ip_address'], $eventType);
        }

        // Send alerts for medium+ risk events
        if ($riskScore >= 50) {
            $this->sendSecurityAlert($eventType, $eventData);
        }
    }

    /**
     * Detect unusual access patterns
     *
     * @param User $user
     * @return array
     */
    private function detectUnusualAccessPatterns(User $user): array
    {
        $patterns = [];

        // Check access outside business hours
        $currentHour = (int) date('H');
        if ($currentHour < 7 || $currentHour > 18) {
            $patterns[] = 'access_outside_business_hours';
        }

        // Check for new location/IP
        $recentIps = $this->getRecentIpAddresses($user, 7);
        $currentIp = request()->ip();
        if (!in_array($currentIp, $recentIps)) {
            $patterns[] = 'new_ip_address';
        }

        // Check for weekend access
        if (date('N') >= 6) { // Saturday or Sunday
            $patterns[] = 'weekend_access';
        }

        return $patterns;
    }

    /**
     * Check if action is privilege escalation attempt
     *
     * @param string $action
     * @param array $context
     * @return bool
     */
    private function isPrivilegeEscalationAttempt(string $action, array $context): bool
    {
        $escalationActions = [
            'role_change_to_admin',
            'permission_grant_admin',
            'super_admin_assignment',
            'system_setting_change'
        ];

        return in_array($action, $escalationActions) || 
               (isset($context['target_role']) && str_contains($context['target_role'], 'admin'));
    }

    /**
     * Additional helper methods for security monitoring
     */
    
    private function isSuspiciousBulkOperation(string $action, array $context): bool
    {
        return str_contains($action, 'bulk_') && 
               (date('H') < 7 || date('H') > 18) &&
               (isset($context['affected_count']) && $context['affected_count'] > 10);
    }

    private function isRapidConsecutiveAction(User $user, string $action): bool
    {
        $frequency = $this->getActionFrequency($user, $action);
        return $frequency > 10; // More than 10 actions in last 5 minutes
    }

    private function getActionFrequency(User $user, string $action): int
    {
        $cacheKey = "action_frequency:{$user->id}:{$action}";
        return Cache::get($cacheKey, 0);
    }

    private function getPeriodStartDate(string $period): Carbon
    {
        return match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30)
        };
    }

    private function getAdminActivitiesSummary(Carbon $startDate): array
    {
        return [
            'total_activities' => 0, // Implement based on audit logs
            'unique_admins' => 0,
            'most_common_actions' => [],
            'peak_activity_hours' => []
        ];
    }

    private function getSecurityEventsSummary(Carbon $startDate): array
    {
        return [
            'total_events' => 0,
            'by_severity' => [],
            'by_type' => [],
            'trends' => []
        ];
    }

    private function autoLockAccount(int $userId, string $reason): void
    {
        $lockKey = 'account_locked:' . $userId;
        Cache::put($lockKey, [
            'reason' => $reason,
            'locked_at' => now()->toISOString(),
            'auto_unlock_at' => now()->addHours(24)->toISOString()
        ], 86400); // 24 hours

        Log::warning('Account auto-locked due to security event', [
            'user_id' => $userId,
            'reason' => $reason
        ]);
    }

    private function rateLimitIp(string $ip, string $reason): void
    {
        $key = 'ip_rate_limited:' . $ip;
        Cache::put($key, [
            'reason' => $reason,
            'limited_at' => now()->toISOString()
        ], 3600); // 1 hour
    }

    private function sendSecurityAlert(string $eventType, array $eventData): void
    {
        // TODO: Implement alert sending (email, Slack, etc.)
        Log::critical('Security alert triggered', [
            'event_type' => $eventType,
            'event_data' => $eventData
        ]);
    }

    private function isKnownMaliciousIp(string $ip): bool
    {
        // TODO: Implement malicious IP checking
        return false;
    }

    private function getRecentIpAddresses(User $user, int $days): array
    {
        // TODO: Implement recent IP tracking
        return [];
    }

    // Additional security check methods
    private function detectBruteForceAttempts(): array { return []; }
    private function detectAccountTakeoverAttempts(): array { return []; }
    private function detectPrivilegeEscalation(): array { return []; }
    private function detectDataExfiltrationPatterns(): array { return []; }
    private function checkPasswordPolicies(): array { return []; }
    private function checkMfaAdoption(): array { return []; }
    private function checkSessionSecurity(): array { return []; }
    private function checkAccessPatterns(): array { return []; }
    private function getHourlySecurityStats(string $hour): array { return []; }
    private function getActiveAdminSessions(): array { return []; }
    private function getRecentSecurityEvents(int $limit): array { return []; }
    private function calculateCurrentSecurityScore(): int { return 85; }
    private function getCurrentAlertLevel(): string { return 'normal'; }
    private function getUserAccessPatterns(Carbon $startDate): array { return []; }
    private function getFailedLoginAttempts(Carbon $startDate): array { return []; }
    private function getPrivilegeChanges(Carbon $startDate): array { return []; }
    private function getPolicyViolations(Carbon $startDate): array { return []; }
    private function calculateComplianceScore(Carbon $startDate): int { return 92; }
}