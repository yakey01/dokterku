<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\Attendance;
use Carbon\Carbon;

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Root Analysis - Masalah Presensi Dokter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .error { background: #fee; border-color: #fcc; color: #c33; }
        .success { background: #efe; border-color: #cfc; color: #363; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f8f9fa; }
        .highlight { background: yellow; padding: 2px; }
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
        .time-valid { color: green; }
        .time-invalid { color: red; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔍 Root Analysis - Masalah Presensi Dokter</h1>
            <p>Analisis mendalam masalah presensi di sistem</p>
        </div>";

// 1. ANALISIS WAKTU SERVER
echo "<div class='section info'>
    <h2>⏰ 1. Analisis Waktu Server</h2>";

$now = Carbon::now();
$serverTime = $now->format('Y-m-d H:i:s');
$currentTimeOnly = $now->format('H:i:s');
$timezone = $now->timezone->getName();

echo "<p><strong>Waktu Server:</strong> <span class='highlight'>{$serverTime}</span></p>";
echo "<p><strong>Jam Saat Ini:</strong> <span class='highlight'>{$currentTimeOnly}</span></p>";
echo "<p><strong>Timezone:</strong> <span class='highlight'>{$timezone}</span></p>";
echo "</div>";

// 2. ANALISIS USER YAYA
echo "<div class='section info'>
    <h2>👨‍⚕️ 2. Analisis User Yaya</h2>";

$yayaUser = User::where('name', 'like', '%Yaya%')->first();
if ($yayaUser) {
    echo "<p><strong>User ID:</strong> {$yayaUser->id}</p>";
    echo "<p><strong>Nama:</strong> {$yayaUser->name}</p>";
    echo "<p><strong>Email:</strong> {$yayaUser->email}</p>";
    echo "<p><strong>Role:</strong> " . json_encode($yayaUser->role) . "</p>";
} else {
    echo "<p class='error'>❌ User Yaya tidak ditemukan!</p>";
}
echo "</div>";

// 3. ANALISIS JADWAL JAGA HARI INI
echo "<div class='section info'>
    <h2>📅 3. Analisis Jadwal Jaga Hari Ini</h2>";

if ($yayaUser) {
    $todaySchedules = JadwalJaga::where('pegawai_id', $yayaUser->id)
        ->whereDate('tanggal_jaga', today())
        ->with('shiftTemplate')
        ->get();

    echo "<p><strong>Total Jadwal Hari Ini:</strong> {$todaySchedules->count()}</p>";

    if ($todaySchedules->count() > 0) {
        echo "<table class='table'>
            <tr>
                <th>ID</th>
                <th>Shift</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Status</th>
                <th>Dalam Jam Jaga</th>
                <th>Analisis</th>
            </tr>";

        foreach ($todaySchedules as $schedule) {
            $shiftName = $schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'N/A';
            $jamMasuk = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_masuk : 'N/A';
            $jamPulang = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_pulang : 'N/A';
            $status = $schedule->status_jaga;

            // Analisis waktu
            $isWithinShift = false;
            $analysis = '';
            
            if ($jamMasuk !== 'N/A' && $jamPulang !== 'N/A') {
                $startTime = Carbon::parse($jamMasuk);
                $endTime = Carbon::parse($jamPulang);
                $currentTime = Carbon::parse($currentTimeOnly);

                // Handle overnight shifts
                if ($endTime->format('H:i:s') < $startTime->format('H:i:s')) {
                    // Overnight shift logic
                    $isWithinShift = $currentTime->format('H:i:s') >= $startTime->format('H:i:s') || 
                                   $currentTime->format('H:i:s') <= $endTime->format('H:i:s');
                } else {
                    // Regular shift logic
                    $isWithinShift = $currentTime->format('H:i:s') >= $startTime->format('H:i:s') && 
                                   $currentTime->format('H:i:s') <= $endTime->format('H:i:s');
                }

                if ($isWithinShift) {
                    $analysis = "✅ Waktu saat ini dalam jam jaga";
                } else {
                    $analysis = "❌ Waktu saat ini di luar jam jaga";
                }
            }

            $statusClass = $status === 'Aktif' ? 'status-active' : 'status-inactive';
            $timeClass = $isWithinShift ? 'time-valid' : 'time-invalid';

            echo "<tr>
                <td>{$schedule->id}</td>
                <td>{$shiftName}</td>
                <td>{$jamMasuk}</td>
                <td>{$jamPulang}</td>
                <td class='{$statusClass}'>{$status}</td>
                <td class='{$timeClass}'>" . ($isWithinShift ? 'YA' : 'TIDAK') . "</td>
                <td>{$analysis}</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Tidak ada jadwal jaga hari ini!</p>";
    }
}
echo "</div>";

// 4. ANALISIS ATTENDANCE HARI INI
echo "<div class='section info'>
    <h2>📊 4. Analisis Attendance Hari Ini</h2>";

if ($yayaUser) {
    $todayAttendance = Attendance::where('user_id', $yayaUser->id)
        ->whereDate('date', today())
        ->first();

    if ($todayAttendance) {
        echo "<p><strong>Status Attendance:</strong> <span class='status-active'>SUDAH CHECK-IN</span></p>";
        echo "<p><strong>Waktu Check-in:</strong> {$todayAttendance->time_in}</p>";
        echo "<p><strong>Waktu Check-out:</strong> " . ($todayAttendance->time_out ?: 'Belum check-out') . "</p>";
        echo "<p><strong>Status:</strong> {$todayAttendance->status}</p>";
    } else {
        echo "<p><strong>Status Attendance:</strong> <span class='status-inactive'>BELUM CHECK-IN</span></p>";
    }
}
echo "</div>";

// 5. ANALISIS LOGIKA VALIDASI
echo "<div class='section warning'>
    <h2>🔍 5. Analisis Logika Validasi</h2>";

echo "<h3>Logika Validasi di Controller:</h3>";
echo "<div class='code'>
// VALIDASI WAKTU JAGA - Cek apakah saat ini dalam jam jaga dengan buffer
\$shiftTemplate = \$jadwalJaga->shiftTemplate;
if (\$shiftTemplate) {
    \$startTime = Carbon::parse(\$shiftTemplate->jam_masuk)->setTimezone('Asia/Jakarta');
    \$endTime = Carbon::parse(\$shiftTemplate->jam_pulang)->setTimezone('Asia/Jakarta');
    \$currentTimeOnly = \$currentTime->format('H:i:s');
    
    // Add buffer for short shifts (5 minutes before and after)
    \$bufferMinutes = 5;
    \$startTimeWithBuffer = \$startTime->copy()->subMinutes(\$bufferMinutes);
    \$endTimeWithBuffer = \$endTime->copy()->addMinutes(\$bufferMinutes);
    
    // Handle overnight shifts (end time < start time)
    if (\$endTime->format('H:i:s') < \$startTime->format('H:i:s')) {
        // For overnight shifts, check if current time is after start OR before end
        if (\$currentTimeOnly < \$startTimeWithBuffer->format('H:i:s') && \$currentTimeOnly > \$endTimeWithBuffer->format('H:i:s')) {
            return response()->json([
                'success' => false,
                'message' => 'Saat ini bukan jam jaga Anda...',
                'code' => 'OUTSIDE_SHIFT_HOURS'
            ], 422);
        }
    } else {
        // For regular shifts, check if current time is within shift hours with buffer
        if (\$currentTimeOnly < \$startTimeWithBuffer->format('H:i:s') || \$currentTimeOnly > \$endTimeWithBuffer->format('H:i:s')) {
            return response()->json([
                'success' => false,
                'message' => 'Saat ini bukan jam jaga Anda...',
                'code' => 'OUTSIDE_SHIFT_HOURS'
            ], 422);
        }
    }
}
</div>";

echo "<h3>Analisis Masalah:</h3>";
if ($yayaUser && $todaySchedules->count() > 0) {
    $schedule = $todaySchedules->first();
    if ($schedule->shiftTemplate) {
        $startTime = Carbon::parse($schedule->shiftTemplate->jam_masuk);
        $endTime = Carbon::parse($schedule->shiftTemplate->jam_pulang);
        $currentTime = Carbon::parse($currentTimeOnly);
        
        $bufferMinutes = 5;
        $startTimeWithBuffer = $startTime->copy()->subMinutes($bufferMinutes);
        $endTimeWithBuffer = $endTime->copy()->addMinutes($bufferMinutes);
        
        echo "<p><strong>Jam Masuk Shift:</strong> {$startTime->format('H:i:s')}</p>";
        echo "<p><strong>Jam Pulang Shift:</strong> {$endTime->format('H:i:s')}</p>";
        echo "<p><strong>Waktu Saat Ini:</strong> {$currentTime->format('H:i:s')}</p>";
        echo "<p><strong>Buffer:</strong> {$bufferMinutes} menit</p>";
        echo "<p><strong>Jam Masuk dengan Buffer:</strong> {$startTimeWithBuffer->format('H:i:s')}</p>";
        echo "<p><strong>Jam Pulang dengan Buffer:</strong> {$endTimeWithBuffer->format('H:i:s')}</p>";
        
        $isWithinBuffer = $currentTime->format('H:i:s') >= $startTimeWithBuffer->format('H:i:s') && 
                         $currentTime->format('H:i:s') <= $endTimeWithBuffer->format('H:i:s');
        
        if ($isWithinBuffer) {
            echo "<p class='success'>✅ Waktu saat ini dalam range buffer (bisa check-in)</p>";
        } else {
            echo "<p class='error'>❌ Waktu saat ini di luar range buffer (tidak bisa check-in)</p>";
        }
    }
}
echo "</div>";

// 6. REKOMENDASI PERBAIKAN
echo "<div class='section success'>
    <h2>🛠️ 6. Rekomendasi Perbaikan</h2>";

echo "<h3>Masalah yang Ditemukan:</h3>";
echo "<ul>
    <li><strong>Waktu Shift Terlalu Singkat:</strong> Shift Yaya hanya 15 menit (17:45-18:00)</li>
    <li><strong>Waktu Saat Ini:</strong> 23:03 (sudah lewat semua shift hari ini)</li>
    <li><strong>Buffer Terlalu Kecil:</strong> Hanya 5 menit buffer</li>
</ul>";

echo "<h3>Solusi yang Direkomendasikan:</h3>";
echo "<ol>
    <li><strong>Perpanjang Buffer:</strong> Ubah buffer dari 5 menit menjadi 30-60 menit</li>
    <li><strong>Perbaiki Jadwal:</strong> Buat jadwal shift yang lebih realistis</li>
    <li><strong>Tambah Shift Malam:</strong> Jika memang ada shift malam</li>
    <li><strong>Fleksibilitas Waktu:</strong> Izinkan check-in dalam rentang waktu yang lebih luas</li>
</ol>";

echo "<h3>Kode Perbaikan:</h3>";
echo "<div class='code'>
// Perbaikan 1: Perpanjang buffer
\$bufferMinutes = 30; // Dari 5 menit menjadi 30 menit

// Perbaikan 2: Tambah validasi khusus untuk shift pendek
if (\$shiftTemplate) {
    \$shiftDuration = \$endTime->diffInMinutes(\$startTime);
    if (\$shiftDuration <= 30) {
        // Untuk shift pendek, berikan buffer lebih besar
        \$bufferMinutes = 60;
    }
}

// Perbaikan 3: Tambah logika untuk shift malam
if (\$endTime->format('H:i:s') < \$startTime->format('H:i:s')) {
    // Shift malam - izinkan check-in sampai tengah malam
    \$midnight = Carbon::parse('23:59:59');
    if (\$currentTimeOnly <= \$midnight->format('H:i:s')) {
        // Masih dalam shift malam
    }
}
</div>";
echo "</div>";

// 7. TEST VALIDASI
echo "<div class='section info'>
    <h2>🧪 7. Test Validasi</h2>";

echo "<p>Untuk test validasi, Anda bisa:</p>";
echo "<ol>
    <li>Buat jadwal shift baru yang sesuai dengan waktu saat ini</li>
    <li>Atau ubah waktu server untuk test</li>
    <li>Atau perpanjang buffer waktu</li>
</ol>";

echo "<h3>Contoh Jadwal yang Bisa Check-in Sekarang:</h3>";
echo "<div class='code'>
// Jadwal shift malam
Jam Masuk: 22:00
Jam Pulang: 06:00 (besok)
Buffer: 60 menit

// Atau jadwal shift sore-malam
Jam Masuk: 20:00
Jam Pulang: 02:00 (besok)
Buffer: 30 menit
</div>";
echo "</div>";

echo "</div>
</body>
</html>";
?>
