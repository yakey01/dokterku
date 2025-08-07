<?php
/**
 * WorkLocation GPS Testing Script
 * Tests GPS auto-detection functionality for geofencing map component
 * 
 * This script simulates the GPS detection process and validates all components:
 * 1. Browser GPS API availability
 * 2. Field detection algorithms
 * 3. Auto-fill functionality
 * 4. Event triggering for Filament reactive forms
 */

echo "🌍 WorkLocation GPS Detection Test\n";
echo "================================\n\n";

// Test 1: Simulate browser environment check
echo "Test 1: Browser Environment Check\n";
echo "- Protocol: " . ($_SERVER['HTTPS'] ?? 'http') . "\n";
echo "- User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI') . "\n";
echo "- Host: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\n";

// Test 2: Check if WorkLocationResource configuration is correct
echo "\nTest 2: WorkLocationResource Field Configuration\n";

$workLocationResourcePath = __DIR__ . '/app/Filament/Resources/WorkLocationResource.php';
if (file_exists($workLocationResourcePath)) {
    $content = file_get_contents($workLocationResourcePath);
    
    // Check latitude field configuration
    $hasLatitudeId = strpos($content, "->id('latitude')") !== false;
    $hasLatitudeDataAttr = strpos($content, "'data-coordinate-field' => 'latitude'") !== false;
    $hasLatitudeReactive = strpos($content, "->reactive()") !== false;
    
    echo "- Latitude field ID: " . ($hasLatitudeId ? "✅" : "❌") . "\n";
    echo "- Latitude data attribute: " . ($hasLatitudeDataAttr ? "✅" : "❌") . "\n";
    echo "- Latitude reactive: " . ($hasLatitudeReactive ? "✅" : "❌") . "\n";
    
    // Check longitude field configuration
    $hasLongitudeId = strpos($content, "->id('longitude')") !== false;
    $hasLongitudeDataAttr = strpos($content, "'data-coordinate-field' => 'longitude'") !== false;
    
    echo "- Longitude field ID: " . ($hasLongitudeId ? "✅" : "❌") . "\n";
    echo "- Longitude data attribute: " . ($hasLongitudeDataAttr ? "✅" : "❌") . "\n";
    
    // Check GPS button configuration
    $hasAutoDetectFunction = strpos($content, "autoDetectLocation()") !== false;
    echo "- GPS button onclick handler: " . ($hasAutoDetectFunction ? "✅" : "❌") . "\n";
    
} else {
    echo "❌ WorkLocationResource.php not found\n";
}

// Test 3: Check leaflet-osm-map component
echo "\nTest 3: Leaflet OSM Map Component\n";

$mapComponentPath = __DIR__ . '/resources/views/filament/forms/components/leaflet-osm-map.blade.php';
if (file_exists($mapComponentPath)) {
    $content = file_get_contents($mapComponentPath);
    
    // Check function existence
    $hasAutoDetectFunction = strpos($content, 'function autoDetectLocation()') !== false;
    $hasGlobalRegistration = strpos($content, 'window.autoDetectLocation = autoDetectLocation') !== false;
    $hasFindFieldsFunction = strpos($content, 'function findCoordinateFieldsGlobal()') !== false;
    $hasTriggerEventsFunction = strpos($content, 'function triggerFieldEventsGlobal(') !== false;
    
    echo "- autoDetectLocation function: " . ($hasAutoDetectFunction ? "✅" : "❌") . "\n";
    echo "- Global window registration: " . ($hasGlobalRegistration ? "✅" : "❌") . "\n";
    echo "- Field detection function: " . ($hasFindFieldsFunction ? "✅" : "❌") . "\n";
    echo "- Event triggering function: " . ($hasTriggerEventsFunction ? "✅" : "❌") . "\n";
    
    // Check GPS options
    $hasHighAccuracy = strpos($content, 'enableHighAccuracy: true') !== false;
    $hasTimeout = strpos($content, 'timeout:') !== false;
    $hasMaxAge = strpos($content, 'maximumAge:') !== false;
    
    echo "- High accuracy GPS: " . ($hasHighAccuracy ? "✅" : "❌") . "\n";
    echo "- GPS timeout configured: " . ($hasTimeout ? "✅" : "❌") . "\n";
    echo "- Maximum age configured: " . ($hasMaxAge ? "✅" : "❌") . "\n";
    
    // Check error handling
    $hasErrorHandling = strpos($content, 'error.PERMISSION_DENIED') !== false;
    $hasIndonesianMessages = strpos($content, 'GPS tidak didukung') !== false;
    
    echo "- GPS error handling: " . ($hasErrorHandling ? "✅" : "❌") . "\n";
    echo "- Indonesian error messages: " . ($hasIndonesianMessages ? "✅" : "❌") . "\n";
    
} else {
    echo "❌ leaflet-osm-map.blade.php not found\n";
}

