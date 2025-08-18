<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

echo "=== DEBUG ATTENDANCE DATES ===\n\n";

// Login as dr. Yaya
$yayaUser = User::find(13);
Auth::login($yayaUser);

$currentMonth = Carbon::now()->month;
$currentYear = Carbon::now()->year;
$startDate = Carbon::create($currentYear, $currentMonth, 1);
$endDate = $startDate->copy()->endOfMonth();

echo "User: {$yayaUser->name}\n";
echo "Period: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}\n\n";

$attendanceRecords = Attendance::where('user_id', $yayaUser->id)
    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
    ->orderBy('date')
    ->get();

echo "Raw Attendance Records: " . $attendanceRecords->count() . "\n\n";

$uniqueDates = [];
foreach ($attendanceRecords as $record) {
    $date = Carbon::parse($record->date)->format('Y-m-d'); // Ensure proper format
    echo "Raw Date: '{$record->date}' -> Parsed: '{$date}'\n";
    
    if (!in_array($date, $uniqueDates)) {
        $uniqueDates[] = $date;
    }
}

echo "\nUnique Dates Found: " . count($uniqueDates) . "\n";
foreach ($uniqueDates as $date) {
    echo "  - {$date}\n";
}

echo "\n=== CALENDAR CHECK ===\n";
$tempDate = $startDate->copy();
while ($tempDate->lte($endDate)) {
    $dateStr = $tempDate->format('Y-m-d');
    $hasAttendance = in_array($dateStr, $uniqueDates) ? 'YES' : 'NO';
    $dayOfWeek = $tempDate->format('l');
    
    echo "{$dateStr} ({$dayOfWeek}): {$hasAttendance}\n";
    $tempDate->addDay();
}

// Check using proper distinct count
$distinctCount = Attendance::where('user_id', $yayaUser->id)
    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
    ->distinct('date')
    ->count();

echo "\nDISTINCT COUNT FROM DATABASE: {$distinctCount}\n";

// Get distinct dates directly from database
$distinctDates = Attendance::where('user_id', $yayaUser->id)
    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
    ->distinct()
    ->pluck('date')
    ->map(function($date) {
        return Carbon::parse($date)->format('Y-m-d');
    })
    ->sort()
    ->values();

echo "\nDISTINCT DATES FROM DATABASE:\n";
foreach ($distinctDates as $date) {
    echo "  - {$date}\n";
}

echo "\nTotal Distinct Dates: " . $distinctDates->count() . "\n";