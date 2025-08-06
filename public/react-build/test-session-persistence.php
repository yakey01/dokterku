<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require __DIR__ . '/../bootstrap/app.php';
$request = Illuminate\Http\Request::capture();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);

header('Content-Type: text/plain');

echo "SESSION PERSISTENCE TEST\n";
echo "========================\n\n";

// Check current auth status
$user = \Illuminate\Support\Facades\Auth::user();
$sessionId = session()->getId();

echo "Session ID: " . $sessionId . "\n";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Lifetime: " . config('session.lifetime') . " minutes\n";
echo "Session Domain: " . (config('session.domain') ?: '(not set)') . "\n";
echo "Session Path: " . config('session.path') . "\n";
echo "Session Same Site: " . config('session.same_site') . "\n\n";

if ($user) {
    echo "✅ AUTHENTICATED!\n";
    echo "User ID: " . $user->id . "\n";
    echo "User Name: " . $user->name . "\n";
    echo "User Email: " . $user->email . "\n";
    echo "User Role: " . ($user->getRoleNames()->first() ?? 'No Role') . "\n\n";
    
    // Check session data
    echo "Session Data:\n";
    echo "- _token: " . (session('_token') ? 'Present' : 'Missing') . "\n";
    echo "- password_hash_web: " . (session('password_hash_web') ? 'Present' : 'Missing') . "\n";
    echo "- login_web_*: " . (session()->has('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d') ? 'Present' : 'Missing') . "\n";
    
} else {
    echo "❌ NOT AUTHENTICATED\n\n";
    
    echo "Possible reasons:\n";
    echo "1. Session not saved properly\n";
    echo "2. Session cookie not sent by browser\n";
    echo "3. Session expired\n";
    echo "4. Session domain mismatch\n\n";
    
    echo "Debug Info:\n";
    echo "- Cookie 'dokterku_session': " . (isset($_COOKIE['dokterku_session']) ? 'Present' : 'Missing') . "\n";
    echo "- Cookie 'XSRF-TOKEN': " . (isset($_COOKIE['XSRF-TOKEN']) ? 'Present' : 'Missing') . "\n";
}

echo "\nAll Cookies:\n";
foreach ($_COOKIE as $name => $value) {
    echo "- $name: " . substr($value, 0, 20) . "...\n";
}

// Check database session
if (config('session.driver') === 'database') {
    echo "\nDatabase Session Check:\n";
    try {
        $dbSession = \Illuminate\Support\Facades\DB::table('sessions')
            ->where('id', $sessionId)
            ->first();
            
        if ($dbSession) {
            echo "- Session found in database ✓\n";
            echo "- User ID in session: " . ($dbSession->user_id ?: 'NULL') . "\n";
            echo "- Last activity: " . date('Y-m-d H:i:s', $dbSession->last_activity) . "\n";
        } else {
            echo "- Session NOT found in database ✗\n";
        }
    } catch (Exception $e) {
        echo "- Error checking database: " . $e->getMessage() . "\n";
    }
}

$kernel->terminate($request, $response);