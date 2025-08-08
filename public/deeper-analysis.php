<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\JadwalJaga;
use Carbon\Carbon;

echo "<h2>Deeper Analysis - Why Tes 4 Still Not Appearing</h2>";

// Start session
session_start();

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
    
    // Test API with different month parameters
    echo "<h3>API Testing with Different Parameters:</h3>";
    
    // Test 1: Default API call (current month)
    echo "<h4>Test 1: Default API Call (Current Month)</h4>";
    $request1 = \Illuminate\Http\Request::create('/api/v2/dashboards/dokter/jadwal-jaga', 'GET');
    $request1->headers->set('Accept', 'application/json');
    $request1->headers->set('Content-Type', 'application/json');
    $request1->headers->set('X-CSRF-TOKEN', csrf_token());
    $request1->headers->set('X-Requested-With', 'XMLHttpRequest');
    $request1->setLaravelSession(app('session.store'));
    
    $response1 = app()->handle($request1);
    $data1 = json_decode($response1->getContent(), true);
    
    echo "<p>Status: " . $response1->getStatusCode() . "</p>";
    echo "<p>Success: " . ($data1['success'] ? 'true' : 'false') . "</p>";
    
    if (isset($data1['data'])) {
        $calendarEvents1 = $data1['data']['calendar_events'] ?? [];
        $weeklySchedule1 = $data1['data']['weekly_schedule'] ?? [];
        
        echo "<p>Calendar Events: " . count($calendarEvents1) . "</p>";
        echo "<p>Weekly Schedule: " . count($weeklySchedule1) . "</p>";
        
        // Check for tes 4
        $tes4InCalendar1 = array_filter($calendarEvents1, function($event) {
            return stripos($event['title'] ?? '', 'tes 4') !== false || 
                   stripos($event['shift_info']['nama_shift'] ?? '', 'tes 4') !== false;
        });
        
        $tes4InWeekly1 = array_filter($weeklySchedule1, function($schedule) {
            return stripos($schedule['shift_template']['nama_shift'] ?? '', 'tes 4') !== false;
        });
        
        echo "<p>Tes 4 in Calendar Events: " . count($tes4InCalendar1) . "</p>";
        echo "<p>Tes 4 in Weekly Schedule: " . count($tes4InWeekly1) . "</p>";
    }
    
    // Test 2: API call for August 2025 (where tes 4 exists)
    echo "<h4>Test 2: API Call for August 2025 (Month 8)</h4>";
    $request2 = \Illuminate\Http\Request::create('/api/v2/dashboards/dokter/jadwal-jaga?month=8&year=2025', 'GET');
    $request2->headers->set('Accept', 'application/json');
    $request2->headers->set('Content-Type', 'application/json');
    $request2->headers->set('X-CSRF-TOKEN', csrf_token());
    $request2->headers->set('X-Requested-With', 'XMLHttpRequest');
    $request2->setLaravelSession(app('session.store'));
    
    $response2 = app()->handle($request2);
    $data2 = json_decode($response2->getContent(), true);
    
    echo "<p>Status: " . $response2->getStatusCode() . "</p>";
    echo "<p>Success: " . ($data2['success'] ? 'true' : 'false') . "</p>";
    
    if (isset($data2['data'])) {
        $calendarEvents2 = $data2['data']['calendar_events'] ?? [];
        $weeklySchedule2 = $data2['data']['weekly_schedule'] ?? [];
        
        echo "<p>Calendar Events: " . count($calendarEvents2) . "</p>";
        echo "<p>Weekly Schedule: " . count($weeklySchedule2) . "</p>";
        
        // Check for tes 4
        $tes4InCalendar2 = array_filter($calendarEvents2, function($event) {
            return stripos($event['title'] ?? '', 'tes 4') !== false || 
                   stripos($event['shift_info']['nama_shift'] ?? '', 'tes 4') !== false;
        });
        
        $tes4InWeekly2 = array_filter($weeklySchedule2, function($schedule) {
            return stripos($schedule['shift_template']['nama_shift'] ?? '', 'tes 4') !== false;
        });
        
        echo "<p>Tes 4 in Calendar Events: " . count($tes4InCalendar2) . "</p>";
        echo "<p>Tes 4 in Weekly Schedule: " . count($tes4InWeekly2) . "</p>";
        
        if (count($tes4InCalendar2) > 0) {
            echo "<h5>Tes 4 Calendar Events Found:</h5>";
            echo "<pre>" . json_encode($tes4InCalendar2, JSON_PRETTY_PRINT) . "</pre>";
        }
        
        if (count($tes4InWeekly2) > 0) {
            echo "<h5>Tes 4 Weekly Schedule Found:</h5>";
            echo "<pre>" . json_encode($tes4InWeekly2, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
    
    // Check if frontend is requesting the right month
    echo "<h3>Frontend Month Request Analysis:</h3>";
    echo "<p>Frontend might be requesting current month ($currentMonth) but tes 4 is in August (month 8)</p>";
    echo "<p>If current month is not August, tes 4 won't appear in frontend</p>";
    
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
    
} else {
    echo "<p>❌ Failed to login</p>";
}

// Logout
Auth::logout();
?>
