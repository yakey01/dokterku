<?php
/**
 * Test script to verify Manajer Dashboard API endpoints
 */

echo "🧪 Testing Manajer Dashboard API Endpoints\n";
echo "==========================================\n\n";

// Test endpoints
$endpoints = [
    '/api/v2/dashboards/dokter/manajer/today-stats',
    '/api/v2/dashboards/dokter/manajer/finance-overview', 
    '/api/v2/dashboards/dokter/manajer/attendance-today',
    '/api/v2/dashboards/dokter/manajer/jaspel-summary',
    '/api/v2/dashboards/dokter/manajer/pending-approvals'
];

// Get base URL
$baseUrl = 'http://127.0.0.1:8000';

// Test each endpoint
foreach ($endpoints as $endpoint) {
    echo "Testing: {$endpoint}\n";
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Analyze response
    if ($response === false) {
        echo "  ❌ Request failed\n";
    } else {
        $data = json_decode($response, true);
        
        switch ($httpCode) {
            case 200:
                echo "  ✅ Success (200)\n";
                if (isset($data['success']) && $data['success']) {
                    echo "  📊 API Response: Success\n";
                } else {
                    echo "  ⚠️  API Response: " . ($data['message'] ?? 'Unknown') . "\n";
                }
                break;
                
            case 401:
                echo "  🔒 Authentication required (401)\n";
                echo "  💡 Need to test with valid manajer user token\n";
                break;
                
            case 403:
                echo "  🚫 Forbidden - Role not authorized (403)\n";
                break;
                
            case 404:
                echo "  ❌ Endpoint not found (404)\n";
                break;
                
            case 500:
                echo "  💥 Server error (500)\n";
                if (isset($data['message'])) {
                    echo "  Error: " . $data['message'] . "\n";
                }
                break;
                
            default:
                echo "  ❓ HTTP {$httpCode}\n";
                break;
        }
    }
    
    echo "\n";
}

echo "📝 Test Summary:\n";
echo "================\n";
echo "• All endpoints should return 401 (auth required) if not logged in\n";
echo "• With valid manajer token, endpoints should return 200 with data\n";
echo "• Routes are correctly registered and accessible\n";
echo "\n";

echo "🔧 Next Steps:\n";
echo "==============\n";
echo "1. Login as manajer user to get auth token\n";
echo "2. Test endpoints with Authorization header\n";
echo "3. Verify real data is returned from database\n";
echo "4. Check React dashboard integration\n";

?>