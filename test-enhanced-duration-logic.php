<?php

require_once 'vendor/autoload.php';

use App\Models\Attendance;
use App\Models\ShiftTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Initialize Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n🧪 TESTING ENHANCED WORK DURATION LOGIC\n";
echo "=========================================\n\n";

// Test Cases based on the provided examples
$testCases = [
    [
        'name' => 'Test 1: Shift 08:00-16:00, Break 60min, Check-in 07:45, Check-out 16:10',
        'shift_start' => '08:00:00',
        'shift_end' => '16:00:00',
        'break_minutes' => 60,
        'check_in' => '07:45:00',
        'check_out' => '16:10:00',
        'expected_hours' => 7, // 8 hours - 1 hour break = 7 hours
    ],
    [
        'name' => 'Test 2: Shift 08:00-16:00, Break 30min, Check-in 08:12, Check-out 15:40',
        'shift_start' => '08:00:00',
        'shift_end' => '16:00:00',
        'break_minutes' => 30,
        'check_in' => '08:12:00',
        'check_out' => '15:40:00',
        'expected_minutes' => 418, // 7h 28m - 30m = 6h 58m = 418 minutes
    ],
    [
        'name' => 'Test 3: Shift 08:00-16:00, No break, Early leave at 10:30',
        'shift_start' => '08:00:00',
        'shift_end' => '16:00:00',
        'break_minutes' => 0,
        'check_in' => '07:50:00',
        'check_out' => '10:30:00',
        'expected_minutes' => 150, // 2h 30m = 150 minutes
    ],
    [
        'name' => 'Test 4: Overnight Shift 22:00-06:00, Break 30min',
        'shift_start' => '22:00:00',
        'shift_end' => '06:00:00',
        'break_minutes' => 30,
        'check_in' => '21:55:00',
        'check_out' => '06:10:00',
        'expected_minutes' => 450, // 8h - 30m = 7h 30m = 450 minutes
    ],
];

// Create or get test shift templates
function getOrCreateShiftTemplate($name, $start, $end, $breakMinutes) {
    $shift = ShiftTemplate::where('nama_shift', $name)->first();
    
    if (!$shift) {
        $shift = ShiftTemplate::create([
            'nama_shift' => $name,
            'jam_masuk' => $start,
            'jam_pulang' => $end,
            'break_duration_minutes' => $breakMinutes,
            'is_break_flexible' => true,
        ]);
        echo "📋 Created shift template: {$name}\n";
    } else {
        $shift->update([
            'break_duration_minutes' => $breakMinutes,
            'is_break_flexible' => true,
        ]);
        echo "📋 Updated shift template: {$name}\n";
    }
    
    return $shift;
}

// Get or create test user
$testUser = User::where('email', 'test@enhanced-duration.com')->first();
if (!$testUser) {
    $testUser = User::create([
        'name' => 'Enhanced Duration Test User',
        'email' => 'test@enhanced-duration.com',
        'password' => bcrypt('password'),
    ]);
    echo "👤 Created test user\n";
}

echo "\n🔧 SETTING UP TEST DATA...\n";

