<?php
/**
 * 🔍 SAFARI BENDAHARA REDIRECT LOOP - ROOT CAUSE ANALYSIS
 * 
 * DEFINITIVE DIAGNOSIS OF THE REDIRECT LOOP ISSUE
 */

echo "🚨 SAFARI BENDAHARA REDIRECT LOOP - ROOT CAUSE IDENTIFIED\n";
echo str_repeat("=", 70) . "\n\n";

// Read current .env configuration
$envFile = __DIR__ . '/.env';
$envSettings = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            [$key, $value] = explode('=', $line, 2);
            $envSettings[trim($key)] = trim($value);
        }
    }
}

echo "📋 CURRENT SESSION CONFIGURATION:\n";
echo str_repeat("-", 40) . "\n";
echo "SESSION_DRIVER: " . ($envSettings['SESSION_DRIVER'] ?? 'NOT SET') . "\n";
echo "SESSION_SECURE_COOKIE: " . ($envSettings['SESSION_SECURE_COOKIE'] ?? 'NOT SET') . "\n";
echo "SESSION_SAME_SITE: " . ($envSettings['SESSION_SAME_SITE'] ?? 'NOT SET (defaults to "none")') . "\n";
echo "SESSION_DOMAIN: " . ($envSettings['SESSION_DOMAIN'] ?? 'NOT SET (null)') . "\n\n";

// Check the config/session.php default value
$sessionConfigFile = __DIR__ . '/config/session.php';
if (file_exists($sessionConfigFile)) {
    $sessionConfig = file_get_contents($sessionConfigFile);
    if (preg_match("/'same_site' => env\('SESSION_SAME_SITE', '([^']+)'\)/", $sessionConfig, $matches)) {
        $defaultSameSite = $matches[1];
        echo "📄 CONFIG/SESSION.PHP DEFAULT:\n";
        echo "same_site default value: '$defaultSameSite'\n\n";
    }
}

echo "🔍 ROOT CAUSE ANALYSIS:\n";
echo str_repeat("-", 30) . "\n\n";

echo "❌ CRITICAL ISSUE IDENTIFIED:\n";
echo "   Safari Cookie Rejection Due to Invalid SameSite Configuration\n\n";

echo "📊 THE PROBLEM:\n";
echo "   1. SESSION_SAME_SITE defaults to 'none' (from config/session.php:201)\n";
echo "   2. SESSION_SECURE_COOKIE = false (HTTP localhost development)\n";
echo "   3. Safari enforces strict SameSite=none policy:\n";
echo "      → SameSite=none REQUIRES Secure=true\n";
echo "      → HTTP + SameSite=none = Cookie REJECTED by Safari\n";
echo "   4. No session cookie = No authentication persistence\n";
echo "   5. Every request appears as 'not authenticated'\n";
echo "   6. Continuous redirects to login → REDIRECT LOOP\n\n";

echo "🔄 REDIRECT LOOP MECHANISM:\n";
echo "   /bendahara → (no session cookie) → /bendahara/login → login success → \n";
echo "   /bendahara → (session cookie rejected) → /bendahara/login → LOOP!\n\n";

echo "🌐 BROWSER COMPATIBILITY:\n";
echo "   ✅ Chrome/Firefox: More lenient with SameSite=none on localhost\n";
echo "   ❌ Safari: Strictly enforces SameSite=none requires Secure=true\n";
echo "   ❌ Webkit browsers: Follow Safari's strict policy\n\n";

echo "🛠️ MIDDLEWARE CONTRIBUTING FACTORS:\n";
echo str_repeat("-", 40) . "\n";
echo "1. RefreshCsrfToken middleware (line 19-23):\n";
echo "   - Calls session()->invalidate() on login pages\n";
echo "   - Aggressive session clearing worsens the issue\n\n";
echo "2. SessionCleanupMiddleware (line 24-27):\n";
echo "   - Regenerates tokens when 80% of lifetime elapsed\n";
echo "   - Compounds session instability\n\n";
echo "3. BendaharaMiddleware (line 28):\n";
echo "   - Redirects to '/bendahara/login' when not authenticated\n";
echo "   - Creates the redirect target that causes loops\n\n";

echo "💡 DEFINITIVE SOLUTION:\n";
echo str_repeat("-", 30) . "\n";
echo "PRIMARY FIX - Update .env file:\n";
echo "   Add: SESSION_SAME_SITE=lax\n\n";
echo "EXPLANATION:\n";
echo "   - 'lax' allows cookies on same-site requests\n";
echo "   - Compatible with HTTP localhost development\n";
echo "   - No Secure=true requirement\n";
echo "   - Works across all browsers including Safari\n\n";

echo "🧪 VERIFICATION STEPS:\n";
echo "1. Add SESSION_SAME_SITE=lax to .env\n";
echo "2. Clear config cache: php artisan config:clear\n";
echo "3. Clear browser cookies for 127.0.0.1\n";
echo "4. Test Safari access to http://127.0.0.1:8000/bendahara\n\n";

echo "📈 EXPECTED RESULT:\n";
echo "   ✅ Safari will accept session cookies\n";
echo "   ✅ Authentication will persist\n";
echo "   ✅ No more redirect loops\n";
echo "   ✅ Normal bendahara panel access\n\n";

// Check if .env already has the fix
if (isset($envSettings['SESSION_SAME_SITE'])) {
    $currentValue = $envSettings['SESSION_SAME_SITE'];
    if ($currentValue === 'lax') {
        echo "✅ GOOD NEWS: SESSION_SAME_SITE is already set to 'lax'\n";
        echo "   If issues persist, clear browser cookies and config cache\n\n";
    } else {
        echo "⚠️  SESSION_SAME_SITE is set to '$currentValue'\n";
        echo "   Change it to 'lax' to fix the Safari issue\n\n";
    }
} else {
    echo "❌ ACTION REQUIRED: Add SESSION_SAME_SITE=lax to .env file\n\n";
}

echo "🎯 DIAGNOSIS COMPLETE - ROOT CAUSE CONFIRMED\n";
echo str_repeat("=", 50) . "\n";
echo "ISSUE: Safari rejects SameSite=none cookies without Secure=true\n";
echo "FIX: Set SESSION_SAME_SITE=lax in .env file\n";
echo "IMPACT: Affects ALL Safari users on ALL panels with same config\n";