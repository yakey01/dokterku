<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Jaspel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== ANALISIS TINDAKAN DISCREPANCY (CORRECTED) ===\n\n";

// Login as dr. Yaya
$yayaUser = User::find(13);
Auth::login($yayaUser);
$dokter = Dokter::where('user_id', $yayaUser->id)->first();

$currentMonth = Carbon::now()->month;
$currentYear = Carbon::now()->year;

echo "User: {$yayaUser->name} (ID: {$yayaUser->id})\n";
echo "Dokter ID: {$dokter->id}\n";
echo "Period: {$currentYear}-{$currentMonth} (August 2025)\n\n";

// 1. TINDAKAN TABLE (corrected column name)
echo "1. TINDAKAN TABLE ANALYSIS (CORRECTED)\n";
echo "=======================================\n";

// Current month using correct column name
$tindakanCurrentMonth = Tindakan::where('dokter_id', $dokter->id)
    ->whereMonth('tanggal_tindakan', $currentMonth)
    ->whereYear('tanggal_tindakan', $currentYear)
    ->get();

echo "Current Month ({$currentYear}-{$currentMonth}):\n";
echo "  - Total Records: " . $tindakanCurrentMonth->count() . "\n";

if ($tindakanCurrentMonth->count() > 0) {
    foreach ($tindakanCurrentMonth as $tindakan) {
        echo "    - {$tindakan->tanggal_tindakan}: Jenis ID {$tindakan->jenis_tindakan_id} (Fee: {$tindakan->jasa_dokter}, Status: {$tindakan->status_validasi})\n";
    }
} else {
    echo "    - No tindakan records found for current month\n";
}

// All months - simplified query for SQLite
$allTindakan = DB::table('tindakan')
    ->where('dokter_id', $dokter->id)
    ->whereNull('deleted_at')
    ->get();

echo "\nAll Time Summary:\n";
if ($allTindakan->count() > 0) {
    $monthlyData = [];
    foreach ($allTindakan as $tindakan) {
        $date = Carbon::parse($tindakan->tanggal_tindakan);
        $key = $date->format('Y-m');
        if (!isset($monthlyData[$key])) {
            $monthlyData[$key] = ['count' => 0, 'total_fee' => 0];
        }
        $monthlyData[$key]['count']++;
        $monthlyData[$key]['total_fee'] += $tindakan->jasa_dokter;
    }
    
    foreach ($monthlyData as $month => $data) {
        echo "  - {$month}: {$data['count']} tindakan (Fee: {$data['total_fee']})\n";
    }
} else {
    echo "  - No tindakan records found in any month\n";
}

// Check status breakdown
$statusBreakdown = DB::table('tindakan')
    ->where('dokter_id', $dokter->id)
    ->whereNull('deleted_at')
    ->selectRaw('status_validasi, COUNT(*) as count')
    ->groupBy('status_validasi')
    ->get();

echo "\nStatus Breakdown (All Time):\n";
foreach ($statusBreakdown as $status) {
    echo "  - {$status->status_validasi}: {$status->count} records\n";
}

echo "\n";

// 2. JASPEL TINDAKAN ANALYSIS
echo "2. JASPEL TINDAKAN ANALYSIS\n";
echo "===========================\n";

// Check JASPEL records with tindakan-related content
$jaspelTindakan = Jaspel::where('user_id', $yayaUser->id)
    ->whereMonth('tanggal', $currentMonth)
    ->whereYear('tanggal', $currentYear)
    ->where(function($query) {
        $query->where('jenis_jaspel', 'LIKE', '%tindakan%')
              ->orWhere('jenis_tindakan', '!=', '')
              ->orWhereNotNull('jenis_tindakan')
              ->orWhere('keterangan', 'LIKE', '%tindakan%')
              ->orWhere('keterangan', 'LIKE', '%prosedur%');
    })
    ->get();

echo "JASPEL Tindakan-related Records (Current Month):\n";
echo "  - Total Records: " . $jaspelTindakan->count() . "\n";

if ($jaspelTindakan->count() > 0) {
    foreach ($jaspelTindakan as $jaspel) {
        echo "    - {$jaspel->tanggal}: {$jaspel->jenis_jaspel}\n";
        if ($jaspel->jenis_tindakan) {
            echo "      Jenis Tindakan: {$jaspel->jenis_tindakan}\n";
        }
        echo "      Nominal: {$jaspel->nominal}, Status: {$jaspel->status_validasi}\n";
        if ($jaspel->keterangan) {
            echo "      Keterangan: " . substr($jaspel->keterangan, 0, 100) . "...\n";
        }
        echo "\n";
    }
}

// 3. CHECK ALL JASPEL FOR PATTERNS
echo "3. ALL JASPEL PATTERNS ANALYSIS\n";
echo "===============================\n";

$allJaspelCurrentMonth = Jaspel::where('user_id', $yayaUser->id)
    ->whereMonth('tanggal', $currentMonth)
    ->whereYear('tanggal', $currentYear)
    ->get();

