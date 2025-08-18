<?php
/**
 * CRITICAL DIAGNOSTIC SCRIPT: Dr. Yaya Negative Hours Investigation
 * 
 * Mission: Find the root cause of Dr. Yaya's -285.14694444444 total hours
 * Focus: Individual attendance record analysis and time calculation validation
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use Carbon\Carbon;

// Configuration
$drYayaUserId = 13;
$targetMonth = 8;   // August 2025
$targetYear = 2025;

echo "🚨 CRITICAL DEBUGGING: DR. YAYA NEGATIVE HOURS INVESTIGATION\n";
echo "============================================================\n";
echo "Target: User ID {$drYayaUserId} (Dr. Yaya)\n";
echo "Period: {$targetMonth}/{$targetYear}\n";
echo "Problem: Total hours showing -285.14694444444\n\n";

// Step 1: Get ALL attendance records for Dr. Yaya
echo "📊 STEP 1: RAW ATTENDANCE DATA ANALYSIS\n";
echo "=========================================\n";

$allAttendances = Attendance::where('user_id', $drYayaUserId)
    ->whereMonth('date', $targetMonth)
    ->whereYear('date', $targetYear)
    ->orderBy('date')
    ->get();

echo "Total attendance records found: " . $allAttendances->count() . "\n\n";

$completedRecords = [];
$incompleteRecords = [];
$problematicRecords = [];

foreach ($allAttendances as $index => $attendance) {
    $recordNumber = $index + 1;
    echo "Record #{$recordNumber} - ID: {$attendance->id}\n";
    echo "  Date: {$attendance->date}\n";
    echo "  Time In: " . ($attendance->time_in ?? 'NULL') . "\n";
    echo "  Time Out: " . ($attendance->time_out ?? 'NULL') . "\n";
    
    // Check for completed records
    if ($attendance->time_in && $attendance->time_out) {
        try {
            $timeIn = Carbon::parse($attendance->time_in);
            $timeOut = Carbon::parse($attendance->time_out);
            
            // Check for invalid timestamps
            if (!$timeIn->isValid() || !$timeOut->isValid()) {
                echo "  ❌ STATUS: INVALID TIMESTAMPS\n";
                $problematicRecords[] = [
                    'id' => $attendance->id,
                    'issue' => 'invalid_timestamps',
                    'time_in' => $attendance->time_in,
                    'time_out' => $attendance->time_out
                ];
            } else {
                // Calculate difference
                $hours = $timeOut->diffInHours($timeIn, false); // Allow negative
                $minutes = $timeOut->diffInMinutes($timeIn, false);
                $hoursFloat = $minutes / 60;
                
                echo "  Hours: {$hours} ({$hoursFloat} float)\n";
                echo "  Minutes: {$minutes}\n";
                
                // Check for anomalies
                if ($hours < 0) {
                    echo "  ⚠️ STATUS: NEGATIVE HOURS DETECTED\n";
                    $problematicRecords[] = [
                        'id' => $attendance->id,
                        'issue' => 'negative_hours',
                        'hours' => $hours,
                        'hours_float' => $hoursFloat,
                        'time_in' => $attendance->time_in,
                        'time_out' => $attendance->time_out,
                        'time_in_parsed' => $timeIn->format('Y-m-d H:i:s'),
                        'time_out_parsed' => $timeOut->format('Y-m-d H:i:s')
                    ];
                } else if ($hours > 24) {
                    echo "  ⚠️ STATUS: EXCESSIVE HOURS (>24h)\n";
                    $problematicRecords[] = [
                        'id' => $attendance->id,
                        'issue' => 'excessive_hours',
                        'hours' => $hours,
                        'hours_float' => $hoursFloat
                    ];
                } else {
                    echo "  ✅ STATUS: NORMAL\n";
                    $completedRecords[] = [
                        'id' => $attendance->id,
                        'hours' => $hours,
                        'hours_float' => $hoursFloat
                    ];
                }
            }
        } catch (\Exception $e) {
            echo "  ❌ STATUS: PARSING ERROR - " . $e->getMessage() . "\n";
            $problematicRecords[] = [
                'id' => $attendance->id,
                'issue' => 'parsing_error',
                'error' => $e->getMessage(),
                'time_in' => $attendance->time_in,
                'time_out' => $attendance->time_out
            ];
        }
    } else {
        echo "  ⏳ STATUS: INCOMPLETE (missing time_in or time_out)\n";
        $incompleteRecords[] = [
            'id' => $attendance->id,
            'missing' => !$attendance->time_in ? 'time_in' : 'time_out'
        ];
    }
    echo "\n";
}

// Step 2: Analyze the calculation issue
echo "🔍 STEP 2: CALCULATION ANALYSIS\n";
echo "================================\n";

echo "Completed records: " . count($completedRecords) . "\n";
echo "Incomplete records: " . count($incompleteRecords) . "\n";
echo "Problematic records: " . count($problematicRecords) . "\n\n";

// Calculate totals using different methods
$totalHoursInteger = array_sum(array_column($completedRecords, 'hours'));
$totalHoursFloat = array_sum(array_column($completedRecords, 'hours_float'));

echo "Total hours (integer method): {$totalHoursInteger}\n";
echo "Total hours (float method): {$totalHoursFloat}\n";

// Current calculation method from DokterDashboardController
$currentCalculationTotal = Attendance::where('user_id', $drYayaUserId)
    ->whereMonth('date', $targetMonth)
    ->whereYear('date', $targetYear)
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

echo "Current calculation method result: {$currentCalculationTotal}\n\n";

// Step 3: Detailed problematic records analysis
if (!empty($problematicRecords)) {
    echo "🚨 STEP 3: PROBLEMATIC RECORDS DETAILED ANALYSIS\n";
    echo "=================================================\n";
    
    foreach ($problematicRecords as $record) {
        echo "Problematic Record ID: {$record['id']}\n";
        echo "Issue Type: {$record['issue']}\n";
        
        switch ($record['issue']) {
            case 'negative_hours':
                echo "Time In: {$record['time_in']} → Parsed: {$record['time_in_parsed']}\n";
                echo "Time Out: {$record['time_out']} → Parsed: {$record['time_out_parsed']}\n";
                echo "Hours: {$record['hours']} (Float: {$record['hours_float']})\n";
                
                // Additional analysis for negative hours
                $timeInObj = Carbon::parse($record['time_in']);
                $timeOutObj = Carbon::parse($record['time_out']);
                
                echo "Time comparison:\n";
                echo "  Time In timestamp: " . $timeInObj->timestamp . "\n";
                echo "  Time Out timestamp: " . $timeOutObj->timestamp . "\n";
                echo "  Difference (seconds): " . ($timeOutObj->timestamp - $timeInObj->timestamp) . "\n";
                
                // Check if overnight shift
                if ($timeOutObj->lt($timeInObj)) {
                    echo "  🌙 OVERNIGHT SHIFT DETECTED: time_out < time_in\n";
                    $correctedTimeOut = $timeOutObj->copy()->addDay();
                    $correctedHours = $correctedTimeOut->diffInHours($timeInObj);
                    echo "  Corrected hours (with +1 day): {$correctedHours}\n";
                }
                break;
                
            case 'invalid_timestamps':
                echo "Invalid Time In: {$record['time_in']}\n";
                echo "Invalid Time Out: {$record['time_out']}\n";
                break;
                
            case 'parsing_error':
                echo "Error: {$record['error']}\n";
                echo "Time In: {$record['time_in']}\n";
                echo "Time Out: {$record['time_out']}\n";
                break;
        }
        echo "\n";
    }
}

// Step 4: Data integrity checks
echo "🔧 STEP 4: DATA INTEGRITY CHECKS\n";
echo "==================================\n";

// Check for duplicates
$dateGroups = $allAttendances->groupBy('date');
$duplicatesFound = false;

foreach ($dateGroups as $date => $attendancesForDate) {
    if ($attendancesForDate->count() > 1) {
        echo "⚠️ DUPLICATE ATTENDANCE FOUND for date: {$date}\n";
        echo "  Records: " . $attendancesForDate->pluck('id')->implode(', ') . "\n";
        $duplicatesFound = true;
    }
}

if (!$duplicatesFound) {
    echo "✅ No duplicate attendance records found\n";
}

// Check for timezone issues
echo "\nTimezone Analysis:\n";
$sampleRecord = $allAttendances->where('time_in', '!=', null)->where('time_out', '!=', null)->first();
if ($sampleRecord) {
    $timeIn = Carbon::parse($sampleRecord->time_in);
    $timeOut = Carbon::parse($sampleRecord->time_out);
    
    echo "Sample record timezone info:\n";
    echo "  Time In timezone: " . $timeIn->timezone->getName() . "\n";
    echo "  Time Out timezone: " . $timeOut->timezone->getName() . "\n";
    echo "  App timezone: " . config('app.timezone') . "\n";
}

// Step 5: Recommendations
echo "\n💡 STEP 5: DIAGNOSTIC RECOMMENDATIONS\n";
echo "======================================\n";

if (!empty($problematicRecords)) {
    echo "CRITICAL ISSUES FOUND:\n";
    
    $negativeHoursCount = count(array_filter($problematicRecords, fn($r) => $r['issue'] === 'negative_hours'));
    if ($negativeHoursCount > 0) {
        echo "- {$negativeHoursCount} records with negative hours\n";
        echo "  → Likely cause: time_out < time_in (overnight shifts not handled)\n";
        echo "  → Solution: Add overnight shift detection logic\n";
    }
    
    $invalidTimestampCount = count(array_filter($problematicRecords, fn($r) => $r['issue'] === 'invalid_timestamps'));
    if ($invalidTimestampCount > 0) {
        echo "- {$invalidTimestampCount} records with invalid timestamps\n";
        echo "  → Solution: Data cleanup required\n";
    }
    
    $parsingErrorCount = count(array_filter($problematicRecords, fn($r) => $r['issue'] === 'parsing_error'));
    if ($parsingErrorCount > 0) {
        echo "- {$parsingErrorCount} records with parsing errors\n";
        echo "  → Solution: Fix timestamp format issues\n";
    }
} else {
    echo "✅ NO CRITICAL ISSUES FOUND in attendance records\n";
    echo "The negative hours issue might be coming from:\n";
    echo "- Calculation logic elsewhere\n";
    echo "- Different data source\n";
    echo "- Cached values\n";
}

echo "\nIMMEDIATE ACTIONS NEEDED:\n";
if (!empty($problematicRecords)) {
    echo "1. Fix overnight shift handling in time calculation\n";
    echo "2. Clean up invalid timestamp data\n";
    echo "3. Add defensive programming to prevent negative calculations\n";
} else {
    echo "1. Check for cached data or different calculation methods\n";
    echo "2. Verify the source of the -285 hours calculation\n";
    echo "3. Add logging to track calculation sources\n";
}

echo "\n🎯 SUMMARY\n";
echo "===========\n";
echo "This diagnostic script analyzed Dr. Yaya's attendance data and found:\n";
echo "- Total records: " . $allAttendances->count() . "\n";
echo "- Completed: " . count($completedRecords) . "\n";
echo "- Problematic: " . count($problematicRecords) . "\n";
echo "- Current total hours: {$currentCalculationTotal}\n";

if ($currentCalculationTotal == -285.14694444444) {
    echo "🎯 ROOT CAUSE CONFIRMED: The calculation matches the reported negative value\n";
} else {
    echo "🤔 CALCULATION MISMATCH: Expected -285.14694444444, got {$currentCalculationTotal}\n";
}

echo "\nNext steps: Run data cleaning and implement overnight shift handling.\n";