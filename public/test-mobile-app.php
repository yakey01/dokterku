<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "<h2>Testing Mobile App Endpoint</h2>";

// Test different ports
$ports = [8000, 8001, 8002, 8003, 8080];
$baseUrl = 'http://localhost';

echo "<h3>Testing Server Connectivity:</h3>";

foreach ($ports as $port) {
    $url = "{$baseUrl}:{$port}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<p>❌ Port {$port}: Connection failed - {$error}</p>";
    } else {
        echo "<p>✅ Port {$port}: HTTP {$httpCode} - {$url}</p>";
    }
}

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
    
    // Test mobile-app endpoint
    echo "<h3>Testing Mobile App Endpoint:</h3>";
    
    // Create request to mobile-app
    $request = \Illuminate\Http\Request::create('/dokter/mobile-app', 'GET');
    $request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15');
    
    // Handle request
    $response = app()->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "<p>Status Code: {$statusCode}</p>";
    
    if ($statusCode === 200) {
        echo "<p>✅ Mobile app endpoint is working</p>";
        
        // Check if content contains expected elements
        if (strpos($content, 'dokter-mobile-app') !== false) {
            echo "<p>✅ Found dokter-mobile-app script reference</p>";
        } else {
            echo "<p>❌ dokter-mobile-app script not found in response</p>";
        }
        
        if (strpos($content, 'KLINIK DOKTERKU') !== false) {
            echo "<p>✅ Found expected title</p>";
        } else {
            echo "<p>❌ Expected title not found</p>";
        }
        
        // Check for Vite assets
        if (strpos($content, '@vite') !== false) {
            echo "<p>✅ Found Vite directive</p>";
        } else {
            echo "<p>❌ Vite directive not found</p>";
        }
        
        // Show first 500 characters of response
        echo "<h4>Response Preview:</h4>";
        echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";
        
    } else {
        echo "<p>❌ Mobile app endpoint returned status {$statusCode}</p>";
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
    }
    
    // Test API endpoint that mobile app might call
    echo "<h3>Testing API Endpoints:</h3>";
    
    $apiEndpoints = [
        '/api/v2/dashboards/dokter/jadwal-jaga',
        '/api/v2/dashboards/dokter',
        '/api/v2/dashboards/dokter/jaspel'
    ];
    
    foreach ($apiEndpoints as $endpoint) {
        $apiRequest = \Illuminate\Http\Request::create($endpoint, 'GET');
        $apiRequest->headers->set('Accept', 'application/json');
        $apiRequest->headers->set('Content-Type', 'application/json');
        $apiRequest->headers->set('X-CSRF-TOKEN', csrf_token());
        $apiRequest->headers->set('X-Requested-With', 'XMLHttpRequest');
        
        $apiResponse = app()->handle($apiRequest);
        $apiStatusCode = $apiResponse->getStatusCode();
        
        if ($apiStatusCode === 200) {
            echo "<p>✅ {$endpoint}: HTTP {$apiStatusCode}</p>";
        } else {
            echo "<p>❌ {$endpoint}: HTTP {$apiStatusCode}</p>";
        }
    }
    
    // Check Vite build status
    echo "<h3>Checking Vite Build Status:</h3>";
    
    $manifestPath = public_path('build/manifest.json');
    if (file_exists($manifestPath)) {
        echo "<p>✅ Manifest file exists</p>";
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if (isset($manifest['resources/js/dokter-mobile-app.tsx'])) {
            echo "<p>✅ dokter-mobile-app.tsx found in manifest</p>";
            $assetPath = $manifest['resources/js/dokter-mobile-app.tsx']['file'];
            $fullAssetPath = public_path('build/' . $assetPath);
            
            if (file_exists($fullAssetPath)) {
                echo "<p>✅ Built asset exists: {$assetPath}</p>";
            } else {
                echo "<p>❌ Built asset missing: {$assetPath}</p>";
            }
        } else {
            echo "<p>❌ dokter-mobile-app.tsx not found in manifest</p>";
        }
    } else {
        echo "<p>❌ Manifest file not found</p>";
    }
    
} else {
    echo "<p>❌ Failed to login</p>";
}

// Logout
Auth::logout();
?>
