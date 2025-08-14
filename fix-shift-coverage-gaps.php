<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\ShiftTemplate;
use App\Models\JadwalJaga;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIXING SHIFT COVERAGE GAPS ===\n\n";

try {
    $yaya = User::where('name', 'like', '%yaya%')->first();
    
    echo "=== 1. CREATE MISSING SHIFT TEMPLATES ===\n";
    
    // Check if shift templates already exist
    $earlyMorning = ShiftTemplate::where('nama_shift', 'Early Morning')->first();
    $extendedEvening = ShiftTemplate::where('nama_shift', 'Extended Evening')->first();
    
    if (!$earlyMorning) {
        $earlyMorning = ShiftTemplate::create([
            'nama_shift' => 'Early Morning',
            'jam_masuk' => '06:00',
            'jam_pulang' => '11:00',
            'durasi_jam' => 5,
            'warna' => '#10B981',
            'break_duration_minutes' => 30,
            'is_break_flexible' => true,
            'keterangan' => 'Early morning shift to cover 06:00-11:00 attendances'
        ]);
        echo "✅ Created Early Morning shift: 06:00-11:00 (ID: {$earlyMorning->id})\n";
    } else {
        echo "ℹ️ Early Morning shift already exists (ID: {$earlyMorning->id})\n";
    }
    
    if (!$extendedEvening) {
        $extendedEvening = ShiftTemplate::create([
            'nama_shift' => 'Extended Evening',
            'jam_masuk' => '17:00',
            'jam_pulang' => '22:15',
            'durasi_jam' => 5.25,
            'warna' => '#8B5CF6',
            'break_duration_minutes' => 30,
            'is_break_flexible' => true,
            'keterangan' => 'Extended evening shift to cover 17:00-22:15 attendance gap'
        ]);
        echo "✅ Created Extended Evening shift: 17:00-22:15 (ID: {$extendedEvening->id})\n";
    } else {
        echo "ℹ️ Extended Evening shift already exists (ID: {$extendedEvening->id})\n";
    }
    
    echo "\n=== 2. CREATE MISSING JADWAL JAGA FOR COVERAGE GAPS ===\n";
    
    // Dates that need better shift coverage
    $problematicDates = [
        '2025-08-11' => [
            'Extended Evening' => $extendedEvening->id // Cover 18:35-21:41 gap
        ],
        '2025-08-12' => [
            'Early Morning' => $earlyMorning->id // Cover 06:30 early attendance
        ]
    ];
    
    foreach ($problematicDates as $date => $shifts) {
        echo "📅 Adding shifts for {$date}:\n";
        
        foreach ($shifts as $shiftName => $shiftTemplateId) {
            // Check if jadwal already exists
            $existingJadwal = JadwalJaga::where('pegawai_id', $yaya->id)
                ->whereDate('tanggal_jaga', $date)
                ->where('shift_template_id', $shiftTemplateId)
                ->first();
            
            if (!$existingJadwal) {
                $jadwal = JadwalJaga::create([
                    'pegawai_id' => $yaya->id,
                    'tanggal_jaga' => $date,
                    'shift_template_id' => $shiftTemplateId,
                    'unit_kerja' => 'Dokter Jaga',
                    'peran' => 'Dokter',
                    'status_jaga' => 'Aktif',
                    'keterangan' => 'Auto-created to fix shift coverage gap'
                ]);
                echo "  ✅ Created {$shiftName} jadwal (ID: {$jadwal->id})\n";
            } else {
                echo "  ℹ️ {$shiftName} jadwal already exists (ID: {$existingJadwal->id})\n";
            }
        }
    }
    
    echo "\n=== 3. LINK ORPHANED ATTENDANCE RECORDS ===\n";
    
    // Get orphaned attendance records
    $orphanedAttendances = \App\Models\Attendance::where('user_id', $yaya->id)
        ->whereYear('date', 2025)
        ->whereMonth('date', 8)
        ->whereNull('jadwal_jaga_id')
        ->with('user')
        ->get();
    
    echo "🔗 Linking " . $orphanedAttendances->count() . " orphaned attendance records:\n";
    
    foreach ($orphanedAttendances as $attendance) {
        if (!$attendance->time_in) continue;
        
        // Find best matching jadwal for this attendance
        $bestJadwal = JadwalJaga::where('pegawai_id', $attendance->user_id)
            ->whereDate('tanggal_jaga', $attendance->date)
            ->with('shiftTemplate')
            ->get()
            ->filter(function($jadwal) use ($attendance) {
                if (!$jadwal->shiftTemplate) return false;
                
                // Calculate if this shift is a good match
                $attendanceTime = \Carbon\Carbon::parse($attendance->time_in);
                $shiftStart = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_masuk);
                $shiftEnd = \Carbon\Carbon::parse($jadwal->shiftTemplate->jam_pulang);
                
                if ($shiftEnd->lt($shiftStart)) {
                    $shiftEnd->addDay();
                }
                
                $attendanceMinutes = $attendanceTime->hour * 60 + $attendanceTime->minute;
                $shiftStartMinutes = $shiftStart->hour * 60 + $shiftStart->minute;
                $shiftEndMinutes = $shiftEnd->hour * 60 + $shiftEnd->minute;
                
                if ($shiftEndMinutes < $shiftStartMinutes) {
                    $shiftEndMinutes += 24 * 60;
                }
                
                // Accept if within 60-minute tolerance window
                $tolerance = 60; // minutes
                return ($attendanceMinutes >= ($shiftStartMinutes - $tolerance) && 
                        $attendanceMinutes <= ($shiftEndMinutes + $tolerance));
            })
            ->first();
        
        if ($bestJadwal) {
            $attendance->update(['jadwal_jaga_id' => $bestJadwal->id]);
            echo "  ✅ Linked attendance {$attendance->id} ({$attendance->date->format('Y-m-d')} {$attendance->time_in->format('H:i')}) → {$bestJadwal->shiftTemplate->nama_shift}\n";
        } else {
            echo "  ⚠️ No suitable jadwal found for attendance {$attendance->id} ({$attendance->date->format('Y-m-d')} {$attendance->time_in->format('H:i')})\n";
        }
    }
    
    echo "\n=== 4. VERIFICATION ===\n";
    
    // Re-run analysis to see improvements
    $afterFix = \App\Models\Attendance::where('user_id', $yaya->id)
        ->whereYear('date', 2025)
        ->whereMonth('date', 8)
        ->get();
    
    $withJadwalAfter = $afterFix->whereNotNull('jadwal_jaga_id')->count();
    $orphanedAfter = $afterFix->whereNull('jadwal_jaga_id')->count();
    
    echo "📊 Results After Fix:\n";
    echo "  - With jadwal_jaga_id: {$withJadwalAfter} (" . round($withJadwalAfter/$afterFix->count()*100, 1) . "%)\n";
    echo "  - Still orphaned: {$orphanedAfter} (" . round($orphanedAfter/$afterFix->count()*100, 1) . "%)\n";
    echo "  - Improvement: " . ($withJadwalAfter - 6) . " records linked\n\n";
    
    echo "✅ Shift coverage gaps analysis and fixes completed!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📋 Trace: " . $e->getTraceAsString() . "\n";
}