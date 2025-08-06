<?php
// Set cookie domain explicitly
ini_set('session.cookie_domain', 'localhost');

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require __DIR__ . '/../bootstrap/app.php';
$request = Illuminate\Http\Request::capture();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);

// Force login admin
$user = \App\Models\User::find(1);
\Illuminate\Support\Facades\Auth::login($user, true);

// Regenerate session with proper domain
session()->regenerate();
session()->save();

// Set cookie explicitly for localhost
setcookie('dokterku_session', session()->getId(), time() + 7200, '/', 'localhost', false, true);

header('Location: /admin');
exit;