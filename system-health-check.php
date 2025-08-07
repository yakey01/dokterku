<?php

// Comprehensive system health check
require_once __DIR__ . '/vendor/autoload.php';

echo "🏥 DOKTERKU SYSTEM HEALTH CHECK\n";
echo "=================================\n\n";

try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Test database connectivity
    echo "1. 📊 DATABASE CONNECTIVITY\n";
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "   ✅ Database connection successful\n";
        
        $userCount = \Illuminate\Support\Facades\DB::table('users')->count();
        $workLocationCount = \Illuminate\Support\Facades\DB::table('work_locations')->count();
        
        echo "   ✅ Users: {$userCount}\n";
        echo "   ✅ Work Locations: {$workLocationCount}\n";
        
    } catch (Exception $e) {
        echo "   ❌ Database error: " . $e->getMessage() . "\n";
    }
    
    // Test key routes
    echo "\n2. 🌐 ROUTE TESTING\n";
    $testRoutes = [
        '/' => 'Home page',
        '/api/v2/locations/work-locations' => 'Work locations API',
        '/api/work-locations/active' => 'Active work locations',
        '/admin/work-locations' => 'Admin work locations'
    ];
    
    foreach ($testRoutes as $route => $description) {
        try {
            $request = Illuminate\Http\Request::create($route, 'GET');
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            
            if ($status < 500) {
                echo "   ✅ {$description}: {$status}\n";
            } else {
                echo "   ❌ {$description}: {$status}\n";
            }
            
            $kernel->terminate($request, $response);
            
        } catch (Exception $e) {
            echo "   ❌ {$description}: Error - " . $e->getMessage() . "\n";
        }
    }
    
    // Test models
    echo "\n3. 🏗️  MODEL TESTING\n";
    $models = [
        'User' => \App\Models\User::class,
        'WorkLocation' => \App\Models\WorkLocation::class,
        'Attendance' => \App\Models\Attendance::class
    ];
    
    foreach ($models as $name => $class) {
        try {
            if (class_exists($class)) {
                $instance = new $class();
                $count = $class::count();
                echo "   ✅ {$name}: {$count} records\n";
            } else {
                echo "   ❌ {$name}: Class not found\n";
            }
        } catch (Exception $e) {
            echo "   ❌ {$name}: Error - " . $e->getMessage() . "\n";
        }
    }
    
    // Test Filament resources
    echo "\n4. 🎛️  FILAMENT RESOURCES\n";
    $resources = [
        'WorkLocationResource' => \App\Filament\Resources\WorkLocationResource::class,
        'UserResource' => \App\Filament\Resources\UserResource::class,
    ];
    
    foreach ($resources as $name => $class) {
        try {
            if (class_exists($class)) {
                echo "   ✅ {$name}: Available\n";
            } else {
                echo "   ❌ {$name}: Not found\n";
            }
        } catch (Exception $e) {
            echo "   ❌ {$name}: Error - " . $e->getMessage() . "\n";
        }
    }
    
    // Test cache and config
    echo "\n5. ⚙️  SYSTEM CONFIGURATION\n";
    try {
        $config = config('app.name');
        echo "   ✅ App Name: {$config}\n";
        
        $env = config('app.env');
        echo "   ✅ Environment: {$env}\n";
        
        echo "   ✅ Config loading: Working\n";
        
    } catch (Exception $e) {
        echo "   ❌ Config error: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 HEALTH CHECK COMPLETED\n";
    echo "==========================\n";
    echo "If all items show ✅, your WorkLocation system is functioning properly!\n";
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}