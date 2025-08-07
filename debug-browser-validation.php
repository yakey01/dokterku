<?php
/**
 * Debug Browser Validation Issue
 * Tests the validation endpoint to simulate browser behavior
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Create a request that simulates the browser
$request = Request::create('/api/v2/jadwal-jaga/validate-checkin', 'POST', [
    'latitude' => -6.91750000,
    'longitude' => 107.61910000,
    'accuracy' => 15,
    'date' => '2025-08-06'
], [], [], [
    'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Safari/605.1.15',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_CONTENT_TYPE' => 'application/json',
]);

// Test with both users
echo "=== Testing Dr. Yaya (ID: 13) ===\n";
// Simulate login as user 13
auth()->loginUsingId(13);
$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getContent() . "\n\n";

echo "=== Testing Dr. Rindang (ID: 14) ===\n";
// Simulate login as user 14
auth()->loginUsingId(14);
$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getContent() . "\n\n";

// Test with slightly outside location to simulate the geofence issue
echo "=== Testing Outside Geofence (Dr. Rindang) ===\n";
$request = Request::create('/api/v2/jadwal-jaga/validate-checkin', 'POST', [
    'latitude' => -6.9158800000000005, // This coordinate was in the logs
    'longitude' => 107.6191,
    'accuracy' => 15,
    'date' => '2025-08-06'
], [], [], [
    'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Safari/605.1.15',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_CONTENT_TYPE' => 'application/json',
]);

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getContent() . "\n\n";

// Test the exact coordinates from the log (where Dr. Rindang was trying to check in)
echo "=== Testing Dr. Rindang's Original Location ===\n";
$request = Request::create('/api/v2/jadwal-jaga/validate-checkin', 'POST', [
    'latitude' => -7.899622447434555, // From the logs
    'longitude' => 111.96282957789202,
    'accuracy' => 66.66666666666667,
    'date' => '2025-08-06'
], [], [], [
    'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Safari/605.1.15',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_CONTENT_TYPE' => 'application/json',
]);

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Response: " . $response->getContent() . "\n";