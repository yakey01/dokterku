<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\JadwalJaga;
use Carbon\Carbon;

echo "<h2>Testing Jadwal Jaga API Endpoint</h2>";

// Find Yaya (user ID 13)
$yaya = User::find(13);

if (!$yaya) {
    echo "<p>❌ User Yaya not found</p>";
    exit;
}

echo "<p>Found user: " . $yaya->name . " (ID: " . $yaya->id . ")</p>";

// Login as Yaya
Auth::login($yaya);

if (Auth::check()) {
    echo "<p>✅ Successfully logged in as Yaya</p>";
    
    // Test API endpoint directly
    echo "<h3>Testing API Endpoint: /api/v2/dashboards/dokter/jadwal-jaga</h3>";
    
    // Create request
    $request = \Illuminate\Http\Request::create('/api/v2/dashboards/dokter/jadwal-jaga', 'GET');
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('X-CSRF-TOKEN', csrf_token());
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    
    // Handle request
    $response = app()->handle($request);
    $data = json_decode($response->getContent(), true);
    
    echo "<p>Status Code: " . $response->getStatusCode() . "</p>";
    echo "<p>Success: " . ($data['success'] ? 'true' : 'false') . "</p>";
    
    if (isset($data['data'])) {
        $calendarEvents = $data['data']['calendar_events'] ?? [];
        $weeklySchedule = $data['data']['weekly_schedule'] ?? [];
        $today = $data['data']['today'] ?? [];
        
        echo "<h4>Calendar Events (" . count($calendarEvents) . "):</h4>";
        foreach ($calendarEvents as $event) {
            echo "<p>- " . $event['title'] . " (" . $event['start'] . ") - " . ($event['shift_info']['nama_shift'] ?? 'Unknown') . "</p>";
        }
        
        echo "<h4>Weekly Schedule (" . count($weeklySchedule) . "):</h4>";
        foreach ($weeklySchedule as $schedule) {
            echo "<p>- " . ($schedule['shift_template']['nama_shift'] ?? 'Unknown') . " (" . $schedule['tanggal_jaga'] . ") - " . ($schedule['shift_template']['jam_masuk'] ?? 'Unknown') . "</p>";
        }
        
        echo "<h4>Today's Schedule (" . count($today) . "):</h4>";
        foreach ($today as $schedule) {
            echo "<p>- " . ($schedule['shift_template']['nama_shift'] ?? 'Unknown') . " (" . $schedule['tanggal_jaga'] . ") - " . ($schedule['shift_template']['jam_masuk'] ?? 'Unknown') . "</p>";
        }
        
        // Check for tes 4 specifically
        echo "<h4>Looking for 'tes 4' in response:</h4>";
        $tes4Found = false;
        
        foreach ($calendarEvents as $event) {
            if (stripos($event['title'], 'tes 4') !== false || 
                stripos($event['shift_info']['nama_shift'] ?? '', 'tes 4') !== false) {
                echo "<p>✅ Found 'tes 4' in calendar events: " . $event['title'] . "</p>";
                $tes4Found = true;
            }
        }
        
        foreach ($weeklySchedule as $schedule) {
            if (stripos($schedule['shift_template']['nama_shift'] ?? '', 'tes 4') !== false) {
                echo "<p>✅ Found 'tes 4' in weekly schedule: " . $schedule['shift_template']['nama_shift'] . "</p>";
                $tes4Found = true;
            }
        }
        
        foreach ($today as $schedule) {
            if (stripos($schedule['shift_template']['nama_shift'] ?? '', 'tes 4') !== false) {
                echo "<p>✅ Found 'tes 4' in today's schedule: " . $schedule['shift_template']['nama_shift'] . "</p>";
                $tes4Found = true;
            }
        }
        
        if (!$tes4Found) {
            echo "<p>❌ 'tes 4' not found in any response data</p>";
        }
        
        // Show full response for debugging
        echo "<h4>Full API Response:</h4>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        
    } else {
        echo "<p>❌ No data in response</p>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    }
    
} else {
    echo "<p>❌ Failed to login</p>";
}

// Logout
Auth::logout();
?>
