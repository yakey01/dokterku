<?php

// Test script to diagnose WorkLocation 500 error
require_once __DIR__ . '/vendor/autoload.php';

try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create a test request to the admin work-locations
    $request = Illuminate\Http\Request::create('/admin/work-locations', 'GET');
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() >= 500) {
        echo "Error detected!\n";
        echo "Response content:\n";
        echo $response->getContent() . "\n";
    } else {
        echo "No 500 error - response is normal\n";
    }
    
    $kernel->terminate($request, $response);
    
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test WorkLocation model directly
try {
    echo "\n--- Testing WorkLocation Model ---\n";
    
    $workLocation = new \App\Models\WorkLocation();
    echo "✅ WorkLocation model can be instantiated\n";
    
    $count = \App\Models\WorkLocation::count();
    echo "✅ WorkLocation count: {$count}\n";
    
} catch (Exception $e) {
    echo "❌ WorkLocation model error: " . $e->getMessage() . "\n";
}

// Test routes specifically
try {
    echo "\n--- Testing Route Registration ---\n";
    
    $router = app('router');
    $routes = $router->getRoutes();
    $workLocationRoutes = 0;
    
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'work-location') !== false) {
            $workLocationRoutes++;
        }
    }
    
    echo "✅ Found {$workLocationRoutes} work-location routes\n";
    
} catch (Exception $e) {
    echo "❌ Route registration error: " . $e->getMessage() . "\n";
}