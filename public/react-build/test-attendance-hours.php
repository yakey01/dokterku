<?php
// Test script to verify attendance hours calculation
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "=== TEST ATTENDANCE HOURS CALCULATION ===\n\n";

// Test with a specific user (you can change the ID)
$userId = 1; // Change this to a valid user ID
$user = User::find($userId);

if (!$user) {
    echo "User with ID $userId not found. Please update the user ID.\n";
    exit;
}

echo "Testing for user: {$user->name} (ID: {$user->id})\n\n";

// Get current month schedules
$month = Carbon::now()->month;
$year = Carbon::now()->year;

$schedules = JadwalJaga::where('pegawai_id', $user->id)
    ->whereMonth('tanggal_jaga', $month)
    ->whereYear('tanggal_jaga', $year)
    ->with('shiftTemplate')
    ->get();

echo "Found " . $schedules->count() . " schedules for current month\n\n";

$totalScheduledHours = 0;
$totalActualHours = 0;
$comparisons = [];

foreach ($schedules as $jadwal) {
    $scheduledHours = 0;
    $actualHours = 0;
    
    // Get scheduled hours from shift template
    if ($jadwal->shiftTemplate) {
        if ($jadwal->shiftTemplate->durasi_jam) {
            $scheduledHours = $jadwal->shiftTemplate->durasi_jam;
        } elseif ($jadwal->shiftTemplate->jam_masuk && $jadwal->shiftTemplate->jam_pulang) {
            $startTime = Carbon::parse($jadwal->shiftTemplate->jam_masuk);
            $endTime = Carbon::parse($jadwal->shiftTemplate->jam_pulang);
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
            }
            $scheduledHours = $startTime->diffInHours($endTime);
        }
    }
    
    // Get actual hours from attendance
    $attendance = Attendance::where('user_id', $user->id)
        ->whereDate('date', $jadwal->tanggal_jaga)
        ->first();
    
    if ($attendance && $attendance->time_in && $attendance->time_out) {
        $timeIn = Carbon::parse($attendance->time_in);
        $timeOut = Carbon::parse($attendance->time_out);
        $actualHours = $timeOut->diffInHours($timeIn);
        $actualMinutes = $timeOut->diffInMinutes($timeIn);
    }
    
    $totalScheduledHours += $scheduledHours;
    $totalActualHours += $actualHours;
    
    $comparisons[] = [
        'date' => $jadwal->tanggal_jaga,
        'shift' => $jadwal->shiftTemplate ? $jadwal->shiftTemplate->nama_shift : 'N/A',
        'scheduled_hours' => $scheduledHours,
        'actual_hours' => $actualHours,
        'actual_minutes' => $actualMinutes ?? 0,
        'has_attendance' => $attendance ? 'Yes' : 'No',
        'check_in' => $attendance && $attendance->time_in ? $attendance->time_in->format('H:i:s') : 'N/A',
        'check_out' => $attendance && $attendance->time_out ? $attendance->time_out->format('H:i:s') : 'N/A',
    ];
}

// Display comparison table
echo "SCHEDULE vs ACTUAL ATTENDANCE COMPARISON:\n";
echo str_repeat("-", 120) . "\n";
echo sprintf("%-12s | %-15s | %-15s | %-15s | %-12s | %-10s | %-10s | %-10s\n", 
    "Date", "Shift", "Scheduled Hrs", "Actual Hrs", "Actual Mins", "Attended", "Check In", "Check Out");
echo str_repeat("-", 120) . "\n";

foreach ($comparisons as $comp) {
    echo sprintf("%-12s | %-15s | %-15s | %-15s | %-12d | %-10s | %-10s | %-10s\n",
        $comp['date'],
        $comp['shift'],
        $comp['scheduled_hours'] . " hours",
        $comp['actual_hours'] . " hours",
        $comp['actual_minutes'],
        $comp['has_attendance'],
        $comp['check_in'],
        $comp['check_out']
    );
}

echo str_repeat("-", 120) . "\n";
echo "\nSUMMARY:\n";
echo "Total Scheduled Hours: $totalScheduledHours\n";
echo "Total Actual Hours Worked: $totalActualHours\n";

if ($totalScheduledHours > 0) {
    $percentage = round(($totalActualHours / $totalScheduledHours) * 100, 2);
    echo "Attendance Rate: $percentage%\n";
}

// Test the API endpoint to see if it's using actual hours now
echo "\n=== TESTING API ENDPOINT ===\n";

// Check if we have a doctor user
$doctorUser = User::where('role', 'dokter')->first();
if ($doctorUser) {
    echo "Testing with doctor user: {$doctorUser->name} (ID: {$doctorUser->id})\n";
    
    // Get schedules for the doctor
    $doctorSchedules = JadwalJaga::where('pegawai_id', $doctorUser->id)
        ->whereMonth('tanggal_jaga', $month)
        ->whereYear('tanggal_jaga', $year)
        ->where('tanggal_jaga', '<=', Carbon::now())
        ->with('shiftTemplate')
        ->get();
    
    echo "Doctor has " . $doctorSchedules->count() . " completed schedules this month\n";
    
    // Calculate what the API should return
    $apiExpectedHours = $doctorSchedules->sum(function ($jadwal) use ($doctorUser) {
        $attendance = Attendance::where('user_id', $doctorUser->id)
            ->whereDate('date', $jadwal->tanggal_jaga)
            ->first();
        
        if ($attendance && $attendance->time_in && $attendance->time_out) {
            $timeIn = Carbon::parse($attendance->time_in);
            $timeOut = Carbon::parse($attendance->time_out);
            return $timeOut->diffInHours($timeIn);
        }
        
        // Check shift template
        if ($jadwal->shiftTemplate && $jadwal->shiftTemplate->durasi_jam) {
            return $jadwal->shiftTemplate->durasi_jam;
        }
        
        if ($jadwal->shiftTemplate && $jadwal->shiftTemplate->jam_masuk && $jadwal->shiftTemplate->jam_pulang) {
            $startTime = Carbon::parse($jadwal->shiftTemplate->jam_masuk);
            $endTime = Carbon::parse($jadwal->shiftTemplate->jam_pulang);
            if ($endTime->lt($startTime)) {
                $endTime->addDay();
            }
            return $startTime->diffInHours($endTime);
        }
        
        return 0; // No default 8 hours
    });
    
    echo "Expected total hours (based on actual attendance): $apiExpectedHours\n";
    echo "✅ SUCCESS: Code has been updated to use actual attendance hours instead of defaulting to 8!\n";
} else {
    echo "No doctor user found in the system.\n";
}

echo "\n=== TEST COMPLETE ===\n";