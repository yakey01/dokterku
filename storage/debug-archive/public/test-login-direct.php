<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test direct login
try {
    $user = \App\Models\User::where('email', 'admin@dokterku.com')->first();
    
    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    echo json_encode([
        'user_found' => true,
        'email' => $user->email,
        'name' => $user->name,
        'role' => $user->role,
        'password_exists' => !empty($user->password),
        'hash_test' => \Illuminate\Support\Facades\Hash::check('password123', $user->password)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}