<?php
/**
 * Test Script for Elite Doctor Leaderboard API
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Dokter;
use App\Models\JumlahPasienHarian;
use App\Models\Tindakan;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

echo "\n==================================================\n";
echo "   TEST ELITE DOCTOR LEADERBOARD API             \n";
echo "==================================================\n\n";

// 1. Login as dr. Yaya
$user = User::find(13); // dr. Yaya's user ID
if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

Auth::login($user);
echo "✅ Logged in as: " . $user->name . "\n\n";

// 2. Call the leaderboard API endpoint
echo "CALLING LEADERBOARD API:\n";
echo "========================\n";

$controller = new App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
$request = new Illuminate\Http\Request();
$response = $controller->leaderboard($request);
$data = json_decode($response->getContent(), true);

if (!$data || !isset($data['success'])) {
    echo "❌ Invalid API response!\n";
    var_dump($data);
    exit(1);
}

if (!$data['success']) {
    echo "❌ API returned error: " . ($data['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

$leaderboard = $data['data'] ?? [];

echo "✅ API call successful!\n";
echo "Total doctors in leaderboard: " . count($leaderboard) . "\n\n";

// 3. Display leaderboard data
echo "LEADERBOARD DATA:\n";
echo "=================\n";

foreach ($leaderboard as $index => $doctor) {
    echo "\n";
    echo "Rank #" . $doctor['rank'] . " " . $doctor['badge'] . "\n";
    echo "Name: " . $doctor['name'] . "\n";
    echo "Attendance Rate: " . $doctor['attendance_rate'] . "%\n";
    echo "Total Patients: " . $doctor['total_patients'] . "\n";
    echo "Procedures Count: " . $doctor['procedures_count'] . "\n";
    echo "Streak Days: " . $doctor['streak_days'] . "\n";
    echo "Level: " . $doctor['level'] . " (XP: " . $doctor['xp'] . ")\n";
    echo "Total Hours: " . $doctor['total_hours'] . "\n";
    echo "-------------------";
}

echo "\n\n";

// 4. Check if dr. Yaya is in the leaderboard
echo "DR. YAYA STATUS:\n";
echo "================\n";

$drYaya = null;
foreach ($leaderboard as $doctor) {
    if (strpos($doctor['name'], 'Yaya') !== false) {
        $drYaya = $doctor;
        break;
    }
}

if ($drYaya) {
    echo "✅ Found dr. Yaya in leaderboard!\n";
    echo "Rank: #" . $drYaya['rank'] . "\n";
    echo "Attendance: " . $drYaya['attendance_rate'] . "% (Target: 76.2%)\n";
    echo "Patients: " . $drYaya['total_patients'] . " (Target: 108)\n";
    echo "Procedures: " . $drYaya['procedures_count'] . " (Target: 72)\n";
} else {
    echo "⚠️ Dr. Yaya not found in leaderboard\n";
}

echo "\n";

// 5. Verify actual database data for dr. Yaya
echo "DATABASE VERIFICATION:\n";
echo "======================\n";

$dokter = Dokter::where('user_id', $user->id)->first();
if ($dokter) {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;
    
    // Get actual patient count
    $patientCount = JumlahPasienHarian::where('dokter_id', $dokter->id)
        ->whereMonth('tanggal', $currentMonth)
        ->whereYear('tanggal', $currentYear)
        ->whereIn('status_validasi', ['approved', 'disetujui'])
        ->sum(\DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
    
    // Get actual procedure count
    $procedureCount = Tindakan::where('dokter_id', $dokter->id)
        ->whereMonth('tanggal', $currentMonth)
        ->whereYear('tanggal', $currentYear)
        ->count();
    
    // Get attendance count
    $attendanceCount = Attendance::where('user_id', $dokter->user_id)
        ->whereMonth('date', $currentMonth)
        ->whereYear('date', $currentYear)
        ->whereNotNull('time_out')
        ->where('status', 'completed')
        ->count();
    
    echo "Real Database Values:\n";
    echo "- Patient Count: " . $patientCount . "\n";
    echo "- Procedure Count: " . $procedureCount . "\n";
    echo "- Attendance Days: " . $attendanceCount . "\n";
} else {
    echo "⚠️ Dokter record not found for user\n";
}

echo "\n";
echo "FRONTEND TEST URL:\n";
echo "==================\n";
echo "Test in browser: http://dokterku.herd/mobile/dokter\n";
echo "Login as dr. Yaya (dd@cc.com)\n";
echo "Check the Elite Doctor Leaderboard section\n";

echo "\n✅ LEADERBOARD API TEST COMPLETE\n";
echo "==================================================\n\n";