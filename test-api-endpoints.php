<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController;
use App\Http\Controllers\Api\DokterStatsController;
use Illuminate\Http\Request;

echo "=== TESTING ALL API ENDPOINTS FOR DR. YAYA ===\n\n";

// Login as dr. Yaya
$yayaUser = User::find(13);
Auth::login($yayaUser);

echo "User: {$yayaUser->name} (ID: {$yayaUser->id})\n\n";

// 1. Test main dashboard endpoint (leaderboard source)
echo "1. TESTING /api/v2/dashboards/dokter (Leaderboard Source)\n";
echo "======================================================\n";

try {
    $controller = new DokterDashboardController();
    $request = new Request();
    $response = $controller->index($request);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        $performance = $data['data']['performance'] ?? null;
        if ($performance) {
            echo "✅ SUCCESS - Performance Data:\n";
            echo "  - Attendance Rate: " . ($performance['attendance_rate'] ?? 'N/A') . "%\n";
            echo "  - Patient Count Today: " . ($performance['patients_today'] ?? 'N/A') . "\n";
            echo "  - Patient Count Month: " . ($performance['patients_month'] ?? 'N/A') . "\n";
        } else {
            echo "❌ No performance data in response\n";
        }
    } else {
        echo "❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Test presensi endpoint
echo "2. TESTING /api/v2/dashboards/dokter/presensi (Presensi Source)\n";
echo "==============================================================\n";

try {
    $controller = new DokterDashboardController();
    $request = new Request();
    $response = $controller->getPresensi($request);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        $stats = $data['data']['stats'] ?? null;
        if ($stats) {
            echo "✅ SUCCESS - Stats Data:\n";
            echo "  - Attendance Rate: " . ($stats['attendance_rate'] ?? 'N/A') . "%\n";
            echo "  - Attendance Current: " . ($stats['attendance_current'] ?? 'N/A') . "\n";
            echo "  - Performance Rate: " . ($stats['performance_rate'] ?? 'N/A') . "%\n";
        } else {
            echo "❌ No stats data in response\n";
        }
        
        // Check if there's performance data too
        $performance = $data['data']['performance'] ?? null;
        if ($performance) {
            echo "  - Performance Attendance Rate: " . ($performance['attendance_rate'] ?? 'N/A') . "%\n";
        }
    } else {
        echo "❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Test DokterStatsController (if it exists)
echo "3. TESTING DokterStatsController (Alternative Source)\n";
echo "====================================================\n";

try {
    $statsController = new DokterStatsController();
    $request = new Request();
    $response = $statsController->stats();
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        $responseData = $data['data'];
        echo "✅ SUCCESS - DokterStats Data:\n";
        echo "  - Attendance Rate Raw: " . ($responseData['attendance_rate_raw'] ?? 'N/A') . "%\n";
        echo "  - Attendance Current: " . ($responseData['attendance_current'] ?? 'N/A') . "\n";
        echo "  - Patients Today: " . ($responseData['patients_today'] ?? 'N/A') . "\n";
        echo "  - Patients Month: " . ($responseData['patients_month'] ?? 'N/A') . "\n";
    } else {
        echo "❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Test leaderboard endpoint directly
echo "4. TESTING /api/v2/dashboards/dokter/leaderboard (Direct Leaderboard)\n";
echo "===================================================================\n";

try {
    $controller = new DokterDashboardController();
    $request = new Request();
    $request->merge(['month' => 8, 'year' => 2025]);
    $response = $controller->leaderboard($request);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        $leaderboard = $data['data']['leaderboard'] ?? [];
        echo "✅ SUCCESS - Leaderboard Data:\n";
        echo "  - Total Doctors: " . count($leaderboard) . "\n";
        
        // Find Dr. Yaya in leaderboard
        foreach ($leaderboard as $doctor) {
            if ($doctor['id'] == $yayaUser->id) {
                echo "  - Dr. Yaya Found:\n";
                echo "    * Rank: " . ($doctor['rank'] ?? 'N/A') . "\n";
                echo "    * Attendance Rate: " . ($doctor['attendance_rate'] ?? 'N/A') . "%\n";
                echo "    * Total Patients: " . ($doctor['total_patients'] ?? 'N/A') . "\n";
                echo "    * Procedures Count: " . ($doctor['procedures_count'] ?? 'N/A') . "\n";
                break;
            }
        }
    } else {
        echo "❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Summary
echo "5. SUMMARY - API ENDPOINT COMPARISON\n";
echo "====================================\n";
echo "This test shows the actual values returned by different API endpoints\n";
echo "that the frontend components might be calling.\n\n";

echo "If you see different attendance rates from different endpoints,\n";
echo "this explains the discrepancy between leaderboard (85.7%) and stats presensi (56%).\n\n";

echo "The frontend components are likely calling different endpoints:\n";
echo "- Leaderboard: /api/v2/dashboards/dokter/leaderboard\n";
echo "- Stats Presensi: /api/v2/dashboards/dokter/presensi or DokterStatsController\n";