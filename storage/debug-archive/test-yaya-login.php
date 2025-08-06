<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "🔐 TESTING YAYA LOGIN\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Find Yaya user
$user = User::where('email', 'yaya@dokter.local')->first();
if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "👤 User: {$user->name} ({$user->email})\n";
echo "🆔 User ID: {$user->id}\n";

// Check pegawai relationship
$pegawai = $user->pegawai ?? App\Models\Pegawai::where('user_id', $user->id)->first();
if (!$pegawai) {
    echo "❌ No pegawai record found!\n";
    exit(1);
}

echo "👨‍⚕️ Pegawai: {$pegawai->nama_lengkap}\n";
echo "🏥 Unit Kerja: {$pegawai->unit_kerja}\n";
echo "📋 Jenis Pegawai: {$pegawai->jenis_pegawai}\n";

// Create API token for testing
$token = $user->createToken('test-login', ['*'])->plainTextToken;
echo "\n🔑 API Token Created: " . substr($token, 0, 20) . "...\n";

// Test the web API endpoint
echo "\n🧪 TESTING WEB API ENDPOINT:\n";
echo "URL: /dokter/web-api/jadwal-jaga\n";

// Simulate authentication
Auth::login($user);
echo "✅ User authenticated in session\n";

// Test the API endpoint directly
try {
    $controller = new App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();
    $request = new Illuminate\Http\Request();
    
    $response = $controller->getJadwalJaga($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "\n📊 API RESPONSE:\n";
    echo "Success: " . ($responseData['success'] ? '✅' : '❌') . "\n";
    
    if (isset($responseData['data']['missions'])) {
        $missions = $responseData['data']['missions'];
        echo "Missions Found: " . count($missions) . "\n";
        
        if (count($missions) > 0) {
            echo "\nSample Mission:\n";
            $sample = $missions[0];
            echo "- ID: {$sample['id']}\n";
            echo "- Title: {$sample['title']}\n";
            echo "- Date: {$sample['date']}\n";
            echo "- Status: {$sample['status_jaga']}\n";
        }
    } else {
        echo "Message: " . ($responseData['message'] ?? 'No message') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🌐 CURL TEST COMMAND:\n";
echo "curl -X GET 'http://127.0.0.1:8000/dokter/web-api/jadwal-jaga' \\\n";
echo "  -H 'Authorization: Bearer {$token}' \\\n";
echo "  -H 'Accept: application/json' \\\n";
echo "  -H 'X-Requested-With: XMLHttpRequest'\n";

echo "\n" . str_repeat("=", 60) . "\n";
echo "✨ TEST COMPLETED!\n";