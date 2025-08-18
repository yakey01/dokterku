<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\JumlahPasienHarian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== ANALISIS DISCREPANCY TINDAKAN DR. YAYA ===\n\n";

// Login as dr. Yaya
$yayaUser = User::find(13);
if (!$yayaUser) {
    echo "❌ Dr. Yaya user not found\n";
    exit;
}

Auth::login($yayaUser);
$dokter = Dokter::where('user_id', $yayaUser->id)->first();

$currentMonth = Carbon::now()->month;
$currentYear = Carbon::now()->year;

echo "User: {$yayaUser->name} (ID: {$yayaUser->id})\n";
echo "Dokter ID: {$dokter->id}\n";
echo "Period: {$currentYear}-{$currentMonth} (August 2025)\n\n";

// 1. CHECK TINDAKAN TABLE
echo "1. TINDAKAN TABLE ANALYSIS\n";
echo "==========================\n";

// Current month
$tindakanCurrentMonth = Tindakan::where('dokter_id', $dokter->id)
    ->whereMonth('tanggal', $currentMonth)
    ->whereYear('tanggal', $currentYear)
    ->get();

echo "Current Month ({$currentYear}-{$currentMonth}):\n";
echo "  - Total Records: " . $tindakanCurrentMonth->count() . "\n";

if ($tindakanCurrentMonth->count() > 0) {
    foreach ($tindakanCurrentMonth as $tindakan) {
        echo "    - {$tindakan->tanggal}: {$tindakan->jenis_tindakan} (Fee: {$tindakan->jasa_dokter})\n";
    }
} else {
    echo "    - No tindakan records found for current month\n";
}

// All months
$allTindakan = Tindakan::where('dokter_id', $dokter->id)
    ->selectRaw('YEAR(tanggal) as year, MONTH(tanggal) as month, COUNT(*) as count, SUM(jasa_dokter) as total_fee')
    ->groupBy('year', 'month')
    ->orderBy('year', 'desc')
    ->orderBy('month', 'desc')
    ->get();

echo "\nAll Months Summary:\n";
if ($allTindakan->count() > 0) {
    foreach ($allTindakan as $monthData) {
        $monthName = Carbon::create($monthData->year, $monthData->month, 1)->format('F Y');
        echo "  - {$monthName}: {$monthData->count} tindakan (Fee: {$monthData->total_fee})\n";
    }
} else {
    echo "  - No tindakan records found in any month\n";
}

echo "\n";

// 2. CHECK JASPEL TINDAKAN
echo "2. JASPEL TINDAKAN ANALYSIS\n";
echo "===========================\n";

$jaspelTindakan = Jaspel::where('user_id', $yayaUser->id)
    ->where('jenis_jaspel', 'LIKE', '%tindakan%')
    ->orWhere('jenis_jaspel', 'LIKE', '%prosedur%')
    ->orWhere('jenis_jaspel', 'LIKE', '%medis%')
    ->get();

echo "JASPEL Tindakan Records:\n";
echo "  - Total Records: " . $jaspelTindakan->count() . "\n";

if ($jaspelTindakan->count() > 0) {
    foreach ($jaspelTindakan as $jaspel) {
        echo "    - {$jaspel->tanggal}: {$jaspel->jenis_jaspel} (Nominal: {$jaspel->nominal}, Status: {$jaspel->status_validasi})\n";
    }
}

// Check all JASPEL records for patterns
$allJaspel = Jaspel::where('user_id', $yayaUser->id)
    ->whereMonth('tanggal', $currentMonth)
    ->whereYear('tanggal', $currentYear)
    ->get();

echo "\nAll JASPEL Current Month:\n";
echo "  - Total Records: " . $allJaspel->count() . "\n";

$jaspelByType = [];
foreach ($allJaspel as $jaspel) {
    $type = $jaspel->jenis_jaspel;
    if (!isset($jaspelByType[$type])) {
        $jaspelByType[$type] = ['count' => 0, 'total' => 0];
    }
    $jaspelByType[$type]['count']++;
    $jaspelByType[$type]['total'] += $jaspel->nominal;
}

foreach ($jaspelByType as $type => $data) {
    echo "    - {$type}: {$data['count']} records, Total: {$data['total']}\n";
}

echo "\n";

// 3. CHECK ACHIEVEMENT TINDAKAN
echo "3. ACHIEVEMENT TINDAKAN ANALYSIS\n";
echo "=================================\n";

// Check if there's an achievement_tindakan table or similar
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%achievement%'");
echo "Achievement-related tables:\n";
foreach ($tables as $table) {
    echo "  - {$table->name}\n";
}

// Check Jaspel records that might contain tindakan achievements
$achievementJaspel = Jaspel::where('user_id', $yayaUser->id)
    ->where(function($query) {
        $query->where('jenis_jaspel', 'LIKE', '%achievement%')
              ->orWhere('jenis_jaspakan', 'LIKE', '%quest%')
              ->orWhere('keterangan', 'LIKE', '%tindakan%')
              ->orWhere('keterangan', 'LIKE', '%prosedur%');
    })
    ->get();

echo "\nJASPEL Achievement-like Records:\n";
echo "  - Total Records: " . $achievementJaspel->count() . "\n";

