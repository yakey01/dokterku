<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\PresensiFull;

// Create a schedule for Aji that has already started
$user = User::where('name', 'LIKE', '%aji%')->first();

if ($user) {
    $now = now();
    $currentTime = $now->format('H:i:s');
    
    // Check if schedule already exists
    $existingJadwal = JadwalJaga::where('pegawai_id', $user->pegawai_id)
        ->whereDate('tanggal_jaga', $now->format('Y-m-d'))
        ->first();
    
    if (!$existingJadwal) {
        // Create a schedule for tes 4 shift
        $jadwal = new JadwalJaga();
        $jadwal->pegawai_id = $user->pegawai_id;
        $jadwal->tanggal_jaga = $now->format('Y-m-d');
        $jadwal->shift_template_id = 4; // tes 4
        $jadwal->unit_kerja = 'Dokter';
        $jadwal->peran = 'Dokter';
        $jadwal->save();
        
        echo "Created schedule for {$user->name} on {$jadwal->tanggal_jaga}\n";
        echo "Shift: tes 4 (19:30 - 19:45)\n";
    } else {
        $jadwal = $existingJadwal;
        echo "Schedule already exists for {$user->name}\n";
    }
    
    echo "Current time: {$now->format('H:i')}\n";
    
    // Check if the shift has started
    $shiftStartTime = strtotime('19:30:00');
    $currentTimeStamp = strtotime($currentTime);
    
    if ($currentTimeStamp < $shiftStartTime) {
        echo "⚠️ Shift hasn't started yet. It starts at 19:30\n";
        echo "The user cannot check in or check out until the shift starts.\n";
    } else {
        // Create or check attendance record
        $attendance = PresensiFull::where('user_id', $user->id)
            ->whereDate('tanggal', $now->format('Y-m-d'))
            ->where('jadwal_jaga_id', $jadwal->id)
            ->first();
        
        if (!$attendance) {
            $attendance = new PresensiFull();
            $attendance->user_id = $user->id;
            $attendance->tanggal = $now->format('Y-m-d');
            $attendance->jadwal_jaga_id = $jadwal->id;
            $attendance->time_in = '19:30:00'; // Simulate check-in at shift start
            $attendance->save();
            echo "✅ Created check-in record at 19:30\n";
            echo "User should now be able to check out.\n";
        } else {
            echo "Attendance record already exists\n";
            if ($attendance->time_in && !$attendance->time_out) {
                echo "✅ User is checked in and can check out\n";
            } elseif ($attendance->time_out) {
                echo "User has already checked out at: {$attendance->time_out}\n";
            }
        }
    }
    
    // Check checkout window
    $shiftEndTime = strtotime('19:45:00');
    $checkoutBuffer = 30 * 60; // 30 minutes
    $maxCheckoutTime = $shiftEndTime + $checkoutBuffer;
    
    if ($currentTimeStamp > $maxCheckoutTime) {
        echo "⚠️ Checkout window has expired (shift end + 30 minutes buffer)\n";
        echo "Maximum checkout time was: " . date('H:i', $maxCheckoutTime) . "\n";
    } else {
        $remainingTime = $maxCheckoutTime - $currentTimeStamp;
        echo "✅ Checkout window is open for " . round($remainingTime / 60) . " more minutes\n";
    }
    
} else {
    echo "User Aji not found\n";
}