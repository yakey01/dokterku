<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Jadwal Shift</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Test Jadwal Shift System</h1>
    
    <div class="section">
        <h2>1. System Check</h2>
        <?php
        try {
            $shiftCount = \App\Models\ShiftTemplate::count();
            $dokterCount = \App\Models\Dokter::where('aktif', true)->count();
            $pegawaiCount = \App\Models\Pegawai::where('aktif', true)->count();
            $jadwalCount = \App\Models\JadwalJaga::count();
            
            echo "<p class='success'>✅ Database connection: OK</p>";
            echo "<p>📊 Shift Templates: {$shiftCount}</p>";
            echo "<p>👨‍⚕️ Active Dokter: {$dokterCount}</p>";
            echo "<p>👥 Active Pegawai: {$pegawaiCount}</p>";
            echo "<p>📅 Total Jadwal: {$jadwalCount}</p>";
        } catch (\Exception $e) {
            echo "<p class='error'>❌ Database Error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>2. Missing User Accounts</h2>
        <?php
        $missingDokter = \App\Models\Dokter::whereNull('user_id')->where('aktif', true)->get();
        $missingPegawai = \App\Models\Pegawai::whereNull('user_id')->where('aktif', true)->get();
        
        if ($missingDokter->count() > 0 || $missingPegawai->count() > 0) {
            echo "<p class='warning'>⚠️ Some staff don't have user accounts:</p>";
            
            if ($missingDokter->count() > 0) {
                echo "<h4>Dokter without user accounts:</h4><ul>";
                foreach ($missingDokter as $dokter) {
                    echo "<li>{$dokter->nama_lengkap} (ID: {$dokter->id})</li>";
                }
                echo "</ul>";
            }
            
            if ($missingPegawai->count() > 0) {
                echo "<h4>Pegawai without user accounts:</h4><ul>";
                foreach ($missingPegawai as $pegawai) {
                    echo "<li>{$pegawai->nama_lengkap} - {$pegawai->jenis_pegawai} (ID: {$pegawai->id})</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p class='success'>✅ All active staff have user accounts</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. Today's Schedule Validation</h2>
        <?php
        $today = \Carbon\Carbon::today('Asia/Jakarta');
        $currentTime = \Carbon\Carbon::now('Asia/Jakarta');
        $shifts = \App\Models\ShiftTemplate::all();
        
        echo "<p>Current time: " . $currentTime->format('H:i') . "</p>";
        echo "<h4>Shift availability for today:</h4>";
        echo "<table>";
        echo "<tr><th>Shift</th><th>Start Time</th><th>End Time</th><th>Status</th></tr>";
        
        foreach ($shifts as $shift) {
            $shiftStart = \Carbon\Carbon::parse($shift->jam_masuk);
            $todayShiftStart = $today->copy()->setHour($shiftStart->hour)->setMinute($shiftStart->minute);
            
            $status = $currentTime->greaterThan($todayShiftStart) 
                ? "<span class='error'>❌ Already started</span>" 
                : "<span class='success'>✅ Can schedule</span>";
                
            echo "<tr>";
            echo "<td>{$shift->nama_shift}</td>";
            echo "<td>{$shift->jam_masuk_format}</td>";
            echo "<td>{$shift->jam_pulang_format}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
        ?>
    </div>
    
    <div class="section">
        <h2>4. Test Create Schedule</h2>
        <form method="POST" action="/test-create-jadwal">
            <?php echo csrf_field(); ?>
            <p>
                <label>Date: <input type="date" name="tanggal_jaga" value="<?php echo now()->addDay()->format('Y-m-d'); ?>" min="<?php echo now()->format('Y-m-d'); ?>"></label>
            </p>
            <p>
                <label>Shift: 
                    <select name="shift_template_id">
                        <?php foreach (\App\Models\ShiftTemplate::all() as $shift): ?>
                            <option value="<?php echo $shift->id; ?>"><?php echo $shift->nama_shift; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </p>
            <p>
                <label>Staff Type:
                    <select name="jenis_tugas">
                        <option value="dokter_jaga">Dokter Jaga</option>
                        <option value="pendaftaran">Pendaftaran</option>
                        <option value="pelayanan">Pelayanan</option>
                    </select>
                </label>
            </p>
            <p><em>Note: This is just a test form. Use the admin panel for actual scheduling.</em></p>
        </form>
    </div>
    
    <div class="section">
        <h2>5. Quick Links</h2>
        <ul>
            <li><a href="/admin/jadwal-jagas" target="_blank">Go to Jadwal Jaga Admin Panel</a></li>
            <li><a href="/admin" target="_blank">Go to Admin Dashboard</a></li>
        </ul>
    </div>
</body>
</html>

<?php
$kernel->terminate($request, $response);
?>