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

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Comprehensive Analysis - Root Problem Finder</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1600px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
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
        .summary-box { background: #e3f2fd; border: 2px solid #2196f3; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .priority-high { background: #ffebee; border-color: #f44336; }
        .priority-medium { background: #fff3e0; border-color: #ff9800; }
        .priority-low { background: #e8f5e8; border-color: #4caf50; }
        .progress-bar { width: 100%; background: #f0f0f0; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 20px; background: linear-gradient(90deg, #22c55e, #16a34a); transition: width 0.3s ease; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔍 Comprehensive Analysis - Root Problem Finder</h1>
            <p>Analisis komprehensif untuk menemukan root problem presensi dan jadwal jaga</p>
        </div>";

// EXECUTIVE SUMMARY
echo "<div class='summary-box'>
    <h2>📋 EXECUTIVE SUMMARY</h2>
    <p><strong>Masalah:</strong> Gagal presensi dan tidak bisa akses jadwal jaga</p>
    <p><strong>Target:</strong> User Yaya (dokter)</p>
    <p><strong>Scope:</strong> Frontend, API, Database, Logic</p>
</div>";

// 1. USER ANALYSIS
echo "<div class='section info'>
    <h2>👤 1. USER ANALYSIS</h2>";

$yayaUser = User::where('name', 'like', '%Yaya%')->first();

if ($yayaUser) {
    echo "<div class='success'>✅ User Yaya ditemukan: <strong>{$yayaUser->name}</strong> (ID: {$yayaUser->id})</div>";
    
    // Cek tokens
    $tokens = DB::table('personal_access_tokens')
        ->where('tokenable_id', $yayaUser->id)
        ->where('tokenable_type', 'App\\Models\\User')
        ->get();
    
    echo "<p><strong>Authentication Tokens:</strong> {$tokens->count()} tokens</p>";
} else {
    echo "<div class='critical priority-high'>❌ KRITIS: User Yaya tidak ditemukan!</div>";
}

echo "</div>";

// 2. DATABASE ANALYSIS
echo "<div class='section info'>
    <h2>🗄️ 2. DATABASE ANALYSIS</h2>";

if ($yayaUser) {
    // Jadwal jaga
    $todaySchedules = JadwalJaga::where('pegawai_id', $yayaUser->id)
        ->whereDate('tanggal_jaga', today())
        ->with('shiftTemplate')
        ->get();
    
    $weekSchedules = JadwalJaga::where('pegawai_id', $yayaUser->id)
        ->whereBetween('tanggal_jaga', [now()->startOfWeek(), now()->endOfWeek()])
        ->with('shiftTemplate')
        ->get();
    
    // Attendance
    $todayAttendance = Attendance::where('user_id', $yayaUser->id)
        ->whereDate('created_at', today())
        ->get();
    
    // Shift templates
    $shiftTemplates = ShiftTemplate::all();
    
    echo "<table class='data-table'>
        <tr><th>Data Type</th><th>Count</th><th>Status</th></tr>
        <tr><td>Jadwal Hari Ini</td><td>{$todaySchedules->count()}</td><td>" . ($todaySchedules->count() > 0 ? '✅ OK' : '❌ MISSING') . "</td></tr>
        <tr><td>Jadwal Minggu Ini</td><td>{$weekSchedules->count()}</td><td>" . ($weekSchedules->count() > 0 ? '✅ OK' : '⚠️ LOW') . "</td></tr>
        <tr><td>Attendance Hari Ini</td><td>{$todayAttendance->count()}</td><td>" . ($todayAttendance->count() > 0 ? '✅ EXISTS' : '⚠️ NONE') . "</td></tr>
        <tr><td>Shift Templates</td><td>{$shiftTemplates->count()}</td><td>" . ($shiftTemplates->count() > 0 ? '✅ OK' : '❌ MISSING') . "</td></tr>
    </table>";
    
    if ($todaySchedules->count() > 0) {
        echo "<h3>Jadwal Hari Ini:</h3>";
        foreach ($todaySchedules as $schedule) {
            $shiftName = $schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'N/A';
            $jamMasuk = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_masuk : 'N/A';
            $jamPulang = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_pulang : 'N/A';
            
            echo "<div class='success'>• {$shiftName}: {$jamMasuk} - {$jamPulang}</div>";
        }
    }
} else {
    echo "<div class='error'>❌ Skip database analysis - User Yaya tidak ditemukan</div>";
}

echo "</div>";

// 3. API ANALYSIS
echo "<div class='section info'>
    <h2>🌐 3. API ANALYSIS</h2>";

$apiEndpoints = [
    '/api/v2/dashboards/dokter' => 'Dashboard Data',
    '/api/v2/dashboards/dokter/jadwal-jaga' => 'Jadwal Jaga',
    '/api/v2/dashboards/dokter/checkin' => 'Check-in',
    '/api/v2/server-time' => 'Server Time'
];

$apiStatus = [];
$onlineCount = 0;

foreach ($apiEndpoints as $endpoint => $description) {
    $url = 'http://localhost:8000' . $endpoint;
    $response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]]));
    $status = $response !== false ? '✅ Online' : '❌ Offline';
    $apiStatus[] = ['endpoint' => $endpoint, 'description' => $description, 'status' => $status];
    
    if ($response !== false) {
        $onlineCount++;
    }
}

