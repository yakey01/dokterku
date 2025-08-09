<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIXING RINDANG'S SCHEDULE ===\n\n";

// Direct SQL to create jadwal
$today = date('Y-m-d');
$dokter_id = 14; // Rindang's ID

try {
    // Check if jadwal exists
    $existing = DB::select("SELECT * FROM jadwal_jagas WHERE dokter_id = ? AND DATE(tanggal) = ?", [$dokter_id, $today]);
    
    if (!empty($existing)) {
        echo "✅ Jadwal already exists for today!\n";
        echo "Jadwal ID: " . $existing[0]->id . "\n";
        echo "Date: " . $existing[0]->tanggal . "\n";
    } else {
        // Create jadwal with direct SQL
        DB::insert("INSERT INTO jadwal_jagas (dokter_id, tanggal, shift_template_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)", 
            [$dokter_id, $today, 1, 'scheduled', now(), now()]
        );
        
        echo "✅ JADWAL CREATED SUCCESSFULLY!\n";
        echo "Doctor ID: 14 (Rindang)\n";
        echo "Date: $today\n";
        echo "Shift Template: 1\n";
        echo "Status: scheduled\n\n";
        echo "🎉 RINDANG CAN NOW CHECK IN!\n";
    }
    
    // Verify
    $verify = DB::select("SELECT * FROM jadwal_jagas WHERE dokter_id = ? AND DATE(tanggal) = ?", [$dokter_id, $today]);
    if (!empty($verify)) {
        echo "\n✓ Verification: Jadwal exists in database\n";
        
        // Check for attendance
        $attendance = DB::select("SELECT * FROM dokter_presensis WHERE dokter_id = ? AND DATE(tanggal) = ?", [$dokter_id, $today]);
        if (empty($attendance)) {
            echo "ℹ️ No attendance record yet - Rindang should check in now\n";
        } else {
            echo "ℹ️ Attendance record exists\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
?>