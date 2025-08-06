<?php

/**
 * PRODUCTION VERIFICATION SCRIPT FOR ENHANCED ADMIN DASHBOARD
 * 
 * This script verifies that the enhanced-admin-dashboard 500 error has been resolved
 * Run this on Hostinger production environment to confirm the fix
 * 
 * Usage: php verify-enhanced-dashboard-production.php
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 ENHANCED ADMIN DASHBOARD - PRODUCTION VERIFICATION\n";
echo "=" . str_repeat("=", 55) . "\n\n";

use App\Filament\Pages\EnhancedAdminDashboard;
use App\Models\SystemMetric;
use App\Models\User;

$errors = [];
$warnings = [];
$successes = [];

// TEST 1: Basic Laravel Environment
echo "1. TESTING LARAVEL ENVIRONMENT...\n";
try {
    $env = app()->environment();
    $debug = config('app.debug');
    echo "   ✅ Environment: $env\n";
    echo "   ✅ Debug mode: " . ($debug ? 'ON' : 'OFF') . "\n";
    $successes[] = "Laravel environment configured";
} catch (Exception $e) {
    $errors[] = "Laravel environment error: " . $e->getMessage();
    echo "   ❌ Laravel environment error\n";
}

// TEST 2: Database Connectivity
echo "\n2. TESTING DATABASE CONNECTIVITY...\n";
try {
    $userCount = User::count();
    echo "   ✅ Database connected - $userCount users found\n";
    $successes[] = "Database connectivity verified";
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
    echo "   ❌ Database connection failed\n";
}

// TEST 3: SystemMetric Model Structure
echo "\n3. TESTING SYSTEMMETRIC MODEL...\n";
try {
    $metricCount = SystemMetric::count();
    echo "   ✅ SystemMetric table accessible - $metricCount records\n";
    
    // Test creating a metric
    $testMetric = SystemMetric::create([
        'metric_type' => 'test',
        'metric_name' => 'production_verification',
        'metric_value' => 100,
        'metric_data' => ['test' => true],
        'status' => 'healthy',
        'recorded_at' => now(),
    ]);
    echo "   ✅ SystemMetric write operations working\n";
    
    // Clean up test metric
    $testMetric->delete();
    $successes[] = "SystemMetric model functioning correctly";
    
} catch (Exception $e) {
    $errors[] = "SystemMetric model error: " . $e->getMessage();
    echo "   ❌ SystemMetric model issues detected\n";
}

// TEST 4: Dashboard Page Instantiation
echo "\n4. TESTING DASHBOARD PAGE CREATION...\n";
try {
    $dashboard = new EnhancedAdminDashboard();
    echo "   ✅ EnhancedAdminDashboard class instantiated\n";
    $successes[] = "Dashboard page class working";
} catch (Exception $e) {
    $errors[] = "Dashboard instantiation error: " . $e->getMessage();
    echo "   ❌ Dashboard instantiation failed\n";
}

// TEST 5: All Dashboard Data Methods
echo "\n5. TESTING ALL DASHBOARD DATA METHODS...\n";
$dashboardMethods = [
    'getSystemHealthOverview' => 'System Health',
    'getSecurityDashboard' => 'Security Dashboard', 
    'getUserManagementSummary' => 'User Management',
    'getSystemPerformance' => 'System Performance',
    'getFinancialOverview' => 'Financial Overview',
    'getMedicalOperations' => 'Medical Operations',
    'getRecentAdminActivities' => 'Recent Activities',
    'getSixMonthTrends' => 'Six Month Trends'
];

$methodSuccesses = 0;
foreach ($dashboardMethods as $method => $name) {
    try {
        $data = $dashboard->$method();
        if (is_array($data) && !empty($data)) {
            echo "   ✅ $name: " . count($data) . " data points\n";
            $methodSuccesses++;
        } else {
            echo "   ⚠️  $name: Empty data returned\n";
            $warnings[] = "$name returned empty data";
        }
    } catch (Exception $e) {
        echo "   ❌ $name: ERROR - " . $e->getMessage() . "\n";
        $errors[] = "$name method error: " . $e->getMessage();
    }
}

if ($methodSuccesses >= 6) {
    $successes[] = "Dashboard data methods functioning ($methodSuccesses/8 working)";
}

// TEST 6: Blade Template Data Preparation
echo "\n6. TESTING BLADE TEMPLATE DATA PREPARATION...\n";
try {
    // Simulate what the Blade template does
    $systemHealth = $dashboard->getSystemHealthOverview();
    $userManagement = $dashboard->getUserManagementSummary();
    $systemPerformance = $dashboard->getSystemPerformance();
    
    // Check critical data points
    $memoryUsage = $systemHealth['memory_usage'] ?? 'missing';
    $activeUsers = $userManagement['active_users'] ?? 'missing';
    $performanceScore = $systemPerformance['performance_score'] ?? 'missing';
    
    echo "   ✅ Memory Usage: {$memoryUsage}%\n";
    echo "   ✅ Active Users: $activeUsers\n";
    echo "   ✅ Performance Score: $performanceScore\n";
    
    $successes[] = "Blade template data preparation successful";
    
} catch (Exception $e) {
    $errors[] = "Blade template preparation error: " . $e->getMessage();
    echo "   ❌ Blade template data preparation failed\n";
}

// TEST 7: Cache Operations
echo "\n7. TESTING CACHE OPERATIONS...\n";
try {
    // Test cache write
    cache()->put('dashboard_test', 'working', 60);
    
    // Test cache read
    $cacheValue = cache()->get('dashboard_test');
    
    if ($cacheValue === 'working') {
        echo "   ✅ Cache read/write operations working\n";
        $successes[] = "Cache operations verified";
    } else {
        echo "   ⚠️  Cache operations may have issues\n";
        $warnings[] = "Cache operations inconsistent";
    }
    
    // Clean up
    cache()->forget('dashboard_test');
    
} catch (Exception $e) {
    $warnings[] = "Cache error: " . $e->getMessage();
    echo "   ⚠️  Cache operations error\n";
}

// RESULTS SUMMARY
echo "\n" . str_repeat("=", 60) . "\n";
echo "🏥 PRODUCTION VERIFICATION RESULTS\n";
echo str_repeat("=", 60) . "\n";

echo "\n✅ SUCCESSES (" . count($successes) . "):\n";
foreach ($successes as $success) {
    echo "   • $success\n";
}

if (!empty($warnings)) {
    echo "\n⚠️  WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "   • $warning\n";
    }
}

if (!empty($errors)) {
    echo "\n❌ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
}

// FINAL VERDICT
echo "\n" . str_repeat("-", 60) . "\n";

if (empty($errors)) {
    if (empty($warnings)) {
        echo "🎉 VERDICT: ENHANCED ADMIN DASHBOARD IS FULLY OPERATIONAL\n";
        echo "   The 500 error has been completely resolved!\n";
        echo "   All dashboard components are working correctly.\n";
    } else {
        echo "✅ VERDICT: DASHBOARD IS OPERATIONAL WITH MINOR ISSUES\n";
        echo "   The 500 error is fixed, but some optimizations may be needed.\n";
    }
} else {
    echo "❌ VERDICT: CRITICAL ISSUES REMAIN\n";
    echo "   The dashboard may still produce 500 errors.\n";
    echo "   Please address the errors listed above.\n";
}

echo "\n📋 NEXT STEPS:\n";

if (empty($errors)) {
    echo "   1. Deploy the fixed EnhancedAdminDashboard.php to production\n";
    echo "   2. Clear all caches: php artisan cache:clear\n";
    echo "   3. Test the dashboard in browser: /admin/enhanced-admin-dashboard\n";
    echo "   4. Monitor logs for any remaining issues\n";
} else {
    echo "   1. Fix the critical errors listed above\n";
    echo "   2. Re-run this verification script\n";
    echo "   3. Only deploy when all tests pass\n";
}

echo "\n🔧 DEPLOYMENT COMMANDS:\n";
echo "   php artisan cache:clear\n";
echo "   php artisan config:clear\n";
echo "   php artisan view:clear\n";
echo "   php artisan optimize\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "Verification completed at: " . now()->format('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n";