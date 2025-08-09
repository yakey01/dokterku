<?php
/**
 * Fix ALL Unclosed Attendance Records
 * Script untuk menutup semua presensi yang lupa check-out
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
echo "FIX ALL UNCLOSED ATTENDANCE RECORDS\n";
echo str_repeat("=", 60) . "\n\n";

// Configuration
$AUTO_FIX = true; // Set to true to auto-close old attendance
$DAYS_TO_CHECK = 30; // Check last 30 days
$AUTO_CLOSE_AFTER_DAYS = 1; // Auto-close if older than 1 day

// Find all unclosed attendance records
$unclosedAttendances = Attendance::whereNotNull('time_in')
    ->whereNull('time_out')
    ->whereDate('date', '>=', Carbon::now()->subDays($DAYS_TO_CHECK)->startOfDay())
    ->orderByDesc('date')
    ->orderByDesc('time_in')
    ->with(['user', 'jadwalJaga.shiftTemplate'])
    ->get();

if ($unclosedAttendances->isEmpty()) {
    echo "✅ No unclosed attendance records found in the last $DAYS_TO_CHECK days.\n";
    exit(0);
}

echo "⚠️ Found " . $unclosedAttendances->count() . " unclosed attendance record(s):\n\n";

// Group by user
$byUser = $unclosedAttendances->groupBy('user_id');
$totalFixed = 0;

foreach ($byUser as $userId => $userAttendances) {
    $user = $userAttendances->first()->user;
    
    echo str_repeat("=", 50) . "\n";
    echo "USER: {$user->name} (ID: {$user->id})\n";
    echo "Email: {$user->email}\n";
    echo "Role: " . ($user->role ? $user->role->name : 'N/A') . "\n";
    echo "Unclosed Records: " . $userAttendances->count() . "\n";
    echo str_repeat("-", 50) . "\n";
    
    foreach ($userAttendances as $attendance) {
        $date = Carbon::parse($attendance->date);
        $timeIn = Carbon::parse($attendance->time_in);
        $daysSince = $date->diffInDays(Carbon::today());
        $isToday = $date->isToday();
        $isYesterday = $date->isYesterday();
        
        echo "\n📅 Date: " . $date->format('Y-m-d') . " (" . $date->format('l') . ")";
        
        if ($isToday) {
            echo " [TODAY]";
        } elseif ($isYesterday) {
            echo " [YESTERDAY]";
        } else {
            echo " [{$daysSince} days ago]";
        }
        echo "\n";
        
        echo "   Check-in: " . $timeIn->format('H:i:s') . "\n";
        echo "   Status: " . ($attendance->status ?? 'N/A') . "\n";
        
        $shouldAutoClose = $daysSince >= $AUTO_CLOSE_AFTER_DAYS;
        
        if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
            $shift = $attendance->jadwalJaga->shiftTemplate;
            echo "   Shift: {$shift->nama_shift} ({$shift->jam_masuk} - {$shift->jam_pulang})\n";
            
            // Calculate expected check-out time
            // Extract time only from jam_pulang and jam_masuk (might contain date prefix)
            $jamPulangStr = $shift->jam_pulang;
            if (strpos($jamPulangStr, ' ') !== false) {
                // If it contains date, extract time part only
                $parts = explode(' ', $jamPulangStr);
                $jamPulangStr = end($parts); // Get last part which should be time
            }
            
            $jamMasukStr = $shift->jam_masuk;
            if (strpos($jamMasukStr, ' ') !== false) {
                $parts = explode(' ', $jamMasukStr);
                $jamMasukStr = end($parts);
            }
            
            $expectedCheckout = Carbon::parse($date->format('Y-m-d') . ' ' . $jamPulangStr);
            if ($expectedCheckout < $timeIn) {
                $expectedCheckout->addDay(); // Handle overnight shifts
            }
            
            $hoursWorked = $timeIn->diffInHours($expectedCheckout);
            echo "   Expected check-out: " . $expectedCheckout->format('H:i:s') . " (~{$hoursWorked} hours)\n";
            
            if ($AUTO_FIX && $shouldAutoClose) {
                // Auto-close with expected check-out time
                $attendance->time_out = $expectedCheckout;
                $attendance->latlon_out = $attendance->latlon_in; // Use same location
                
                // Update location out if coordinates available
                if ($attendance->latitude && $attendance->longitude) {
                    $attendance->checkout_latitude = $attendance->latitude;
                    $attendance->checkout_longitude = $attendance->longitude;
                    $attendance->checkout_accuracy = $attendance->accuracy;
                }
                
                // Update status
                $attendance->status = 'auto_closed';
                
                $attendance->save();
                echo "   ✅ AUTO-CLOSED at " . $expectedCheckout->format('H:i:s') . "\n";
                $totalFixed++;
            } elseif ($shouldAutoClose) {
                echo "   ⚠️ Should be closed (older than $AUTO_CLOSE_AFTER_DAYS day)\n";
            } else {
                echo "   ℹ️ Recent attendance - user should close manually\n";
            }
        } else {
            echo "   Shift: No shift template found\n";
            
            if ($AUTO_FIX && $shouldAutoClose) {
                // Auto-close with 8 hours after check-in as default
                $defaultCheckout = $timeIn->copy()->addHours(8);
                
                // Don't exceed current time
                if ($defaultCheckout->isFuture()) {
                    $defaultCheckout = Carbon::now();
                }
                
                $attendance->time_out = $defaultCheckout;
                $attendance->latlon_out = $attendance->latlon_in;
                
                // Update location out if coordinates available
                if ($attendance->latitude && $attendance->longitude) {
                    $attendance->checkout_latitude = $attendance->latitude;
                    $attendance->checkout_longitude = $attendance->longitude;
                    $attendance->checkout_accuracy = $attendance->accuracy;
                }
                
                $attendance->status = 'auto_closed';
                
                $attendance->save();
                echo "   ✅ AUTO-CLOSED with 8-hour default at " . $defaultCheckout->format('H:i:s') . "\n";
                $totalFixed++;
            } elseif ($shouldAutoClose) {
                echo "   ⚠️ Should be closed (no shift info, older than $AUTO_CLOSE_AFTER_DAYS day)\n";
            }
        }
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "Total unclosed records: " . $unclosedAttendances->count() . "\n";
echo "Total users affected: " . $byUser->count() . "\n";

if ($AUTO_FIX) {
    echo "Total records fixed: $totalFixed\n";
    echo "✅ Auto-close completed\n";
} else {
    $shouldClose = $unclosedAttendances->filter(function($a) use ($AUTO_CLOSE_AFTER_DAYS) {
        return Carbon::parse($a->date)->diffInDays(Carbon::today()) >= $AUTO_CLOSE_AFTER_DAYS;
    })->count();
    
    echo "Records that should be closed: $shouldClose\n";
    echo "\n⚠️ DRY RUN MODE - No changes made\n";
    echo "To auto-close old attendance records:\n";
    echo "1. Edit this file and set \$AUTO_FIX = true\n";
    echo "2. Run again: php public/fix-all-unclosed-attendance.php\n";
}

echo "\nRECOMMENDATIONS:\n";
echo "• Records from today should be closed manually by users\n";
echo "• Records older than $AUTO_CLOSE_AFTER_DAYS day(s) can be auto-closed\n";
echo "• Consider implementing automatic check-out at shift end\n";
echo "• Send reminders to users who forget to check-out\n";
echo "\n";