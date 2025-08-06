<?php
require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING DOKTER ACCESS ===\n";

// Find dokter user
$dokter = User::where('email', 'yaya@dokterku.com')->first();

if (!$dokter) {
    echo "❌ Dokter user not found\n";
    exit(1);
}

echo "✅ Found dokter: {$dokter->name} ({$dokter->email})\n";
echo "✅ Role: {$dokter->role}\n";

// Test token creation
try {
    $token = $dokter->createToken('test-mobile-app')->plainTextToken;
    echo "✅ Token created successfully: " . substr($token, 0, 20) . "...\n";
} catch (Exception $e) {
    echo "❌ Token creation failed: " . $e->getMessage() . "\n";
}

// Test route existence
$routes = app('router')->getRoutes();
$dokterRoutes = [];

foreach ($routes as $route) {
    if (str_contains($route->uri(), 'dokter/mobile-app')) {
        $dokterRoutes[] = $route->uri();
    }
}

echo "✅ Found dokter routes: " . implode(', ', $dokterRoutes) . "\n";

// Test API endpoint
echo "✅ Testing API endpoint...\n";

try {
    $response = app('Illuminate\Http\Client\Factory')->withHeaders([
        'Authorization' => "Bearer $token",
        'Accept' => 'application/json'
    ])->get('http://127.0.0.1:8000/api/v2/dashboards/dokter/jadwal-jaga');
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✅ API Response successful\n";
        echo "✅ Calendar events count: " . count($data['data']['calendar_events'] ?? []) . "\n";
        echo "✅ Next shift: " . ($data['data']['next_shift']['date'] ?? 'None') . "\n";
    } else {
        echo "❌ API Response failed: " . $response->status() . "\n";
    }
} catch (Exception $e) {
    echo "❌ API Test failed: " . $e->getMessage() . "\n";
}

echo "=== TEST COMPLETED ===\n";