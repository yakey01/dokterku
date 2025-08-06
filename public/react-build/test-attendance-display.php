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

echo "<h1>Test Attendance Display</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .warning { color: orange; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; }
</style>";

try {
    // Test with a paramedis user
    $testUserId = 11; // Naning ID
    $user = User::find($testUserId);
    
    if (!$user) {
        echo "<p class='error'>User with ID $testUserId not found!</p>";
        exit;
    }
    
    echo "<h2>Testing User: {$user->name} (ID: {$user->id})</h2>";
    echo "<p><strong>Current Time:</strong> " . Carbon::now()->format('Y-m-d H:i:s') . "</p>";
    
    // Get attendance status using the same method as the API
    $attendanceStatus = Attendance::getTodayStatus($user->id);
    $attendance = $attendanceStatus['attendance'];
    
    echo "<h3>1. Attendance Status (getTodayStatus)</h3>";
    echo "<pre>" . json_encode($attendanceStatus, JSON_PRETTY_PRINT) . "</pre>";
    
    if ($attendance) {
        echo "<h3>2. Attendance Details</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Raw Value</th><th>Formatted Value</th></tr>";
        echo "<tr><td>ID</td><td>{$attendance->id}</td><td>-</td></tr>";
        echo "<tr><td>Date</td><td>{$attendance->date}</td><td>{$attendance->date->format('Y-m-d')}</td></tr>";
        echo "<tr><td>time_in</td><td>{$attendance->time_in}</td><td>" . ($attendance->time_in ? $attendance->time_in->format('H:i:s') : '-') . "</td></tr>";
        echo "<tr><td>time_out</td><td>{$attendance->time_out}</td><td>" . ($attendance->time_out ? $attendance->time_out->format('H:i:s') : '-') . "</td></tr>";
        echo "<tr><td>Status</td><td>{$attendance->status}</td><td>-</td></tr>";
        echo "<tr><td>Work Duration</td><td>{$attendance->work_duration} minutes</td><td>{$attendance->formatted_work_duration}</td></tr>";
        echo "</table>";
        
        echo "<h3>3. API Response Format (What Frontend Receives)</h3>";
        $apiResponse = [
            'id' => $attendance->id,
            'date' => $attendance->date->format('Y-m-d'),
            'check_in_time' => $attendance->time_in?->format('Y-m-d H:i:s'),
            'check_out_time' => $attendance->time_out?->format('Y-m-d H:i:s'),
            'work_duration' => $attendance->formatted_work_duration,
            'work_duration_minutes' => $attendance->time_in && $attendance->time_out 
                ? $attendance->work_duration 
                : ($attendance->time_in ? Carbon::now()->diffInMinutes($attendance->time_in) : null),
            'location_in' => $attendance->location_name_in,
            'location_out' => $attendance->location_name_out,
            'status' => $attendance->status,
            'is_late' => $attendance->status === 'late',
        ];
        echo "<pre>" . json_encode($apiResponse, JSON_PRETTY_PRINT) . "</pre>";
        
        echo "<h3>4. Time Format Testing</h3>";
        if ($attendance->time_in) {
            echo "<table>";
            echo "<tr><th>Format Type</th><th>Output</th></tr>";
            echo "<tr><td>H:i:s</td><td>{$attendance->time_in->format('H:i:s')}</td></tr>";
            echo "<tr><td>H:i</td><td>{$attendance->time_in->format('H:i')}</td></tr>";
            echo "<tr><td>Y-m-d H:i:s</td><td>{$attendance->time_in->format('Y-m-d H:i:s')}</td></tr>";
            echo "<tr><td>ISO 8601</td><td>{$attendance->time_in->toISOString()}</td></tr>";
            echo "<tr><td>toTimeString()</td><td>{$attendance->time_in->toTimeString()}</td></tr>";
            echo "</table>";
        }
    } else {
        echo "<p class='info'>No attendance record for today.</p>";
    }
    
    // Test the ParamedisDashboardController directly
    echo "<h3>5. Direct Controller Test</h3>";
    $controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
    
    // Manually set the authenticated user
    \Auth::login($user);
    
    $request = new \Illuminate\Http\Request();
    $response = $controller->getAttendanceStatus($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "<h4>Controller Response:</h4>";
    echo "<pre>" . json_encode($responseData, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (\Exception $e) {
    echo "<h3 class='error'>ERROR:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='/paramedis/mobile-app'>Back to Paramedis App</a> | ";
echo "<a href='javascript:location.reload()'>Refresh</a></p>";

$kernel->terminate($request, $response);