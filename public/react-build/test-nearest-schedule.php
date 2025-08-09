<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

echo "<!DOCTYPE html>
<html>
<head>
    <title>⏰ Test Nearest Schedule Logic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .schedule-card { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #667eea; }
        .current { border-left-color: #28a745; background: #d4edda; }
        .upcoming { border-left-color: #ffc107; background: #fff3cd; }
        .past { border-left-color: #6c757d; background: #e2e3e5; }
        .time-badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 14px; font-weight: bold; margin-right: 10px; }
        .time-current { background: #28a745; color: white; }
        .time-upcoming { background: #ffc107; color: black; }
        .time-past { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .button { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #5a67d8; }
        .timeline { position: relative; padding: 20px 0; }
        .timeline-item { position: relative; padding-left: 40px; margin-bottom: 20px; }
        .timeline-marker { position: absolute; left: 10px; top: 5px; width: 10px; height: 10px; border-radius: 50%; }
        .timeline-line { position: absolute; left: 14px; top: 15px; width: 2px; height: calc(100% + 10px); background: #ddd; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>⏰ Test Nearest Schedule Logic</h1>
            <p>Verifikasi logic menampilkan jadwal terdekat</p>
        </div>";

// Current time info
$now = Carbon::now('Asia/Jakarta');
$currentTime = $now->format('H:i:s');
$currentHour = $now->hour;
$currentMinute = $now->minute;
$currentSeconds = $currentHour * 3600 + $currentMinute * 60;

echo "<div class='section info'>
    <h2>🕐 Current Time Information</h2>
    <table>
        <tr><th>Server Time</th><td>" . $now->format('Y-m-d H:i:s') . " (Asia/Jakarta)</td></tr>
        <tr><th>Current Hour</th><td>{$currentHour}</td></tr>
        <tr><th>Current Minute</th><td>{$currentMinute}</td></tr>
        <tr><th>Seconds from midnight</th><td>{$currentSeconds} seconds</td></tr>
    </table>
</div>";

// Test with user TES 2
$testUser = User::where('name', 'like', '%TES%2%')
    ->orWhere('name', 'like', '%yaya%')
    ->first();

if ($testUser) {
    echo "<div class='section'>
        <h2>👨‍⚕️ User: {$testUser->name}</h2>
        <p><strong>User ID:</strong> {$testUser->id}</p>";
    
    // Get all schedules for today
    $todaySchedules = JadwalJaga::where('pegawai_id', $testUser->id)
        ->whereDate('tanggal_jaga', today())
        ->with('shiftTemplate')
        ->orderBy('tanggal_jaga')
        ->get();
    
    if ($todaySchedules->count() > 0) {
        echo "<h3>📅 All Schedules for Today (" . $todaySchedules->count() . " schedules)</h3>";
        echo "<div class='timeline'>";
        
        $nearestSchedule = null;
        $nearestDistance = PHP_INT_MAX;
        $currentSchedule = null;
        
        foreach ($todaySchedules as $index => $schedule) {
            $shiftTemplate = $schedule->shiftTemplate;
            if (!$shiftTemplate) continue;
            
            $startTime = $shiftTemplate->jam_masuk;
            $endTime = $shiftTemplate->jam_pulang;
            
            // Parse times
            $startParts = explode(':', $startTime);
            $endParts = explode(':', $endTime);
            $startSeconds = intval($startParts[0]) * 3600 + intval($startParts[1]) * 60;
            $endSeconds = intval($endParts[0]) * 3600 + intval($endParts[1]) * 60;
            
            // Calculate buffer (30 minutes)
            $bufferSeconds = 30 * 60;
            $startWithBuffer = $startSeconds - $bufferSeconds;
            $endWithBuffer = $endSeconds + $bufferSeconds;
            
            // Determine status
            $isCurrent = false;
            $isUpcoming = false;
            $isPast = false;
            $status = '';
            $cssClass = 'past';
            $badgeClass = 'time-past';
            
            if ($currentSeconds >= $startWithBuffer && $currentSeconds <= $endWithBuffer) {
                $isCurrent = true;
                $status = 'CURRENT (In shift with buffer)';
                $cssClass = 'current';
                $badgeClass = 'time-current';
                $currentSchedule = $schedule;
            } elseif ($startSeconds > $currentSeconds) {
                $isUpcoming = true;
                $status = 'UPCOMING';
                $cssClass = 'upcoming';
                $badgeClass = 'time-upcoming';
                
                // Calculate distance for nearest upcoming
                $distance = $startSeconds - $currentSeconds;
                if ($distance < $nearestDistance && !$currentSchedule) {
                    $nearestDistance = $distance;
                    $nearestSchedule = $schedule;
                }
            } else {
                $isPast = true;
                $status = 'PAST';
                
                // If no current or upcoming, track the most recent past
                if (!$currentSchedule && !$nearestSchedule) {
                    $distance = $currentSeconds - $endSeconds;
                    if ($distance < $nearestDistance) {
                        $nearestDistance = $distance;
                        $nearestSchedule = $schedule;
                    }
                }
            }
            
            // Calculate time differences
            $minutesToStart = round(($startSeconds - $currentSeconds) / 60);
            $minutesToEnd = round(($endSeconds - $currentSeconds) / 60);
            
            echo "<div class='timeline-item'>
                <div class='timeline-marker' style='background: " . 
                    ($isCurrent ? '#28a745' : ($isUpcoming ? '#ffc107' : '#6c757d')) . "'></div>
                " . ($index < $todaySchedules->count() - 1 ? "<div class='timeline-line'></div>" : "") . "
                <div class='schedule-card {$cssClass}'>
                    <span class='time-badge {$badgeClass}'>{$status}</span>
                    <h4>{$shiftTemplate->nama_shift}</h4>
                    <table style='margin-top: 10px;'>
                        <tr><th>Time</th><td>{$startTime} - {$endTime}</td></tr>
                        <tr><th>Unit</th><td>{$schedule->unit_kerja}</td></tr>
                        <tr><th>Start in</th><td>" . 
                            ($minutesToStart > 0 ? "{$minutesToStart} minutes" : 
                            ($minutesToStart < 0 ? abs($minutesToStart) . " minutes ago" : "Now")) . "</td></tr>
                        <tr><th>End in</th><td>" . 
                            ($minutesToEnd > 0 ? "{$minutesToEnd} minutes" : 
                            ($minutesToEnd < 0 ? abs($minutesToEnd) . " minutes ago" : "Now")) . "</td></tr>
                        <tr><th>Buffer Window</th><td>" . date('H:i', $startWithBuffer) . " - " . date('H:i', $endWithBuffer) . "</td></tr>
                    </table>
                </div>
            </div>";
        }
        
        echo "</div>";
        
        // Display which schedule should be shown
        $displaySchedule = $currentSchedule ?: $nearestSchedule;
        
        if ($displaySchedule) {
            $shiftTemplate = $displaySchedule->shiftTemplate;
            echo "<div class='section success'>
                <h3>✅ Schedule That Should Be Displayed in App</h3>
                <table>
                    <tr><th>Shift Name</th><td>{$shiftTemplate->nama_shift}</td></tr>
                    <tr><th>Time</th><td>{$shiftTemplate->jam_masuk} - {$shiftTemplate->jam_pulang}</td></tr>
                    <tr><th>Unit</th><td>{$displaySchedule->unit_kerja}</td></tr>
                    <tr><th>Reason</th><td>" . 
                        ($currentSchedule ? "Currently in this shift (with 30-minute buffer)" : 
                        "Nearest upcoming/recent schedule") . "</td></tr>
                </table>
            </div>";
        }
        
        // Logic explanation
        echo "<div class='section info'>
            <h3>🧮 Logic Explanation</h3>
            <ol>
                <li><strong>Current Shift Priority:</strong> If current time is within shift hours (±30 min buffer), show that shift</li>
                <li><strong>Nearest Upcoming:</strong> If not in any shift, show the nearest upcoming shift</li>
                <li><strong>Most Recent Past:</strong> If all shifts have passed, show the most recent one</li>
            </ol>
            
            <h4>Current Logic Decision:</h4>
            <ul>
                <li>Current time: {$currentTime}</li>
                <li>Current shift found: " . ($currentSchedule ? "Yes" : "No") . "</li>
                <li>Nearest schedule: " . ($nearestSchedule ? $nearestSchedule->shiftTemplate->nama_shift : "None") . "</li>
                <li>Final display: " . ($displaySchedule ? $displaySchedule->shiftTemplate->nama_shift : "None") . "</li>
            </ul>
        </div>";
        
    } else {
        echo "<div class='warning'>⚠️ No schedules found for today</div>";
    }
    
} else {
    echo "<div class='error'>❌ Test user not found</div>";
}

// Test scenarios
echo "<div class='section'>
    <h2>🧪 Test Scenarios</h2>
    <h3>Scenario for Multiple Schedules:</h3>
    <ul>
        <li><strong>07:30 - 08:00:</strong> Morning shift (General Outpatient)</li>
        <li><strong>17:45 - 18:00:</strong> Evening shift (Dokter Jaga)</li>
    </ul>
    
    <h4>Expected Behavior by Time:</h4>
    <table>
        <tr><th>Current Time</th><th>Should Display</th><th>Reason</th></tr>
        <tr><td>06:00</td><td>07:30 - 08:00</td><td>Nearest upcoming</td></tr>
        <tr><td>07:00</td><td>07:30 - 08:00</td><td>Within buffer (07:00-08:30)</td></tr>
        <tr><td>07:45</td><td>07:30 - 08:00</td><td>Currently in shift</td></tr>
        <tr><td>08:30</td><td>07:30 - 08:00</td><td>Still in buffer</td></tr>
        <tr><td>09:00</td><td>17:45 - 18:00</td><td>Next upcoming shift</td></tr>
        <tr><td>17:15</td><td>17:45 - 18:00</td><td>Within buffer (17:15-18:30)</td></tr>
        <tr><td>17:50</td><td>17:45 - 18:00</td><td>Currently in shift</td></tr>
        <tr><td>19:00</td><td>17:45 - 18:00</td><td>Most recent past shift</td></tr>
    </table>
</div>";

echo "</div></body></html>";
?>