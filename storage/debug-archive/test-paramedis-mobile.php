<?php

// Test paramedis mobile app route

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Login as paramedis user
$user = \App\Models\User::find(3);
Auth::login($user);

echo "Logged in as: {$user->name}\n";
echo "Roles: " . implode(', ', $user->roles->pluck('name')->toArray()) . "\n\n";

// Test the mobile-app route
echo "Testing /paramedis/mobile-app route...\n\n";

try {
    // Manually resolve the route
    $request = Illuminate\Http\Request::create('/paramedis/mobile-app', 'GET');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    // Get the route
    $route = Route::getRoutes()->match($request);
    
    if ($route) {
        echo "Route found: " . $route->uri() . "\n";
        echo "Action: " . json_encode($route->getAction()) . "\n\n";
        
        // Execute the route action
        $action = $route->getAction('uses');
        if ($action instanceof \Closure) {
            echo "Executing route closure...\n";
            ob_start();
            $result = $action();
            $output = ob_get_clean();
            
            if ($result instanceof \Illuminate\View\View) {
                echo "View returned: " . $result->getName() . "\n";
                echo "View data:\n";
                print_r($result->getData());
            } else {
                echo "Result type: " . gettype($result) . "\n";
                echo "Output: " . substr($output, 0, 200) . "...\n";
            }
        }
    } else {
        echo "Route not found!\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Cleanup
Auth::logout();