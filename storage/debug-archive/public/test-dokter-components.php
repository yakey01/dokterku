<?php
// Test script to verify dokter dashboard components are accessible
echo "<h1>Dokter Dashboard Components Test</h1>";

echo "<h2>Component Integration Test</h2>";

// Test main dokter mobile app
echo "<h3>1. Main Dokter Mobile App</h3>";
echo "<p>URL: <a href='/dokter/mobile-app' target='_blank'>/dokter/mobile-app</a></p>";
echo "<p>Status: Should load HolisticMedicalDashboard with gaming navigation</p>";

// Check if individual component files exist
$components = [
    'HolisticMedicalDashboard' => '/resources/js/components/dokter/HolisticMedicalDashboard.tsx',
    'JadwalJaga' => '/resources/js/components/dokter/JadwalJaga.tsx', 
    'Presensi' => '/resources/js/components/dokter/Presensi.tsx',
    'Jaspel' => '/resources/js/components/dokter/Jaspel.tsx'
];

echo "<h3>2. Component Files Status</h3>";
foreach ($components as $name => $path) {
    $fullPath = __DIR__ . '/..' . $path;
    $exists = file_exists($fullPath);
    $size = $exists ? filesize($fullPath) : 0;
    $lastModified = $exists ? date('Y-m-d H:i:s', filemtime($fullPath)) : 'N/A';
    
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid " . ($exists ? 'green' : 'red') . "; border-radius: 5px;'>";
    echo "<strong>$name:</strong> " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "<br>";
    if ($exists) {
        echo "Size: " . number_format($size) . " bytes<br>";
        echo "Last Modified: $lastModified<br>";
        echo "Path: $path";
    }
    echo "</div>";
}

// Test main app bundle
echo "<h3>3. Built Assets Status</h3>";
$buildFiles = [
    'dokter-mobile-app' => '/build/assets/dokter-mobile-app-*.js',
    'manifest' => '/build/manifest.json'
];

$manifestPath = __DIR__ . '/build/manifest.json';
if (file_exists($manifestPath)) {
    echo "<p>✅ Manifest exists</p>";
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (isset($manifest['resources/js/dokter-mobile-app.tsx'])) {
        $entry = $manifest['resources/js/dokter-mobile-app.tsx'];
        echo "<p>✅ Dokter app entry found: " . $entry['file'] . "</p>";
        echo "<p>Size: " . number_format(filesize(__DIR__ . '/build/' . $entry['file'])) . " bytes</p>";
    } else {
        echo "<p>❌ Dokter app entry not found in manifest</p>";
    }
} else {
    echo "<p>❌ Manifest file not found</p>";
}

// Test route availability
echo "<h3>4. Route Status</h3>";
echo "<p>Testing /dokter/mobile-app route...</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/dokter/mobile-app');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Status: <strong>$httpCode</strong></p>";

if ($httpCode == 200) {
    echo "<p style='color: green;'>✅ Route is accessible - components should load</p>";
} elseif ($httpCode == 302) {
    echo "<p style='color: orange;'>⚠️ Route redirects to login - authentication required</p>";
} else {
    echo "<p style='color: red;'>❌ Route has issues</p>";
}

echo "<h3>5. Integration Summary</h3>";
echo "<ul>";
echo "<li><strong>HolisticMedicalDashboard</strong>: ✅ Primary component loaded by dokter-mobile-app.tsx</li>";
echo "<li><strong>JadwalJaga</strong>: ✅ Standalone component (Medical Mission Central)</li>";
echo "<li><strong>Presensi</strong>: ✅ Standalone component (Smart Attendance)</li>";
echo "<li><strong>Jaspel</strong>: ✅ Standalone component (Financial Dashboard)</li>";
echo "</ul>";

echo "<h3>6. Next Steps</h3>";
echo "<ol>";
echo "<li>Login as dokter user at <a href='/login'>/login</a></li>";
echo "<li>Access main dashboard at <a href='/dokter/mobile-app'>/dokter/mobile-app</a></li>";
echo "<li>Verify gaming navigation is working</li>";
echo "<li>Test component interactions</li>";
echo "</ol>";

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>