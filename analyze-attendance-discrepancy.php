<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Dokter;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== ANALISIS DISCREPANCY TINGKAT KEHADIRAN DR. YAYA ===\n\n";

// Login as dr. Yaya
$yayaUser = User::find(13);
Auth::login($yayaUser);
$dokter = Dokter::where('user_id', $yayaUser->id)->first();

$currentMonth = Carbon::now()->month;
$currentYear = Carbon::now()->year;

echo "User: {$yayaUser->name} (ID: {$yayaUser->id})\n";
echo "Period: {$currentYear}-{$currentMonth} (August 2025)\n\n";

// 1. LEADERBOARD CALCULATION LOGIC
echo "1. LEADERBOARD ATTENDANCE CALCULATION\n";
echo "=====================================\n";

// Replicate exact leaderboard calculation
$attendanceRecords = Attendance::where('user_id', $yayaUser->id)
    ->whereMonth('date', $currentMonth)
    ->whereYear('date', $currentYear)
    ->get();

echo "Total Attendance Records (Current Month): " . $attendanceRecords->count() . "\n\n";

foreach ($attendanceRecords as $record) {
    echo "Date: {$record->date}\n";
    echo "  - Time In: " . ($record->time_in ?: 'NULL') . "\n";
    echo "  - Time Out: " . ($record->time_out ?: 'NULL') . "\n";
    echo "  - Status: {$record->status}\n";
    echo "  - Work Minutes: " . ($record->logical_work_minutes ?: 0) . "\n";
    echo "  - Has Time Out: " . ($record->time_out ? 'YES' : 'NO') . "\n";
    echo "\n";
}

// Calculate using leaderboard logic
$attendanceCount = Attendance::where('user_id', $yayaUser->id)
    ->whereMonth('date', $currentMonth)
    ->whereYear('date', $currentYear)
    ->whereNotNull('time_out')
    ->count();

$workingDaysInMonth = Carbon::create($currentYear, $currentMonth)->daysInMonth;
$attendanceRate = $workingDaysInMonth > 0 ? ($attendanceCount / $workingDaysInMonth) * 100 : 0;

echo "LEADERBOARD CALCULATION:\n";
echo "  - Days with time_out: {$attendanceCount}\n";
echo "  - Total days in month: {$workingDaysInMonth}\n";
echo "  - Attendance Rate: " . round($attendanceRate, 1) . "%\n";
echo "  - Formula: ({$attendanceCount} / {$workingDaysInMonth}) * 100\n\n";

// 2. ALTERNATIVE CALCULATION METHODS
echo "2. ALTERNATIVE CALCULATION METHODS\n";
echo "==================================\n";

// Method 1: Only 'completed' status
$completedAttendance = Attendance::where('user_id', $yayaUser->id)
    ->whereMonth('date', $currentMonth)
    ->whereYear('date', $currentYear)
    ->where('status', 'completed')
    ->count();

$completedRate = $workingDaysInMonth > 0 ? ($completedAttendance / $workingDaysInMonth) * 100 : 0;

echo "METHOD 1 - Only 'completed' status:\n";
echo "  - Completed records: {$completedAttendance}\n";
echo "  - Rate: " . round($completedRate, 1) . "%\n\n";

// Method 2: Both time_in and time_out exist
$bothTimesAttendance = Attendance::where('user_id', $yayaUser->id)
    ->whereMonth('date', $currentMonth)
    ->whereYear('date', $currentYear)
    ->whereNotNull('time_in')
    ->whereNotNull('time_out')
    ->count();

$bothTimesRate = $workingDaysInMonth > 0 ? ($bothTimesAttendance / $workingDaysInMonth) * 100 : 0;

echo "METHOD 2 - Both time_in and time_out exist:\n";
echo "  - Records with both times: {$bothTimesAttendance}\n";
echo "  - Rate: " . round($bothTimesRate, 1) . "%\n\n";

// Method 3: Working days only (exclude weekends)
$workingDaysOnly = 0;
$startDate = Carbon::create($currentYear, $currentMonth, 1);
$endDate = Carbon::create($currentYear, $currentMonth)->endOfMonth();

