<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== FIXING RINDANG'S SCHEDULE (PROPER WAY) ===\n\n";

$today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
$rindang_id = 14;

try {
    // First, check available shifts
    $shifts = DB::select("SELECT * FROM shifts WHERE is_active = 1");
    
    if (empty($shifts)) {
        // Create a default shift if none exist
        echo "No shifts found. Creating default shift...\n";
        DB::insert(
            "INSERT INTO shifts (name, start_time, end_time, description, is_active, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            ['Shift Pagi', '08:00:00', '16:00:00', 'Morning shift for doctors', 1, now(), now()]
        );
        $shifts = DB::select("SELECT * FROM shifts WHERE is_active = 1");
    }
    
    echo "Available shifts:\n";
    foreach ($shifts as $shift) {
        echo "- ID: {$shift->id}, Name: {$shift->name}, Time: {$shift->start_time} - {$shift->end_time}\n";
    }
    
    // Check if Rindang already has a schedule for today
    $existing = DB::select(
        "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time 
         FROM schedules s 
         LEFT JOIN shifts sh ON s.shift_id = sh.id
         WHERE s.user_id = ? AND s.date = ?",
        [$rindang_id, $today]
    );
    
    if (!empty($existing)) {
        echo "\n✅ Schedule already exists for Rindang today!\n";
        echo "Date: {$existing[0]->date}\n";
        echo "Shift: {$existing[0]->shift_name}\n";
        echo "Time: {$existing[0]->start_time} - {$existing[0]->end_time}\n";
        echo "Day off: " . ($existing[0]->is_day_off ? 'Yes' : 'No') . "\n";
    } else {
        // Create schedule for Rindang using the first available shift
        $shift_id = $shifts[0]->id;
        
        DB::insert(
            "INSERT INTO schedules (user_id, shift_id, date, is_day_off, notes, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $rindang_id,
                $shift_id,
                $today,
                0, // not a day off
                'Schedule created for check-in',
                now(),
                now()
            ]
        );
        
        echo "\n✅ SCHEDULE CREATED SUCCESSFULLY!\n";
        echo "User ID: 14 (dr Rindang)\n";
        echo "Date: $today\n";
        echo "Shift: {$shifts[0]->name}\n";
        echo "Time: {$shifts[0]->start_time} - {$shifts[0]->end_time}\n";
    }
    
    // Verify the schedule
    $verify = DB::select(
        "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time, u.name as user_name
         FROM schedules s 
         JOIN shifts sh ON s.shift_id = sh.id
         JOIN users u ON s.user_id = u.id
         WHERE s.user_id = ? AND s.date = ?",
        [$rindang_id, $today]
    );
    
    if (!empty($verify)) {
        echo "\n✓ VERIFICATION SUCCESS\n";
        echo "Doctor: {$verify[0]->user_name}\n";
        echo "Schedule confirmed for: {$verify[0]->date}\n";
        echo "Shift: {$verify[0]->shift_name} ({$verify[0]->start_time} - {$verify[0]->end_time})\n";
        
        // Check attendance
        $attendance = DB::select(
            "SELECT * FROM dokter_presensis WHERE dokter_id = ? AND DATE(tanggal) = ?",
            [$rindang_id, $today]
        );
        
        if (empty($attendance)) {
            echo "\n🎉 RINDANG CAN NOW CHECK IN!\n";
            echo "Go to: /dokter/mobile-app\n";
        } else {
            echo "\nAttendance status:\n";
            echo "- Check In: " . ($attendance[0]->check_in ?? 'Not yet') . "\n";
            echo "- Check Out: " . ($attendance[0]->check_out ?? 'Not yet') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Check if it's a unique constraint violation
    if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
        echo "\nNote: A schedule already exists for this user and date.\n";
        
        // Show the existing schedule
        $existing = DB::select(
            "SELECT s.*, sh.name as shift_name, sh.start_time, sh.end_time 
             FROM schedules s 
             LEFT JOIN shifts sh ON s.shift_id = sh.id
             WHERE s.user_id = ? AND s.date = ?",
            [$rindang_id, $today]
        );
        
        if (!empty($existing)) {
            echo "Existing schedule:\n";
            echo "- Shift: " . ($existing[0]->shift_name ?? 'No shift') . "\n";
            echo "- Time: " . ($existing[0]->start_time ?? 'N/A') . " - " . ($existing[0]->end_time ?? 'N/A') . "\n";
        }
    }
}

echo "\n=== DONE ===\n";
?>