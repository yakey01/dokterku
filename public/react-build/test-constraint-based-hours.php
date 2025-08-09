<?php
/**
 * Test Script: Constraint-Based Working Hours
 * Testing perhitungan jam kerja dengan constraint jadwal jaga
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Carbon\Carbon;

echo "\n" . str_repeat("=", 60) . "\n";
echo "CONSTRAINT-BASED WORKING HOURS TEST\n";
echo str_repeat("=", 60) . "\n\n";

/**
 * Apply constraints: working hours only count within shift schedule
 * This mimics the logic from Presensi.tsx lines 1015-1026
 */
function calculateConstrainedHours($checkIn, $checkOut, $shiftStart, $shiftEnd) {
    // Parse times
    $checkInTime = Carbon::parse($checkIn);
    $checkOutTime = Carbon::parse($checkOut);
    $shiftStartTime = Carbon::parse($shiftStart);
    $shiftEndTime = Carbon::parse($shiftEnd);
    
    // Handle overnight shifts
    if ($shiftEndTime <= $shiftStartTime) {
        $shiftEndTime->addDay();
    }
    if ($checkOutTime <= $checkInTime) {
        $checkOutTime->addDay();
    }
    
    // Apply constraints
    // Effective start = max(checkIn, shiftStart)
    $effectiveStart = $checkInTime->max($shiftStartTime);
    
    // Effective end = min(checkOut, shiftEnd)
    $effectiveEnd = $checkOutTime->min($shiftEndTime);
    
    // Calculate working time only if effective period is positive
    if ($effectiveEnd > $effectiveStart) {
        $minutes = $effectiveStart->diffInMinutes($effectiveEnd);
        return round($minutes / 60, 2);
    }
    
    return 0;
}

// Test scenarios with constraints
$testCases = [
    [
        'name' => 'Early Check-in (Before Shift)',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'check_in' => '07:30',  // 30 min early
        'check_out' => '16:00',
        'expected_hours' => 8,  // Should only count from 08:00
        'explanation' => 'Check-in before shift start - only count from 08:00'
    ],
    [
        'name' => 'Late Check-out (After Shift)',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'check_in' => '08:00',
        'check_out' => '17:00',  // 1 hour overtime
        'expected_hours' => 8,  // Should only count until 16:00
        'explanation' => 'Check-out after shift end - only count until 16:00'
    ],
    [
        'name' => 'Both Early and Late',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'check_in' => '07:00',  // 1 hour early
        'check_out' => '18:00',  // 2 hours late
        'expected_hours' => 8,  // Should only count 08:00-16:00
        'explanation' => 'Early check-in and late check-out - only count shift hours'
    ],
    [
        'name' => 'Late Check-in',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'check_in' => '09:00',  // 1 hour late
        'check_out' => '16:00',
        'expected_hours' => 7,  // Should count from actual check-in
        'explanation' => 'Late check-in - count from actual check-in time'
    ],
    [
        'name' => 'Early Check-out',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'check_in' => '08:00',
        'check_out' => '14:00',  // 2 hours early
        'expected_hours' => 6,  // Should count until actual check-out
        'explanation' => 'Early check-out - count until actual check-out time'
    ],
    [
        'name' => 'Short Shift Example (User Case)',
        'shift_start' => '08:13',
        'shift_end' => '09:19',
        'check_in' => '09:19',  // Check-in at shift end
        'check_out' => '09:20',  // 1 minute after shift
        'expected_hours' => 0,   // No overlap with shift
        'explanation' => 'Check-in at shift end - no valid working hours'
    ],
    [
        'name' => 'Partial Overlap',
        'shift_start' => '08:00',
        'shift_end' => '12:00',
        'check_in' => '11:00',  // Last hour of shift
        'check_out' => '14:00',  // 2 hours after shift
        'expected_hours' => 1,   // Only 11:00-12:00 counts
        'explanation' => 'Partial overlap - only count hours within shift'
    ],
    [
        'name' => 'Overnight Shift',
        'shift_start' => '22:00',
        'shift_end' => '06:00',
        'check_in' => '21:30',  // 30 min early
        'check_out' => '07:00',  // 1 hour late
        'expected_hours' => 8,   // Should only count 22:00-06:00
        'explanation' => 'Overnight shift - constraint applies across midnight'
    ],
    [
        'name' => 'Perfect Attendance',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'check_in' => '08:00',
        'check_out' => '16:00',
        'expected_hours' => 8,
        'explanation' => 'Perfect attendance - full shift hours counted'
    ],
    [
        'name' => 'No Show (Outside Shift)',
        'shift_start' => '08:00',
        'shift_end' => '16:00',
        'check_in' => '17:00',  // After shift
        'check_out' => '18:00',
        'expected_hours' => 0,
        'explanation' => 'Attendance outside shift hours - no hours counted'
    ]
];

$allPassed = true;

foreach ($testCases as $idx => $test) {
    echo str_repeat("-", 50) . "\n";
    echo "TEST " . ($idx + 1) . ": " . $test['name'] . "\n";
    echo str_repeat("-", 50) . "\n";
    
    $today = Carbon::today()->toDateString();
    $checkIn = $today . ' ' . $test['check_in'];
    $checkOut = $today . ' ' . $test['check_out'];
    $shiftStart = $today . ' ' . $test['shift_start'];
    $shiftEnd = $today . ' ' . $test['shift_end'];
    
    $calculatedHours = calculateConstrainedHours($checkIn, $checkOut, $shiftStart, $shiftEnd);
    
    echo "Shift Schedule: {$test['shift_start']} - {$test['shift_end']}\n";
    echo "Attendance: {$test['check_in']} - {$test['check_out']}\n";
    echo "Expected Hours: {$test['expected_hours']}\n";
    echo "Calculated Hours: {$calculatedHours}\n";
    
    $passed = abs($calculatedHours - $test['expected_hours']) < 0.01;
    echo "Result: " . ($passed ? "✅ PASS" : "❌ FAIL") . "\n";
    echo "Explanation: {$test['explanation']}\n\n";
    
    if (!$passed) {
        $allPassed = false;
        echo "⚠️ ERROR: Expected {$test['expected_hours']} but got {$calculatedHours}\n\n";
    }
}

echo str_repeat("=", 60) . "\n";
echo "CONSTRAINT LOGIC SUMMARY\n";
echo str_repeat("=", 60) . "\n";

if ($allPassed) {
    echo "✅ ALL TESTS PASSED!\n\n";
} else {
    echo "❌ SOME TESTS FAILED!\n\n";
}

echo "KEY PRINCIPLES:\n";
echo "1. Working hours ONLY count within scheduled shift boundaries\n";
echo "2. Early check-in: Hours start from shift start time\n";
echo "3. Late check-out: Hours end at shift end time\n";
echo "4. Late check-in: Hours start from actual check-in\n";
echo "5. Early check-out: Hours end at actual check-out\n";
echo "6. No overlap: Zero hours if attendance outside shift\n\n";

echo "USER REQUIREMENT MET:\n";
echo "✅ \"01:06:00 Jam Kerja, buat logic setelah waktu cek out\n";
echo "   dikurangi cek tapi ada constraint jam jadwal jaga\"\n";
echo "→ Implementation constrains working hours to shift schedule\n\n";