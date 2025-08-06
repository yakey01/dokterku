<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test jadwal jaga functionality
echo "<h2>Test Jadwal Jaga System</h2>";

// 1. Check Shift Templates
echo "<h3>1. Shift Templates Available:</h3>";
$shifts = \App\Models\ShiftTemplate::all();
echo "<ul>";
foreach ($shifts as $shift) {
    echo "<li>{$shift->nama_shift} ({$shift->jam_masuk} - {$shift->jam_pulang})</li>";
}
echo "</ul>";
echo "<p>Total: " . $shifts->count() . " shift templates</p>";

// 2. Check Pegawai
echo "<h3>2. Staff Available:</h3>";
echo "<h4>Dokter:</h4>";
$dokters = \App\Models\Dokter::where('aktif', true)->get();
echo "<ul>";
foreach ($dokters as $dokter) {
    $userId = $dokter->user_id ? " (User ID: {$dokter->user_id})" : " <span style='color:red'>(NO USER ACCOUNT)</span>";
    echo "<li>{$dokter->nama_lengkap}{$userId}</li>";
}
echo "</ul>";
echo "<p>Total: " . $dokters->count() . " active doctors</p>";

echo "<h4>Pegawai (Paramedis & Non-Paramedis):</h4>";
$pegawais = \App\Models\Pegawai::where('aktif', true)->get();
echo "<ul>";
foreach ($pegawais as $pegawai) {
    $userId = $pegawai->user_id ? " (User ID: {$pegawai->user_id})" : " <span style='color:red'>(NO USER ACCOUNT)</span>";
    echo "<li>{$pegawai->nama_lengkap} - {$pegawai->jenis_pegawai}{$userId}</li>";
}
echo "</ul>";
echo "<p>Total: " . $pegawais->count() . " active staff</p>";

// 3. Check Recent Jadwal
echo "<h3>3. Recent Jadwal Jaga (Last 10):</h3>";
$jadwals = \App\Models\JadwalJaga::with(['pegawai', 'shiftTemplate'])
    ->orderBy('tanggal_jaga', 'desc')
    ->take(10)
    ->get();
    
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Tanggal</th><th>Pegawai</th><th>Shift</th><th>Unit</th><th>Status</th></tr>";
foreach ($jadwals as $jadwal) {
    echo "<tr>";
    echo "<td>" . $jadwal->tanggal_jaga->format('d/m/Y') . "</td>";
    echo "<td>" . ($jadwal->pegawai ? $jadwal->pegawai->name : 'N/A') . "</td>";
    echo "<td>" . ($jadwal->shiftTemplate ? $jadwal->shiftTemplate->nama_shift : 'N/A') . "</td>";
    echo "<td>" . $jadwal->unit_kerja . "</td>";
    echo "<td>" . $jadwal->status_jaga . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Test Create Jadwal
echo "<h3>4. Test Create Jadwal Jaga:</h3>";
try {
    // Get first available dokter with user account
    $dokter = \App\Models\Dokter::whereNotNull('user_id')->first();
    $shift = \App\Models\ShiftTemplate::first();
    
    if ($dokter && $shift) {
        echo "<p>Testing with: {$dokter->nama_lengkap} for shift {$shift->nama_shift}</p>";
        
        // Check if can create
        $testDate = now()->addDay()->format('Y-m-d');
        $exists = \App\Models\JadwalJaga::where('tanggal_jaga', $testDate)
            ->where('pegawai_id', $dokter->user_id)
            ->where('shift_template_id', $shift->id)
            ->exists();
            
        if ($exists) {
            echo "<p style='color:orange'>Schedule already exists for this date/shift/person combination</p>";
        } else {
            echo "<p style='color:green'>Can create new schedule for {$testDate}</p>";
        }
    } else {
        echo "<p style='color:red'>Missing required data (dokter with user account or shift template)</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 5. Check Permissions
echo "<h3>5. Check Admin Panel Access:</h3>";
echo "<p><a href='/admin/jadwal-jagas' target='_blank'>Go to Jadwal Jaga Admin Panel</a></p>";
echo "<p>Make sure you are logged in as admin to access this page.</p>";

$kernel->terminate($request, $response);