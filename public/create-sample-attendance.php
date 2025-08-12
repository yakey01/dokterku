<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkLocation;
use Carbon\Carbon;

echo "=== CREATING SAMPLE ATTENDANCE DATA ===\n\n";

// Get or create work location
$workLocation = WorkLocation::first();
if (!$workLocation) {
    $workLocation = WorkLocation::create([
        'name' => 'Klinik Utama',
        'latitude' => -7.898878,
        'longitude' => 111.961884,
        'radius' => 100,
        'is_active' => true
    ]);
    echo "✅ Created work location: Klinik Utama\n";
}

// Get all doctors (role_id = 6 for dokter)
$doctorRoleId = 6;
$doctors = User::where('role_id', $doctorRoleId)->get();

if ($doctors->isEmpty()) {
    echo "❌ No doctors found in the system\n";
    exit;
}

echo "Found " . $doctors->count() . " doctors\n\n";

// Create attendance for current month
$startDate = Carbon::now()->startOfMonth();
$today = Carbon::now();
$workingDays = 0;

// Different attendance patterns for each doctor
$attendancePatterns = [
    0 => 0.95,  // 95% attendance - top performer
    1 => 0.85,  // 85% attendance - good performer  
    2 => 0.75,  // 75% attendance - average
    3 => 0.65,  // 65% attendance - below average
    4 => 0.90,  // 90% attendance - excellent
];

foreach ($doctors as $index => $doctor) {
    $attendanceRate = $attendancePatterns[$index % count($attendancePatterns)];
    $createdCount = 0;
    $currentStreak = 0;
    $maxStreak = 0;
    
    echo "Creating attendance for: {$doctor->name}\n";
    
    // Loop through each day of the month up to today
    for ($date = $startDate->copy(); $date <= $today; $date->addDay()) {
        // Skip weekends
        if ($date->isWeekend()) {
            continue;
        }
        
        $workingDays++;
        
        // Determine if doctor attended based on their pattern
        $shouldAttend = (mt_rand(1, 100) / 100) <= $attendanceRate;
        
        if ($shouldAttend) {
            // Random check-in time between 7:30 and 9:00
            $checkInHour = mt_rand(7, 8);
            $checkInMinute = mt_rand(0, 59);
            $checkInTime = $date->copy()->setTime($checkInHour, $checkInMinute);
            
            // Random check-out time between 16:00 and 18:00
            $checkOutHour = mt_rand(16, 17);
            $checkOutMinute = mt_rand(0, 59);
            $checkOutTime = $date->copy()->setTime($checkOutHour, $checkOutMinute);
            
            // Random shift
            $shifts = ['morning', 'evening'];
            $shift = $shifts[array_rand($shifts)];
            
            // Create attendance record
            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $doctor->id,
                    'date' => $date->format('Y-m-d')
                ],
                [
                    'time_in' => $checkInTime->format('H:i:s'),
                    'time_out' => $checkOutTime->format('H:i:s'),
                    'status' => 'present',
                    'work_location_id' => $workLocation->id,
                    'latitude' => $workLocation->latitude + (mt_rand(-10, 10) / 10000),
                    'longitude' => $workLocation->longitude + (mt_rand(-10, 10) / 10000),
                    'checkout_latitude' => $workLocation->latitude + (mt_rand(-10, 10) / 10000),
                    'checkout_longitude' => $workLocation->longitude + (mt_rand(-10, 10) / 10000),
                    'latlon_in' => ($workLocation->latitude + (mt_rand(-10, 10) / 10000)) . ',' . ($workLocation->longitude + (mt_rand(-10, 10) / 10000)),
                    'latlon_out' => ($workLocation->latitude + (mt_rand(-10, 10) / 10000)) . ',' . ($workLocation->longitude + (mt_rand(-10, 10) / 10000)),
                    'location_name_in' => 'Klinik Utama',
                    'location_name_out' => 'Klinik Utama',
                    'notes' => 'Sample attendance data',
                    'logical_time_in' => $checkInTime->format('H:i:s'),
                    'logical_time_out' => $checkOutTime->format('H:i:s'),
                    'logical_work_minutes' => $checkOutTime->diffInMinutes($checkInTime)
                ]
            );
            
            $createdCount++;
            $currentStreak++;
            $maxStreak = max($maxStreak, $currentStreak);
        } else {
            $currentStreak = 0;
        }
    }
    
    $actualRate = $workingDays > 0 ? round(($createdCount / $workingDays) * 100, 1) : 0;
    echo "  ✅ Created {$createdCount} attendance records (Rate: {$actualRate}%, Max Streak: {$maxStreak} days)\n";
}

echo "\n=== SAMPLE DATA CREATED SUCCESSFULLY ===\n";
echo "Total working days this month: {$workingDays}\n\n";

// Test the leaderboard API
echo "Testing leaderboard API with new data...\n\n";

use App\Http\Controllers\Api\V2\Dashboards\LeaderboardController;

$controller = new LeaderboardController();
$result = $controller->getTopDoctors();
$data = json_decode($result->content(), true);

if ($data['success']) {
    echo "🏆 TOP 3 DOCTORS (With Sample Data):\n";
    echo str_repeat("=", 60) . "\n";
    
    foreach ($data['data']['leaderboard'] as $doctor) {
        echo "\n" . $doctor['badge'] . " Rank #" . $doctor['rank'] . ": " . $doctor['name'] . "\n";
        echo "   📊 Attendance: " . $doctor['attendance_rate'] . "%\n";
        echo "   🎮 Level: " . $doctor['level'] . "\n";
        echo "   ⭐ XP: " . number_format($doctor['xp']) . "\n";
        echo "   📅 Days Present: " . $doctor['total_days'] . "\n";
        echo "   ⏱️ Total Hours: " . $doctor['total_hours'] . "h\n";
        echo "   🔥 Streak: " . $doctor['streak_days'] . " days\n";
    }
}

$kernel->terminate($request, $response);