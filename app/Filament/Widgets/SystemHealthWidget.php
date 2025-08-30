<?php

namespace App\Filament\Widgets;

use App\Models\SystemMetric;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = '30s';
    
    public static function canView(): bool
    {
        return Auth::user()?->hasRole(['super-admin', 'admin']) ?? false;
    }
    
    protected function getStats(): array
    {
        try {
            // Check if system_metrics table exists
            if (!Schema::hasTable('system_metrics')) {
                return $this->getFallbackStats('Table system_metrics not found');
            }
            
            $healthSummary = SystemMetric::getHealthSummary();
            
            // Get current system metrics
            $systemMetrics = SystemMetric::getMetricsByType(SystemMetric::TYPE_SYSTEM, 1);
            $databaseMetrics = SystemMetric::getMetricsByType(SystemMetric::TYPE_DATABASE, 1);
            $performanceMetrics = SystemMetric::getMetricsByType(SystemMetric::TYPE_PERFORMANCE, 1);
            
            // Memory usage
            $memoryUsage = $this->getMetricValue($systemMetrics, 'memory_usage', 0);
            $memoryStatus = $this->getMetricStatus($systemMetrics, 'memory_usage');
            
            // Database connection time - fallback to query_time if connection_time not available
            $dbConnectionTime = $this->getMetricValue($databaseMetrics, 'connection_time', 0);
            if ($dbConnectionTime === 0) {
                $dbConnectionTime = $this->getMetricValue($databaseMetrics, 'query_time', 0);
            }
            $dbStatus = $this->getMetricStatus($databaseMetrics, 'connection_time');
            if ($dbStatus === 'unknown') {
                $dbStatus = $this->getMetricStatus($databaseMetrics, 'query_time');
            }
            
            // Response time
            $responseTime = $this->getMetricValue($performanceMetrics, 'response_time', 0);
            $responseStatus = $this->getMetricStatus($performanceMetrics, 'response_time');
            
            // Overall health
            $overallStatus = $healthSummary['overall_status'] ?? 'healthy';
            $criticalCount = $healthSummary['critical'] ?? 0;
        } catch (\Exception $e) {
            \Log::error('SystemHealthWidget error: ' . $e->getMessage());
            return $this->getFallbackStats('Error loading metrics: ' . $e->getMessage());
        }
        
        return [
            Stat::make('System Health', ucfirst($overallStatus))
                ->description($this->getHealthDescription($overallStatus, $criticalCount))
                ->descriptionIcon($this->getStatusIcon($overallStatus))
                ->color($this->getStatusColor($overallStatus)),
                
            Stat::make('Memory Usage', $memoryUsage > 0 ? number_format($memoryUsage, 1) . '%' : 'N/A')
                ->description($this->getMemoryDescription($memoryUsage))
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($this->getStatusColor($memoryStatus)),
                
            Stat::make('Database', $dbConnectionTime > 0 ? number_format($dbConnectionTime, 0) . 'ms' : 'N/A')
                ->description($this->getDatabaseDescription($dbConnectionTime))
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color($this->getStatusColor($dbStatus)),
                
            Stat::make('Response Time', $responseTime > 0 ? number_format($responseTime, 0) . 'ms' : 'N/A')
                ->description($this->getResponseDescription($responseTime))
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color($this->getStatusColor($responseStatus)),
        ];
    }
    
    private function getFallbackStats($reason = 'No data available'): array
    {
        return [
            Stat::make('System Health', 'Unknown')
                ->description($reason)
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('gray'),
                
            Stat::make('Memory Usage', 'N/A')
                ->description('Metrics unavailable')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('gray'),
                
            Stat::make('Database', 'N/A')
                ->description('Metrics unavailable')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('gray'),
                
            Stat::make('Response Time', 'N/A')
                ->description('Metrics unavailable')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('gray'),
        ];
    }
    
    private function getMetricValue($metrics, $name, $default = 0)
    {
        if (isset($metrics[$name]) && $metrics[$name]->isNotEmpty()) {
            return $metrics[$name]->first()->metric_value;
        }
        return $default;
    }
    
    private function getMetricStatus($metrics, $name)
    {
        if (isset($metrics[$name]) && $metrics[$name]->isNotEmpty()) {
            return $metrics[$name]->first()->status;
        }
        return 'unknown';
    }
    
    private function getStatusColor($status)
    {
        return match($status) {
            'healthy' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
            default => 'gray',
        };
    }
    
    private function getStatusIcon($status)
    {
        return match($status) {
            'healthy' => 'heroicon-m-check-circle',
            'warning' => 'heroicon-m-exclamation-triangle',
            'critical' => 'heroicon-m-x-circle',
            default => 'heroicon-m-question-mark-circle',
        };
    }
    
    private function getHealthDescription($status, $criticalCount)
    {
        return match($status) {
            'healthy' => 'All systems operational',
            'warning' => 'Some systems need attention',
            'critical' => $criticalCount > 1 ? "{$criticalCount} critical alerts" : "1 critical alert",
            default => 'Status unknown',
        };
    }
    
    private function getMemoryDescription($usage)
    {
        if ($usage == 0) return 'No data available';
        
        if ($usage > 85) {
            return 'High memory usage';
        } elseif ($usage > 70) {
            return 'Moderate memory usage';
        } else {
            return 'Memory usage normal';
        }
    }
    
    private function getDatabaseDescription($connectionTime)
    {
        if ($connectionTime == 0) return 'No data available';
        
        if ($connectionTime > 1000) {
            return 'Slow database connection';
        } elseif ($connectionTime > 500) {
            return 'Moderate connection time';
        } else {
            return 'Fast database connection';
        }
    }
    
    private function getResponseDescription($responseTime)
    {
        if ($responseTime == 0) return 'No data available';
        
        if ($responseTime > 2000) {
            return 'Slow response time';
        } elseif ($responseTime > 1000) {
            return 'Moderate response time';
        } else {
            return 'Fast response time';
        }
    }
}