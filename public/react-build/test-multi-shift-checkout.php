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

echo "<h1>Test Multi-Shift Checkout</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

try {
    // Test with a user who might have multiple shifts
    $testUserId = 11; // Change this to a valid user ID in your system
    $user = User::find($testUserId);
    
    if (!$user) {
        echo "<p class='error'>User with ID $testUserId not found!</p>";
        exit;
    }
    
    echo "<h2>Testing with User: {$user->name} (ID: {$user->id})</h2>";
    
    // Get today's attendance status
    $attendanceStatus = Attendance::getTodayStatus($user->id);
    
    echo "<h3>1. Today's Attendance Status</h3>";
    echo "<table>";
    echo "<tr><th>Status</th><td>{$attendanceStatus['status']}</td></tr>";
    echo "<tr><th>Message</th><td>{$attendanceStatus['message']}</td></tr>";
    echo "<tr><th>Can Check In</th><td>" . ($attendanceStatus['can_check_in'] ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "<tr><th>Can Check Out</th><td>" . ($attendanceStatus['can_check_out'] ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "</table>";
    
    // Get all today's attendances
    $todayAttendances = Attendance::getTodayAttendances($user->id);
    
    echo "<h3>2. All Today's Attendances (" . $todayAttendances->count() . " records)</h3>";
    if ($todayAttendances->isEmpty()) {
        echo "<p class='info'>No attendance records for today.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Time In</th><th>Time Out</th><th>Status</th><th>Can Checkout?</th><th>Shift Info</th></tr>";
        foreach ($todayAttendances as $attendance) {
            $canCheckout = $attendance->canCheckOut() ? '✅ Yes' : '❌ No';
            $shiftInfo = $attendance->jadwalJaga ? 
                $attendance->jadwalJaga->shiftTemplate->nama_shift . ' (' . 
                $attendance->jadwalJaga->shiftTemplate->jam_masuk . ' - ' . 
                $attendance->jadwalJaga->shiftTemplate->jam_pulang . ')' : 
                'No shift info';
            
            echo "<tr>";
            echo "<td>{$attendance->id}</td>";
            echo "<td>" . ($attendance->time_in ? $attendance->time_in->format('H:i:s') : '-') . "</td>";
            echo "<td>" . ($attendance->time_out ? $attendance->time_out->format('H:i:s') : '-') . "</td>";
            echo "<td>{$attendance->status}</td>";
            echo "<td>{$canCheckout}</td>";
            echo "<td>{$shiftInfo}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Get current active attendance
    $activeAttendance = Attendance::getCurrentActiveAttendance($user->id);
    
    echo "<h3>3. Current Active Attendance</h3>";
    if ($activeAttendance) {
        echo "<table>";
        echo "<tr><th>ID</th><td>{$activeAttendance->id}</td></tr>";
        echo "<tr><th>Time In</th><td>{$activeAttendance->time_in->format('H:i:s')}</td></tr>";
        echo "<tr><th>Status</th><td>{$activeAttendance->status}</td></tr>";
        if ($activeAttendance->jadwalJaga) {
            echo "<tr><th>Shift</th><td>{$activeAttendance->jadwalJaga->shiftTemplate->nama_shift}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No active attendance (all shifts completed or no check-in yet).</p>";
    }
    
    // Test the validation service
    echo "<h3>4. Checkout Validation Test</h3>";
    $validationService = app(\App\Services\AttendanceValidationService::class);
    
    // Test coordinates (adjust these to your actual work location)
    $testLat = -7.898878;
    $testLon = 111.961884;
    $testAccuracy = 10.0;
    
    $validation = $validationService->validateCheckout($user, $testLat, $testLon, $testAccuracy);
    
    echo "<table>";
    echo "<tr><th>Valid</th><td>" . ($validation['valid'] ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "<tr><th>Message</th><td>{$validation['message']}</td></tr>";
    echo "<tr><th>Code</th><td>{$validation['code']}</td></tr>";
    if (isset($validation['data'])) {
        echo "<tr><th>Additional Data</th><td><pre>" . json_encode($validation['data'], JSON_PRETTY_PRINT) . "</pre></td></tr>";
    }
    echo "</table>";
    
} catch (\Exception $e) {
    echo "<h3 class='error'>ERROR:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='/paramedis/mobile-app'>Back to Paramedis App</a></p>";

$kernel->terminate($request, $response);