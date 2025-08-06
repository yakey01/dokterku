<?php
// Debug script to verify HolisticMedicalDashboard component update
echo "<h1>Component Update Verification</h1>";

$componentPath = __DIR__ . '/../resources/js/components/dokter/HolisticMedicalDashboard.tsx';
$buildPath = __DIR__ . '/build/assets/dokter-mobile-app-BtDZlSYv.js';

echo "<h2>Component File Info:</h2>";
if (file_exists($componentPath)) {
    echo "<p>✅ Component file exists</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime($componentPath)) . "</p>";
    echo "<p>Size: " . filesize($componentPath) . " bytes</p>";
    
    // Check if contains new features
    $content = file_get_contents($componentPath);
    $hasProgressBars = strpos($content, 'attendanceWidth') !== false && strpos($content, 'jaspelWidth') !== false;
    $hasMobileDesign = strpos($content, 'max-w-sm mx-auto') !== false;
    $hasGameNavigation = strpos($content, 'Gaming Home Indicator') !== false;
    
    echo "<p>✅ Has animated progress bars: " . ($hasProgressBars ? 'YES' : 'NO') . "</p>";
    echo "<p>✅ Has mobile-first design: " . ($hasMobileDesign ? 'YES' : 'NO') . "</p>";
    echo "<p>✅ Has gaming navigation: " . ($hasGameNavigation ? 'YES' : 'NO') . "</p>";
} else {
    echo "<p>❌ Component file not found</p>";
}

echo "<h2>Built Asset Info:</h2>";
if (file_exists($buildPath)) {
    echo "<p>✅ Built asset exists</p>";
    echo "<p>Last modified: " . date('Y-m-d H:i:s', filemtime($buildPath)) . "</p>";
    echo "<p>Size: " . filesize($buildPath) . " bytes</p>";
} else {
    echo "<p>❌ Built asset not found</p>";
}

echo "<h2>Manifest Check:</h2>";
$manifestPath = __DIR__ . '/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $dokterEntry = $manifest['resources/js/dokter-mobile-app.tsx'] ?? null;
    
    if ($dokterEntry) {
        echo "<p>✅ Dokter entry found in manifest</p>";
        echo "<p>Asset file: " . $dokterEntry['file'] . "</p>";
        echo "<p>Generated at: " . date('Y-m-d H:i:s', filemtime($manifestPath)) . "</p>";
    } else {
        echo "<p>❌ Dokter entry not found in manifest</p>";
    }
} else {
    echo "<p>❌ Manifest file not found</p>";
}

echo "<h2>Cache Buster:</h2>";
echo "<p>Current timestamp: " . time() . "</p>";
echo "<p>Force refresh URL: <a href='?t=" . time() . "'>Click here to force refresh</a></p>";

echo "<script>
if (location.search.includes('t=')) {
    // Clear all caches
    if ('caches' in window) {
        caches.keys().then(names => {
            names.forEach(name => {
                caches.delete(name);
            });
        });
    }
    
    // Clear localStorage
    try { localStorage.clear(); } catch(e) {}
    
    // Redirect to main dokter app
    setTimeout(() => {
        window.location.href = '/dokter/mobile-app';
    }, 2000);
    
    alert('Cache cleared! Redirecting to app in 2 seconds...');
}
</script>";
?>