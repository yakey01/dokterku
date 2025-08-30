<?php
// Debug tool to trace bendahara redirect issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Bendahara Debug Tool</title>";
echo "<style>body{background:#0a0a0b;color:#fff;font-family:monospace;padding:2rem;line-height:1.6;}";
echo ".section{background:#111118;padding:1rem;margin:1rem 0;border-radius:0.5rem;border:1px solid #333;}";
echo ".success{color:#22d65f;}.error{color:#ef4444;}.warning{color:#f59e0b;}";
echo "</style></head><body>";

echo "<h1>🔍 Bendahara Panel Debug Tool</h1>";

// Test 1: Basic connectivity
echo "<div class='section'>";
echo "<h2>1. Server Connectivity Test</h2>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>Root URL Response: <span class='" . ($httpCode == 302 ? 'success' : 'error') . "'>$httpCode</span></p>";
echo "</div>";

// Test 2: Bendahara routes
echo "<div class='section'>";
echo "<h2>2. Bendahara Routes Test</h2>";

$routes = [
    '/bendahara' => 'Bendahara Root',
    '/bendahara/login' => 'Bendahara Login',
    '/bendahara/laporan-jaspel' => 'Laporan Jaspel'
];

foreach ($routes as $route => $name) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000$route");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    
    $status = ($httpCode == 200 || $httpCode == 302) ? 'success' : 'error';
    echo "<p>$name: <span class='$status'>$httpCode</span>";
    if ($redirectUrl) {
        echo " → $redirectUrl";
    }
    echo "</p>";
}
echo "</div>";

// Test 3: Redirect chain tracking
echo "<div class='section'>";
echo "<h2>3. Redirect Chain Analysis</h2>";

function traceRedirects($url, $maxRedirects = 10) {
    $redirects = [];
    $currentUrl = $url;
    
    for ($i = 0; $i < $maxRedirects; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $currentUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        
        $redirects[] = [
            'step' => $i + 1,
            'url' => $currentUrl,
            'code' => $httpCode,
            'redirect' => $redirectUrl
        ];
        
        if ($httpCode != 302 && $httpCode != 301) {
            break;
        }
        
        if (!$redirectUrl) {
            break;
        }
        
        // Check for loops
        foreach ($redirects as $prev) {
            if ($prev['url'] === $redirectUrl) {
                $redirects[] = [
                    'step' => $i + 2,
                    'url' => $redirectUrl,
                    'code' => 'LOOP DETECTED',
                    'redirect' => 'INFINITE LOOP!'
                ];
                return $redirects;
            }
        }
        
        $currentUrl = $redirectUrl;
    }
    
    return $redirects;
}

$redirectChain = traceRedirects('http://127.0.0.1:8000/bendahara');
echo "<ol>";
foreach ($redirectChain as $step) {
    $class = ($step['code'] === 'LOOP DETECTED') ? 'error' : 
             (($step['code'] == 302 || $step['code'] == 301) ? 'warning' : 'success');
    echo "<li>";
    echo "<strong>Step {$step['step']}:</strong> ";
    echo "<span class='$class'>{$step['url']} ({$step['code']})</span>";
    if ($step['redirect'] && $step['redirect'] !== 'INFINITE LOOP!') {
        echo " → {$step['redirect']}";
    } elseif ($step['redirect'] === 'INFINITE LOOP!') {
        echo " <span class='error'>→ {$step['redirect']}</span>";
    }
    echo "</li>";
}
echo "</ol>";
echo "</div>";

// Test 4: Session configuration
echo "<div class='section'>";
echo "<h2>4. Session Configuration</h2>";
echo "<p>SESSION_DRIVER: " . ($_ENV['SESSION_DRIVER'] ?? 'not set') . "</p>";
echo "<p>SESSION_LIFETIME: " . ($_ENV['SESSION_LIFETIME'] ?? 'not set') . "</p>";
echo "<p>SESSION_SAME_SITE: " . ($_ENV['SESSION_SAME_SITE'] ?? 'not set') . "</p>";
echo "<p>SESSION_SECURE_COOKIE: " . ($_ENV['SESSION_SECURE_COOKIE'] ?? 'not set') . "</p>";
echo "</div>";

// Test 5: Recommended actions
echo "<div class='section'>";
echo "<h2>5. Recommended Actions</h2>";
echo "<ul>";
echo "<li><strong>For Safari:</strong> Clear cookies for 127.0.0.1</li>";
echo "<li><strong>Test in Chrome:</strong> See if issue is Safari-specific</li>";
echo "<li><strong>Direct Login:</strong> Try <a href='/bendahara/login' style='color:#60a5fa;'>/bendahara/login</a></li>";
echo "<li><strong>Check Network Tab:</strong> Monitor redirects in Safari DevTools</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>