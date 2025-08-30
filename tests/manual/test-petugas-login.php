<?php
// Test login and get dashboard HTML

$ch = curl_init();

// Step 1: Get CSRF token from login page
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/petugas/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
$loginPage = curl_exec($ch);

// Extract CSRF token
preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage, $matches);
$csrfToken = $matches[1] ?? '';

if (!$csrfToken) {
    preg_match('/<meta[^>]*name="csrf-token"[^>]*content="([^"]*)"/', $loginPage, $matches);
    $csrfToken = $matches[1] ?? '';
}

echo "CSRF Token: " . substr($csrfToken, 0, 20) . "...\n";

// Step 2: Login
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/petugas/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'email' => 'petugas@dokterku.com',
    'password' => 'password123',
    '_token' => $csrfToken
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);

// Step 3: Get dashboard
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/petugas");
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HEADER, false);
$dashboard = curl_exec($ch);

curl_close($ch);

// Check for world-class elements
echo "\n=== CHECKING FOR WORLD-CLASS UI ELEMENTS ===\n";

if (strpos($dashboard, 'fi-sidebar') !== false) {
    echo "✓ Sidebar found\n";
} else {
    echo "✗ Sidebar NOT found\n";
}

if (strpos($dashboard, 'glassmorphism') !== false || strpos($dashboard, 'Force black glassmorphism') !== false) {
    echo "✓ Glassmorphism styles found\n";
} else {
    echo "✗ Glassmorphism styles NOT found\n";
}

if (strpos($dashboard, 'Inter') !== false) {
    echo "✓ Inter font found\n";
} else {
    echo "✗ Inter font NOT found\n";
}

if (strpos($dashboard, 'world-class-2025') !== false) {
    echo "✓ World-class CSS found\n";
} else {
    echo "✗ World-class CSS NOT found\n";
}

// Save dashboard HTML for inspection
file_put_contents('dashboard-output.html', $dashboard);
echo "\n✓ Dashboard HTML saved to dashboard-output.html\n";

// Extract relevant parts
if (preg_match('/<div[^>]*class="[^"]*fi-sidebar[^"]*"[^>]*>(.*?)<\/div>/s', $dashboard, $matches)) {
    echo "\n=== SIDEBAR HTML ===\n";
    echo substr(strip_tags($matches[0]), 0, 200) . "...\n";
}