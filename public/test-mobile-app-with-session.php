<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "<h2>Testing Mobile App with Session</h2>";

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
    
    // Test mobile-app endpoint with session
    echo "<h3>Testing Mobile App Endpoint with Session:</h3>";
    
    // Create request to mobile-app with session
    $request = \Illuminate\Http\Request::create('/dokter/mobile-app', 'GET');
    $request->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15');
    
    // Set session
    $request->setLaravelSession(app('session.store'));
    
    // Handle request
    $response = app()->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "<p>Status Code: {$statusCode}</p>";
    
    if ($statusCode === 200) {
        echo "<p>✅ Mobile app endpoint is working with session</p>";
        
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
        
        // Check for built assets
        if (strpos($content, 'dokter-mobile-app-BEMZObSl.js') !== false) {
            echo "<p>✅ Found built asset reference</p>";
        } else {
            echo "<p>❌ Built asset reference not found</p>";
        }
        
        // Show first 1000 characters of response
        echo "<h4>Response Preview:</h4>";
        echo "<pre>" . htmlspecialchars(substr($content, 0, 1000)) . "...</pre>";
        
        // Check if there are any script loading errors
        echo "<h4>Checking for Potential Issues:</h4>";
        
        if (strpos($content, 'Failed to load resource') !== false) {
            echo "<p>❌ Found 'Failed to load resource' in response</p>";
        } else {
            echo "<p>✅ No 'Failed to load resource' found</p>";
        }
        
        if (strpos($content, 'Could not connect to the server') !== false) {
            echo "<p>❌ Found 'Could not connect to the server' in response</p>";
        } else {
            echo "<p>✅ No 'Could not connect to the server' found</p>";
        }
        
        // Check if the built asset file exists
        $assetPath = public_path('build/assets/js/dokter-mobile-app-BEMZObSl.js');
        if (file_exists($assetPath)) {
            echo "<p>✅ Built asset file exists: assets/js/dokter-mobile-app-BEMZObSl.js</p>";
            
            // Check file size
            $fileSize = filesize($assetPath);
            echo "<p>📁 Asset file size: " . number_format($fileSize) . " bytes</p>";
            
            if ($fileSize > 0) {
                echo "<p>✅ Asset file is not empty</p>";
            } else {
                echo "<p>❌ Asset file is empty</p>";
            }
        } else {
            echo "<p>❌ Built asset file missing: assets/js/dokter-mobile-app-BEMZObSl.js</p>";
        }
        
    } else {
        echo "<p>❌ Mobile app endpoint returned status {$statusCode}</p>";
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
    }
    
} else {
    echo "<p>❌ Failed to login</p>";
}

// Logout
Auth::logout();
?>
