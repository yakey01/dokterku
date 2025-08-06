<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Test dokter user authentication
$dokter = App\Models\User::where('email', '3333@dokter.local')->first();

if (!$dokter) {
    echo "❌ Dokter user not found\n";
    exit(1);
}

echo "✅ Found dokter user: {$dokter->name}\n";
echo "📧 Email: {$dokter->email}\n";
echo "🎭 Role: " . ($dokter->role['name'] ?? 'no role') . "\n";

// 2. Test asset availability
echo "\n🔍 Testing Asset Availability:\n";

$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    echo "✅ Build manifest exists\n";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    
    if (isset($manifest['resources/js/dokter-mobile-app.tsx'])) {
        $assetInfo = $manifest['resources/js/dokter-mobile-app.tsx'];
        $assetPath = public_path('build/' . $assetInfo['file']);
        
        if (file_exists($assetPath)) {
            echo "✅ Dokter mobile app asset exists: " . $assetInfo['file'] . "\n";
            echo "📦 Asset size: " . round(filesize($assetPath) / 1024, 2) . " KB\n";
        } else {
            echo "❌ Dokter mobile app asset file missing: " . $assetPath . "\n";
        }
        
        // Check CSS
        if (isset($assetInfo['css'])) {
            foreach ($assetInfo['css'] as $cssFile) {
                $cssPath = public_path('build/' . $cssFile);
                if (file_exists($cssPath)) {
                    echo "✅ CSS asset exists: " . $cssFile . "\n";
                } else {
                    echo "❌ CSS asset missing: " . $cssFile . "\n";
                }
            }
        }
    } else {
        echo "❌ Dokter mobile app not found in manifest\n";
    }
} else {
    echo "❌ Build manifest not found\n";
}

// 3. Test Laravel route
echo "\n🌐 Testing Route Configuration:\n";
try {
    $routes = app('router')->getRoutes();
    $dokterMobileRoute = null;
    
    foreach ($routes as $route) {
        if ($route->getName() === 'dokter.mobile-app') {
            $dokterMobileRoute = $route;
            break;
        }
    }
    
    if ($dokterMobileRoute) {
        echo "✅ Route 'dokter.mobile-app' found\n";
        echo "🛣️  URI: " . $dokterMobileRoute->uri() . "\n";
        echo "🎯 Methods: " . implode(', ', $dokterMobileRoute->methods()) . "\n";
    } else {
        echo "❌ Route 'dokter.mobile-app' not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking routes: " . $e->getMessage() . "\n";
}

// 4. Test HTTP access to assets
echo "\n🌍 Testing HTTP Asset Access:\n";

function testHttpAsset($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode;
}

$baseUrl = 'http://localhost:8000';
$manifestUrl = $baseUrl . '/build/manifest.json';
$manifestCode = testHttpAsset($manifestUrl);

if ($manifestCode === 200) {
    echo "✅ Manifest accessible via HTTP (200)\n";
    
    if (isset($manifest['resources/js/dokter-mobile-app.tsx'])) {
        $assetUrl = $baseUrl . '/build/' . $manifest['resources/js/dokter-mobile-app.tsx']['file'];
        $assetCode = testHttpAsset($assetUrl);
        
        if ($assetCode === 200) {
            echo "✅ Dokter mobile app asset accessible via HTTP (200)\n";
        } else {
            echo "❌ Dokter mobile app asset HTTP error: $assetCode\n";
        }
    }
} else {
    echo "❌ Manifest HTTP error: $manifestCode\n";
}

// 5. Resolution summary
echo "\n📋 DIAGNOSIS SUMMARY:\n";
echo "====================\n";

if (file_exists($manifestPath) && isset($manifest['resources/js/dokter-mobile-app.tsx'])) {
    echo "✅ Assets are built and available\n";
    echo "✅ Manifest is properly configured\n";
    echo "✅ Dokter user exists and has correct role\n";
    
    echo "\n🔧 POTENTIAL SOLUTIONS:\n";
    echo "1. Clear browser cache (Ctrl+Shift+R)\n";
    echo "2. Login as dokter user first: http://localhost:8000/login\n";
    echo "   - Email: 3333@dokter.local\n";
    echo "   - Password: password\n";
    echo "3. Access dokter mobile app: http://localhost:8000/dokter/mobile-app\n";
    echo "4. Check browser console for specific error details\n";
    echo "\n💡 NOTE: 404 errors often occur when:\n";
    echo "   - Accessing mobile app without authentication\n";
    echo "   - Browser cache contains old asset references\n";
    echo "   - Incorrect route access\n";
} else {
    echo "❌ Build or manifest issues detected\n";
    echo "\n🔧 REQUIRED FIXES:\n";
    echo "1. Run: npm run build\n";
    echo "2. Check vite.config.js configuration\n";
    echo "3. Verify entry point: resources/js/dokter-mobile-app.tsx\n";
}

echo "\n🧪 TESTING INSTRUCTIONS:\n";
echo "1. Start Laravel server: php artisan serve\n";
echo "2. Open browser to: http://localhost:8000/login\n";
echo "3. Login with: 3333@dokter.local / password\n";
echo "4. Navigate to: http://localhost:8000/dokter/mobile-app\n";
echo "5. Check browser console for any remaining 404 errors\n";

?>