<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Deep Presensi Analysis - Root Problem Finder</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #efe; border-color: #cfc; color: #363; }
        .error { background: #fee; border-color: #fcc; color: #c33; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .critical { background: #f8d7da; border-color: #f5c6cb; color: #721c24; font-weight: bold; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .button { background: #dc2626; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #991b1b; }
        .data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background: #f8f9fa; font-weight: bold; }
        .highlight { background: #fff3cd; padding: 2px 4px; border-radius: 3px; }
        .api-test { background: #e3f2fd; border: 1px solid #2196f3; padding: 15px; border-radius: 8px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔍 Deep Presensi Analysis - Root Problem Finder</h1>
            <p>Analisis mendalam untuk menemukan root problem presensi dan akses jadwal jaga</p>
        </div>";

// 1. ANALISIS USER YAYA
echo "<div class='section info'>
    <h2>👤 1. ANALISIS USER YAYA</h2>";

$yayaUser = User::where('name', 'like', '%Yaya%')->first();

if ($yayaUser) {
    echo "<div class='success'>✅ User Yaya ditemukan: <strong>{$yayaUser->name}</strong> (ID: {$yayaUser->id})</div>";
    
    // Cek role dan permissions
    echo "<h3>Role & Permissions:</h3>";
    echo "<table class='data-table'>
        <tr><th>Field</th><th>Value</th></tr>
        <tr><td>ID</td><td>{$yayaUser->id}</td></tr>
        <tr><td>Name</td><td>{$yayaUser->name}</td></tr>
        <tr><td>Email</td><td>{$yayaUser->email}</td></tr>
        <tr><td>Role</td><td>" . ($yayaUser->role ?? 'N/A') . "</td></tr>
        <tr><td>Created At</td><td>{$yayaUser->created_at}</td></tr>
        <tr><td>Updated At</td><td>{$yayaUser->updated_at}</td></tr>
    </table>";
} else {
    echo "<div class='critical'>❌ KRITIS: User Yaya tidak ditemukan!</div>";
    echo "<p>Ini bisa menjadi root cause utama masalah presensi.</p>";
}

echo "</div>";

// 2. ANALISIS JADWAL JAGA
echo "<div class='section info'>
    <h2>📅 2. ANALISIS JADWAL JAGA</h2>";

if ($yayaUser) {
    $todaySchedules = JadwalJaga::where('pegawai_id', $yayaUser->id)
        ->whereDate('tanggal_jaga', today())
        ->with('shiftTemplate')
        ->get();
    
    echo "<h3>Jadwal Hari Ini ({$todaySchedules->count()} jadwal):</h3>";
    
    if ($todaySchedules->count() > 0) {
        echo "<table class='data-table'>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Shift Template</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Status</th>
            </tr>";
        
        foreach ($todaySchedules as $schedule) {
            $shiftName = $schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'N/A';
            $jamMasuk = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_masuk : 'N/A';
            $jamPulang = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_pulang : 'N/A';
            
            echo "<tr>
                <td>{$schedule->id}</td>
                <td>{$schedule->tanggal_jaga}</td>
                <td>{$shiftName}</td>
                <td>{$jamMasuk}</td>
                <td>{$jamPulang}</td>
                <td>" . ($schedule->status ?? 'Active') . "</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='critical'>❌ KRITIS: Tidak ada jadwal jaga hari ini untuk Yaya!</div>";
    }
    
    // Cek jadwal minggu ini
    $weekSchedules = JadwalJaga::where('pegawai_id', $yayaUser->id)
        ->whereBetween('tanggal_jaga', [now()->startOfWeek(), now()->endOfWeek()])
        ->with('shiftTemplate')
        ->get();
    
    echo "<h3>Jadwal Minggu Ini ({$weekSchedules->count()} jadwal):</h3>";
    if ($weekSchedules->count() > 0) {
        echo "<table class='data-table'>
            <tr>
                <th>Tanggal</th>
                <th>Hari</th>
                <th>Shift</th>
                <th>Jam</th>
            </tr>";
        
        foreach ($weekSchedules as $schedule) {
            $shiftName = $schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'N/A';
            $jamMasuk = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_masuk : 'N/A';
            $jamPulang = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_pulang : 'N/A';
            $hari = Carbon::parse($schedule->tanggal_jaga)->format('l');
            
            echo "<tr>
                <td>{$schedule->tanggal_jaga}</td>
                <td>{$hari}</td>
                <td>{$shiftName}</td>
                <td>{$jamMasuk} - {$jamPulang}</td>
            </tr>";
        }
        echo "</table>";
    }
} else {
    echo "<div class='error'>❌ Skip analisis jadwal - User Yaya tidak ditemukan</div>";
}

echo "</div>";

// 3. ANALISIS SHIFT TEMPLATES
echo "<div class='section info'>
    <h2>⏰ 3. ANALISIS SHIFT TEMPLATES</h2>";

$shiftTemplates = ShiftTemplate::all();

if ($shiftTemplates->count() > 0) {
    echo "<div class='success'>✅ Ada {$shiftTemplates->count()} shift templates</div>";
    
    echo "<table class='data-table'>
        <tr>
            <th>ID</th>
            <th>Nama Shift</th>
            <th>Jam Masuk</th>
            <th>Jam Pulang</th>
            <th>Durasi</th>
            <th>Tipe</th>
        </tr>";
    
    foreach ($shiftTemplates as $template) {
        $jamMasuk = Carbon::parse($template->jam_masuk);
        $jamPulang = Carbon::parse($template->jam_pulang);
        $durasi = $jamPulang->diffInMinutes($jamMasuk);
        $jamMasukHour = (int) $jamMasuk->format('H');
        
        $tipe = 'Normal';
        if ($jamMasukHour >= 22 || $jamMasukHour <= 6) {
            $tipe = 'Malam';
        } elseif ($durasi <= 30) {
            $tipe = 'Pendek';
        }
        
        echo "<tr>
            <td>{$template->id}</td>
            <td>{$template->nama_shift}</td>
            <td>{$template->jam_masuk}</td>
            <td>{$template->jam_pulang}</td>
            <td>{$durasi} menit</td>
            <td>{$tipe}</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<div class='critical'>❌ KRITIS: Tidak ada shift templates!</div>";
}

echo "</div>";

// 4. ANALISIS WAKTU SERVER
echo "<div class='section info'>
    <h2>🕐 4. ANALISIS WAKTU SERVER</h2>";

$now = Carbon::now();
$currentTime = $now->format('H:i:s');
$currentHour = (int) $now->format('H');
$currentDay = $now->format('l');
$timezone = config('app.timezone');

echo "<table class='data-table'>
    <tr><th>Field</th><th>Value</th></tr>
    <tr><td>Waktu Server</td><td>{$now->format('Y-m-d H:i:s')}</td></tr>
    <tr><td>Jam Saat Ini</td><td>{$currentTime}</td></tr>
    <tr><td>Jam (angka)</td><td>{$currentHour}</td></tr>
    <tr><td>Hari</td><td>{$currentDay}</td></tr>
    <tr><td>Timezone</td><td>{$timezone}</td></tr>
</table>";

// Cek apakah shift malam
$isNightShift = $currentHour >= 22 || $currentHour <= 6;
echo "<div class='" . ($isNightShift ? 'warning' : 'info') . "'>
    " . ($isNightShift ? '🌙' : '☀️') . " Status: " . ($isNightShift ? 'Shift Malam' : 'Shift Siang') . "
</div>";

echo "</div>";

// 5. ANALISIS PRESENSI LOGIC
echo "<div class='section info'>
    <h2>🔐 5. ANALISIS PRESENSI LOGIC</h2>";

if ($yayaUser && $todaySchedules->count() > 0) {
    $schedule = $todaySchedules->first();
    if ($schedule->shiftTemplate) {
        $startTime = Carbon::parse($schedule->shiftTemplate->jam_masuk);
        $endTime = Carbon::parse($schedule->shiftTemplate->jam_pulang);
        $currentTimeObj = Carbon::parse($currentTime);
        
        $shiftDuration = $endTime->diffInMinutes($startTime);
        $bufferMinutes = $shiftDuration <= 30 ? 60 : 30;
        
        $startTimeWithBuffer = $startTime->copy()->subMinutes($bufferMinutes);
        $endTimeWithBuffer = $endTime->copy()->addMinutes($bufferMinutes);
        
        $isWithinBuffer = $currentTimeObj->format('H:i:s') >= $startTimeWithBuffer->format('H:i:s') && 
                         $currentTimeObj->format('H:i:s') <= $endTimeWithBuffer->format('H:i:s');
        
        $isNightShiftTime = $currentHour >= 22 || $currentHour <= 6;
        $canCheckIn = $isWithinBuffer || ($isNightShiftTime && $currentHour >= 22);
        
        echo "<h3>Shift Details:</h3>";
        echo "<table class='data-table'>
            <tr><th>Field</th><th>Value</th></tr>
            <tr><td>Shift Name</td><td>{$schedule->shiftTemplate->nama_shift}</td></tr>
            <tr><td>Start Time</td><td>{$startTime->format('H:i')}</td></tr>
            <tr><td>End Time</td><td>{$endTime->format('H:i')}</td></tr>
            <tr><td>Duration</td><td>{$shiftDuration} menit</td></tr>
            <tr><td>Buffer</td><td>{$bufferMinutes} menit</td></tr>
            <tr><td>Buffer Start</td><td>{$startTimeWithBuffer->format('H:i')}</td></tr>
            <tr><td>Buffer End</td><td>{$endTimeWithBuffer->format('H:i')}</td></tr>
        </table>";
        
        echo "<h3>Check-in Logic:</h3>";
        echo "<table class='data-table'>
            <tr><th>Condition</th><th>Status</th><th>Result</th></tr>
            <tr><td>Dalam Range Buffer</td><td>" . ($isWithinBuffer ? '✅ YA' : '❌ TIDAK') . "</td><td>" . ($isWithinBuffer ? 'Bisa Check-in' : 'Tidak bisa') . "</td></tr>
            <tr><td>Shift Malam</td><td>" . ($isNightShiftTime ? '✅ YA' : '❌ TIDAK') . "</td><td>" . ($isNightShiftTime ? 'Jam ' . $currentHour : 'Bukan shift malam') . "</td></tr>
            <tr><td>Jam >= 22</td><td>" . ($currentHour >= 22 ? '✅ YA' : '❌ TIDAK') . "</td><td>" . ($currentHour >= 22 ? 'Bisa check-in malam' : 'Belum jam malam') . "</td></tr>
            <tr><td><strong>FINAL RESULT</strong></td><td><strong>" . ($canCheckIn ? '✅ BISA CHECK-IN' : '❌ TIDAK BISA') . "</strong></td><td><strong>" . ($canCheckIn ? 'SUCCESS' : 'FAILED') . "</strong></td></tr>
        </table>";
        
        if (!$canCheckIn) {
            echo "<div class='critical'>
                <h3>🚨 ROOT PROBLEM DITEMUKAN!</h3>
                <p>Yaya <strong>TIDAK BISA</strong> check-in karena:</p>
                <ul>
                    <li>Waktu saat ini: <span class='highlight'>{$currentTime}</span></li>
                    <li>Range buffer: <span class='highlight'>{$startTimeWithBuffer->format('H:i')} - {$endTimeWithBuffer->format('H:i')}</span></li>
                    <li>Jam saat ini: <span class='highlight'>{$currentHour}</span></li>
                </ul>
            </div>";
        } else {
            echo "<div class='success'>
                <h3>✅ LOGIC CHECK-IN NORMAL</h3>
                <p>Yaya <strong>BISA</strong> check-in berdasarkan logic yang ada.</p>
            </div>";
        }
    }
} else {
    echo "<div class='error'>❌ Skip analisis presensi - Tidak ada jadwal hari ini</div>";
}

echo "</div>";

// 6. ANALISIS API ENDPOINTS
echo "<div class='section info'>
    <h2>🌐 6. ANALISIS API ENDPOINTS</h2>";

$apiEndpoints = [
    '/api/v2/dashboards/dokter' => 'Dashboard Data',
    '/api/v2/dashboards/dokter/jadwal-jaga' => 'Jadwal Jaga',
    '/api/v2/dashboards/dokter/checkin' => 'Check-in',
    '/api/v2/server-time' => 'Server Time'
];

echo "<h3>API Endpoints Test:</h3>";

foreach ($apiEndpoints as $endpoint => $description) {
    $url = 'http://localhost:8000' . $endpoint;
    $response = @file_get_contents($url, false, stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => 'Content-Type: application/json'
        ]
    ]));
    
    $status = $response !== false ? '✅ Online' : '❌ Offline';
    $statusClass = $response !== false ? 'success' : 'error';
    
    echo "<div class='api-test'>
        <strong>{$description}:</strong> <span class='{$statusClass}'>{$status}</span><br>
        <code>{$url}</code>
    </div>";
}

echo "</div>";

// 7. ANALISIS ATTENDANCE RECORDS
echo "<div class='section info'>
    <h2>📊 7. ANALISIS ATTENDANCE RECORDS</h2>";

if ($yayaUser) {
    $todayAttendance = Attendance::where('user_id', $yayaUser->id)
        ->whereDate('created_at', today())
        ->get();
    
    echo "<h3>Attendance Hari Ini ({$todayAttendance->count()} records):</h3>";
    
    if ($todayAttendance->count() > 0) {
        echo "<table class='data-table'>
            <tr>
                <th>ID</th>
                <th>Check-in Time</th>
                <th>Check-out Time</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>";
        
        foreach ($todayAttendance as $attendance) {
            echo "<tr>
                <td>{$attendance->id}</td>
                <td>{$attendance->check_in_time}</td>
                <td>" . (isset($attendance->check_out_time) ? $attendance->check_out_time : 'N/A') . "</td>
                <td>" . (isset($attendance->status) ? $attendance->status : 'Active') . "</td>
                <td>{$attendance->created_at}</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Tidak ada attendance record hari ini</div>";
    }
    
    // Cek attendance minggu ini
    $weekAttendance = Attendance::where('user_id', $yayaUser->id)
        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
        ->get();
    
    echo "<h3>Attendance Minggu Ini ({$weekAttendance->count()} records):</h3>";
    if ($weekAttendance->count() > 0) {
        echo "<table class='data-table'>
            <tr>
                <th>Tanggal</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Status</th>
            </tr>";
        
        foreach ($weekAttendance as $attendance) {
            $tanggal = Carbon::parse($attendance->created_at)->format('Y-m-d');
            echo "<tr>
                <td>{$tanggal}</td>
                <td>{$attendance->check_in_time}</td>
                <td>" . (isset($attendance->check_out_time) ? $attendance->check_out_time : 'N/A') . "</td>
                <td>" . (isset($attendance->status) ? $attendance->status : 'Active') . "</td>
            </tr>";
        }
        echo "</table>";
    }
} else {
    echo "<div class='error'>❌ Skip analisis attendance - User Yaya tidak ditemukan</div>";
}

echo "</div>";

// 8. ROOT PROBLEM SUMMARY
echo "<div class='section critical'>
    <h2>🚨 ROOT PROBLEM SUMMARY</h2>";

$problems = [];

if (!$yayaUser) {
    $problems[] = "❌ User Yaya tidak ditemukan di database";
}

if ($yayaUser && $todaySchedules->count() == 0) {
    $problems[] = "❌ Tidak ada jadwal jaga hari ini untuk Yaya";
}

if ($shiftTemplates->count() == 0) {
    $problems[] = "❌ Tidak ada shift templates di database";
}

if ($yayaUser && $todaySchedules->count() > 0) {
    $schedule = $todaySchedules->first();
    if ($schedule->shiftTemplate) {
        $startTime = Carbon::parse($schedule->shiftTemplate->jam_masuk);
        $endTime = Carbon::parse($schedule->shiftTemplate->jam_pulang);
        $currentTimeObj = Carbon::parse($currentTime);
        
        $shiftDuration = $endTime->diffInMinutes($startTime);
        $bufferMinutes = $shiftDuration <= 30 ? 60 : 30;
        
        $startTimeWithBuffer = $startTime->copy()->subMinutes($bufferMinutes);
        $endTimeWithBuffer = $endTime->copy()->addMinutes($bufferMinutes);
        
        $isWithinBuffer = $currentTimeObj->format('H:i:s') >= $startTimeWithBuffer->format('H:i:s') && 
                         $currentTimeObj->format('H:i:s') <= $endTimeWithBuffer->format('H:i:s');
        
        $isNightShiftTime = $currentHour >= 22 || $currentHour <= 6;
        $canCheckIn = $isWithinBuffer || ($isNightShiftTime && $currentHour >= 22);
        
        if (!$canCheckIn) {
            $problems[] = "❌ Waktu saat ini ({$currentTime}) di luar range buffer ({$startTimeWithBuffer->format('H:i')} - {$endTimeWithBuffer->format('H:i')})";
            $problems[] = "❌ Logic check-in terlalu ketat untuk shift ini";
        }
    }
}

if (empty($problems)) {
    echo "<div class='success'>
        <h3>✅ TIDAK ADA ROOT PROBLEM DITEMUKAN</h3>
        <p>Sistem presensi dan jadwal jaga berfungsi normal.</p>
    </div>";
} else {
    echo "<h3>🚨 ROOT PROBLEMS DITEMUKAN:</h3>";
    echo "<ul>";
    foreach ($problems as $problem) {
        echo "<li>{$problem}</li>";
    }
    echo "</ul>";
    
    echo "<h3>🔧 RECOMMENDED SOLUTIONS:</h3>";
    echo "<ol>";
    if (!$yayaUser) {
        echo "<li><strong>Create User Yaya:</strong> Pastikan user Yaya ada di database</li>";
    }
    if ($yayaUser && $todaySchedules->count() == 0) {
        echo "<li><strong>Create Schedule:</strong> Buat jadwal jaga untuk Yaya hari ini</li>";
    }
    if ($shiftTemplates->count() == 0) {
        echo "<li><strong>Create Shift Templates:</strong> Buat shift templates di database</li>";
    }
    echo "<li><strong>Adjust Buffer Logic:</strong> Perluas buffer waktu atau tambah logic khusus</li>";
    echo "<li><strong>Test API Endpoints:</strong> Pastikan semua API endpoints berfungsi</li>";
    echo "</ol>";
}

echo "</div>";

// 9. QUICK FIX ACTIONS
echo "<div class='section success'>
    <h2>⚡ QUICK FIX ACTIONS</h2>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='quick-test-fix.php' class='button'>⚡ Quick Test Fix</a>";
echo "<a href='create-night-shift.php' class='button'>📅 Create Night Shift</a>";
echo "<a href='test-api-endpoint.php' class='button'>🌐 Test API</a>";
echo "<a href='test-mobile-app.php' class='button'>📱 Test Mobile App</a>";
echo "<a href='root-analysis-summary.php' class='button'>📋 Summary</a>";
echo "</div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
