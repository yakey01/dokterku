<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use Carbon\Carbon;

echo "<!DOCTYPE html>
<html>
<head>
    <title>🛠️ Fix Presensi Buffer - Dokter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #efe; border-color: #cfc; color: #363; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .button { background: #22c55e; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #16a34a; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🛠️ Fix Presensi Buffer - Dokter</h1>
            <p>Memperbaiki masalah buffer waktu presensi untuk dokter</p>
        </div>";

// Analisis masalah
echo "<div class='section info'>
    <h2>🔍 Analisis Masalah</h2>";

$now = Carbon::now();
$currentTime = $now->format('H:i:s');
$yayaUser = User::where('name', 'like', '%Yaya%')->first();

echo "<p><strong>Waktu Saat Ini:</strong> {$currentTime}</p>";

if ($yayaUser) {
    $todaySchedules = JadwalJaga::where('pegawai_id', $yayaUser->id)
        ->whereDate('tanggal_jaga', today())
        ->with('shiftTemplate')
        ->get();

    echo "<p><strong>Total Jadwal Yaya Hari Ini:</strong> {$todaySchedules->count()}</p>";

    if ($todaySchedules->count() > 0) {
        echo "<h3>Jadwal Hari Ini:</h3>";
        foreach ($todaySchedules as $schedule) {
            $shiftName = $schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'N/A';
            $jamMasuk = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_masuk : 'N/A';
            $jamPulang = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_pulang : 'N/A';
            
            echo "<p>• <strong>{$shiftName}:</strong> {$jamMasuk} - {$jamPulang}</p>";
        }
    }
}
echo "</div>";

// Solusi 1: Perpanjang Buffer
echo "<div class='section success'>
    <h2>🛠️ Solusi 1: Perpanjang Buffer Waktu</h2>";

echo "<p>Masalah utama adalah buffer waktu yang terlalu kecil (5 menit). Mari kita perpanjang menjadi 30-60 menit.</p>";

echo "<h3>Kode Perbaikan:</h3>";
echo "<div class='code'>
// Di file: app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php
// Method: checkIn()

// Ganti baris ini:
\$bufferMinutes = 5;

// Menjadi:
\$bufferMinutes = 30; // Perpanjang buffer menjadi 30 menit

// Atau untuk shift pendek, gunakan buffer lebih besar:
if (\$shiftTemplate) {
    \$shiftDuration = \$endTime->diffInMinutes(\$startTime);
    if (\$shiftDuration <= 30) {
        // Untuk shift pendek (≤30 menit), berikan buffer 60 menit
        \$bufferMinutes = 60;
    } else {
        // Untuk shift normal, berikan buffer 30 menit
        \$bufferMinutes = 30;
    }
}
</div>";

echo "<a href='test-buffer-fix.php' class='button'>🧪 Test Buffer Fix</a>";
echo "</div>";

// Solusi 2: Buat Jadwal Shift Malam
echo "<div class='section success'>
    <h2>🛠️ Solusi 2: Buat Jadwal Shift Malam</h2>";

echo "<p>Buat jadwal shift malam untuk Yaya agar bisa check-in sekarang.</p>";

echo "<h3>Jadwal yang Direkomendasikan:</h3>";
echo "<div class='code'>
// Shift Malam 1: 22:00 - 06:00 (besok)
// Shift Malam 2: 20:00 - 02:00 (besok)
// Shift Malam 3: 23:00 - 07:00 (besok)
</div>";

echo "<a href='create-night-shift.php' class='button'>📅 Buat Shift Malam</a>";
echo "</div>";

// Solusi 3: Fleksibilitas Waktu
echo "<div class='section success'>
    <h2>🛠️ Solusi 3: Fleksibilitas Waktu</h2>";

echo "<p>Tambahkan logika untuk mengizinkan check-in dalam rentang waktu yang lebih fleksibel.</p>";

echo "<h3>Kode Perbaikan:</h3>";
echo "<div class='code'>
// Tambahkan logika fleksibilitas waktu
\$currentHour = (int) \$currentTime->format('H');

// Izinkan check-in jika:
// 1. Dalam jam jaga, ATAU
// 2. Dalam buffer waktu, ATAU  
// 3. Shift malam (jam 22:00 - 06:00)

\$isNightShift = \$currentHour >= 22 || \$currentHour <= 6;
\$isWithinShift = \$currentTimeOnly >= \$startTimeWithBuffer->format('H:i:s') && 
                 \$currentTimeOnly <= \$endTimeWithBuffer->format('H:i:s');

if (\$isWithinShift || \$isNightShift) {
    // Izinkan check-in
} else {
    // Tolak check-in
}
</div>";
echo "</div>";

// Implementasi Fix
echo "<div class='section info'>
    <h2>🚀 Implementasi Fix</h2>";

echo "<p>Pilih salah satu solusi di atas untuk mengatasi masalah:</p>";

echo "<ol>
    <li><strong>Perpanjang Buffer:</strong> Ubah buffer dari 5 menit menjadi 30-60 menit</li>
    <li><strong>Buat Shift Malam:</strong> Tambahkan jadwal shift malam untuk Yaya</li>
    <li><strong>Fleksibilitas Waktu:</strong> Tambahkan logika untuk shift malam</li>
</ol>";

echo "<h3>Langkah Implementasi:</h3>";
echo "<ol>
    <li>Edit file <code>app/Http/Controllers/Api/V2/Dashboards/DokterDashboardController.php</code></li>
    <li>Cari method <code>checkIn()</code></li>
    <li>Ubah logika buffer dan validasi waktu</li>
    <li>Test dengan jadwal yang ada</li>
</ol>";
echo "</div>";

// Quick Fix untuk Test
echo "<div class='section success'>
    <h2>⚡ Quick Fix untuk Test</h2>";

echo "<p>Untuk test cepat, Anda bisa:</p>";

echo "<h3>1. Buat Jadwal Test:</h3>";
echo "<div class='code'>
// Buat jadwal shift malam untuk Yaya
\$jadwal = new JadwalJaga();
\$jadwal->pegawai_id = 13; // ID Yaya
\$jadwal->tanggal_jaga = today();
\$jadwal->shift_template_id = 1; // ID shift template
\$jadwal->status_jaga = 'Aktif';
\$jadwal->save();
</div>";

echo "<h3>2. Atau Ubah Waktu Server (untuk development):</h3>";
echo "<div class='code'>
// Di file .env, ubah timezone atau gunakan Carbon::setTestNow()
Carbon::setTestNow(Carbon::parse('2025-08-08 17:50:00'));
</div>";

echo "<a href='quick-test-fix.php' class='button'>⚡ Quick Test</a>";
echo "</div>";

echo "</div>
</body>
</html>";
?>
