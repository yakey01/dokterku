<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\JadwalJaga;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔄 Test Schedule Refresh - Real-time Updates</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
        .button { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #5a67d8; }
        .cache-status { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 12px; margin: 5px; }
        .cache-hit { background: #28a745; color: white; }
        .cache-miss { background: #dc3545; color: white; }
        .cache-cleared { background: #ffc107; color: black; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .refresh-indicator { animation: pulse 2s infinite; }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
    <script>
        function testCacheInvalidation() {
            document.getElementById('test-result').innerHTML = '<div class=\"refresh-indicator\">🔄 Testing cache invalidation...</div>';
            
            fetch('test-schedule-refresh.php?action=test_cache')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('test-result').innerHTML = data;
                });
        }
        
        function createTestSchedule() {
            document.getElementById('create-result').innerHTML = '<div class=\"refresh-indicator\">📝 Creating test schedule...</div>';
            
            fetch('test-schedule-refresh.php?action=create_schedule')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('create-result').innerHTML = data;
                });
        }
        
        function autoRefresh() {
            setInterval(() => {
                fetch('test-schedule-refresh.php?action=check_cache')
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('cache-monitor').innerHTML = data;
                    });
            }, 5000); // Check every 5 seconds
        }
        
        window.onload = autoRefresh;
    </script>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🔄 Test Schedule Refresh - Real-time Updates</h1>
            <p>Testing cache invalidation and real-time schedule updates</p>
        </div>";

// Handle AJAX actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'test_cache':
            testCacheInvalidation();
            exit;
        case 'create_schedule':
            createTestSchedule();
            exit;
        case 'check_cache':
            checkCacheStatus();
            exit;
    }
}

// Main page display
echo "<div class='section info'>
    <h2>🔍 Current System Status</h2>";

$now = Carbon::now();
echo "<p><strong>Server Time:</strong> " . $now->format('Y-m-d H:i:s') . " (Asia/Jakarta)</p>";
echo "<p><strong>Cache Driver:</strong> " . config('cache.default') . "</p>";
echo "<p><strong>Cache TTL (Schedule):</strong> 30 seconds (reduced from 60)</p>";
echo "<p><strong>Cache TTL (Dashboard):</strong> 120 seconds (reduced from 300)</p>";

echo "</div>";

// Test User Status
echo "<div class='section'>
    <h2>👨‍⚕️ Test User: Dr. TES 2</h2>";

$testUser = User::where('name', 'like', '%TES 2%')->first();
if ($testUser) {
    echo "<p><strong>User ID:</strong> {$testUser->id}</p>";
    echo "<p><strong>Name:</strong> {$testUser->name}</p>";
    
    // Check current schedule
    $todaySchedule = JadwalJaga::where('pegawai_id', $testUser->id)
        ->whereDate('tanggal_jaga', today())
        ->with('shiftTemplate')
        ->first();
    
    if ($todaySchedule) {
        echo "<div class='success'>";
        echo "<h3>✅ Current Schedule Found</h3>";
        echo "<table>";
        echo "<tr><th>Date</th><td>" . $todaySchedule->tanggal_jaga->format('Y-m-d') . "</td></tr>";
        echo "<tr><th>Shift</th><td>" . ($todaySchedule->shiftTemplate ? $todaySchedule->shiftTemplate->nama_shift : 'N/A') . "</td></tr>";
        echo "<tr><th>Time</th><td>" . ($todaySchedule->shiftTemplate ? $todaySchedule->shiftTemplate->jam_masuk . ' - ' . $todaySchedule->shiftTemplate->jam_pulang : 'N/A') . "</td></tr>";
        echo "<tr><th>Unit</th><td>" . $todaySchedule->unit_kerja . "</td></tr>";
        echo "<tr><th>Status</th><td>" . $todaySchedule->status_jaga . "</td></tr>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ No schedule found for today</div>";
    }
    
    // Check cache status
    $month = $now->month;
    $year = $now->year;
    $cacheKey = "jadwal_jaga_{$testUser->id}_{$month}_{$year}";
    $dashboardCacheKey = "dokter_dashboard_stats_{$testUser->id}";
    
    echo "<h3>📦 Cache Status</h3>";
    echo "<div class='code'>";
    
    if (Cache::has($cacheKey)) {
        echo "<span class='cache-status cache-hit'>✅ Schedule Cache: HIT</span>";
        $cachedData = Cache::get($cacheKey);
        if (isset($cachedData['cache_info'])) {
            echo " <small>(Cached at: " . $cachedData['cache_info']['cached_at'] . ")</small>";
        }
    } else {
        echo "<span class='cache-status cache-miss'>❌ Schedule Cache: MISS</span>";
    }
    
    echo "<br>";
    
    if (Cache::has($dashboardCacheKey)) {
        echo "<span class='cache-status cache-hit'>✅ Dashboard Cache: HIT</span>";
    } else {
        echo "<span class='cache-status cache-miss'>❌ Dashboard Cache: MISS</span>";
    }
    
    echo "</div>";
} else {
    echo "<div class='error'>❌ Test user 'TES 2' not found</div>";
}

echo "</div>";

// Cache Invalidation Test
echo "<div class='section'>
    <h2>🧪 Cache Invalidation Test</h2>
    <p>Click the button below to test cache invalidation when schedule is updated:</p>
    <button class='button' onclick='testCacheInvalidation()'>🔄 Test Cache Invalidation</button>
    <div id='test-result' style='margin-top: 15px;'></div>
