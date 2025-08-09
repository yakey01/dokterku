<?php
/**
 * Create Schedule for Dokter User
 * Membuat jadwal untuk user dokter agar bisa test check-in
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

echo "\n" . str_repeat("=", 60) . "\n";
echo "CREATE SCHEDULE FOR DOKTER USER\n";
echo str_repeat("=", 60) . "\n\n";

// Find dokter user
$doctor = User::whereHas('roles', function($q) {
    $q->where('name', 'dokter');
})->where('email', 'dokter@dokterku.com')->first();

if (!$doctor) {
    echo "❌ Doctor user not found\n";
    exit(1);
}

echo "Doctor: {$doctor->name} (ID: {$doctor->id})\n";
echo "Email: {$doctor->email}\n\n";

// Create or find shift template for current time
$now = Carbon::now('Asia/Jakarta');
// Set start time to 30 minutes ago so we're within check-in window
$startTime = $now->copy()->addMinutes(10)->format('H:i'); // Start in 10 minutes
$endTime = $now->copy()->addHours(3)->format('H:i'); // End in 3 hours

echo "Creating shift for current time:\n";
echo "  Start: {$startTime}\n";
echo "  End: {$endTime}\n\n";

// Create shift template
$shiftTemplate = ShiftTemplate::firstOrCreate([
    'nama_shift' => 'Shift Test Check-In'
], [
    'jam_masuk' => $startTime . ':00',
    'jam_pulang' => $endTime . ':00',
    'durasi_jam' => 2,
    'warna' => '#22c55e'
]);

echo "Shift Template: {$shiftTemplate->nama_shift}\n";
echo "  ID: {$shiftTemplate->id}\n";
echo "  Time: {$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang}\n\n";

// Delete existing schedule for today
JadwalJaga::where('pegawai_id', $doctor->id)
    ->whereDate('tanggal_jaga', Carbon::today())
    ->delete();

// Create new schedule
$schedule = JadwalJaga::create([
    'pegawai_id' => $doctor->id, // This refers to users.id
    'shift_template_id' => $shiftTemplate->id,
    'tanggal_jaga' => Carbon::today(),
    'peran' => 'Dokter',
    'unit_instalasi' => 'Poli Umum',
    'unit_kerja' => 'Dokter Jaga',
    'status_jaga' => 'Aktif',
    'keterangan' => 'Test schedule for check-in'
]);

echo "✅ Schedule created successfully!\n";
echo "  Schedule ID: {$schedule->id}\n";
echo "  Date: {$schedule->tanggal_jaga}\n";
echo "  Unit: {$schedule->unit_instalasi}\n";
echo "  Peran: {$schedule->peran}\n\n";

// Clear cache
$cacheKey = 'dokter_schedule_' . $doctor->id;
\Illuminate\Support\Facades\Cache::forget($cacheKey);
echo "✅ Cache cleared\n\n";

// Verify the schedule
$verify = JadwalJaga::with('shiftTemplate')
    ->where('pegawai_id', $doctor->id)
    ->whereDate('tanggal_jaga', Carbon::today())
    ->first();

if ($verify) {
    echo "✅ VERIFICATION SUCCESSFUL\n";
    echo "  Schedule found in database\n";
    echo "  Shift: {$verify->shiftTemplate->nama_shift}\n";
    echo "  Time: {$verify->shiftTemplate->jam_masuk} - {$verify->shiftTemplate->jam_pulang}\n\n";
    
    // Check if within check-in window
    $jamMasuk = $verify->shiftTemplate->jam_masuk;
    // Remove date prefix if already exists
    if (strpos($jamMasuk, ' ') !== false) {
        $jamMasuk = explode(' ', $jamMasuk)[1];
    }
    $startTime = Carbon::parse(Carbon::today()->toDateString() . ' ' . $jamMasuk);
    $earliestCheckin = $startTime->copy()->subMinutes(60); // 60 minutes before
    $latestCheckin = $startTime->copy()->addMinutes(15); // 15 minutes after
    
    echo "Check-in Window:\n";
    echo "  Earliest: " . $earliestCheckin->format('H:i:s') . "\n";
    echo "  Latest: " . $latestCheckin->format('H:i:s') . "\n";
    echo "  Now: " . $now->format('H:i:s') . "\n";
    
    $canCheckIn = $now->gte($earliestCheckin) && $now->lte($latestCheckin);
    echo "\n🎯 Can Check-In Now: " . ($canCheckIn ? "✅ YES" : "❌ NO") . "\n";
} else {
    echo "❌ VERIFICATION FAILED - Schedule not found\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "NEXT STEPS:\n";
echo str_repeat("=", 60) . "\n";
echo "1. Refresh the doctor's presensi page\n";
echo "2. The schedule should now appear\n";
echo "3. Check-in button should be enabled\n";
echo "4. Try to check-in within the time window\n";
echo "\n";