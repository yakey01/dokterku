<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\User;
use App\Models\Dokter;
use App\Models\DokterPresensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "=== CREATING ATTENDANCE HISTORY FOR DOCTORS ===\n\n";

// First, let's see what users we have
$users = DB::table('users')->get();
echo "Total users in database: " . count($users) . "\n";

// Check for dokter role users
$dokterUsers = [];
foreach ($users as $user) {
    $nameLower = strtolower($user->name);
    $roleValue = isset($user->role) ? $user->role : 'unknown';
    
    if (str_contains($nameLower, 'yaya') || 
        str_contains($nameLower, 'rindang') || 
        str_contains($nameLower, 'aji') ||
        $roleValue === 'dokter') {
        $dokterUsers[] = $user;
        echo "Found potential doctor: {$user->name} (email: {$user->email}, role: {$roleValue})\n";
    }
}

if (empty($dokterUsers)) {
    echo "\n⚠️ No doctor users found. Let's check the Dokter table directly...\n";
    
    // Check dokters table
    $dokters = DB::table('dokters')->get();
    echo "Found " . count($dokters) . " records in dokters table\n";
    
    foreach ($dokters as $dokter) {
        echo "Dokter: {$dokter->nama} (ID: {$dokter->id})\n";
        
        // Create attendance history for this dokter
        echo "Creating attendance history for {$dokter->nama}...\n";
        
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);
            
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }
            
            // Check if record already exists
            $existing = DB::table('dokter_presensis')
                         ->where('dokter_id', $dokter->id)
                         ->where('tanggal', $date->format('Y-m-d'))
                         ->first();
            
            if ($existing) {
                echo "  - {$date->format('Y-m-d')}: Already exists\n";
                continue;
            }
            
            // Random times
            $checkInHour = rand(7, 8);
            $checkInMinute = rand(0, 59);
            $checkOutHour = rand(16, 17);
            $checkOutMinute = rand(0, 59);
            
            DB::table('dokter_presensis')->insert([
                'dokter_id' => $dokter->id,
                'tanggal' => $date->format('Y-m-d'),
                'jam_masuk' => sprintf('%02d:%02d:00', $checkInHour, $checkInMinute),
                'jam_pulang' => sprintf('%02d:%02d:00', $checkOutHour, $checkOutMinute),
                'created_at' => $date,
                'updated_at' => $date
            ]);
            
            echo "  ✅ {$date->format('Y-m-d')}: Check-in {$checkInHour}:{$checkInMinute}, Check-out {$checkOutHour}:{$checkOutMinute}\n";
        }
    }
} else {
    // Create attendance for found users
    foreach ($dokterUsers as $user) {
        echo "\nProcessing {$user->name}...\n";
        
        // Find or create dokter record
        $dokter = DB::table('dokters')->where('email', $user->email)->first();
        
        if (!$dokter) {
            // Try to find by name
            $dokter = DB::table('dokters')
                       ->where('nama', 'LIKE', '%' . explode(' ', $user->name)[0] . '%')
                       ->first();
        }
        
        if (!$dokter) {
            echo "Creating dokter record for {$user->name}...\n";
            $dokterId = DB::table('dokters')->insertGetId([
                'nama' => $user->name,
                'email' => $user->email,
                'no_str' => 'STR' . rand(1000, 9999),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $dokter = DB::table('dokters')->find($dokterId);
        }
        
        echo "Using dokter ID: {$dokter->id}\n";
        
        // Create attendance history
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);
            
            if ($date->isWeekend()) {
                continue;
            }
            
            $existing = DB::table('dokter_presensis')
                         ->where('dokter_id', $dokter->id)
                         ->where('tanggal', $date->format('Y-m-d'))
                         ->first();
            
            if ($existing) {
                echo "  - {$date->format('Y-m-d')}: Already exists\n";
                continue;
            }
            
            $checkInHour = rand(7, 8);
            $checkInMinute = rand(0, 59);
            $checkOutHour = rand(16, 17);
            $checkOutMinute = rand(0, 59);
            
            DB::table('dokter_presensis')->insert([
                'dokter_id' => $dokter->id,
                'tanggal' => $date->format('Y-m-d'),
                'jam_masuk' => sprintf('%02d:%02d:00', $checkInHour, $checkInMinute),
                'jam_pulang' => sprintf('%02d:%02d:00', $checkOutHour, $checkOutMinute),
                'created_at' => $date,
                'updated_at' => $date
            ]);
            
            echo "  ✅ {$date->format('Y-m-d')}: Check-in {$checkInHour}:{$checkInMinute}, Check-out {$checkOutHour}:{$checkOutMinute}\n";
        }
    }
}

echo "\n=== SUMMARY ===\n";
$totalRecords = DB::table('dokter_presensis')->count();
echo "Total attendance records in database: {$totalRecords}\n";

// Show recent records
$recent = DB::table('dokter_presensis')
           ->join('dokters', 'dokter_presensis.dokter_id', '=', 'dokters.id')
           ->select('dokters.nama', 'dokter_presensis.*')
           ->orderBy('tanggal', 'desc')
           ->limit(10)
           ->get();

echo "\nRecent attendance records:\n";
foreach ($recent as $record) {
    echo "  - {$record->nama}: {$record->tanggal} ({$record->jam_masuk} - {$record->jam_pulang})\n";
}

echo "\n✅ Attendance history created successfully!\n";
echo "You can now check the 'Riwayat' tab in the doctor dashboard.\n";