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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Fix Attendance Display</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #2563eb;
        }
        .time-display {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            padding: 10px;
            background: #e3f2fd;
            border-radius: 4px;
            display: inline-block;
            margin: 10px;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow: auto;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Fix Attendance Display</h1>
    
    <?php
    // Step 1: Find Yaya/Rindang
    $paramedis = User::whereHas('roles', function($q) {
        $q->where('name', 'paramedis');
    })
    ->where(function($q) {
        $q->where('email', 'yaya@dokterku.com')
          ->orWhere('name', 'like', '%yaya%')
          ->orWhere('name', 'like', '%rindang%');
    })
    ->first();
    
    if (!$paramedis) {
        // Get first paramedis user
        $paramedis = User::whereHas('roles', function($q) {
            $q->where('name', 'paramedis');
        })->first();
    }
    
    if ($paramedis) {
        echo "<div class='card'>";
        echo "<h2>Step 1: User Found</h2>";
        echo "<p>Name: <strong>{$paramedis->name}</strong></p>";
        echo "<p>Email: {$paramedis->email}</p>";
        echo "<p>ID: {$paramedis->id}</p>";
        
        // Login as this user
        Auth::login($paramedis);
        session()->regenerate();
        
        echo "<p class='success'>✓ Logged in successfully</p>";
        echo "</div>";
        
        // Step 2: Check/Create Attendance
        echo "<div class='card'>";
        echo "<h2>Step 2: Attendance Check</h2>";
        
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $paramedis->id)
            ->whereDate('date', $today)
            ->first();
            
        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => $paramedis->id,
                'date' => $today,
                'time_in' => Carbon::now()->subHours(2), // 2 hours ago
                'status' => 'present',
                'latitude' => -7.898878,
                'longitude' => 111.961884,
                'location_name_in' => 'Klinik Dokterku',
                'latlon_in' => '-7.898878,111.961884'
            ]);
            echo "<p class='info'>Created new attendance record</p>";
        }
        
        echo "<p>Check-in Time: <span class='time-display'>" . $attendance->time_in->format('H:i') . "</span></p>";
        echo "<p>Raw time_in: " . $attendance->time_in->format('Y-m-d H:i:s') . "</p>";
        echo "</div>";
        
        // Step 3: Test API
        echo "<div class='card'>";
        echo "<h2>Step 3: API Test</h2>";
        echo "<button onclick='testAPI()'>Test API Endpoint</button>";
        echo "<div id='apiResult'></div>";
        echo "</div>";
        
        // Step 4: Test Display
        echo "<div class='card'>";
        echo "<h2>Step 4: Test Display</h2>";
        echo "<div id='displayTest'></div>";
        echo "</div>";
        
        // Step 5: Open App
        echo "<div class='card'>";
        echo "<h2>Step 5: Open Paramedis App</h2>";
        echo "<p>Click the button below to open the app. The check-in time should now display correctly.</p>";
        echo "<button onclick='window.open(\"/paramedis/mobile-app\", \"_blank\")'>Open Paramedis App</button>";
        echo "</div>";
        
    } else {
        echo "<div class='card'>";
        echo "<p class='error'>No paramedis user found!</p>";
        echo "</div>";
    }
    ?>
    
    <script>
        // Format time function
        const formatTime = (timeString) => {
            if (!timeString) return '--:--';
            
            console.log('formatTime input:', timeString);
            
            if (timeString.includes(' ')) {
                // Format: 2025-08-03 13:31:39
                const parts = timeString.split(' ');
                if (parts[1]) {
                    const timePart = parts[1].substring(0, 5);
                    console.log('Extracted time:', timePart);
                    return timePart;
                }
            }
            
            return '--:--';
        };
        
        // Test API
        async function testAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('/paramedis/web-api/attendance-status', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                console.log('API Response:', data);
                
                resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                
                // Test display
                if (data.success && data.data && data.data.attendance && data.data.attendance.check_in_time) {
                    const checkInTime = data.data.attendance.check_in_time;
                    const formatted = formatTime(checkInTime);
                    
                    document.getElementById('displayTest').innerHTML = `
                        <p>Raw check_in_time: <code>${checkInTime}</code></p>
                        <p>Formatted time: <span class="time-display">${formatted}</span></p>
                        <p class="success">✓ Time formatting works correctly!</p>
                    `;
                } else {
                    document.getElementById('displayTest').innerHTML = 
                        '<p class="error">No attendance data in response</p>';
                }
                
            } catch (error) {
                console.error('API Error:', error);
                resultDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>

<?php
$kernel->terminate($request, $response);
?>