<?php
/**
 * Fix Script for dr. Rindang's Attendance Issue
 * This script specifically handles dr. Rindang's check-in problems
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
echo "FIX DR. RINDANG ATTENDANCE ISSUE\n";
echo str_repeat("=", 60) . "\n\n";

// Find dr. Rindang
$rindang = User::where('email', 'dd@rrr.com')->first();

if (!$rindang) {
    echo "❌ Dr. Rindang not found\n";
    exit(1);
}

echo "👤 Found: {$rindang->name} (ID: {$rindang->id})\n\n";

// Check for unclosed attendance
$unclosedAttendances = Attendance::where('user_id', $rindang->id)
    ->whereNotNull('time_in')
    ->whereNull('time_out')
    ->whereDate('date', '>=', Carbon::now()->subDays(7)->startOfDay())
    ->orderByDesc('date')
    ->orderByDesc('time_in')
    ->with('jadwalJaga.shiftTemplate')
    ->get();

if ($unclosedAttendances->isEmpty()) {
    echo "✅ No unclosed attendance records found.\n";
    echo "Dr. Rindang should be able to check-in normally.\n";
    exit(0);
}

echo "⚠️ Found " . $unclosedAttendances->count() . " unclosed attendance record(s)\n\n";

$fixed = 0;
foreach ($unclosedAttendances as $attendance) {
    $date = Carbon::parse($attendance->date);
    $timeIn = Carbon::parse($attendance->time_in);
    
    echo "📅 Date: " . $date->format('Y-m-d') . "\n";
    echo "   Check-in: " . $timeIn->format('H:i:s') . "\n";
    
    if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
        $shift = $attendance->jadwalJaga->shiftTemplate;
        echo "   Shift: {$shift->nama_shift}\n";
        
        // Extract time part from jam_pulang (might contain date prefix)
        $jamPulangStr = $shift->jam_pulang;
        if (strpos($jamPulangStr, ' ') !== false) {
            $parts = explode(' ', $jamPulangStr);
            $jamPulangStr = end($parts);
        }
        
        $expectedCheckout = Carbon::parse($date->format('Y-m-d') . ' ' . $jamPulangStr);
        if ($expectedCheckout < $timeIn) {
            $expectedCheckout->addDay();
        }
        
        // Check if shift has ended
        if (Carbon::now()->greaterThan($expectedCheckout)) {
            $attendance->time_out = $expectedCheckout;
            $attendance->latlon_out = $attendance->latlon_in;
            
            // Update location out if coordinates available
            if ($attendance->latitude && $attendance->longitude) {
                $attendance->checkout_latitude = $attendance->latitude;
                $attendance->checkout_longitude = $attendance->longitude;
                $attendance->checkout_accuracy = $attendance->accuracy;
            }
            
            $attendance->status = 'auto_closed';
            $attendance->save();
            
            echo "   ✅ AUTO-CLOSED at " . $expectedCheckout->format('H:i:s') . "\n";
            $fixed++;
        } else {
            echo "   ⏳ Shift still active (ends at " . $expectedCheckout->format('H:i:s') . ")\n";
        }
    } else {
        // No shift template, use 8-hour default
        $defaultCheckout = $timeIn->copy()->addHours(8);
        
        if (Carbon::now()->greaterThan($defaultCheckout)) {
            $attendance->time_out = $defaultCheckout;
            $attendance->latlon_out = $attendance->latlon_in;
            
            if ($attendance->latitude && $attendance->longitude) {
                $attendance->checkout_latitude = $attendance->latitude;
                $attendance->checkout_longitude = $attendance->longitude;
                $attendance->checkout_accuracy = $attendance->accuracy;
            }
            
            $attendance->status = 'auto_closed';
            $attendance->save();
            
            echo "   ✅ AUTO-CLOSED with 8-hour default\n";
            $fixed++;
        }
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "Total unclosed: " . $unclosedAttendances->count() . "\n";
echo "Total fixed: $fixed\n";

if ($fixed > 0) {
    echo "\n✅ Dr. Rindang can now check-in for the next shift.\n";
} else {
    echo "\n⏳ Some shifts are still active. Dr. Rindang needs to check-out first.\n";
}

echo "\n";