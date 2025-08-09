<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;
use App\Models\NonParamedisAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Get dr. Rindang
$dokter = User::where('username', 'rindang')
    ->orWhere('name', 'like', '%Rindang%')
    ->first();

if (!$dokter) {
    die("Dr. Rindang not found!\n");
}

echo "=== COMPLETE FIX FOR DR. RINDANG ===\n\n";
echo "User Details:\n";
echo "  - ID: {$dokter->id}\n";
echo "  - Name: {$dokter->name}\n";
echo "  - Username: {$dokter->username}\n\n";

$today = Carbon::today();
$now = Carbon::now();

// Step 1: Ensure pegawai record exists with correct column names
$pegawai = DB::table('pegawais')->where('user_id', $dokter->id)->first();
if (!$pegawai) {
    echo "Creating pegawai record...\n";
    $pegawaiId = DB::table('pegawais')->insertGetId([
        'nik' => 'TEST-' . $dokter->id,
        'nama_lengkap' => $dokter->name,
        'email' => $dokter->email,
        'jabatan' => 'Dokter',
        'jenis_pegawai' => 'Dokter',
        'aktif' => 1,
        'user_id' => $dokter->id,
        'username' => $dokter->username,
        'status_akun' => 'Aktif',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "  ✅ Created pegawai ID: $pegawaiId\n\n";
} else {
    $pegawaiId = $pegawai->id;
    echo "  ✅ Found existing pegawai ID: $pegawaiId\n\n";
}

// Step 2: Create shift template if needed
$shift = DB::table('shift_templates')
    ->where('nama_shift', 'Shift Sore Test')
    ->first();

if (!$shift) {
    echo "Creating shift template...\n";
    $shiftId = DB::table('shift_templates')->insertGetId([
        'nama_shift' => 'Shift Sore Test',
        'waktu_mulai' => '17:00:00',
        'waktu_selesai' => '18:30:00',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "  ✅ Created shift ID: $shiftId\n\n";
} else {
    $shiftId = $shift->id;
    echo "  ✅ Using existing shift: {$shift->nama_shift} ({$shift->waktu_mulai} - {$shift->waktu_selesai})\n\n";
}

// Step 3: Create jadwal for today
$existingJadwal = DB::table('jadwal_jagas')
    ->where('pegawai_id', $pegawaiId)
    ->whereDate('tanggal_jaga', $today)
    ->first();

if ($existingJadwal) {
    echo "  ✅ Found existing jadwal ID: {$existingJadwal->id}\n\n";
    $jadwalId = $existingJadwal->id;
} else {
    echo "Creating jadwal for today...\n";
    $jadwalId = DB::table('jadwal_jagas')->insertGetId([
        'tanggal_jaga' => $today->format('Y-m-d'),
        'shift_template_id' => $shiftId,
        'pegawai_id' => $pegawaiId,
        'unit_instalasi' => 'Test Unit',
        'unit_kerja' => 'Test Unit',
        'peran' => 'Dokter',
        'status_jaga' => 'scheduled',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "  ✅ Created jadwal ID: $jadwalId for {$today->format('Y-m-d')}\n\n";
}

// Step 4: Create attendance record (checked in but not checked out)
$existingAttendance = NonParamedisAttendance::where('user_id', $dokter->id)
    ->whereDate('date', $today)
    ->first();

if ($existingAttendance) {
    echo "Existing attendance:\n";
    echo "  - Check-in: {$existingAttendance->check_in_time}\n";
    echo "  - Check-out: " . ($existingAttendance->check_out_time ?: '❌ Not yet') . "\n";
    echo "  - Status: {$existingAttendance->status}\n\n";
    
    // Clear check-out if exists to simulate "still checked in"
    if ($existingAttendance->check_out_time) {
        $existingAttendance->check_out_time = null;
        $existingAttendance->save();
        echo "  ✅ Cleared check-out time to simulate active check-in\n\n";
    }
} else {
    echo "Creating attendance record...\n";
    $attendance = NonParamedisAttendance::create([
        'user_id' => $dokter->id,
        'date' => $today,
        'check_in_time' => '17:07:43',
        'status' => 'present',
        'jadwal_jaga_id' => $jadwalId,
        'latitude' => -7.8788,
        'longitude' => 110.3289,
        'location_accuracy' => 10
    ]);
    echo "  ✅ Created attendance with check-in at 17:07:43\n\n";
}

// Step 5: Verify the API would return correct data
echo "=== VERIFICATION ===\n\n";

// Check what the API endpoint would return
$apiJadwal = DB::table('jadwal_jagas as jj')
    ->join('shift_templates as st', 'jj.shift_template_id', '=', 'st.id')
    ->join('pegawais as p', 'jj.pegawai_id', '=', 'p.id')
    ->where('p.user_id', $dokter->id)
    ->whereDate('jj.tanggal_jaga', $today)
    ->select(
        'jj.id',
        'jj.tanggal_jaga',
        'st.nama_shift',
        'st.waktu_mulai',
        'st.waktu_selesai',
        'jj.unit_kerja',
        'jj.status_jaga'
    )
    ->first();

if ($apiJadwal) {
    echo "✅ API Query Returns Schedule:\n";
    echo "   - Jadwal ID: {$apiJadwal->id}\n";
    echo "   - Date: {$apiJadwal->tanggal_jaga}\n";
    echo "   - Shift: {$apiJadwal->nama_shift}\n";
    echo "   - Time: {$apiJadwal->waktu_mulai} - {$apiJadwal->waktu_selesai}\n";
    echo "   - Unit: {$apiJadwal->unit_kerja}\n\n";
} else {
    echo "❌ API query returns no jadwal - CHECK THE JOIN!\n\n";
}

$apiAttendance = NonParamedisAttendance::where('user_id', $dokter->id)
    ->whereDate('date', $today)
    ->first();

if ($apiAttendance) {
    echo "✅ Attendance Status:\n";
    echo "   - Checked in at: {$apiAttendance->check_in_time}\n";
    echo "   - Checked out at: " . ($apiAttendance->check_out_time ?: 'Still checked in') . "\n\n";
}

// Check build file
$manifestPath = __DIR__ . '/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    foreach ($manifest as $key => $data) {
        if (strpos($key, 'Presensi') !== false && strpos($key, '.js') !== false) {
            $buildFile = __DIR__ . '/build/' . ($data['file'] ?? '');
            if (file_exists($buildFile)) {
                $content = file_get_contents($buildFile);
                if (preg_match('/isOnDuty:[^,}]*\|\|/', $content)) {
                    echo "✅ Build File Contains Fix:\n";
                    echo "   - The isOnDuty logic includes OR condition for checked-in state\n\n";
                } else {
                    echo "❌ Build file missing the fix!\n\n";
                }
                break;
            }
        }
    }
}

echo "=== EXPECTED BEHAVIOR ===\n\n";
echo "With this data, dr. Rindang should:\n\n";
echo "1. ✅ Have a jadwal for today (17:00-18:30)\n";
echo "2. ✅ Be checked in (at 17:07:43)\n";
echo "3. ✅ See her schedule information\n";
echo "4. ✅ NOT see 'Anda tidak memiliki jadwal jaga hari ini'\n";
echo "5. ✅ Be able to click the CHECK OUT button\n\n";

echo "Current time: {$now->format('H:i:s')}\n";
echo "Schedule: 17:00:00 - 18:30:00\n";
echo "Check-in window: 16:30:00 - 18:30:00\n";
echo "Check-out window: 17:00:00 - 19:00:00\n\n";

$isWithinSchedule = $now->between(
    Carbon::parse($today->format('Y-m-d') . ' 17:00:00'),
    Carbon::parse($today->format('Y-m-d') . ' 18:30:00')
);

$isWithinCheckinWindow = $now->between(
    Carbon::parse($today->format('Y-m-d') . ' 16:30:00'),
    Carbon::parse($today->format('Y-m-d') . ' 18:30:00')
);

echo "Status Analysis:\n";
echo "  - Within schedule time: " . ($isWithinSchedule ? "YES" : "NO") . "\n";
echo "  - Within check-in window: " . ($isWithinCheckinWindow ? "YES" : "NO") . "\n";
echo "  - Is checked in: YES\n";
echo "  - Can check out: YES (because checked in)\n\n";

echo "The fix ensures that:\n";
echo "  - isOnDuty = hasSchedule && (withinWindow || isCheckedIn)\n";
echo "  - Since isCheckedIn = true, isOnDuty = true\n";
echo "  - Validation message checks isCheckedIn first\n\n";

echo "Please refresh the app at: http://127.0.0.1:8000/dokter/mobile-app\n";
echo "Login as: drrindang\n";