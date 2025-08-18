<?php

/**
 * QUICK TOTAL HOURS VALIDATION SCRIPT
 * 
 * Run immediately to validate the Total Hours calculation fixes
 * Usage: php validate-total-hours-quick.php
 */

echo "🔬 QUICK TOTAL HOURS VALIDATION\n";
echo "==============================\n\n";

// Configuration
$baseUrl = 'http://localhost:8000';
$testUserId = 26; // Dr. Yaya

echo "🎯 TESTING CONFIGURATION:\n";
echo "   Base URL: {$baseUrl}\n";
echo "   Test User: {$testUserId} (Dr. Yaya)\n";
echo "   Mission: Zero tolerance for negative total_hours\n\n";

// Test endpoints
$endpoints = [
    'Main Dashboard' => '/api/v2/dashboards/dokter',
    'Jadwal Jaga' => '/api/v2/dashboards/dokter/jadwal-jaga',
    'Presensi' => '/api/v2/dashboards/dokter/presensi',
    'Leaderboard' => '/api/v2/dashboards/dokter/leaderboard',
];

$results = [];
$errors = [];

echo "🚀 STARTING VALIDATION...\n\n";

foreach ($endpoints as $name => $endpoint) {
    echo "📡 Testing: {$name}\n";
    echo "   Endpoint: {$endpoint}\n";
    
    $url = $baseUrl . $endpoint . '?user_id=' . $testUserId;
    
    // Make request
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Accept: application/json',
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "   ❌ ERROR: Cannot access endpoint\n";
        $errors[] = "{$name}: Cannot access endpoint";
        echo "\n";
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        echo "   ❌ ERROR: Invalid JSON response\n";
        $errors[] = "{$name}: Invalid JSON response";
        echo "\n";
        continue;
    }
    
    // Extract total hours
    $totalHours = extractTotalHours($data);
    
    if ($totalHours === null) {
        echo "   ⚠️  WARNING: total_hours field not found\n";
        $results[$name] = 'NOT_FOUND';
    } elseif ($totalHours < 0) {
        echo "   ❌ CRITICAL: Negative total_hours = {$totalHours}\n";
        $results[$name] = $totalHours;
        $errors[] = "{$name}: Negative total_hours = {$totalHours}";
    } else {
        echo "   ✅ VALID: total_hours = {$totalHours}\n";
        $results[$name] = $totalHours;
    }
    
    echo "\n";
}

echo "📊 VALIDATION SUMMARY\n";
echo "=====================\n\n";

echo "📈 TOTAL HOURS BY ENDPOINT:\n";
foreach ($results as $endpoint => $hours) {
    $status = $hours === 'NOT_FOUND' ? '⚠️' : ($hours < 0 ? '❌' : '✅');
    echo "   {$status} {$endpoint}: {$hours}\n";
}
echo "\n";

// Check consistency
$validHours = array_filter($results, function($h) { return $h !== 'NOT_FOUND' && is_numeric($h); });
$uniqueHours = array_unique($validHours);

echo "🔄 CONSISTENCY CHECK:\n";
if (count($uniqueHours) <= 1) {
    echo "   ✅ All endpoints return consistent values\n";
} else {
    echo "   ❌ Inconsistent values across endpoints\n";
    $errors[] = "Inconsistent total_hours across endpoints";
}
echo "\n";

// Error summary
echo "🚨 ERROR SUMMARY:\n";
if (empty($errors)) {
    echo "   ✅ No errors found!\n";
} else {
    foreach ($errors as $error) {
        echo "   ❌ {$error}\n";
    }
}
echo "\n";

// Final verdict
echo "🏁 FINAL VERDICT:\n";
$criticalErrors = count(array_filter($errors, function($e) { return strpos($e, 'Negative') !== false; }));

if ($criticalErrors > 0) {
    echo "   ❌ VALIDATION FAILED\n";
    echo "   🚨 {$criticalErrors} critical error(s) found\n";
    echo "   ⚠️  DO NOT deploy to production\n";
    $exitCode = 1;
} elseif (count($errors) > 0) {
    echo "   ⚠️  VALIDATION PARTIAL\n";
    echo "   💛 Non-critical issues found\n";
    echo "   📝 Review issues before deployment\n";
    $exitCode = 2;
} else {
    echo "   ✅ VALIDATION PASSED\n";
    echo "   🎉 Total Hours fix is working correctly!\n";
    echo "   🚀 System is ready for production\n";
    $exitCode = 0;
}

echo "\n";

// Exit with appropriate code
exit($exitCode);

/**
 * Extract total hours from API response
 */
function extractTotalHours($data) {
    $paths = [
        'schedule_stats.total_hours',
        'presensi_stats.total_hours',
        'attendance_stats.total_hours',
        'stats.total_hours',
        'total_hours',
    ];
    
    foreach ($paths as $path) {
        $value = getNestedValue($data, $path);
        if ($value !== null && $value !== '') {
            return (float) $value;
        }
    }
    
    return null;
}

/**
 * Get nested value from array
 */
function getNestedValue($array, $path) {
    $keys = explode('.', $path);
    $current = $array;
    
    foreach ($keys as $key) {
        if (!is_array($current) || !isset($current[$key])) {
            return null;
        }
        $current = $current[$key];
    }
    
    return $current;
}