<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

// Find Yaya
$yaya = User::where('email', 'yaya@dokterku.com')
    ->orWhere('name', 'like', '%yaya%')
    ->first();

if ($yaya) {
    // Force login
    \Auth::login($yaya);
    session()->put('user_id', $yaya->id);
    session()->put('user_role', 'paramedis');
    session()->save();
    
    echo "<h1>Logged in as: {$yaya->name}</h1>";
    echo "<p>User ID: {$yaya->id}</p>";
    echo "<p>Email: {$yaya->email}</p>";
    echo "<p>Session ID: " . session()->getId() . "</p>";
    echo "<p>Auth Check: " . (Auth::check() ? 'Yes' : 'No') . "</p>";
    
    // Check if Yaya has attendance today
    $attendance = Attendance::where('user_id', $yaya->id)
        ->whereDate('date', Carbon::today())
        ->first();
        
    if (!$attendance) {
        // Create attendance for Yaya
        $attendance = Attendance::create([
            'user_id' => $yaya->id,
            'date' => Carbon::today(),
            'time_in' => Carbon::now(),
            'status' => 'present',
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'location_name_in' => 'Klinik Dokterku',
            'latlon_in' => '-7.898878,111.961884'
        ]);
        echo "<p><strong>Created attendance record for today</strong></p>";
    }
    
    echo "<p>Attendance Time In: " . ($attendance->time_in ? $attendance->time_in->format('H:i:s') : 'None') . "</p>";
    
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
    echo "<p>User Yaya not found!</p>";
    
    // List all paramedis users
    echo "<h2>Available Paramedis Users:</h2>";
    $paramedisUsers = User::whereHas('roles', function($q) {
        $q->where('name', 'paramedis');
    })->get();
    
    echo "<ul>";
    foreach ($paramedisUsers as $user) {
        echo "<li>{$user->name} - {$user->email} (ID: {$user->id})</li>";
    }
    echo "</ul>";
}

$kernel->terminate($request, $response);