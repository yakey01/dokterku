<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DepartmentPerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'department',
        'metric_name',
        'metric_value',
        'metric_unit',
        'measurement_date',
        'period_type',
        'target_value',
        'benchmark_value',
        'trend',
        'metadata',
        'notes',
        'is_kpi',
        'score',
        'recorded_by',
    ];

    protected $casts = [
        'metric_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'benchmark_value' => 'decimal:2',
        'measurement_date' => 'date',
        'metadata' => 'array',
        'is_kpi' => 'boolean',
        'score' => 'integer',
    ];

    // Relationships
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Accessors & Mutators
    public function getPerformancePercentageAttribute(): float
    {
        if (!$this->target_value || $this->target_value == 0) {
            return 0;
        }
        
        return ($this->metric_value / $this->target_value) * 100;
    }

    public function getTrendColorAttribute(): string
    {
        return match ($this->trend) {
            'improving' => 'success',
            'declining' => 'danger',
            'stable' => 'warning',
            default => 'gray',
        };
    }

    public function getFormattedValueAttribute(): string
    {
        return match ($this->metric_unit) {
            'IDR' => 'Rp ' . number_format($this->metric_value, 0, ',', '.'),
            'percentage' => number_format($this->metric_value, 1) . '%',
            'count' => number_format($this->metric_value, 0),
            'hours' => number_format($this->metric_value, 1) . ' hours',
            default => number_format($this->metric_value, 2) . ' ' . $this->metric_unit,
        };
    }

    // Scopes
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeKpiOnly($query)
    {
        return $query->where('is_kpi', true);
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period_type', $period);
    }

    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('measurement_date', Carbon::now()->month)
                     ->whereYear('measurement_date', Carbon::now()->year);
    }

    public function scopeLastMonth($query)
    {
        $lastMonth = Carbon::now()->subMonth();
        return $query->whereMonth('measurement_date', $lastMonth->month)
                     ->whereYear('measurement_date', $lastMonth->year);
    }

    // Static Methods
    public static function getDepartmentOptions(): array
    {
        return [
            'medical' => '🏥 Medical',
            'administrative' => '📋 Administrative',
            'financial' => '💰 Financial',
            'support' => '🛠️ Support',
        ];
    }

    public static function getPeriodTypeOptions(): array
    {
        return [
            'daily' => '📅 Daily',
            'weekly' => '📊 Weekly',
            'monthly' => '📆 Monthly',
            'quarterly' => '📈 Quarterly',
        ];
    }

    public static function getTrendOptions(): array
    {
        return [
            'improving' => '📈 Improving',
            'declining' => '📉 Declining',
            'stable' => '➡️ Stable',
        ];
    }

    // Business Logic
    public static function calculateDepartmentScore(string $department): int
    {
        $kpiMetrics = static::byDepartment($department)
            ->kpiOnly()
            ->currentMonth()
            ->get();

        if ($kpiMetrics->isEmpty()) {
            return 0;
        }

        $totalScore = $kpiMetrics->sum('score');
        $avgScore = $totalScore / $kpiMetrics->count();

        return round($avgScore);
    }

    public static function getDepartmentTrend(string $department): string
    {
        $currentMetrics = static::byDepartment($department)->currentMonth()->avg('metric_value');
        $lastMonthMetrics = static::byDepartment($department)->lastMonth()->avg('metric_value');

        if (!$currentMetrics || !$lastMonthMetrics) {
            return 'stable';
        }

        $changePercentage = (($currentMetrics - $lastMonthMetrics) / $lastMonthMetrics) * 100;

        if ($changePercentage > 5) {
            return 'improving';
        } elseif ($changePercentage < -5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
}