echo "All JASPEL Current Month: " . $allJaspelCurrentMonth->count() . " records\n\n";

$jaspelByType = [];
foreach ($allJaspelCurrentMonth as $jaspel) {
    $type = $jaspel->jenis_jaspel;
    if (!isset($jaspelByType[$type])) {
        $jaspelByType[$type] = ['count' => 0, 'total' => 0];
    }
    $jaspelByType[$type]['count']++;
    $jaspelByType[$type]['total'] += $jaspel->nominal;
}

echo "JASPEL by Type:\n";
foreach ($jaspelByType as $type => $data) {
    echo "  - {$type}: {$data['count']} records, Total: {$data['total']}\n";
}

echo "\n";

// 4. LEADERBOARD QUERY REPLICATION
echo "4. LEADERBOARD QUERY REPLICATION\n";
echo "=================================\n";

// Replicate exact leaderboard query
$leaderboardTindakanCount = Tindakan::where('dokter_id', $dokter->id)
    ->whereMonth('tanggal_tindakan', $currentMonth)
    ->whereYear('tanggal_tindakan', $currentYear)
    ->count();

echo "Leaderboard Query Result: {$leaderboardTindakanCount}\n";

// Check with validation filter
$approvedTindakanCount = Tindakan::where('dokter_id', $dokter->id)
    ->whereMonth('tanggal_tindakan', $currentMonth)
    ->whereYear('tanggal_tindakan', $currentYear)
    ->whereIn('status_validasi', ['approved', 'disetujui'])
    ->count();

echo "Approved Tindakan Count: {$approvedTindakanCount}\n";

// Check without any filters
$allTindakanForDoctor = Tindakan::where('dokter_id', $dokter->id)->count();
echo "Total Tindakan (All Time): {$allTindakanForDoctor}\n\n";

// 5. ACHIEVEMENTS ANALYSIS
echo "5. ACHIEVEMENTS ANALYSIS\n";
echo "========================\n";

// Look for gaming/achievement related JASPEL
$achievementJaspel = Jaspel::where('user_id', $yayaUser->id)
    ->where(function($query) {
        $query->where('jenis_jaspel', 'LIKE', '%achievement%')
              ->orWhere('jenis_jaspel', 'LIKE', '%quest%')
              ->orWhere('jenis_jaspel', 'LIKE', '%gaming%')
              ->orWhere('keterangan', 'LIKE', '%achievement%')
              ->orWhere('keterangan', 'LIKE', '%quest%');
    })
    ->get();

echo "Achievement-related JASPEL: " . $achievementJaspel->count() . " records\n";

if ($achievementJaspel->count() > 0) {
    foreach ($achievementJaspel as $jaspel) {
        echo "  - {$jaspel->tanggal}: {$jaspel->jenis_jaspel} (Nominal: {$jaspel->nominal})\n";
    }
}

echo "\n";

// 6. SUMMARY AND DISCREPANCY ANALYSIS
echo "6. DISCREPANCY SUMMARY\n";
echo "======================\n";

echo "Tindakan Count Sources:\n";
echo "  - Tindakan Table (Current Month): {$leaderboardTindakanCount}\n";
echo "  - Approved Tindakan (Current Month): {$approvedTindakanCount}\n";
echo "  - JASPEL Tindakan-related: " . $jaspelTindakan->count() . "\n";
echo "  - All JASPEL (Current Month): " . $allJaspelCurrentMonth->count() . "\n";

if ($leaderboardTindakanCount == 0 && $jaspelTindakan->count() > 0) {
    echo "\n⚠️ POTENTIAL DISCREPANCY FOUND!\n";
    echo "Leaderboard shows 0 tindakan but JASPEL has " . $jaspelTindakan->count() . " tindakan-related records.\n";
    echo "\nThis suggests:\n";
    echo "1. Tindakan data might be recorded in JASPEL system instead of Tindakan table\n";
    echo "2. Leaderboard query might need to include JASPEL tindakan records\n";
    echo "3. Business logic: What counts as 'tindakan' for leaderboard?\n";
} elseif ($leaderboardTindakanCount == 0 && $allJaspelCurrentMonth->count() > 0) {
    echo "\n📋 JASPEL DATA AVAILABLE BUT NO DIRECT TINDAKAN\n";
    echo "Dr. Yaya has " . $allJaspelCurrentMonth->count() . " JASPEL records but 0 direct tindakan.\n";
    echo "Need to determine if any JASPEL records should count as 'tindakan'.\n";
} else {
    echo "\n✅ CONSISTENT DATA\n";
    echo "Tindakan count appears consistent across sources.\n";
}

echo "\nRecommendation:\n";
if ($jaspelTindakan->count() > 0) {
    echo "1. Review JASPEL tindakan-related records to see if they should count\n";
    echo "2. Consider updating leaderboard logic to include relevant JASPEL records\n";
    echo "3. Clarify business definition of 'tindakan' for scoring purposes\n";
} else {
    echo "1. Dr. Yaya genuinely has 0 tindakan for current month\n";
    echo "2. This is accurate data, not a system error\n";
}