foreach ($testCases as $index => $testCase) {
    $testNum = $index + 1;
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🧪 {$testCase['name']}\n";
    echo str_repeat("=", 50) . "\n";
    
    // Create shift template for this test
    $shiftName = "Test Shift {$testNum}";
    $shift = getOrCreateShiftTemplate(
        $shiftName,
        $testCase['shift_start'],
        $testCase['shift_end'],
        $testCase['break_minutes']
    );
    
    // Create test attendance record
    $today = Carbon::today()->format('Y-m-d');
    
    // Delete existing test record
    Attendance::where('user_id', $testUser->id)
        ->where('date', $today)
        ->where('notes', "Test Case {$testNum}")
        ->delete();
    
    $attendance = Attendance::create([
        'user_id' => $testUser->id,
        'date' => $today,
        'time_in' => $testCase['check_in'],
        'time_out' => $testCase['check_out'],
        'status' => 'present',
        'notes' => "Test Case {$testNum}",
        'shift_id' => $shift->id,
        'latlon_in' => '-7.898878,111.961884',
        'latlon_out' => '-7.898878,111.961884',
        'location_name_in' => 'Test Location',
        'location_name_out' => 'Test Location',
    ]);
    
    echo "✅ Created test attendance record\n";
    
    // Get calculation results
    $workDuration = $attendance->work_duration;
    $enhancedDuration = $attendance->getEnhancedWorkDurationAttribute();
    $simpleDuration = $attendance->getSimpleWorkDurationAttribute();
    $breakdown = $attendance->work_duration_breakdown;
    
    // Display results
    echo "\n📊 CALCULATION RESULTS:\n";
    echo "------------------------\n";
    
    echo "Shift: {$shift->nama_shift} ({$shift->jam_masuk_format} - {$shift->jam_pulang_format})\n";
    echo "Break Time: {$shift->break_duration_minutes} minutes\n";
    echo "Check-in: {$testCase['check_in']} → Check-out: {$testCase['check_out']}\n\n";
    
    echo "🔹 Effective Times:\n";
    echo "   Start: {$breakdown['effective_start']} (max of check-in and shift start)\n";
    echo "   End: {$breakdown['effective_end']} (min of check-out and shift end)\n\n";
    
    echo "🔹 Duration Calculation:\n";
    echo "   Raw Duration: {$breakdown['raw_duration_minutes']} minutes\n";
    echo "   Break Deduction: {$breakdown['break_deduction_minutes']} minutes\n";
    echo "   Final Work Duration: {$workDuration} minutes\n";
    echo "   Formatted: {$attendance->formatted_work_duration}\n\n";
    
    echo "🔹 Calculation Methods:\n";
    echo "   Enhanced Logic: " . ($enhancedDuration ?? 'NULL') . " minutes\n";
    echo "   Simple Logic: " . ($simpleDuration ?? 'NULL') . " minutes\n";
    echo "   Final Result: {$workDuration} minutes\n\n";
    
    echo "🔹 Attendance Percentage: {$breakdown['attendance_percentage']}%\n\n";
    
    // Validate against expected result
    $expected = $testCase['expected_hours'] ?? null;
    if ($expected) {
        $expectedMinutes = $expected * 60;
    } else {
        $expectedMinutes = $testCase['expected_minutes'];
    }
    
    echo "🎯 VALIDATION:\n";
    echo "   Expected: {$expectedMinutes} minutes\n";
    echo "   Actual: {$workDuration} minutes\n";
    
    if ($workDuration == $expectedMinutes) {
        echo "   ✅ PASSED - Calculation is correct!\n";
    } else {
        echo "   ❌ FAILED - Expected {$expectedMinutes}, got {$workDuration}\n";
        echo "   Difference: " . abs($workDuration - $expectedMinutes) . " minutes\n";
    }
}

echo "\n\n🔍 EDGE CASE TESTING:\n";
echo "=====================\n";

// Test edge case: No shift template
echo "\n📝 Testing: No shift template (fallback to simple calculation)\n";
$noShiftAttendance = Attendance::create([
    'user_id' => $testUser->id,
    'date' => Carbon::today()->format('Y-m-d'),
    'time_in' => '09:00:00',
    'time_out' => '17:00:00',
    'status' => 'present',
    'notes' => 'No shift test',
    'latlon_in' => '-7.898878,111.961884',
    'latlon_out' => '-7.898878,111.961884',
    'location_name_in' => 'Test Location',
    'location_name_out' => 'Test Location',
]);

echo "No shift - Work Duration: " . ($noShiftAttendance->work_duration ?? 'NULL') . " minutes\n";
echo "Expected: 480 minutes (simple calculation)\n";
echo ($noShiftAttendance->work_duration == 480 ? "✅ PASSED" : "❌ FAILED") . "\n";

// Test edge case: Only check-in (no check-out)
echo "\n📝 Testing: Missing check-out time\n";
$incompleteAttendance = Attendance::create([
    'user_id' => $testUser->id,
    'date' => Carbon::today()->format('Y-m-d'),
    'time_in' => '08:00:00',
    'time_out' => null,
    'status' => 'present',
    'notes' => 'Incomplete test',
    'latlon_in' => '-7.898878,111.961884',
    'location_name_in' => 'Test Location',
]);

echo "Incomplete - Work Duration: " . ($incompleteAttendance->work_duration ?? 'NULL') . "\n";
echo "Expected: NULL (no duration without check-out)\n";
echo (is_null($incompleteAttendance->work_duration) ? "✅ PASSED" : "❌ FAILED") . "\n";

echo "\n\n🎉 ENHANCED DURATION LOGIC TESTING COMPLETED!\n";
echo "===============================================\n";

// Cleanup
echo "\n🧹 Cleaning up test data...\n";
Attendance::where('user_id', $testUser->id)->delete();
ShiftTemplate::where('nama_shift', 'LIKE', 'Test Shift %')->delete();
$testUser->delete();
echo "✅ Cleanup completed\n";

echo "\n💡 SUMMARY:\n";
echo "The enhanced work duration logic implements the 5-step algorithm:\n";
echo "1. Get shift_start and shift_end from shift template\n";
echo "2. Get actual check_in and check_out times\n";
echo "3. Calculate effective_start = max(check_in, shift_start)\n";
echo "4. Calculate effective_end = min(check_out, shift_end)\n";
echo "5. Apply break time deductions: final = raw_duration - break_overlap\n";
echo "\nThis ensures fair and accurate work duration calculations! 🚀\n";