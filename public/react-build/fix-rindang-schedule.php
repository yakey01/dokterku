<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use App\Models\DokterPresensi;
use Carbon\Carbon;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Rindang Schedule</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🗓️ Fix Schedule for Rindang</h1>";

// Find Rindang
$rindang = User::where('name', 'like', '%rindang%')->first();

if (!$rindang) {
    echo "<div class='error'>❌ User Rindang not found!</div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='info'>
    <h3>User Information:</h3>
    <p><strong>Name:</strong> {$rindang->name}</p>
    <p><strong>ID:</strong> {$rindang->id}</p>
    <p><strong>Role:</strong> {$rindang->role->name}</p>
</div>";

// Check if doctor role
if ($rindang->role->name !== 'dokter') {
    echo "<div class='error'>❌ Rindang is not a doctor (role: {$rindang->role->name})</div>";
    echo "</div></body></html>";
    exit;
}

// Get today's date
$today = Carbon::now('Asia/Jakarta');
$todayDate = $today->format('Y-m-d');
$dayName = $today->locale('id')->dayName;

echo "<div class='info'>
    <h3>Current Date:</h3>
    <p><strong>Date:</strong> {$todayDate}</p>
    <p><strong>Day:</strong> {$dayName}</p>
    <p><strong>Time:</strong> {$today->format('H:i:s')} WIB</p>
</div>";

// Check existing jadwal for today
$existingJadwal = JadwalJaga::where('dokter_id', $rindang->id)
    ->whereDate('tanggal', $todayDate)
    ->first();

if ($existingJadwal) {
    echo "<div class='success'>
        <h3>✅ Schedule Already Exists for Today!</h3>";
    
    if ($existingJadwal->shift_template) {
        echo "<p><strong>Shift:</strong> {$existingJadwal->shift_template->nama}</p>
        <p><strong>Time:</strong> {$existingJadwal->shift_template->jam_masuk} - {$existingJadwal->shift_template->jam_pulang}</p>";
    }
    
    echo "<p><strong>Status:</strong> {$existingJadwal->status}</p>
    </div>";
    
    // Check attendance
    $attendance = DokterPresensi::where('dokter_id', $rindang->id)
        ->whereDate('tanggal', $todayDate)
        ->first();
    
    if ($attendance) {
        echo "<div class='info'>
            <h3>Attendance Status:</h3>
            <p><strong>Check In:</strong> " . ($attendance->check_in ?? 'Not yet') . "</p>
            <p><strong>Check Out:</strong> " . ($attendance->check_out ?? 'Not yet') . "</p>
        </div>";
    } else {
        echo "<div class='info'>
            <h3>Attendance Status:</h3>
            <p>No attendance record yet. Rindang can check in now!</p>
        </div>";
    }
} else {
    echo "<div class='error'>
        <h3>⚠️ No Schedule Found for Today</h3>
        <p>Creating new schedule...</p>
    </div>";
    
    // Get available shift templates
    $shifts = ShiftTemplate::all();
    
    if ($shifts->isEmpty()) {
        echo "<div class='error'>❌ No shift templates available!</div>";
        
        // Create default shift template
        $defaultShift = new ShiftTemplate();
        $defaultShift->nama = 'Shift Pagi';
        $defaultShift->jam_masuk = '08:00:00';
        $defaultShift->jam_pulang = '16:00:00';
        $defaultShift->save();
        
        echo "<div class='success'>✅ Created default shift template (08:00 - 16:00)</div>";
        $shifts = ShiftTemplate::all();
    }
    
    echo "<div class='info'>
        <h3>Available Shift Templates:</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($shifts as $shift) {
        echo "<tr>
            <td>{$shift->id}</td>
            <td>{$shift->nama}</td>
            <td>{$shift->jam_masuk}</td>
            <td>{$shift->jam_pulang}</td>
        </tr>";
    }
    
    echo "</tbody></table></div>";
    
    // Create jadwal with first available shift
    $firstShift = $shifts->first();
    
    $newJadwal = new JadwalJaga();
    $newJadwal->dokter_id = $rindang->id;
    $newJadwal->tanggal = $todayDate;
    $newJadwal->shift_template_id = $firstShift->id;
    $newJadwal->status = 'scheduled';
    $newJadwal->save();
    
    echo "<div class='success'>
        <h3>✅ Schedule Created Successfully!</h3>
        <p><strong>Doctor:</strong> {$rindang->name}</p>
        <p><strong>Date:</strong> {$todayDate}</p>
        <p><strong>Shift:</strong> {$firstShift->nama}</p>
        <p><strong>Time:</strong> {$firstShift->jam_masuk} - {$firstShift->jam_pulang}</p>
        <p><strong>Status:</strong> scheduled</p>
        <br>
        <p><strong>🎉 Rindang can now check in!</strong></p>
    </div>";
}

// Show recent schedules
$recentSchedules = JadwalJaga::where('dokter_id', $rindang->id)
    ->orderBy('tanggal', 'desc')
    ->limit(7)
    ->get();

echo "<div class='info'>
    <h3>Recent Schedules (Last 7):</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Shift</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";

foreach ($recentSchedules as $schedule) {
    $date = Carbon::parse($schedule->tanggal);
    $isToday = $date->format('Y-m-d') === $todayDate;
    $rowStyle = $isToday ? "style='background: #ffeaa7;'" : "";
    
    echo "<tr {$rowStyle}>
        <td>{$date->format('Y-m-d')}" . ($isToday ? " <strong>(TODAY)</strong>" : "") . "</td>
        <td>{$date->locale('id')->dayName}</td>
        <td>" . ($schedule->shift_template ? $schedule->shift_template->nama : 'No shift') . "</td>
        <td>" . ($schedule->shift_template ? $schedule->shift_template->jam_masuk . ' - ' . $schedule->shift_template->jam_pulang : '-') . "</td>
        <td>{$schedule->status}</td>
    </tr>";
}

echo "</tbody></table></div>";

// Create schedule for next 7 days button
echo "<div style='margin-top: 20px;'>
    <h3>Quick Actions:</h3>
    <a href='/dokter/mobile-app' class='btn btn-success'>Go to Dokter Mobile App</a>
    <a href='/admin/jadwal-jagas' class='btn'>Manage Schedules (Admin)</a>
</div>";

echo "</div></body></html>";
?>