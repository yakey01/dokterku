<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;

// Login as Naning
$naning = User::where('email', 'naning@dokterku.com')->first();

if ($naning) {
    \Auth::login($naning);
    session()->save(); // Force session save
    
    echo "<h1>Testing as: {$naning->name}</h1>";
    echo "<p>Session ID: " . session()->getId() . "</p>";
    
    // Test the route directly
    $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
    $request = new \Illuminate\Http\Request();
    
    echo "<h2>Testing getAttendanceStatus:</h2>";
    try {
        $response = $controller->getAttendanceStatus($request);
        $data = json_decode($response->getContent(), true);
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        
        if (isset($data['data']['attendance'])) {
            echo "<h3>Check-in Time: " . ($data['data']['attendance']['check_in_time'] ?? 'NULL') . "</h3>";
        }
    } catch (\Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    
    // Create a test link
    echo "<hr>";
    echo "<p><a href='/paramedis/mobile-app' target='_blank'>Open Paramedis App</a></p>";
    echo "<p>Make sure you're logged in as Naning in the same browser!</p>";
}

$kernel->terminate($request, $response);