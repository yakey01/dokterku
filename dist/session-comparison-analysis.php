<?php
/**
 * Session & CSRF Deep Comparison: Local vs Hostinger
 * Access: https://dokterkuklinik.com/session-comparison-analysis.php?auth=bismillah2024emergency
 * 
 * Comprehensive analysis comparing local and production configurations
 */

$authToken = $_GET['auth'] ?? '';
$validToken = 'bismillah2024emergency';

if ($authToken !== $validToken) {
    http_response_code(401);
    die('Authentication required: add ?auth=bismillah2024emergency');
}

// Change to Laravel root
$laravelRoot = dirname(__DIR__);
chdir($laravelRoot);

if (!file_exists('artisan')) {
    die('Laravel installation not found');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Comparison Analysis - KLINIK DOKTERKU</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
        .container { max-width: 1400px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; overflow: hidden; }
        .card-header { background: #1f2937; color: white; padding: 20px; font-size: 18px; font-weight: 600; }
        .card-body { padding: 20px; }
        .success { color: #059669; background: #ecfdf5; padding: 12px; border-radius: 6px; border-left: 4px solid #059669; }
        .error { color: #dc2626; background: #fef2f2; padding: 12px; border-radius: 6px; border-left: 4px solid #dc2626; }
        .warning { color: #d97706; background: #fffbeb; padding: 12px; border-radius: 6px; border-left: 4px solid #d97706; }
        .info { color: #0891b2; background: #f0f9ff; padding: 12px; border-radius: 6px; border-left: 4px solid #0891b2; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: 1 / -1; }
        pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 14px; }
        .status-good { color: #059669; font-weight: 600; }
        .status-bad { color: #dc2626; font-weight: 600; }
        .status-warning { color: #d97706; font-weight: 600; }
        .comparison-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .comparison-table th, .comparison-table td { padding: 12px; text-align: left; border: 1px solid #e5e7eb; }
        .comparison-table th { background: #f9fafb; font-weight: 600; }
        .local-env { background: #f0fdf4; }
        .production-env { background: #fef2f2; }
        .fix-button { background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin: 5px; }
        .fix-button:hover { background: #1d4ed8; }
        .diff-same { background: #f0fdf4; }
        .diff-different { background: #fef2f2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">🔍 Deep Session & CSRF Comparison: Local vs Production</div>
            <div class="card-body">
                <div class="info">
                    <strong>Issue:</strong> Frequent refresh/CSRF errors on Hostinger admin pages<br>
                    <strong>Analysis Time:</strong> <?= date('Y-m-d H:i:s') ?><br>
                    <strong>Environment:</strong> Production (Hostinger)<br>
                    <strong>Goal:</strong> Compare with local configuration and implement long-life CSRF
                </div>
            </div>
        </div>

        <!-- Current Production Configuration -->
        <div class="card">
            <div class="card-header">⚙️ Current Production Configuration</div>
            <div class="card-body">
                <?php
                echo "<h4>Environment Variables</h4>";
                
                if (file_exists('.env')) {
                    $env = file_get_contents('.env');
                    $envLines = explode("\n", $env);
                    
                    $sessionKeys = [
                        'APP_ENV', 'APP_DEBUG', 'APP_URL',
                        'SESSION_DRIVER', 'SESSION_LIFETIME', 'SESSION_ENCRYPT', 
                        'SESSION_SECURE_COOKIE', 'SESSION_HTTP_ONLY', 'SESSION_SAME_SITE',
                        'SESSION_DOMAIN', 'SESSION_PATH', 'CACHE_STORE'
                    ];
                    
                    echo "<table class='comparison-table'>";
                    echo "<tr><th>Setting</th><th>Production Value</th><th>Recommended Local</th><th>Status</th></tr>";
                    
                    $recommendations = [
                        'APP_ENV' => 'production',
                        'APP_DEBUG' => 'false',
                        'APP_URL' => 'https://dokterkuklinik.com',
                        'SESSION_DRIVER' => 'database',
                        'SESSION_LIFETIME' => '1440', // 24 hours instead of 120 minutes
                        'SESSION_ENCRYPT' => 'false',
                        'SESSION_SECURE_COOKIE' => 'true',
                        'SESSION_HTTP_ONLY' => 'true', 
                        'SESSION_SAME_SITE' => 'lax',
                        'SESSION_DOMAIN' => '.dokterkuklinik.com',
                        'SESSION_PATH' => '/',
                        'CACHE_STORE' => 'database'
                    ];
                    
                    foreach ($sessionKeys as $key) {
                        $currentValue = 'NOT SET';
                        foreach ($envLines as $line) {
                            if (strpos(trim($line), $key . '=') === 0) {
                                $currentValue = explode('=', $line, 2)[1] ?? '';
                                break;
                            }
                        }
                        
                        $recommended = $recommendations[$key] ?? 'N/A';
                        $status = '<span class="status-good">✓ OK</span>';
                        $rowClass = 'diff-same';
                        
                        if ($currentValue === 'NOT SET') {
                            $status = '<span class="status-bad">❌ Missing</span>';
                            $rowClass = 'diff-different';
                        } elseif ($currentValue !== $recommended && $recommended !== 'N/A') {
                            $status = '<span class="status-warning">⚠ Different</span>';
                            $rowClass = 'diff-different';
                        }
                        
                        echo "<tr class='$rowClass'>";
                        echo "<td>$key</td>";
                        echo "<td>" . htmlspecialchars($currentValue) . "</td>";
                        echo "<td>$recommended</td>";
                        echo "<td>$status</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo '<div class="error">❌ .env file not found</div>';
                }
                ?>
            </div>
        </div>

        <div class="grid">
            <!-- Session Storage Analysis -->
            <div class="card">
                <div class="card-header">💾 Session Storage Analysis</div>
                <div class="card-body">
                    <?php
                    echo "<h4>Database Session Tables</h4>";
                    
                    try {
                        // Check if sessions table exists
                        $tablesCheck = shell_exec('php artisan tinker --execute="echo DB::table(\'information_schema.tables\')->where(\'table_name\', \'sessions\')->exists() ? \'EXISTS\' : \'NOT_EXISTS\';" 2>&1');
                        
                        if (strpos($tablesCheck, 'EXISTS') !== false) {
                            echo "<div class='success'>✅ Sessions table exists</div>";
                            
                            // Count current sessions
                            $sessionCount = shell_exec('php artisan tinker --execute="echo DB::table(\'sessions\')->count();" 2>&1');
                            echo "<p><strong>Active Sessions:</strong> " . trim($sessionCount) . "</p>";
                            
                            // Check recent sessions
                            $recentSessions = shell_exec('php artisan tinker --execute="echo DB::table(\'sessions\')->where(\'last_activity\', \'>\', ' . (time() - 3600) . ')->count();" 2>&1');
                            echo "<p><strong>Recent Sessions (1h):</strong> " . trim($recentSessions) . "</p>";
                            
                        } else {
                            echo "<div class='error'>❌ Sessions table not found</div>";
                            echo "<div class='warning'>⚠️ Run: php artisan session:table && php artisan migrate</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='error'>❌ Could not check sessions table: " . $e->getMessage() . "</div>";
                    }
                    
                    echo "<h4>File Storage Analysis</h4>";
                    $sessionPath = 'storage/framework/sessions';
                    if (is_dir($sessionPath)) {
                        $sessionFiles = glob($sessionPath . '/*');
                        $fileCount = count($sessionFiles);
                        echo "<p><strong>Session Files:</strong> $fileCount</p>";
                        
                        if ($fileCount > 0) {
                            $recentFiles = 0;
                            foreach ($sessionFiles as $file) {
                                if (filemtime($file) > (time() - 3600)) {
                                    $recentFiles++;
                                }
                            }
                            echo "<p><strong>Recent Files (1h):</strong> $recentFiles</p>";
                        }
                        
                        $permissions = substr(sprintf('%o', fileperms($sessionPath)), -4);
                        $writable = is_writable($sessionPath);
                        echo "<p><strong>Permissions:</strong> $permissions " . ($writable ? '✅' : '❌') . "</p>";
                    } else {
                        echo "<div class='error'>❌ Session storage directory not found</div>";
                    }
                    ?>
                </div>
            </div>

            <!-- CSRF Token Analysis -->
            <div class="card">
                <div class="card-header">🔐 CSRF Token Analysis</div>
                <div class="card-body">
                    <?php
                    echo "<h4>Current CSRF Configuration</h4>";
                    
                    // Check middleware configuration
                    $middlewarePath = 'app/Http/Middleware/VerifyCsrfToken.php';
                    if (file_exists($middlewarePath)) {
                        $middlewareContent = file_get_contents($middlewarePath);
                        echo "<div class='success'>✅ CSRF Middleware exists</div>";
                        
                        // Check exceptions
                        if (strpos($middlewareContent, 'protected $except') !== false) {
                            preg_match('/protected \$except\s*=\s*\[(.*?)\];/s', $middlewareContent, $matches);
                            if ($matches) {
                                echo "<h5>CSRF Exceptions:</h5>";
                                echo "<pre>" . htmlspecialchars($matches[1]) . "</pre>";
                            }
                        }
                        
                        // Check if EnhancedVerifyCsrfToken exists
                        $enhancedPath = 'app/Http/Middleware/EnhancedVerifyCsrfToken.php';
                        if (file_exists($enhancedPath)) {
                            echo "<div class='info'>ℹ️ Enhanced CSRF middleware available</div>";
                        }
                    } else {
                        echo "<div class='error'>❌ CSRF Middleware not found</div>";
                    }
                    
                    echo "<h4>Token Generation Test</h4>";
                    try {
                        session_start();
                        $token = bin2hex(random_bytes(32));
                        echo "<p><strong>Test Token:</strong> " . substr($token, 0, 20) . "...</p>";
                        echo "<p><strong>Token Length:</strong> " . strlen($token) . " chars</p>";
                        
                        // Check Laravel token generation
                        $laravelToken = shell_exec('php artisan tinker --execute="echo csrf_token();" 2>&1');
                        if ($laravelToken && strlen(trim($laravelToken)) > 10) {
                            echo "<div class='success'>✅ Laravel CSRF token generation working</div>";
                            echo "<p><strong>Laravel Token:</strong> " . substr(trim($laravelToken), 0, 20) . "...</p>";
                        } else {
                            echo "<div class='error'>❌ Laravel CSRF token generation failed</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='error'>❌ Token generation test failed: " . $e->getMessage() . "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Recommended Fixes -->
        <div class="card full-width">
            <div class="card-header">🚀 Long-Life CSRF Implementation</div>
            <div class="card-body">
                <div class="info">
                    <h4>Key Issues Identified:</h4>
                    <ul>
                        <li><strong>Short Session Lifetime:</strong> Currently 120 minutes vs recommended 1440 minutes (24 hours)</li>
                        <li><strong>Session Driver Mismatch:</strong> May be using file vs database storage</li>
                        <li><strong>HTTPS Cookie Settings:</strong> Need proper secure cookie configuration</li>
                        <li><strong>CSRF Token Lifecycle:</strong> Tokens expiring too quickly</li>
                    </ul>
                </div>

                <h4>Automatic Fix Options:</h4>
                <form method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="implement_longlife_csrf">
                    <input type="hidden" name="auth_token" value="<?= $validToken ?>">
                    <button type="submit" class="fix-button">🔧 Implement Long-Life CSRF Configuration</button>
                </form>
                
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="optimize_session_config">
                    <input type="hidden" name="auth_token" value="<?= $validToken ?>">
                    <button type="submit" class="fix-button">⚙️ Optimize Session Configuration</button>
                </form>

                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="create_sessions_table">
                    <input type="hidden" name="auth_token" value="<?= $validToken ?>">
                    <button type="submit" class="fix-button">💾 Create/Migrate Sessions Table</button>
                </form>
            </div>
        </div>

        <?php
        // Handle automatic fixes
        if ($_POST['action'] ?? '' && ($_POST['auth_token'] ?? '') === $validToken) {
            echo '<div class="card full-width">';
            echo '<div class="card-header">🔄 Executing Fix</div>';
            echo '<div class="card-body">';
            
            $action = $_POST['action'];
            
            switch ($action) {
                case 'implement_longlife_csrf':
                    echo "<h4>Implementing Long-Life CSRF Configuration</h4>";
                    
                    // Update .env file
                    if (file_exists('.env')) {
                        $envContent = file_get_contents('.env');
                        $updates = [
                            'SESSION_LIFETIME' => '1440',
                            'SESSION_DRIVER' => 'database',
                            'SESSION_SECURE_COOKIE' => 'true',
                            'SESSION_HTTP_ONLY' => 'true',
                            'SESSION_SAME_SITE' => 'lax',
                            'SESSION_DOMAIN' => '.dokterkuklinik.com',
                            'CACHE_STORE' => 'database'
                        ];
                        
                        foreach ($updates as $key => $value) {
                            if (strpos($envContent, $key . '=') !== false) {
                                $envContent = preg_replace("/^$key=.*$/m", "$key=$value", $envContent);
                                echo "<div class='success'>✅ Updated $key=$value</div>";
                            } else {
                                $envContent .= "\n$key=$value";
                                echo "<div class='success'>✅ Added $key=$value</div>";
                            }
                        }
                        
                        file_put_contents('.env', $envContent);
                        echo "<div class='success'>✅ Environment configuration updated</div>";
                    }
                    
                    // Clear caches
                    echo "<h5>Clearing Caches</h5>";
                    $commands = [
                        'php artisan config:clear',
                        'php artisan cache:clear',
                        'php artisan session:flush',
                        'php artisan config:cache'
                    ];
                    
                    foreach ($commands as $cmd) {
                        $output = shell_exec($cmd . ' 2>&1');
                        echo "<div class='info'>$ $cmd</div>";
                        echo "<pre>" . htmlspecialchars($output) . "</pre>";
                    }
                    break;
                    
                case 'optimize_session_config':
                    echo "<h4>Optimizing Session Configuration</h4>";
                    
                    // Copy optimized session config
                    if (file_exists('config/session-optimized.php')) {
                        copy('config/session-optimized.php', 'config/session.php');
                        echo "<div class='success'>✅ Applied optimized session configuration</div>";
                    }
                    
                    $output = shell_exec('php artisan config:cache 2>&1');
                    echo "<div class='info'>$ php artisan config:cache</div>";
                    echo "<pre>" . htmlspecialchars($output) . "</pre>";
                    break;
                    
                case 'create_sessions_table':
                    echo "<h4>Creating Sessions Table</h4>";
                    
                    $commands = [
                        'php artisan session:table',
                        'php artisan migrate --force'
                    ];
                    
                    foreach ($commands as $cmd) {
                        $output = shell_exec($cmd . ' 2>&1');
                        echo "<div class='info'>$ $cmd</div>";
                        echo "<pre>" . htmlspecialchars($output) . "</pre>";
                    }
                    break;
            }
            
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <!-- Live Testing -->
        <div class="card full-width">
            <div class="card-header">🧪 Live Testing</div>
            <div class="card-body">
                <div style="margin-top: 20px;">
                    <a href="https://dokterkuklinik.com/admin" target="_blank" class="fix-button">🔗 Test Admin Panel</a>
                    <a href="https://dokterkuklinik.com/admin/dokters/2/edit" target="_blank" class="fix-button">🧪 Test Problem Page</a>
                    <a href="javascript:location.reload()" class="fix-button">🔄 Refresh Analysis</a>
                </div>
                
                <div class="info" style="margin-top: 20px;">
                    <h4>Next Steps After Applying Fixes:</h4>
                    <ol>
                        <li>Clear browser cache and cookies</li>
                        <li>Test admin login and navigation</li>
                        <li>Monitor session duration (should last 24 hours)</li>
                        <li>Check for CSRF errors in browser console</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>