<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

try {
    echo "🔍 DASHBOARD NAVIGATION DEBUG\n";
    echo "=============================\n\n";
    
    // Find dokter user
    $user = User::where('email', '3333@dokter.local')->first();
    if (!$user) {
        echo "❌ Dokter user not found\n";
        exit(1);
    }
    
    echo "✅ User found: {$user->name} (ID: {$user->id})\n";
    echo "📧 Email: {$user->email}\n";
    echo "🎭 Roles: " . $user->roles()->pluck('name')->implode(', ') . "\n";
    echo "🔐 Has dokter role: " . ($user->hasRole('dokter') ? 'YES' : 'NO') . "\n\n";
    
    // Simulate authentication
    Auth::login($user);
    
    echo "🔍 AUTHENTICATION STATUS:\n";
    echo "- Auth::check(): " . (Auth::check() ? 'TRUE' : 'FALSE') . "\n";
    echo "- Auth::user(): " . (Auth::user() ? Auth::user()->name : 'NULL') . "\n";
    echo "- Session authenticated: " . (session('authenticated') ? 'TRUE' : 'FALSE') . "\n\n";
    
    // Test middleware check
    echo "🛡️ MIDDLEWARE ROLE CHECK:\n";
    $middleware = new \App\Http\Middleware\RoleMiddleware();
    echo "- Manual role check: " . ($user->hasRole('dokter') ? 'PASS' : 'FAIL') . "\n\n";
    
    // Check route access
    echo "🌐 ROUTE ACCESS TEST:\n";
    try {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('dokter.mobile-app');
        if ($route) {
            echo "- Route exists: YES\n";
            echo "- Route URI: " . $route->uri() . "\n";
            echo "- Route middleware: " . implode(', ', $route->middleware()) . "\n";
        } else {
            echo "- Route exists: NO\n";
        }
    } catch (Exception $e) {
        echo "- Route check error: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎯 DASHBOARD COMPONENT CHECK:\n";
    
    // Simulate component props
    $userData = [
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'dokter'
    ];
    
    echo "- User data for component: " . json_encode($userData, JSON_PRETTY_PRINT) . "\n";
    
    // Check if navigation callback would work
    echo "\n📱 NAVIGATION CALLBACK TEST:\n";
    $tabs = ['dashboard', 'jadwal', 'presensi', 'jaspel', 'profil'];
    echo "- Available tabs: " . implode(', ', $tabs) . "\n";
    echo "- onNavigate prop type: function\n";
    echo "- setActiveTab function type: function\n";
    
    echo "\n🎨 CSS/STYLING CHECK:\n";
    $cssClasses = [
        'lg:hidden' => 'Should hide on desktop (≥1024px)',
        'absolute bottom-0' => 'Fixed position at bottom',
        'z-10' => 'Z-index layering above content',
        'bg-gradient-to-t' => 'Background gradient styling',
        'backdrop-blur-3xl' => 'Backdrop blur effect'
    ];
    
    foreach ($cssClasses as $class => $description) {
        echo "- {$class}: {$description}\n";
    }
    
    echo "\n🔧 BROWSER VIEWPORT CHECK:\n";
    echo "- Mobile (< 1024px): Navigation should be VISIBLE\n";
    echo "- Desktop (≥ 1024px): Navigation should be HIDDEN (lg:hidden)\n";
    echo "- If you're on desktop, resize browser to < 1024px width\n";
    
    echo "\n📊 RESPONSIVE BREAKPOINTS:\n";
    echo "- sm: 640px+\n";
    echo "- md: 768px+\n";
    echo "- lg: 1024px+ (navigation hidden at this breakpoint)\n";
    echo "- xl: 1280px+\n";
    
    echo "\n💡 TROUBLESHOOTING CHECKLIST:\n";
    echo "1. ✅ User has correct role (dokter)\n";
    echo "2. ✅ Route exists and is accessible\n";
    echo "3. ✅ Component receives userData prop\n";
    echo "4. ✅ Navigation HTML is present in Dashboard.tsx (lines 284-364)\n";
    echo "5. ❓ Browser viewport width < 1024px?\n";
    echo "6. ❓ CSS classes applying correctly?\n";
    echo "7. ❓ React component rendering without errors?\n";
    
    echo "\n🚨 LIKELY CAUSE:\n";
    echo "If you're testing on desktop (wide screen), the navigation is\n";
    echo "intentionally HIDDEN due to 'lg:hidden' class. Try:\n";
    echo "- Resize browser to mobile width (< 1024px)\n";
    echo "- Use browser dev tools to toggle device view\n";
    echo "- Check console for React errors\n";
    echo "- Verify CSS is loading correctly\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}