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

echo "<h1>Debug Yaya's Attendance</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; }
</style>";

// Find Yaya
$yaya = User::where('email', 'yaya@dokterku.com')
    ->orWhere('name', 'like', '%yaya%')
    ->first();

if (!$yaya) {
    echo "<p class='error'>User Yaya not found!</p>";
    
    // List all users to find the correct one
    echo "<h2>All Users:</h2>";
    $users = User::all();
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($users as $user) {
        $roles = $user->roles->pluck('name')->join(', ');
        echo "<tr><td>{$user->id}</td><td>{$user->name}</td><td>{$user->email}</td><td>{$roles}</td></tr>";
    }
    echo "</table>";
    exit;
}

echo "<h2>User Info:</h2>";
echo "<ul>";
echo "<li>Name: {$yaya->name}</li>";
echo "<li>ID: {$yaya->id}</li>";
echo "<li>Email: {$yaya->email}</li>";
echo "<li>Roles: " . $yaya->roles->pluck('name')->join(', ') . "</li>";
echo "</ul>";

// Check attendance
$today = Carbon::today();
$attendance = Attendance::where('user_id', $yaya->id)
    ->whereDate('date', $today)
    ->first();

echo "<h2>Attendance Data:</h2>";
if ($attendance) {
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th><th>Type</th></tr>";
    echo "<tr><td>ID</td><td>{$attendance->id}</td><td>-</td></tr>";
    echo "<tr><td>Date</td><td>{$attendance->date}</td><td>" . gettype($attendance->date) . "</td></tr>";
    echo "<tr><td>time_in (raw)</td><td>{$attendance->time_in}</td><td>" . gettype($attendance->time_in) . "</td></tr>";
    echo "<tr><td>time_out (raw)</td><td>{$attendance->time_out}</td><td>" . gettype($attendance->time_out) . "</td></tr>";
    echo "<tr><td>Status</td><td>{$attendance->status}</td><td>-</td></tr>";
    
    // Format time_in in different ways
    if ($attendance->time_in) {
        echo "<tr><td colspan='3'><strong>Time In Formats:</strong></td></tr>";
        echo "<tr><td>Format Y-m-d H:i:s</td><td>" . $attendance->time_in->format('Y-m-d H:i:s') . "</td><td>-</td></tr>";
        echo "<tr><td>Format H:i:s</td><td>" . $attendance->time_in->format('H:i:s') . "</td><td>-</td></tr>";
        echo "<tr><td>Format H:i</td><td>" . $attendance->time_in->format('H:i') . "</td><td>-</td></tr>";
        echo "<tr><td>toDateTimeString()</td><td>" . $attendance->time_in->toDateTimeString() . "</td><td>-</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p class='info'>No attendance for today. Creating one...</p>";
    
    $attendance = Attendance::create([
        'user_id' => $yaya->id,
        'date' => $today,
        'time_in' => Carbon::now(),
        'status' => 'present',
        'latitude' => -7.898878,
        'longitude' => 111.961884,
        'location_name_in' => 'Klinik Dokterku',
        'latlon_in' => '-7.898878,111.961884'
    ]);
    
    echo "<p class='success'>Created attendance with time_in: " . $attendance->time_in->format('Y-m-d H:i:s') . "</p>";
}

// Test API response
echo "<h2>API Response Test:</h2>";
\Auth::login($yaya);
$controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
$request = new \Illuminate\Http\Request();
$response = $controller->getAttendanceStatus($request);
$data = json_decode($response->getContent(), true);

echo "<h3>Full Response:</h3>";
echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

if (isset($data['data']['attendance'])) {
    echo "<h3>Attendance Fields:</h3>";
    echo "<ul>";
    echo "<li><strong>check_in_time:</strong> " . ($data['data']['attendance']['check_in_time'] ?? 'NULL') . "</li>";
    echo "<li><strong>check_out_time:</strong> " . ($data['data']['attendance']['check_out_time'] ?? 'NULL') . "</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h2>JavaScript Test:</h2>";
?>
<script>
    // Test the formatTime function
    const formatTime = (timeString) => {
        if (!timeString) return '--:--';
        
        console.log('Testing formatTime with:', timeString);
        
        // Handle different time formats
        if (timeString.includes('T')) {
            // ISO format: 2024-01-01T14:30:00.000000Z
            return new Date(timeString).toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: false 
            });
        } else if (timeString.includes(' ')) {
            // Format: 2025-08-03 13:31:39
            const parts = timeString.split(' ');
            if (parts[1]) {
                return parts[1].substring(0, 5);
            }
        } else if (timeString.length > 5) {
            // Format: 14:30:00
            return timeString.substring(0, 5);
        } else {
            // Format: 14:30
            return timeString;
        }
        
        // Fallback
        return '--:--';
    };

    // Test with the API response
    const apiResponse = <?php echo json_encode($data); ?>;
    if (apiResponse.data && apiResponse.data.attendance) {
        const checkInTime = apiResponse.data.attendance.check_in_time;
        console.log('Check-in time from API:', checkInTime);
        console.log('Formatted time:', formatTime(checkInTime));
        
        document.write('<p>JavaScript formatTime result: <strong>' + formatTime(checkInTime) + '</strong></p>');
    }
</script>

<?php
echo "<hr>";
echo "<p><a href='/paramedis/mobile-app' style='background: blue; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Open Paramedis App</a></p>";

$kernel->terminate($request, $response);
?>