<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

try {
    echo "🔍 TESTING DOKTER ROUTE AND TEMPLATE\n";
    echo "====================================\n\n";
    
    // 1. Check if route exists
    echo "1. CHECKING ROUTE:\n";
    $routes = Route::getRoutes();
    $dokterRoute = $routes->getByName('dokter.mobile-app');
    
    if ($dokterRoute) {
        echo "✅ Route exists: " . $dokterRoute->uri() . "\n";
        echo "📍 Middleware: " . implode(', ', $dokterRoute->middleware()) . "\n";
        echo "🎯 Action: " . $dokterRoute->getActionName() . "\n\n";
    } else {
        echo "❌ Route 'dokter.mobile-app' not found\n\n";
        
        // List all dokter routes
        echo "Available dokter routes:\n";
        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'dokter')) {
                echo "- " . $route->uri() . " (name: " . $route->getName() . ")\n";
            }
        }
        exit(1);
    }
    
    // 2. Force login dokter user
    echo "2. AUTHENTICATING USER:\n";
    $user = User::where('email', '3333@dokter.local')->first();
    if (!$user) {
        echo "❌ User not found\n";
        exit(1);
    }
    
    Auth::login($user);
    echo "✅ User authenticated: " . $user->name . "\n";
    echo "🎭 Role check: " . ($user->hasRole('dokter') ? 'PASS' : 'FAIL') . "\n\n";
    
    // 3. Check template file
    echo "3. CHECKING TEMPLATE FILE:\n";
    $templatePath = resource_path('views/mobile/dokter/app.blade.php');
    if (file_exists($templatePath)) {
        echo "✅ Template file exists: " . $templatePath . "\n";
        echo "📏 File size: " . formatBytes(filesize($templatePath)) . "\n";
        
        // Check if template contains dokter-app div
        $content = file_get_contents($templatePath);
        if (str_contains($content, 'id="dokter-app"')) {
            echo "✅ Template contains dokter-app container\n";
        } else {
            echo "❌ Template missing dokter-app container\n";
        }
        
        // Check if template references Vite assets
        if (str_contains($content, 'dokter-mobile-app.tsx')) {
            echo "✅ Template references Vite asset\n";
        } else {
            echo "❌ Template missing Vite asset reference\n";
        }
        
        echo "\n📄 Template preview (first 500 chars):\n";
        echo substr($content, 0, 500) . "...\n\n";
        
    } else {
        echo "❌ Template file not found: " . $templatePath . "\n\n";
    }
    
    // 4. Test route execution
    echo "4. TESTING ROUTE EXECUTION:\n";
    
    // Simulate request
    $request = \Illuminate\Http\Request::create('/dokter/mobile-app', 'GET');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    try {
        // Get the closure from route
        $action = $dokterRoute->getAction();
        
        if (isset($action['uses']) && $action['uses'] instanceof Closure) {
            echo "✅ Route uses closure\n";
            
            // Try to execute the closure
            $result = $action['uses']();
            
            if ($result instanceof \Illuminate\View\View) {
                echo "✅ Route returns view: " . $result->name() . "\n";
                echo "📊 View data keys: " . implode(', ', array_keys($result->getData())) . "\n";
            } elseif ($result instanceof \Illuminate\Http\RedirectResponse) {
                echo "🔄 Route redirects to: " . $result->getTargetUrl() . "\n";
            } else {
                echo "⚠️ Route returns: " . gettype($result) . "\n";
            }
            
        } else {
            echo "⚠️ Route does not use closure\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Route execution failed: " . $e->getMessage() . "\n";
        echo "Stack trace: " . substr($e->getTraceAsString(), 0, 300) . "...\n";
    }
    
    echo "\n";
    
    // 5. Check Vite manifest
    echo "5. CHECKING VITE ASSETS:\n";
    $manifestPath = public_path('build/manifest.json');
    if (file_exists($manifestPath)) {
        echo "✅ Vite manifest exists\n";
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (isset($manifest['resources/js/dokter-mobile-app.tsx'])) {
            echo "✅ Dokter mobile app asset found in manifest\n";
            $asset = $manifest['resources/js/dokter-mobile-app.tsx'];
            echo "📦 Asset file: " . $asset['file'] . "\n";
            
            // Check if asset file exists
            $assetPath = public_path('build/' . $asset['file']);
            if (file_exists($assetPath)) {
                echo "✅ Asset file exists: " . formatBytes(filesize($assetPath)) . "\n";
            } else {
                echo "❌ Asset file missing: " . $assetPath . "\n";
            }
        } else {
            echo "❌ Dokter mobile app asset not found in manifest\n";
            echo "Available assets:\n";
            foreach ($manifest as $key => $value) {
                if (is_array($value) && isset($value['file'])) {
                    echo "- " . $key . " → " . $value['file'] . "\n";
                }
            }
        }
    } else {
        echo "❌ Vite manifest not found: " . $manifestPath . "\n";
        echo "Run: npm run build\n";
    }
    
    echo "\n";
    
    // 6. Provide solutions
    echo "6. SOLUTIONS:\n";
    echo "=============\n";
    
    if (!file_exists($templatePath)) {
        echo "🔧 Create missing template file\n";
    }
    
    if (!file_exists($manifestPath)) {
        echo "🔧 Run: npm run build\n";
    }
    
    echo "🔧 Direct test URL: http://127.0.0.1:8000/dokter/mobile-app\n";
    echo "🔧 Check browser console for JavaScript errors\n";
    echo "🔧 Check network tab for 404 errors\n";
    echo "🔧 Clear browser cache and cookies\n";
    
    echo "\n✅ Diagnostic complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = ['B', 'KB', 'MB', 'GB'];
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}