<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Dokter;
use App\Models\Attendance;
use App\Models\AttendanceRecap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== ANALISIS 56% TINGKAT KEHADIRAN DR. YAYA ===\n\n";

// Login as dr. Yaya
$yayaUser = User::find(13);
Auth::login($yayaUser);
$dokter = Dokter::where('user_id', $yayaUser->id)->first();

$currentMonth = Carbon::now()->month;
$currentYear = Carbon::now()->year;

echo "User: {$yayaUser->name} (ID: {$yayaUser->id})\n";
echo "Period: {$currentYear}-{$currentMonth} (August 2025)\n\n";

// 1. TEST API/PRESENSI ENDPOINT CALCULATION
echo "1. API/V2/DASHBOARDS/DOKTER/PRESENSI CALCULATION\n";
echo "==============================================\n";

// Replicate the getAttendanceRate calculation from getPresensi method
$startDate = Carbon::create($currentYear, $currentMonth, 1);
$endDate = $startDate->copy()->endOfMonth();

// Count working days (Monday to Saturday, exclude Sunday) - like in getAttendanceRate
$workingDays = 0;
$tempDate = $startDate->copy();
while ($tempDate->lte($endDate)) {
    if ($tempDate->dayOfWeek !== Carbon::SUNDAY) {
        $workingDays++;
    }
    $tempDate->addDay();
}

echo "WORKING DAYS CALCULATION (Monday-Saturday):\n";
echo "  - Start Date: {$startDate->format('Y-m-d')}\n";
echo "  - End Date: {$endDate->format('Y-m-d')}\n";
echo "  - Working Days (exclude Sunday): {$workingDays}\n\n";

// Count attendance days using the same query as getAttendanceRate
$attendanceDays = Attendance::where('user_id', $yayaUser->id)
    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
    ->distinct('date')
    ->count();

$fallbackRate = $workingDays > 0 ? round(($attendanceDays / $workingDays) * 100, 2) : 0;

echo "ATTENDANCE CALCULATION:\n";
echo "  - Attendance Days (distinct dates): {$attendanceDays}\n";
echo "  - Working Days: {$workingDays}\n";
echo "  - Fallback Rate: {$fallbackRate}%\n";
echo "  - Formula: ({$attendanceDays} / {$workingDays}) * 100\n\n";

// 2. CHECK ATTENDANCERECAP MODEL
echo "2. ATTENDANCERECAP MODEL CALCULATION\n";
echo "====================================\n";

try {
    $attendanceData = AttendanceRecap::getRecapData($currentMonth, $currentYear, 'Dokter');
    
    echo "AttendanceRecap Total Records: " . $attendanceData->count() . "\n\n";
    
    $foundUser = false;
    foreach ($attendanceData as $staff) {
        if ($staff['staff_id'] == $yayaUser->id) {
            echo "✅ FOUND DR. YAYA IN ATTENDANCERECAP:\n";
            echo "  - Staff ID: {$staff['staff_id']}\n";
            echo "  - Name: " . ($staff['name'] ?? 'N/A') . "\n";
            echo "  - Attendance Percentage: {$staff['attendance_percentage']}%\n";
            echo "  - Rank: " . ($staff['rank'] ?? 'N/A') . "\n";
            $foundUser = true;
            
            if ($staff['attendance_percentage'] == 56 || abs($staff['attendance_percentage'] - 56) < 1) {
                echo "  🎯 THIS IS THE SOURCE OF 56%!\n";
            }
            break;
        }
    }
    
    if (!$foundUser) {
        echo "❌ Dr. Yaya NOT FOUND in AttendanceRecap data\n";
        echo "Using fallback calculation: {$fallbackRate}%\n";
    }
    
} catch (\Exception $e) {
    echo "❌ AttendanceRecap::getRecapData failed: " . $e->getMessage() . "\n";
    echo "Using fallback calculation: {$fallbackRate}%\n";
}

echo "\n";

// 3. DETAILED ATTENDANCE RECORDS
echo "3. DETAILED ATTENDANCE RECORDS ANALYSIS\n";
echo "=======================================\n";

$attendanceRecords = Attendance::where('user_id', $yayaUser->id)
    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
    ->orderBy('date')
    ->get();

echo "Raw Attendance Records (Current Month): " . $attendanceRecords->count() . "\n\n";

$uniqueDates = [];
foreach ($attendanceRecords as $record) {
    $date = $record->date;
    if (!in_array($date, $uniqueDates)) {
        $uniqueDates[] = $date;
    }
    
    $dayOfWeek = Carbon::parse($date)->format('l');
    $timeIn = $record->time_in ?: 'NULL';
    $timeOut = $record->time_out ?: 'NULL';
    $status = $record->status;
    
    echo "  - {$date} ({$dayOfWeek}): In={$timeIn}, Out={$timeOut}, Status={$status}\n";
}

echo "\nUnique Attendance Dates: " . count($uniqueDates) . "\n";

// 4. CALENDAR BREAKDOWN
echo "\n4. CALENDAR BREAKDOWN\n";
echo "=====================\n";

$calendarDays = $endDate->day;
$sundays = 0;
$attendedSundays = 0;

$tempDate = $startDate->copy();
while ($tempDate->lte($endDate)) {
    $dayOfWeek = $tempDate->format('l');
    $dateStr = $tempDate->format('Y-m-d');
    $hasAttendance = in_array($dateStr, $uniqueDates) ? 'YES' : 'NO';
    
    if ($tempDate->dayOfWeek === Carbon::SUNDAY) {
        $sundays++;
        if ($hasAttendance === 'YES') {
            $attendedSundays++;
        }
    }
    
    echo "  {$dateStr} ({$dayOfWeek}): Attended={$hasAttendance}\n";
    $tempDate->addDay();
}

