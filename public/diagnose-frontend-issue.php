<?php
/**
 * Deep Diagnostic for Frontend State Issue
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use App\Models\WorkLocation;
use Carbon\Carbon;

echo "\n" . str_repeat("=", 70) . "\n";
echo "DEEP FRONTEND DIAGNOSTIC - DR. RINDANG\n";
echo str_repeat("=", 70) . "\n\n";

$rindang = User::where('email', 'dd@rrr.com')->first();
if (!$rindang) {
    die("User not found\n");
}

$now = Carbon::now('Asia/Jakarta');
$today = Carbon::today('Asia/Jakarta');

echo "🕐 Current Time: " . $now->format('Y-m-d H:i:s') . "\n\n";

// ====== 1. SCHEDULE DATA ======
echo "1️⃣ SCHEDULE DATA (jadwal_jagas table):\n";
echo str_repeat("-", 50) . "\n";

$schedules = JadwalJaga::where('pegawai_id', $rindang->id)
    ->whereDate('tanggal_jaga', $today)
    ->with('shiftTemplate')
    ->get();

foreach ($schedules as $idx => $jadwal) {
    echo "Schedule " . ($idx + 1) . ":\n";
    echo "  ID: {$jadwal->id}\n";
    echo "  Pegawai ID: {$jadwal->pegawai_id}\n";
    echo "  Tanggal: {$jadwal->tanggal_jaga}\n";
    echo "  Unit: {$jadwal->unit_kerja}\n";
    echo "  Status: {$jadwal->status_jaga}\n";
    
    if ($jadwal->shiftTemplate) {
        $shift = $jadwal->shiftTemplate;
        echo "  Shift ID: {$shift->id}\n";
        echo "  Shift Name: {$shift->nama_shift}\n";
        echo "  Jam Masuk (raw): '{$shift->jam_masuk}'\n";
        echo "  Jam Pulang (raw): '{$shift->jam_pulang}'\n";
        
        // Parse times
        $jamMasuk = $shift->jam_masuk;
        $jamPulang = $shift->jam_pulang;
        
        // Check for date prefix
        if (strpos($jamMasuk, ' ') !== false) {
            echo "  ⚠️ Jam Masuk has date prefix\n";
            $jamMasuk = explode(' ', $jamMasuk)[1] ?? $jamMasuk;
        }
        if (strpos($jamPulang, ' ') !== false) {
            echo "  ⚠️ Jam Pulang has date prefix\n";
            $jamPulang = explode(' ', $jamPulang)[1] ?? $jamPulang;
        }
        
        echo "  Clean Time: {$jamMasuk} - {$jamPulang}\n";
        
        // Check if currently in shift
        $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $jamMasuk);
        $shiftEnd = Carbon::parse($today->format('Y-m-d') . ' ' . $jamPulang);
        if ($shiftEnd < $shiftStart) {
            $shiftEnd->addDay();
        }
        
        $isInShift = $now->between($shiftStart, $shiftEnd);
        echo "  Currently in shift? " . ($isInShift ? "✅ YES" : "❌ NO") . "\n";
        
        // Check-in window calculation
        $bufferMinutes = 30;
        $lateToleranceMinutes = 15;
        $checkInStart = $shiftStart->copy()->subMinutes($bufferMinutes);
        $checkInEnd = $shiftStart->copy()->addMinutes($lateToleranceMinutes);
        
        $isInCheckInWindow = $now->between($checkInStart, $checkInEnd);
        echo "  In check-in window? " . ($isInCheckInWindow ? "✅ YES" : "❌ NO") . "\n";
        echo "    Window: " . $checkInStart->format('H:i') . " - " . $checkInEnd->format('H:i') . "\n";
    } else {
        echo "  ❌ No shift template linked!\n";
    }
    echo "\n";
}

if ($schedules->isEmpty()) {
    echo "❌ NO SCHEDULES FOUND FOR TODAY\n";
}

// ====== 2. ATTENDANCE DATA ======
echo "\n2️⃣ ATTENDANCE DATA (attendances table):\n";
echo str_repeat("-", 50) . "\n";

$attendances = Attendance::where('user_id', $rindang->id)
    ->whereDate('date', $today)
    ->orderBy('time_in', 'desc')
    ->get();

foreach ($attendances as $idx => $att) {
    echo "Attendance " . ($idx + 1) . ":\n";
    echo "  ID: {$att->id}\n";
    echo "  Date: {$att->date}\n";
    echo "  Time In: {$att->time_in}\n";
    echo "  Time Out: " . ($att->time_out ?: 'NULL (OPEN)') . "\n";
    echo "  Status: {$att->status}\n";
    echo "  Jadwal Jaga ID: {$att->jadwal_jaga_id}\n";
    
    if (!$att->time_out) {
        echo "  🔴 THIS IS AN OPEN ATTENDANCE (NOT CHECKED OUT)\n";
    }
    echo "\n";
}

if ($attendances->isEmpty()) {
    echo "❌ NO ATTENDANCE RECORDS FOR TODAY\n";
}

// Find open attendance
$openAttendance = $attendances->first(function($a) {
    return $a->time_in && !$a->time_out;
});

// ====== 3. WORK LOCATION ======
echo "\n3️⃣ WORK LOCATION DATA:\n";
echo str_repeat("-", 50) . "\n";

$workLocation = WorkLocation::first();
if ($workLocation) {
    echo "ID: {$workLocation->id}\n";
    echo "Name: {$workLocation->name}\n";
    echo "Latitude: {$workLocation->latitude}\n";
    echo "Longitude: {$workLocation->longitude}\n";
    echo "Radius: {$workLocation->radius_meters}m\n";
} else {
    echo "❌ NO WORK LOCATION CONFIGURED\n";
}

// ====== 4. STATE ANALYSIS ======
echo "\n4️⃣ STATE ANALYSIS:\n";
echo str_repeat("-", 50) . "\n";

$hasSchedule = $schedules->isNotEmpty();
$isCheckedIn = $openAttendance !== null;
$hasWorkLocation = $workLocation !== null;

echo "Has Schedule Today: " . ($hasSchedule ? "✅ YES" : "❌ NO") . "\n";
echo "Is Checked In: " . ($isCheckedIn ? "✅ YES (at {$openAttendance->time_in})" : "❌ NO") . "\n";
echo "Has Work Location: " . ($hasWorkLocation ? "✅ YES" : "❌ NO") . "\n";

// Calculate isWithinCheckinWindow
$isWithinCheckinWindow = false;
$currentShift = null;

if ($hasSchedule) {
    foreach ($schedules as $jadwal) {
        if ($jadwal->shiftTemplate) {
            $shift = $jadwal->shiftTemplate;
            $jamMasuk = $shift->jam_masuk;
            if (strpos($jamMasuk, ' ') !== false) {
                $jamMasuk = explode(' ', $jamMasuk)[1] ?? $jamMasuk;
            }
            
            $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $jamMasuk);
            $checkInStart = $shiftStart->copy()->subMinutes(30);
            $checkInEnd = $shiftStart->copy()->addMinutes(15);
            
            if ($now->between($checkInStart, $checkInEnd)) {
                $isWithinCheckinWindow = true;
                $currentShift = $jadwal;
                break;
            }
        }
    }
}

echo "Is Within Check-in Window: " . ($isWithinCheckinWindow ? "✅ YES" : "❌ NO") . "\n";

// ====== 5. FRONTEND LOGIC SIMULATION ======
echo "\n5️⃣ FRONTEND LOGIC SIMULATION:\n";
echo str_repeat("-", 50) . "\n";

// Simulate the exact logic from Presensi.tsx
$isOnDutyToday = $hasSchedule;
$isOnDuty = $isOnDutyToday && ($isWithinCheckinWindow || $isCheckedIn);
$canCheckIn = $isOnDutyToday && $isWithinCheckinWindow && !$isCheckedIn;
$canCheckOut = $isCheckedIn;

echo "isOnDutyToday: " . ($isOnDutyToday ? "true" : "false") . "\n";
echo "isWithinCheckinWindow: " . ($isWithinCheckinWindow ? "true" : "false") . "\n";
echo "isCheckedIn: " . ($isCheckedIn ? "true" : "false") . "\n";
echo "----------------------------------------\n";
echo "isOnDuty: " . ($isOnDuty ? "true" : "false");
echo " (should be: " . ($isOnDutyToday && ($isWithinCheckinWindow || $isCheckedIn) ? "true" : "false") . ")\n";
echo "canCheckIn: " . ($canCheckIn ? "true" : "false") . "\n";
echo "canCheckOut: " . ($canCheckOut ? "true" : "false") . "\n";

// Validation message logic
$validationMessage = '';
if ($isCheckedIn) {
    if ($canCheckOut === false) {
        $validationMessage = '⏰ Waktu check-out sudah melewati batas';
    } else {
        $validationMessage = ''; // Ready to check out
    }
} else {
    if (!$isOnDutyToday) {
        $validationMessage = 'Anda tidak memiliki jadwal jaga hari ini';
    } else if (!$isWithinCheckinWindow) {
        $validationMessage = 'Saat ini bukan jam jaga Anda';
    } else if (!$hasWorkLocation) {
        $validationMessage = 'Work location belum ditugaskan';
    }
}

echo "validationMessage: '{$validationMessage}'\n";

// ====== 6. ISSUES DETECTED ======
echo "\n6️⃣ ISSUES DETECTED:\n";
echo str_repeat("-", 50) . "\n";

$issues = [];

// Check for state inconsistencies
if ($isCheckedIn && !$isOnDuty) {
    $issues[] = "❌ CRITICAL: isCheckedIn=true but isOnDuty=false";
}

if ($isCheckedIn && !$canCheckOut) {
    $issues[] = "❌ CRITICAL: isCheckedIn=true but canCheckOut=false";
}

if ($isCheckedIn && $validationMessage === 'Anda tidak memiliki jadwal jaga hari ini') {
    $issues[] = "❌ CRITICAL: Showing 'no schedule' message when checked in";
}

if ($hasSchedule && !$isCheckedIn && $validationMessage === 'Anda tidak memiliki jadwal jaga hari ini') {
    $issues[] = "❌ ERROR: Showing 'no schedule' message when schedule exists";
}

if ($isCheckedIn && $openAttendance && $openAttendance->jadwal_jaga_id) {
    $matchedSchedule = $schedules->first(function($s) use ($openAttendance) {
        return $s->id == $openAttendance->jadwal_jaga_id;
    });
    
    if (!$matchedSchedule) {
        $issues[] = "⚠️ WARNING: Attendance linked to jadwal_jaga_id {$openAttendance->jadwal_jaga_id} but not in today's schedules";
    }
}

if (empty($issues)) {
    echo "✅ No issues detected - state is consistent\n";
} else {
    foreach ($issues as $issue) {
        echo $issue . "\n";
    }
}

// ====== 7. EXPECTED BEHAVIOR ======
echo "\n7️⃣ EXPECTED BEHAVIOR:\n";
echo str_repeat("-", 50) . "\n";

if ($isCheckedIn) {
    echo "User is CHECKED IN:\n";
    echo "  ✅ Should NOT show 'Anda tidak memiliki jadwal jaga hari ini'\n";
    echo "  ✅ Check-out button should be ENABLED\n";
    echo "  ✅ isOnDuty should be TRUE\n";
    echo "  ✅ canCheckOut should be TRUE\n";
} else if ($hasSchedule) {
    echo "User has SCHEDULE but not checked in:\n";
    echo "  ✅ Should NOT show 'Anda tidak memiliki jadwal jaga hari ini'\n";
    if ($isWithinCheckinWindow) {
        echo "  ✅ Check-in button should be ENABLED\n";
    } else {
        echo "  ⚠️ Should show 'Saat ini bukan jam jaga Anda'\n";
    }
} else {
    echo "User has NO SCHEDULE:\n";
    echo "  ✅ Should show 'Anda tidak memiliki jadwal jaga hari ini'\n";
}

// ====== 8. FIX VERIFICATION ======
echo "\n8️⃣ FIX VERIFICATION:\n";
echo str_repeat("-", 50) . "\n";

// Check if the fix is in the code
$presensiFile = file_get_contents(__DIR__ . '/../resources/js/components/dokter/Presensi.tsx');

// Check for the fixed isOnDuty logic
if (strpos($presensiFile, 'isOnDuty: isOnDutyToday && (isWithinCheckinWindow || isCheckedIn)') !== false) {
    echo "✅ Fix 1: isOnDuty logic is CORRECT in source\n";
} else {
    echo "❌ Fix 1: isOnDuty logic is WRONG in source\n";
}

// Check for the fixed validation message
if (strpos($presensiFile, 'if (isCheckedIn) {') !== false && 
    strpos($presensiFile, 'if (canCheckOut === false)') !== false) {
    echo "✅ Fix 2: Validation message priority is CORRECT in source\n";
} else {
    echo "❌ Fix 2: Validation message priority is WRONG in source\n";
}

// Check build file
$buildFiles = glob(__DIR__ . '/../public/build/assets/js/Presensi-*.js');
if (!empty($buildFiles)) {
    $latestBuild = end($buildFiles);
    $buildTime = date('Y-m-d H:i:s', filemtime($latestBuild));
    echo "✅ Build file exists: " . basename($latestBuild) . "\n";
    echo "   Built at: {$buildTime}\n";
    
    // Check if fix is in build
    $buildContent = file_get_contents($latestBuild);
    if (strpos($buildContent, 'isWithinCheckinWindow||isCheckedIn') !== false ||
        strpos($buildContent, 'isWithinCheckinWindow || isCheckedIn') !== false) {
        echo "   ✅ Fix is IN the build file\n";
    } else {
        echo "   ❌ Fix is NOT in the build file\n";
    }
} else {
    echo "❌ No build file found\n";
}

echo "\n" . str_repeat("=", 70) . "\n\n";