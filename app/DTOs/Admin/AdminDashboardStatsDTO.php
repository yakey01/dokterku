<?php

namespace App\DTOs\Admin;

/**
 * Admin Dashboard Statistics Data Transfer Object
 * 
 * Standardizes dashboard data structure and provides
 * type safety for dashboard statistics.
 */
class AdminDashboardStatsDTO
{
    public array $userStats;
    public array $financialStats;
    public array $medicalStats;
    public array $systemStats;
    public array $recentActivities;
    public array $performanceMetrics;
    public string $lastUpdated;

    public function __construct(
        array $userStats,
        array $financialStats,
        array $medicalStats,
        array $systemStats,
        array $recentActivities,
        array $performanceMetrics,
        string $lastUpdated
    ) {
        $this->userStats = $userStats;
        $this->financialStats = $financialStats;
        $this->medicalStats = $medicalStats;
        $this->systemStats = $systemStats;
        $this->recentActivities = $recentActivities;
        $this->performanceMetrics = $performanceMetrics;
        $this->lastUpdated = $lastUpdated;
    }

    /**
     * Create DTO from array data
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        // Helper function to ensure array conversion
        $ensureArray = fn($value) => $value instanceof \Illuminate\Support\Collection ? $value->toArray() : (array) $value;
        
        return new self(
            userStats: $ensureArray($data['user_stats'] ?? []),
            financialStats: $ensureArray($data['financial_stats'] ?? []),
            medicalStats: $ensureArray($data['medical_stats'] ?? []),
            systemStats: $ensureArray($data['system_stats'] ?? []),
            recentActivities: $ensureArray($data['recent_activities'] ?? []),
            performanceMetrics: $ensureArray($data['performance_metrics'] ?? []),
            lastUpdated: $data['last_updated'] ?? now()->toISOString()
        );
    }

    /**
     * Convert DTO to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_stats' => $this->userStats,
            'financial_stats' => $this->financialStats,
            'medical_stats' => $this->medicalStats,
            'system_stats' => $this->systemStats,
            'recent_activities' => $this->recentActivities,
            'performance_metrics' => $this->performanceMetrics,
            'last_updated' => $this->lastUpdated
        ];
    }

    /**
     * Get summary statistics
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'total_users' => $this->userStats['total_users'] ?? 0,
            'total_income' => $this->financialStats['total_income'] ?? 0,
            'total_patients' => $this->medicalStats['total_patients'] ?? 0,
            'system_health' => $this->systemStats['uptime'] ?? 'Unknown',
            'last_updated' => $this->lastUpdated
        ];
    }

    /**
     * Check if dashboard data is fresh (within cache TTL)
     *
     * @param int $ttlSeconds
     * @return bool
     */
    public function isFresh(int $ttlSeconds = 300): bool
    {
        $lastUpdated = \Carbon\Carbon::parse($this->lastUpdated);
        return $lastUpdated->diffInSeconds(now()) <= $ttlSeconds;
    }

    /**
     * Get growth indicators
     *
     * @return array
     */
    public function getGrowthIndicators(): array
    {
        return [
            'user_growth' => $this->userStats['growth_rate'] ?? 0,
            'patient_growth' => $this->medicalStats['patient_growth_rate'] ?? 0,
            'procedure_growth' => $this->medicalStats['procedure_growth_rate'] ?? 0,
            'profitability_ratio' => $this->financialStats['profitability_ratio'] ?? 0
        ];
    }

    /**
     * Get critical alerts based on thresholds
     *
     * @return array
     */
    public function getCriticalAlerts(): array
    {
        $alerts = [];

        // System health alerts
        if (isset($this->systemStats['error_rate']) && $this->systemStats['error_rate'] > 1.0) {
            $alerts[] = [
                'type' => 'error_rate',
                'message' => 'High error rate detected',
                'value' => $this->systemStats['error_rate'],
                'severity' => 'high'
            ];
        }

        // Financial alerts
        if (isset($this->financialStats['monthly_profit']) && $this->financialStats['monthly_profit'] < 0) {
            $alerts[] = [
                'type' => 'negative_profit',
                'message' => 'Negative monthly profit',
                'value' => $this->financialStats['monthly_profit'],
                'severity' => 'high'
            ];
        }

        // Pending approvals alert
        if (isset($this->financialStats['pending_approvals']) && $this->financialStats['pending_approvals'] > 10) {
            $alerts[] = [
                'type' => 'pending_approvals',
                'message' => 'High number of pending approvals',
                'value' => $this->financialStats['pending_approvals'],
                'severity' => 'medium'
            ];
        }

        return $alerts;
    }

    /**
     * Get performance score (0-100)
     *
     * @return int
     */
    public function getPerformanceScore(): int
    {
        $score = 100;

        // Deduct points for high error rate
        if (isset($this->systemStats['error_rate'])) {
            $score -= min(20, $this->systemStats['error_rate'] * 10);
        }

        // Deduct points for slow response time
        if (isset($this->systemStats['average_response_time']) && $this->systemStats['average_response_time'] > 500) {
            $score -= min(15, ($this->systemStats['average_response_time'] - 500) / 100);
        }

        // Deduct points for low cache hit rate
        if (isset($this->systemStats['cache_hit_rate']) && $this->systemStats['cache_hit_rate'] < 90) {
            $score -= (90 - $this->systemStats['cache_hit_rate']);
        }

        return max(0, (int) $score);
    }
}