<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Login as dokter user
$dokter = App\Models\User::where('email', '3333@dokter.local')->first();

if (!$dokter) {
    echo "❌ Dokter user not found\n";
    exit(1);
}

echo "✅ Found dokter user: {$dokter->name}\n";
echo "📧 Email: {$dokter->email}\n";
echo "🎭 Role: " . ($dokter->role['name'] ?? 'no role') . "\n";

// Check role permissions
if (isset($dokter->role['name']) && $dokter->role['name'] === 'dokter') {
    echo "✅ Role is correct: dokter\n";
    
    // Check if user has doctor-dashboard permission
    if (isset($dokter->role['permissions']) && in_array('doctor-dashboard', $dokter->role['permissions'])) {
        echo "✅ Has doctor-dashboard permission\n";
    } else {
        echo "❌ Missing doctor-dashboard permission\n";
    }
} else {
    echo "❌ Role is not dokter\n";
}

// Set authentication for testing
Auth::login($dokter);
echo "🔐 Logged in as dokter for testing\n";

// Test route access
echo "\n🌐 Testing route access:\n";
echo "- URL: http://localhost:8000/dokter/mobile-app\n";
echo "- User can access: " . (Auth::check() ? 'YES' : 'NO') . "\n";
echo "- Current user: " . (Auth::user() ? Auth::user()->name : 'None') . "\n";

// Create test URL with session
$testUrl = "http://localhost:8000/test-dokter-dashboard";
echo "\n🧪 Test URL created: {$testUrl}\n";
echo "\n📱 Instructions:\n";
echo "1. Open browser to: http://localhost:8000/dokter/mobile-app\n";
echo "2. Login with: 3333@dokter.local / password\n";
echo "3. Check if Gaming Bottom Navigation appears\n";
echo "4. Navigation should show: Home, Missions, Guardian, Rewards, Profile\n";

?>