echo "<table class='data-table'>
    <tr><th>Endpoint</th><th>Description</th><th>Status</th></tr>";

foreach ($apiStatus as $api) {
    $statusClass = strpos($api['status'], '✅') !== false ? 'success' : 'error';
    echo "<tr>
        <td><code>{$api['endpoint']}</code></td>
        <td>{$api['description']}</td>
        <td class='{$statusClass}'>{$api['status']}</td>
    </tr>";
}

echo "</table>";

$apiSuccessRate = count($apiEndpoints) > 0 ? round(($onlineCount / count($apiEndpoints)) * 100, 2) : 0;
echo "<div class='progress-bar'>
    <div class='progress-fill' style='width: {$apiSuccessRate}%'></div>
</div>";
echo "<p><strong>API Success Rate:</strong> {$apiSuccessRate}% ({$onlineCount}/" . count($apiEndpoints) . ")</p>";

echo "</div>";

// 4. FRONTEND ANALYSIS
echo "<div class='section info'>
    <h2>📱 4. FRONTEND ANALYSIS</h2>";

$frontendFiles = [
    'resources/js/components/dokter/HolisticMedicalDashboard.tsx' => 'Main Dashboard Component',
    'resources/js/utils/doctorApi.ts' => 'API Utility Functions',
    'resources/js/components/dokter/JadwalJaga.tsx' => 'Jadwal Jaga Component',
    'resources/views/mobile/dokter/app.blade.php' => 'Mobile App View (Old)',
    'resources/views/mobile/dokter/app-simple.blade.php' => 'Mobile App View (New)'
];

$frontendStatus = [];
$existsCount = 0;

foreach ($frontendFiles as $file => $description) {
    $exists = file_exists($file);
    $status = $exists ? '✅ Exists' : '❌ Missing';
    $frontendStatus[] = ['file' => $file, 'description' => $description, 'status' => $status];
    
    if ($exists) {
        $existsCount++;
    }
}

echo "<table class='data-table'>
    <tr><th>File</th><th>Description</th><th>Status</th></tr>";

foreach ($frontendStatus as $file) {
    $statusClass = strpos($file['status'], '✅') !== false ? 'success' : 'error';
    echo "<tr>
        <td><code>{$file['file']}</code></td>
        <td>{$file['description']}</td>
        <td class='{$statusClass}'>{$file['status']}</td>
    </tr>";
}

echo "</table>";

$frontendSuccessRate = count($frontendFiles) > 0 ? round(($existsCount / count($frontendFiles)) * 100, 2) : 0;
echo "<div class='progress-bar'>
    <div class='progress-fill' style='width: {$frontendSuccessRate}%'></div>
</div>";
echo "<p><strong>Frontend Success Rate:</strong> {$frontendSuccessRate}% ({$existsCount}/" . count($frontendFiles) . ")</p>";

echo "</div>";

// 5. PRESENSI LOGIC ANALYSIS
echo "<div class='section info'>
    <h2>🔐 5. PRESENSI LOGIC ANALYSIS</h2>";

