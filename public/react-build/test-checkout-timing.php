<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use Carbon\Carbon;

echo "<h1>Test Checkout Timing Validation</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .warning { color: orange; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
</style>";

try {
    // Test with a specific user
    $testUserId = 11; // Change this to a valid user ID
    $user = User::find($testUserId);
    
    if (!$user) {
        echo "<p class='error'>User with ID $testUserId not found!</p>";
        exit;
    }
    
    echo "<h2>Testing User: {$user->name} (ID: {$user->id})</h2>";
    echo "<p><strong>Current Time:</strong> " . Carbon::now()->format('H:i:s') . " (" . Carbon::now()->timezone . ")</p>";
    
    // Get active attendance
    $activeAttendance = Attendance::getCurrentActiveAttendance($user->id);
    
    if (!$activeAttendance) {
        echo "<p class='warning'>No active attendance found. User needs to check-in first.</p>";
    } else {
        echo "<h3>Active Attendance Details</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><td>{$activeAttendance->id}</td></tr>";
        echo "<tr><th>Check-in Time</th><td>{$activeAttendance->time_in->format('H:i:s')}</td></tr>";
        echo "<tr><th>Work Duration (minutes)</th><td>" . Carbon::now()->diffInMinutes($activeAttendance->time_in) . "</td></tr>";
        echo "</table>";
        
        // Get jadwal jaga details
        if ($activeAttendance->jadwal_jaga_id) {
            $jadwalJaga = JadwalJaga::with('shiftTemplate')->find($activeAttendance->jadwal_jaga_id);
            if ($jadwalJaga && $jadwalJaga->shiftTemplate) {
                echo "<h3>Shift Details</h3>";
                echo "<table>";
                echo "<tr><th>Shift Name</th><td>{$jadwalJaga->shiftTemplate->nama_shift}</td></tr>";
                echo "<tr><th>Shift Start</th><td>{$jadwalJaga->shiftTemplate->jam_masuk}</td></tr>";
                echo "<tr><th>Shift End</th><td>{$jadwalJaga->shiftTemplate->jam_pulang}</td></tr>";
                echo "</table>";
                
                // Calculate checkout window
                $shiftEnd = Carbon::createFromFormat('H:i', $jadwalJaga->shiftTemplate->jam_pulang);
                $currentTimeOnly = Carbon::createFromFormat('H:i:s', Carbon::now()->format('H:i:s'));
                
                // Get work location for tolerance settings
                $workLocation = $user->workLocation;
                $earlyDepartureTolerance = $workLocation ? $workLocation->early_departure_tolerance_minutes ?? 15 : 15;
                $checkoutAfterShift = $workLocation ? $workLocation->checkout_after_shift_minutes ?? 60 : 60;
                
                $checkoutEarliest = $shiftEnd->copy()->subMinutes($earlyDepartureTolerance);
                $checkoutLatest = $shiftEnd->copy()->addMinutes($checkoutAfterShift);
                
                echo "<h3>Checkout Window Calculation</h3>";
                echo "<table>";
                echo "<tr><th>Shift End Time</th><td>{$shiftEnd->format('H:i')}</td></tr>";
                echo "<tr><th>Early Departure Tolerance</th><td>{$earlyDepartureTolerance} minutes</td></tr>";
                echo "<tr><th>Checkout After Shift</th><td>{$checkoutAfterShift} minutes</td></tr>";
                echo "<tr><th>Earliest Checkout</th><td>{$checkoutEarliest->format('H:i')}</td></tr>";
                echo "<tr><th>Latest Checkout</th><td>{$checkoutLatest->format('H:i')}</td></tr>";
                echo "<tr><th>Current Time (H:i:s)</th><td>{$currentTimeOnly->format('H:i:s')}</td></tr>";
                echo "</table>";
                
                // Check timing
                echo "<h3>Checkout Timing Status</h3>";
                if ($currentTimeOnly->lt($checkoutEarliest)) {
                    $minutesLeft = $currentTimeOnly->diffInMinutes($checkoutEarliest);
                    echo "<p class='error'>❌ TOO EARLY: Can checkout in {$minutesLeft} minutes (at {$checkoutEarliest->format('H:i')})</p>";
                } elseif ($currentTimeOnly->gt($checkoutLatest)) {
                    $minutesLate = $currentTimeOnly->diffInMinutes($shiftEnd);
                    echo "<p class='warning'>⚠️ VERY LATE: {$minutesLate} minutes after shift end (overtime)</p>";
                } elseif ($currentTimeOnly->lt($shiftEnd)) {
                    $minutesEarly = $currentTimeOnly->diffInMinutes($shiftEnd);
                    echo "<p class='success'>✅ WITHIN TOLERANCE: {$minutesEarly} minutes before shift end</p>";
                } else {
                    echo "<p class='success'>✅ ON TIME or NORMAL OVERTIME</p>";
                }
            }
        }
    }
    
    // Test validation service
    echo "<h3>Validation Service Test</h3>";
    $validationService = app(\App\Services\AttendanceValidationService::class);
    
    // Test coordinates
    $testLat = -7.898878;
    $testLon = 111.961884;
    $testAccuracy = 10.0;
    
    $validation = $validationService->validateCheckout($user, $testLat, $testLon, $testAccuracy);
    
    echo "<table>";
    echo "<tr><th>Valid</th><td>" . ($validation['valid'] ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "<tr><th>Message</th><td>{$validation['message']}</td></tr>";
    echo "<tr><th>Code</th><td>{$validation['code']}</td></tr>";
    echo "</table>";
    
    if (isset($validation['data'])) {
        echo "<h4>Validation Data</h4>";
        echo "<pre>" . json_encode($validation['data'], JSON_PRETTY_PRINT) . "</pre>";
    }
    
} catch (\Exception $e) {
    echo "<h3 class='error'>ERROR:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='/paramedis/mobile-app'>Back to Paramedis App</a> | ";
echo "<a href='/admin/jadwal-jagas'>Jadwal Jaga Admin</a> | ";
echo "<a href='javascript:location.reload()'>Refresh</a></p>";

$kernel->terminate($request, $response);