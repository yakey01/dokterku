<?php
/**
 * Check-in Validation Test with Generated Token
 */

// API Token for Dr. Yaya Mulyana
$apiToken = '151|5MRw8Qva9igm48Ap9q5Ale07iQqCJpYMFYSuihZw25ea6966';

// Work location details
$workLatitude = -6.91750000;
$workLongitude = 107.61910000;
$radiusMeters = 150;

echo "🚀 Check-in Validation Test\n";
echo str_repeat('=', 60) . "\n";
echo "📍 Work Location: Cabang Bandung ({$workLatitude}, {$workLongitude})\n";
echo "📏 Allowed Radius: {$radiusMeters}m\n";
echo "👤 Test User: Dr. Yaya Mulyana\n";
echo "🔑 Using Token: " . substr($apiToken, 0, 20) . "...\n\n";

function testApiCall($endpoint, $data, $token) {
    $url = "https://dokterku.test{$endpoint}";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
        'raw_response' => $response
    ];
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

// Test scenarios
$tests = [
    [
        'name' => 'Test 1: Valid check-in at exact work location',
        'data' => [
            'latitude' => $workLatitude,
            'longitude' => $workLongitude,
            'accuracy' => 10.0,
            'date' => date('Y-m-d')
        ],
        'expected_valid' => true,
        'expected_http' => 200
    ],
    [
        'name' => 'Test 2: Valid check-in near work location (50m away)',
        'data' => [
            'latitude' => $workLatitude + 0.0005, // ~55m north
            'longitude' => $workLongitude,
            'accuracy' => 15.0,
            'date' => date('Y-m-d')
        ],
        'expected_valid' => true,
        'expected_http' => 200
    ],
    [
        'name' => 'Test 3: Invalid check-in outside geofence (300m away)',
        'data' => [
            'latitude' => $workLatitude + 0.003, // ~333m north
            'longitude' => $workLongitude,
            'accuracy' => 15.0,
            'date' => date('Y-m-d')
        ],
        'expected_valid' => false,
        'expected_http' => 400
    ],
    [
        'name' => 'Test 4: Invalid coordinates (latitude out of range)',
        'data' => [
            'latitude' => 95.0, // Invalid: > 90
            'longitude' => $workLongitude,
            'accuracy' => 15.0,
            'date' => date('Y-m-d')
        ],
        'expected_valid' => false,
        'expected_http' => 422
    ],
    [
        'name' => 'Test 5: Suspicious zero coordinates',
        'data' => [
            'latitude' => 0.0,
            'longitude' => 0.0,
            'accuracy' => 15.0,
            'date' => date('Y-m-d')
        ],
        'expected_valid' => false,
        'expected_http' => 400
    ]
];

$results = [];

foreach ($tests as $test) {
    echo "📋 {$test['name']}\n";
    
    $distance = calculateDistance($workLatitude, $workLongitude, $test['data']['latitude'], $test['data']['longitude']);
    echo "📍 Coordinates: ({$test['data']['latitude']}, {$test['data']['longitude']})\n";
    echo "📏 Distance from work: " . round($distance) . "m\n";
    
    $result = testApiCall('/api/v2/jadwal-jaga/validate-checkin', $test['data'], $apiToken);
    
    if (isset($result['error'])) {
        echo "❌ cURL Error: {$result['error']}\n\n";
        continue;
    }
    
    $httpCode = $result['http_code'];
    $response = $result['response'];
    
    echo "📊 HTTP Response: {$httpCode}\n";
    echo "📄 Response: " . substr($result['raw_response'], 0, 200) . "...\n";
    
    if ($httpCode === 200 || $httpCode === 400) {
        $isValid = $response['data']['validation']['valid'] ?? false;
        $message = $response['data']['validation']['message'] ?? 'N/A';
        echo "✅ Valid: " . ($isValid ? 'true' : 'false') . "\n";
        echo "💬 Message: {$message}\n";
        
        $testPassed = ($isValid === $test['expected_valid'] && $httpCode === $test['expected_http']);
    } else {
        $testPassed = ($httpCode === $test['expected_http']);
    }
    
    if ($testPassed) {
        echo "🎉 TEST PASSED\n";
        $results[] = true;
    } else {
        echo "❌ TEST FAILED\n";
        echo "   Expected HTTP: {$test['expected_http']}, Got: {$httpCode}\n";
        if (isset($isValid)) {
            echo "   Expected Valid: " . ($test['expected_valid'] ? 'true' : 'false') . ", Got: " . ($isValid ? 'true' : 'false') . "\n";
        }
        $results[] = false;
    }
    
    echo "\n" . str_repeat('-', 60) . "\n\n";
}

// Summary
$passed = count(array_filter($results));
$total = count($results);

echo "📊 FINAL SUMMARY\n";
echo str_repeat('=', 60) . "\n";
echo "Total Tests: {$total}\n";
echo "Passed: {$passed}\n";
echo "Failed: " . ($total - $passed) . "\n";
echo "Success Rate: " . round(($passed / $total) * 100, 1) . "%\n\n";

if ($passed === $total) {
    echo "🎉 ALL TESTS PASSED! Check-in validation is working correctly.\n";
} else {
    echo "⚠️ Some tests failed. Check the implementation.\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";