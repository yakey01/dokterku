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

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Paramedis Auth</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .box { border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Test Paramedis Authentication & Attendance</h1>
";

// Check current auth status
echo "<div class='box'>";
echo "<h2>Current Auth Status:</h2>";
if (Auth::check()) {
    $currentUser = Auth::user();
    echo "<p class='success'>Logged in as: {$currentUser->name} (ID: {$currentUser->id})</p>";
    echo "<p>Email: {$currentUser->email}</p>";
    echo "<p>Roles: " . $currentUser->roles->pluck('name')->join(', ') . "</p>";
} else {
    echo "<p class='error'>Not logged in!</p>";
}
echo "</div>";

// List all paramedis users
echo "<div class='box'>";
echo "<h2>Available Paramedis Users:</h2>";
$paramedisUsers = User::whereHas('roles', function($q) {
    $q->where('name', 'paramedis');
})->get();

foreach ($paramedisUsers as $user) {
    echo "<form method='post' style='display: inline;'>";
    echo "<input type='hidden' name='user_id' value='{$user->id}'>";
    echo "<button type='submit' name='action' value='login'>Login as {$user->name}</button>";
    echo "</form>";
    echo " ({$user->email})<br>";
}
echo "</div>";

// Handle login action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'login') {
    $userId = $_POST['user_id'];
    $user = User::find($userId);
    
    if ($user) {
        Auth::login($user);
        session()->regenerate();
        echo "<script>window.location.reload();</script>";
    }
}

// If logged in as paramedis, show attendance info
if (Auth::check() && Auth::user()->hasRole('paramedis')) {
    $user = Auth::user();
    $today = Carbon::today();
    $attendance = Attendance::where('user_id', $user->id)
        ->whereDate('date', $today)
        ->first();
    
    echo "<div class='box'>";
    echo "<h2>Attendance Status for Today:</h2>";
    
    if ($attendance) {
        echo "<p class='success'>Attendance found!</p>";
        echo "<ul>";
        echo "<li>Time In: " . ($attendance->time_in ? $attendance->time_in->format('Y-m-d H:i:s') : 'NULL') . "</li>";
        echo "<li>Time Out: " . ($attendance->time_out ? $attendance->time_out->format('Y-m-d H:i:s') : 'NULL') . "</li>";
        echo "<li>Status: {$attendance->status}</li>";
        echo "</ul>";
    } else {
        echo "<p class='info'>No attendance yet today.</p>";
        echo "<form method='post'>";
        echo "<button type='submit' name='action' value='create_attendance'>Create Test Attendance</button>";
        echo "</form>";
    }
    echo "</div>";
    
    // Handle create attendance
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create_attendance') {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'time_in' => Carbon::now(),
            'status' => 'present',
            'latitude' => -7.898878,
            'longitude' => 111.961884,
            'location_name_in' => 'Klinik Dokterku',
            'latlon_in' => '-7.898878,111.961884'
        ]);
        echo "<script>window.location.reload();</script>";
    }
    
    // Test API endpoint
    echo "<div class='box'>";
    echo "<h2>API Test:</h2>";
    echo "<button onclick='testAPI()'>Test API Endpoint</button>";
    echo "<div id='apiResult'></div>";
    echo "</div>";
    
    // Links
    echo "<div class='box'>";
    echo "<h2>Quick Links:</h2>";
    echo "<p><a href='/paramedis/mobile-app' target='_blank' class='button'>Open Paramedis App</a></p>";
    echo "<p><a href='/logout' onclick='return confirm(\"Logout?\")'>Logout</a></p>";
    echo "</div>";
}

?>

<script>
function testAPI() {
    fetch('/paramedis/web-api/attendance-status', {
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '<?php echo csrf_token(); ?>'
        }
    })
    .then(r => r.json())
    .then(data => {
        console.log('API Response:', data);
        document.getElementById('apiResult').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        
        if (data.data && data.data.attendance && data.data.attendance.check_in_time) {
            alert('Check-in time: ' + data.data.attendance.check_in_time);
        }
    })
    .catch(err => {
        document.getElementById('apiResult').innerHTML = '<p class="error">Error: ' + err.message + '</p>';
    });
}
</script>

</body>
</html>

<?php
$kernel->terminate($request, $response);
?>