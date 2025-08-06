<?php

// Test script for paramedis API authentication

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Bootstrap the application
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Find a paramedis user
$user = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'paramedis');
})->first();

if (!$user) {
    echo "No paramedis user found!\n";
    exit(1);
}

echo "Testing with user: {$user->name} (ID: {$user->id})\n";

// Create a Sanctum token
$token = $user->createToken('test-paramedis-api')->plainTextToken;
echo "Token created: $token\n\n";

// Test the API endpoint
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
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";

// Cleanup token
$user->tokens()->where('name', 'test-paramedis-api')->delete();