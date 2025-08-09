<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use Carbon\Carbon;

echo "<!DOCTYPE html>
<html>
<head>
    <title>📅 Create Night Shift - Dokter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #efe; border-color: #cfc; color: #363; }
        .error { background: #fee; border-color: #fcc; color: #c33; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .button { background: #8b5cf6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #7c3aed; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>📅 Create Night Shift - Dokter</h1>
            <p>Membuat jadwal shift malam untuk Yaya agar bisa test check-in</p>
        </div>";

// Cek apakah ada request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_shift') {
        echo "<div class='section success'>
            <h2>🔄 Membuat Shift Malam...</h2>";
        
        try {
            $yayaUser = User::where('name', 'like', '%Yaya%')->first();
            
            if (!$yayaUser) {
                throw new Exception('User Yaya tidak ditemukan');
            }
            
            // Cek apakah sudah ada shift template malam
            $nightShiftTemplate = ShiftTemplate::where('nama_shift', 'like', '%malam%')
                ->orWhere('nama_shift', 'like', '%night%')
                ->orWhere('jam_masuk', '>=', '20:00')
                ->first();
            
            if (!$nightShiftTemplate) {
                // Buat shift template malam baru
                $nightShiftTemplate = ShiftTemplate::create([
                    'nama_shift' => 'Shift Malam Test',
                    'jam_masuk' => '22:00:00',
                    'jam_pulang' => '06:00:00',
                    'keterangan' => 'Shift malam untuk testing presensi'
                ]);
                echo "<p>✅ Shift template malam baru dibuat: {$nightShiftTemplate->nama_shift}</p>";
            } else {
                echo "<p>✅ Menggunakan shift template yang ada: {$nightShiftTemplate->nama_shift}</p>";
            }
            
            // Hapus jadwal lama hari ini (jika ada)
            JadwalJaga::where('pegawai_id', $yayaUser->id)
                ->whereDate('tanggal_jaga', today())
                ->delete();
            
            echo "<p>🗑️ Jadwal lama hari ini dihapus</p>";
            
            // Buat jadwal shift malam baru
            $newJadwal = JadwalJaga::create([
                'tanggal_jaga' => today(),
                'shift_template_id' => $nightShiftTemplate->id,
                'pegawai_id' => $yayaUser->id,
                'unit_kerja' => 'Dokter Jaga',
                'peran' => 'Dokter',
                'status_jaga' => 'Aktif',
                'keterangan' => 'Shift malam untuk testing presensi'
            ]);
            
            echo "<p>✅ Jadwal shift malam berhasil dibuat!</p>";
            echo "<p><strong>ID Jadwal:</strong> {$newJadwal->id}</p>";
            echo "<p><strong>Tanggal:</strong> {$newJadwal->tanggal_jaga}</p>";
            echo "<p><strong>Shift:</strong> {$nightShiftTemplate->nama_shift}</p>";
            echo "<p><strong>Jam:</strong> {$nightShiftTemplate->jam_masuk} - {$nightShiftTemplate->jam_pulang}</p>";
            echo "<p><strong>Status:</strong> {$newJadwal->status_jaga}</p>";
            
            echo "<div class='code'>
// Jadwal yang dibuat:
{
    'id': {$newJadwal->id},
    'tanggal_jaga': '{$newJadwal->tanggal_jaga}',
    'shift_template_id': {$nightShiftTemplate->id},
    'pegawai_id': {$yayaUser->id},
    'shift_name': '{$nightShiftTemplate->nama_shift}',
    'jam_masuk': '{$nightShiftTemplate->jam_masuk}',
    'jam_pulang': '{$nightShiftTemplate->jam_pulang}',
    'status_jaga': '{$newJadwal->status_jaga}'
}
            </div>";
            
            echo "<a href='test-buffer-fix.php' class='button'>🧪 Test Buffer Fix</a>";
            echo "<a href='test-mobile-app.php' class='button'>📱 Test Mobile App</a>";
            
        } catch (Exception $e) {
            echo "<div class='section error'>";
            echo "<h3>❌ Error:</h3>";
            echo "<p>{$e->getMessage()}</p>";
            echo "</div>";
        }
        
        echo "</div>";
    }
} else {
    // Form untuk membuat shift
    echo "<div class='section info'>
        <h2>📋 Form Pembuatan Shift Malam</h2>";
    
    $yayaUser = User::where('name', 'like', '%Yaya%')->first();
    $shiftTemplates = ShiftTemplate::all();
    
    if ($yayaUser) {
        echo "<p><strong>User:</strong> {$yayaUser->name} (ID: {$yayaUser->id})</p>";
        echo "<p><strong>Tanggal Hari Ini:</strong> " . today()->format('Y-m-d') . "</p>";
        
        echo "<form method='POST'>
            <input type='hidden' name='action' value='create_shift'>
            
            <div class='form-group'>
                <label>Pilih Shift Template:</label>
                <select name='shift_template_id'>";
        
        foreach ($shiftTemplates as $template) {
            $selected = ($template->jam_masuk >= '20:00' || $template->jam_masuk <= '06:00') ? 'selected' : '';
            echo "<option value='{$template->id}' {$selected}>{$template->nama_shift} ({$template->jam_masuk} - {$template->jam_pulang})</option>";
        }
        
        echo "</select>
            </div>
            
            <div class='form-group'>
                <label>Atau buat shift template baru:</label>
                <input type='text' name='new_shift_name' placeholder='Nama Shift (misal: Shift Malam Test)' value='Shift Malam Test'>
            </div>
            
            <div class='form-group'>
                <label>Jam Masuk:</label>
                <input type='time' name='jam_masuk' value='22:00'>
            </div>
            
            <div class='form-group'>
                <label>Jam Pulang:</label>
                <input type='time' name='jam_pulang' value='06:00'>
            </div>
            
            <button type='submit' class='button'>📅 Buat Shift Malam</button>
        </form>";
        
    } else {
        echo "<p class='error'>❌ User Yaya tidak ditemukan!</p>";
    }
    
    echo "</div>";
    
    // Informasi shift yang ada
    echo "<div class='section info'>
        <h2>📊 Shift Templates yang Tersedia</h2>";
    
    if ($shiftTemplates->count() > 0) {
        echo "<table style='width: 100%; border-collapse: collapse;'>
            <tr style='background: #f8f9fa;'>
                <th style='border: 1px solid #ddd; padding: 8px;'>ID</th>
                <th style='border: 1px solid #ddd; padding: 8px;'>Nama Shift</th>
                <th style='border: 1px solid #ddd; padding: 8px;'>Jam Masuk</th>
                <th style='border: 1px solid #ddd; padding: 8px;'>Jam Pulang</th>
                <th style='border: 1px solid #ddd; padding: 8px;'>Tipe</th>
            </tr>";
        
        foreach ($shiftTemplates as $template) {
            $jamMasuk = Carbon::parse($template->jam_masuk);
            $jamPulang = Carbon::parse($template->jam_pulang);
            $jamMasukHour = (int) $jamMasuk->format('H');
            $jamPulangHour = (int) $jamPulang->format('H');
            
            $tipe = 'Normal';
            if ($jamMasukHour >= 22 || $jamMasukHour <= 6 || $jamPulangHour >= 22 || $jamPulangHour <= 6) {
                $tipe = 'Malam';
            } elseif ($jamMasukHour >= 6 && $jamMasukHour < 12) {
                $tipe = 'Pagi';
            } elseif ($jamMasukHour >= 12 && $jamMasukHour < 18) {
                $tipe = 'Siang';
            } elseif ($jamMasukHour >= 18 && $jamMasukHour < 22) {
                $tipe = 'Sore';
            }
            
            echo "<tr>
                <td style='border: 1px solid #ddd; padding: 8px;'>{$template->id}</td>
                <td style='border: 1px solid #ddd; padding: 8px;'>{$template->nama_shift}</td>
                <td style='border: 1px solid #ddd; padding: 8px;'>{$template->jam_masuk}</td>
                <td style='border: 1px solid #ddd; padding: 8px;'>{$template->jam_pulang}</td>
                <td style='border: 1px solid #ddd; padding: 8px;'>{$tipe}</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Tidak ada shift template yang tersedia!</p>";
    }
    
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
