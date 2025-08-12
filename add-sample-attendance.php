<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

// Get doctors
$doctors = User::whereHas('roles', function ($q) { 
    $q->whereIn('name', ['dokter', 'dokter_gigi', 'paramedis']); 
})->take(3)->get();

// Add some attendance records for August 2025
foreach ($doctors as $index => $doctor) {
    // Different attendance rates for each doctor
    if ($index == 0) {
        $attendanceDays = 18; // 85.7% attendance
    } elseif ($index == 1) {
        $attendanceDays = 20; // 95.2% attendance
    } else {
        $attendanceDays = 15; // 71.4% attendance
    }
    
    // Clear existing attendance for this month
    Attendance::where('user_id', $doctor->id)
        ->whereMonth('check_in_time', 8)
        ->whereYear('check_in_time', 2025)
        ->delete();
    
    $daysAdded = 0;
    for ($i = 1; $i <= 31 && $daysAdded < $attendanceDays; $i++) {
        $date = Carbon::create(2025, 8, $i);
        if (!$date->isWeekend()) {
            $checkIn = $date->copy()->setTime(8, rand(0, 30));
            $checkOut = $date->copy()->setTime(16 + rand(0, 2), rand(0, 59));
            
            Attendance::create([
                'user_id' => $doctor->id,
                'date' => $date->toDateString(),
                'check_in_time' => $checkIn->toDateTimeString(),
                'check_out_time' => $checkOut->toDateTimeString(),
                'time_in' => $checkIn->format('H:i:s'),
                'time_out' => $checkOut->format('H:i:s'),
                'status' => 'present',
                'work_location_id' => 3,
                'latitude' => -7.898878,
                'longitude' => 111.961884,
                'accuracy' => rand(10, 50),
                'latlon_in' => '-7.898878,111.961884',
                'latlon_out' => '-7.898878,111.961884',
                'location_name_in' => 'Klinik Dokterku',
                'location_name_out' => 'Klinik Dokterku'
            ]);
            $daysAdded++;
        }
    }
    echo "Added $daysAdded attendance days for " . $doctor->name . PHP_EOL;
}

echo "Completed adding sample attendance data\n";