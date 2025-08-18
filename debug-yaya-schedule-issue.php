<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Carbon\Carbon;

// Mock test
echo "=== YAYA SCHEDULE DETECTION DEBUG ===\n\n";

// Test scenario: User "yaya" logging in at 04:40 with schedule 7-11
$testTime = Carbon::createFromFormat('H:i', '04:40');
$scheduleStart = Carbon::createFromFormat('H:i', '07:00');  
$scheduleEnd = Carbon::createFromFormat('H:i', '11:00');

echo "Test Scenario:\n";
echo "- Current Time: {$testTime->format('H:i')} (04:40)\n";
echo "- Schedule: {$scheduleStart->format('H:i')} - {$scheduleEnd->format('H:i')} (7-11)\n\n";

// Test tolerance settings
$toleranceEarly = 30; // Default tolerance
$toleranceLate = 15;  // Default tolerance

echo "Tolerance Settings:\n";
echo "- Early check-in tolerance: {$toleranceEarly} minutes\n";
echo "- Late check-in tolerance: {$toleranceLate} minutes\n\n";

// Calculate window
$windowStart = $scheduleStart->copy()->subMinutes($toleranceEarly);
$windowEnd = $scheduleStart->copy()->addMinutes($toleranceLate);

echo "Check-in Window:\n";
echo "- Window Start: {$windowStart->format('H:i')} (schedule start - {$toleranceEarly}min)\n";
echo "- Window End: {$windowEnd->format('H:i')} (schedule start + {$toleranceLate}min)\n\n";

// Test current logic
$currentTime = $testTime;

echo "ANALYSIS:\n";
echo "Current time ({$currentTime->format('H:i')}) vs Window ({$windowStart->format('H:i')} - {$windowEnd->format('H:i')}):\n";

if ($currentTime->between($windowStart, $windowEnd)) {
    echo "✅ WITHIN WINDOW - Can check in\n";
} elseif ($currentTime->lessThan($windowStart)) {
    $minutesEarly = $currentTime->diffInMinutes($windowStart);
    echo "❌ TOO EARLY - Need to wait {$minutesEarly} minutes\n";
    echo "   Should show: 'Check-in mulai pukul {$windowStart->format('H:i')}'\n";
} else {
    $minutesLate = $windowEnd->diffInMinutes($currentTime);
    echo "❌ TOO LATE - Missed window by {$minutesLate} minutes\n";
}

echo "\n=== ROOT CAUSE ANALYSIS ===\n\n";

echo "ISSUE IDENTIFIED:\n";
echo "1. Current time: 04:40\n";
echo "2. Window starts: 06:30 (07:00 - 30min tolerance)\n";
echo "3. 04:40 < 06:30 = TOO EARLY\n";
echo "4. Minutes to wait: " . $testTime->diffInMinutes($windowStart) . " minutes\n\n";

echo "INCORRECT LOGIC:\n";
echo "The system shows 'No schedule for today' instead of 'Not time to check in yet'\n";
echo "This happens in line 826 of AttendanceController:\n";
echo "\n";
echo "if (!canCheckIn && todaySchedules->isEmpty()) {\n";
echo "    message = 'Tidak ada jadwal untuk hari ini';\n";
echo "}\n\n";

echo "PROBLEM:\n";
echo "- The condition checks if todaySchedules is empty\n";
echo "- But schedules EXIST, they're just not in check-in window yet\n";
echo "- So it should check the time window instead\n\n";

echo "CORRECT LOGIC SHOULD BE:\n";
echo "if (!canCheckIn) {\n";
echo "    if (todaySchedules->isEmpty()) {\n";
echo "        message = 'Tidak ada jadwal untuk hari ini';\n";
echo "    } else {\n";
echo "        // Find next available shift and show when check-in opens\n";
echo "        message = 'Check-in untuk shift X mulai pukul Y';\n";
echo "    }\n";
echo "}\n\n";

echo "=== SOLUTION RECOMMENDATIONS ===\n\n";

echo "1. FIX THE MESSAGE LOGIC:\n";
echo "   - Check if schedules exist but are outside window\n";
echo "   - Show appropriate 'not time yet' message\n\n";

echo "2. VERIFY TOLERANCE SETTINGS:\n";
echo "   - Check admin tolerance configuration\n";
echo "   - Ensure yaya's user has correct tolerance settings\n\n";

echo "3. IMPROVE TIME WINDOW LOGIC:\n";
echo "   - Better handling of early check-in scenarios\n";
echo "   - Clear messaging for different time windows\n\n";

echo "=== SPECIFIC CODE CHANGES NEEDED ===\n\n";

echo "File: app/Http/Controllers/Api/V2/Attendance/AttendanceController.php\n";
echo "Line: 825-827\n\n";

echo "BEFORE:\n";
echo "if (!canCheckIn && todaySchedules->isEmpty()) {\n";
echo "    message = 'Tidak ada jadwal untuk hari ini';\n";
echo "}\n\n";

echo "AFTER:\n";
echo "if (!canCheckIn) {\n";
echo "    if (todaySchedules->isEmpty()) {\n";
echo "        message = 'Tidak ada jadwal untuk hari ini';\n";
echo "    } else {\n";
echo "        // Find earliest shift and show when check-in opens\n";
echo "        \$earliestShift = todaySchedules->first();\n";
echo "        \$shift = \$earliestShift->shiftTemplate;\n";
echo "        \$shiftStart = Carbon::parse(\$shift->jam_masuk);\n";
echo "        \$windowStart = \$shiftStart->copy()->subMinutes(\$toleranceEarly);\n";
echo "        message = 'Check-in untuk shift ' . \$shift->nama_shift . ' mulai pukul ' . \$windowStart->format('H:i');\n";
echo "    }\n";
echo "}\n\n";

echo "=== TEST EXPECTED RESULTS ===\n\n";
echo "With fix applied:\n";
echo "- Time: 04:40, Schedule: 7-11\n";
echo "- Expected message: 'Check-in untuk shift [nama] mulai pukul 06:30'\n";
echo "- Instead of: 'Tidak ada jadwal untuk hari ini'\n\n";

echo "DEBUG COMPLETE ✅\n";