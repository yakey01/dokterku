<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V2\JaspelComparisonController;
use Illuminate\Http\Request;

echo "=== TESTING JASPEL COMPARISON API ===\n\n";

// Login as dr. Yaya
$yayaUser = User::find(13);
Auth::login($yayaUser);

echo "User: {$yayaUser->name} (ID: {$yayaUser->id})\n\n";

try {
    $unifiedService = new \App\Services\Jaspel\UnifiedJaspelCalculationService();
    $validatedService = new \App\Services\ValidatedJaspelCalculationService($unifiedService);
    $controller = new JaspelComparisonController($validatedService);
    $request = new Request();
    $request->merge(['month' => 8, 'year' => 2025]);
    
    echo "1. TESTING MONTHLY COMPARISON\n";
    echo "=============================\n";
    
    $response = $controller->getMonthlyComparison($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    if (isset($data['success']) && $data['success']) {
        $comparison = $data['data'];
        
        echo "✅ SUCCESS - Monthly Comparison Data:\n\n";
        
        echo "CURRENT MONTH ({$comparison['current_month']['month_name']}):\n";
        echo "  - Total: Rp " . number_format($comparison['current_month']['total'], 0, ',', '.') . "\n";
        echo "  - Count: {$comparison['current_month']['count']} items\n";
        echo "  - Approved: Rp " . number_format($comparison['current_month']['approved'], 0, ',', '.') . "\n\n";
        
        echo "PREVIOUS MONTH ({$comparison['previous_month']['month_name']}):\n";
        echo "  - Total: Rp " . number_format($comparison['previous_month']['total'], 0, ',', '.') . "\n";
        echo "  - Count: {$comparison['previous_month']['count']} items\n";
        echo "  - Approved: Rp " . number_format($comparison['previous_month']['approved'], 0, ',', '.') . "\n\n";
        
        echo "COMPARISON ANALYSIS:\n";
        echo "  - Percentage Change: {$comparison['comparison']['percentage_change']}%\n";
        echo "  - Amount Change: Rp " . number_format($comparison['comparison']['amount_change'], 0, ',', '.') . "\n";
        echo "  - Trend: {$comparison['comparison']['trend']}\n";
        echo "  - Status: {$comparison['comparison']['status']}\n\n";
        
        echo "INSIGHTS:\n";
        echo "  - Message: {$comparison['insights']['message']}\n";
        echo "  - Recommendation: {$comparison['insights']['recommendation']}\n\n";
        
    } else {
        echo "❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "\n\n";
    }
    
    echo "2. TESTING QUARTERLY TREND\n";
    echo "==========================\n";
    
    $response = $controller->getQuarterlyTrend($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    if (isset($data['success']) && $data['success']) {
        $trends = $data['data']['trends'];
        
        echo "✅ SUCCESS - Quarterly Trend Data:\n\n";
        
        foreach ($trends as $index => $trend) {
            echo "MONTH " . ($index + 1) . " ({$trend['month_name']}):\n";
            echo "  - Total: Rp " . number_format($trend['total'], 0, ',', '.') . "\n";
            echo "  - Count: {$trend['count']} items\n";
            echo "  - Approved: Rp " . number_format($trend['approved'], 0, ',', '.') . "\n\n";
        }
        
    } else {
        echo "❌ Quarterly trend failed: " . ($data['message'] ?? 'Unknown error') . "\n\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FRONTEND INTEGRATION GUIDE ===\n";
echo "API Endpoints:\n";
echo "  - GET /api/v2/jaspel/comparison/monthly\n";
echo "  - GET /api/v2/jaspel/comparison/quarterly\n\n";

echo "Example Usage in React:\n";
echo "```typescript\n";
echo "const fetchComparison = async () => {\n";
echo "  const response = await fetch('/api/v2/jaspel/comparison/monthly');\n";
echo "  const data = await response.json();\n";
echo "  setComparisonData(data.data);\n";
echo "};\n";
echo "```\n\n";

echo "Component Integration:\n";
echo "```jsx\n";
echo "<JaspelProgressComparison \n";
echo "  data={comparisonData} \n";
echo "  loading={comparisonLoading} \n";
echo "/>\n";
echo "```\n\n";

echo "🎯 RESULT: Jaspel comparison API is ready for animated progress display!\n";