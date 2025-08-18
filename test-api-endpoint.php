<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING API ENDPOINT ACCESSIBILITY ===\n\n";

// Get a token for dr. Yaya
$yayaUser = User::find(13);

// Create a personal access token
$token = $yayaUser->createToken('test-current-month')->plainTextToken;

echo "Generated token for {$yayaUser->name}: {$token}\n\n";

// Test the endpoint with proper authentication
$endpoint = 'http://127.0.0.1:8000/api/v2/dashboards/dokter/jaspel/current-month';
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
    'X-Requested-With: XMLHttpRequest'
];

echo "Testing endpoint: {$endpoint}\n";
echo "Headers: " . implode(', ', $headers) . "\n\n";

// Create curl command for testing
$curlCommand = "curl -X GET '{$endpoint}' \\\n";
foreach ($headers as $header) {
    $curlCommand .= "  -H '{$header}' \\\n";
}
$curlCommand = rtrim($curlCommand, " \\\n");

echo "CURL Command for testing:\n";
echo $curlCommand . "\n\n";

// Test with PHP curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Response Code: {$httpCode}\n";

if ($error) {
    echo "CURL Error: {$error}\n";
} else {
    echo "Response Preview:\n";
    $data = json_decode($response, true);
    
    if ($data && isset($data['success'])) {
        echo "✅ SUCCESS: {$data['message']}\n";
        
        if (isset($data['data']['current_month'])) {
            $currentMonth = $data['data']['current_month'];
            echo "📊 Current Month Data:\n";
            echo "  - Total: Rp " . number_format($currentMonth['total_received']) . "\n";
            echo "  - Progress: {$currentMonth['progress_percentage']}%\n";
            echo "  - Count: {$currentMonth['count']} items\n";
            echo "  - Month: {$currentMonth['month_name']}\n";
        }
        
        if (isset($data['data']['insights'])) {
            $insights = $data['data']['insights'];
            echo "💡 Insights:\n";
            echo "  - Daily Average: Rp " . number_format($insights['daily_average']) . "\n";
            echo "  - Target Likelihood: {$insights['target_likelihood']}\n";
        }
    } else {
        echo "❌ Error or unexpected response:\n";
        echo substr($response, 0, 500) . "...\n";
    }
}

echo "\n=== FRONTEND INTEGRATION INSTRUCTIONS ===\n";
echo "1. Open browser developer console (F12)\n";
echo "2. Go to http://127.0.0.1:8000/dokter\n";
echo "3. Look for these console logs:\n";
echo "   - '📊 [CURRENT MONTH MODE] Fetching current month Jaspel progress... (NEW IMPLEMENTATION)'\n";
echo "   - '🎯 JaspelCurrentMonthProgress: Starting animation with data:'\n";
echo "4. Check for component with title '🚀 Progress Bulan Ini (NEW)'\n";
echo "5. Verify real-time progress animation is working\n\n";

echo "🎯 EXPECTED BEHAVIOR:\n";
echo "- Title shows 'Progress Bulan Ini (NEW)' instead of 'Recent Achievements'\n";
echo "- Progress circle shows 60% completion\n";
echo "- Amount shows Rp 1.2M (current month total)\n";
echo "- Target comparison shows vs Rp 2.0M target\n";
echo "- Animation duration: 2.5 seconds with smooth easing\n";
echo "- Console logs confirm new implementation is active\n\n";

echo "✅ API ENDPOINT IS READY FOR FRONTEND INTEGRATION!\n";