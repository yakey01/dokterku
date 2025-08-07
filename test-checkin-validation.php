<?php
/**
 * Comprehensive Check-in Validation Test Script
 * 
 * Tests the check-in API endpoint with various scenarios to validate
 * the work location assignment fix for Dr. Yaya Mulyana.
 */

require_once __DIR__ . '/vendor/autoload.php';

class CheckinValidationTester
{
    private string $baseUrl;
    private ?string $apiToken = null;
    private array $testResults = [];
    
    // Test user: Dr. Yaya Mulyana (ID: 13)
    private int $userId = 13;
    
    // Cabang Bandung coordinates: -6.91750000, 107.61910000
    private float $workLatitude = -6.91750000;
    private float $workLongitude = 107.61910000;
    private int $radiusMeters = 150;
    
    public function __construct()
    {
        $this->baseUrl = 'https://dokterku.test';
        $this->apiToken = $this->getApiToken();
        
        echo "🚀 Starting Check-in Validation Tests\n";
        echo "📍 Work Location: Cabang Bandung ({$this->workLatitude}, {$this->workLongitude})\n";
        echo "📏 Allowed Radius: {$this->radiusMeters}m\n";
        echo "👤 Test User: Dr. Yaya Mulyana (ID: {$this->userId})\n";
        echo str_repeat('=', 80) . "\n\n";
    }
    
    private function getApiToken(): string
    {
        // Get authentication token for Dr. Yaya
        $loginData = [
            'login' => 'yaya',
            'password' => 'password',
            'device_id' => 'test-device-' . time()
        ];
        
        $response = $this->makeRequest('POST', '/api/v2/auth/login', $loginData);
        
        if (!$response['success']) {
            die("❌ Failed to authenticate: " . $response['message'] . "\n");
        }
        
        echo "✅ Authentication successful\n";
        return $response['data']['token'];
    }
    
