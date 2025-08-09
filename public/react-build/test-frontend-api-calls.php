<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "<h2>Testing Frontend API Calls</h2>";

// Find Yaya (user ID 13)
$yaya = User::find(13);

if (!$yaya) {
    echo "<p>❌ User Yaya not found</p>";
    exit;
}

echo "<p>Found user: " . $yaya->name . " (ID: " . $yaya->id . ")</p>";

// Login as Yaya
Auth::login($yaya);

if (Auth::check()) {
    echo "<p>✅ Successfully logged in as Yaya</p>";
    
    // Test API endpoints that frontend might call
    echo "<h3>Testing Frontend API Endpoints:</h3>";
    
    $apiEndpoints = [
        '/api/v2/dashboards/dokter/jadwal-jaga',
        '/api/v2/dashboards/dokter',
        '/api/v2/dashboards/dokter/jaspel',
        '/api/v2/dashboards/dokter/tindakan',
        '/api/v2/dashboards/dokter/presensi',
        '/api/v2/dashboards/dokter/attendance',
        '/api/v2/dashboards/dokter/patients',
        '/api/v2/dashboards/dokter/schedules',
        '/api/v2/dashboards/dokter/weekly-schedules',
        '/api/v2/dashboards/dokter/igd-schedules',
        '/api/v2/dashboards/dokter/work-location/status',
        '/api/v2/dashboards/dokter/auth-test'
    ];
    
    foreach ($apiEndpoints as $endpoint) {
        $request = \Illuminate\Http\Request::create($endpoint, 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-CSRF-TOKEN', csrf_token());
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setLaravelSession(app('session.store'));
        
        $response = app()->handle($request);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        
        if ($statusCode === 200) {
            echo "<p>✅ {$endpoint}: HTTP {$statusCode}</p>";
            
            // Check if response is valid JSON
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<p>  ✅ Valid JSON response</p>";
                
                // Check for success field
                if (isset($data['success'])) {
                    echo "<p>  ✅ Has 'success' field: " . ($data['success'] ? 'true' : 'false') . "</p>";
                } else {
                    echo "<p>  ⚠️ No 'success' field found</p>";
                }
                
                // Check for data field
                if (isset($data['data'])) {
                    echo "<p>  ✅ Has 'data' field</p>";
                } else {
                    echo "<p>  ⚠️ No 'data' field found</p>";
                }
            } else {
                echo "<p>  ❌ Invalid JSON response</p>";
            }
        } else {
            echo "<p>❌ {$endpoint}: HTTP {$statusCode}</p>";
            
            // Show error content
            if (strlen($content) < 500) {
                echo "<p>  Error: " . htmlspecialchars($content) . "</p>";
            }
        }
    }
    
    // Test with different headers that frontend might use
    echo "<h3>Testing with Different Headers:</h3>";
    
    $testHeaders = [
        'Mobile Headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
        ],
        'Web Headers' => [
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'X-CSRF-TOKEN' => csrf_token(),
            'X-Requested-With' => 'XMLHttpRequest',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
        ],
        'Minimal Headers' => [
            'Accept' => 'application/json',
            'X-CSRF-TOKEN' => csrf_token()
        ]
    ];
    
    foreach ($testHeaders as $headerName => $headers) {
        echo "<h4>Testing with {$headerName}:</h4>";
        
        $request = \Illuminate\Http\Request::create('/api/v2/dashboards/dokter/jadwal-jaga', 'GET');
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }
        $request->setLaravelSession(app('session.store'));
        
        $response = app()->handle($request);
        $statusCode = $response->getStatusCode();
        
        if ($statusCode === 200) {
            echo "<p>✅ {$headerName}: HTTP {$statusCode}</p>";
        } else {
            echo "<p>❌ {$headerName}: HTTP {$statusCode}</p>";
        }
    }
    
    // Test CORS headers
    echo "<h3>Testing CORS Headers:</h3>";
    
    $corsRequest = \Illuminate\Http\Request::create('/api/v2/dashboards/dokter/jadwal-jaga', 'GET');
    $corsRequest->headers->set('Accept', 'application/json');
    $corsRequest->headers->set('X-CSRF-TOKEN', csrf_token());
    $corsRequest->headers->set('Origin', 'http://localhost:8003');
    $corsRequest->headers->set('Referer', 'http://localhost:8003/dokter/mobile-app');
    $corsRequest->setLaravelSession(app('session.store'));
    
    $corsResponse = app()->handle($corsRequest);
    $corsStatusCode = $corsResponse->getStatusCode();
    
    if ($corsStatusCode === 200) {
        echo "<p>✅ CORS request: HTTP {$corsStatusCode}</p>";
    } else {
        echo "<p>❌ CORS request: HTTP {$corsStatusCode}</p>";
    }
    
} else {
    echo "<p>❌ Failed to login</p>";
}

// Logout
Auth::logout();
?>