if ($achievementJaspel->count() > 0) {
    foreach ($achievementJaspel as $jaspel) {
        echo "    - {$jaspel->tanggal}: {$jaspel->jenis_jaspel}\n";
        echo "      Keterangan: {$jaspel->keterangan}\n";
        echo "      Nominal: {$jaspel->nominal}, Status: {$jaspel->status_validasi}\n";
    }
}

echo "\n";

// 4. CHECK LEADERBOARD QUERY LOGIC
echo "4. LEADERBOARD QUERY ANALYSIS\n";
echo "==============================\n";

// Replicate the exact leaderboard query for tindakan
$leaderboardTindakanQuery = Tindakan::where('dokter_id', $dokter->id)
    ->whereMonth('tanggal', $currentMonth)
    ->whereYear('tanggal', $currentYear);

echo "Leaderboard Tindakan Query:\n";
echo "  - Count: " . $leaderboardTindakanQuery->count() . "\n";

// Check if query has additional filters
$tindakanWithStatus = Tindakan::where('dokter_id', $dokter->id)
    ->whereMonth('tanggal', $currentMonth)
    ->whereYear('tanggal', $currentYear);

// Check if Tindakan table has status_validasi column
$tindakanTableInfo = DB::select("PRAGMA table_info(tindakans)");
$hasStatusValidasi = false;
foreach ($tindakanTableInfo as $column) {
    if ($column->name === 'status_validasi') {
        $hasStatusValidasi = true;
        break;
    }
}

echo "  - Tindakan table has status_validasi: " . ($hasStatusValidasi ? 'YES' : 'NO') . "\n";

if ($hasStatusValidasi) {
    $approvedTindakan = Tindakan::where('dokter_id', $dokter->id)
        ->whereMonth('tanggal', $currentMonth)
        ->whereYear('tanggal', $currentYear)
        ->whereIn('status_validasi', ['approved', 'disetujui'])
        ->count();
    
    echo "  - Approved Tindakan Count: {$approvedTindakan}\n";
}

echo "\n";

// 5. CHECK JASPEL AS TINDAKAN SOURCE
echo "5. JASPEL AS TINDAKAN SOURCE ANALYSIS\n";
echo "=====================================\n";

// Look for JASPEL records that might represent tindakan
$potentialTindakanJaspel = Jaspel::where('user_id', $yayaUser->id)
    ->whereMonth('tanggal', $currentMonth)
    ->whereYear('tanggal', $currentYear)
    ->where(function($query) {
        $query->where('jenis_tindakan', '!=', '')
              ->orWhereNotNull('jenis_tindakan')
              ->orWhere('jenis_jaspel', 'NOT LIKE', '%jaga%')
              ->orWhere('jenis_jaspel', 'NOT LIKE', '%pasien%');
    })
    ->get();

echo "Potential Tindakan from JASPEL:\n";
echo "  - Total Records: " . $potentialTindakanJaspel->count() . "\n";

$groupedByTindakan = [];
foreach ($potentialTindakanJaspel as $jaspel) {
    $tindakan = $jaspel->jenis_tindakan ?: $jaspel->jenis_jaspel;
    if (!isset($groupedByTindakan[$tindakan])) {
        $groupedByTindakan[$tindakan] = 0;
    }
    $groupedByTindakan[$tindakan]++;
}

foreach ($groupedByTindakan as $tindakan => $count) {
    echo "    - {$tindakan}: {$count} records\n";
}

echo "\n";

// 6. CONCLUSION AND RECOMMENDATIONS
echo "6. CONCLUSION & RECOMMENDATIONS\n";
echo "===============================\n";

$totalTindakanFromTable = $tindakanCurrentMonth->count();
$totalTindakanFromJaspel = $potentialTindakanJaspel->count();

echo "Summary:\n";
echo "  - Tindakan Table: {$totalTindakanFromTable}\n";
echo "  - Potential Tindakan from JASPEL: {$totalTindakanFromJaspel}\n";

if ($totalTindakanFromTable == 0 && $totalTindakanFromJaspel > 0) {
    echo "\n⚠️ DISCREPANCY FOUND!\n";
    echo "Leaderboard shows 0 tindakan but JASPEL contains {$totalTindakanFromJaspel} potential tindakan records.\n";
    echo "\nPossible causes:\n";
    echo "1. Tindakan data is stored in JASPEL table instead of Tindakan table\n";
    echo "2. Leaderboard query should include JASPEL tindakan records\n";
    echo "3. Different validation status filters\n";
    echo "4. Data migration issue - tindakan moved to JASPEL system\n";
    
    echo "\nRecommendations:\n";
    echo "1. Update leaderboard query to include JASPEL tindakan records\n";
    echo "2. Create unified tindakan counting logic\n";
    echo "3. Verify data model consistency\n";
} elseif ($totalTindakanFromTable == 0 && $totalTindakanFromJaspel == 0) {
    echo "\n✅ NO DISCREPANCY\n";
    echo "Dr. Yaya genuinely has 0 tindakan records for current month.\n";
} else {
    echo "\n📊 DATA AVAILABLE\n";
    echo "Both sources show tindakan data - need to verify counting logic.\n";
}

echo "\nNext steps:\n";
echo "1. Examine the actual JASPEL records in detail\n";
echo "2. Check if tindakan counting should include JASPEL records\n";
echo "3. Verify the business logic for what counts as 'tindakan'\n";