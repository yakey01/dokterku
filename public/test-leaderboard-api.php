<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Http\Controllers\Api\V2\Dashboards\LeaderboardController;

echo "=== LEADERBOARD API TEST ===\n\n";

try {
    // Test the leaderboard controller directly
    $controller = new LeaderboardController();
    $result = $controller->getTopDoctors();
    $data = json_decode($result->content(), true);
    
    if ($data['success']) {
        echo "✅ Leaderboard API is working!\n\n";
        echo "📊 Current Month: " . $data['data']['month'] . "\n";
        echo "📅 Working Days: " . $data['data']['working_days'] . "\n\n";
        
        echo "🏆 TOP 3 DOCTORS:\n";
        echo str_repeat("=", 60) . "\n";
        
        foreach ($data['data']['leaderboard'] as $doctor) {
            echo "\n" . $doctor['badge'] . " Rank #" . $doctor['rank'] . ": " . $doctor['name'] . "\n";
            echo "   📊 Attendance: " . $doctor['attendance_rate'] . "%\n";
            echo "   🎮 Level: " . $doctor['level'] . "\n";
            echo "   ⭐ XP: " . number_format($doctor['xp']) . "\n";
            echo "   📅 Days Present: " . $doctor['total_days'] . "\n";
            echo "   ⏱️ Total Hours: " . $doctor['total_hours'] . "h\n";
            echo "   🔥 Streak: " . $doctor['streak_days'] . " days\n";
            echo "   🏥 Department: " . $doctor['department'] . "\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Last Updated: " . $data['data']['last_updated'] . "\n";
    } else {
        echo "❌ API returned error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response);