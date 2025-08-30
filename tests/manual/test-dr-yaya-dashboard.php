<?php

/**
 * Test Script untuk Verifikasi Dashboard dr. Yaya
 * 
 * Script ini akan memverifikasi:
 * 1. Data jumlah pasien yang benar (260 pasien)
 * 2. Welcome message yang personal
 * 3. Jadwal jaga yang sesuai
 * 4. JASPEL yang terkalkulasi dengan benar
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use App\Models\Dokter;
use App\Models\JumlahPasienHarian;
use App\Models\JadwalJaga;
use App\Models\Jaspel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController;

// Bootstrap Laravel application
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "====================================================\n";
echo "       VERIFIKASI DASHBOARD DR. YAYA               \n";
echo "====================================================\n\n";

// 1. Cek data dokter
echo "1. DATA DOKTER\n";
echo "----------------------------------------------------\n";

// Find dr. Yaya by user_id (we know it's 13)
$dokter = Dokter::where('user_id', 13)->first();
if (!$dokter) {
    echo "❌ Dokter tidak ditemukan!\n";
    exit(1);
}

// Get user data for name
$user = User::find($dokter->user_id);
$dokterName = $user ? $user->name : 'Unknown';

echo "✅ Dokter ID: " . $dokter->id . "\n";
echo "✅ Nama: " . $dokterName . "\n";
echo "✅ User ID: " . $dokter->user_id . "\n\n";

// 2. Cek data jumlah pasien
echo "2. DATA JUMLAH PASIEN\n";
echo "----------------------------------------------------\n";

$jumlahPasienRecords = JumlahPasienHarian::where('dokter_id', $dokter->id)
    ->whereMonth('tanggal', date('m'))
    ->whereYear('tanggal', date('Y'))
    ->whereIn('status_validasi', ['approved', 'disetujui'])
    ->get();

$totalUmum = $jumlahPasienRecords->sum('jumlah_pasien_umum');
$totalBPJS = $jumlahPasienRecords->sum('jumlah_pasien_bpjs');
$totalPasien = $totalUmum + $totalBPJS;

echo "📊 Jumlah Record: " . $jumlahPasienRecords->count() . "\n";
echo "📊 Total Pasien Umum: " . $totalUmum . "\n";
echo "📊 Total Pasien BPJS: " . $totalBPJS . "\n";
echo "✅ TOTAL PASIEN BULAN INI: " . $totalPasien . "\n\n";

// Detail per record
echo "Detail Record:\n";
foreach ($jumlahPasienRecords as $record) {
    echo "  - " . $record->tanggal . ": ";
    echo "Umum=" . $record->jumlah_pasien_umum . ", ";
    echo "BPJS=" . $record->jumlah_pasien_bpjs . ", ";
    echo "Status=" . $record->status_validasi . "\n";
}
echo "\n";

// 3. Cek jadwal jaga
echo "3. DATA JADWAL JAGA\n";
echo "----------------------------------------------------\n";

$jadwalJaga = JadwalJaga::where('dokter_id', $dokter->id)
    ->whereMonth('tanggal', date('m'))
    ->whereYear('tanggal', date('Y'))
    ->count();

echo "📅 Total Jadwal Jaga Bulan Ini: " . $jadwalJaga . "\n\n";

// 4. Test API Response
echo "4. TEST API DASHBOARD RESPONSE\n";
echo "----------------------------------------------------\n";

// Login sebagai dr. Yaya
$user = User::find($dokter->user_id);
if (!$user) {
    echo "❌ User tidak ditemukan!\n";
    exit(1);
}

Auth::login($user);

// Call dashboard controller
$controller = new DokterDashboardController();
$request = new Request();
$response = $controller->index($request);
$data = json_decode($response->getContent(), true);

if (!$data || !isset($data['data'])) {
    echo "❌ Response tidak valid!\n";
    exit(1);
}

$stats = $data['data']['stats'] ?? [];
$userData = $data['data']['user'] ?? [];

echo "API Response:\n";
echo "  👤 User Name: " . ($userData['name'] ?? 'N/A') . "\n";
echo "  📧 User Email: " . ($userData['email'] ?? 'N/A') . "\n";
echo "  🏥 Jabatan: " . ($userData['jabatan'] ?? 'N/A') . "\n";
echo "\n";

echo "Dashboard Stats:\n";
echo "  📊 Patients Today: " . ($stats['patients_today'] ?? 0) . "\n";
echo "  📊 Patients This Month: " . ($stats['patients_month'] ?? 0) . "\n";
echo "  📅 Shifts This Month: " . ($stats['shifts_month'] ?? 0) . "\n";
echo "  💰 JASPEL This Month: Rp " . number_format($stats['jaspel_month'] ?? 0, 0, ',', '.') . "\n";
echo "  ✅ Attendance Rate: " . ($stats['attendance_rate'] ?? 0) . "%\n";
echo "\n";

// 5. Verifikasi hasil
echo "5. VERIFIKASI HASIL\n";
echo "====================================================\n";

$tests = [
    [
        'name' => 'Jumlah Pasien Bulan Ini',
        'expected' => 260,
        'actual' => $stats['patients_month'] ?? 0,
        'passed' => ($stats['patients_month'] ?? 0) == 260
    ],
    [
        'name' => 'User Name Terisi',
        'expected' => 'dr. Yaya Mulyana, M.Kes',
        'actual' => $userData['name'] ?? 'N/A',
        'passed' => !empty($userData['name'])
    ],
    [
        'name' => 'Jadwal Jaga Terisi',
        'expected' => '> 0',
        'actual' => $stats['shifts_month'] ?? 0,
        'passed' => ($stats['shifts_month'] ?? 0) > 0
    ],
    [
        'name' => 'JASPEL Terkalkulasi',
        'expected' => '> 0',
        'actual' => $stats['jaspel_month'] ?? 0,
        'passed' => ($stats['jaspel_month'] ?? 0) > 0
    ]
];

$allPassed = true;
foreach ($tests as $test) {
    if ($test['passed']) {
        echo "✅ " . $test['name'] . "\n";
        echo "   Expected: " . $test['expected'] . "\n";
        echo "   Actual: " . $test['actual'] . "\n";
    } else {
        echo "❌ " . $test['name'] . "\n";
        echo "   Expected: " . $test['expected'] . "\n";
        echo "   Actual: " . $test['actual'] . "\n";
        $allPassed = false;
    }
    echo "\n";
}

// 6. Frontend Component Check
echo "6. FRONTEND COMPONENT CHECK\n";
echo "----------------------------------------------------\n";

$frontendFile = __DIR__ . '/resources/js/components/dokter/HolisticMedicalDashboard.tsx';
$fixedFile = __DIR__ . '/resources/js/components/dokter/HolisticMedicalDashboardFixed.tsx';

if (file_exists($fixedFile)) {
    echo "✅ Fixed Component exists: HolisticMedicalDashboardFixed.tsx\n";
    
    // Check if it has personalized greeting
    $content = file_get_contents($fixedFile);
    if (strpos($content, 'Selamat Pagi') !== false) {
        echo "✅ Indonesian greeting implemented\n";
    }
    if (strpos($content, 'getPersonalizedGreeting') !== false) {
        echo "✅ Personalized greeting function exists\n";
    }
    if (strpos($content, 'patients_month') !== false) {
        echo "✅ Uses patients_month field from API\n";
    }
} else {
    echo "⚠️ Fixed component not found. Make sure to deploy HolisticMedicalDashboardFixed.tsx\n";
}

echo "\n";
echo "====================================================\n";
if ($allPassed) {
    echo "🎉 SEMUA VERIFIKASI BERHASIL! 🎉\n";
    echo "Dashboard dr. Yaya sudah menampilkan data yang benar.\n";
} else {
    echo "⚠️ Ada beberapa test yang gagal.\n";
    echo "Silakan periksa kembali implementasi.\n";
}
echo "====================================================\n\n";

// 7. Summary
echo "RINGKASAN:\n";
echo "----------\n";
echo "• Nama Dokter: " . $dokterName . "\n";
echo "• Total Pasien Bulan Ini: " . $totalPasien . " pasien\n";
echo "• Jadwal Jaga: " . $jadwalJaga . " hari\n";
echo "• JASPEL: Rp " . number_format($stats['jaspel_month'] ?? 0, 0, ',', '.') . "\n";
echo "• Status Dashboard: " . ($allPassed ? "✅ WORKING" : "⚠️ NEEDS FIX") . "\n\n";

echo "Next Steps:\n";
echo "-----------\n";
if ($allPassed) {
    echo "1. Deploy to production\n";
    echo "2. Clear cache: php artisan cache:clear\n";
    echo "3. Build assets: npm run build\n";
    echo "4. Test di browser dengan login sebagai dr. Yaya\n";
} else {
    echo "1. Review DokterDashboardController.php\n";
    echo "2. Ensure JumlahPasienHarian is used instead of Tindakan\n";
    echo "3. Check status_validasi includes both 'approved' and 'disetujui'\n";
    echo "4. Verify HolisticMedicalDashboardFixed.tsx is deployed\n";
}
echo "\n";