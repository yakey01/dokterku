<?php
/**
 * Test Script untuk Verifikasi Frontend Greeting
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Dokter;
use Illuminate\Support\Facades\Auth;

echo "\n==================================================\n";
echo "   TEST FRONTEND GREETING - DR. YAYA             \n";
echo "==================================================\n\n";

// 1. Login as dr. Yaya
$user = User::find(13); // dr. Yaya's user ID
if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

Auth::login($user);
echo "✅ Logged in as: " . $user->name . "\n\n";

// 2. Simulate API call
$controller = new App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
$request = new Illuminate\Http\Request();
$response = $controller->index($request);
$data = json_decode($response->getContent(), true);

if (!$data || !isset($data['data'])) {
    echo "❌ Invalid API response!\n";
    exit(1);
}

$userData = $data['data']['user'] ?? [];
$stats = $data['data']['stats'] ?? [];

echo "API RESPONSE DATA:\n";
echo "==================\n";
echo "User Name: " . ($userData['name'] ?? 'N/A') . "\n";
echo "User Email: " . ($userData['email'] ?? 'N/A') . "\n";
echo "Patients Month: " . ($stats['patients_month'] ?? 0) . "\n";
echo "JASPEL: Rp " . number_format($stats['jaspel_month'] ?? 0, 0, ',', '.') . "\n\n";

// 3. Test greeting construction (simulate frontend logic)
echo "GREETING CONSTRUCTION TEST:\n";
echo "============================\n";

$doctorName = $userData['name'] ?? 'Doctor';
$firstName = explode(' ', $doctorName)[0] ?? 'Doctor';

// Test for different times
$times = [
    '08:00' => 'Selamat Pagi',
    '13:00' => 'Selamat Siang',
    '19:00' => 'Selamat Malam',
];

foreach ($times as $time => $expectedGreeting) {
    $hour = intval(substr($time, 0, 2));
    
    if ($hour < 12) {
        $greeting = 'Selamat Pagi';
    } elseif ($hour < 17) {
        $greeting = 'Selamat Siang';
    } else {
        $greeting = 'Selamat Malam';
    }
    
    $fullGreeting = "$greeting, $firstName!";
    
    echo "Time $time: $fullGreeting ";
    if ($greeting === $expectedGreeting) {
        echo "✅\n";
    } else {
        echo "❌ (Expected: $expectedGreeting)\n";
    }
}

echo "\n";
echo "EXPECTED FRONTEND DISPLAY:\n";
echo "===========================\n";
$currentHour = date('H');
if ($currentHour < 12) {
    $currentGreeting = 'Selamat Pagi';
} elseif ($currentHour < 17) {
    $currentGreeting = 'Selamat Siang';
} else {
    $currentGreeting = 'Selamat Malam';
}

echo "Current time: " . date('H:i') . "\n";
echo "Should show: \"$currentGreeting, $firstName!\"\n";
echo "Doctor name: $doctorName\n";
echo "Patients: " . ($stats['patients_month'] ?? 0) . "\n\n";

// 4. Check built files
echo "BUILD FILES CHECK:\n";
echo "==================\n";

$buildFiles = glob('public/build/assets/js/dokter-mobile-app-*.js');
if (empty($buildFiles)) {
    echo "❌ No build files found!\n";
} else {
    foreach ($buildFiles as $file) {
        $content = file_get_contents($file);
        $hasIndonesian = strpos($content, 'Selamat Pagi') !== false;
        $filename = basename($file);
        
        echo "File: $filename\n";
        echo "  - Has Indonesian greetings: " . ($hasIndonesian ? '✅' : '❌') . "\n";
        echo "  - Size: " . number_format(filesize($file) / 1024, 2) . " KB\n";
        echo "  - Modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
    }
}

echo "\n";
echo "TROUBLESHOOTING STEPS:\n";
echo "======================\n";
echo "1. Clear browser cache: Ctrl+Shift+R (or Cmd+Shift+R on Mac)\n";
echo "2. Check console for errors: F12 → Console\n";
echo "3. Verify correct JS file loaded: F12 → Network → Filter by JS\n";
echo "4. Try incognito/private mode\n";
echo "5. Force reload: Add ?v=" . time() . " to URL\n";
echo "\n";

echo "✅ TEST COMPLETE\n";
echo "If greeting still shows English, browser cache is the issue.\n";
echo "==================================================\n\n";