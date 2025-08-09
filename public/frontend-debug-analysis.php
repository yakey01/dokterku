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
    <title>🔍 Frontend Debug Analysis - Jadwal Jaga Display</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #efe; border-color: #cfc; color: #363; }
        .error { background: #fee; border-color: #fcc; color: #c33; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .critical { background: #f8d7da; border-color: #f5c6cb; color: #721c24; font-weight: bold; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .button { background: #f59e0b; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #d97706; }
        .data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background: #f8f9fa; font-weight: bold; }
        .file-content { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px; margin: 10px 0; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; }
        .highlight { background: #fff3cd; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔍 Frontend Debug Analysis - Jadwal Jaga Display</h1>
            <p>Analisis mendalam untuk masalah tampilan jadwal jaga di frontend</p>
        </div>";

// 1. ANALISIS DATA YANG DIPERLUKAN FRONTEND
echo "<div class='section info'>
    <h2>📊 1. ANALISIS DATA YANG DIPERLUKAN FRONTEND</h2>";

$yayaUser = User::where('name', 'like', '%Yaya%')->first();

if ($yayaUser) {
    echo "<div class='success'>✅ User Yaya ditemukan: <strong>{$yayaUser->name}</strong> (ID: {$yayaUser->id})</div>";
    
    // Cek jadwal jaga
    $todaySchedules = JadwalJaga::where('pegawai_id', $yayaUser->id)
        ->whereDate('tanggal_jaga', today())
        ->with('shiftTemplate')
        ->get();
    
    echo "<h3>Data Jadwal Jaga untuk Frontend:</h3>";
    
    if ($todaySchedules->count() > 0) {
        echo "<div class='success'>✅ Ada {$todaySchedules->count()} jadwal jaga hari ini</div>";
        
        foreach ($todaySchedules as $index => $schedule) {
            echo "<h4>Jadwal #" . ($index + 1) . ":</h4>";
            echo "<table class='data-table'>
                <tr><th>Field</th><th>Value</th></tr>
                <tr><td>ID</td><td>{$schedule->id}</td></tr>
                <tr><td>Tanggal</td><td>{$schedule->tanggal_jaga}</td></tr>
                <tr><td>Pegawai ID</td><td>{$schedule->pegawai_id}</td></tr>
                <tr><td>Shift Template ID</td><td>{$schedule->shift_template_id}</td></tr>";
            
            if ($schedule->shiftTemplate) {
                echo "<tr><td>Shift Name</td><td>{$schedule->shiftTemplate->nama_shift}</td></tr>
                <tr><td>Jam Masuk</td><td>{$schedule->shiftTemplate->jam_masuk}</td></tr>
                <tr><td>Jam Pulang</td><td>{$schedule->shiftTemplate->jam_pulang}</td></tr>";
            } else {
                echo "<tr><td>Shift Template</td><td class='error'>❌ Tidak ada shift template</td></tr>";
            }
            
            echo "</table>";
        }
    } else {
        echo "<div class='critical'>❌ KRITIS: Tidak ada jadwal jaga hari ini!</div>";
        echo "<p>Ini adalah root cause utama mengapa frontend tidak menampilkan jadwal jaga.</p>";
    }
} else {
    echo "<div class='critical'>❌ KRITIS: User Yaya tidak ditemukan!</div>";
}

echo "</div>";

// 2. ANALISIS FRONTEND COMPONENTS
echo "<div class='section info'>
    <h2>📱 2. ANALISIS FRONTEND COMPONENTS</h2>";

$frontendFiles = [
    'resources/js/components/dokter/HolisticMedicalDashboard.tsx' => 'Main Dashboard Component',
    'resources/js/utils/doctorApi.ts' => 'API Utility Functions',
    'resources/views/mobile/dokter/app.blade.php' => 'Mobile App View (Old)',
    'resources/views/mobile/dokter/app-simple.blade.php' => 'Mobile App View (New)'
];

echo "<h3>Frontend Files Status:</h3>";

foreach ($frontendFiles as $file => $description) {
    $exists = file_exists($file);
    $statusClass = $exists ? 'success' : 'error';
    $statusText = $exists ? '✅ Exists' : '❌ Missing';
    
    echo "<div class='{$statusClass}'>
        <strong>{$description}:</strong> {$statusText}<br>
        <code>{$file}</code>
    </div>";
    
    if ($exists) {
        $fileSize = filesize($file);
        $lastModified = date('Y-m-d H:i:s', filemtime($file));
        echo "<small>Size: " . number_format($fileSize) . " bytes | Modified: {$lastModified}</small><br><br>";
    }
}

echo "</div>";

// 3. ANALISIS API CALLS
echo "<div class='section info'>
    <h2>🌐 3. ANALISIS API CALLS</h2>";

echo "<h3>Expected API Calls from Frontend:</h3>";

$apiCalls = [
    'GET /api/v2/dashboards/dokter' => 'Dashboard data (user info, metrics)',
    'GET /api/v2/dashboards/dokter/jadwal-jaga' => 'Jadwal jaga data',
    'POST /api/v2/dashboards/dokter/checkin' => 'Check-in attendance',
    'GET /api/v2/server-time' => 'Server time check'
];

echo "<table class='data-table'>
    <tr>
        <th>API Call</th>
        <th>Purpose</th>
        <th>Status</th>
        <th>Expected Response</th>
    </tr>";

foreach ($apiCalls as $apiCall => $purpose) {
    $url = 'http://localhost:8000' . explode(' ', $apiCall)[1];
    $method = explode(' ', $apiCall)[0];
    
    // Test API endpoint
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'timeout' => 5,
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    $status = $response !== false ? '✅ Online' : '❌ Offline';
    $statusClass = $response !== false ? 'success' : 'error';
    
    $expectedResponse = '';
    if (strpos($apiCall, 'jadwal-jaga') !== false) {
        $expectedResponse = 'JSON dengan array jadwal_jaga';
    } elseif (strpos($apiCall, 'dashboards/dokter') !== false) {
        $expectedResponse = 'JSON dengan user data dan metrics';
    } elseif (strpos($apiCall, 'server-time') !== false) {
        $expectedResponse = 'JSON dengan current timestamp';
    } else {
        $expectedResponse = 'JSON response';
    }
    
    echo "<tr>
        <td><code>{$apiCall}</code></td>
        <td>{$purpose}</td>
        <td class='{$statusClass}'>{$status}</td>
        <td>{$expectedResponse}</td>
    </tr>";
}

echo "</table>";

echo "</div>";

// 4. ANALISIS JADWAL JAGA COMPONENT
echo "<div class='section info'>
    <h2>📅 4. ANALISIS JADWAL JAGA COMPONENT</h2>";

// Cek file JadwalJaga component
$jadwalJagaFile = 'resources/js/components/dokter/JadwalJaga.tsx';
$jadwalJagaExists = file_exists($jadwalJagaFile);

if ($jadwalJagaExists) {
    echo "<div class='success'>✅ JadwalJaga component ditemukan</div>";
    
    // Baca file untuk analisis
    $content = file_get_contents($jadwalJagaFile);
    
    echo "<h3>JadwalJaga Component Analysis:</h3>";
    
    // Cek apakah ada props untuk jadwal jaga
    if (strpos($content, 'jadwalJaga') !== false) {
        echo "<div class='success'>✅ Props jadwalJaga ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ Props jadwalJaga tidak ditemukan</div>";
    }
    
    // Cek apakah ada mapping untuk jadwal jaga
    if (strpos($content, 'map') !== false && strpos($content, 'jadwal') !== false) {
        echo "<div class='success'>✅ Mapping jadwal jaga ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ Mapping jadwal jaga tidak ditemukan</div>";
    }
    
    // Cek apakah ada conditional rendering
    if (strpos($content, 'jadwalJaga') !== false && strpos($content, 'length') !== false) {
        echo "<div class='success'>✅ Conditional rendering untuk jadwal jaga ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ Conditional rendering untuk jadwal jaga tidak ditemukan</div>";
    }
    
} else {
    echo "<div class='error'>❌ JadwalJaga component tidak ditemukan</div>";
    echo "<p>File: <code>{$jadwalJagaFile}</code></p>";
}

echo "</div>";

// 5. ANALISIS DASHBOARD COMPONENT
echo "<div class='section info'>
    <h2>📊 5. ANALISIS DASHBOARD COMPONENT</h2>";

$dashboardFile = 'resources/js/components/dokter/HolisticMedicalDashboard.tsx';
$dashboardExists = file_exists($dashboardFile);

if ($dashboardExists) {
    echo "<div class='success'>✅ HolisticMedicalDashboard component ditemukan</div>";
    
    $content = file_get_contents($dashboardFile);
    
    echo "<h3>Dashboard Component Analysis:</h3>";
    
    // Cek API calls
    if (strpos($content, 'fetchDashboardData') !== false) {
        echo "<div class='success'>✅ fetchDashboardData function ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ fetchDashboardData function tidak ditemukan</div>";
    }
    
    // Cek jadwal jaga state
    if (strpos($content, 'jadwalJaga') !== false) {
        echo "<div class='success'>✅ jadwalJaga state ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ jadwalJaga state tidak ditemukan</div>";
    }
    
    // Cek useEffect untuk fetch data
    if (strpos($content, 'useEffect') !== false && strpos($content, 'fetch') !== false) {
        echo "<div class='success'>✅ useEffect untuk fetch data ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ useEffect untuk fetch data tidak ditemukan</div>";
    }
    
    // Cek error handling
    if (strpos($content, 'error') !== false && strpos($content, 'catch') !== false) {
        echo "<div class='success'>✅ Error handling ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ Error handling tidak ditemukan</div>";
    }
    
} else {
    echo "<div class='error'>❌ HolisticMedicalDashboard component tidak ditemukan</div>";
}

echo "</div>";

// 6. ANALISIS API UTILITY
echo "<div class='section info'>
    <h2>🔧 6. ANALISIS API UTILITY</h2>";

$apiFile = 'resources/js/utils/doctorApi.ts';
$apiExists = file_exists($apiFile);

if ($apiExists) {
    echo "<div class='success'>✅ doctorApi utility ditemukan</div>";
    
    $content = file_get_contents($apiFile);
    
    echo "<h3>API Utility Analysis:</h3>";
    
    // Cek jadwal jaga API function
    if (strpos($content, 'jadwal-jaga') !== false) {
        echo "<div class='success'>✅ Jadwal jaga API endpoint ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ Jadwal jaga API endpoint tidak ditemukan</div>";
    }
    
    // Cek dashboard API function
    if (strpos($content, 'dashboards/dokter') !== false) {
        echo "<div class='success'>✅ Dashboard API endpoint ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ Dashboard API endpoint tidak ditemukan</div>";
    }
    
    // Cek authentication headers
    if (strpos($content, 'Authorization') !== false) {
        echo "<div class='success'>✅ Authentication headers ditemukan</div>";
    } else {
        echo "<div class='warning'>⚠️ Authentication headers tidak ditemukan</div>";
    }
    
} else {
    echo "<div class='error'>❌ doctorApi utility tidak ditemukan</div>";
}

echo "</div>";

// 7. ROOT PROBLEM ANALYSIS
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

// Problem 3: Frontend files missing
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

// Problem 4: JadwalJaga component missing
if (!$jadwalJagaExists) {
    $problems[] = "❌ JadwalJaga component tidak ditemukan";
    $solutions[] = "🔧 Buat atau restore JadwalJaga component";
}

// Problem 5: API endpoints tidak accessible
$apiProblems = 0;
foreach ($apiCalls as $apiCall => $purpose) {
    $url = 'http://localhost:8000' . explode(' ', $apiCall)[1];
    $response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]]));
    if ($response === false) {
        $apiProblems++;
    }
}

