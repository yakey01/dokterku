<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a mock request
$request = Illuminate\Http\Request::create('/test', 'GET');
$response = $kernel->handle($request);

// Boot the app
$kernel->terminate($request, $response);

use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use App\Models\User;

// Find Dr. Yaya Mulyana
$yayaUser = User::where('name', 'LIKE', '%Yaya%Mulyana%')
    ->orWhere('name', 'LIKE', '%yaya%mulyana%')
    ->first();

if ($yayaUser) {
    echo "✅ Found User: Dr. Yaya Mulyana (ID: {$yayaUser->id})\n";
    
    // Find Dokter record
    $yayaDokter = Dokter::where('user_id', $yayaUser->id)->first();
    
    if ($yayaDokter) {
        echo "✅ Found Dokter: {$yayaDokter->nama_lengkap} (ID: {$yayaDokter->id})\n";
        
        // Get JumlahPasienHarian data
        $jumlahPasienData = JumlahPasienHarian::where('dokter_id', $yayaDokter->id)
            ->orderBy('tanggal', 'desc')
            ->get();
        
        echo "\n📊 JumlahPasienHarian Data for Dr. Yaya Mulyana:\n";
        echo "====================================================\n";
        
        foreach ($jumlahPasienData as $data) {
            echo "Date: {$data->tanggal->format('Y-m-d')} | ";
            echo "Poli: {$data->poli} | ";
            echo "Umum: {$data->jumlah_pasien_umum} | ";
            echo "BPJS: {$data->jumlah_pasien_bpjs} | ";
            echo "Total: {$data->total_pasien} | ";
            echo "Status: {$data->status_validasi}\n";
            
            if ($data->validasi_by) {
                $validator = User::find($data->validasi_by);
                echo "  → Validated by: " . ($validator ? $validator->name : 'Unknown') . " at {$data->validasi_at}\n";
            }
            if ($data->catatan_validasi) {
                echo "  → Validation Note: {$data->catatan_validasi}\n";
            }
            echo "\n";
        }
        
        // Summary
        $approved = $jumlahPasienData->where('status_validasi', 'approved')->count();
        $pending = $jumlahPasienData->where('status_validasi', 'pending')->count();
        $total = $jumlahPasienData->count();
        
        echo "====================================================\n";
        echo "Summary: Total {$total} entries | Approved: {$approved} | Pending: {$pending}\n";
        
        // Test the API controller
        echo "\n🔍 Testing API Integration:\n";
        echo "====================================================\n";
        
        $controller = new \App\Http\Controllers\Api\V2\JumlahPasienController();
        
        // Mock request with auth
        Auth::login($yayaUser);
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'month' => now()->month,
            'year' => now()->year,
        ]);
        
        $response = $controller->getJumlahPasienForJaspel($request);
        $responseData = json_decode($response->getContent(), true);
        
        if ($responseData['success']) {
            echo "✅ API Response Success!\n";
            echo "Total Items: " . count($responseData['data']['jumlah_pasien_items']) . "\n";
            
            // Verify all returned items are approved
            $allApproved = true;
            foreach ($responseData['data']['jumlah_pasien_items'] as $item) {
                if ($item['status_validasi'] !== 'approved' && $item['status_validasi'] !== 'disetujui') {
                    $allApproved = false;
                    echo "⚠️ Found non-approved entry: Date {$item['tanggal']}, Status: {$item['status_validasi']}\n";
                }
            }
            
            if ($allApproved) {
                echo "✅ All returned entries are Bendahara-validated (approved only)\n";
            } else {
                echo "❌ Some entries are not approved - API filter may not be working\n";
            }
            
            echo "Summary:\n";
            echo "  - Total Hari Jaga (Approved Only): " . $responseData['data']['summary']['total_hari_jaga'] . "\n";
            echo "  - Total Pasien (Approved Only): " . $responseData['data']['summary']['total_pasien_bulan_ini'] . "\n";
            echo "  - Rata-rata Pasien: " . round($responseData['data']['summary']['rata_rata_pasien'], 1) . "\n";
            echo "  - Estimated Jaspel (Approved Only): Rp " . number_format($responseData['data']['summary']['total_estimated_jaspel'], 0, ',', '.') . "\n";
        } else {
            echo "❌ API Response Failed: " . $responseData['message'] . "\n";
        }
        
    } else {
        echo "❌ Dokter record not found for user ID: {$yayaUser->id}\n";
    }
} else {
    echo "❌ Dr. Yaya Mulyana not found in Users table\n";
}