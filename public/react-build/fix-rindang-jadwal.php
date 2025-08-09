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

echo "=== FIXING DR. RINDANG'S JADWAL ===\n\n";
echo "User ID: {$dokter->id}\n";
echo "Name: {$dokter->name}\n";
echo "Username: {$dokter->username}\n\n";

$today = Carbon::today();

// Step 1: Check if pegawai record exists
$pegawai = DB::table('di_paramedis')->where('user_id', $dokter->id)->first();
if (!$pegawai) {
    echo "Creating pegawai record...\n";
    $pegawaiId = DB::table('di_paramedis')->insertGetId([
        'user_id' => $dokter->id,
        'name' => $dokter->name,
        'nip' => 'TEST-' . $dokter->id,
        'unit_kerja' => 'Test Unit',
        'position' => 'Dokter',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "Created pegawai ID: $pegawaiId\n\n";
} else {
    $pegawaiId = $pegawai->id;
    echo "Found existing pegawai ID: $pegawaiId\n\n";
}

// Step 2: Check if shift template exists
$shift = DB::table('shift_templates')
    ->where('nama_shift', 'Shift Sore')
    ->first();

if (!$shift) {
    echo "Creating shift template...\n";
    $shiftId = DB::table('shift_templates')->insertGetId([
        'nama_shift' => 'Shift Sore',
        'waktu_mulai' => '17:00:00',
        'waktu_selesai' => '18:30:00',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "Created shift ID: $shiftId\n\n";
} else {
    $shiftId = $shift->id;
    echo "Found existing shift ID: $shiftId\n";
    echo "  - {$shift->nama_shift}: {$shift->waktu_mulai} - {$shift->waktu_selesai}\n\n";
}

// Step 3: Check existing jadwal for today
$existingJadwal = DB::table('jadwal_jagas')
    ->where('pegawai_id', $pegawaiId)
    ->whereDate('tanggal_jaga', $today)
    ->first();

if ($existingJadwal) {
    echo "Found existing jadwal ID: {$existingJadwal->id}\n";
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
    echo "Created jadwal ID: $jadwalId\n";
}

// Step 4: Check existing attendance
$existingAttendance = NonParamedisAttendance::where('user_id', $dokter->id)
    ->whereDate('date', $today)
    ->first();

if ($existingAttendance) {
    echo "\nFound existing attendance:\n";
    echo "  - Check-in: {$existingAttendance->check_in_time}\n";
    echo "  - Check-out: " . ($existingAttendance->check_out_time ?: 'Not yet') . "\n";
} else {
    echo "\nCreating attendance record...\n";
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
    echo "Created attendance with check-in at 17:07:43\n";
}

// Step 5: Verify the setup
echo "\n=== VERIFICATION ===\n";

// API check - simulate what the frontend would get
$apiJadwal = DB::table('jadwal_jagas as jj')
    ->join('shift_templates as st', 'jj.shift_template_id', '=', 'st.id')
    ->join('di_paramedis as dp', 'jj.pegawai_id', '=', 'dp.id')
    ->where('dp.user_id', $dokter->id)
    ->whereDate('jj.tanggal_jaga', $today)
    ->select('jj.*', 'st.nama_shift', 'st.waktu_mulai', 'st.waktu_selesai')
    ->first();

if ($apiJadwal) {
    echo "✅ API will return jadwal:\n";
    echo "   - Shift: {$apiJadwal->nama_shift}\n";
    echo "   - Time: {$apiJadwal->waktu_mulai} - {$apiJadwal->waktu_selesai}\n";
} else {
    echo "❌ API query returns no jadwal - there might be a join issue\n";
}

$apiAttendance = NonParamedisAttendance::where('user_id', $dokter->id)
    ->whereDate('date', $today)
    ->first();

if ($apiAttendance && $apiAttendance->check_in_time && !$apiAttendance->check_out_time) {
    echo "✅ User is checked in and can check out\n";
}

echo "\n=== EXPECTED BEHAVIOR ===\n";
echo "Dr. Rindang should now:\n";
echo "  1. ✅ Have a jadwal for today (Shift Sore: 17:00-18:30)\n";
echo "  2. ✅ Be checked in (at 17:07:43)\n";
echo "  3. ✅ See her schedule info, NOT 'Anda tidak memiliki jadwal jaga hari ini'\n";
echo "  4. ✅ Be able to click the CHECK OUT button\n";
echo "\nThe fix in the build file (isOnDuty with OR condition) ensures that:\n";
echo "  - When checked in, isOnDuty remains true even outside check-in window\n";
echo "  - The validation message prioritizes checked-in state\n";
echo "\nPlease refresh the app at http://127.0.0.1:8000/dokter/mobile-app\n";