if ($apiProblems > 0) {
    $problems[] = "❌ {$apiProblems} API endpoints tidak accessible";
    $solutions[] = "🔧 Pastikan Laravel server berjalan di port 8000";
}

if (empty($problems)) {
    echo "<div class='success'>
        <h3>✅ TIDAK ADA ROOT PROBLEM DITEMUKAN</h3>
        <p>Sistem frontend berfungsi normal.</p>
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
    
    echo "<h3>🎯 PRIORITY ORDER:</h3>";
    echo "<ol>";
    if (!$yayaUser) {
        echo "<li><strong>HIGH:</strong> Buat user Yaya di database</li>";
    }
    if ($yayaUser && $todaySchedules->count() == 0) {
        echo "<li><strong>HIGH:</strong> Buat jadwal jaga untuk Yaya hari ini</li>";
    }
    if ($apiProblems > 0) {
        echo "<li><strong>MEDIUM:</strong> Pastikan Laravel server berjalan</li>";
    }
    if ($missingFiles > 0) {
        echo "<li><strong>MEDIUM:</strong> Rebuild frontend assets</li>";
    }
    if (!$jadwalJagaExists) {
        echo "<li><strong>LOW:</strong> Restore JadwalJaga component</li>";
    }
    echo "</ol>";
}

echo "</div>";

// 8. QUICK FIX ACTIONS
echo "<div class='section success'>
    <h2>⚡ QUICK FIX ACTIONS</h2>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='deep-presensi-analysis.php' class='button'>🔍 Deep Presensi Analysis</a>";
echo "<a href='frontend-api-analysis.php' class='button'>🌐 Frontend API Analysis</a>";
echo "<a href='quick-test-fix.php' class='button'>⚡ Quick Test Fix</a>";
echo "<a href='create-night-shift.php' class='button'>📅 Create Night Shift</a>";
echo "<a href='test-mobile-app.php' class='button'>📱 Test Mobile App</a>";
echo "</div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
