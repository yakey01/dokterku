<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== CREATING JADWAL FOR RINDANG ===\n\n";

$today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
$pegawai_id = 14; // Rindang's ID

try {
    // Check if jadwal exists
    $existing = DB::select(
        "SELECT * FROM jadwal_jagas WHERE pegawai_id = ? AND tanggal_jaga = ?", 
        [$pegawai_id, $today]
    );
    
    if (!empty($existing)) {
        echo "✅ Jadwal already exists for today!\n";
        echo "Jadwal ID: " . $existing[0]->id . "\n";
        echo "Date: " . $existing[0]->tanggal_jaga . "\n";
        echo "Status: " . $existing[0]->status_jaga . "\n";
        echo "Unit: " . $existing[0]->unit_kerja . "\n";
    } else {
        // Create jadwal with correct column names
        DB::insert(
            "INSERT INTO jadwal_jagas (pegawai_id, tanggal_jaga, shift_template_id, peran, status_jaga, unit_kerja, keterangan, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
            [
                $pegawai_id,           // pegawai_id
                $today,                // tanggal_jaga
                1,                     // shift_template_id (assuming ID 1 exists)
                'Dokter',              // peran
                'Aktif',               // status_jaga
                'Dokter Jaga',         // unit_kerja
                'Created for Rindang', // keterangan
                now(),                 // created_at
                now()                  // updated_at
            ]
        );
        
        echo "✅ JADWAL CREATED SUCCESSFULLY!\n";
        echo "Pegawai ID: 14 (dr Rindang)\n";
        echo "Date: $today\n";
        echo "Shift Template: 1\n";
        echo "Peran: Dokter\n";
        echo "Status: Aktif\n";
        echo "Unit Kerja: Dokter Jaga\n\n";
        echo "🎉 RINDANG CAN NOW CHECK IN!\n";
    }
    
    // Verify the jadwal was created
    $verify = DB::select(
        "SELECT j.*, s.jam_masuk, s.jam_pulang 
         FROM jadwal_jagas j 
         LEFT JOIN shift_templates s ON j.shift_template_id = s.id
         WHERE j.pegawai_id = ? AND j.tanggal_jaga = ?", 
        [$pegawai_id, $today]
    );
    
    if (!empty($verify)) {
        echo "\n✓ Verification: Jadwal exists in database\n";
        echo "Shift Time: " . ($verify[0]->jam_masuk ?? '08:00') . " - " . ($verify[0]->jam_pulang ?? '16:00') . "\n";
        
        // Check for attendance
        $attendance = DB::select(
            "SELECT * FROM dokter_presensis WHERE dokter_id = ? AND DATE(tanggal) = ?", 
            [$pegawai_id, $today]
        );
        
        if (empty($attendance)) {
            echo "\nℹ️ No attendance record yet\n";
            echo "👉 Rindang should now be able to check in through /dokter/mobile-app\n";
        } else {
            echo "\nℹ️ Attendance record exists:\n";
            echo "- Check In: " . ($attendance[0]->check_in ?? 'Not yet') . "\n";
            echo "- Check Out: " . ($attendance[0]->check_out ?? 'Not yet') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Try to provide helpful info
    if (strpos($e->getMessage(), 'shift_template_id') !== false) {
        echo "\nNote: Shift template with ID 1 might not exist. Checking available shifts...\n";
        $shifts = DB::select("SELECT * FROM shift_templates LIMIT 5");
        if (!empty($shifts)) {
            echo "Available shift templates:\n";
            foreach ($shifts as $shift) {
                echo "- ID: {$shift->id}\n";
            }
            echo "\nPlease update the script to use one of these IDs.\n";
        } else {
            echo "No shift templates found. Need to create one first.\n";
        }
    }
}

echo "\n=== DONE ===\n";
?>