<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 DEBUGGING 404 ERRORS - Real-time Analysis\n";
echo "============================================\n\n";

// 1. Check current browser session context
echo "📱 BROWSER SESSION CONTEXT:\n";
echo "When you see these errors, are you:\n";
echo "  A) On the login page trying to access dokter app?\n";
echo "  B) Already logged in and seeing errors in the app?\n";
echo "  C) Getting redirected and losing context?\n\n";

// 2. Analyze the specific error messages
echo "🚨 ERROR ANALYSIS:\n";
echo "Error 1: 'client, line 0' - This suggests a browser-side loading issue\n";
echo "Error 2: 'dokter-mobile-app.tsx, line 0' - The source file, not the built asset\n\n";

// 3. Check current Laravel serving status
echo "🌐 LARAVEL SERVER STATUS:\n";
$serverUrl = 'http://localhost:8000';

function quickHttpTest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode;
}

$homeTest = quickHttpTest($serverUrl);
echo "Laravel Home Page: " . ($homeTest === 200 ? "✅ $homeTest" : "❌ $homeTest") . "\n";

$loginTest = quickHttpTest($serverUrl . '/login');
echo "Login Page: " . ($loginTest === 200 ? "✅ $loginTest" : "❌ $loginTest") . "\n";

$dokterTest = quickHttpTest($serverUrl . '/dokter/mobile-app');
echo "Dokter Mobile App: " . ($dokterTest === 302 ? "🔄 $dokterTest (redirect to login)" : ($dokterTest === 200 ? "✅ $dokterTest" : "❌ $dokterTest")) . "\n\n";

// 4. Check browser-accessible asset paths
echo "🗂️ BROWSER ASSET ACCESSIBILITY:\n";

// Check if Vite dev server is running
$viteTest = quickHttpTest('http://localhost:5173');
echo "Vite Dev Server: " . ($viteTest === 200 ? "🔥 RUNNING" : "⏹️ STOPPED") . "\n";

// Check actual built assets
$manifestTest = quickHttpTest($serverUrl . '/build/manifest.json');
echo "Build Manifest: " . ($manifestTest === 200 ? "✅ $manifestTest" : "❌ $manifestTest") . "\n";

if ($manifestTest === 200) {
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    if (isset($manifest['resources/js/dokter-mobile-app.tsx'])) {
        $assetFile = $manifest['resources/js/dokter-mobile-app.tsx']['file'];
        $assetTest = quickHttpTest($serverUrl . '/build/' . $assetFile);
        echo "Dokter JS Asset: " . ($assetTest === 200 ? "✅ $assetTest" : "❌ $assetTest") . " ($assetFile)\n";
        
        if (isset($manifest['resources/js/dokter-mobile-app.tsx']['css'])) {
            foreach ($manifest['resources/js/dokter-mobile-app.tsx']['css'] as $cssFile) {
                $cssTest = quickHttpTest($serverUrl . '/build/' . $cssFile);
                echo "CSS Asset: " . ($cssTest === 200 ? "✅ $cssTest" : "❌ $cssTest") . " ($cssFile)\n";
            }
        }
    }
}

echo "\n";

// 5. Specific debugging for the error patterns
echo "🔬 SPECIFIC ERROR PATTERN ANALYSIS:\n";
echo "The errors you're seeing suggest:\n\n";

echo "1. 'client, line 0' = Browser can't load the initial script\n";
echo "   Possible causes:\n";
echo "   - Wrong asset path in HTML\n";
echo "   - Server not serving the file\n";
echo "   - CORS/security blocking\n\n";

echo "2. 'dokter-mobile-app.tsx, line 0' = Browser trying to load source file\n";
echo "   Possible causes:\n";
echo "   - Vite dev server expected but not running\n";
echo "   - @vite directive pointing to source instead of built asset\n";
echo "   - Development/production mode mismatch\n\n";

// 6. Check current Blade template
echo "📄 BLADE TEMPLATE ANALYSIS:\n";
$bladeTemplate = file_get_contents(__DIR__ . '/resources/views/mobile/dokter/app.blade.php');

if (strpos($bladeTemplate, '@vite') !== false) {
    echo "✅ Using @vite directive (correct)\n";
    
    // Extract the vite directive
    preg_match('/@vite\(\[(.*?)\]\)/', $bladeTemplate, $matches);
    if (!empty($matches[1])) {
        echo "📂 Vite entry point: " . trim($matches[1], "'\"") . "\n";
    }
} else {
    echo "❌ Not using @vite directive\n";
}

if (strpos($bladeTemplate, 'vite-fallback') !== false) {
    echo "⚠️ Found vite-fallback (should be removed)\n";
}

echo "\n";

// 7. Environment analysis
echo "🌍 ENVIRONMENT ANALYSIS:\n";
echo "APP_ENV: " . config('app.env') . "\n";
echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
echo "NODE_ENV: " . ($_SERVER['NODE_ENV'] ?? 'not set') . "\n\n";

// 8. Immediate action steps
echo "🚀 IMMEDIATE ACTION STEPS:\n";
echo "=====================================\n\n";

echo "Step 1: Stop Vite dev server if running\n";
echo "   pkill -f vite\n\n";

echo "Step 2: Ensure production build exists\n";
echo "   npm run build\n\n";

echo "Step 3: Clear browser cache completely\n";
echo "   Chrome: Ctrl+Shift+Del -> Clear everything\n";
echo "   Firefox: Ctrl+Shift+Del -> Clear everything\n\n";

echo "Step 4: Test in incognito/private mode\n";
echo "   Open incognito window\n";
echo "   Go to: http://localhost:8000/login\n";
echo "   Login: 3333@dokter.local / password\n";
echo "   Navigate: http://localhost:8000/dokter/mobile-app\n\n";

echo "Step 5: Check browser console immediately\n";
echo "   F12 -> Console tab\n";
echo "   Look for the EXACT 404 URL that's failing\n";
echo "   Screenshot and share the full error details\n\n";

// 9. Quick fix script
echo "💊 QUICK FIX COMMANDS:\n";
echo "=====================\n";
echo "Run these commands in order:\n\n";

echo "# Kill any running Vite servers\n";
echo "pkill -f vite\n\n";

echo "# Clean everything\n";
echo "rm -rf public/build/\n";
echo "rm -rf node_modules/.vite/\n\n";

echo "# Rebuild\n";
echo "npm run build\n\n";

echo "# Verify build\n";
echo "ls -la public/build/assets/\n";
echo "curl -I http://localhost:8000/build/manifest.json\n\n";

echo "# Test login flow\n";
echo "# 1. Open: http://localhost:8000/login\n";
echo "# 2. Login: 3333@dokter.local / password\n";
echo "# 3. Navigate: http://localhost:8000/dokter/mobile-app\n\n";

echo "🔍 IF ERRORS PERSIST:\n";
echo "===================\n";
echo "1. Share the EXACT 404 URL from browser console\n";
echo "2. Share screenshot of browser network tab\n";
echo "3. Run: php test-dokter-mobile-access.php\n";
echo "4. Check if you're accessing the correct URL\n\n";

echo "💡 MOST COMMON CAUSE:\n";
echo "====================\n";
echo "You're probably accessing the app without being logged in first!\n";
echo "ALWAYS login at /login before going to /dokter/mobile-app\n\n";

?>