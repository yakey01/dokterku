<?php
/**
 * System Monitoring Script
 * Proactive monitoring and alerting for system health
 */

require_once __DIR__ . '/vendor/autoload.php';

class SystemMonitor
{
    private array $healthChecks = [];
    private array $alerts = [];

    public function runHealthCheck(): void
    {
        echo "🔍 Starting System Health Check...\n\n";

        $this->checkDatabaseConnection();
        $this->checkAssetFiles();
        $this->checkFilePermissions();
        $this->checkDiskSpace();
        $this->checkLogFiles();
        $this->checkCacheSystem();
        
        $this->generateReport();
    }

    private function checkDatabaseConnection(): void
    {
        echo "📊 Checking database connection...\n";
        
        try {
            $app = require __DIR__ . '/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            
            $connection = DB::connection();
            $connection->getPdo();
            
            $tableCount = DB::select("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'")[0]->count;
            
            $this->recordCheck('Database Connection', 'healthy', 
                "Connected with {$tableCount} tables");
                
        } catch (Exception $e) {
            $this->recordCheck('Database Connection', 'critical', 
                "Failed: " . $e->getMessage());
        }
    }

    private function checkAssetFiles(): void
    {
        echo "📦 Checking asset files...\n";
        
        $criticalAssets = [
            'public/build/manifest.json' => 'Build manifest',
            'public/react-build/build/manifest.json' => 'React manifest',
        ];
        
        $allHealthy = true;
        $details = [];
        
        foreach ($criticalAssets as $file => $description) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                if (json_decode($content) !== null) {
                    $details[] = "✅ {$description}";
                } else {
                    $details[] = "❌ {$description} (invalid JSON)";
                    $allHealthy = false;
                }
            } else {
                $details[] = "❌ {$description} (missing)";
                $allHealthy = false;
            }
        }
        
        $this->recordCheck('Asset Files', $allHealthy ? 'healthy' : 'warning', 
            implode(', ', $details));
    }

    private function checkFilePermissions(): void
    {
        echo "📁 Checking file permissions...\n";
        
        $criticalDirs = [
            'storage/logs',
            'storage/app', 
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'bootstrap/cache',
            'public/build'
        ];
        
        $allWritable = true;
        $issues = [];
        
        foreach ($criticalDirs as $dir) {
            if (!is_dir($dir)) {
                $issues[] = "Missing: {$dir}";
                $allWritable = false;
            } elseif (!is_writable($dir)) {
                $issues[] = "Not writable: {$dir}";
                $allWritable = false;
            }
        }
        
        $status = $allWritable ? 'healthy' : 'critical';
        $message = $allWritable ? 'All directories writable' : implode(', ', $issues);
        
        $this->recordCheck('File Permissions', $status, $message);
    }

    private function checkDiskSpace(): void
    {
        echo "💾 Checking disk space...\n";
        
        $totalBytes = disk_total_space('.');
        $freeBytes = disk_free_space('.');
        $usedBytes = $totalBytes - $freeBytes;
        $usagePercent = ($usedBytes / $totalBytes) * 100;
        
        $totalGB = round($totalBytes / (1024**3), 2);
        $freeGB = round($freeBytes / (1024**3), 2);
        $usagePercentRounded = round($usagePercent, 1);
        
        $status = 'healthy';
        if ($usagePercent > 90) {
            $status = 'critical';
        } elseif ($usagePercent > 80) {
            $status = 'warning';
        }
        
        $this->recordCheck('Disk Space', $status, 
            "Used: {$usagePercentRounded}% ({$freeGB}GB free of {$totalGB}GB total)");
    }

    private function checkLogFiles(): void
    {
        echo "📋 Checking log files...\n";
        
        $logDir = 'storage/logs';
        $issues = [];
        $status = 'healthy';
        
        if (!is_dir($logDir)) {
            $this->recordCheck('Log Files', 'critical', 'Log directory missing');
            return;
        }
        
        // Check for large log files
        $logFiles = glob($logDir . '/*.log');
        foreach ($logFiles as $logFile) {
            $sizeBytes = filesize($logFile);
            $sizeMB = round($sizeBytes / (1024**2), 1);
            
            if ($sizeMB > 100) {
                $issues[] = basename($logFile) . " is {$sizeMB}MB";
                $status = 'warning';
            }
        }
        
        // Check for recent errors
        $latestLog = $logDir . '/laravel.log';
        if (file_exists($latestLog)) {
            $recentContent = tail($latestLog, 50);
            if (stripos($recentContent, 'ERROR') !== false) {
                $issues[] = 'Recent errors found';
                $status = 'warning';
            }
        }
        
        $message = empty($issues) ? 'Log files healthy' : implode(', ', $issues);
        $this->recordCheck('Log Files', $status, $message);
    }

    private function checkCacheSystem(): void
    {
        echo "⚡ Checking cache system...\n";
        
        try {
            $app = require __DIR__ . '/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            
            // Test cache write/read
            Cache::put('health_check', 'test', 60);
            $cached = Cache::get('health_check');
            
            if ($cached === 'test') {
                $this->recordCheck('Cache System', 'healthy', 'Cache read/write working');
            } else {
                $this->recordCheck('Cache System', 'warning', 'Cache read/write failed');
            }
            
            Cache::forget('health_check');
            
        } catch (Exception $e) {
            $this->recordCheck('Cache System', 'critical', 
                'Cache system failed: ' . $e->getMessage());
        }
    }

    private function recordCheck(string $component, string $status, string $details): void
    {
        $icons = [
            'healthy' => '✅',
            'warning' => '⚠️',
            'critical' => '❌'
        ];
        
        $icon = $icons[$status] ?? '❓';
        echo "  {$icon} {$component}: {$details}\n";
        
        $this->healthChecks[] = [
            'component' => $component,
            'status' => $status,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($status !== 'healthy') {
            $this->alerts[] = [
                'severity' => $status,
                'component' => $component,
                'message' => $details
            ];
        }
    }

    private function generateReport(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 SYSTEM HEALTH REPORT\n";
        echo str_repeat('=', 60) . "\n\n";
        
        $healthy = array_filter($this->healthChecks, fn($c) => $c['status'] === 'healthy');
        $warnings = array_filter($this->healthChecks, fn($c) => $c['status'] === 'warning');
        $critical = array_filter($this->healthChecks, fn($c) => $c['status'] === 'critical');
        
        echo "Total Checks: " . count($this->healthChecks) . "\n";
        echo "✅ Healthy: " . count($healthy) . "\n";
        echo "⚠️  Warnings: " . count($warnings) . "\n";
        echo "❌ Critical: " . count($critical) . "\n\n";
        
        // Overall system status
        if (count($critical) > 0) {
            $overallStatus = "❌ CRITICAL ISSUES DETECTED";
        } elseif (count($warnings) > 0) {
            $overallStatus = "⚠️  WARNINGS DETECTED";
        } else {
            $overallStatus = "✅ SYSTEM HEALTHY";
        }
        
        echo "Overall Status: {$overallStatus}\n\n";
        
        // Alert summary
        if (!empty($this->alerts)) {
            echo "🚨 ALERTS REQUIRING ATTENTION:\n";
            foreach ($this->alerts as $alert) {
                $icon = $alert['severity'] === 'critical' ? '❌' : '⚠️';
                echo "  {$icon} {$alert['component']}: {$alert['message']}\n";
            }
            echo "\n";
        }
        
        // Recommendations
        echo "💡 RECOMMENDATIONS:\n";
        if (empty($this->alerts)) {
            echo "  • System is running smoothly\n";
            echo "  • Continue regular monitoring\n";
            echo "  • Consider automated health checks\n";
        } else {
            if (count($critical) > 0) {
                echo "  • Address critical issues immediately\n";
                echo "  • Check system logs for details\n";
                echo "  • Consider system maintenance\n";
            }
            if (count($warnings) > 0) {
                echo "  • Review warning conditions\n";
                echo "  • Schedule maintenance if needed\n";
            }
            echo "  • Run this health check regularly\n";
        }
        
        // Save report to file
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_status' => $overallStatus,
            'summary' => [
                'total' => count($this->healthChecks),
                'healthy' => count($healthy),
                'warnings' => count($warnings),
                'critical' => count($critical)
            ],
            'checks' => $this->healthChecks,
            'alerts' => $this->alerts
        ];
        
        $reportFile = 'storage/logs/health-report-' . date('Y-m-d-H-i') . '.json';
        file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));
        
        echo "\n📄 Report saved to: {$reportFile}\n";
        echo str_repeat('=', 60) . "\n";
    }
}

// Helper function to read last N lines of a file
function tail(string $filename, int $lines): string
{
    if (!file_exists($filename)) {
        return '';
    }
    
    $file = file($filename);
    return implode('', array_slice($file, -$lines));
}

// Bootstrap Laravel classes
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

// Run the health check
if (php_sapi_name() === 'cli') {
    $monitor = new SystemMonitor();
    $monitor->runHealthCheck();
}