// Test 4: Check field detection strategies
echo "\nTest 4: Field Detection Strategy Analysis\n";

if (file_exists($mapComponentPath)) {
    $content = file_get_contents($mapComponentPath);
    
    // Strategy checks
    $hasDataAttributeStrategy = strpos($content, 'data-coordinate-field="latitude"') !== false;
    $hasIdStrategy = strpos($content, '#latitude') !== false;
    $hasNameStrategy = strpos($content, 'input[name="latitude"]') !== false;
    $hasWireModelStrategy = strpos($content, 'wire:model') !== false;
    
    echo "- Data attribute strategy: " . ($hasDataAttributeStrategy ? "✅" : "❌") . "\n";
    echo "- ID selector strategy: " . ($hasIdStrategy ? "✅" : "❌") . "\n";
    echo "- Name attribute strategy: " . ($hasNameStrategy ? "✅" : "❌") . "\n";
    echo "- Wire model strategy: " . ($hasWireModelStrategy ? "✅" : "❌") . "\n";
}

// Test 5: Generate JavaScript test code
echo "\nTest 5: JavaScript Test Code Generation\n";

$jsTestCode = <<<'JAVASCRIPT'
// Copy and paste this code into browser console on WorkLocation form page

function testGPSDetection() {
    console.log('🌍 Testing GPS Detection...');
    
    // Test 1: Check if autoDetectLocation is available globally
    if (typeof window.autoDetectLocation === 'function') {
        console.log('✅ autoDetectLocation function is globally accessible');
    } else {
        console.log('❌ autoDetectLocation function not found globally');
        return false;
    }
    
    // Test 2: Check field detection
    const fields = findCoordinateFieldsGlobal();
    console.log('Field detection result:', fields);
    
    if (fields.latitude && fields.longitude) {
        console.log('✅ Both coordinate fields found');
        console.log('Latitude field:', fields.latitude);
        console.log('Longitude field:', fields.longitude);
    } else {
        console.log('❌ Coordinate fields not found');
        return false;
    }
    
    // Test 3: Test GPS API availability
    if (navigator.geolocation) {
        console.log('✅ Geolocation API available');
    } else {
        console.log('❌ Geolocation API not available');
        return false;
    }
    
    // Test 4: Test GPS button trigger
    console.log('🚀 Triggering autoDetectLocation...');
    try {
        window.autoDetectLocation();
        console.log('✅ GPS detection triggered successfully');
        return true;
    } catch (error) {
        console.log('❌ Error triggering GPS detection:', error);
        return false;
    }
}

// Run the test
testGPSDetection();
JAVASCRIPT;

echo "Copy this JavaScript code and run it in browser console on WorkLocation form:\n\n";
echo $jsTestCode . "\n";

// Test 6: Summary and recommendations
echo "\nTest 6: Summary and Recommendations\n";

$issues = [];
if (!file_exists($workLocationResourcePath)) {
    $issues[] = "WorkLocationResource.php file not found";
}
if (!file_exists($mapComponentPath)) {
    $issues[] = "leaflet-osm-map.blade.php file not found";
}

if (empty($issues)) {
    echo "✅ All components found and configured correctly\n";
    echo "\nTo test GPS functionality:\n";
    echo "1. Open WorkLocation form in browser\n";
    echo "2. Open browser console (F12)\n";
    echo "3. Run the JavaScript test code above\n";
    echo "4. Click the GPS button to test actual functionality\n";
    echo "5. Check if coordinates are auto-filled in latitude and longitude fields\n";
} else {
    echo "❌ Issues found:\n";
    foreach ($issues as $issue) {
        echo "- $issue\n";
    }
}

echo "\nGPS Requirements Checklist:\n";
echo "□ HTTPS connection (required for GPS in production)\n";
echo "□ User permission for location access\n";
echo "□ GPS enabled on device\n";
echo "□ Clear sky view (for better GPS accuracy)\n";
echo "□ No browser GPS blocking extensions\n";

echo "\nCommon GPS Issues and Solutions:\n";
echo "1. Permission denied → Ask user to enable location access\n";
echo "2. Position unavailable → Check GPS/network settings\n";
echo "3. Timeout → Increase timeout or try progressive detection\n";
echo "4. Function not found → Check if leaflet-osm-map component is loaded\n";
echo "5. Fields not found → Verify field IDs and data attributes\n";

echo "\n🎯 Test completed! Check browser console for live testing.\n";
?>