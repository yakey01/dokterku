<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get session and check if user is authenticated
session_start();

echo "<h1>Authentication Debug</h1>";

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Auth Status:</h2>";
try {
    $user = \Illuminate\Support\Facades\Auth::user();
    if ($user) {
        echo "✅ User authenticated: " . $user->name . " (ID: " . $user->id . ")<br>";
        echo "Email: " . $user->email . "<br>";
        echo "Role: " . ($user->role ? $user->role->name : 'No role') . "<br>";
        
        // Create a test token
        $token = $user->createToken('debug-test-token')->plainTextToken;
        echo "<br><strong>Generated Token:</strong><br>";
        echo "<code>" . $token . "</code><br>";
        
        // Test the token
        echo "<br><h2>Token Validation Test:</h2>";
        $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if ($tokenRecord) {
            echo "✅ Token is valid<br>";
            echo "Token ID: " . $tokenRecord->id . "<br>";
            echo "Token User: " . $tokenRecord->tokenable->name . "<br>";
        } else {
            echo "❌ Token validation failed<br>";
        }
        
    } else {
        echo "❌ No user authenticated<br>";
        echo "You need to log in first.<br>";
        echo '<a href="/login">Go to Login</a>';
    }
} catch (Exception $e) {
    echo "❌ Error checking auth: " . $e->getMessage() . "<br>";
}

echo "<h2>Meta Tags Test:</h2>";
if (isset($user) && $user) {
    $metaToken = $user->createToken('meta-test')->plainTextToken;
    echo '<meta name="api-token" content="' . $metaToken . '">' . "<br>";
    echo '<meta name="csrf-token" content="' . csrf_token() . '">' . "<br>";
} else {
    echo "No user for meta tags<br>";
}

echo "<br><a href='/dokter/mobile-app'>Go to Mobile App</a>";
?>