<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StrategicGoal;
use App\Models\DepartmentPerformanceMetric;
use App\Models\User;
use Carbon\Carbon;

class ManagerFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        // Find manager user
        $manager = User::whereHas('roles', function($query) {
            $query->where('name', 'manajer');
        })->first();

        if (!$manager) {
            echo "⚠️  No manager user found. Please create a user with 'manajer' role first.\n";
            return;
        }

        // Create sample strategic goals
        $strategicGoals = [
            [
                'title' => 'Increase Monthly Revenue by 25%',
                'description' => 'Achieve 25% revenue growth through improved patient services and operational efficiency.',
                'category' => 'financial',
                'period' => 'quarterly',
                'start_date' => now()->startOfQuarter(),
                'end_date' => now()->endOfQuarter(),
                'target_value' => 62500000, // 25% increase from 50M
                'current_value' => 45000000,
                'unit' => 'IDR',
                'status' => 'active',
                'priority' => 1,
                'success_criteria' => [
                    ['criterion' => 'Monthly revenue > 60M IDR', 'achieved' => false],
                    ['criterion' => 'Patient satisfaction > 85%', 'achieved' => false],
                    ['criterion' => 'Cost efficiency improvement > 15%', 'achieved' => false],
                ],
                'created_by' => $manager->id,
                'assigned_to' => $manager->id,
            ],
            [
                'title' => 'Improve Patient Satisfaction to 90%',
                'description' => 'Enhance patient experience through better service quality and reduced waiting times.',
                'category' => 'patient_satisfaction',
                'period' => 'monthly',
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'target_value' => 90,
                'current_value' => 82,
                'unit' => 'percentage',
                'status' => 'active',
                'priority' => 2,
                'success_criteria' => [
                    ['criterion' => 'Waiting time < 30 minutes', 'achieved' => true],
                    ['criterion' => 'Service quality score > 4.5/5', 'achieved' => false],
                    ['criterion' => 'Zero critical complaints', 'achieved' => true],
                ],
                'created_by' => $manager->id,
                'assigned_to' => $manager->id,
            ],
            [
                'title' => 'Reduce Operational Costs by 10%',
                'description' => 'Optimize operational efficiency and reduce unnecessary expenses without compromising quality.',
                'category' => 'operational',
                'period' => 'quarterly',
                'start_date' => now()->startOfQuarter(),
                'end_date' => now()->endOfQuarter(),
                'target_value' => 10,
                'current_value' => 6.5,
                'unit' => 'percentage',
                'status' => 'active',
                'priority' => 3,
                'success_criteria' => [
                    ['criterion' => 'Supply cost reduction > 8%', 'achieved' => true],
                    ['criterion' => 'Energy cost reduction > 12%', 'achieved' => false],
                    ['criterion' => 'Administrative efficiency > 95%', 'achieved' => false],
                ],
                'created_by' => $manager->id,
            ],
            [
                'title' => 'Staff Training Completion 100%',
                'description' => 'Ensure all staff complete mandatory training programs for improved competency.',
                'category' => 'staff',
                'period' => 'monthly',
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'target_value' => 100,
                'current_value' => 85,
                'unit' => 'percentage',
                'status' => 'active',
                'priority' => 4,
                'success_criteria' => [
                    ['criterion' => 'Safety training completion 100%', 'achieved' => true],
                    ['criterion' => 'Technical skills update 100%', 'achieved' => false],
                    ['criterion' => 'Customer service training 100%', 'achieved' => true],
                ],
                'created_by' => $manager->id,
            ],
        ];

        foreach ($strategicGoals as $goalData) {
            StrategicGoal::create($goalData);
        }

        // Create sample department performance metrics
        $currentDate = now()->toDateString();
        $metrics = [
            // Financial Department
            [
                'department' => 'financial',
                'metric_name' => 'Monthly Revenue',
                'metric_value' => 45000000,
                'metric_unit' => 'IDR',
                'measurement_date' => $currentDate,
                'period_type' => 'monthly',
                'target_value' => 50000000,
                'benchmark_value' => 40000000,
                'trend' => 'improving',
                'is_kpi' => true,
                'score' => 90,
                'recorded_by' => $manager->id,
            ],
            [
                'department' => 'financial',
                'metric_name' => 'Cost Control Efficiency',
                'metric_value' => 88.5,
                'metric_unit' => 'percentage',
                'measurement_date' => $currentDate,
                'period_type' => 'monthly',
                'target_value' => 85,
                'benchmark_value' => 80,
                'trend' => 'stable',
                'is_kpi' => true,
                'score' => 95,
                'recorded_by' => $manager->id,
            ],
            // Medical Department
            [
                'department' => 'medical',
                'metric_name' => 'Patient Satisfaction',
                'metric_value' => 87.2,
                'metric_unit' => 'percentage',
                'measurement_date' => $currentDate,
                'period_type' => 'monthly',
                'target_value' => 90,
                'benchmark_value' => 85,
                'trend' => 'improving',
                'is_kpi' => true,
                'score' => 85,
                'recorded_by' => $manager->id,
            ],
            [
                'department' => 'medical',
                'metric_name' => 'Average Treatment Time',
                'metric_value' => 35,
                'metric_unit' => 'minutes',
                'measurement_date' => $currentDate,
                'period_type' => 'monthly',
                'target_value' => 30,
                'benchmark_value' => 40,
                'trend' => 'improving',
                'is_kpi' => true,
                'score' => 75,
                'recorded_by' => $manager->id,
            ],
            // Administrative Department
            [
                'department' => 'administrative',
                'metric_name' => 'Processing Efficiency',
                'metric_value' => 92.8,
                'metric_unit' => 'percentage',
                'measurement_date' => $currentDate,
                'period_type' => 'monthly',
                'target_value' => 95,
                'benchmark_value' => 90,
                'trend' => 'stable',
                'is_kpi' => true,
                'score' => 88,
                'recorded_by' => $manager->id,
            ],
            // Support Department
            [
                'department' => 'support',
                'metric_name' => 'System Uptime',
                'metric_value' => 99.8,
                'metric_unit' => 'percentage',
                'measurement_date' => $currentDate,
                'period_type' => 'monthly',
                'target_value' => 99.9,
                'benchmark_value' => 99.5,
                'trend' => 'stable',
                'is_kpi' => true,
                'score' => 95,
                'recorded_by' => $manager->id,
            ],
        ];

        foreach ($metrics as $metricData) {
            DepartmentPerformanceMetric::create($metricData);
        }

        echo "✅ Manager features seeded successfully!\n";
        echo "🎯 Created " . count($strategicGoals) . " strategic goals\n";
        echo "📊 Created " . count($metrics) . " performance metrics\n";
        echo "🏢 Manager panel ready with sample data!\n";
    }
}