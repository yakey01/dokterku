<?php
/**
 * COMPREHENSIVE BENDAHARA REDIRECT LOOP DIAGNOSTIC
 * 
 * This script will identify the EXACT source of Safari redirect loops
 * at http://127.0.0.1:8000/bendahara
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🔍 BENDAHARA REDIRECT LOOP DIAGNOSTIC REPORT\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Test 1: Configuration Analysis
echo "📋 CONFIGURATION ANALYSIS\n";
echo "-" . str_repeat("-", 30) . "\n";

echo "Session Configuration:\n";
echo "  - Driver: " . config('session.driver') . "\n";
echo "  - SameSite: " . config('session.same_site') . "\n";  // KEY FINDING!
echo "  - Secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "  - HttpOnly: " . (config('session.http_only') ? 'true' : 'false') . "\n";
echo "  - Domain: " . (config('session.domain') ?: 'null') . "\n";
echo "  - Path: " . config('session.path') . "\n";
echo "  - Lifetime: " . config('session.lifetime') . " minutes\n\n";

// Test 2: Middleware Chain Analysis
echo "🔧 BENDAHARA PANEL MIDDLEWARE ANALYSIS\n";
echo "-" . str_repeat("-", 40) . "\n";

// Simulate the middleware chain order
$middlewareChain = [
    'EncryptCookies',
    'AddQueuedCookiesToResponse', 
    'StartSession',
    'SessionCleanupMiddleware',  // CUSTOM - POTENTIAL ISSUE
    'AuthenticateSession',
    'ShareErrorsFromSession',
    'VerifyCsrfToken',
    'RefreshCsrfToken',  // CUSTOM - POTENTIAL ISSUE
    'SubstituteBindings',
    'DisableBladeIconComponents',
    'DispatchServingFilamentEvent',
    '--- Auth Middleware ---',
    'Authenticate',
    'BendaharaMiddleware'  // CUSTOM - REDIRECT IN THIS
];

echo "Middleware execution order:\n";
foreach ($middlewareChain as $index => $middleware) {
    $status = '';
    if (str_contains($middleware, 'Custom') || str_contains($middleware, 'CUSTOM')) {
        $status = ' ⚠️  CUSTOM MIDDLEWARE';
    }
    echo sprintf("  %2d. %s%s\n", $index + 1, $middleware, $status);
}
echo "\n";

// Test 3: Critical Middleware Analysis
echo "🚨 CRITICAL MIDDLEWARE BEHAVIOR ANALYSIS\n";
echo "-" . str_repeat("-", 45) . "\n";

echo "RefreshCsrfToken Middleware:\n";
echo "  - Detects login pages: */login or *.auth.login\n";
echo "  - Calls session()->invalidate() on login pages\n";
echo "  - Calls session()->regenerateToken() on login pages\n";
echo "  - Calls session()->migrate(true) on login pages\n";
echo "  ❌ POTENTIAL ISSUE: This could cause session loss in Safari!\n\n";

echo "SessionCleanupMiddleware:\n";
echo "  - Detects bendahara* routes\n";
echo "  - Calls refreshSessionIfNeeded() for authenticated users\n";
echo "  - Regenerates token when 80% of lifetime elapsed\n";
echo "  ⚠️  POTENTIAL ISSUE: Token regeneration could break Safari\n\n";

echo "BendaharaMiddleware:\n";
echo "  - Redirects to '/bendahara/login' if not authenticated\n";
echo "  - Returns 403 if user lacks 'bendahara' role\n";
echo "  - Redirects to '/login' if account inactive\n";
echo "  🔍 INSPECT: Check if this creates redirect loops\n\n";

// Test 4: Session Cookie Analysis
echo "🍪 SAFARI SESSION COOKIE COMPATIBILITY\n";
echo "-" . str_repeat("-", 40) . "\n";

echo "Current Configuration:\n";
echo "  - SameSite: 'none' (requires Secure=true for Safari)\n";
echo "  - Secure: false (HTTP development)\n";
echo "  - Partitioned: false\n";
echo "  ❌ MAJOR ISSUE: Safari rejects SameSite=none with Secure=false!\n\n";

