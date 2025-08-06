<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Pegawai;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use Carbon\Carbon;

// Find Siti
$siti = User::find(23); // Siti Rahayu

if (!$siti) {
    die("❌ Siti not found\n");
}

echo "👤 Found user: " . $siti->name . " (ID: " . $siti->id . ")\n\n";

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
    echo "   - Kode: " . $pegawai->kode_pegawai . "\n";
    echo "   - Jabatan: " . $pegawai->jabatan . "\n\n";
} else {
    echo "✅ Pegawai record already exists\n";
    echo "   - Kode: " . $pegawai->kode_pegawai . "\n\n";
}

// Check existing Tindakan
$existingTindakan = Tindakan::where('paramedis_id', $pegawai->id)
    ->where('status_validasi', 'pending')
    ->count();

echo "📊 Existing pending Tindakan: " . $existingTindakan . "\n\n";

// Create some pending Tindakan for testing
$jenisTindakan = JenisTindakan::where('persentase_jaspel', '>', 0)->first();

if ($jenisTindakan) {
    // Create 2 pending tindakan for today
    for ($i = 1; $i <= 2; $i++) {
        $tarif = 150000 + ($i * 50000);
        
        $tindakan = Tindakan::create([
            'paramedis_id' => $pegawai->id,
            'pasien_id' => 1, // Use existing pasien
            'shift_id' => 1, // Use morning shift
            'jenis_tindakan_id' => $jenisTindakan->id,
            'tanggal_tindakan' => Carbon::now(),
            'tarif' => $tarif,
            'status_validasi' => 'pending',
            'created_by' => $siti->id,
            'input_by' => $siti->id
        ]);
        
        $expectedJaspel = $tindakan->tarif * ($jenisTindakan->persentase_jaspel / 100);
        
        echo "✅ Created pending Tindakan #" . $i . ":\n";
        echo "   - Jenis: " . $jenisTindakan->nama . "\n";
        echo "   - Tarif: Rp " . number_format($tindakan->tarif) . "\n";
        echo "   - Persentase Jaspel: " . $jenisTindakan->persentase_jaspel . "%\n";
        echo "   - Expected Jaspel: Rp " . number_format($expectedJaspel) . "\n\n";
    }
}

// Test the API endpoint
echo "🔍 Testing API endpoint...\n";
$ch = curl_init('http://127.0.0.1:8000/test-paramedis-dashboard-api');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data) {
    echo "✅ API Response:\n";
    echo "   - Jaspel Monthly: Rp " . number_format($data['jaspel_monthly']) . "\n";
    echo "   - Pending: Rp " . number_format($data['pending_jaspel']) . "\n";
    echo "   - Approved: Rp " . number_format($data['approved_jaspel']) . "\n";
    echo "   - Growth: " . $data['growth_percent'] . "%\n";
}

echo "\n🎉 Siti should now see her Jaspel data in the dashboard!\n";
echo "📱 Please ask Siti to:\n";
echo "   1. Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)\n";
echo "   2. Access: http://127.0.0.1:8000/paramedis/dashboard-new\n";