<?php
// Test script to diagnose Livewire update issues
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Check Livewire configuration
echo "=== Livewire Configuration Check ===\n";
echo "Livewire Version: " . \Composer\InstalledVersions::getVersion('livewire/livewire') . "\n";
echo "Livewire Update Endpoint: " . config('livewire.update_uri', '/livewire/update') . "\n";
echo "Asset URL: " . config('livewire.asset_url') . "\n";
echo "App URL: " . config('app.url') . "\n\n";

// Check registered Livewire components
echo "=== Registered Livewire Components ===\n";
$componentRegistry = app(\Livewire\Mechanisms\ComponentRegistry::class);
$components = [];

// Try to find Petugas widgets
$widgetPath = app_path('Filament/Petugas/Widgets');
if (is_dir($widgetPath)) {
    $files = glob($widgetPath . '/*.php');
    foreach ($files as $file) {
        $className = 'App\\Filament\\Petugas\\Widgets\\' . basename($file, '.php');
        if (class_exists($className)) {
            $isLivewire = is_subclass_of($className, \Livewire\Component::class) || 
                         is_subclass_of($className, \Filament\Widgets\Widget::class);
            if ($isLivewire) {
                echo "  - " . basename($file, '.php') . " (Livewire: Yes)\n";
                
                // Check if it has mount method issues
                $reflection = new ReflectionClass($className);
                if ($reflection->hasMethod('mount')) {
                    $method = $reflection->getMethod('mount');
                    $params = $method->getParameters();
                    if (count($params) > 0) {
                        echo "    WARNING: mount() has parameters - may cause serialization issues\n";
                    }
                }
            }
        }
    }
}

// Check middleware that might interfere
echo "\n=== Middleware Configuration ===\n";
$middleware = config('livewire.middleware_group', 'web');
echo "Livewire Middleware Group: " . $middleware . "\n";

// Check session configuration
echo "\n=== Session Configuration ===\n";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Lifetime: " . config('session.lifetime') . " minutes\n";
echo "Session Cookie: " . config('session.cookie') . "\n";
echo "Session Domain: " . config('session.domain') . "\n";
echo "Session Same Site: " . config('session.same_site') . "\n";

// Check CSRF configuration
echo "\n=== CSRF Configuration ===\n";
echo "CSRF Token Name: " . config('session.token', '_token') . "\n";

// Check for potential serialization issues
echo "\n=== Potential Issues ===\n";
$potentialIssues = [];

// Check ForceLocalSession middleware
if (class_exists(\App\Http\Middleware\ForceLocalSession::class)) {
    $potentialIssues[] = "ForceLocalSession middleware detected - may interfere with Livewire state";
}

// Check for session cleanup middleware
if (class_exists(\App\Http\Middleware\ClearStaleSessionMiddleware::class)) {
    $potentialIssues[] = "ClearStaleSessionMiddleware detected - may clear Livewire state";
}

if (empty($potentialIssues)) {
    echo "No obvious issues detected\n";
} else {
    foreach ($potentialIssues as $issue) {
        echo "⚠️  " . $issue . "\n";
    }
}

echo "\n=== Testing Simple Widget ===\n";
if (class_exists(\App\Filament\Petugas\Widgets\PetugasSimpleDashboardWidget::class)) {
    echo "✅ PetugasSimpleDashboardWidget exists\n";
    $widget = new \App\Filament\Petugas\Widgets\PetugasSimpleDashboardWidget();
    echo "✅ Can instantiate PetugasSimpleDashboardWidget\n";
    
    // Check if it's a Livewire component
    if ($widget instanceof \Livewire\Component) {
        echo "⚠️  Widget is a Livewire component (may have update issues)\n";
    } else {
        echo "✅ Widget is not a direct Livewire component\n";
    }
    
    if ($widget instanceof \Filament\Widgets\Widget) {
        echo "✅ Widget extends Filament Widget class\n";
    }
} else {
    echo "❌ PetugasSimpleDashboardWidget not found\n";
}

echo "\n=== Recommendations ===\n";
echo "1. The simple widget should work without Livewire update issues\n";
echo "2. If errors persist, check browser console for JavaScript errors\n";
echo "3. Verify CSRF token is being sent with requests\n";
echo "4. Check network tab for the actual error response\n";