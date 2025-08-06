<?php
require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING DOKTER PAGE ACCESS ===\n";

// Find dokter user
$dokter = User::where('email', 'yaya@dokterku.com')->first();

if (!$dokter) {
    echo "❌ Dokter user not found\n";
    exit(1);
}

// Create session token for login simulation
$token = $dokter->createToken('mobile-app-dokter-' . now()->timestamp)->plainTextToken;

echo "✅ Created session token for: {$dokter->name}\n";
echo "✅ Token: " . substr($token, 0, 30) . "...\n";

// Test with curl using session simulation
$cookieJar = tempnam(sys_get_temp_dir(), 'cookies');

// Step 1: Login simulation
echo "🔐 Simulating login...\n";
$loginResult = shell_exec("curl -s -c '$cookieJar' -X POST 'http://127.0.0.1:8000/api/v2/auth/login' -H 'Content-Type: application/json' -d '{\"login\": \"yaya@dokterku.com\", \"password\": \"password123\", \"device_id\": \"test-device\", \"device_name\": \"Test Device\"}'");

if (str_contains($loginResult, 'success')) {
    echo "✅ Login simulation successful\n";
} else {
    echo "❌ Login simulation failed\n";
    echo "Response: " . substr($loginResult, 0, 200) . "\n";
}

// Step 2: Access dokter page
echo "📱 Testing dokter mobile app page...\n";
$pageResult = shell_exec("curl -s -b '$cookieJar' -H 'Accept: text/html' 'http://127.0.0.1:8000/dokter/mobile-app'");

if (str_contains($pageResult, 'dokter-mobile-app')) {
    echo "✅ Dokter page loaded successfully\n";
    echo "✅ Found Vite asset reference\n";
} elseif (str_contains($pageResult, 'Redirecting')) {
    echo "⚠️ Page redirecting (authentication required)\n";
    echo "Redirect: " . substr($pageResult, 0, 200) . "\n";
} else {
    echo "❌ Page load failed or unexpected content\n";
    echo "Content preview: " . substr($pageResult, 0, 300) . "\n";
}

// Step 3: Check built assets exist
echo "🏗️ Checking built assets...\n";
$manifest = file_get_contents(__DIR__ . '/public/build/manifest.json');
$manifestData = json_decode($manifest, true);

if (isset($manifestData['resources/js/dokter-mobile-app.tsx'])) {
    $asset = $manifestData['resources/js/dokter-mobile-app.tsx'];
    echo "✅ Dokter mobile app asset found: " . $asset['file'] . "\n";
    
    if (file_exists(__DIR__ . '/public/build/' . $asset['file'])) {
        echo "✅ Asset file exists on disk\n";
        $fileSize = filesize(__DIR__ . '/public/build/' . $asset['file']);
        echo "✅ Asset size: " . number_format($fileSize) . " bytes\n";
    } else {
        echo "❌ Asset file missing on disk\n";
    }
} else {
    echo "❌ Dokter mobile app asset not found in manifest\n";
}

// Clean up
unlink($cookieJar);

echo "=== TEST COMPLETED ===\n";