echo "\nSUMMARY:\n";
echo "  - Total Calendar Days: {$calendarDays}\n";
echo "  - Total Sundays: {$sundays}\n";
echo "  - Working Days (exclude Sundays): {$workingDays}\n";
echo "  - Attendance Days: {$attendanceDays}\n";
echo "  - Attended Sundays: {$attendedSundays}\n\n";

// 5. DIFFERENT CALCULATION METHODS
echo "5. DIFFERENT CALCULATION METHODS\n";
echo "================================\n";

$method1 = $calendarDays > 0 ? round(($attendanceDays / $calendarDays) * 100, 2) : 0;
$method2 = $workingDays > 0 ? round(($attendanceDays / $workingDays) * 100, 2) : 0;
$method3 = 21 > 0 ? round(($attendanceDays / 21) * 100, 2) : 0; // Standard working days
$method4 = 26 > 0 ? round(($attendanceDays / 26) * 100, 2) : 0; // Working days in typical month

echo "METHOD 1 - Calendar Days: {$attendanceDays} / {$calendarDays} = {$method1}%\n";
echo "METHOD 2 - Working Days (Mon-Sat): {$attendanceDays} / {$workingDays} = {$method2}%\n";
echo "METHOD 3 - Standard Working Days (21): {$attendanceDays} / 21 = {$method3}%\n";
echo "METHOD 4 - Typical Working Days (26): {$attendanceDays} / 26 = {$method4}%\n\n";

// Check which one matches 56%
$target = 56;
$methods = [
    'Calendar Days' => $method1,
    'Working Days (Mon-Sat)' => $method2,
    'Standard 21 Days' => $method3,
    'Typical 26 Days' => $method4
];

foreach ($methods as $name => $value) {
    if (abs($value - $target) < 2) {
        echo "🎯 POTENTIAL MATCH: {$name} = {$value}% (close to {$target}%)\n";
    }
}

// 6. COMPARISON WITH LEADERBOARD
echo "\n6. COMPARISON WITH LEADERBOARD CALCULATION\n";
echo "==========================================\n";

// Leaderboard calculation (working days only, with time_out)
$leaderboardAttendanceCount = Attendance::where('user_id', $yayaUser->id)
    ->whereMonth('date', $currentMonth)
    ->whereYear('date', $currentYear)
    ->whereNotNull('time_out')
    ->count();

$workingDaysInMonth = Carbon::create($currentYear, $currentMonth)->daysInMonth;
$leaderboardAttendanceRate = $workingDaysInMonth > 0 ? ($leaderboardAttendanceCount / $workingDaysInMonth) * 100 : 0;

// Alternative: working days only (exclude weekends)
$workingDaysOnlyCount = 0;
$tempDate2 = Carbon::create($currentYear, $currentMonth, 1);
$endDate2 = Carbon::create($currentYear, $currentMonth)->endOfMonth();

for ($date = $tempDate2->copy(); $date <= $endDate2; $date->addDay()) {
    if (!$date->isWeekend()) {
        $workingDaysOnlyCount++;
    }
}

$leaderboardWorkingDaysRate = $workingDaysOnlyCount > 0 ? ($leaderboardAttendanceCount / $workingDaysOnlyCount) * 100 : 0;

echo "LEADERBOARD vs PRESENSI COMPARISON:\n";
echo "  - Leaderboard (days with time_out): {$leaderboardAttendanceCount}\n";
echo "  - Leaderboard rate (calendar days): " . round($leaderboardAttendanceRate, 1) . "%\n";
echo "  - Leaderboard rate (working days): " . round($leaderboardWorkingDaysRate, 1) . "%\n";
echo "  - Presensi API rate (working days): {$fallbackRate}%\n";
echo "  - Difference: " . round(abs($leaderboardWorkingDaysRate - $fallbackRate), 1) . " percentage points\n\n";

// 7. CONCLUSIONS
echo "7. CONCLUSION & EXPLANATION\n";
echo "===========================\n";

echo "DISCREPANCY SOURCES:\n";
echo "1. Different calculation methods:\n";
echo "   - Leaderboard: Records with time_out / working days (no Sundays)\n";
echo "   - Presensi API: Distinct attendance dates / working days (Mon-Sat)\n\n";

echo "2. Different criteria:\n";
echo "   - Leaderboard: Requires time_out (completed attendance)\n";
echo "   - Presensi API: Any attendance record (time_in exists)\n\n";

echo "3. Different base calculations:\n";
echo "   - Leaderboard working days: {$workingDaysOnlyCount} (exclude weekends)\n";
echo "   - Presensi working days: {$workingDays} (exclude Sundays only)\n\n";

if (abs($fallbackRate - 56) < 2) {
    echo "🎯 FOUND THE 56% SOURCE!\n";
    echo "The 56% comes from the API/V2/DASHBOARDS/DOKTER/PRESENSI endpoint\n";
    echo "using the fallback calculation: {$attendanceDays} attendance days / {$workingDays} working days\n\n";
}

echo "RECOMMENDATIONS:\n";
echo "1. Standardize attendance calculation across all endpoints\n";
echo "2. Use consistent working days definition (weekends vs Sunday only)\n";
echo "3. Use consistent attendance criteria (time_out vs time_in)\n";
echo "4. Document which calculation method is the 'official' one\n";
echo "5. Update frontend to use the same API endpoint for consistency\n";