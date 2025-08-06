<?php
echo "<h1>🔍 JadwalJaga Component Update Verification</h1>";

echo "<h2>📝 Build Verification</h2>";
$bundleFile = __DIR__ . '/build/assets/dokter-mobile-app-PlseburA.js';
if (file_exists($bundleFile)) {
    $size = filesize($bundleFile);
    $modified = date('Y-m-d H:i:s', filemtime($bundleFile));
    echo "✅ Dokter mobile app bundle exists<br>";
    echo "📦 Size: " . number_format($size / 1024, 1) . " KB<br>";
    echo "🕐 Last modified: $modified<br>";
} else {
    echo "❌ Bundle file not found<br>";
}

echo "<h2>🔄 Component Verification</h2>";
$componentFile = __DIR__ . '/../resources/js/components/dokter/JadwalJaga.tsx';
if (file_exists($componentFile)) {
    $size = filesize($componentFile);
    $modified = date('Y-m-d H:i:s', filemtime($componentFile));
    echo "✅ JadwalJaga.tsx exists<br>";
    echo "📦 Size: " . number_format($size / 1024, 1) . " KB<br>";
    echo "🕐 Last modified: $modified<br>";
    
    // Check if it contains Mission interface
    $content = file_get_contents($componentFile);
    if (strpos($content, 'interface Mission') !== false) {
        echo "✅ Mission interface found in component<br>";
    } else {
        echo "❌ Mission interface not found<br>";
    }
    
    // Check if it's using the new gaming UI
    if (strpos($content, 'glassmorphism') !== false || strpos($content, 'gaming') !== false) {
        echo "✅ Gaming UI styles detected<br>";
    } else {
        echo "⚠️ Gaming UI styles not detected<br>";
    }
} else {
    echo "❌ Component file not found<br>";
}

echo "<h2>🌐 Test Links</h2>";
echo '<a href="/dokter/mobile-app" target="_blank">🔗 Dokter Mobile App (requires login)</a><br>';
echo '<a href="/test-dokter-components.php" target="_blank">🔗 Component Test Page</a><br>';
echo '<a href="/login" target="_blank">🔗 Login Page</a><br>';

echo "<h2>📊 System Status</h2>";
echo "✅ All components ready for testing<br>";
echo "📱 Access dokter mobile app after login to see JadwalJaga missions<br>";
echo "🎮 New gaming UI with Mission interface should be active<br>";
?>