<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request
$request = Illuminate\Http\Request::create('/login', 'GET');
$response = $kernel->handle($request);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Real Login Test</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Real Login Test - Direct PHP</h1>
    
    <div class="info">
        <h3>Session Info:</h3>
        <pre><?php
        session_start();
        echo "Session ID: " . session_id() . "\n";
        echo "Session Name: " . session_name() . "\n";
        echo "Session Save Path: " . session_save_path() . "\n";
        echo "Session Data: " . json_encode($_SESSION, JSON_PRETTY_PRINT) . "\n";
        ?></pre>
    </div>
    
    <div class="info">
        <h3>Cookie Info:</h3>
        <pre><?php
        echo "Cookies: " . json_encode($_COOKIE, JSON_PRETTY_PRINT) . "\n";
        ?></pre>
    </div>
    
    <div class="info">
        <h3>CSRF Token:</h3>
        <pre><?php
        $token = session()->token();
        echo "Token: " . $token . "\n";
        ?></pre>
    </div>
    
    <h2>Test Login Form</h2>
    <form method="POST" action="/login">
        <input type="hidden" name="_token" value="<?php echo session()->token(); ?>">
        <div>
            <label>Username/Email:</label><br>
            <input type="text" name="email_or_username" value="admin@dokterku.com" style="width: 300px; padding: 5px;">
        </div>
        <div style="margin-top: 10px;">
            <label>Password:</label><br>
            <input type="password" name="password" value="password" style="width: 300px; padding: 5px;">
        </div>
        <div style="margin-top: 15px;">
            <button type="submit">Login via Form POST</button>
        </div>
    </form>
    
    <h2>Test Users</h2>
    <div class="info">
        <p><strong>Admin:</strong> admin@dokterku.com / password</p>
        <p><strong>Paramedis:</strong> naning@dokterku.com / password</p>
        <p><strong>Dokter:</strong> 3333@dokter.local / password</p>
        <p><strong>All passwords:</strong> password</p>
    </div>
    
    <h2>Direct Login Test</h2>
    <button onclick="testDirectLogin('admin@dokterku.com')">Test Admin Login</button>
    <button onclick="testDirectLogin('naning@dokterku.com')">Test Paramedis Login</button>
    <button onclick="testDirectLogin('3333@dokter.local')">Test Dokter Login</button>
    
    <div id="result" style="margin-top: 20px;"></div>
    
    <script>
    function testDirectLogin(username) {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="info">Testing login for ' + username + '...</div>';
        
        // Create form data
        const formData = new FormData();
        formData.append('email_or_username', username);
        formData.append('password', 'password');
        formData.append('_token', '<?php echo session()->token(); ?>');
        
        fetch('/login', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            redirect: 'manual'
        })
        .then(response => {
            console.log('Response:', response);
            
            if (response.type === 'opaqueredirect' || response.status === 302) {
                resultDiv.innerHTML = '<div class="success">Login successful! Redirecting...</div>';
                // Try to get redirect location
                setTimeout(() => {
                    window.location.href = '/admin';
                }, 1000);
            } else {
                return response.text().then(text => {
                    resultDiv.innerHTML = '<div class="error">Login failed. Status: ' + response.status + '</div>';
                    resultDiv.innerHTML += '<pre>' + text.substring(0, 500) + '...</pre>';
                });
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="error">Error: ' + error.message + '</div>';
        });
    }
    </script>
    
    <h2>Session Debug</h2>
    <div class="info">
        <?php
        // Check if auth is working
        if (Auth::check()) {
            echo '<div class="success">Authenticated as: ' . Auth::user()->email . '</div>';
        } else {
            echo '<div class="error">Not authenticated</div>';
        }
        
        // Check database connection
        try {
            $sessionCount = DB::table('sessions')->count();
            echo '<div class="success">Sessions table has ' . $sessionCount . ' records</div>';
        } catch (\Exception $e) {
            echo '<div class="error">Database error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>