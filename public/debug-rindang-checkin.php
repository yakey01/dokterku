<?php
/**
 * Debug Check-in Issue for dr. Rindang
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

echo "\n" . str_repeat("=", 60) . "\n";
echo "DEBUG CHECK-IN ISSUE - DR. RINDANG\n";
echo str_repeat("=", 60) . "\n\n";

// Find dr. Rindang
$rindang = User::where('email', 'dd@rrr.com')
    ->orWhere('name', 'LIKE', '%Rindang%')
    ->first();

if (!$rindang) {
    echo "❌ Dr. Rindang not found\n";
    exit(1);
}

echo "👤 USER INFO:\n";
echo "   Name: {$rindang->name}\n";
echo "   ID: {$rindang->id}\n";
echo "   Email: {$rindang->email}\n";
echo "   Role: " . ($rindang->role ? $rindang->role->name : 'N/A') . "\n\n";

$now = Carbon::now('Asia/Jakarta');
$today = Carbon::today('Asia/Jakarta');

echo "⏰ CURRENT TIME:\n";
echo "   Now: " . $now->format('Y-m-d H:i:s') . " (Asia/Jakarta)\n";
echo "   Today: " . $today->format('Y-m-d') . "\n\n";

// Check for unclosed attendance (from last 7 days)
echo "📋 CHECKING UNCLOSED ATTENDANCE:\n";
$unclosedAttendance = Attendance::where('user_id', $rindang->id)
    ->whereDate('date', '>=', Carbon::now()->subDays(7)->startOfDay())
    ->whereNotNull('time_in')
    ->whereNull('time_out')
    ->orderByDesc('date')
    ->orderByDesc('time_in')
    ->with('jadwalJaga.shiftTemplate')
    ->get();

if ($unclosedAttendance->count() > 0) {
    echo "   ⚠️ Found " . $unclosedAttendance->count() . " unclosed attendance(s):\n";
    foreach ($unclosedAttendance as $att) {
        $date = Carbon::parse($att->date);
        echo "   - Date: " . $date->format('Y-m-d') . " (" . $date->diffForHumans() . ")\n";
        echo "     Check-in: " . Carbon::parse($att->time_in)->format('H:i:s') . "\n";
        if ($att->jadwalJaga && $att->jadwalJaga->shiftTemplate) {
            echo "     Shift: " . $att->jadwalJaga->shiftTemplate->nama_shift . "\n";
        }
        echo "\n";
    }
} else {
    echo "   ✅ No unclosed attendance found\n\n";
}

// Check today's schedule
echo "📅 TODAY'S SCHEDULE (JADWAL JAGA):\n";
$jadwalHariIni = JadwalJaga::where('pegawai_id', $rindang->id)
    ->whereDate('tanggal_jaga', $today)
    ->with('shiftTemplate')
    ->get();

if ($jadwalHariIni->isEmpty()) {
    echo "   ❌ No schedule found for today\n\n";
} else {
    echo "   Found " . $jadwalHariIni->count() . " schedule(s):\n";
    foreach ($jadwalHariIni as $idx => $jadwal) {
        echo "\n   Schedule " . ($idx + 1) . ":\n";
        echo "   - ID: {$jadwal->id}\n";
        echo "   - Date: " . Carbon::parse($jadwal->tanggal_jaga)->format('Y-m-d') . "\n";
        echo "   - Unit: {$jadwal->unit_kerja}\n";
        echo "   - Status: {$jadwal->status_jaga}\n";
        
        if ($jadwal->shiftTemplate) {
            $shift = $jadwal->shiftTemplate;
            echo "   - Shift: {$shift->nama_shift}\n";
            
            // Parse shift times
            $jamMasuk = $shift->jam_masuk;
            $jamPulang = $shift->jam_pulang;
            
            // Clean up time format (remove date prefix if exists)
            if (strpos($jamMasuk, ' ') !== false) {
                $jamMasuk = explode(' ', $jamMasuk)[1] ?? $jamMasuk;
            }
            if (strpos($jamPulang, ' ') !== false) {
                $jamPulang = explode(' ', $jamPulang)[1] ?? $jamPulang;
            }
            
            echo "   - Time: {$jamMasuk} - {$jamPulang}\n";
            echo "   - Duration: {$shift->durasi_jam} hours\n";
            
            // Calculate check-in window
            $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $jamMasuk);
            $shiftEnd = Carbon::parse($today->format('Y-m-d') . ' ' . $jamPulang);
            
            if ($shiftEnd < $shiftStart) {
                $shiftEnd->addDay(); // Overnight shift
            }
            
            // Buffer calculation
            $durationMinutes = $shiftEnd->diffInMinutes($shiftStart);
            $bufferMinutes = $durationMinutes <= 30 ? 60 : 30;
            
            $checkInWindowStart = $shiftStart->copy()->subMinutes($bufferMinutes);
            $checkInWindowEnd = $shiftEnd->copy()->addMinutes($bufferMinutes);
            
            echo "\n   ⏰ CHECK-IN WINDOW:\n";
            echo "   - Can check-in from: " . $checkInWindowStart->format('H:i') . "\n";
            echo "   - Can check-in until: " . $checkInWindowEnd->format('H:i') . "\n";
            
            $canCheckInNow = $now->between($checkInWindowStart, $checkInWindowEnd);
            echo "   - Can check-in NOW? " . ($canCheckInNow ? "✅ YES" : "❌ NO") . "\n";
            
            if (!$canCheckInNow) {
                if ($now < $checkInWindowStart) {
                    $waitTime = $now->diffInMinutes($checkInWindowStart);
                    echo "   - Wait " . $waitTime . " minutes until check-in window opens\n";
                } else {
                    echo "   - Check-in window has passed\n";
                }
            }
        } else {
            echo "   - ⚠️ No shift template linked\n";
        }
    }
}

// Check today's attendance
echo "\n📊 TODAY'S ATTENDANCE:\n";
$todayAttendance = Attendance::where('user_id', $rindang->id)
    ->whereDate('date', $today)
    ->orderByDesc('time_in')
    ->get();

if ($todayAttendance->isEmpty()) {
    echo "   No attendance records for today\n";
} else {
    echo "   Found " . $todayAttendance->count() . " attendance record(s):\n";
    foreach ($todayAttendance as $att) {
        echo "   - Check-in: " . ($att->time_in ? Carbon::parse($att->time_in)->format('H:i:s') : 'N/A') . "\n";
        echo "   - Check-out: " . ($att->time_out ? Carbon::parse($att->time_out)->format('H:i:s') : 'NOT YET') . "\n";
        echo "   - Status: {$att->status}\n";
        echo "   - Jadwal Jaga ID: " . ($att->jadwal_jaga_id ?? 'N/A') . "\n\n";
    }
}

// Diagnose the issue
echo str_repeat("=", 60) . "\n";
echo "🔍 DIAGNOSIS:\n";
echo str_repeat("=", 60) . "\n";

$canCheckIn = true;
$reasons = [];

// 1. Check unclosed attendance
if ($unclosedAttendance->count() > 0) {
    $canCheckIn = false;
    $lastUnclosed = $unclosedAttendance->first();
    $date = Carbon::parse($lastUnclosed->date);
    $reasons[] = "❌ Has unclosed attendance from " . $date->format('Y-m-d') . " (needs check-out first)";
}

// 2. Check schedule availability
if ($jadwalHariIni->isEmpty()) {
    $canCheckIn = false;
    $reasons[] = "❌ No schedule (jadwal jaga) for today";
} else {
    // Check if any schedule is in valid check-in window
    $hasValidWindow = false;
    foreach ($jadwalHariIni as $jadwal) {
        if ($jadwal->shiftTemplate) {
            $shift = $jadwal->shiftTemplate;
            $jamMasuk = $shift->jam_masuk;
            if (strpos($jamMasuk, ' ') !== false) {
                $jamMasuk = explode(' ', $jamMasuk)[1] ?? $jamMasuk;
            }
            $jamPulang = $shift->jam_pulang;
            if (strpos($jamPulang, ' ') !== false) {
                $jamPulang = explode(' ', $jamPulang)[1] ?? $jamPulang;
            }
            
            $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $jamMasuk);
            $shiftEnd = Carbon::parse($today->format('Y-m-d') . ' ' . $jamPulang);
            if ($shiftEnd < $shiftStart) {
                $shiftEnd->addDay();
            }
            
            $durationMinutes = $shiftEnd->diffInMinutes($shiftStart);
            $bufferMinutes = $durationMinutes <= 30 ? 60 : 30;
            
            $checkInWindowStart = $shiftStart->copy()->subMinutes($bufferMinutes);
            $checkInWindowEnd = $shiftEnd->copy()->addMinutes($bufferMinutes);
            
            if ($now->between($checkInWindowStart, $checkInWindowEnd)) {
                $hasValidWindow = true;
                break;
            }
        }
    }
    
    if (!$hasValidWindow) {
        $canCheckIn = false;
        $reasons[] = "❌ Not within any valid check-in window";
    }
}

// 3. Check if already checked in today
$openTodayAttendance = $todayAttendance->filter(function($att) {
    return $att->time_in && !$att->time_out;
})->first();

if ($openTodayAttendance) {
    $canCheckIn = false;
    $reasons[] = "❌ Already checked-in today at " . Carbon::parse($openTodayAttendance->time_in)->format('H:i');
}

if ($canCheckIn) {
    echo "✅ CAN CHECK-IN NOW\n";
} else {
    echo "❌ CANNOT CHECK-IN\n";
    echo "\nReasons:\n";
    foreach ($reasons as $reason) {
        echo $reason . "\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "💡 RECOMMENDATIONS:\n";
echo str_repeat("=", 60) . "\n";

if (!$canCheckIn) {
    if ($unclosedAttendance->count() > 0) {
        echo "1. Close previous attendance first:\n";
        echo "   - Go to attendance page and click Check-out\n";
        echo "   - Or ask admin to run: php public/fix-all-unclosed-attendance.php\n\n";
    }
    
    if ($jadwalHariIni->isEmpty()) {
        echo "2. No schedule for today:\n";
        echo "   - Contact admin to create schedule (jadwal jaga)\n";
        echo "   - Schedule must be created before check-in\n\n";
    }
    
    if (!empty($reasons) && strpos(implode('', $reasons), 'window') !== false) {
        echo "3. Outside check-in window:\n";
        echo "   - Wait for valid check-in time\n";
        echo "   - Or contact admin to adjust schedule\n\n";
    }
} else {
    echo "✅ Everything looks good! User should be able to check-in.\n";
    echo "   If still having issues:\n";
    echo "   1. Clear browser cache and refresh\n";
    echo "   2. Check GPS/location permissions\n";
    echo "   3. Try from different browser/device\n";
}

echo "\n";