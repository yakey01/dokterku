<?php

namespace App\DTOs\Dashboard;

/**
 * Metrics Data Transfer Object
 * 
 * Standardized structure for user metrics and KPIs across all dashboard implementations.
 * Provides consistent metric data regardless of user role or dashboard context.
 */
class MetricsDTO
{
    public function __construct(
        public readonly string $period,
        public readonly array $attendance,
        public readonly array $performance,
        public readonly array $financial,
        public readonly array $productivity,
        public readonly array $goals,
        public readonly array $comparisons,
        public readonly array $trends,
        public readonly array $alerts = []
    ) {}
    
    /**
     * Convert DTO to array for API responses
     */
    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'attendance' => $this->attendance,
            'performance' => $this->performance,
            'financial' => $this->financial,
            'productivity' => $this->productivity,
            'goals' => $this->goals,
            'comparisons' => $this->comparisons,
            'trends' => $this->trends,
            'alerts' => $this->alerts,
            'summary' => $this->getSummary(),
        ];
    }
    
    /**
     * Create DTO instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            period: $data['period'] ?? 'today',
            attendance: $data['attendance'] ?? [],
            performance: $data['performance'] ?? [],
            financial: $data['financial'] ?? [],
            productivity: $data['productivity'] ?? [],
            goals: $data['goals'] ?? [],
            comparisons: $data['comparisons'] ?? [],
            trends: $data['trends'] ?? [],
            alerts: $data['alerts'] ?? []
        );
    }
    
    /**
     * Create metrics for Dokter role
     */
    public static function forDokter(array $data): self
    {
        return new self(
            period: $data['period'] ?? 'today',
            attendance: [
                'status' => $data['attendance_status'] ?? 'not_checked_in',
                'check_in_time' => $data['check_in_time'] ?? null,
                'check_out_time' => $data['check_out_time'] ?? null,
                'total_hours' => $data['total_hours'] ?? 0,
                'expected_hours' => $data['expected_hours'] ?? 8,
                'overtime_hours' => $data['overtime_hours'] ?? 0,
            ],
            performance: [
                'patients_treated' => $data['patients_treated'] ?? 0,
                'procedures_completed' => $data['procedures_completed'] ?? 0,
                'average_treatment_time' => $data['avg_treatment_time'] ?? 0,
                'patient_satisfaction' => $data['patient_satisfaction'] ?? 0,
                'efficiency_score' => $data['efficiency_score'] ?? 0,
            ],
            financial: [
                'jaspel_earned' => $data['jaspel_earned'] ?? 0,
                'procedures_revenue' => $data['procedures_revenue'] ?? 0,
                'bonus_earned' => $data['bonus_earned'] ?? 0,
                'total_earnings' => $data['total_earnings'] ?? 0,
            ],
            productivity: [
                'procedures_per_hour' => $data['procedures_per_hour'] ?? 0,
                'completion_rate' => $data['completion_rate'] ?? 0,
                'quality_score' => $data['quality_score'] ?? 0,
                'punctuality_score' => $data['punctuality_score'] ?? 0,
            ],
            goals: $data['goals'] ?? [],
            comparisons: $data['comparisons'] ?? [],
            trends: $data['trends'] ?? [],
            alerts: $data['alerts'] ?? []
        );
    }
    
    /**
     * Create metrics for Paramedis role
     */
    public static function forParamedis(array $data): self
    {
        return new self(
            period: $data['period'] ?? 'today',
            attendance: [
                'status' => $data['attendance_status'] ?? 'not_checked_in',
                'shift' => $data['shift'] ?? null,
                'location' => $data['work_location'] ?? null,
                'check_in_time' => $data['check_in_time'] ?? null,
                'total_hours' => $data['total_hours'] ?? 0,
            ],
            performance: [
                'tasks_completed' => $data['tasks_completed'] ?? 0,
                'patients_assisted' => $data['patients_assisted'] ?? 0,
                'procedures_assisted' => $data['procedures_assisted'] ?? 0,
                'efficiency_rating' => $data['efficiency_rating'] ?? 0,
            ],
            financial: [
                'jaspel_earned' => $data['jaspel_earned'] ?? 0,
                'shift_allowance' => $data['shift_allowance'] ?? 0,
                'overtime_pay' => $data['overtime_pay'] ?? 0,
                'total_earnings' => $data['total_earnings'] ?? 0,
            ],
            productivity: [
                'tasks_per_hour' => $data['tasks_per_hour'] ?? 0,
                'response_time' => $data['response_time'] ?? 0,
                'collaboration_score' => $data['collaboration_score'] ?? 0,
                'reliability_score' => $data['reliability_score'] ?? 0,
            ],
            goals: $data['goals'] ?? [],
            comparisons: $data['comparisons'] ?? [],
            trends: $data['trends'] ?? [],
            alerts: $data['alerts'] ?? []
        );
    }
    
    /**
     * Create metrics for Manager role
     */
    public static function forManager(array $data): self
    {
        return new self(
            period: $data['period'] ?? 'today',
            attendance: [
                'team_attendance_rate' => $data['team_attendance_rate'] ?? 0,
                'absent_staff' => $data['absent_staff'] ?? [],
                'late_arrivals' => $data['late_arrivals'] ?? [],
                'overtime_hours' => $data['overtime_hours'] ?? 0,
            ],
            performance: [
                'team_productivity' => $data['team_productivity'] ?? 0,
                'patient_satisfaction' => $data['patient_satisfaction'] ?? 0,
                'quality_metrics' => $data['quality_metrics'] ?? [],
                'efficiency_trends' => $data['efficiency_trends'] ?? [],
            ],
            financial: [
                'daily_revenue' => $data['daily_revenue'] ?? 0,
                'monthly_revenue' => $data['monthly_revenue'] ?? 0,
                'expenses' => $data['expenses'] ?? 0,
                'profit_margin' => $data['profit_margin'] ?? 0,
                'budget_variance' => $data['budget_variance'] ?? 0,
            ],
            productivity: [
                'operational_efficiency' => $data['operational_efficiency'] ?? 0,
                'resource_utilization' => $data['resource_utilization'] ?? 0,
                'staff_efficiency' => $data['staff_efficiency'] ?? 0,
                'process_optimization' => $data['process_optimization'] ?? 0,
            ],
            goals: $data['strategic_goals'] ?? [],
            comparisons: $data['comparisons'] ?? [],
            trends: $data['trends'] ?? [],
            alerts: $data['management_alerts'] ?? []
        );
    }
    
    /**
     * Get overall performance score (0-100)
     */
    public function getOverallScore(): float
    {
        $scores = [];
        
        // Attendance score
        if (!empty($this->attendance)) {
            $attendanceScore = match (true) {
                isset($this->attendance['completion_rate']) => $this->attendance['completion_rate'],
                isset($this->attendance['punctuality_score']) => $this->attendance['punctuality_score'],
                isset($this->attendance['team_attendance_rate']) => $this->attendance['team_attendance_rate'],
                default => 75
            };
            $scores[] = $attendanceScore;
        }
        
        // Performance score
        if (!empty($this->performance)) {
            $performanceScore = match (true) {
                isset($this->performance['efficiency_score']) => $this->performance['efficiency_score'],
                isset($this->performance['efficiency_rating']) => $this->performance['efficiency_rating'],
                isset($this->performance['team_productivity']) => $this->performance['team_productivity'],
                default => 75
            };
            $scores[] = $performanceScore;
        }
        
        // Productivity score
        if (!empty($this->productivity)) {
            $productivityScore = match (true) {
                isset($this->productivity['quality_score']) => $this->productivity['quality_score'],
                isset($this->productivity['reliability_score']) => $this->productivity['reliability_score'],
                isset($this->productivity['operational_efficiency']) => $this->productivity['operational_efficiency'],
                default => 75
            };
            $scores[] = $productivityScore;
        }
        
        return empty($scores) ? 0 : round(array_sum($scores) / count($scores), 1);
    }
    
    /**
     * Get critical alerts that need immediate attention
     */
    public function getCriticalAlerts(): array
    {
        return array_filter($this->alerts, fn($alert) => 
            ($alert['priority'] ?? 'low') === 'critical' || 
            ($alert['severity'] ?? 'low') === 'high'
        );
    }
    
    /**
     * Get metrics summary for quick overview
     */
    public function getSummary(): array
    {
        return [
            'overall_score' => $this->getOverallScore(),
            'critical_alerts' => count($this->getCriticalAlerts()),
            'goals_on_track' => count(array_filter(
                $this->goals, 
                fn($goal) => ($goal['status'] ?? 'pending') === 'on_track'
            )),
            'positive_trends' => count(array_filter(
                $this->trends, 
                fn($trend) => ($trend['direction'] ?? 'stable') === 'up'
            )),
            'period' => $this->period,
            'has_financial_data' => !empty($this->financial),
        ];
    }
    
    /**
     * Compare metrics with previous period
     */
    public function compareWithPrevious(MetricsDTO $previous): array
    {
        return [
            'attendance_change' => $this->calculateChange(
                $this->attendance, 
                $previous->attendance, 
                'completion_rate'
            ),
            'performance_change' => $this->calculateChange(
                $this->performance, 
                $previous->performance, 
                'efficiency_score'
            ),
            'financial_change' => $this->calculateChange(
                $this->financial, 
                $previous->financial, 
                'total_earnings'
            ),
            'overall_score_change' => $this->getOverallScore() - $previous->getOverallScore(),
        ];
    }
    
    /**
     * Calculate percentage change between periods
     */
    private function calculateChange(array $current, array $previous, string $key): float
    {
        $currentValue = $current[$key] ?? 0;
        $previousValue = $previous[$key] ?? 0;
        
        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }
        
        return round((($currentValue - $previousValue) / $previousValue) * 100, 1);
    }
}