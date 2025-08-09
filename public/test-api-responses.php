<?php
/**
 * Test actual API responses for dr. Rindang
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController;

echo "\n" . str_repeat("=", 60) . "\n";
echo "API RESPONSE TEST FOR DR. RINDANG\n";
echo str_repeat("=", 60) . "\n\n";

// Find and authenticate as dr. Rindang
$rindang = User::where('email', 'dd@rrr.com')->first();
if (!$rindang) {
    die("User not found\n");
}

Auth::login($rindang);

// Create controller instance
$controller = new DokterDashboardController();

// Test 1: Schedule endpoint
echo "1️⃣ SCHEDULE ENDPOINT (/api/v2/dashboards/dokter/schedule):\n";
$request = Illuminate\Http\Request::create('/api/v2/dashboards/dokter/schedule', 'GET');
$request->setUserResolver(function () use ($rindang) {
    return $rindang;
});

try {
    $response = $controller->getSchedule($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    
    if (isset($data['data']['today_schedule'])) {
        echo "Today's Schedule Count: " . count($data['data']['today_schedule']) . "\n";
        foreach ($data['data']['today_schedule'] as $idx => $schedule) {
            echo "\nSchedule " . ($idx + 1) . ":\n";
            echo "  ID: " . $schedule['id'] . "\n";
            echo "  Unit: " . $schedule['unit_kerja'] . "\n";
            echo "  Status: " . $schedule['status_jaga'] . "\n";
            if (isset($schedule['shift_template'])) {
                echo "  Shift: " . $schedule['shift_template']['nama_shift'] . "\n";
                echo "  Time: " . $schedule['shift_template']['jam_masuk'] . " - " . $schedule['shift_template']['jam_pulang'] . "\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 2: Attendance endpoint
echo "\n\n2️⃣ ATTENDANCE ENDPOINT (/api/v2/dashboards/dokter/presensi):\n";
$request = Illuminate\Http\Request::create('/api/v2/dashboards/dokter/presensi?include_all=1', 'GET');
$request->setUserResolver(function () use ($rindang) {
    return $rindang;
});

try {
    $response = $controller->getTodayAttendance($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    
    if (isset($data['data']['today'])) {
        $today = $data['data']['today'];
        echo "\nToday's Attendance:\n";
        echo "  Date: " . $today['date'] . "\n";
        echo "  Check-in: " . ($today['time_in'] ?? 'null') . "\n";
        echo "  Check-out: " . ($today['time_out'] ?? 'null') . "\n";
        echo "  Status: " . ($today['status'] ?? 'null') . "\n";
        echo "  Jadwal Jaga ID: " . ($today['jadwal_jaga_id'] ?? 'null') . "\n";
    }
    
    if (isset($data['data']['today_records'])) {
        echo "\nAll Today Records: " . count($data['data']['today_records']) . "\n";
        foreach ($data['data']['today_records'] as $idx => $record) {
            echo "\nRecord " . ($idx + 1) . ":\n";
            echo "  Time In: " . ($record['time_in'] ?? 'null') . "\n";
            echo "  Time Out: " . ($record['time_out'] ?? 'null') . "\n";
            echo "  Jadwal Jaga ID: " . ($record['jadwal_jaga_id'] ?? 'null') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 3: Work Location endpoint
echo "\n\n3️⃣ WORK LOCATION ENDPOINT (/api/v2/dashboards/dokter/work-location/status):\n";
$request = Illuminate\Http\Request::create('/api/v2/dashboards/dokter/work-location/status', 'GET');
$request->setUserResolver(function () use ($rindang) {
    return $rindang;
});

try {
    $response = $controller->getWorkLocationStatus($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    
    if (isset($data['data']['work_location'])) {
        $wl = $data['data']['work_location'];
        echo "\nWork Location:\n";
        echo "  ID: " . $wl['id'] . "\n";
        echo "  Name: " . $wl['name'] . "\n";
        echo "  Coordinates: " . $wl['coordinates']['latitude'] . ", " . $wl['coordinates']['longitude'] . "\n";
        echo "  Radius: " . $wl['radius_meters'] . " meters\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 60) . "\n";

// Summary analysis
$hasSchedule = isset($data['data']['today_schedule']) && count($data['data']['today_schedule']) > 0;
$isCheckedIn = isset($today) && $today['time_in'] && !$today['time_out'];

echo "Has Schedule: " . ($hasSchedule ? "YES" : "NO") . "\n";
echo "Is Checked In: " . ($isCheckedIn ? "YES" : "NO") . "\n";

if ($isCheckedIn && $hasSchedule) {
    echo "\n✅ EXPECTED BEHAVIOR:\n";
    echo "- Should NOT show 'Anda tidak memiliki jadwal jaga hari ini'\n";
    echo "- Check-out button should be ENABLED\n";
    echo "- isOnDuty should be TRUE\n";
    echo "- canCheckOut should be TRUE\n";
} else {
    echo "\n⚠️ UNEXPECTED STATE\n";
}

echo "\n";