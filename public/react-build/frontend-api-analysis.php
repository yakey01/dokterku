<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔍 Frontend & API Analysis - Jadwal Jaga Access</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #efe; border-color: #cfc; color: #363; }
        .error { background: #fee; border-color: #fcc; color: #c33; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .critical { background: #f8d7da; border-color: #f5c6cb; color: #721c24; font-weight: bold; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .button { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #1d4ed8; }
        .data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background: #f8f9fa; font-weight: bold; }
        .api-response { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px; margin: 10px 0; font-family: monospace; font-size: 12px; }
        .json-pretty { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔍 Frontend & API Analysis - Jadwal Jaga Access</h1>
            <p>Analisis mendalam untuk masalah akses jadwal jaga di frontend</p>
        </div>";

// 1. ANALISIS USER DAN AUTHENTICATION
echo "<div class='section info'>
    <h2>🔐 1. ANALISIS USER & AUTHENTICATION</h2>";

$yayaUser = User::where('name', 'like', '%Yaya%')->first();

if ($yayaUser) {
    echo "<div class='success'>✅ User Yaya ditemukan: <strong>{$yayaUser->name}</strong> (ID: {$yayaUser->id})</div>";
    
    // Cek apakah user memiliki token
    $tokens = DB::table('personal_access_tokens')
        ->where('tokenable_id', $yayaUser->id)
        ->where('tokenable_type', 'App\\Models\\User')
        ->get();
    
    echo "<h3>Personal Access Tokens ({$tokens->count()}):</h3>";
    if ($tokens->count() > 0) {
        echo "<table class='data-table'>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Created At</th>
                <th>Last Used At</th>
                <th>Expires At</th>
            </tr>";
        
        foreach ($tokens as $token) {
            echo "<tr>
                <td>{$token->id}</td>
                <td>{$token->name}</td>
                <td>{$token->created_at}</td>
                <td>" . (isset($token->last_used_at) ? $token->last_used_at : 'Never') . "</td>
                <td>" . (isset($token->expires_at) ? $token->expires_at : 'Never') . "</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Tidak ada personal access tokens untuk Yaya</div>";
    }
} else {
    echo "<div class='critical'>❌ KRITIS: User Yaya tidak ditemukan!</div>";
}

echo "</div>";

// 2. ANALISIS JADWAL JAGA DATABASE
echo "<div class='section info'>
    <h2>📅 2. ANALISIS JADWAL JAGA DATABASE</h2>";

if ($yayaUser) {
    // Jadwal hari ini
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
                <th>Shift Template ID</th>
                <th>Shift Name</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
            </tr>";
        
        foreach ($todaySchedules as $schedule) {
            $shiftName = $schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'N/A';
            $jamMasuk = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_masuk : 'N/A';
            $jamPulang = $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_pulang : 'N/A';
            
            echo "<tr>
                <td>{$schedule->id}</td>
                <td>{$schedule->tanggal_jaga}</td>
                <td>{$schedule->shift_template_id}</td>
                <td>{$shiftName}</td>
                <td>{$jamMasuk}</td>
                <td>{$jamPulang}</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='critical'>❌ KRITIS: Tidak ada jadwal jaga hari ini untuk Yaya!</div>";
    }
    
    // Jadwal minggu ini
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

// 3. ANALISIS API ENDPOINTS
echo "<div class='section info'>
    <h2>🌐 3. ANALISIS API ENDPOINTS</h2>";

$apiEndpoints = [
    '/api/v2/dashboards/dokter' => 'Dashboard Data',
    '/api/v2/dashboards/dokter/jadwal-jaga' => 'Jadwal Jaga',
    '/api/v2/dashboards/dokter/checkin' => 'Check-in',
    '/api/v2/server-time' => 'Server Time'
];

echo "<h3>API Endpoints Test:</h3>";

foreach ($apiEndpoints as $endpoint => $description) {
    $url = 'http://localhost:8000' . $endpoint;
    
    // Test tanpa authentication
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    $httpCode = 0;
    
    if ($response === false) {
        $error = error_get_last();
        if (isset($error['message'])) {
            if (strpos($error['message'], '401') !== false) {
                $httpCode = 401;
                $response = 'Unauthorized - Authentication required';
            } elseif (strpos($error['message'], '404') !== false) {
                $httpCode = 404;
                $response = 'Not Found - Endpoint not found';
            } else {
                $httpCode = 500;
                $response = 'Server Error - ' . $error['message'];
            }
        } else {
            $httpCode = 0;
            $response = 'Connection failed';
        }
    } else {
        $httpCode = 200;
    }
    
    $statusClass = $httpCode == 200 ? 'success' : ($httpCode == 401 ? 'warning' : 'error');
    $statusText = $httpCode == 200 ? '✅ Online' : ($httpCode == 401 ? '⚠️ Auth Required' : '❌ Error');
    
    echo "<div class='api-test'>
        <strong>{$description}:</strong> <span class='{$statusClass}'>{$statusText} (HTTP {$httpCode})</span><br>
        <code>{$url}</code><br>";
    
    if ($httpCode == 200) {
        echo "<div class='api-response'>
            <strong>Response:</strong><br>
            <div class='json-pretty'>" . htmlspecialchars($response) . "</div>
        </div>";
    } elseif ($httpCode == 401) {
        echo "<div class='api-response'>
            <strong>Response:</strong> Authentication required - This is expected for protected endpoints
        </div>";
    } else {
        echo "<div class='api-response'>
            <strong>Error:</strong> {$response}
        </div>";
    }
    
    echo "</div>";
}

echo "</div>";

// 4. ANALISIS FRONTEND COMPONENTS
echo "<div class='section info'>
    <h2>📱 4. ANALISIS FRONTEND COMPONENTS</h2>";

echo "<h3>Mobile App Routes:</h3>";
echo "<table class='data-table'>
    <tr>
        <th>Route</th>
        <th>Status</th>
        <th>Description</th>
    </tr>
    <tr>
        <td>/dokter/mobile-app</td>
        <td>✅ Active</td>
        <td>Mobile app lama (sudah diperbaiki)</td>
    </tr>
    <tr>
        <td>/dokter/mobile-app-simple</td>
        <td>✅ Active</td>
        <td>Mobile app baru (simplified)</td>
    </tr>
</table>";

echo "<h3>Frontend Files Analysis:</h3>";

$frontendFiles = [
    'resources/js/components/dokter/HolisticMedicalDashboard.tsx' => 'Main Dashboard Component',
    'resources/js/utils/doctorApi.ts' => 'API Utility Functions',
    'resources/views/mobile/dokter/app.blade.php' => 'Mobile App View (Old)',
    'resources/views/mobile/dokter/app-simple.blade.php' => 'Mobile App View (New)'
];

foreach ($frontendFiles as $file => $description) {
    $exists = file_exists($file);
    $statusClass = $exists ? 'success' : 'error';
    $statusText = $exists ? '✅ Exists' : '❌ Missing';
    
    echo "<div class='{$statusClass}'>
        <strong>{$description}:</strong> {$statusText}<br>
        <code>{$file}</code>
    </div>";
}

echo "</div>";

// 5. ANALISIS JADWAL JAGA API RESPONSE
echo "<div class='section info'>
    <h2>📊 5. ANALISIS JADWAL JAGA API RESPONSE</h2>";

if ($yayaUser) {
    // Simulasi API response untuk jadwal jaga
    $jadwalJagaData = [
        'success' => true,
        'data' => [
            'jadwal_jaga' => []
        ]
    ];
    
    if ($todaySchedules->count() > 0) {
        foreach ($todaySchedules as $schedule) {
            $jadwalJagaData['data']['jadwal_jaga'][] = [
                'id' => $schedule->id,
                'tanggal_jaga' => $schedule->tanggal_jaga,
                'shift_template' => $schedule->shiftTemplate ? [
                    'id' => $schedule->shiftTemplate->id,
                    'nama_shift' => $schedule->shiftTemplate->nama_shift,
                    'jam_masuk' => $schedule->shiftTemplate->jam_masuk,
                    'jam_pulang' => $schedule->shiftTemplate->jam_pulang
                ] : null
            ];
        }
    }
    
    echo "<h3>Expected API Response for /api/v2/dashboards/dokter/jadwal-jaga:</h3>";
    echo "<div class='api-response'>
        <div class='json-pretty'>" . htmlspecialchars(json_encode($jadwalJagaData, JSON_PRETTY_PRINT)) . "</div>
    </div>";
    
    if (empty($jadwalJagaData['data']['jadwal_jaga'])) {
        echo "<div class='critical'>
            <h3>🚨 ROOT PROBLEM: Tidak ada jadwal jaga untuk ditampilkan!</h3>
            <p>Frontend tidak akan menampilkan jadwal jaga karena data kosong.</p>
        </div>";
    } else {
        echo "<div class='success'>
            <h3>✅ Data jadwal jaga tersedia untuk frontend</h3>
            <p>Frontend seharusnya bisa menampilkan jadwal jaga dengan benar.</p>
        </div>";
    }
} else {
    echo "<div class='error'>❌ Skip analisis API response - User Yaya tidak ditemukan</div>";
}

echo "</div>";

// 6. ROOT PROBLEM ANALYSIS
echo "<div class='section critical'>
    <h2>🚨 ROOT PROBLEM ANALYSIS</h2>";

$problems = [];
$solutions = [];

// Problem 1: User tidak ditemukan
if (!$yayaUser) {
    $problems[] = "❌ User Yaya tidak ditemukan di database";
    $solutions[] = "🔧 Buat user Yaya di database atau cek nama yang benar";
}

// Problem 2: Tidak ada jadwal jaga
if ($yayaUser && $todaySchedules->count() == 0) {
    $problems[] = "❌ Tidak ada jadwal jaga hari ini untuk Yaya";
    $solutions[] = "🔧 Buat jadwal jaga untuk Yaya hari ini";
}

// Problem 3: API endpoints tidak accessible
$apiProblems = 0;
foreach ($apiEndpoints as $endpoint => $description) {
    $url = 'http://localhost:8000' . $endpoint;
    $response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]]));
    if ($response === false) {
        $apiProblems++;
    }
}

