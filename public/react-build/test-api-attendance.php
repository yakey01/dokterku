<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;

// Test with Bita (ID: 6)
$user = User::find(6);

if ($user) {
    echo "<h1>Testing API Response for: {$user->name}</h1>";
    
    // Login as the user
    \Auth::login($user);
    
    // Test the controller directly
    $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
    $request = new \Illuminate\Http\Request();
    
    echo "<h2>1. getAttendanceStatus Response:</h2>";
    $response = $controller->getAttendanceStatus($request);
    $data = json_decode($response->getContent(), true);
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    
    if (isset($data['data']['attendance'])) {
        echo "<h3>Attendance Data:</h3>";
        echo "<ul>";
        echo "<li>check_in_time: " . ($data['data']['attendance']['check_in_time'] ?? 'NULL') . "</li>";
        echo "<li>check_out_time: " . ($data['data']['attendance']['check_out_time'] ?? 'NULL') . "</li>";
        echo "<li>time_in (raw): " . ($data['data']['attendance']['time_in'] ?? 'NULL') . "</li>";
        echo "<li>time_out (raw): " . ($data['data']['attendance']['time_out'] ?? 'NULL') . "</li>";
        echo "</ul>";
    }
    
    echo "<h2>2. getPresensi Response:</h2>";
    $response2 = $controller->getPresensi($request);
    $data2 = json_decode($response2->getContent(), true);
    echo "<pre>" . json_encode($data2['data']['today'] ?? [], JSON_PRETTY_PRINT) . "</pre>";
}

$kernel->terminate($request, $response);