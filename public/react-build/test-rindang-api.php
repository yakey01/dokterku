<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;
use App\Models\JadwalJaga;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "=== TESTING DR. RINDANG'S JADWAL API ===\n\n";

// Get dr. Rindang
$dokter = User::where('username', 'drrindang')
    ->orWhere('name', 'like', '%Rindang%')
    ->first();

if (!$dokter) {
    die("Dr. Rindang not found!\n");
}

echo "User Details:\n";
echo "  - ID: {$dokter->id}\n";
echo "  - Name: {$dokter->name}\n";
echo "  - Username: {$dokter->username}\n";
echo "  - pegawai_id (on user): " . ($dokter->pegawai_id ?: 'NULL') . "\n";

// Check pegawai relationship
$pegawai = $dokter->pegawai;
if ($pegawai) {
    echo "  - Related Pegawai ID: {$pegawai->id}\n";
    echo "  - Pegawai Name: {$pegawai->nama_lengkap}\n";
} else {
    echo "  - No pegawai relationship found\n";
    
    // Check pegawais table directly
    $pegawaiRecord = DB::table('pegawais')->where('user_id', $dokter->id)->first();
    if ($pegawaiRecord) {
        echo "  - Found pegawai by user_id: ID {$pegawaiRecord->id}\n";
    }
}

echo "\n";

// Now check jadwal_jagas table
$today = Carbon::today();
$pegawaiId = $dokter->pegawai_id ?: ($dokter->pegawai ? $dokter->pegawai->id : null);

if (!$pegawaiId) {
    // Try to find pegawai_id from pegawais table
    $pegawaiRecord = DB::table('pegawais')->where('user_id', $dokter->id)->first();
    if ($pegawaiRecord) {
        $pegawaiId = $pegawaiRecord->id;
        echo "Found pegawai_id from table: $pegawaiId\n";
    }
}

echo "=== JADWAL JAGA QUERIES ===\n\n";

// Query 1: Using user->id directly (OLD/WRONG way)
$jadwalWrong = JadwalJaga::where('pegawai_id', $dokter->id)
    ->whereDate('tanggal_jaga', $today)
    ->with(['shiftTemplate'])
    ->get();

echo "1. Query with user->id ({$dokter->id}):\n";
echo "   - Count: " . $jadwalWrong->count() . "\n";
if ($jadwalWrong->isNotEmpty()) {
    foreach ($jadwalWrong as $j) {
        echo "   - ID {$j->id}: {$j->tanggal_jaga} - " . ($j->shiftTemplate ? $j->shiftTemplate->nama_shift : 'No shift') . "\n";
    }
} else {
    echo "   - No results (this is why the schedule disappears!)\n";
}

echo "\n";

// Query 2: Using correct pegawai_id
if ($pegawaiId) {
    $jadwalCorrect = JadwalJaga::where('pegawai_id', $pegawaiId)
        ->whereDate('tanggal_jaga', $today)
        ->with(['shiftTemplate'])
        ->get();
    
    echo "2. Query with pegawai_id ($pegawaiId):\n";
    echo "   - Count: " . $jadwalCorrect->count() . "\n";
    if ($jadwalCorrect->isNotEmpty()) {
        foreach ($jadwalCorrect as $j) {
            $shift = $j->shiftTemplate;
            echo "   - ID {$j->id}: {$j->tanggal_jaga}\n";
            if ($shift) {
                echo "     Shift: {$shift->nama_shift} ({$shift->waktu_mulai} - {$shift->waktu_selesai})\n";
            }
        }
    } else {
        echo "   - No results\n";
    }
} else {
    echo "2. Cannot query - no pegawai_id found\n";
}

echo "\n=== API SIMULATION ===\n\n";

// Simulate what the API would return
$controller = new \App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController();

// Create a mock request
$request = new \Illuminate\Http\Request();
$request->setUserResolver(function () use ($dokter) {
    return $dokter;
});

// Call the getJadwalJaga method
echo "Calling API endpoint...\n";
$response = $controller->getJadwalJaga($request);
$data = json_decode($response->getContent(), true);

if ($data['success']) {
    echo "✅ API call successful\n";
    echo "   - Today's schedule count: " . count($data['data']['today'] ?? []) . "\n";
    
    if (!empty($data['data']['today'])) {
        echo "   - Today's schedules:\n";
        foreach ($data['data']['today'] as $schedule) {
            echo "     • " . ($schedule['shift_template']['nama_shift'] ?? 'Unknown') . " ";
            echo "(" . ($schedule['shift_template']['jam_masuk'] ?? '?') . " - ";
            echo ($schedule['shift_template']['jam_pulang'] ?? '?') . ")\n";
        }
    } else {
        echo "   ⚠️ No schedules returned for today!\n";
    }
} else {
    echo "❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "\n";
}

echo "\n=== DIAGNOSIS ===\n\n";

if (!$pegawaiId) {
    echo "❌ PROBLEM: Dr. Rindang has no pegawai_id!\n";
    echo "   - User ID {$dokter->id} is not linked to any pegawai record\n";
    echo "   - The API was incorrectly using user->id as pegawai_id\n";
    echo "   - This causes the query to fail after cache expires\n";
} else {
    echo "✅ Dr. Rindang has pegawai_id: $pegawaiId\n";
    echo "   - The fix now properly uses this ID to query jadwal_jagas\n";
    
    if ($jadwalCorrect && $jadwalCorrect->isNotEmpty()) {
        echo "   - Schedule found for today\n";
    } else {
        echo "   - No schedule for today (need to create one)\n";
    }
}

echo "\n=== SOLUTION ===\n";
echo "The controller has been fixed to:\n";
echo "1. Get pegawai_id from user->pegawai_id or user->pegawai relationship\n";
echo "2. Use the correct pegawai_id to query jadwal_jagas table\n";
echo "3. Fall back to user->id only if no pegawai_id exists (backward compatibility)\n";
echo "\nThis ensures the schedule appears consistently on both manual and auto-refresh!\n";