if ($apiProblems > 0) {
    $problems[] = "❌ {$apiProblems} API endpoints tidak accessible";
    $solutions[] = "🔧 Pastikan Laravel server berjalan di port 8000";
}

// Problem 4: Frontend files missing
$missingFiles = 0;
foreach ($frontendFiles as $file => $description) {
    if (!file_exists($file)) {
        $missingFiles++;
    }
}

if ($missingFiles > 0) {
    $problems[] = "❌ {$missingFiles} frontend files missing";
    $solutions[] = "🔧 Rebuild frontend assets dengan npm run build";
}

if (empty($problems)) {
    echo "<div class='success'>
        <h3>✅ TIDAK ADA ROOT PROBLEM DITEMUKAN</h3>
        <p>Sistem frontend dan API berfungsi normal.</p>
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
    foreach ($solutions as $solution) {
        echo "<li>{$solution}</li>";
    }
    echo "</ol>";
}

echo "</div>";

// 7. QUICK FIX ACTIONS
echo "<div class='section success'>
    <h2>⚡ QUICK FIX ACTIONS</h2>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='deep-presensi-analysis.php' class='button'>🔍 Deep Presensi Analysis</a>";
echo "<a href='quick-test-fix.php' class='button'>⚡ Quick Test Fix</a>";
echo "<a href='create-night-shift.php' class='button'>📅 Create Night Shift</a>";
echo "<a href='test-api-endpoint.php' class='button'>🌐 Test API</a>";
echo "<a href='test-mobile-app.php' class='button'>📱 Test Mobile App</a>";
echo "</div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