if ($yayaUser && $todaySchedules->count() > 0) {
    $schedule = $todaySchedules->first();
    if ($schedule->shiftTemplate) {
        $startTime = Carbon::parse($schedule->shiftTemplate->jam_masuk);
        $endTime = Carbon::parse($schedule->shiftTemplate->jam_pulang);
        $currentTime = Carbon::now();
        $currentHour = (int) $currentTime->format('H');
        
        $shiftDuration = $endTime->diffInMinutes($startTime);
        $bufferMinutes = $shiftDuration <= 30 ? 60 : 30;
        
        $startTimeWithBuffer = $startTime->copy()->subMinutes($bufferMinutes);
        $endTimeWithBuffer = $endTime->copy()->addMinutes($bufferMinutes);
        
        $isWithinBuffer = $currentTime->format('H:i:s') >= $startTimeWithBuffer->format('H:i:s') && 
                         $currentTime->format('H:i:s') <= $endTimeWithBuffer->format('H:i:s');
        
        $isNightShiftTime = $currentHour >= 22 || $currentHour <= 6;
        $canCheckIn = $isWithinBuffer || ($isNightShiftTime && $currentHour >= 22);
        
        echo "<table class='data-table'>
            <tr><th>Field</th><th>Value</th></tr>
            <tr><td>Current Time</td><td>{$currentTime->format('H:i:s')}</td></tr>
            <tr><td>Shift Start</td><td>{$startTime->format('H:i')}</td></tr>
            <tr><td>Shift End</td><td>{$endTime->format('H:i')}</td></tr>
            <tr><td>Buffer</td><td>{$bufferMinutes} minutes</td></tr>
            <tr><td>Buffer Range</td><td>{$startTimeWithBuffer->format('H:i')} - {$endTimeWithBuffer->format('H:i')}</td></tr>
            <tr><td>Within Buffer</td><td>" . ($isWithinBuffer ? '✅ YES' : '❌ NO') . "</td></tr>
            <tr><td>Night Shift</td><td>" . ($isNightShiftTime ? '✅ YES' : '❌ NO') . "</td></tr>
            <tr><td><strong>Can Check-in</strong></td><td><strong>" . ($canCheckIn ? '✅ YES' : '❌ NO') . "</strong></td></tr>
        </table>";
        
        if (!$canCheckIn) {
            echo "<div class='critical priority-high'>
                <h3>🚨 PRESENSI PROBLEM DITEMUKAN!</h3>
                <p>Yaya tidak bisa check-in karena waktu di luar range buffer.</p>
            </div>";
        }
    }
} else {
    echo "<div class='error'>❌ Skip presensi logic - Tidak ada jadwal hari ini</div>";
}

echo "</div>";

// 6. ROOT PROBLEM SUMMARY
echo "<div class='section critical'>
    <h2>🚨 ROOT PROBLEM SUMMARY</h2>";

$problems = [];
$priorities = [];

// Problem 1: User tidak ditemukan
if (!$yayaUser) {
    $problems[] = "❌ User Yaya tidak ditemukan di database";
    $priorities[] = ['problem' => 'User Yaya tidak ditemukan', 'priority' => 'HIGH', 'solution' => 'Buat user Yaya di database'];
}

// Problem 2: Tidak ada jadwal jaga
if ($yayaUser && $todaySchedules->count() == 0) {
    $problems[] = "❌ Tidak ada jadwal jaga hari ini untuk Yaya";
    $priorities[] = ['problem' => 'Tidak ada jadwal jaga hari ini', 'priority' => 'HIGH', 'solution' => 'Buat jadwal jaga untuk Yaya hari ini'];
}

// Problem 3: API endpoints tidak accessible
if ($apiSuccessRate < 100) {
    $problems[] = "❌ API endpoints tidak semua accessible ({$apiSuccessRate}%)";
    $priorities[] = ['problem' => 'API endpoints tidak accessible', 'priority' => 'MEDIUM', 'solution' => 'Pastikan Laravel server berjalan di port 8000'];
}

// Problem 4: Frontend files missing
if ($frontendSuccessRate < 100) {
    $problems[] = "❌ Frontend files tidak semua ada ({$frontendSuccessRate}%)";
    $priorities[] = ['problem' => 'Frontend files missing', 'priority' => 'MEDIUM', 'solution' => 'Rebuild frontend assets dengan npm run build'];
}

// Problem 5: Presensi logic issue
if ($yayaUser && $todaySchedules->count() > 0) {
    $schedule = $todaySchedules->first();
    if ($schedule->shiftTemplate) {
        $startTime = Carbon::parse($schedule->shiftTemplate->jam_masuk);
        $endTime = Carbon::parse($schedule->shiftTemplate->jam_pulang);
        $currentTime = Carbon::now();
        $currentHour = (int) $currentTime->format('H');
        
        $shiftDuration = $endTime->diffInMinutes($startTime);
        $bufferMinutes = $shiftDuration <= 30 ? 60 : 30;
        
        $startTimeWithBuffer = $startTime->copy()->subMinutes($bufferMinutes);
        $endTimeWithBuffer = $endTime->copy()->addMinutes($bufferMinutes);
        
        $isWithinBuffer = $currentTime->format('H:i:s') >= $startTimeWithBuffer->format('H:i:s') && 
                         $currentTime->format('H:i:s') <= $endTimeWithBuffer->format('H:i:s');
        
        $isNightShiftTime = $currentHour >= 22 || $currentHour <= 6;
        $canCheckIn = $isWithinBuffer || ($isNightShiftTime && $currentHour >= 22);
        
        if (!$canCheckIn) {
            $problems[] = "❌ Presensi logic terlalu ketat - tidak bisa check-in";
            $priorities[] = ['problem' => 'Presensi logic terlalu ketat', 'priority' => 'HIGH', 'solution' => 'Perluas buffer waktu atau tambah logic khusus'];
        }
    }
}

