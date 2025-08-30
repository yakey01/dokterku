<?php

namespace App\Filament\Bendahara\Resources\LaporanKeuanganReportResource\Widgets;

use App\Services\ProcedureJaspelCalculationService;
use App\Services\SubAgents\ValidationSubAgentService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Request;
use Carbon\Carbon;

class JaspelDetailStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Get user ID from route parameter
        $userId = Request::route('record');
        
        if (!$userId) {
            return [];
        }

        $procedureCalculator = app(ProcedureJaspelCalculationService::class);
        $validationAgent = app(ValidationSubAgentService::class);
        
        $procedureData = $procedureCalculator->calculateJaspelFromProcedures($userId, []);
        $validationData = $validationAgent->performCermatJaspelValidation($userId);

        // Calculate trending data (mock for now)
        $currentMonth = $procedureData['total_jaspel'] ?? 0;
        $trend = $currentMonth > 500000 ? 'increase' : ($currentMonth > 200000 ? 'neutral' : 'decrease');
        $trendPercentage = rand(5, 25); // Mock percentage

        return [
            Stat::make('Total Jaspel', 'Rp ' . number_format($procedureData['total_jaspel'] ?? 0, 0, ',', '.'))
                ->description($this->getTrendDescription($trend, $trendPercentage))
                ->descriptionIcon($this->getTrendIcon($trend))
                ->color($this->getTrendColor($trend))
                ->chart($this->generateTrendChart()),

            Stat::make('Procedures Count', number_format($procedureData['total_procedures'] ?? 0))
                ->description(($procedureData['tindakan_count'] ?? 0) . ' tindakan + ' . ($procedureData['pasien_days'] ?? 0) . ' hari pasien')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Validation Score', ($validationData['summary']['validation_score'] ?? 0) . '%')
                ->description($this->getValidationDescription($validationData['summary']['validation_score'] ?? 0))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($this->getValidationColor($validationData['summary']['validation_score'] ?? 0)),

            Stat::make('Data Quality', $this->getDataQualityStatus($procedureData, $validationData))
                ->description('Integritas data dan akurasi calculation')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }

    protected function getTrendDescription(string $trend, int $percentage): string
    {
        return match($trend) {
            'increase' => $percentage . '% increase from last month',
            'decrease' => $percentage . '% decrease from last month',
            'neutral' => 'Stable performance',
            default => 'No trend data'
        };
    }

    protected function getTrendIcon(string $trend): string
    {
        return match($trend) {
            'increase' => 'heroicon-m-arrow-trending-up',
            'decrease' => 'heroicon-m-arrow-trending-down',
            'neutral' => 'heroicon-m-minus',
            default => 'heroicon-m-question-mark-circle'
        };
    }

    protected function getTrendColor(string $trend): string
    {
        return match($trend) {
            'increase' => 'success',
            'decrease' => 'danger',
            'neutral' => 'warning',
            default => 'gray'
        };
    }

    protected function getValidationDescription(int $score): string
    {
        if ($score >= 90) {
            return 'Excellent data quality';
        } elseif ($score >= 75) {
            return 'Good data quality';
        } elseif ($score >= 60) {
            return 'Fair data quality';
        } else {
            return 'Needs improvement';
        }
    }

    protected function getValidationColor(int $score): string
    {
        if ($score >= 90) {
            return 'success';
        } elseif ($score >= 75) {
            return 'info';
        } elseif ($score >= 60) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    protected function getDataQualityStatus(array $procedureData, array $validationData): string
    {
        $totalJaspel = $procedureData['total_jaspel'] ?? 0;
        $validationScore = $validationData['summary']['validation_score'] ?? 0;
        
        if ($totalJaspel > 0 && $validationScore >= 80) {
            return 'Verified ✅';
        } elseif ($totalJaspel > 0 && $validationScore >= 60) {
            return 'Good 👍';
        } elseif ($totalJaspel > 0) {
            return 'Review Needed ⚠️';
        } else {
            return 'No Data 📭';
        }
    }

    protected function generateTrendChart(): array
    {
        // Mock chart data - in real implementation would pull historical data
        return [
            rand(100, 200),
            rand(150, 250),
            rand(200, 300),
            rand(180, 280),
            rand(220, 320),
            rand(250, 350),
            rand(300, 400),
        ];
    }
}