    private function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();
        
        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($this->apiToken) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $this->apiToken;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'message' => "cURL Error: $error",
                'http_code' => 0
            ];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $decoded['data'] ?? null,
            'message' => $decoded['message'] ?? 'Unknown response',
            'raw_response' => $decoded
        ];
    }
    
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    private function generateNearbyCoordinates(float $lat, float $lon, float $distanceMeters): array
    {
        // Generate coordinates within specified distance
        $earthRadius = 6371000; // Earth's radius in meters
        
        // Random angle
        $angle = mt_rand(0, 360) * (M_PI / 180);
        
        // Convert distance to degrees (approximate)
        $dLat = ($distanceMeters / $earthRadius) * (180 / M_PI);
        $dLon = ($distanceMeters / $earthRadius) * (180 / M_PI) / cos($lat * M_PI / 180);
        
        $newLat = $lat + ($dLat * cos($angle));
        $newLon = $lon + ($dLon * sin($angle));
        
        return [$newLat, $newLon];
    }
    
    private function generateFarCoordinates(float $lat, float $lon, float $minDistanceMeters = 1000): array
    {
        // Generate coordinates far from the work location
        $earthRadius = 6371000; // Earth's radius in meters
        
        // Random distance between minDistance and minDistance * 2
        $distance = mt_rand($minDistanceMeters, $minDistanceMeters * 2);
        $angle = mt_rand(0, 360) * (M_PI / 180);
        
        $dLat = ($distance / $earthRadius) * (180 / M_PI);
        $dLon = ($distance / $earthRadius) * (180 / M_PI) / cos($lat * M_PI / 180);
        
        $newLat = $lat + ($dLat * cos($angle));
        $newLon = $lon + ($dLon * sin($angle));
        
        return [$newLat, $newLon];
    }
    
    public function runAllTests(): void
    {
        echo "🔍 Running comprehensive check-in validation tests...\n\n";
        
        // Test 1: Valid check-in within geofence
        $this->testValidCheckinWithinGeofence();
        
        // Test 2: Valid check-in at exact work location
        $this->testValidCheckinAtExactLocation();
        
        // Test 3: Invalid check-in outside geofence (near boundary)
        $this->testInvalidCheckinNearBoundary();
        
        // Test 4: Invalid check-in far from work location
        $this->testInvalidCheckinFarLocation();
        
        // Test 5: Invalid coordinates (out of range)
        $this->testInvalidCoordinatesOutOfRange();
        
        // Test 6: Invalid coordinates (zero values)
        $this->testInvalidCoordinatesZeroValues();
        
        // Test 7: Missing required fields
        $this->testMissingRequiredFields();
        
        // Test 8: Invalid GPS accuracy
        $this->testInvalidGpsAccuracy();
        
        // Test 9: Future date validation
        $this->testFutureDateValidation();
        
        // Test 10: Past date validation
        $this->testPastDateValidation();
        
        $this->printSummary();
    }
    
    private function testValidCheckinWithinGeofence(): void
    {
        echo "📋 Test 1: Valid check-in within geofence (50m from work location)\n";
        
        // Generate coordinates 50m from work location (well within 150m radius)
        [$testLat, $testLon] = $this->generateNearbyCoordinates($this->workLatitude, $this->workLongitude, 50);
        $actualDistance = $this->calculateDistance($this->workLatitude, $this->workLongitude, $testLat, $testLon);
        
        $requestData = [
            'latitude' => $testLat,
            'longitude' => $testLon,
            'accuracy' => 15.0,
            'date' => date('Y-m-d')
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedValid = true;
        $actualValid = $response['raw_response']['data']['validation']['valid'] ?? false;
        
        $this->recordTest('Valid Check-in Within Geofence', [
            'coordinates' => "({$testLat}, {$testLon})",
            'distance_from_work' => round($actualDistance) . 'm',
            'expected_valid' => $expectedValid,
            'actual_valid' => $actualValid,
            'http_code' => $response['http_code'],
            'validation_message' => $response['raw_response']['data']['validation']['message'] ?? 'N/A',
            'passed' => $actualValid === $expectedValid && $response['http_code'] === 200
        ]);
        
        echo "  📍 Test coordinates: ({$testLat}, {$testLon})\n";
        echo "  📏 Distance from work: " . round($actualDistance) . "m\n";
        echo "  🎯 Expected: Valid = {$expectedValid}\n";
        echo "  📊 Result: Valid = {$actualValid}, HTTP: {$response['http_code']}\n";
        echo "  💬 Message: " . ($response['raw_response']['data']['validation']['message'] ?? 'N/A') . "\n";
        echo "  " . ($actualValid === $expectedValid ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function testValidCheckinAtExactLocation(): void
    {
        echo "📋 Test 2: Valid check-in at exact work location\n";
        
        $requestData = [
            'latitude' => $this->workLatitude,
            'longitude' => $this->workLongitude,
            'accuracy' => 10.0,
            'date' => date('Y-m-d')
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedValid = true;
        $actualValid = $response['raw_response']['data']['validation']['valid'] ?? false;
        
        $this->recordTest('Valid Check-in At Exact Location', [
            'coordinates' => "({$this->workLatitude}, {$this->workLongitude})",
            'distance_from_work' => '0m',
            'expected_valid' => $expectedValid,
            'actual_valid' => $actualValid,
            'http_code' => $response['http_code'],
            'validation_message' => $response['raw_response']['data']['validation']['message'] ?? 'N/A',
            'passed' => $actualValid === $expectedValid && $response['http_code'] === 200
        ]);
        
        echo "  📍 Test coordinates: ({$this->workLatitude}, {$this->workLongitude})\n";
        echo "  📏 Distance from work: 0m (exact location)\n";
        echo "  🎯 Expected: Valid = {$expectedValid}\n";
        echo "  📊 Result: Valid = {$actualValid}, HTTP: {$response['http_code']}\n";
        echo "  💬 Message: " . ($response['raw_response']['data']['validation']['message'] ?? 'N/A') . "\n";
        echo "  " . ($actualValid === $expectedValid ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function testInvalidCheckinNearBoundary(): void
    {
        echo "📋 Test 3: Invalid check-in near boundary (200m from work location)\n";
        
        // Generate coordinates 200m from work location (outside 150m radius)
        [$testLat, $testLon] = $this->generateNearbyCoordinates($this->workLatitude, $this->workLongitude, 200);
        $actualDistance = $this->calculateDistance($this->workLatitude, $this->workLongitude, $testLat, $testLon);
        
        $requestData = [
            'latitude' => $testLat,
            'longitude' => $testLon,
            'accuracy' => 15.0,
            'date' => date('Y-m-d')
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedValid = false;
        $actualValid = $response['raw_response']['data']['validation']['valid'] ?? true;
        
        $this->recordTest('Invalid Check-in Near Boundary', [
            'coordinates' => "({$testLat}, {$testLon})",
            'distance_from_work' => round($actualDistance) . 'm',
            'expected_valid' => $expectedValid,
            'actual_valid' => $actualValid,
            'http_code' => $response['http_code'],
            'validation_message' => $response['raw_response']['data']['validation']['message'] ?? 'N/A',
            'passed' => $actualValid === $expectedValid && $response['http_code'] === 400
        ]);
        
        echo "  📍 Test coordinates: ({$testLat}, {$testLon})\n";
        echo "  📏 Distance from work: " . round($actualDistance) . "m\n";
        echo "  🎯 Expected: Valid = {$expectedValid}\n";
        echo "  📊 Result: Valid = {$actualValid}, HTTP: {$response['http_code']}\n";
        echo "  💬 Message: " . ($response['raw_response']['data']['validation']['message'] ?? 'N/A') . "\n";
        echo "  " . ($actualValid === $expectedValid ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function testInvalidCheckinFarLocation(): void
    {
        echo "📋 Test 4: Invalid check-in far from work location\n";
        
        // Generate coordinates far from work location (1-2km away)
        [$testLat, $testLon] = $this->generateFarCoordinates($this->workLatitude, $this->workLongitude, 1000);
        $actualDistance = $this->calculateDistance($this->workLatitude, $this->workLongitude, $testLat, $testLon);
        
        $requestData = [
            'latitude' => $testLat,
            'longitude' => $testLon,
            'accuracy' => 20.0,
            'date' => date('Y-m-d')
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedValid = false;
        $actualValid = $response['raw_response']['data']['validation']['valid'] ?? true;
        
        $this->recordTest('Invalid Check-in Far Location', [
            'coordinates' => "({$testLat}, {$testLon})",
            'distance_from_work' => round($actualDistance) . 'm',
            'expected_valid' => $expectedValid,
            'actual_valid' => $actualValid,
            'http_code' => $response['http_code'],
            'validation_message' => $response['raw_response']['data']['validation']['message'] ?? 'N/A',
            'passed' => $actualValid === $expectedValid && $response['http_code'] === 400
        ]);
        
        echo "  📍 Test coordinates: ({$testLat}, {$testLon})\n";
        echo "  📏 Distance from work: " . round($actualDistance) . "m\n";
        echo "  🎯 Expected: Valid = {$expectedValid}\n";
        echo "  📊 Result: Valid = {$actualValid}, HTTP: {$response['http_code']}\n";
        echo "  💬 Message: " . ($response['raw_response']['data']['validation']['message'] ?? 'N/A') . "\n";
        echo "  " . ($actualValid === $expectedValid ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function testInvalidCoordinatesOutOfRange(): void
    {
        echo "📋 Test 5: Invalid coordinates (out of range)\n";
        
        $requestData = [
            'latitude' => 95.0, // Invalid: > 90
            'longitude' => -185.0, // Invalid: < -180
            'accuracy' => 15.0,
            'date' => date('Y-m-d')
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedHttpCode = 422; // Validation error
        $actualHttpCode = $response['http_code'];
        
        $this->recordTest('Invalid Coordinates Out of Range', [
            'coordinates' => "(95.0, -185.0)",
            'expected_http_code' => $expectedHttpCode,
            'actual_http_code' => $actualHttpCode,
            'validation_errors' => $response['raw_response']['errors'] ?? 'N/A',
            'passed' => $actualHttpCode === $expectedHttpCode
        ]);
        
        echo "  📍 Test coordinates: (95.0, -185.0)\n";
        echo "  🎯 Expected: HTTP = {$expectedHttpCode}\n";
        echo "  📊 Result: HTTP = {$actualHttpCode}\n";
        echo "  ⚠️  Validation errors: " . json_encode($response['raw_response']['errors'] ?? 'N/A') . "\n";
        echo "  " . ($actualHttpCode === $expectedHttpCode ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function testInvalidCoordinatesZeroValues(): void
    {
        echo "📋 Test 6: Invalid coordinates (suspicious zero values)\n";
        
        $requestData = [
            'latitude' => 0.0,
            'longitude' => 0.0,
            'accuracy' => 15.0,
            'date' => date('Y-m-d')
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedHttpCode = 400; // Should reject suspicious coordinates
        $actualHttpCode = $response['http_code'];
        
        $this->recordTest('Invalid Coordinates Zero Values', [
            'coordinates' => "(0.0, 0.0)",
            'expected_http_code' => $expectedHttpCode,
            'actual_http_code' => $actualHttpCode,
            'message' => $response['raw_response']['message'] ?? 'N/A',
            'passed' => $actualHttpCode === $expectedHttpCode
        ]);
        
        echo "  📍 Test coordinates: (0.0, 0.0)\n";
        echo "  🎯 Expected: HTTP = {$expectedHttpCode}\n";
        echo "  📊 Result: HTTP = {$actualHttpCode}\n";
        echo "  💬 Message: " . ($response['raw_response']['message'] ?? 'N/A') . "\n";
        echo "  " . ($actualHttpCode === $expectedHttpCode ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function testMissingRequiredFields(): void
    {
        echo "📋 Test 7: Missing required fields\n";
        
        // Test with missing latitude
        $requestData = [
            'longitude' => $this->workLongitude,
            'accuracy' => 15.0,
            'date' => date('Y-m-d')
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedHttpCode = 422; // Validation error
        $actualHttpCode = $response['http_code'];
        
        $this->recordTest('Missing Required Fields', [
            'missing_field' => 'latitude',
            'expected_http_code' => $expectedHttpCode,
            'actual_http_code' => $actualHttpCode,
            'validation_errors' => $response['raw_response']['errors'] ?? 'N/A',
            'passed' => $actualHttpCode === $expectedHttpCode
        ]);
        
        echo "  🚫 Missing field: latitude\n";
        echo "  🎯 Expected: HTTP = {$expectedHttpCode}\n";
        echo "  📊 Result: HTTP = {$actualHttpCode}\n";
        echo "  ⚠️  Validation errors: " . json_encode($response['raw_response']['errors'] ?? 'N/A') . "\n";
        echo "  " . ($actualHttpCode === $expectedHttpCode ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function testInvalidGpsAccuracy(): void
    {
        echo "📋 Test 8: Invalid GPS accuracy (too high)\n";
        
        $requestData = [
            'latitude' => $this->workLatitude,
            'longitude' => $this->workLongitude,
            'accuracy' => 1500.0, // Too high (max is 1000m)
            'date' => date('Y-m-d')
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedHttpCode = 422; // Validation error
        $actualHttpCode = $response['http_code'];
        
        $this->recordTest('Invalid GPS Accuracy', [
            'accuracy' => '1500.0m',
            'expected_http_code' => $expectedHttpCode,
            'actual_http_code' => $actualHttpCode,
            'validation_errors' => $response['raw_response']['errors'] ?? 'N/A',
            'passed' => $actualHttpCode === $expectedHttpCode
        ]);
        
        echo "  📡 Test accuracy: 1500.0m (exceeds 1000m limit)\n";
        echo "  🎯 Expected: HTTP = {$expectedHttpCode}\n";
        echo "  📊 Result: HTTP = {$actualHttpCode}\n";
        echo "  ⚠️  Validation errors: " . json_encode($response['raw_response']['errors'] ?? 'N/A') . "\n";
        echo "  " . ($actualHttpCode === $expectedHttpCode ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function testFutureDateValidation(): void
    {
        echo "📋 Test 9: Future date validation\n";
        
        $futureDate = date('Y-m-d', strtotime('+7 days'));
        
        $requestData = [
            'latitude' => $this->workLatitude,
            'longitude' => $this->workLongitude,
            'accuracy' => 15.0,
            'date' => $futureDate
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedValid = false; // Should not allow future dates
        $actualValid = $response['raw_response']['data']['validation']['valid'] ?? true;
        
        $this->recordTest('Future Date Validation', [
            'test_date' => $futureDate,
            'expected_valid' => $expectedValid,
            'actual_valid' => $actualValid,
            'http_code' => $response['http_code'],
            'validation_message' => $response['raw_response']['data']['validation']['message'] ?? 'N/A',
            'passed' => $actualValid === $expectedValid || $response['http_code'] === 400
        ]);
        
        echo "  📅 Test date: {$futureDate} (7 days in future)\n";
        echo "  🎯 Expected: Valid = {$expectedValid} or HTTP 400\n";
        echo "  📊 Result: Valid = {$actualValid}, HTTP: {$response['http_code']}\n";
        echo "  💬 Message: " . ($response['raw_response']['data']['validation']['message'] ?? 'N/A') . "\n";
        echo "  " . (($actualValid === $expectedValid || $response['http_code'] === 400) ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function testPastDateValidation(): void
    {
        echo "📋 Test 10: Past date validation\n";
        
        $pastDate = date('Y-m-d', strtotime('-7 days'));
        
        $requestData = [
            'latitude' => $this->workLatitude,
            'longitude' => $this->workLongitude,
            'accuracy' => 15.0,
            'date' => $pastDate
        ];
        
        $response = $this->makeRequest('POST', '/api/jadwal-jaga/validate-checkin', $requestData);
        
        $expectedValid = false; // Should not allow past dates (no schedule)
        $actualValid = $response['raw_response']['data']['validation']['valid'] ?? true;
        
        $this->recordTest('Past Date Validation', [
            'test_date' => $pastDate,
            'expected_valid' => $expectedValid,
            'actual_valid' => $actualValid,
            'http_code' => $response['http_code'],
            'validation_message' => $response['raw_response']['data']['validation']['message'] ?? 'N/A',
            'passed' => $actualValid === $expectedValid || $response['http_code'] === 400
        ]);
        
        echo "  📅 Test date: {$pastDate} (7 days ago)\n";
        echo "  🎯 Expected: Valid = {$expectedValid} or HTTP 400\n";
        echo "  📊 Result: Valid = {$actualValid}, HTTP: {$response['http_code']}\n";
        echo "  💬 Message: " . ($response['raw_response']['data']['validation']['message'] ?? 'N/A') . "\n";
        echo "  " . (($actualValid === $expectedValid || $response['http_code'] === 400) ? "✅ PASSED" : "❌ FAILED") . "\n\n";
    }
    
    private function recordTest(string $testName, array $details): void
    {
        $this->testResults[] = [
            'name' => $testName,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function printSummary(): void
    {
        echo str_repeat('=', 80) . "\n";
        echo "📊 TEST SUMMARY\n";
        echo str_repeat('=', 80) . "\n";
        
        $totalTests = count($this->testResults);
        $passedTests = 0;
        $failedTests = [];
        
        foreach ($this->testResults as $result) {
            if ($result['details']['passed']) {
                $passedTests++;
                echo "✅ {$result['name']}: PASSED\n";
            } else {
                $failedTests[] = $result;
                echo "❌ {$result['name']}: FAILED\n";
            }
        }
        
        echo "\n";
        echo "📈 Overall Results:\n";
        echo "  • Total Tests: {$totalTests}\n";
        echo "  • Passed: {$passedTests}\n";
        echo "  • Failed: " . count($failedTests) . "\n";
        echo "  • Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";
        
        if (!empty($failedTests)) {
            echo "🔍 Failed Test Details:\n";
            echo str_repeat('-', 40) . "\n";
            
            foreach ($failedTests as $failed) {
                echo "❌ {$failed['name']}:\n";
                foreach ($failed['details'] as $key => $value) {
                    if ($key !== 'passed') {
                        echo "   {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
                    }
                }
                echo "\n";
            }
        }
        
        if ($passedTests === $totalTests) {
            echo "🎉 ALL TESTS PASSED! The check-in validation fix is working correctly.\n";
        } else {
            echo "⚠️  Some tests failed. Please review the implementation.\n";
        }
        
        echo "\n📋 Test completed at: " . date('Y-m-d H:i:s') . "\n";
    }
}

// Run the tests
try {
    $tester = new CheckinValidationTester();
    $tester->runAllTests();
} catch (Exception $e) {
    echo "❌ Test execution failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}