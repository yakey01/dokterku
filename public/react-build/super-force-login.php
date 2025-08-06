<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require __DIR__ . '/../bootstrap/app.php';
$request = Illuminate\Http\Request::capture();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);

// Get user ID from query parameter
$userId = $_GET['user'] ?? null;

if (!$userId) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Super Force Login</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #1a1a1a;
                color: #0f0;
                padding: 50px;
                text-align: center;
            }
            a {
                display: inline-block;
                margin: 10px;
                padding: 15px 30px;
                background: #0f0;
                color: #000;
                text-decoration: none;
                font-weight: bold;
                border-radius: 5px;
            }
            a:hover {
                background: #0a0;
            }
            h1 {
                color: #0f0;
            }
        </style>
    </head>
    <body>
        <h1>🚀 SUPER FORCE LOGIN</h1>
        <p>Click to force login as:</p>
        <a href="?user=1">Admin</a>
        <a href="?user=3">Naning (Paramedis)</a>
        <a href="?user=10">Dr. Yaya (Dokter)</a>
    </body>
    </html>
    <?php
    exit;
}

// Force login
try {
    $user = \App\Models\User::find($userId);
    
    if (!$user) {
        die("❌ User ID $userId not found!");
    }
    
    // Force login using Laravel Auth
    \Illuminate\Support\Facades\Auth::login($user, true);
    
    // Save session
    session()->regenerate();
    session()->save();
    
    // Determine redirect
    $redirect = '/admin';
    if ($user->hasRole('dokter')) {
        $redirect = '/dokter/mobile-app';
    } elseif ($user->hasRole('paramedis')) {
        $redirect = '/paramedis/mobile-app';
    } elseif ($user->hasRole('admin')) {
        $redirect = '/admin';
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login Success</title>
        <meta http-equiv="refresh" content="2;url=<?php echo $redirect; ?>">
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #1a1a1a;
                color: #0f0;
                padding: 50px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <h1>✅ LOGIN SUCCESSFUL!</h1>
        <p>Logged in as: <?php echo htmlspecialchars($user->name); ?></p>
        <p>Role: <?php echo htmlspecialchars($user->getRoleNames()->first() ?? 'No Role'); ?></p>
        <p>Redirecting to <?php echo htmlspecialchars($redirect); ?> in 2 seconds...</p>
        <p><a href="<?php echo htmlspecialchars($redirect); ?>" style="color: #0f0;">Click here if not redirected</a></p>
    </body>
    </html>
    <?php
    
    // Terminate properly
    $kernel->terminate($request, $response);
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}