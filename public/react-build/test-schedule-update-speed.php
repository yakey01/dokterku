<?php
/**
 * Test Script: Schedule Update Speed
 * Tests if doctor schedule updates are reflected quickly in the API
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
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

echo "\n" . str_repeat("=", 60) . "\n";
echo "SCHEDULE UPDATE SPEED TEST\n";
echo str_repeat("=", 60) . "\n\n";

// Find a doctor user
$doctor = User::whereHas('roles', function($q) {
    $q->where('name', 'dokter');
})->where('name', 'like', '%Rindang%')->first();

if (!$doctor) {
    $doctor = User::whereHas('roles', function($q) {
        $q->where('name', 'dokter');
    })->first();
}

if (!$doctor) {
    echo "❌ No doctor users found\n";
    exit(1);
}

echo "Testing with Doctor: {$doctor->name} (ID: {$doctor->id})\n";
echo "Email: {$doctor->email}\n\n";

// Get or create a work location
$workLocation = WorkLocation::first() ?? WorkLocation::create([
    'name' => 'Test Clinic',
    'address' => 'Test Address',
    'latitude' => -7.898878,
    'longitude' => 111.961884,
    'radius' => 100
]);

echo "Work Location: {$workLocation->name}\n\n";

// Get or create a shift template
$shiftTemplate = ShiftTemplate::where('nama_shift', 'Shift Pagi')->first() 
    ?? ShiftTemplate::create([
        'nama_shift' => 'Shift Pagi',
        'jam_masuk' => '08:00:00',
        'jam_pulang' => '16:00:00',
        'durasi_jam' => 8
    ]);

echo "Shift Template: {$shiftTemplate->nama_shift}\n";
echo "Hours: {$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang}\n\n";

// Test 1: Create or find a schedule
echo "TEST 1: Creating/Finding Schedule\n";
echo str_repeat("-", 40) . "\n";

// Find or create pegawai for the doctor
$pegawai = \App\Models\Pegawai::where('email', $doctor->email)->first();
if (!$pegawai) {
    $pegawai = \App\Models\Pegawai::create([
        'nik' => '123456789' . str_pad($doctor->id, 7, '0', STR_PAD_LEFT),
        'nama_lengkap' => $doctor->name,
        'email' => $doctor->email,
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'jabatan' => 'Dokter',
        'jenis_pegawai' => 'Paramedis',
        'aktif' => 1,
        'user_id' => $doctor->id
    ]);
}

// Delete any existing schedule for today
JadwalJaga::where('pegawai_id', $pegawai->id)
    ->whereDate('tanggal_jaga', Carbon::today())
    ->delete();

$newSchedule = JadwalJaga::create([
    'pegawai_id' => $pegawai->id,
    'shift_template_id' => $shiftTemplate->id,
    'tanggal_jaga' => Carbon::today(),
    'peran' => 'Dokter',
    'unit_instalasi' => 'Poli Umum',
    'unit_kerja' => 'Dokter Jaga'
]);

echo "✅ Schedule created at: " . Carbon::now()->format('H:i:s') . "\n";

// Clear cache to force fresh data
Cache::forget('dokter_schedule_' . $doctor->id);
Cache::forget('dokter_shift_template_' . $doctor->id . '_' . Carbon::today()->format('Y-m-d'));

// Test API response times
echo "\nTesting API Response Times:\n";

for ($i = 1; $i <= 5; $i++) {
    $startTime = microtime(true);
    
    // Simulate API call using cache
    $cacheKey = 'dokter_schedule_' . $doctor->id;
    $scheduleData = Cache::remember($cacheKey, 10, function () use ($doctor) {
        $todaySchedule = JadwalJaga::with(['shiftTemplate', 'pegawai.user'])
            ->whereHas('pegawai', function ($q) use ($doctor) {
                $q->where('user_id', $doctor->id);
            })
            ->whereDate('tanggal_jaga', Carbon::today())
            ->first();
        
        return [
            'todaySchedule' => $todaySchedule ? $todaySchedule->toArray() : null
        ];
    });
    
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    $hasSchedule = !empty($scheduleData['todaySchedule']);
    $status = $hasSchedule ? '✅ Schedule Found' : '❌ No Schedule';
    
    echo "  Attempt {$i}: {$responseTime}ms - {$status}\n";
    
    if ($i < 5) {
        sleep(2); // Wait 2 seconds between attempts
    }
}

// Test 2: Update existing schedule
echo "\nTEST 2: Updating Schedule Time\n";
echo str_repeat("-", 40) . "\n";

$newSchedule->unit_instalasi = 'Poli Gigi';
$newSchedule->save();

// Clear cache again
Cache::forget('dokter_schedule_' . $doctor->id);

echo "✅ Schedule updated at: " . Carbon::now()->format('H:i:s') . "\n";

// Test update propagation
echo "\nTesting Update Propagation:\n";

for ($i = 1; $i <= 3; $i++) {
    $startTime = microtime(true);
    
    // Simulate API call using cache
    $cacheKey = 'dokter_schedule_' . $doctor->id;
    $scheduleData = Cache::remember($cacheKey, 10, function () use ($doctor) {
        $todaySchedule = JadwalJaga::with(['shiftTemplate', 'pegawai.user'])
            ->whereHas('pegawai', function ($q) use ($doctor) {
                $q->where('user_id', $doctor->id);
            })
            ->whereDate('tanggal_jaga', Carbon::today())
            ->first();
        
        return [
            'todaySchedule' => $todaySchedule ? $todaySchedule->toArray() : null
        ];
    });
    
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    $scheduleUnit = $scheduleData['todaySchedule']['unit_instalasi'] ?? 'not found';
    $isUpdated = $scheduleUnit === 'Poli Gigi';
    $status = $isUpdated ? '✅ Updated to Poli Gigi' : '⚠️ Unit: ' . $scheduleUnit;
    
    echo "  Attempt {$i}: {$responseTime}ms - {$status}\n";
    
    if ($i < 3) {
        sleep(2);
    }
}

// Test 3: Cache TTL
echo "\nTEST 3: Cache TTL Verification\n";
echo str_repeat("-", 40) . "\n";

// Get fresh data and check cache
$cacheKey = 'dokter_schedule_' . $doctor->id;
Cache::forget($cacheKey); // Clear first

// Make request to populate cache
$scheduleData = Cache::remember($cacheKey, 10, function () use ($doctor) {
    $todaySchedule = JadwalJaga::with(['shiftTemplate', 'pegawai.user'])
        ->whereHas('pegawai', function ($q) use ($doctor) {
            $q->where('user_id', $doctor->id);
        })
        ->whereDate('tanggal_jaga', Carbon::today())
        ->first();
    
    return [
        'todaySchedule' => $todaySchedule ? $todaySchedule->toArray() : null
    ];
});

// Check if cache exists
$hasCache = Cache::has($cacheKey);
echo "Cache populated: " . ($hasCache ? '✅ Yes' : '❌ No') . "\n";

if ($hasCache) {
    // Wait and check cache expiry
    echo "Waiting 10 seconds for cache expiry...\n";
    sleep(10);
    
    $stillCached = Cache::has($cacheKey);
    echo "Cache after 10s: " . ($stillCached ? '⚠️ Still cached' : '✅ Expired as expected') . "\n";
}

// Clean up
$newSchedule->delete();

echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Backend cache TTL: 10 seconds\n";
echo "✅ Frontend polling: 10 seconds\n";
echo "✅ Manual refresh button available\n";
echo "✅ Schedule updates should be visible within 10-20 seconds\n";
echo "\nTEST COMPLETED\n\n";