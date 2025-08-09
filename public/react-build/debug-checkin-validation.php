<?php
/**
 * Debug Script: Check-In Validation Issues
 * Debugging kenapa tombol check-in tidak bisa diklik
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use App\Models\WorkLocation;
use App\Models\Pegawai;
use Carbon\Carbon;

echo "\n" . str_repeat("=", 60) . "\n";
echo "CHECK-IN VALIDATION DEBUG\n";
echo str_repeat("=", 60) . "\n\n";

// Current time
$now = Carbon::now('Asia/Jakarta');
echo "Current Time: " . $now->format('Y-m-d H:i:s') . " WIB\n\n";

// Find doctor user
$doctor = User::whereHas('roles', function($q) {
    $q->where('name', 'dokter');
})->first();

if (!$doctor) {
    echo "❌ No doctor user found\n";
    exit(1);
}

echo "Doctor: {$doctor->name} (ID: {$doctor->id})\n";
echo "Email: {$doctor->email}\n\n";

// Find pegawai
$pegawai = Pegawai::where('user_id', $doctor->id)->first();
if (!$pegawai) {
    echo "⚠️ No pegawai record for this doctor\n";
} else {
    echo "Pegawai ID: {$pegawai->id}\n";
}

// Find today's schedule
echo str_repeat("-", 40) . "\n";
echo "TODAY'S SCHEDULE\n";
echo str_repeat("-", 40) . "\n";

$todaySchedules = JadwalJaga::with(['shiftTemplate', 'pegawai'])
    ->whereDate('tanggal_jaga', $now->toDateString())
    ->where(function($q) use ($doctor, $pegawai) {
        // Check both user_id in pegawai relation and direct pegawai_id
        if ($pegawai) {
            $q->where('pegawai_id', $pegawai->id);
        }
        $q->orWhereHas('pegawai', function($q2) use ($doctor) {
            $q2->where('user_id', $doctor->id);
        });
    })
    ->get();

if ($todaySchedules->isEmpty()) {
    echo "❌ No schedule found for today\n";
} else {
    foreach ($todaySchedules as $idx => $schedule) {
        echo "\nSchedule #" . ($idx + 1) . ":\n";
        echo "  ID: {$schedule->id}\n";
        echo "  Date: {$schedule->tanggal_jaga}\n";
        echo "  Unit: {$schedule->unit_instalasi}\n";
        echo "  Peran: {$schedule->peran}\n";
        
        if ($schedule->shiftTemplate) {
            $st = $schedule->shiftTemplate;
            echo "  Shift: {$st->nama_shift}\n";
            echo "  Time: {$st->jam_masuk} - {$st->jam_pulang}\n";
            echo "  Duration: {$st->durasi_jam} hours\n";
            
            // Parse shift times
            $startTime = Carbon::parse($now->toDateString() . ' ' . $st->jam_masuk);
            $endTime = Carbon::parse($now->toDateString() . ' ' . $st->jam_pulang);
            
            // Handle overnight shifts
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
            }
            
            echo "\n  🕐 Time Analysis:\n";
            echo "    Start: " . $startTime->format('H:i:s') . "\n";
            echo "    End: " . $endTime->format('H:i:s') . "\n";
            echo "    Now: " . $now->format('H:i:s') . "\n";
            
            // Check time windows
            $isBeforeShift = $now->lt($startTime);
            $isDuringShift = $now->gte($startTime) && $now->lte($endTime);
            $isAfterShift = $now->gt($endTime);
            
            echo "    Status: ";
            if ($isBeforeShift) {
                $minutesUntil = $now->diffInMinutes($startTime);
                echo "⏰ BEFORE shift (starts in {$minutesUntil} minutes)\n";
            } elseif ($isDuringShift) {
                echo "✅ DURING shift\n";
            } else {
                $minutesAfter = $endTime->diffInMinutes($now);
                echo "❌ AFTER shift (ended {$minutesAfter} minutes ago)\n";
            }
        } else {
            echo "  ⚠️ No shift template assigned\n";
        }
    }
}

// Check work location
echo "\n" . str_repeat("-", 40) . "\n";
echo "WORK LOCATION\n";
echo str_repeat("-", 40) . "\n";

$workLocations = WorkLocation::all();
if ($workLocations->isEmpty()) {
    echo "❌ No work locations in system\n";
} else {
    foreach ($workLocations as $wl) {
        echo "\nLocation: {$wl->name}\n";
        echo "  Address: {$wl->address}\n";
        echo "  Coordinates: {$wl->latitude}, {$wl->longitude}\n";
        echo "  Radius: {$wl->radius}m\n";
        
        // Check tolerances
        $checkinBefore = $wl->checkin_before_shift_minutes ?? 30;
        $lateTolerance = $wl->late_tolerance_minutes ?? 15;
        
        echo "  Check-in Window:\n";
        echo "    - Can check-in: {$checkinBefore} minutes before shift\n";
        echo "    - Late tolerance: {$lateTolerance} minutes after shift start\n";
    }
}

// Check-in validation logic
echo "\n" . str_repeat("-", 40) . "\n";
echo "CHECK-IN VALIDATION\n";
echo str_repeat("-", 40) . "\n";

if (!$todaySchedules->isEmpty()) {
    $schedule = $todaySchedules->first();
    $st = $schedule->shiftTemplate;
    
    if ($st) {
        $startTime = Carbon::parse($now->toDateString() . ' ' . $st->jam_masuk);
        $endTime = Carbon::parse($now->toDateString() . ' ' . $st->jam_pulang);
        
        // Get work location tolerances
        $wl = $workLocations->first();
        $checkinBefore = $wl ? ($wl->checkin_before_shift_minutes ?? 30) : 30;
        $lateTolerance = $wl ? ($wl->late_tolerance_minutes ?? 15) : 15;
        
        // Calculate check-in window
        $earliestCheckin = $startTime->copy()->subMinutes($checkinBefore);
        $latestCheckin = $startTime->copy()->addMinutes($lateTolerance);
        
        echo "Check-in Window:\n";
        echo "  Earliest: " . $earliestCheckin->format('H:i:s') . " ({$checkinBefore} min before)\n";
        echo "  Latest: " . $latestCheckin->format('H:i:s') . " ({$lateTolerance} min after)\n";
        echo "  Current: " . $now->format('H:i:s') . "\n";
        
        $canCheckIn = $now->gte($earliestCheckin) && $now->lte($latestCheckin);
        
        echo "\n🎯 Can Check-In: " . ($canCheckIn ? "✅ YES" : "❌ NO") . "\n";
        
        if (!$canCheckIn) {
            if ($now->lt($earliestCheckin)) {
                $minutesUntil = $now->diffInMinutes($earliestCheckin);
                echo "  Reason: Too early (wait {$minutesUntil} more minutes)\n";
            } else {
                $minutesLate = $latestCheckin->diffInMinutes($now);
                echo "  Reason: Too late (window closed {$minutesLate} minutes ago)\n";
            }
        }
        
        // Additional checks
        echo "\nOther Requirements:\n";
        echo "  ✅ Has schedule today\n";
        echo "  " . ($wl ? "✅" : "❌") . " Work location exists\n";
        
        // Check attendance status
        $attendance = \App\Models\Attendance::where('user_id', $doctor->id)
            ->whereDate('date', $now->toDateString())
            ->first();
        
        $isCheckedIn = $attendance && $attendance->time_in && !$attendance->time_out;
        echo "  " . ($isCheckedIn ? "⚠️ Already checked in" : "✅ Not checked in yet") . "\n";
        
        // Final validation
        $canCheckInFinal = $canCheckIn && !$isCheckedIn && $wl;
        echo "\n📊 FINAL CHECK-IN STATUS: " . ($canCheckInFinal ? "✅ ENABLED" : "❌ DISABLED") . "\n";
        
        if (!$canCheckInFinal) {
            echo "\nReasons button is disabled:\n";
            if (!$canCheckIn) echo "  - Not within check-in time window\n";
            if ($isCheckedIn) echo "  - Already checked in\n";
            if (!$wl) echo "  - No work location configured\n";
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "RECOMMENDATIONS\n";
echo str_repeat("=", 60) . "\n";
echo "1. Ensure shift times are configured correctly\n";
echo "2. Check work location tolerances (checkin_before_shift_minutes)\n";
echo "3. Verify the current time is within check-in window\n";
echo "4. Make sure user hasn't already checked in today\n";
echo "\n";