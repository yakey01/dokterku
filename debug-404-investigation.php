<?php
/**
 * 404 JavaScript Bundle Investigation Script
 * 
 * This script investigates the root cause of the missing 
 * dokter-mobile-app-oyAz7MK-.js file error and provides solutions.
 */

echo "🔍 DOKTERKU 404 JavaScript Bundle Investigation\n";
echo "===============================================\n\n";

// 1. Verify Vite Manifest
echo "1️⃣ VITE MANIFEST VERIFICATION:\n";
$manifestPath = __DIR__ . '/public/build/manifest.json';
if (!file_exists($manifestPath)) {
    echo "❌ CRITICAL: Vite manifest.json not found at: $manifestPath\n";
    echo "   Solution: Run 'npm run build'\n\n";
    exit(1);
}

$manifest = json_decode(file_get_contents($manifestPath), true);
if (!$manifest) {
    echo "❌ CRITICAL: Vite manifest.json is corrupted\n";
    echo "   Solution: Run 'npm run build'\n\n";
    exit(1);
}

$dokterAppEntry = $manifest['resources/js/dokter-mobile-app.tsx'] ?? null;
if (!$dokterAppEntry) {
    echo "❌ CRITICAL: dokter-mobile-app.tsx not found in manifest\n";
    echo "   Solution: Check vite.config.js input configuration\n\n";
    exit(1);
}

echo "✅ Manifest found with dokter-mobile-app.tsx entry\n";
echo "   File: {$dokterAppEntry['file']}\n";
echo "   Generated: " . date('Y-m-d H:i:s', filemtime($manifestPath)) . "\n\n";

// 2. Verify Physical Files
echo "2️⃣ PHYSICAL FILE VERIFICATION:\n";
$jsFilePath = __DIR__ . '/public/build/' . $dokterAppEntry['file'];
if (!file_exists($jsFilePath)) {
    echo "❌ CRITICAL: JavaScript bundle file not found at: $jsFilePath\n";
    echo "   Solution: Run 'npm run build'\n\n";
    exit(1);
}

$fileSize = filesize($jsFilePath);
$fileModified = date('Y-m-d H:i:s', filemtime($jsFilePath));
echo "✅ JavaScript bundle exists\n";
echo "   Path: $jsFilePath\n";
echo "   Size: " . number_format($fileSize) . " bytes\n";
echo "   Modified: $fileModified\n\n";

// 3. Verify CSS Files
echo "3️⃣ CSS FILES VERIFICATION:\n";
$cssFiles = $dokterAppEntry['css'] ?? [];
foreach ($cssFiles as $cssFile) {
    $cssPath = __DIR__ . '/public/build/' . $cssFile;
    if (file_exists($cssPath)) {
        echo "✅ CSS: $cssFile (" . number_format(filesize($cssPath)) . " bytes)\n";
    } else {
        echo "❌ Missing CSS: $cssFile\n";
    }
}
echo "\n";

// 4. Check Vite Configuration
echo "4️⃣ VITE CONFIGURATION CHECK:\n";
$viteConfigPath = __DIR__ . '/vite.config.js';
if (!file_exists($viteConfigPath)) {
    echo "❌ vite.config.js not found\n\n";
} else {
    $viteConfig = file_get_contents($viteConfigPath);
    if (strpos($viteConfig, 'dokter-mobile-app.tsx') !== false) {
        echo "✅ dokter-mobile-app.tsx found in vite.config.js input\n";
    } else {
        echo "❌ dokter-mobile-app.tsx NOT found in vite.config.js input\n";
        echo "   Solution: Add 'resources/js/dokter-mobile-app.tsx' to vite input array\n";
    }
    echo "\n";
}

// 5. Check Source File
echo "5️⃣ SOURCE FILE VERIFICATION:\n";
$sourcePath = __DIR__ . '/resources/js/dokter-mobile-app.tsx';
if (!file_exists($sourcePath)) {
    echo "❌ CRITICAL: Source file not found at: $sourcePath\n\n";
    exit(1);
}

$sourceSize = filesize($sourcePath);
$sourceModified = date('Y-m-d H:i:s', filemtime($sourcePath));
echo "✅ Source file exists\n";
echo "   Path: $sourcePath\n";
echo "   Size: " . number_format($sourceSize) . " bytes\n";
echo "   Modified: $sourceModified\n\n";

// 6. Check Laravel Asset Helper
echo "6️⃣ LARAVEL ASSET HELPER CHECK:\n";
try {
    // Simulate Laravel Vite helper
    $buildPath = '/build';
    $expectedUrl = $buildPath . '/' . $dokterAppEntry['file'];
    echo "✅ Expected asset URL: $expectedUrl\n\n";
} catch (Exception $e) {
    echo "❌ Laravel asset helper error: " . $e->getMessage() . "\n\n";
}

// 7. HTTP Accessibility Test
echo "7️⃣ HTTP ACCESSIBILITY TEST:\n";
$testUrl = "http://127.0.0.1:8000/build/" . $dokterAppEntry['file'];
echo "Testing URL: $testUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ File is accessible via HTTP (Status: $httpCode)\n\n";
} else {
    echo "❌ File is NOT accessible via HTTP (Status: $httpCode)\n";
    echo "   Possible causes:\n";
    echo "   - Server not running on port 8000\n";
    echo "   - .htaccess blocking assets\n";
    echo "   - Laravel routing issues\n\n";
}

// 8. Browser Cache Analysis
echo "8️⃣ BROWSER CACHE ANALYSIS:\n";
echo "The error 'dokter-mobile-app-oyAz7MK-.js' suggests a browser cache issue.\n";
echo "Current file hash: " . basename($dokterAppEntry['file'], '.js') . "\n";
echo "If browser is requesting different hash, it's a cache issue.\n\n";

// 9. Solutions Summary
echo "🔧 SOLUTIONS SUMMARY:\n";
echo "=====================\n\n";

echo "If the file exists but browser shows 404:\n\n";

echo "IMMEDIATE FIXES:\n";
echo "1. Hard refresh in browser (Ctrl+Shift+R or Cmd+Shift+R)\n";
echo "2. Clear browser cache completely\n";
echo "3. Open DevTools > Application > Storage > Clear site data\n";
echo "4. Try incognito/private browsing mode\n\n";

echo "CACHE BUSTING FIXES:\n";
echo "1. Add timestamp to assets:\n";
echo "   php artisan optimize:clear\n";
echo "   npm run build\n\n";

echo "AGGRESSIVE CACHE PREVENTION:\n";
echo "1. Check if template has aggressive cache headers\n";
echo "2. Verify no service worker is caching assets\n";
echo "3. Add cache-busting parameters to @vite directive\n\n";

echo "VERIFICATION STEPS:\n";
echo "1. Visit: $testUrl\n";
echo "2. Check if file downloads correctly\n";
echo "3. If yes, it's a browser cache issue\n";
echo "4. If no, it's a server configuration issue\n\n";

echo "LARAVEL SPECIFIC:\n";
echo "1. Check APP_ENV is set correctly\n";
echo "2. Verify public/build directory permissions\n";
echo "3. Ensure Laravel Vite plugin is properly configured\n\n";

// 10. Real-time Test
echo "🧪 REAL-TIME TEST:\n";
echo "==================\n";
$currentTime = time();
echo "Current timestamp: $currentTime\n";
echo "Manual test URL: http://127.0.0.1:8000/build/{$dokterAppEntry['file']}?v=$currentTime\n\n";

echo "✅ Investigation complete!\n";
echo "If all checks pass but browser still shows 404, the issue is browser cache.\n";
echo "The solution is to force a hard refresh or clear browser cache.\n";