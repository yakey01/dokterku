<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Get dr. Rindang
$dokter = User::where('username', 'drrindang')
    ->orWhere('name', 'like', '%Rindang%')
    ->first();

if (!$dokter) {
    die("Dr. Rindang not found!\n");
}

// Login as dr. Rindang
Auth::login($dokter);

echo "=== TESTING API AS DR. RINDANG ===\n\n";
echo "Logged in as: {$dokter->name} (ID: {$dokter->id})\n\n";

// Create a request with authentication
$request = Illuminate\Http\Request::create(
    '/api/v2/dashboards/dokter/jadwal-jaga',
    'GET',
    ['t' => time()]
);

// Set the authenticated user
$request->setUserResolver(function () use ($dokter) {
    return $dokter;
});

// Handle the request through the kernel
$response = $kernel->handle($request);
$content = $response->getContent();
$data = json_decode($content, true);

echo "API Response:\n";
echo "  - Status: " . $response->getStatusCode() . "\n";
echo "  - Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";

if ($data['success']) {
    echo "\nToday's Schedule:\n";
    $today = $data['data']['today'] ?? [];
    echo "  - Count: " . count($today) . "\n";
    
    if (count($today) > 0) {
        foreach ($today as $idx => $schedule) {
            echo "\n  Schedule " . ($idx + 1) . ":\n";
            echo "    - ID: " . ($schedule['id'] ?? 'N/A') . "\n";
            echo "    - Date: " . ($schedule['tanggal_jaga'] ?? 'N/A') . "\n";
            echo "    - Unit: " . ($schedule['unit_kerja'] ?? 'N/A') . "\n";
            
            if (isset($schedule['shift_template'])) {
                $shift = $schedule['shift_template'];
                echo "    - Shift: " . ($shift['nama_shift'] ?? 'N/A') . "\n";
                echo "    - Time: " . ($shift['jam_masuk'] ?? '?') . " - " . ($shift['jam_pulang'] ?? '?') . "\n";
            }
        }
    } else {
        echo "  ⚠️ No schedules found for today\n";
    }
    
    echo "\nCache Info:\n";
    $cacheInfo = $data['data']['cache_info'] ?? [];
    echo "  - Cached at: " . ($cacheInfo['cached_at'] ?? 'N/A') . "\n";
    echo "  - Cache TTL: " . ($cacheInfo['cache_ttl'] ?? 'N/A') . " seconds\n";
    echo "  - Is Refresh: " . ($cacheInfo['is_refresh'] ? 'YES' : 'NO') . "\n";
    
} else {
    echo "\n❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
}

echo "\n=== CHECKING FALLBACK MECHANISM ===\n\n";

// Check what pegawai_id would be used
$pegawaiId = $dokter->pegawai_id ?: ($dokter->pegawai ? $dokter->pegawai->id : null);

echo "Pegawai ID Resolution:\n";
echo "  - user->pegawai_id: " . ($dokter->pegawai_id ?: 'NULL') . "\n";
echo "  - user->pegawai relationship: " . ($dokter->pegawai ? "EXISTS (ID: {$dokter->pegawai->id})" : 'NULL') . "\n";
echo "  - Resolved pegawai_id: " . ($pegawaiId ?: 'NULL (will use fallback)') . "\n";
echo "  - Fallback user->id: {$dokter->id}\n";

echo "\n✅ The controller now uses this logic:\n";
echo "   1. Try to get pegawai_id from user->pegawai_id\n";
echo "   2. If null, try user->pegawai relationship\n";
echo "   3. If still null, fall back to user->id for backward compatibility\n";
echo "\nThis ensures schedules appear consistently!\n";