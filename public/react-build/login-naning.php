<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;

// Find Naning
$naning = User::where('email', 'naning@dokterku.com')->first();

if ($naning) {
    // Force login
    \Auth::login($naning);
    session()->put('user_id', $naning->id);
    session()->put('user_role', 'paramedis');
    session()->save();
    
    echo "<h1>Logged in as: {$naning->name}</h1>";
    echo "<p>User ID: {$naning->id}</p>";
    echo "<p>Email: {$naning->email}</p>";
    echo "<p>Session ID: " . session()->getId() . "</p>";
    echo "<p>Auth Check: " . (Auth::check() ? 'Yes' : 'No') . "</p>";
    
    // Set a cookie to maintain session
    setcookie('laravel_session', session()->getId(), time() + 3600, '/');
    
    echo "<hr>";
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li>Click the link below to open the Paramedis app</li>";
    echo "<li>The check-in time should now be visible</li>";
    echo "</ol>";
    echo "<p><a href='/paramedis/mobile-app' target='_blank' style='background: blue; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Open Paramedis App</a></p>";
    
    // Also provide direct test
    echo "<hr>";
    echo "<h2>Direct API Test:</h2>";
    echo "<p><a href='/paramedis/web-api/attendance-status' target='_blank'>Test API Endpoint</a></p>";
} else {
    echo "<p>User Naning not found!</p>";
}

$kernel->terminate($request, $response);