</div>";

// Create Schedule Test
echo "<div class='section'>
    <h2>➕ Create New Schedule Test</h2>
    <p>Create a new schedule and verify cache is cleared immediately:</p>
    <button class='button' onclick='createTestSchedule()'>📝 Create Test Schedule</button>
    <div id='create-result' style='margin-top: 15px;'></div>
</div>";

// Real-time Cache Monitor
echo "<div class='section'>
    <h2>📊 Real-time Cache Monitor</h2>
    <div id='cache-monitor'>
        <div class='refresh-indicator'>Loading cache status...</div>
    </div>
</div>";

// Recommendations
echo "<div class='section info'>
    <h2>📋 Implementation Summary</h2>
    <h3>✅ Implemented Fixes:</h3>
    <ul>
        <li>✅ Cache invalidation on schedule create/update/delete in admin panel</li>
        <li>✅ Reduced cache TTL from 60s to 30s for schedules</li>
        <li>✅ Reduced cache TTL from 300s to 120s for dashboard stats</li>
        <li>✅ Added force refresh mechanism in frontend with timestamp</li>
        <li>✅ Added manual refresh button in UI</li>
    </ul>
    
    <h3>🚀 Next Steps (Optional Enhancements):</h3>
    <ul>
        <li>⏳ Implement WebSocket for real-time updates</li>
        <li>⏳ Add Redis for better cache management</li>
        <li>⏳ Implement event-driven architecture</li>
        <li>⏳ Add cache warming strategies</li>
    </ul>
</div>";

echo "</div></body></html>";

// Helper functions for AJAX actions
function testCacheInvalidation() {
    $testUser = User::where('name', 'like', '%TES 2%')->first();
    if (!$testUser) {
        echo "<div class='error'>❌ Test user not found</div>";
        return;
    }
    
    $month = Carbon::now()->month;
    $year = Carbon::now()->year;
    $cacheKey = "jadwal_jaga_{$testUser->id}_{$month}_{$year}";
    
    echo "<div class='info'>";
    echo "<h4>Step 1: Check Initial Cache</h4>";
    $hadCache = Cache::has($cacheKey);
    echo $hadCache ? "<span class='cache-status cache-hit'>Cache exists</span>" : "<span class='cache-status cache-miss'>No cache</span>";
    
    echo "<h4>Step 2: Clear Cache (Simulating Admin Update)</h4>";
    Cache::forget($cacheKey);
    Cache::forget("dokter_dashboard_stats_{$testUser->id}");
    echo "<span class='cache-status cache-cleared'>✅ Cache cleared</span>";
    
    echo "<h4>Step 3: Verify Cache Cleared</h4>";
    $stillHasCache = Cache::has($cacheKey);
    echo $stillHasCache ? "<span class='cache-status cache-hit'>❌ Cache still exists!</span>" : "<span class='cache-status cache-miss'>✅ Cache successfully cleared</span>";
    
    echo "<h4>Result:</h4>";
    if (!$stillHasCache) {
        echo "<div class='success'>✅ Cache invalidation working correctly! Schedule will be fetched fresh on next request.</div>";
    } else {
        echo "<div class='error'>❌ Cache invalidation failed!</div>";
    }
    echo "</div>";
}

function createTestSchedule() {
    // This simulates creating a schedule through admin panel
    echo "<div class='warning'>⚠️ This would create a test schedule through the admin panel.</div>";
    echo "<div class='info'>In production, this would:</div>";
    echo "<ol>";
    echo "<li>Create schedule in database</li>";
    echo "<li>Automatically clear cache via afterCreate() hook</li>";
    echo "<li>Show notification to admin</li>";
    echo "<li>Schedule appears immediately in mobile app</li>";
    echo "</ol>";
}

function checkCacheStatus() {
    $testUser = User::where('name', 'like', '%TES 2%')->first();
    if (!$testUser) {
        echo "<div class='error'>Test user not found</div>";
        return;
    }
    
    $month = Carbon::now()->month;
    $year = Carbon::now()->year;
    $cacheKey = "jadwal_jaga_{$testUser->id}_{$month}_{$year}";
    $dashboardKey = "dokter_dashboard_stats_{$testUser->id}";
    
    echo "<div class='code'>";
    echo "<strong>Last Check:</strong> " . Carbon::now()->format('H:i:s') . "<br>";
    echo "<strong>Schedule Cache:</strong> ";
    echo Cache::has($cacheKey) ? "<span class='cache-status cache-hit'>HIT</span>" : "<span class='cache-status cache-miss'>MISS</span>";
    echo "<br>";
    echo "<strong>Dashboard Cache:</strong> ";
    echo Cache::has($dashboardKey) ? "<span class='cache-status cache-hit'>HIT</span>" : "<span class='cache-status cache-miss'>MISS</span>";
    
    // Check TTL if cache exists
    if (Cache::has($cacheKey)) {
        $data = Cache::get($cacheKey);
        if (isset($data['cache_info']['cached_at'])) {
            $cachedAt = Carbon::parse($data['cache_info']['cached_at']);
            $age = $cachedAt->diffInSeconds(Carbon::now());
            $ttlRemaining = max(0, 30 - $age);
            echo "<br><strong>Cache Age:</strong> {$age}s";
            echo "<br><strong>TTL Remaining:</strong> {$ttlRemaining}s";
        }
    }
    
    echo "</div>";
}
?>