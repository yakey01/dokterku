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
    <title>📱 Test Mobile App - Presensi Dokter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #efe; border-color: #cfc; color: #363; }
        .error { background: #fee; border-color: #fcc; color: #c33; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .button { background: #f59e0b; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #d97706; }
        .mobile-frame { border: 2px solid #ddd; border-radius: 20px; padding: 20px; margin: 20px 0; background: #f8f9fa; }
        .status-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; }
        .status-online { background: #22c55e; }
        .status-offline { background: #ef4444; }
        .status-warning { background: #f59e0b; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>📱 Test Mobile App - Presensi Dokter</h1>
            <p>Testing aplikasi mobile setelah perbaikan buffer waktu presensi</p>
        </div>";

// Status Server
echo "<div class='section info'>
    <h2>🖥️ Status Server</h2>";

$now = Carbon::now();
$currentTime = $now->format('H:i:s');
$currentHour = (int) $now->format('H');

echo "<p><span class='status-indicator status-online'></span><strong>Server Online:</strong> " . $now->format('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Waktu Saat Ini:</strong> {$currentTime}</p>";
echo "<p><strong>Jam:</strong> {$currentHour}</p>";

// Cek apakah Laravel server berjalan
$serverStatus = 'offline';
$serverUrl = 'http://localhost:8000';
$serverResponse = @file_get_contents($serverUrl . '/api/v2/server-time', false, stream_context_create(['http' => ['timeout' => 5]]));

if ($serverResponse !== false) {
    $serverStatus = 'online';
    echo "<p><span class='status-indicator status-online'></span><strong>Laravel Server:</strong> Online</p>";
} else {
    echo "<p><span class='status-indicator status-offline'></span><strong>Laravel Server:</strong> Offline</p>";
    echo "<p class='warning'>⚠️ Pastikan Laravel server berjalan di port 8000</p>";
}

echo "</div>";

// Status Jadwal Yaya
echo "<div class='section info'>
    <h2>👨‍⚕️ Status Jadwal Yaya</h2>";

$yayaUser = User::where('name', 'like', '%Yaya%')->first();
if ($yayaUser) {
    $todaySchedules = JadwalJaga::where('pegawai_id', $yayaUser->id)
        ->whereDate('tanggal_jaga', today())
        ->with('shiftTemplate')
        ->get();

    echo "<p><strong>User:</strong> {$yayaUser->name} (ID: {$yayaUser->id})</p>";
    echo "<p><strong>Total Jadwal Hari Ini:</strong> {$todaySchedules->count()}</p>";

    if ($todaySchedules->count() > 0) {
        echo "<h3>Jadwal Hari Ini:</h3>";
        foreach ($todaySchedules as $schedule) {
            $shiftName = $schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'N/A';
            $jamMasuk = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_masuk : 'N/A';
            $jamPulang = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_pulang : 'N/A';
            
            // Analisis waktu
            $canCheckIn = false;
            $reason = '';
            
            if ($jamMasuk !== 'N/A' && $jamPulang !== 'N/A') {
                $startTime = Carbon::parse($jamMasuk);
                $endTime = Carbon::parse($jamPulang);
                $currentTimeObj = Carbon::parse($currentTime);
                
                $shiftDuration = $endTime->diffInMinutes($startTime);
                $bufferMinutes = $shiftDuration <= 30 ? 60 : 30;
                
                $startTimeWithBuffer = $startTime->copy()->subMinutes($bufferMinutes);
                $endTimeWithBuffer = $endTime->copy()->addMinutes($bufferMinutes);
                
                $isWithinBuffer = $currentTimeObj->format('H:i:s') >= $startTimeWithBuffer->format('H:i:s') && 
                                 $currentTimeObj->format('H:i:s') <= $endTimeWithBuffer->format('H:i:s');
                
                $isNightShift = $currentHour >= 22 || $currentHour <= 6;
                
                if ($isWithinBuffer) {
                    $canCheckIn = true;
                    $reason = 'Dalam range buffer';
                } elseif ($isNightShift && $currentHour >= 22) {
                    $canCheckIn = true;
                    $reason = 'Shift malam';
                } else {
                    $canCheckIn = false;
                    $reason = 'Di luar range waktu';
                }
            }
            
            $statusClass = $canCheckIn ? 'success' : 'error';
            $statusIcon = $canCheckIn ? '✅' : '❌';
            
            echo "<div class='{$statusClass}'>";
            echo "<p><strong>{$statusIcon} {$shiftName}:</strong> {$jamMasuk} - {$jamPulang}</p>";
            echo "<p><strong>Status:</strong> " . ($canCheckIn ? 'BISA CHECK-IN' : 'TIDAK BISA CHECK-IN') . "</p>";
            echo "<p><strong>Alasan:</strong> {$reason}</p>";
            echo "<p><strong>Buffer:</strong> {$bufferMinutes} menit</p>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>❌ Tidak ada jadwal jaga hari ini!</p>";
    }
} else {
    echo "<p class='error'>❌ User Yaya tidak ditemukan!</p>";
}
echo "</div>";

// Link Mobile App
echo "<div class='section success'>
    <h2>📱 Link Mobile App</h2>";

echo "<div class='mobile-frame'>
    <h3>🚀 Mobile App yang Sudah Diperbaiki</h3>";
echo "<p>Berikut adalah link untuk test mobile app yang sudah diperbaiki:</p>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='http://localhost:8000/dokter/mobile-app' target='_blank' class='button' style='font-size: 18px; padding: 15px 30px; margin: 10px;'>
    📱 Mobile App LAMA (Fixed)
</a>";
echo "<br>";
echo "<a href='http://localhost:8000/dokter/mobile-app-simple' target='_blank' class='button' style='font-size: 16px; padding: 12px 25px; margin: 10px; background: #3b82f6;'>
    📱 Mobile App BARU (Simple)
</a>";
echo "</div>";

echo "<h3>🔐 Login Credentials:</h3>";
echo "<div class='code'>
Email: dd@cc.com
Password: password123
</div>";

echo "<h3>📋 Langkah Test:</h3>";
echo "<ol>
    <li>Klik salah satu link mobile app di atas</li>
    <li>Login dengan credentials yang diberikan</li>
    <li>Masuk ke tab 'Presensi'</li>
    <li>Coba lakukan check-in</li>
    <li>Periksa apakah masih muncul pesan 'Saat ini bukan jam jaga Anda'</li>
</ol>";
echo "</div>";
echo "</div>";

// Test API Endpoint
echo "<div class='section info'>
    <h2>🌐 Test API Endpoint</h2>";

echo "<p>Test langsung API endpoint untuk validasi check-in:</p>";

echo "<div class='code'>
// Test API Check-in
POST http://localhost:8000/api/v2/dashboards/dokter/checkin
Headers:
  Authorization: Bearer {token}
  Content-Type: application/json
  X-CSRF-TOKEN: {csrf_token}

Body:
{
  'latitude': -7.8235,
  'longitude': 112.0178,
  'location': 'Klinik Dokterku, Mojo Kediri',
  'accuracy': 50
}
</div>";

echo "<h3>Expected Response (Setelah Fix):</h3>";
echo "<div class='code'>
// Jika berhasil:
{
  'success': true,
  'message': 'Check-in berhasil',
  'data': {
    'attendance': {...},
    'schedule': {
      'id': 123,
      'shift_name': 'Shift Malam Test',
      'start_time': '22:00',
      'end_time': '06:00',
      'unit_kerja': 'Dokter Jaga'
    }
  }
}

// Jika masih gagal:
{
  'success': false,
  'message': 'Saat ini bukan jam jaga Anda...',
  'code': 'OUTSIDE_SHIFT_HOURS',
  'debug_info': {...}
}
</div>";

echo "<a href='test-api-endpoint.php' class='button'>🧪 Test API Endpoint</a>";
echo "</div>";

// Troubleshooting
echo "<div class='section warning'>
    <h2>🔧 Troubleshooting</h2>";

echo "<h3>Jika Masih Ada Masalah:</h3>";
echo "<ol>
    <li><strong>Server Tidak Berjalan:</strong> Jalankan <code>php artisan serve --port=8000</code></li>
    <li><strong>Buffer Masih Kecil:</strong> Perpanjang buffer menjadi 90-120 menit</li>
    <li><strong>Jadwal Tidak Ada:</strong> Buat jadwal shift malam untuk Yaya</li>
    <li><strong>Cache Browser:</strong> Clear cache browser atau buka incognito</li>
    <li><strong>Token Expired:</strong> Login ulang untuk mendapatkan token baru</li>
</ol>";

echo "<h3>Debug Commands:</h3>";
echo "<div class='code'>
# Cek jadwal Yaya
php artisan tinker --execute=\"echo 'Jadwal Yaya: '; \$jadwal = \App\Models\JadwalJaga::where('pegawai_id', 13)->whereDate('tanggal_jaga', today())->first(); echo \$jadwal ? 'ADA' : 'TIDAK ADA'; echo PHP_EOL;\"

# Cek waktu server
php artisan tinker --execute=\"echo 'Server Time: ' . now()->format('Y-m-d H:i:s') . PHP_EOL;\"

# Cek log error
tail -f storage/logs/laravel.log
</div>";
echo "</div>";

// Quick Actions
echo "<div class='section success'>
    <h2>⚡ Quick Actions</h2>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='root-analysis-presensi.php' class='button'>🔍 Root Analysis</a>";
echo "<a href='fix-presensi-buffer.php' class='button'>🛠️ Fix Buffer</a>";
echo "<a href='create-night-shift.php' class='button'>📅 Create Night Shift</a>";
echo "<a href='test-buffer-fix.php' class='button'>🧪 Test Buffer Fix</a>";
echo "</div>";

echo "<p><strong>Status Perbaikan:</strong></p>";
echo "<ul>
    <li>✅ Buffer waktu diperpanjang dari 5 menit menjadi 30-60 menit</li>
    <li>✅ Logika shift malam ditambahkan</li>
    <li>✅ Mobile app lama dan baru sudah diperbaiki</li>
    <li>✅ JavaScript errors sudah diperbaiki</li>
    <li>✅ Infinite loop sudah diperbaiki</li>
</ul>";
echo "</div>";

echo "</div>
</body>
</html>";
?>
