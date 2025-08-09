<?php
/**
 * Fix Unclosed Attendance Records
 * Script untuk menutup presensi yang lupa check-out
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

echo "\n" . str_repeat("=", 60) . "\n";
echo "FIX UNCLOSED ATTENDANCE RECORDS\n";
echo str_repeat("=", 60) . "\n\n";

// Configuration
$USER_EMAIL = 'dd@rrr.com'; // dr. Rindang's email
$AUTO_FIX = true; // Set to true to auto-close old attendance

// Find user
$user = User::where('email', $USER_EMAIL)->first();

if (!$user) {
    echo "❌ User not found: $USER_EMAIL\n";
    exit(1);
}

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Email: {$user->email}\n\n";

// Find all unclosed attendance records
$unclosedAttendances = Attendance::where('user_id', $user->id)
    ->whereNotNull('time_in')
    ->whereNull('time_out')
    ->orderByDesc('date')
    ->orderByDesc('time_in')
    ->with('jadwalJaga.shiftTemplate')
    ->get();

if ($unclosedAttendances->isEmpty()) {
    echo "✅ No unclosed attendance records found.\n";
    exit(0);
}

echo "⚠️ Found " . $unclosedAttendances->count() . " unclosed attendance record(s):\n\n";

foreach ($unclosedAttendances as $attendance) {
    $date = Carbon::parse($attendance->date);
    $timeIn = Carbon::parse($attendance->time_in);
    $daysSince = $date->diffInDays(Carbon::today());
    
    echo str_repeat("-", 40) . "\n";
    echo "Date: " . $date->format('Y-m-d') . " (" . $date->format('l') . ")\n";
    echo "Days ago: $daysSince\n";
    echo "Check-in time: " . $timeIn->format('H:i:s') . "\n";
    echo "Status: " . ($attendance->status ?? 'N/A') . "\n";
    
    if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
        $shift = $attendance->jadwalJaga->shiftTemplate;
        echo "Shift: {$shift->nama_shift}\n";
        echo "Schedule: {$shift->jam_masuk} - {$shift->jam_pulang}\n";
        
        // Calculate expected check-out time
        // Extract time part from jam_pulang (might contain date prefix)
        $jamPulangStr = $shift->jam_pulang;
        if (strpos($jamPulangStr, ' ') !== false) {
            // If it contains date, extract time part only
            $parts = explode(' ', $jamPulangStr);
            $jamPulangStr = end($parts); // Get last part which should be time
        }
        
        $expectedCheckout = Carbon::parse($date->format('Y-m-d') . ' ' . $jamPulangStr);
        if ($expectedCheckout < $timeIn) {
            $expectedCheckout->addDay(); // Handle overnight shifts
        }
        echo "Expected check-out: " . $expectedCheckout->format('Y-m-d H:i:s') . "\n";
        
        if ($AUTO_FIX) {
            // Auto-close attendance (including today's if shift has ended)
            $attendance->time_out = $expectedCheckout;
            $attendance->latlon_out = $attendance->latlon_in; // Use same location
            
            // Calculate work duration (removed work_duration_minutes as column doesn't exist)
            
            // Update status if needed
            if (!$attendance->status) {
                $attendance->status = 'auto_closed';
            }
            
            $attendance->save();
            echo "✅ AUTO-CLOSED with check-out at " . $expectedCheckout->format('H:i:s') . "\n";
        }
    } else {
        echo "Shift: No shift template found\n";
        
        if ($AUTO_FIX) {
            // Auto-close with 8 hours after check-in as default
            $defaultCheckout = $timeIn->copy()->addHours(8);
            $attendance->time_out = $defaultCheckout;
            $attendance->latlon_out = $attendance->latlon_in;
            
            // Calculate work duration
            $workMinutes = $timeIn->diffInMinutes($defaultCheckout);
            $attendance->work_duration_minutes = $workMinutes;
            
            if (!$attendance->status) {
                $attendance->status = 'auto_closed';
            }
            
            $attendance->save();
            echo "✅ AUTO-CLOSED with default 8-hour duration\n";
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";

if (!$AUTO_FIX) {
    echo "⚠️ DRY RUN MODE - No changes made\n";
    echo "To auto-close old attendance records, set \$AUTO_FIX = true\n";
} else {
    echo "✅ Auto-close completed\n";
}

echo "\nRECOMMENDATIONS:\n";
echo "1. Review the unclosed records above\n";
echo "2. If legitimate, set \$AUTO_FIX = true and run again\n";
echo "3. Or manually close through admin panel\n";
echo "4. Consider implementing auto-close after shift ends\n";
echo "\n";