<?php

require_once 'vendor/autoload.php';

use App\Models\Attendance;
use App\Models\ShiftTemplate;
use App\Models\User;
use Carbon\Carbon;

// Initialize Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 DEBUGGING DURATION CALCULATION\n";
echo "==================================\n\n";

// Create test data
$shift = ShiftTemplate::create([
    'nama_shift' => 'Debug Shift',
    'jam_masuk' => '08:00:00',
    'jam_pulang' => '16:00:00',
    'break_duration_minutes' => 60,
    'is_break_flexible' => true,
]);

$user = User::first() ?? User::create([
    'name' => 'Debug User',
    'email' => 'debug@test.com',
    'password' => bcrypt('password'),
]);

$attendance = Attendance::create([
    'user_id' => $user->id,
    'date' => Carbon::today(),
    'time_in' => '07:45:00',
    'time_out' => '16:10:00',
    'status' => 'present',
    'shift_id' => $shift->id,
    'latlon_in' => '-7.898878,111.961884',
    'latlon_out' => '-7.898878,111.961884',
    'location_name_in' => 'Debug Location',
    'location_name_out' => 'Debug Location',
]);

echo "📊 STEP-BY-STEP DEBUG:\n";
echo "======================\n\n";

echo "1. Shift Template Info:\n";
echo "   ID: {$shift->id}\n";
echo "   Name: {$shift->nama_shift}\n";
echo "   Start: {$shift->jam_masuk}\n";
echo "   End: {$shift->jam_pulang}\n";
echo "   Break: {$shift->break_duration_minutes} minutes\n\n";

echo "2. Attendance Record:\n";
echo "   ID: {$attendance->id}\n";
echo "   Date: {$attendance->date}\n";
echo "   Check-in: {$attendance->time_in}\n";
echo "   Check-out: {$attendance->time_out}\n";
echo "   Shift ID: {$attendance->shift_id}\n\n";

echo "3. Relationship Check:\n";
$shiftTemplate = $attendance->getShiftTemplate();
if ($shiftTemplate) {
    echo "   ✅ Shift template found: {$shiftTemplate->nama_shift}\n";
} else {
    echo "   ❌ No shift template found!\n";
}

$boundaries = $attendance->getShiftBoundaries();
if ($boundaries) {
    echo "   ✅ Shift boundaries calculated:\n";
    echo "      Start: {$boundaries['shift_start']->format('Y-m-d H:i:s')}\n";
    echo "      End: {$boundaries['shift_end']->format('Y-m-d H:i:s')}\n";
} else {
    echo "   ❌ Could not calculate shift boundaries!\n";
}

echo "\n4. Time Parsing:\n";
$checkIn = $attendance->getParsedTimeIn();
$checkOut = $attendance->getParsedTimeOut();
echo "   Check-in parsed: " . ($checkIn ? $checkIn->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "   Check-out parsed: " . ($checkOut ? $checkOut->format('Y-m-d H:i:s') : 'NULL') . "\n";

echo "\n5. Effective Times:\n";
$effectiveStart = $attendance->effective_start_time;
$effectiveEnd = $attendance->effective_end_time;
echo "   Effective start: " . ($effectiveStart ? $effectiveStart->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "   Effective end: " . ($effectiveEnd ? $effectiveEnd->format('Y-m-d H:i:s') : 'NULL') . "\n";

echo "\n6. Duration Calculations:\n";
$enhanced = $attendance->getEnhancedWorkDurationAttribute();
$simple = $attendance->getSimpleWorkDurationAttribute();
$final = $attendance->work_duration;

echo "   Enhanced calculation: " . ($enhanced ?? 'NULL') . " minutes\n";
echo "   Simple calculation: " . ($simple ?? 'NULL') . " minutes\n";
echo "   Final work duration: " . ($final ?? 'NULL') . " minutes\n";

echo "\n7. Manual Calculation Check:\n";
if ($boundaries && $checkIn && $checkOut) {
    $manualEffectiveStart = $checkIn->greaterThan($boundaries['shift_start']) ? $checkIn : $boundaries['shift_start'];
    $manualEffectiveEnd = $checkOut->lessThan($boundaries['shift_end']) ? $checkOut : $boundaries['shift_end'];
    
    echo "   Manual effective start: {$manualEffectiveStart->format('H:i:s')}\n";
    echo "   Manual effective end: {$manualEffectiveEnd->format('H:i:s')}\n";
    
    $manualRawDuration = $manualEffectiveStart->diffInMinutes($manualEffectiveEnd);
    echo "   Manual raw duration: {$manualRawDuration} minutes\n";
    
    $breakDeduction = $shift->calculateBreakOverlapMinutes($manualEffectiveStart, $manualEffectiveEnd);
    echo "   Break deduction: {$breakDeduction} minutes\n";
    
    $manualFinalDuration = max(0, $manualRawDuration - $breakDeduction);
    echo "   Manual final duration: {$manualFinalDuration} minutes\n";
    
    // Expected: 08:00-16:00 = 8 hours = 480 minutes, minus 60 minutes break = 420 minutes
    echo "   Expected: 420 minutes (8h - 1h break)\n";
    echo "   Match: " . ($manualFinalDuration == 420 ? "✅ YES" : "❌ NO") . "\n";
}

// Cleanup
$attendance->delete();
$shift->delete();
if ($user->email === 'debug@test.com') {
    $user->delete();
}

echo "\n✅ Debug completed and cleaned up\n";