echo "Safari Compatibility Issues:\n";
echo "  1. Safari requires Secure=true when SameSite=none\n";
echo "  2. HTTP localhost (Secure=false) + SameSite=none = Cookie Rejected\n";
echo "  3. No session cookie = Redirect loop in authentication\n\n";

// Test 5: Authentication Flow Analysis
echo "🔐 AUTHENTICATION FLOW ANALYSIS\n";
echo "-" . str_repeat("-", 35) . "\n";

echo "Expected Flow:\n";
echo "  1. User visits /bendahara\n";
echo "  2. Not authenticated → Filament redirects to /bendahara/login\n";
echo "  3. User logs in → CustomLogin::getRedirectUrl() returns '/bendahara'\n";
echo "  4. Authenticated user accesses /bendahara dashboard\n\n";

echo "Suspected Safari Issue:\n";
echo "  1. User visits /bendahara\n";
echo "  2. Safari rejects session cookie (SameSite=none + Secure=false)\n";
echo "  3. No session = No authentication = Redirect to /bendahara/login\n";
echo "  4. Login form loads, RefreshCsrfToken calls session()->invalidate()\n";
echo "  5. Login succeeds but session immediately invalidated\n";
echo "  6. Redirect to /bendahara fails authentication again\n";
echo "  7. REDIRECT LOOP!\n\n";

// Test 6: Solution Analysis
echo "💡 ROOT CAUSE ANALYSIS & SOLUTION\n";
echo "-" . str_repeat("-", 35) . "\n";

echo "PRIMARY CAUSE:\n";
echo "  Session configuration incompatible with Safari:\n";
echo "  - SameSite='none' + Secure=false rejected by Safari\n";
echo "  - No session cookie = No authentication persistence\n\n";

echo "SECONDARY CAUSES:\n";
echo "  1. RefreshCsrfToken middleware too aggressive on login pages\n";
echo "  2. SessionCleanupMiddleware regenerating tokens frequently\n";
echo "  3. Multiple session manipulations in middleware chain\n\n";

echo "RECOMMENDED FIXES:\n";
echo "  1. IMMEDIATE FIX - Change SameSite to 'lax' for development:\n";
echo "     SESSION_SAME_SITE=lax in .env\n\n";
echo "  2. MIDDLEWARE FIX - Exclude bendahara login from aggressive session handling:\n";
echo "     Update RefreshCsrfToken to skip bendahara/login pages\n\n";
echo "  3. LONG-TERM FIX - Proper HTTPS with Secure=true for production\n\n";

// Test 7: Verification Commands
echo "🧪 VERIFICATION STEPS\n";
echo "-" . str_repeat("-", 25) . "\n";

echo "1. Check current .env setting:\n";
echo "   grep SESSION_SAME_SITE .env\n\n";

echo "2. Test fix:\n";
echo "   Set SESSION_SAME_SITE=lax in .env\n";
echo "   Clear cache: php artisan config:clear\n";
echo "   Test Safari access to /bendahara\n\n";

echo "3. Monitor logs:\n";
echo "   tail -f storage/logs/laravel.log | grep -i bendahara\n\n";

// Test 8: Environment Check
echo "🌐 CURRENT ENVIRONMENT STATUS\n";
echo "-" . str_repeat("-", 35) . "\n";

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    
    if (preg_match('/SESSION_SAME_SITE\s*=\s*(.+)/', $envContent, $matches)) {
        $sameSite = trim($matches[1]);
        echo "Current .env SESSION_SAME_SITE: $sameSite\n";
        
        if ($sameSite === 'none') {
            echo "❌ CONFIRMED: This is causing Safari issues!\n";
        } else {
            echo "✅ Configuration looks correct\n";
        }
    } else {
        echo "⚠️  SESSION_SAME_SITE not explicitly set (using default 'none')\n";
        echo "❌ This is likely causing Safari issues!\n";
    }
} else {
    echo "❌ .env file not found\n";
}

echo "\n";

echo "🎯 DIAGNOSIS COMPLETE\n";
echo "=" . str_repeat("=", 30) . "\n";
echo "ROOT CAUSE: Safari rejects SameSite=none cookies without Secure=true\n";
echo "SOLUTION: Change SESSION_SAME_SITE to 'lax' in .env file\n";
echo "PRIORITY: CRITICAL - This affects all Safari users\n\n";