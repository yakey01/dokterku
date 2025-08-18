<?php
require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\V2\Dashboards\DokterDashboardController;
use Illuminate\Http\Request;

echo "=== TESTING LEADERBOARD DIRECT ===\n\n";

// Login as dr. Yaya
$yayaUser = User::find(13);
Auth::login($yayaUser);

echo "User: {$yayaUser->name} (ID: {$yayaUser->id})\n\n";

try {
    $controller = new DokterDashboardController();
    $request = new Request();
    $request->merge(['month' => 8, 'year' => 2025]);
    
    echo "Calling leaderboard method...\n";
    $response = $controller->leaderboard($request);
    $data = json_decode($response->getContent(), true);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response data:\n";
    print_r($data);
    
    if (isset($data['success']) && $data['success']) {
        $leaderboard = $data['data']['leaderboard'] ?? [];
        echo "\n✅ SUCCESS - Leaderboard Data:\n";
        echo "  - Total Doctors: " . count($leaderboard) . "\n\n";
        
        if (count($leaderboard) > 0) {
            foreach ($leaderboard as $index => $doctor) {
                echo "  Doctor #" . ($index + 1) . ":\n";
                echo "    - ID: " . ($doctor['id'] ?? 'N/A') . "\n";
                echo "    - Name: " . ($doctor['name'] ?? 'N/A') . "\n";
                echo "    - Rank: " . ($doctor['rank'] ?? 'N/A') . "\n";
                echo "    - Attendance Rate: " . ($doctor['attendance_rate'] ?? 'N/A') . "%\n";
                echo "    - Total Patients: " . ($doctor['total_patients'] ?? 'N/A') . "\n";
                echo "    - Procedures Count: " . ($doctor['procedures_count'] ?? 'N/A') . "\n";
                echo "\n";
                
                if ($doctor['id'] == $yayaUser->id) {
                    echo "    🎯 THIS IS DR. YAYA!\n\n";
                }
            }
        } else {
            echo "  ❌ No doctors found in leaderboard\n";
        }
    } else {
        echo "❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}