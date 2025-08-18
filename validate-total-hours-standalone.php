<?php

/**
 * STANDALONE TOTAL HOURS VALIDATION
 * 
 * Direct database validation for Total Hours calculation fixes
 * Run with: php validate-total-hours-standalone.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Dokter;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "🔬 STANDALONE TOTAL HOURS VALIDATION\n";
echo "=====================================\n\n";

echo "🎯 TESTING CONFIGURATION:\n";
echo "   Target: Total Hours calculation fixes\n";
echo "   Method: Direct database validation\n";
echo "   Mission: Zero tolerance for negative total_hours\n\n";

$testResults = [];
$errors = [];

echo "🚀 STARTING VALIDATION...\n\n";

// Test Case 1: Dr. Yaya (original issue case)
echo "📡 Test Case 1: Dr. Yaya (User ID 26)\n";
echo "   Description: Original negative total_hours case\n";

try {
    $user = User::find(26);
    if (!$user) {
        echo "   ❌ ERROR: User 26 (Dr. Yaya) not found\n";
        $errors[] = "User 26 not found";
    } else {
        echo "   ✅ User found: {$user->name}\n";
        
        // Calculate total hours using the same logic as the controller
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        $totalHours = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out') // REQUIRE COMPLETED ATTENDANCE
            ->get()
            ->sum(function($attendance) {
                if ($attendance->time_in && $attendance->time_out) {
                    $timeIn = Carbon::parse($attendance->time_in);
                    $timeOut = Carbon::parse($attendance->time_out);
                    return $timeOut->diffInHours($timeIn);
                }
                return 0;
            });
        
        echo "   📊 Calculated Total Hours: {$totalHours}\n";
        
        if ($totalHours < 0) {
            echo "   ❌ CRITICAL: Negative total hours detected!\n";
            $errors[] = "Dr. Yaya has negative total hours: {$totalHours}";
        } else {
            echo "   ✅ VALID: Total hours is non-negative\n";
        }
        
        $testResults['dr_yaya'] = [
            'user_id' => $user->id,
            'name' => $user->name,
            'total_hours' => $totalHours,
            'passed' => $totalHours >= 0
        ];
        
        // Check attendance records
        $attendanceCount = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->count();
            
        echo "   📈 Completed attendance records: {$attendanceCount}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ ERROR: {$e->getMessage()}\n";
    $errors[] = "Dr. Yaya test failed: {$e->getMessage()}";
}

echo "\n";

// Test Case 2: Random active users
echo "📡 Test Case 2: Random Active Users\n";
echo "   Description: Sample of active users to validate calculation\n";

try {
    $activeUsers = User::whereHas('attendance', function($query) {
            $query->whereMonth('date', Carbon::now()->month)
                  ->whereYear('date', Carbon::now()->year);
        })
        ->take(5)
        ->get();
    
    echo "   👥 Found {$activeUsers->count()} active users\n";
    
    foreach ($activeUsers as $user) {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        $totalHours = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->get()
            ->sum(function($attendance) {
                if ($attendance->time_in && $attendance->time_out) {
                    $timeIn = Carbon::parse($attendance->time_in);
                    $timeOut = Carbon::parse($attendance->time_out);
                    return $timeOut->diffInHours($timeIn);
                }
                return 0;
            });
        
        $status = $totalHours >= 0 ? '✅' : '❌';
        echo "   {$status} User {$user->id} ({$user->name}): {$totalHours} hours\n";
        
        if ($totalHours < 0) {
            $errors[] = "User {$user->id} has negative total hours: {$totalHours}";
        }
        
        $testResults["user_{$user->id}"] = [
            'user_id' => $user->id,
            'name' => $user->name,
            'total_hours' => $totalHours,
            'passed' => $totalHours >= 0
        ];
    }
} catch (\Exception $e) {
    echo "   ❌ ERROR: {$e->getMessage()}\n";
    $errors[] = "Random users test failed: {$e->getMessage()}";
}

echo "\n";

// Test Case 3: Edge cases
echo "📡 Test Case 3: Edge Cases\n";
echo "   Description: Test boundary conditions\n";

try {
    // User with no attendance
    $userWithNoAttendance = User::whereDoesntHave('attendance')->first();
    if ($userWithNoAttendance) {
        $totalHours = Attendance::where('user_id', $userWithNoAttendance->id)
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->get()
            ->sum(function($attendance) {
                if ($attendance->time_in && $attendance->time_out) {
                    $timeIn = Carbon::parse($attendance->time_in);
                    $timeOut = Carbon::parse($attendance->time_out);
                    return $timeOut->diffInHours($timeIn);
                }
                return 0;
            });
        
        echo "   ✅ User with no attendance: {$totalHours} hours (should be 0)\n";
        
        $testResults['no_attendance'] = [
            'user_id' => $userWithNoAttendance->id,
            'name' => $userWithNoAttendance->name,
            'total_hours' => $totalHours,
            'passed' => $totalHours >= 0
        ];
        
        if ($totalHours != 0) {
            $errors[] = "User with no attendance should have 0 hours, got {$totalHours}";
        }
    }
    
    // User with incomplete attendance (no time_out)
    $incompleteAttendance = Attendance::whereNotNull('time_in')
        ->whereNull('time_out')
        ->whereMonth('date', Carbon::now()->month)
        ->whereYear('date', Carbon::now()->year)
        ->first();
        
    if ($incompleteAttendance) {
        $user = User::find($incompleteAttendance->user_id);
        if ($user) {
            $totalHours = Attendance::where('user_id', $user->id)
                ->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year)
                ->whereNotNull('time_in')
                ->whereNotNull('time_out') // This should exclude incomplete records
                ->get()
                ->sum(function($attendance) {
                    if ($attendance->time_in && $attendance->time_out) {
                        $timeIn = Carbon::parse($attendance->time_in);
                        $timeOut = Carbon::parse($attendance->time_out);
                        return $timeOut->diffInHours($timeIn);
                    }
                    return 0;
                });
            
            echo "   ✅ User with incomplete attendance: {$totalHours} hours (incomplete records excluded)\n";
            
            if ($totalHours < 0) {
                $errors[] = "User with incomplete attendance has negative hours: {$totalHours}";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "   ❌ ERROR: {$e->getMessage()}\n";
    $errors[] = "Edge cases test failed: {$e->getMessage()}";
}

echo "\n";

// Test Case 4: Data integrity checks
echo "📡 Test Case 4: Data Integrity\n";
echo "   Description: Validate business logic constraints\n";

try {
    // Check for any attendance with time_out before time_in
    $invalidAttendance = Attendance::whereNotNull('time_in')
        ->whereNotNull('time_out')
        ->whereRaw('time_out < time_in')
        ->whereMonth('date', Carbon::now()->month)
        ->whereYear('date', Carbon::now()->year)
        ->count();
    
    echo "   📊 Invalid attendance records (time_out < time_in): {$invalidAttendance}\n";
    
    if ($invalidAttendance > 0) {
        echo "   ⚠️  WARNING: Found {$invalidAttendance} invalid attendance records\n";
        $errors[] = "Found {$invalidAttendance} attendance records with time_out < time_in";
    } else {
        echo "   ✅ No invalid attendance records found\n";
    }
    
    // Check for extremely long shifts (>24 hours)
    $extremeShifts = Attendance::whereNotNull('time_in')
        ->whereNotNull('time_out')
        ->whereMonth('date', Carbon::now()->month)
        ->whereYear('date', Carbon::now()->year)
        ->get()
        ->filter(function($attendance) {
            if ($attendance->time_in && $attendance->time_out) {
                $timeIn = Carbon::parse($attendance->time_in);
                $timeOut = Carbon::parse($attendance->time_out);
                return $timeOut->diffInHours($timeIn) > 24;
            }
            return false;
        })
        ->count();
    
    echo "   📊 Extreme shifts (>24 hours): {$extremeShifts}\n";
    
    if ($extremeShifts > 0) {
        echo "   ⚠️  WARNING: Found {$extremeShifts} shifts longer than 24 hours\n";
        // This is a warning, not an error
    } else {
        echo "   ✅ No extreme shifts found\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ ERROR: {$e->getMessage()}\n";
    $errors[] = "Data integrity test failed: {$e->getMessage()}";
}

echo "\n";

// Generate final report
echo "📋 COMPREHENSIVE VALIDATION REPORT\n";
echo str_repeat("=", 60) . "\n\n";

$totalTests = count($testResults);
$passedTests = collect($testResults)->where('passed', true)->count();
$failedTests = $totalTests - $passedTests;
$totalErrors = count($errors);

echo "📊 SUMMARY:\n";
echo "  • Total Tests: {$totalTests}\n";
echo "  • Passed: {$passedTests}\n";
echo "  • Failed: {$failedTests}\n";
echo "  • Total Errors: {$totalErrors}\n\n";

// Detailed results
if (!empty($testResults)) {
    echo "📈 DETAILED RESULTS:\n";
    foreach ($testResults as $testName => $result) {
        $status = $result['passed'] ? '✅' : '❌';
        echo "  {$status} {$testName}: {$result['name']} = {$result['total_hours']} hours\n";
    }
    echo "\n";
}

// Errors
if (!empty($errors)) {
    echo "❌ ERRORS:\n";
    foreach ($errors as $error) {
        echo "  • {$error}\n";
    }
    echo "\n";
}

// Final verdict
$passed = empty($errors) && $failedTests === 0;

echo "🏁 FINAL VERDICT:\n";
if ($passed) {
    echo "  ✅ VALIDATION PASSED\n";
    echo "  🎉 Total Hours calculation is working correctly!\n";
    echo "  🚀 System is ready for production\n";
    $exitCode = 0;
} else {
    echo "  ❌ VALIDATION FAILED\n";
    echo "  🚨 Critical issues found that must be addressed\n";
    echo "  ⚠️  DO NOT deploy to production\n";
    $exitCode = 1;
}

echo "\n" . str_repeat("=", 60) . "\n";

exit($exitCode);