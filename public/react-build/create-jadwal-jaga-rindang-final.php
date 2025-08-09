<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\JadwalJaga;
use App\Models\User;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

echo "=== CREATING JADWAL JAGA FOR RINDANG (FINAL FIX) ===\n\n";

$today = Carbon::now('Asia/Jakarta');

// Find Rindang
$rindang = User::find(14);
if (!$rindang) {
    echo "❌ Rindang not found!\n";
    exit;
}

echo "Found user: {$rindang->name} (ID: {$rindang->id})\n";
echo "Role: {$rindang->role->name}\n\n";

// Check if jadwal already exists for today
$existingJadwal = JadwalJaga::where('pegawai_id', $rindang->id)
    ->whereDate('tanggal_jaga', $today)
    ->first();

if ($existingJadwal) {
    echo "✅ Jadwal already exists for today!\n";
    echo "ID: {$existingJadwal->id}\n";
    echo "Date: {$existingJadwal->tanggal_jaga->format('Y-m-d')}\n";
    echo "Status: {$existingJadwal->status_jaga}\n";
    
    if ($existingJadwal->shiftTemplate) {
        echo "Shift: {$existingJadwal->shiftTemplate->nama}\n";
        echo "Time: {$existingJadwal->shiftTemplate->jam_masuk} - {$existingJadwal->shiftTemplate->jam_pulang}\n";
    }
} else {
    // Get or create shift template
    $shiftTemplate = ShiftTemplate::first();
    
    if (!$shiftTemplate) {
        echo "Creating default shift template...\n";
        $shiftTemplate = ShiftTemplate::create([
            'nama' => 'Shift Pagi',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '16:00:00',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    echo "Using shift: {$shiftTemplate->nama} ({$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang})\n\n";
    
    // Create jadwal jaga
    $jadwal = JadwalJaga::create([
        'pegawai_id' => $rindang->id,
        'tanggal_jaga' => $today->format('Y-m-d'),
        'shift_template_id' => $shiftTemplate->id,
        'unit_kerja' => 'Dokter Jaga',
        'peran' => 'Dokter',
        'status_jaga' => 'Aktif',
        'keterangan' => 'Created for check-in capability'
    ]);
    
    echo "✅ JADWAL JAGA CREATED SUCCESSFULLY!\n";
    echo "ID: {$jadwal->id}\n";
    echo "Pegawai: {$rindang->name}\n";
    echo "Date: {$jadwal->tanggal_jaga->format('Y-m-d')}\n";
    echo "Shift: {$shiftTemplate->nama}\n";
    echo "Time: {$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang}\n";
    echo "Status: {$jadwal->status_jaga}\n";
}

// Verify in controller's method
echo "\n=== VERIFICATION ===\n";

// Check what the API will return
$nextSchedule = JadwalJaga::where('pegawai_id', $rindang->id)
    ->where('tanggal_jaga', '>=', Carbon::today())
    ->with(['shiftTemplate'])
    ->orderBy('tanggal_jaga')
    ->first();

if ($nextSchedule) {
    echo "✓ Next schedule found by API logic\n";
    echo "Date: {$nextSchedule->tanggal_jaga->format('Y-m-d')}\n";
    
    if ($nextSchedule->shiftTemplate) {
        echo "Shift: {$nextSchedule->shiftTemplate->nama}\n";
        echo "Time: {$nextSchedule->shiftTemplate->jam_masuk} - {$nextSchedule->shiftTemplate->jam_pulang}\n";
    }
    
    // Check if it's today
    if ($nextSchedule->tanggal_jaga->isToday()) {
        echo "\n🎉 SCHEDULE IS FOR TODAY - RINDANG CAN CHECK IN NOW!\n";
    }
} else {
    echo "❌ No schedule found by API logic\n";
}

// Check attendance
$attendance = \App\Models\DokterPresensi::where('dokter_id', $rindang->id)
    ->whereDate('tanggal', $today)
    ->first();

if ($attendance) {
    echo "\nAttendance record exists:\n";
    echo "- Check In: " . ($attendance->check_in ?? 'Not yet') . "\n";
    echo "- Check Out: " . ($attendance->check_out ?? 'Not yet') . "\n";
} else {
    echo "\n📱 No attendance yet - Ready for check-in via /dokter/mobile-app\n";
}

echo "\n=== DONE ===\n";
?>