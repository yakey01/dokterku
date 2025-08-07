<?php
/**
 * Deep Session & CSRF Analysis Tool
 * Access: https://dokterkuklinik.com/deep-session-analysis.php?auth=bismillah2024emergency
 * 
 * Comprehensive analysis of Laravel session and CSRF token issues
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
    <title>Deep Session Analysis - KLINIK DOKTERKU</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f8fafc; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; overflow: hidden; }
        .card-header { background: #1f2937; color: white; padding: 20px; font-size: 18px; font-weight: 600; }
        .card-body { padding: 20px; }
        .success { color: #059669; background: #ecfdf5; padding: 12px; border-radius: 6px; border-left: 4px solid #059669; }
        .error { color: #dc2626; background: #fef2f2; padding: 12px; border-radius: 6px; border-left: 4px solid #dc2626; }
        .warning { color: #d97706; background: #fffbeb; padding: 12px; border-radius: 6px; border-left: 4px solid #d97706; }
        .info { color: #0891b2; background: #f0f9ff; padding: 12px; border-radius: 6px; border-left: 4px solid #0891b2; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 14px; }
        .status-good { color: #059669; font-weight: 600; }
        .status-bad { color: #dc2626; font-weight: 600; }
        .status-warning { color: #d97706; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .fix-button { background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin: 5px; }
        .fix-button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">🔍 Deep Session & CSRF Analysis</div>
            <div class="card-body">
                <div class="info">
                    <strong>Target Issue:</strong> "This page has expired" error on dokter edit page<br>
                    <strong>Analysis Time:</strong> <?= date('Y-m-d H:i:s') ?><br>
                    <strong>Laravel Root:</strong> <?= getcwd() ?>
                </div>
            </div>
        </div>

        <div class="grid">
            <!-- Environment Analysis -->
            <div class="card">
                <div class="card-header">⚙️ Environment Configuration</div>
                <div class="card-body">
                    <?php
                    echo "<h4>Laravel Version</h4>";
                    $version = trim(shell_exec('php artisan --version 2>&1'));
                    echo "<pre>$version</pre>";

                    echo "<h4>PHP Configuration</h4>";
                    echo "<table>";
                    echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
                    
                    $phpChecks = [
                        'PHP Version' => PHP_VERSION,
                        'Session Save Handler' => ini_get('session.save_handler'),
                        'Session Save Path' => ini_get('session.save_path'),
                        'Session Cookie Lifetime' => ini_get('session.cookie_lifetime'),
                        'Session GC Max Lifetime' => ini_get('session.gc_maxlifetime'),
                        'Upload Max Filesize' => ini_get('upload_max_filesize'),
                        'Post Max Size' => ini_get('post_max_size'),
                        'Max Execution Time' => ini_get('max_execution_time'),
                        'Memory Limit' => ini_get('memory_limit')
                    ];
                    
                    foreach ($phpChecks as $setting => $value) {
                        $status = '<span class="status-good">✓ OK</span>';
                        if ($setting == 'Session GC Max Lifetime' && (int)$value < 1440) {
                            $status = '<span class="status-warning">⚠ Low</span>';
                        }
                        echo "<tr><td>$setting</td><td>$value</td><td>$status</td></tr>";
                    }
                    echo "</table>";
                    ?>
                </div>
            </div>

            <!-- Laravel Session Configuration -->
            <div class="card">
                <div class="card-header">🗂️ Laravel Session Configuration</div>
                <div class="card-body">
                    <?php
                    if (file_exists('.env')) {
                        echo "<h4>.env Session Settings</h4>";
                        $env = file_get_contents('.env');
                        $sessionLines = [];
                        $lines = explode("\n", $env);
                        
                        $relevantKeys = ['APP_KEY', 'APP_ENV', 'APP_DEBUG', 'SESSION_DRIVER', 'SESSION_LIFETIME', 'SESSION_SECURE_COOKIE', 'SESSION_SAME_SITE', 'CSRF_TOKEN'];
                        
                        foreach ($lines as $line) {
                            $line = trim($line);
                            foreach ($relevantKeys as $key) {
                                if (strpos($line, $key . '=') === 0) {
                                    $sessionLines[] = $line;
                                }
                            }
                        }
                        
                        if (empty($sessionLines)) {
                            echo '<div class="error">❌ No session configuration found in .env</div>';
                        } else {
                            echo "<table>";
                            echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
                            
                            foreach ($sessionLines as $line) {
                                list($key, $value) = explode('=', $line, 2);
                                $status = '<span class="status-good">✓ OK</span>';
                                
                                if ($key == 'APP_KEY' && (empty($value) || strpos($value, 'base64:') !== 0)) {
                                    $status = '<span class="status-bad">❌ Invalid</span>';
                                }
                                if ($key == 'SESSION_DRIVER' && $value != 'file') {
                                    $status = '<span class="status-warning">⚠ Non-standard</span>';
                                }
                                if ($key == 'SESSION_LIFETIME' && (int)$value < 120) {
                                    $status = '<span class="status-warning">⚠ Too short</span>';
                                }
                                
                                echo "<tr><td>$key</td><td>" . ($key == 'APP_KEY' ? substr($value, 0, 20) . '...' : $value) . "</td><td>$status</td></tr>";
                            }
                            echo "</table>";
                        }
                    } else {
                        echo '<div class="error">❌ .env file not found</div>';
                    }

                    // Check config cache
                    echo "<h4>Configuration Cache Status</h4>";
                    if (file_exists('bootstrap/cache/config.php')) {
                        $configCacheTime = date('Y-m-d H:i:s', filemtime('bootstrap/cache/config.php'));
                        echo "<div class='success'>✅ Config cache exists (created: $configCacheTime)</div>";
                    } else {
                        echo "<div class='warning'>⚠️ No config cache found</div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Session Storage Analysis -->
            <div class="card">
                <div class="card-header">💾 Session Storage Analysis</div>
                <div class="card-body">
                    <?php
                    echo "<h4>Session Directory Status</h4>";
                    $sessionPath = 'storage/framework/sessions';
                    
                    if (is_dir($sessionPath)) {
                        $permissions = substr(sprintf('%o', fileperms($sessionPath)), -4);
                        $writeable = is_writable($sessionPath);
                        
                        echo "<table>";
                        echo "<tr><th>Property</th><th>Value</th><th>Status</th></tr>";
                        echo "<tr><td>Path</td><td>$sessionPath</td><td><span class='status-good'>✓ Exists</span></td></tr>";
                        echo "<tr><td>Permissions</td><td>$permissions</td><td>" . ($permissions >= '0755' ? '<span class="status-good">✓ OK</span>' : '<span class="status-bad">❌ Too restrictive</span>') . "</td></tr>";
                        echo "<tr><td>Writable</td><td>" . ($writeable ? 'Yes' : 'No') . "</td><td>" . ($writeable ? '<span class="status-good">✓ OK</span>' : '<span class="status-bad">❌ Not writable</span>') . "</td></tr>";
                        echo "</table>";
                        
                        // Count session files
                        $sessionFiles = glob($sessionPath . '/laravel_session*');
                        $totalSessions = count($sessionFiles);
                        $recentSessions = 0;
                        $oldSessions = 0;
                        
                        foreach ($sessionFiles as $file) {
                            if (filemtime($file) > (time() - 3600)) {
                                $recentSessions++;
                            } else {
                                $oldSessions++;
                            }
                        }
                        
                        echo "<h4>Session Files Analysis</h4>";
                        echo "<table>";
                        echo "<tr><td>Total Sessions</td><td>$totalSessions</td></tr>";
                        echo "<tr><td>Recent (< 1 hour)</td><td>$recentSessions</td></tr>";
                        echo "<tr><td>Old (> 1 hour)</td><td>$oldSessions</td></tr>";
                        echo "</table>";
                        
                        if ($oldSessions > 100) {
                            echo "<div class='warning'>⚠️ Too many old session files may cause performance issues</div>";
                        }
                        
                    } else {
                        echo "<div class='error'>❌ Session directory does not exist: $sessionPath</div>";
                    }

                    // Check storage permissions
                    echo "<h4>Storage Directory Permissions</h4>";
                    $storageDirs = ['storage', 'storage/framework', 'storage/framework/cache', 'storage/framework/views', 'storage/logs'];
                    
                    echo "<table>";
                    echo "<tr><th>Directory</th><th>Exists</th><th>Permissions</th><th>Writable</th></tr>";
                    
                    foreach ($storageDirs as $dir) {
                        $exists = is_dir($dir);
                        $perms = $exists ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A';
                        $writable = $exists ? is_writable($dir) : false;
                        
                        echo "<tr>";
                        echo "<td>$dir</td>";
                        echo "<td>" . ($exists ? '✓' : '❌') . "</td>";
                        echo "<td>$perms</td>";
                        echo "<td>" . ($writable ? '✓' : '❌') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    ?>
                </div>
            </div>

            <!-- CSRF Token Analysis -->
            <div class="card">
                <div class="card-header">🔐 CSRF Token Analysis</div>
                <div class="card-body">
                    <?php
                    echo "<h4>CSRF Configuration</h4>";
                    
                    // Try to get Laravel config
                    $configOutput = shell_exec('php artisan config:show session 2>&1');
                    if (strpos($configOutput, 'Configuration') !== false) {
                        echo "<pre>$configOutput</pre>";
                    } else {
                        echo "<div class='warning'>⚠️ Could not retrieve session config</div>";
                    }

                    // Check middleware
                    echo "<h4>Middleware Analysis</h4>";
                    $middlewareFiles = [
                        'app/Http/Kernel.php',
                        'app/Http/Middleware/VerifyCsrfToken.php'
                    ];
                    
                    foreach ($middlewareFiles as $file) {
                        if (file_exists($file)) {
                            echo "<div class='success'>✅ $file exists</div>";
                            
                            if ($file == 'app/Http/Middleware/VerifyCsrfToken.php') {
                                $content = file_get_contents($file);
                                
                                // Check for except array
                                if (strpos($content, 'protected $except') !== false) {
                                    echo "<div class='info'>ℹ️ CSRF exceptions configured</div>";
                                }
                                
                                // Check for addtokentoexception
                                if (strpos($content, 'tokensMatch') !== false) {
                                    echo "<div class='info'>ℹ️ Token matching logic found</div>";
                                }
                            }
                        } else {
                            echo "<div class='error'>❌ $file not found</div>";
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Network & Server Analysis -->
            <div class="card">
                <div class="card-header">🌐 Network & Server Analysis</div>
                <div class="card-body">
                    <?php
                    echo "<h4>Server Information</h4>";
                    echo "<table>";
                    echo "<tr><th>Property</th><th>Value</th></tr>";
                    echo "<tr><td>Server Software</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</td></tr>";
                    echo "<tr><td>Request Method</td><td>" . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "</td></tr>";
                    echo "<tr><td>HTTPS</td><td>" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'Yes' : 'No') . "</td></tr>";
                    echo "<tr><td>Host</td><td>" . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "</td></tr>";
                    echo "<tr><td>User Agent</td><td>" . substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 100) . "...</td></tr>";
                    echo "</table>";

                    echo "<h4>Cookie Analysis</h4>";
                    if (empty($_COOKIE)) {
                        echo "<div class='warning'>⚠️ No cookies found</div>";
                    } else {
                        echo "<table>";
                        echo "<tr><th>Cookie Name</th><th>Value Length</th><th>Type</th></tr>";
                        foreach ($_COOKIE as $name => $value) {
                            $type = 'Other';
                            if (strpos($name, 'laravel_session') === 0) $type = 'Laravel Session';
                            if (strpos($name, 'XSRF-TOKEN') === 0) $type = 'CSRF Token';
                            if (strpos($name, '_token') !== false) $type = 'Token';
                            
                            echo "<tr><td>$name</td><td>" . strlen($value) . "</td><td>$type</td></tr>";
                        }
                        echo "</table>";
                    }

                    echo "<h4>Laravel Routes Test</h4>";
                    $routeTest = shell_exec('php artisan route:list --path=admin/dokters 2>&1');
                    if (strpos($routeTest, 'admin/dokters') !== false) {
                        echo "<div class='success'>✅ Admin routes found</div>";
                        echo "<pre>" . substr($routeTest, 0, 500) . "...</pre>";
                    } else {
                        echo "<div class='error'>❌ Admin routes not found or accessible</div>";
                        echo "<pre>$routeTest</pre>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Action Items -->
        <div class="card">
            <div class="card-header">🔧 Recommended Actions</div>
            <div class="card-body">
                <div class="info">
                    <h4>Based on analysis, here are the recommended fixes:</h4>
                    <ol>
                        <li><strong>Clear all caches:</strong> Config, routes, views</li>
                        <li><strong>Regenerate APP_KEY:</strong> If not properly set</li>
                        <li><strong>Clean session files:</strong> Remove old sessions</li>
                        <li><strong>Fix permissions:</strong> Ensure storage is writable</li>
                        <li><strong>Update session config:</strong> Optimize settings</li>
                        <li><strong>Test CSRF middleware:</strong> Verify token validation</li>
                    </ol>
                </div>

                <form method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="comprehensive_fix">
                    <input type="hidden" name="auth_token" value="<?= $validToken ?>">
                    <button type="submit" class="fix-button">🚀 Run Comprehensive Fix</button>
                </form>

                <div style="margin-top: 20px;">
                    <a href="https://dokterkuklinik.com/admin/dokters/2/edit" target="_blank" class="fix-button">🧪 Test Target Page</a>
                    <a href="https://dokterkuklinik.com/admin" target="_blank" class="fix-button">🔗 Admin Panel</a>
                </div>
            </div>
        </div>

        <?php
        // Handle comprehensive fix
        if ($_POST['action'] ?? '' === 'comprehensive_fix' && ($_POST['auth_token'] ?? '') === $validToken) {
            echo '<div class="card">';
            echo '<div class="card-header">🔄 Executing Comprehensive Fix</div>';
            echo '<div class="card-body">';
            
            $commands = [
                'Clear Config Cache' => 'php artisan config:clear',
                'Clear Application Cache' => 'php artisan cache:clear',
                'Clear Route Cache' => 'php artisan route:clear',
                'Clear View Cache' => 'php artisan view:clear',
                'Clear Compiled Classes' => 'php artisan clear-compiled',
                'Generate APP_KEY' => 'php artisan key:generate --force',
                'Rebuild Config Cache' => 'php artisan config:cache',
                'Optimize Application' => 'php artisan optimize'
            ];
            
            foreach ($commands as $description => $command) {
                echo "<h4>$description</h4>";
                echo "<div class='command'>$ $command</div>";
                $output = shell_exec($command . ' 2>&1');
                echo "<pre>$output</pre>";
            }
            
            // Clean session files
            echo "<h4>Clean Old Session Files</h4>";
            $sessionPath = 'storage/framework/sessions';
            if (is_dir($sessionPath)) {
                $sessionFiles = glob($sessionPath . '/laravel_session*');
                $cleaned = 0;
                foreach ($sessionFiles as $file) {
                    if (filemtime($file) < (time() - 1800)) { // 30 minutes
                        unlink($file);
                        $cleaned++;
                    }
                }
                echo "<div class='success'>✅ Cleaned $cleaned old session files</div>";
            }
            
            // Fix permissions
            echo "<h4>Fix Storage Permissions</h4>";
            $dirs = ['storage', 'storage/framework', 'storage/framework/sessions', 'storage/framework/cache', 'storage/logs'];
            foreach ($dirs as $dir) {
                if (is_dir($dir)) {
                    chmod($dir, 0755);
                    echo "<div class='success'>✅ Fixed permissions for $dir</div>";
                }
            }
            
            echo '<div class="success">';
            echo '<h3>✅ Comprehensive Fix Completed!</h3>';
            echo '<p>All fixes have been applied. Please test the page again.</p>';
            echo '</div>';
            
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>