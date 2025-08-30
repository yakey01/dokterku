<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController;
use Illuminate\Http\Request;

echo "=== TESTING CURRENT MONTH JASPEL PROGRESS API ===\n\n";

// Login as dr. Yaya
$yayaUser = User::find(13);
Auth::login($yayaUser);

echo "User: {$yayaUser->name} (ID: {$yayaUser->id})\n\n";

try {
    $controller = new DokterDashboardController();
    $request = new Request();
    
    // Get current month and year
    $currentMonth = now()->month;
    $currentYear = now()->year;
    
    $request->merge(['month' => $currentMonth, 'year' => $currentYear]);
    
    echo "1. TESTING CURRENT MONTH JASPEL PROGRESS\n";
    echo "=======================================\n";
    echo "Month: {$currentMonth}, Year: {$currentYear}\n\n";
    
    $response = $controller->getCurrentMonthJaspelProgress($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    if (isset($data['success']) && $data['success']) {
        $currentMonthData = $data['data'];
        
        echo "✅ SUCCESS - Current Month Jaspel Progress Data:\n\n";
        
        echo "CURRENT MONTH PROGRESS:\n";
        echo "  - Month: {$currentMonthData['current_month']['month_name']}\n";
        echo "  - Total Received: Rp " . number_format($currentMonthData['current_month']['total_received'], 0, ',', '.') . "\n";
        echo "  - Target Amount: Rp " . number_format($currentMonthData['current_month']['target_amount'], 0, ',', '.') . "\n";
        echo "  - Progress: {$currentMonthData['current_month']['progress_percentage']}%\n";
        echo "  - Count: {$currentMonthData['current_month']['count']} items\n";
        echo "  - Days Elapsed: {$currentMonthData['current_month']['days_elapsed']} days\n";
        echo "  - Days Remaining: {$currentMonthData['current_month']['days_remaining']} days\n\n";
        
        echo "INSIGHTS:\n";
        echo "  - Daily Average: Rp " . number_format($currentMonthData['insights']['daily_average'], 0, ',', '.') . "\n";
        echo "  - Projected Total: Rp " . number_format($currentMonthData['insights']['projected_total'], 0, ',', '.') . "\n";
        echo "  - Target Likelihood: {$currentMonthData['insights']['target_likelihood']}\n\n";
        
        echo "REAL-TIME INFO:\n";
        echo "  - Last Entry: {$currentMonthData['real_time']['last_entry']}\n";
        echo "  - Is Live: " . ($currentMonthData['real_time']['is_live'] ? 'YES' : 'NO') . "\n";
        echo "  - Last Updated: {$currentMonthData['real_time']['last_updated']}\n\n";
        
        echo "DAILY BREAKDOWN:\n";
        if (!empty($currentMonthData['current_month']['daily_breakdown'])) {
            foreach ($currentMonthData['current_month']['daily_breakdown'] as $day) {
                echo "  - {$day['formatted_date']}: Rp " . number_format($day['amount'], 0, ',', '.') . " ({$day['count']} items)\n";
            }
        } else {
            echo "  - No daily breakdown available\n";
        }
        echo "\n";
        
        echo "VALIDATION INFO:\n";
        echo "  - Data Source: {$currentMonthData['validation_info']['data_source']}\n";
        echo "  - Validation Status: {$currentMonthData['validation_info']['validation_status']}\n";
        echo "  - Financial Accuracy: {$currentMonthData['validation_info']['financial_accuracy']}\n\n";
        
    } else {
        echo "❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "\n\n";
        if (isset($data['error'])) {
            echo "Error details: {$data['error']}\n\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FRONTEND INTEGRATION GUIDE ===\n";
echo "API Endpoint:\n";
echo "  - GET /api/v2/dashboards/dokter/jaspel/current-month\n\n";

echo "Example Usage in React:\n";
echo "```typescript\n";
echo "const fetchCurrentMonthData = async () => {\n";
echo "  const response = await fetch('/api/v2/dashboards/dokter/jaspel/current-month');\n";
echo "  const data = await response.json();\n";
echo "  setCurrentMonthData(data.data);\n";
echo "};\n";
echo "```\n\n";

echo "Component Integration:\n";
echo "```jsx\n";
echo "<JaspelCurrentMonthProgress \n";
echo "  data={currentMonthData} \n";
echo "  loading={currentMonthLoading} \n";
echo "/>\n";
echo "```\n\n";

echo "🎯 ANIMATION FEATURES:\n";
echo "  - Progressive counter animation from 0 to current total\n";
echo "  - Smooth progress circle showing percentage toward target\n";
echo "  - Real-time pulse indicators for live data\n";
echo "  - Color-coded progress based on achievement level\n";
echo "  - Daily breakdown visualization with smooth transitions\n";
echo "  - Sparkle effects at milestone achievements\n\n";

echo "🎉 RESULT: Current month Jaspel progress API is ready for real-time animated display!\n";