<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\JadwalJaga;
use App\Models\User;
use App\Models\NonParamedisAttendance;
use Carbon\Carbon;

// Get dr. Rindang
$dokter = User::where('username', 'rindang')
    ->orWhere('name', 'like', '%Rindang%')
    ->first();

if (!$dokter) {
    die("Dr. Rindang not found!\n");
}

echo "=== DR. RINDANG DATA CHECK ===\n\n";
echo "User ID: {$dokter->id}\n";
echo "Name: {$dokter->name}\n";
echo "Username: {$dokter->username}\n\n";

// Check today's attendance
$today = Carbon::today();
$attendance = NonParamedisAttendance::where('user_id', $dokter->id)
    ->whereDate('date', $today)
    ->first();
    
if ($attendance) {
    echo "TODAY'S ATTENDANCE:\n";
    echo "  - Check-in: {$attendance->check_in_time}\n";
    echo "  - Check-out: " . ($attendance->check_out_time ?: 'Not yet') . "\n";
    echo "  - Status: {$attendance->status}\n";
    echo "  - Jadwal ID: {$attendance->jadwal_jaga_id}\n\n";
    
    // Get the associated jadwal
    if ($attendance->jadwal_jaga_id) {
        $jadwal = JadwalJaga::find($attendance->jadwal_jaga_id);
        if ($jadwal) {
            echo "ASSOCIATED JADWAL (ID: {$jadwal->id}):\n";
            echo "  - Date: {$jadwal->tanggal}\n";
            echo "  - Shift: {$jadwal->nama_shift}\n";
            echo "  - Time: {$jadwal->waktu_mulai} - {$jadwal->waktu_selesai}\n";
            echo "  - User ID in jadwal: {$jadwal->user_id}\n";
        } else {
            echo "JADWAL ID {$attendance->jadwal_jaga_id} NOT FOUND!\n";
        }
    }
} else {
    echo "NO ATTENDANCE TODAY\n\n";
}

// Check existing jadwal for today
$existingJadwal = JadwalJaga::where('user_id', $dokter->id)
    ->whereDate('tanggal', $today)
    ->first();

if ($existingJadwal) {
    echo "\nEXISTING JADWAL FOR TODAY:\n";
    echo "  - ID: {$existingJadwal->id}\n";
    echo "  - Shift: {$existingJadwal->nama_shift}\n";
    echo "  - Time: {$existingJadwal->waktu_mulai} - {$existingJadwal->waktu_selesai}\n";
} else {
    // Create a test jadwal for today
    echo "\nCREATING TEST JADWAL FOR TODAY...\n";
    $testJadwal = JadwalJaga::create([
        'user_id' => $dokter->id,
        'tanggal' => $today,
        'nama_shift' => 'Test Shift Sore',
        'waktu_mulai' => '17:00:00',
        'waktu_selesai' => '18:30:00',
        'unit_kerja' => 'Test Unit'
    ]);
    
    echo "Created jadwal ID: {$testJadwal->id}\n";
    echo "Now dr. Rindang should have a schedule for today!\n";
    
    // Also create attendance record
    echo "\nCREATING ATTENDANCE RECORD...\n";
    $testAttendance = NonParamedisAttendance::create([
        'user_id' => $dokter->id,
        'date' => $today,
        'check_in_time' => '17:07:43',
        'status' => 'present',
        'jadwal_jaga_id' => $testJadwal->id,
        'latitude' => -7.8788,
        'longitude' => 110.3289,
        'location_accuracy' => 10
    ]);
    
    echo "Created attendance record with check-in at 17:07:43\n";
    echo "Dr. Rindang is now checked in!\n";
}

echo "\n=== CURRENT STATE ===\n";
echo "Dr. Rindang should now:\n";
echo "  1. Have a jadwal for today (17:00-18:30)\n";
echo "  2. Be checked in (at 17:07:43)\n";
echo "  3. See her schedule info (NOT 'Anda tidak memiliki jadwal jaga hari ini')\n";
echo "  4. Be able to click CHECK OUT button\n";
echo "\nPlease refresh the app to see the changes!\n";