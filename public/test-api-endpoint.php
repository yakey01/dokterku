<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use Carbon\Carbon;

echo "<!DOCTYPE html>
<html>
<head>
    <title>🧪 Test API Endpoint - Presensi Dokter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #efe; border-color: #cfc; color: #363; }
        .error { background: #fee; border-color: #fcc; color: #c33; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .button { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #059669; }
        .response { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0; }
        .response-success { border-color: #28a745; background: #d4edda; }
        .response-error { border-color: #dc3545; background: #f8d7da; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🧪 Test API Endpoint - Presensi Dokter</h1>
            <p>Testing API endpoint check-in secara langsung</p>
        </div>";

// Cek apakah ada request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_checkin') {
        echo "<div class='section info'>
            <h2>🔄 Testing API Check-in...</h2>";
        
        try {
            $yayaUser = User::where('name', 'like', '%Yaya%')->first();
            
            if (!$yayaUser) {
                throw new Exception('User Yaya tidak ditemukan');
            }
            
            // Login sebagai Yaya
            Auth::login($yayaUser);
            
            if (!Auth::check()) {
                throw new Exception('Gagal login sebagai Yaya');
            }
            
            echo "<p>✅ Berhasil login sebagai: {$yayaUser->name}</p>";
            
            // Cek jadwal hari ini
            $todaySchedule = JadwalJaga::where('pegawai_id', $yayaUser->id)
                ->whereDate('tanggal_jaga', today())
                ->with('shiftTemplate')
                ->first();
            
            if (!$todaySchedule) {
                throw new Exception('Tidak ada jadwal jaga hari ini');
            }
            
            echo "<p>✅ Jadwal ditemukan: {$todaySchedule->shiftTemplate->nama_shift}</p>";
            echo "<p>✅ Jam: {$todaySchedule->shiftTemplate->jam_masuk} - {$todaySchedule->shiftTemplate->jam_pulang}</p>";
            
            // Simulasi request check-in
            $requestData = [
                'latitude' => -7.8235,
                'longitude' => 112.0178,
                'location' => 'Klinik Dokterku, Mojo Kediri',
                'accuracy' => 50
            ];
            
            echo "<p>📡 Mengirim request check-in...</p>";
            echo "<div class='code'>
Request Data:
" . json_encode($requestData, JSON_PRETTY_PRINT) . "
            </div>";
            
            // Buat request ke API
            $request = \Illuminate\Http\Request::create('/api/v2/dashboards/dokter/checkin', 'POST', $requestData);
            $request->headers->set('Accept', 'application/json');
            $request->headers->set('Content-Type', 'application/json');
            $request->headers->set('X-CSRF-TOKEN', csrf_token());
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
            
            // Handle request
            $response = app()->handle($request);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent();
            
            echo "<p><strong>Response Status:</strong> {$statusCode}</p>";
            
            // Parse response
            $responseData = json_decode($content, true);
            
            if ($statusCode === 200 || $statusCode === 201) {
                $responseClass = 'response-success';
                $statusIcon = '✅';
                $statusText = 'SUCCESS';
            } else {
                $responseClass = 'response-error';
                $statusIcon = '❌';
                $statusText = 'FAILED';
            }
            
            echo "<div class='response {$responseClass}'>";
            echo "<h3>{$statusIcon} API Response ({$statusText})</h3>";
            echo "<pre>" . json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            echo "</div>";
            
            // Analisis response
            if (isset($responseData['success']) && $responseData['success']) {
                echo "<div class='section success'>";
                echo "<h3>🎉 Check-in Berhasil!</h3>";
                echo "<p>✅ API endpoint berfungsi dengan baik</p>";
                echo "<p>✅ Buffer waktu sudah diperbaiki</p>";
                echo "<p>✅ Validasi waktu sudah benar</p>";
                echo "</div>";
            } else {
                echo "<div class='section error'>";
                echo "<h3>❌ Check-in Gagal</h3>";
                echo "<p><strong>Message:</strong> " . ($responseData['message'] ?? 'Unknown error') . "</p>";
                echo "<p><strong>Code:</strong> " . ($responseData['code'] ?? 'Unknown') . "</p>";
                
                if (isset($responseData['debug_info'])) {
                    echo "<h4>Debug Info:</h4>";
                    echo "<div class='code'>";
                    echo json_encode($responseData['debug_info'], JSON_PRETTY_PRINT);
                    echo "</div>";
                }
                
                echo "<h4>Analisis Masalah:</h4>";
                if (strpos($responseData['message'] ?? '', 'bukan jam jaga') !== false) {
                    echo "<p>❌ Masih ada masalah dengan validasi waktu</p>";
                    echo "<p>💡 Solusi: Perpanjang buffer lagi atau buat jadwal shift malam</p>";
                } elseif (strpos($responseData['message'] ?? '', 'tidak memiliki jadwal') !== false) {
                    echo "<p>❌ Tidak ada jadwal jaga hari ini</p>";
                    echo "<p>💡 Solusi: Buat jadwal shift untuk Yaya</p>";
                } else {
                    echo "<p>❌ Masalah lain yang perlu dianalisis</p>";
                }
                echo "</div>";
            }
            
            // Logout
            Auth::logout();
            
        } catch (Exception $e) {
            echo "<div class='section error'>";
            echo "<h3>❌ Error:</h3>";
            echo "<p>{$e->getMessage()}</p>";
            echo "</div>";
        }
        
        echo "</div>";
    }
} else {
    // Form untuk test API
    echo "<div class='section info'>
        <h2>📋 Form Test API Check-in</h2>";
    
    $yayaUser = User::where('name', 'like', '%Yaya%')->first();
    $now = Carbon::now();
    
    if ($yayaUser) {
        $todaySchedule = JadwalJaga::where('pegawai_id', $yayaUser->id)
            ->whereDate('tanggal_jaga', today())
            ->with('shiftTemplate')
            ->first();
        
        echo "<p><strong>User:</strong> {$yayaUser->name} (ID: {$yayaUser->id})</p>";
        echo "<p><strong>Waktu Saat Ini:</strong> {$now->format('Y-m-d H:i:s')}</p>";
        
        if ($todaySchedule) {
            $shiftName = $todaySchedule->shiftTemplate->nama_shift;
            $jamMasuk = $todaySchedule->shiftTemplate->jam_masuk;
            $jamPulang = $todaySchedule->shiftTemplate->jam_pulang;
            
            echo "<p><strong>Jadwal Hari Ini:</strong> {$shiftName} ({$jamMasuk} - {$jamPulang})</p>";
            
            // Analisis waktu
            $startTime = Carbon::parse($jamMasuk);
            $endTime = Carbon::parse($jamPulang);
            $currentTime = Carbon::parse($now->format('H:i:s'));
            
            $shiftDuration = $endTime->diffInMinutes($startTime);
            $bufferMinutes = $shiftDuration <= 30 ? 60 : 30;
            
            $startTimeWithBuffer = $startTime->copy()->subMinutes($bufferMinutes);
            $endTimeWithBuffer = $endTime->copy()->addMinutes($bufferMinutes);
            
            $isWithinBuffer = $currentTime->format('H:i:s') >= $startTimeWithBuffer->format('H:i:s') && 
                             $currentTime->format('H:i:s') <= $endTimeWithBuffer->format('H:i:s');
            
            $currentHour = (int) $now->format('H');
            $isNightShift = $currentHour >= 22 || $currentHour <= 6;
            
            $canCheckIn = $isWithinBuffer || ($isNightShift && $currentHour >= 22);
            
            echo "<p><strong>Buffer:</strong> {$bufferMinutes} menit</p>";
            echo "<p><strong>Range Buffer:</strong> {$startTimeWithBuffer->format('H:i')} - {$endTimeWithBuffer->format('H:i')}</p>";
            echo "<p><strong>Dalam Range Buffer:</strong> " . ($isWithinBuffer ? 'YA' : 'TIDAK') . "</p>";
            echo "<p><strong>Shift Malam:</strong> " . ($isNightShift ? 'YA' : 'TIDAK') . "</p>";
            echo "<p><strong>Prediksi Check-in:</strong> " . ($canCheckIn ? 'BISA' : 'TIDAK BISA') . "</p>";
            
        } else {
            echo "<p class='error'>❌ Tidak ada jadwal jaga hari ini!</p>";
        }
        
        echo "<form method='POST'>
            <input type='hidden' name='action' value='test_checkin'>
            
            <div style='margin: 20px 0;'>
                <h3>Test Data:</h3>
                <p><strong>Latitude:</strong> -7.8235 (Klinik Dokterku)</p>
                <p><strong>Longitude:</strong> 112.0178 (Klinik Dokterku)</p>
                <p><strong>Location:</strong> Klinik Dokterku, Mojo Kediri</p>
                <p><strong>Accuracy:</strong> 50 meters</p>
            </div>
            
            <button type='submit' class='button'>🧪 Test API Check-in</button>
        </form>";
        
    } else {
        echo "<p class='error'>❌ User Yaya tidak ditemukan!</p>";
    }
    
    echo "</div>";
    
    // Informasi API
    echo "<div class='section info'>
        <h2>🌐 Informasi API Endpoint</h2>";
    
    echo "<h3>Endpoint:</h3>";
    echo "<div class='code'>
POST /api/v2/dashboards/dokter/checkin
        </div>";
    
    echo "<h3>Headers:</h3>";
    echo "<div class='code'>
Accept: application/json
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}
X-Requested-With: XMLHttpRequest
Authorization: Bearer {token} (jika menggunakan Sanctum)
        </div>";
    
    echo "<h3>Request Body:</h3>";
    echo "<div class='code'>
{
  'latitude': -7.8235,
  'longitude': 112.0178,
  'location': 'Klinik Dokterku, Mojo Kediri',
  'accuracy': 50
}
        </div>";
    
    echo "<h3>Expected Response (Success):</h3>";
    echo "<div class='code'>
{
  'success': true,
  'message': 'Check-in berhasil',
  'data': {
    'attendance': {
      'id': 123,
      'user_id': 13,
      'date': '2025-08-08',
      'time_in': '2025-08-08 23:15:00',
      'status': 'present'
    },
    'schedule': {
      'id': 456,
      'shift_name': 'Shift Malam Test',
      'start_time': '22:00',
      'end_time': '06:00',
      'unit_kerja': 'Dokter Jaga'
    }
  }
}
        </div>";
    
    echo "<h3>Expected Response (Error):</h3>";
    echo "<div class='code'>
{
  'success': false,
  'message': 'Saat ini bukan jam jaga Anda...',
  'code': 'OUTSIDE_SHIFT_HOURS',
  'debug_info': {
    'current_time': '2025-08-08T23:15:00.000000Z',
    'shift_start': '2025-08-08T22:00:00.000000Z',
    'shift_end': '2025-08-08T06:00:00.000000Z',
    'timezone': 'Asia/Jakarta',
    'buffer_minutes': 30
  }
}
        </div>";
    echo "</div>";
}

// Quick Actions
echo "<div class='section success'>
    <h2>⚡ Quick Actions</h2>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='root-analysis-presensi.php' class='button'>🔍 Root Analysis</a>";
echo "<a href='fix-presensi-buffer.php' class='button'>🛠️ Fix Buffer</a>";
echo "<a href='create-night-shift.php' class='button'>📅 Create Night Shift</a>";
echo "<a href='test-buffer-fix.php' class='button'>🧪 Test Buffer Fix</a>";
echo "<a href='test-mobile-app.php' class='button'>📱 Test Mobile App</a>";
echo "</div>";

echo "</div>";

echo "</div>
</body>
</html>";
?>
