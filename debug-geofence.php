<?php
/**
 * Debug Geofence Calculation
 */

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$workLocation = App\Models\WorkLocation::find(4); // Cabang Bandung
$workLat = -6.91750000;
$workLon = 107.61910000;

// Test coordinate that should be outside (160m)
$testLat = -6.91606;
$testLon = 107.6191;
$accuracy = 15.0;

echo "🔍 Geofence Debug Test\n";
echo str_repeat('=', 50) . "\n";
echo "Work Location: {$workLocation->name}\n";
echo "Work Coordinates: ({$workLat}, {$workLon})\n";
echo "Radius: {$workLocation->radius_meters}m\n";
echo "Test Coordinates: ({$testLat}, {$testLon})\n";
echo "GPS Accuracy: {$accuracy}m\n\n";

// Calculate distance using our function
function calculateTestDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

$testDistance = calculateTestDistance($workLat, $workLon, $testLat, $testLon);
echo "📏 Test Distance Calculation: " . round($testDistance, 2) . "m\n";

$modelDistance = $workLocation->calculateDistance($testLat, $testLon);
echo "📏 Model Distance Calculation: " . round($modelDistance, 2) . "m\n";

$isWithinGeofence = $workLocation->isWithinGeofence($testLat, $testLon, $accuracy);
echo "✅ Is within geofence (with accuracy): " . ($isWithinGeofence ? 'YES' : 'NO') . "\n";

$isWithinGeofenceNoAccuracy = $workLocation->isWithinGeofence($testLat, $testLon, null);
echo "✅ Is within geofence (no accuracy): " . ($isWithinGeofenceNoAccuracy ? 'YES' : 'NO') . "\n";

// Show calculation details
$effectiveRadius = $workLocation->radius_meters + min($accuracy, 50);
echo "\n📊 Calculation Details:\n";
echo "• Base radius: {$workLocation->radius_meters}m\n";
echo "• GPS accuracy tolerance: " . min($accuracy, 50) . "m\n";
echo "• Effective radius: {$effectiveRadius}m\n";
echo "• Distance: " . round($modelDistance, 2) . "m\n";
echo "• Within geofence: " . ($modelDistance <= $effectiveRadius ? 'YES' : 'NO') . "\n";

// Test with different coordinates to find the actual boundary
echo "\n🎯 Testing actual boundary...\n";
for ($offset = 0.0013; $offset <= 0.0017; $offset += 0.0001) {
    $testLat2 = $workLat + $offset;
    $distance2 = $workLocation->calculateDistance($testLat2, $workLon);
    $withinGeofence2 = $workLocation->isWithinGeofence($testLat2, $workLon, $accuracy);
    echo "Offset: " . number_format($offset, 4) . " | Distance: " . round($distance2) . "m | Within: " . ($withinGeofence2 ? 'YES' : 'NO') . "\n";
}