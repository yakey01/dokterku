<?php

// Debug script to understand the authentication flow

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

// Test user Naning
$user = \App\Models\User::find(3);
echo "=== USER DETAILS ===\n";
echo "ID: {$user->id}\n";
echo "Name: {$user->name}\n";
echo "Email: {$user->email}\n";
echo "Roles: " . implode(', ', $user->roles->pluck('name')->toArray()) . "\n";

// Check if Pegawai exists
$pegawai = \App\Models\Pegawai::where('user_id', $user->id)->first();
echo "\n=== PEGAWAI DETAILS ===\n";
if ($pegawai) {
    echo "ID: {$pegawai->id}\n";
    echo "NIK: {$pegawai->nik}\n";
    echo "Jenis Pegawai: {$pegawai->jenis_pegawai}\n";
    echo "Jabatan: {$pegawai->jabatan}\n";
    echo "Unit Kerja: " . ($pegawai->unit_kerja ?? 'NULL') . "\n";
} else {
    echo "No Pegawai record found!\n";
}

// Test authentication flow
echo "\n=== AUTHENTICATION TEST ===\n";

// Create a web session (simulating login)
Auth::login($user);
echo "Logged in via web auth: " . (Auth::check() ? 'YES' : 'NO') . "\n";

// Create Sanctum token
$token = $user->createToken('test-mobile-app')->plainTextToken;
echo "Sanctum token created: " . substr($token, 0, 20) . "...\n";

// Test different authentication approaches
echo "\n=== API AUTHENTICATION TESTS ===\n";

// 1. Test with Bearer token
echo "\n1. Testing with Bearer token:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v2/dashboards/paramedis');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Status: $httpCode\n";
echo "Response: " . substr($response, 0, 100) . "...\n";

// 2. Test with session cookie
echo "\n2. Testing with session cookie:\n";
session_start();
$sessionId = session_id();
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v2/dashboards/paramedis');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Cookie: laravel_session=' . $sessionId,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP Status: $httpCode\n";
echo "Response: " . substr($response, 0, 100) . "...\n";

// Cleanup
$user->tokens()->where('name', 'test-mobile-app')->delete();
Auth::logout();

echo "\n=== FRONTEND AUTHENTICATION ===\n";
echo "The React app should:\n";
echo "1. Read the token from meta tag: <meta name=\"api-token\" content=\"{token}\">\n";
echo "2. Include it in API requests as: Authorization: Bearer {token}\n";
echo "3. Also include credentials: 'include' for cookie-based auth\n";