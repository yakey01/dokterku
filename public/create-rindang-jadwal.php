<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// First, let's check what shift templates exist
$shifts = DB::table('shift_templates')->get();
echo "Available shifts:\n";
foreach ($shifts as $shift) {
    echo "ID: {$shift->id}, Name: {$shift->nama}, Start: {$shift->jam_masuk}, End: {$shift->jam_pulang}\n";
}

// Check if Rindang has jadwal for today
$today = date('Y-m-d');
$existing = DB::table('jadwal_jagas')
    ->where('dokter_id', 14)
    ->whereDate('tanggal', $today)
    ->first();

if ($existing) {
    echo "\nRindang already has jadwal for today (ID: {$existing->id})\n";
} else {
    // Get first shift or create one
    $shiftId = 1; // Use first shift template
    
    // If no shifts exist, create a default one
    if ($shifts->isEmpty()) {
        $shiftId = DB::table('shift_templates')->insertGetId([
            'nama' => 'Shift Pagi',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '16:00:00',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "\nCreated default shift template (ID: {$shiftId})\n";
    }
    
    // Create jadwal for Rindang
    $jadwalId = DB::table('jadwal_jagas')->insertGetId([
        'dokter_id' => 14,
        'tanggal' => $today,
        'shift_template_id' => $shiftId,
        'status' => 'scheduled',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "\n✅ SUCCESS! Created jadwal for Rindang:\n";
    echo "- Jadwal ID: {$jadwalId}\n";
    echo "- Date: {$today}\n";
    echo "- Shift Template ID: {$shiftId}\n";
    echo "- Status: scheduled\n";
    echo "\n🎉 Rindang can now check in!\n";
}

// Verify the jadwal exists
$jadwal = DB::table('jadwal_jagas')
    ->where('dokter_id', 14)
    ->whereDate('tanggal', $today)
    ->first();

if ($jadwal) {
    echo "\nVerification: Jadwal exists for today ✓\n";
    
    // Also check if presensi record needs to be created
    $presensi = DB::table('dokter_presensis')
        ->where('dokter_id', 14)
        ->whereDate('tanggal', $today)
        ->first();
    
    if (!$presensi) {
        echo "Note: No attendance record yet - Rindang needs to check in\n";
    } else {
        echo "Attendance record exists - Check In: " . ($presensi->check_in ?? 'Not yet') . "\n";
    }
}
?>