for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
    if (!$date->isWeekend()) {
        $workingDaysOnly++;
    }
}

$workingDaysRate = $workingDaysOnly > 0 ? ($attendanceCount / $workingDaysOnly) * 100 : 0;

echo "METHOD 3 - Working days only (exclude weekends):\n";
echo "  - Working days in month: {$workingDaysOnly}\n";
echo "  - Days with time_out: {$attendanceCount}\n";
echo "  - Rate: " . round($workingDaysRate, 1) . "%\n\n";

// 3. CHECK STATS PRESENSI SOURCE
echo "3. STATS PRESENSI SOURCE ANALYSIS\n";
echo "==================================\n";

// Look for other attendance-related calculations
echo "Checking for different attendance calculation sources...\n\n";

// Method 4: Check if there's a different timeframe
$allTimeAttendance = Attendance::where('user_id', $yayaUser->id)->get();
echo "ALL TIME ATTENDANCE RECORDS:\n";

$monthlyBreakdown = [];
foreach ($allTimeAttendance as $record) {
    $date = Carbon::parse($record->date);
    $monthKey = $date->format('Y-m');
    
    if (!isset($monthlyBreakdown[$monthKey])) {
        $monthlyBreakdown[$monthKey] = [
            'total' => 0,
            'with_timeout' => 0,
            'completed' => 0
        ];
    }
    
    $monthlyBreakdown[$monthKey]['total']++;
    if ($record->time_out) {
        $monthlyBreakdown[$monthKey]['with_timeout']++;
    }
    if ($record->status === 'completed') {
        $monthlyBreakdown[$monthKey]['completed']++;
    }
}

foreach ($monthlyBreakdown as $month => $data) {
    $monthDate = Carbon::createFromFormat('Y-m', $month);
    $daysInMonth = $monthDate->daysInMonth;
    $rate = ($data['with_timeout'] / $daysInMonth) * 100;
    
    echo "  - {$month}: {$data['with_timeout']}/{$daysInMonth} days = " . round($rate, 1) . "%\n";
}

echo "\n";

// 4. POTENTIAL 56% CALCULATION
echo "4. REVERSE ENGINEERING 56% CALCULATION\n";
echo "======================================\n";

// Try to figure out how 56% could be calculated
$target = 56;
echo "Looking for calculation that results in ~{$target}%...\n\n";

// Test different denominators
$numerators = [$attendanceCount, $completedAttendance, $bothTimesAttendance];
$denominators = [$workingDaysInMonth, $workingDaysOnly, 25, 30, 35]; // Common work day counts

foreach ($numerators as $num) {
    foreach ($denominators as $den) {
        if ($den > 0) {
            $rate = ($num / $den) * 100;
            if (abs($rate - $target) < 2) { // Within 2% of target
                echo "POTENTIAL MATCH: {$num} / {$den} = " . round($rate, 1) . "%\n";
                echo "  - Numerator: {$num} (attendance days)\n";
                echo "  - Denominator: {$den} (possible base period)\n\n";
            }
        }
    }
}

// 5. SUMMARY AND EXPLANATION
echo "5. SUMMARY & EXPLANATION\n";
echo "========================\n";

echo "DISCREPANCY ANALYSIS:\n";
echo "  - Leaderboard: " . round($attendanceRate, 1) . "% (Current implementation)\n";
echo "  - Stats Presensi: 56% (Source unknown)\n";
echo "  - Difference: " . round(abs($attendanceRate - 56), 1) . " percentage points\n\n";

echo "LIKELY CAUSES:\n";
echo "1. Different calculation periods (month vs. custom range)\n";
echo "2. Different attendance criteria (time_out vs. completed status)\n";
echo "3. Different base calculations (calendar days vs. working days)\n";
echo "4. Different data sources or caching\n";
echo "5. Frontend vs. backend calculation differences\n\n";

echo "RECOMMENDATIONS:\n";
echo "1. Check the Stats Presensi component source code\n";
echo "2. Verify if it uses a different API endpoint\n";
echo "3. Check for hardcoded values or different time periods\n";
echo "4. Ensure both components use the same data source\n";
echo "5. Standardize attendance calculation logic across all components\n";