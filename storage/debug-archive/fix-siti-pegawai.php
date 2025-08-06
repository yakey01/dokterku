<?php

use App\Models\User;
use App\Models\Pegawai;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use Carbon\Carbon;

// Find Siti
$siti = User::find(23); // Siti Rahayu

if (!$siti) {
    die("Siti not found\n");
}

// Check if Pegawai exists
$pegawai = Pegawai::where('user_id', $siti->id)->first();

if (!$pegawai) {
    // Create Pegawai record for Siti
    $pegawai = Pegawai::create([
        'user_id' => $siti->id,
        'kode_pegawai' => 'PRM-' . str_pad($siti->id, 4, '0', STR_PAD_LEFT),
        'nama' => $siti->name,
        'jabatan' => 'Paramedis',
        'departemen' => 'Keperawatan',
        'tanggal_masuk' => Carbon::now()->subYears(2),
        'status' => 'aktif'
    ]);
    
    echo "✅ Created Pegawai record for Siti\n";
} else {
    echo "✅ Pegawai record already exists for Siti\n";
}

// Create some pending Tindakan for testing
$jenisTindakan = JenisTindakan::first();

if ($jenisTindakan) {
    // Create a pending tindakan for today
    $tindakan = Tindakan::create([
        'paramedis_id' => $pegawai->id,
        'jenis_tindakan_id' => $jenisTindakan->id,
        'tanggal' => Carbon::now(),
        'tarif' => 150000,
        'status_validasi' => 'pending',
        'created_by' => $siti->id
    ]);
    
    $expectedJaspel = $tindakan->tarif * ($jenisTindakan->persentase_jaspel / 100);
    
    echo "✅ Created pending Tindakan:\n";
    echo "   - Tarif: Rp " . number_format($tindakan->tarif) . "\n";
    echo "   - Persentase Jaspel: " . $jenisTindakan->persentase_jaspel . "%\n";
    echo "   - Expected Jaspel: Rp " . number_format($expectedJaspel) . "\n";
}

echo "\n🎉 Siti should now see her Jaspel data in the dashboard!\n";