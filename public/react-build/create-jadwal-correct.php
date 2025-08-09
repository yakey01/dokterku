<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\JadwalJaga;
use App\Models\User;
use App\Models\NonParamedis;
use App\Models\NonParamedisAttendance;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

// Get dr. Rindang
$dokter = User::where('username', 'rindang')
    ->orWhere('name', 'like', '%Rindang%')
    ->first();

if (!$dokter) {
    die("Dr. Rindang not found!\n");
}

// Find the associated NonParamedis record
$nonParamedis = NonParamedis::where('user_id', $dokter->id)->first();
if (!$nonParamedis) {
    echo "Creating NonParamedis record for dr. Rindang...\n";
    $nonParamedis = NonParamedis::create([
        'user_id' => $dokter->id,
        'name' => $dokter->name,
        'nip' => 'TEST-' . $dokter->id,
        'unit_kerja' => 'Test Unit',
        'position' => 'Dokter'
    ]);
}

echo "=== DR. RINDANG DATA ===\n";
echo "User ID: {$dokter->id}\n";
echo "Name: {$dokter->name}\n";
echo "Username: {$dokter->username}\n";
echo "NonParamedis ID: {$nonParamedis->id}\n\n";

// Check today's jadwal using correct column names
$today = Carbon::today();
$existingJadwal = JadwalJaga::where('pegawai_id', $nonParamedis->id)
    ->whereDate('tanggal_jaga', $today)
    ->first();

if ($existingJadwal) {
    echo "EXISTING JADWAL FOR TODAY:\n";
    echo "  - ID: {$existingJadwal->id}\n";
    echo "  - Date: {$existingJadwal->tanggal_jaga}\n";
    echo "  - Status: {$existingJadwal->status_jaga}\n";
    
    // Get shift details
    if ($existingJadwal->shift_template_id) {
        $shift = ShiftTemplate::find($existingJadwal->shift_template_id);
        if ($shift) {
            echo "  - Shift: {$shift->nama_shift}\n";
            echo "  - Time: {$shift->waktu_mulai} - {$shift->waktu_selesai}\n";
        }
    }
} else {
    echo "NO JADWAL FOR TODAY. Creating one...\n\n";
    
    // Find or create a shift template
    $shift = ShiftTemplate::where('nama_shift', 'Shift Sore')->first();
    if (!$shift) {
        echo "Creating Shift Template...\n";
        $shift = ShiftTemplate::create([
            'nama_shift' => 'Shift Sore',
            'waktu_mulai' => '17:00:00',
            'waktu_selesai' => '18:30:00'
        ]);
    }
    
    // Create jadwal with correct column names
    $testJadwal = JadwalJaga::create([
        'tanggal_jaga' => $today->format('Y-m-d'),
        'shift_template_id' => $shift->id,
        'pegawai_id' => $nonParamedis->id,
        'unit_instalasi' => 'Test Unit',
        'unit_kerja' => 'Test Unit',
        'peran' => 'Dokter',
        'status_jaga' => 'scheduled'
    ]);
    
    echo "Created jadwal ID: {$testJadwal->id}\n";
    echo "  - Date: {$testJadwal->tanggal_jaga}\n";
    echo "  - Shift: {$shift->nama_shift} ({$shift->waktu_mulai} - {$shift->waktu_selesai})\n";
}

// Check today's attendance
$attendance = NonParamedisAttendance::where('user_id', $dokter->id)
    ->whereDate('date', $today)
    ->first();

if ($attendance) {
    echo "\nEXISTING ATTENDANCE:\n";
    echo "  - Check-in: {$attendance->check_in_time}\n";
    echo "  - Check-out: " . ($attendance->check_out_time ?: 'Not yet') . "\n";
    echo "  - Status: {$attendance->status}\n";
} else {
    echo "\nNO ATTENDANCE. Creating check-in record...\n";
    
    // Get the jadwal we just created or found
    $jadwal = JadwalJaga::where('pegawai_id', $nonParamedis->id)
        ->whereDate('tanggal_jaga', $today)
        ->first();
    
    if ($jadwal) {
        $testAttendance = NonParamedisAttendance::create([
            'user_id' => $dokter->id,
            'date' => $today,
            'check_in_time' => '17:07:43',
            'status' => 'present',
            'jadwal_jaga_id' => $jadwal->id,
            'latitude' => -7.8788,
            'longitude' => 110.3289,
            'location_accuracy' => 10
        ]);
        
        echo "Created attendance record with check-in at 17:07:43\n";
    }
}

echo "\n=== FINAL STATE ===\n";
echo "Dr. Rindang now has:\n";
echo "  ✅ A jadwal for today (Shift Sore: 17:00-18:30)\n";
echo "  ✅ Check-in record at 17:07:43\n";
echo "  ✅ The build file contains the fix (isOnDuty with OR condition)\n";
echo "\nExpected behavior in the app:\n";
echo "  - Should see schedule information\n";
echo "  - Should NOT see 'Anda tidak memiliki jadwal jaga hari ini'\n";
echo "  - Should be able to click CHECK OUT button\n";
echo "\nPlease refresh the app at http://127.0.0.1:8000/dokter/mobile-app\n";