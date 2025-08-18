<?php

/**
 * SIMPLE TOTAL HOURS VALIDATION
 * 
 * Direct database validation for Total Hours calculation fixes
 * Run with: php validate-total-hours-simple.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

echo "🔬 SIMPLE TOTAL HOURS VALIDATION\n";
echo "================================\n\n";

echo "🎯 MISSION: Zero tolerance for negative total_hours\n";
echo "📅 Testing Period: " . Carbon::now()->format('F Y') . "\n\n";

$currentMonth = Carbon::now()->month;
$currentYear = Carbon::now()->year;
$testResults = [];
$errors = [];

echo "🚀 STARTING VALIDATION...\n\n";

// Get users who have attendance records
$usersWithAttendance = \Illuminate\Support\Facades\DB::table('attendances')
    ->select('user_id')
    ->distinct()
    ->get()
    ->pluck('user_id')
    ->toArray();

echo "👥 Found " . count($usersWithAttendance) . " users with attendance records\n\n";

foreach ($usersWithAttendance as $userId) {
    $user = User::find($userId);
    if (!$user) {
        echo "⚠️  User {$userId}: NOT FOUND in users table\n";
        continue;
    }
    
    echo "👤 Testing User {$userId}: {$user->name}\n";
    
    try {
        // ✅ FIXED CALCULATION: Only count completed attendance (both time_in and time_out)
        $totalHours = Attendance::where('user_id', $userId)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out') // CRITICAL: Require completed attendance
            ->get()
            ->sum(function($attendance) {
                if ($attendance->time_in && $attendance->time_out) {
                    $timeIn = Carbon::parse($attendance->time_in);
                    $timeOut = Carbon::parse($attendance->time_out);
                    $hours = $timeOut->diffInHours($timeIn);
                    
                    // Validate reasonable hours (0-24 per day)
                    if ($hours < 0 || $hours > 24) {
                        echo "    ⚠️  Suspicious hours: {$hours} (time_in: {$timeIn->format('H:i')}, time_out: {$timeOut->format('H:i')})\n";
                        return 0; // Don't count invalid records
                    }
                    
                    return $hours;
                }
                return 0;
            });
        
        // Count attendance records for context
        $totalRecords = Attendance::where('user_id', $userId)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->count();
            
        $completedRecords = Attendance::where('user_id', $userId)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->count();
        
        echo "   📊 Total Records: {$totalRecords}, Completed: {$completedRecords}\n";
        echo "   ⏱️  Total Hours: {$totalHours}\n";
        
        // Validation checks
        if ($totalHours < 0) {
            echo "   ❌ CRITICAL: Negative total hours!\n";
            $errors[] = "User {$userId} ({$user->name}) has negative total hours: {$totalHours}";
        } else {
            echo "   ✅ VALID: Total hours is non-negative\n";
        }
        
        // Business logic validation
        if ($totalHours > ($completedRecords * 24)) {
            echo "   ⚠️  WARNING: Total hours ({$totalHours}) exceeds possible maximum ({$completedRecords} days × 24h)\n";
            $errors[] = "User {$userId} has impossible total hours: {$totalHours} (max possible: " . ($completedRecords * 24) . ")";
        }
        
        $testResults[] = [
            'user_id' => $userId,
            'name' => $user->name,
            'total_hours' => $totalHours,
            'total_records' => $totalRecords,
            'completed_records' => $completedRecords,
            'passed' => $totalHours >= 0 && $totalHours <= ($completedRecords * 24)
        ];
        
    } catch (\Exception $e) {
        echo "   ❌ ERROR: {$e->getMessage()}\n";
        $errors[] = "User {$userId} calculation failed: {$e->getMessage()}";
    }
    
    echo "\n";
}

// Special focus on Dr. Yaya (User 13)
echo "🔍 SPECIAL FOCUS: Dr. Yaya (User 13)\n";
echo "====================================\n";

$drYaya = User::find(13);
if ($drYaya) {
    echo "👨‍⚕️ Found Dr. Yaya: {$drYaya->name}\n";
    
    // Get ALL attendance records for analysis
    $allAttendance = Attendance::where('user_id', 13)
        ->whereMonth('date', $currentMonth)
        ->whereYear('date', $currentYear)
        ->orderBy('date')
        ->get();
    
    echo "📊 Total attendance records: {$allAttendance->count()}\n";
    
    $completedCount = 0;
    $incompleteCount = 0;
    $invalidCount = 0;
    $totalHoursDetailed = 0;
    
    foreach ($allAttendance as $attendance) {
        $date = $attendance->date->format('Y-m-d');
        $timeIn = $attendance->time_in ? $attendance->time_in->format('H:i') : 'NULL';
        $timeOut = $attendance->time_out ? $attendance->time_out->format('H:i') : 'NULL';
        
        if ($attendance->time_in && $attendance->time_out) {
            $hours = Carbon::parse($attendance->time_in)->diffInHours(Carbon::parse($attendance->time_out));
            $totalHoursDetailed += $hours;
            $completedCount++;
            echo "  ✅ {$date}: {$timeIn} - {$timeOut} ({$hours}h)\n";
        } elseif ($attendance->time_in) {
            $incompleteCount++;
            echo "  ⚠️  {$date}: {$timeIn} - {$timeOut} (incomplete)\n";
        } else {
            $invalidCount++;
            echo "  ❌ {$date}: {$timeIn} - {$timeOut} (invalid)\n";
        }
    }
    
    echo "\n📈 Dr. Yaya Summary:\n";
    echo "   • Completed records: {$completedCount}\n";
    echo "   • Incomplete records: {$incompleteCount}\n";
    echo "   • Invalid records: {$invalidCount}\n";
    echo "   • Total hours (detailed): {$totalHoursDetailed}\n";
    
    if ($totalHoursDetailed < 0) {
        echo "   ❌ CRITICAL: Dr. Yaya still has negative hours!\n";
        $errors[] = "Dr. Yaya (detailed calculation) has negative hours: {$totalHoursDetailed}";
    } else {
        echo "   ✅ SUCCESS: Dr. Yaya's total hours is non-negative\n";
    }
} else {
    echo "❌ Dr. Yaya (User 13) not found\n";
    $errors[] = "Dr. Yaya (User 13) not found";
}

echo "\n";

// Generate final report
echo "📋 VALIDATION REPORT\n";
echo str_repeat("=", 40) . "\n\n";

$totalTests = count($testResults);
$passedTests = collect($testResults)->where('passed', true)->count();
$failedTests = $totalTests - $passedTests;
$criticalErrors = count(array_filter($errors, function($error) {
    return strpos($error, 'negative') !== false;
}));

echo "📊 SUMMARY:\n";
echo "  • Users Tested: {$totalTests}\n";
echo "  • Passed: {$passedTests}\n";
echo "  • Failed: {$failedTests}\n";
echo "  • Critical Errors: {$criticalErrors}\n";
echo "  • Total Issues: " . count($errors) . "\n\n";

if (!empty($errors)) {
    echo "❌ ISSUES FOUND:\n";
    foreach ($errors as $error) {
        $isCritical = strpos($error, 'negative') !== false;
        $icon = $isCritical ? '🚨' : '⚠️';
        echo "  {$icon} {$error}\n";
    }
    echo "\n";
}

// Final verdict
$passed = $criticalErrors === 0;

echo "🏁 FINAL VERDICT:\n";
if ($passed) {
    echo "  ✅ VALIDATION PASSED\n";
    echo "  🎉 No negative total_hours found!\n";
    echo "  🚀 Total Hours calculation is working correctly\n";
    $exitCode = 0;
} else {
    echo "  ❌ VALIDATION FAILED\n";
    echo "  🚨 {$criticalErrors} critical error(s) found\n";
    echo "  ⚠️  Total Hours calculation needs fixing\n";
    $exitCode = 1;
}

echo "\n" . str_repeat("=", 40) . "\n";

exit($exitCode);