if (empty($problems)) {
    echo "<div class='success'>
        <h3>✅ TIDAK ADA ROOT PROBLEM DITEMUKAN</h3>
        <p>Sistem berfungsi normal.</p>
    </div>";
} else {
    echo "<h3>🚨 ROOT PROBLEMS DITEMUKAN ({count($problems)}):</h3>";
    echo "<ul>";
    foreach ($problems as $problem) {
        echo "<li>{$problem}</li>";
    }
    echo "</ul>";
    
    echo "<h3>🎯 PRIORITY-BASED SOLUTIONS:</h3>";
    
    // Group by priority
    $highPriority = array_filter($priorities, function($item) { return $item['priority'] === 'HIGH'; });
    $mediumPriority = array_filter($priorities, function($item) { return $item['priority'] === 'MEDIUM'; });
    $lowPriority = array_filter($priorities, function($item) { return $item['priority'] === 'LOW'; });
    
    if (!empty($highPriority)) {
        echo "<div class='priority-high'>
            <h4>🔴 HIGH PRIORITY:</h4>
            <ol>";
        foreach ($highPriority as $item) {
            echo "<li><strong>{$item['problem']}</strong> - {$item['solution']}</li>";
        }
        echo "</ol></div>";
    }
    
    if (!empty($mediumPriority)) {
        echo "<div class='priority-medium'>
            <h4>🟡 MEDIUM PRIORITY:</h4>
            <ol>";
        foreach ($mediumPriority as $item) {
            echo "<li><strong>{$item['problem']}</strong> - {$item['solution']}</li>";
        }
        echo "</ol></div>";
    }
    
    if (!empty($lowPriority)) {
        echo "<div class='priority-low'>
            <h4>🟢 LOW PRIORITY:</h4>
            <ol>";
        foreach ($lowPriority as $item) {
            echo "<li><strong>{$item['problem']}</strong> - {$item['solution']}</li>";
        }
        echo "</ol></div>";
    }
}

echo "</div>";

// 7. QUICK FIX ACTIONS
echo "<div class='section success'>
    <h2>⚡ QUICK FIX ACTIONS</h2>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='deep-presensi-analysis.php' class='button'>🔍 Deep Presensi Analysis</a>";
echo "<a href='frontend-api-analysis.php' class='button'>🌐 Frontend API Analysis</a>";
echo "<a href='frontend-debug-analysis.php' class='button'>📱 Frontend Debug Analysis</a>";
echo "<a href='quick-test-fix.php' class='button'>⚡ Quick Test Fix</a>";
echo "<a href='create-night-shift.php' class='button'>📅 Create Night Shift</a>";
echo "<a href='test-mobile-app.php' class='button'>📱 Test Mobile App</a>";
echo "</div>";

echo "</div>";

// 8. SYSTEM STATUS
echo "<div class='section info'>
    <h2>📊 SYSTEM STATUS</h2>";

$now = Carbon::now();
$serverTime = $now->format('Y-m-d H:i:s');
$timezone = config('app.timezone');

echo "<table class='data-table'>
    <tr><th>Component</th><th>Status</th><th>Details</th></tr>
    <tr><td>Server Time</td><td>✅ Running</td><td>{$serverTime} ({$timezone})</td></tr>
    <tr><td>Database</td><td>" . ($yayaUser ? '✅ Connected' : '❌ Error') . "</td><td>" . ($yayaUser ? 'User Yaya found' : 'User Yaya not found') . "</td></tr>
    <tr><td>API Endpoints</td><td>" . ($apiSuccessRate >= 80 ? '✅ Good' : ($apiSuccessRate >= 50 ? '⚠️ Partial' : '❌ Poor')) . "</td><td>{$apiSuccessRate}% success rate</td></tr>
    <tr><td>Frontend Files</td><td>" . ($frontendSuccessRate >= 80 ? '✅ Good' : ($frontendSuccessRate >= 50 ? '⚠️ Partial' : '❌ Poor')) . "</td><td>{$frontendSuccessRate}% files exist</td></tr>
    <tr><td>Presensi Logic</td><td>" . (empty($problems) ? '✅ Working' : '❌ Issues') . "</td><td>" . (empty($problems) ? 'No problems found' : count($problems) . ' problems found') . "</td></tr>
</table>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
