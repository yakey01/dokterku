<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\JadwalJaga;
use Carbon\Carbon;

echo "<h2>Deeper Analysis - Why Tes 4 Still Not Appearing</h2>";

// Find Yaya (user ID 13)
$yaya = User::find(13);

if (!$yaya) {
    echo "<p>❌ User Yaya not found</p>";
    exit;
}

echo "<p>Found user: " . $yaya->name . " (ID: " . $yaya->id . ")</p>";

// Login as Yaya
Auth::login($yaya);

if (Auth::check()) {
    echo "<p>✅ Successfully logged in as Yaya</p>";
    
    // Check current date and month
    $currentDate = Carbon::now();
    $currentMonth = $currentDate->month;
    $currentYear = $currentDate->year;
    
    echo "<h3>Current Date Analysis:</h3>";
    echo "<p>Current Date: " . $currentDate->format('Y-m-d H:i:s') . "</p>";
    echo "<p>Current Month: " . $currentMonth . " (Year: " . $currentYear . ")</p>";
    
    // Get all jadwal jaga for Yaya
    $allJadwal = JadwalJaga::where('pegawai_id', 13)->get();
    echo "<p>Total Jadwal Jaga for Yaya: " . $allJadwal->count() . "</p>";
    
    // Check jadwal by month
    echo "<h3>Jadwal by Month Analysis:</h3>";
    $jadwalByMonth = [];
    
    foreach ($allJadwal as $jadwal) {
        if ($jadwal->tanggal_jaga) {
            $month = Carbon::parse($jadwal->tanggal_jaga)->month;
            $year = Carbon::parse($jadwal->tanggal_jaga)->year;
            $key = "$year-$month";
            
            if (!isset($jadwalByMonth[$key])) {
                $jadwalByMonth[$key] = [];
            }
            $jadwalByMonth[$key][] = $jadwal;
        }
    }
    
    foreach ($jadwalByMonth as $monthKey => $jadwals) {
        echo "<h4>Month: $monthKey (" . count($jadwals) . " schedules)</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Tanggal Jaga</th><th>Shift Template ID</th><th>Nama Shift</th><th>Status</th></tr>";
        
        foreach ($jadwals as $jadwal) {
            $shiftTemplate = $jadwal->shiftTemplate;
            $shiftName = $shiftTemplate ? $shiftTemplate->nama_shift : 'Unknown';
            
            echo "<tr>";
            echo "<td>" . $jadwal->id . "</td>";
            echo "<td>" . ($jadwal->tanggal_jaga ?? 'null') . "</td>";
            echo "<td>" . ($jadwal->shift_template_id ?? 'null') . "</td>";
            echo "<td>" . $shiftName . "</td>";
            echo "<td>" . ($jadwal->status_jaga ?? 'null') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check current month jadwal specifically
    echo "<h3>Current Month Jadwal (Month $currentMonth):</h3>";
    $currentMonthJadwal = JadwalJaga::where('pegawai_id', 13)
        ->whereMonth('tanggal_jaga', $currentMonth)
        ->whereYear('tanggal_jaga', $currentYear)
        ->with(['shiftTemplate'])
        ->get();
    
    echo "<p>Current month jadwal count: " . $currentMonthJadwal->count() . "</p>";
    
    if ($currentMonthJadwal->count() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Tanggal Jaga</th><th>Shift Template</th><th>Nama Shift</th><th>Jam Masuk</th><th>Jam Pulang</th></tr>";
        
        foreach ($currentMonthJadwal as $jadwal) {
            $shiftTemplate = $jadwal->shiftTemplate;
            echo "<tr>";
            echo "<td>" . $jadwal->id . "</td>";
            echo "<td>" . ($jadwal->tanggal_jaga ?? 'null') . "</td>";
            echo "<td>" . ($shiftTemplate ? $shiftTemplate->id : 'null') . "</td>";
            echo "<td>" . ($shiftTemplate ? $shiftTemplate->nama_shift : 'null') . "</td>";
            echo "<td>" . ($shiftTemplate ? $shiftTemplate->jam_masuk : 'null') . "</td>";
            echo "<td>" . ($shiftTemplate ? $shiftTemplate->jam_pulang : 'null') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check all months where tes 4 exists
    echo "<h3>Months with Tes 4:</h3>";
    $tes4Jadwal = JadwalJaga::where('pegawai_id', 13)
        ->whereHas('shiftTemplate', function($query) {
            $query->where('nama_shift', 'like', '%tes 4%');
        })
        ->get();
    
    if ($tes4Jadwal->count() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Tanggal Jaga</th><th>Month</th><th>Year</th><th>Nama Shift</th></tr>";
        
        foreach ($tes4Jadwal as $jadwal) {
            $tanggal = Carbon::parse($jadwal->tanggal_jaga);
            $shiftTemplate = $jadwal->shiftTemplate;
            
            echo "<tr>";
            echo "<td>" . $jadwal->id . "</td>";
            echo "<td>" . $jadwal->tanggal_jaga . "</td>";
            echo "<td>" . $tanggal->month . "</td>";
            echo "<td>" . $tanggal->year . "</td>";
            echo "<td>" . ($shiftTemplate ? $shiftTemplate->nama_shift : 'Unknown') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check if frontend is requesting the right month
    echo "<h3>Frontend Month Request Analysis:</h3>";
    echo "<p>Frontend might be requesting current month ($currentMonth) but tes 4 is in August (month 8)</p>";
    echo "<p>If current month is not August, tes 4 won't appear in frontend</p>";
    
} else {
    echo "<p>❌ Failed to login</p>";
}

// Logout
Auth::logout();
?>
