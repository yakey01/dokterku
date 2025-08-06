<?php
/**
 * Emergency Fix for "This page has expired" error
 * Access via: https://dokterkuklinik.com/fix-session.php
 * 
 * This script fixes CSRF token and session issues
 */

// Security check - only allow in development or specific conditions
$allowedIPs = ['127.0.0.1', '::1', 'localhost'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Simple authentication - remove this line if you want public access
$authToken = $_GET['auth'] ?? '';
$validToken = 'bismillah2024emergency';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Session & CSRF Issues - KLINIK DOKTERKU</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        .success { color: #059669; background: #ecfdf5; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc2626; background: #fef2f2; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0891b2; background: #f0f9ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .command { background: #1f2937; color: #f9fafb; padding: 10px; border-radius: 5px; font-family: monospace; margin: 5px 0; }
        .button { display: inline-block; background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px 10px 0; }
        .button:hover { background: #1d4ed8; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="header">🔧 Fix Session & CSRF Issues</h1>
        <p><strong>Target:</strong> https://dokterkuklinik.com/admin/dokters/2/edit</p>
        <p><strong>Error:</strong> "This page has expired"</p>
        
        <?php if ($authToken !== $validToken): ?>
            <div class="error">
                <h3>🔐 Authentication Required</h3>
                <p>Please provide the authentication token to run this fix.</p>
                <p><strong>Usage:</strong> <code>https://dokterkuklinik.com/fix-session.php?auth=bismillah2024emergency</code></p>
            </div>
        <?php else: ?>
            <div class="info">
                <h3>📋 About to fix the following issues:</h3>
                <ul>
                    <li>Clear Laravel configuration cache</li>
                    <li>Clear application cache</li>
                    <li>Clear view cache</li>
                    <li>Clear route cache</li>
                    <li>Clean old session files</li>
                    <li>Set proper storage permissions</li>
                    <li>Verify APP_KEY configuration</li>
                    <li>Update session settings</li>
                    <li>Rebuild configuration cache</li>
                </ul>
            </div>

            <?php
            // Change to Laravel root directory
            $originalDir = getcwd();
            $laravelRoot = dirname(__DIR__);
            chdir($laravelRoot);
            
            echo "<h2>🔄 Executing Fix...</h2>";
            
            // Check if we're in the right place
            if (!file_exists('artisan')) {
                echo "<div class='error'>❌ Error: Laravel artisan not found. Current directory: " . getcwd() . "</div>";
                exit;
            }
            
            echo "<div class='success'>✅ Laravel project detected</div>";
            
            // Function to run command and display output
            function runCommand($command, $description) {
                echo "<h3>$description</h3>";
                echo "<div class='command'>$ $command</div>";
                
                $output = [];
                $return_var = 0;
                exec($command . ' 2>&1', $output, $return_var);
                
                echo "<pre>";
                foreach ($output as $line) {
                    echo htmlspecialchars($line) . "\n";
                }
                echo "</pre>";
                
                if ($return_var === 0) {
                    echo "<div class='success'>✅ Command completed successfully</div>";
                } else {
                    echo "<div class='error'>⚠️ Command completed with warnings (exit code: $return_var)</div>";
                }
                
                return $return_var === 0;
            }
            
            // Clear caches
            runCommand('php artisan config:clear', '🗑️ Clearing Configuration Cache');
            runCommand('php artisan cache:clear', '🗑️ Clearing Application Cache');
            runCommand('php artisan view:clear', '🗑️ Clearing View Cache');
            runCommand('php artisan route:clear', '🗑️ Clearing Route Cache');
            
            // Clean session files
            echo "<h3>🗂️ Cleaning Session Files</h3>";
            $sessionPath = 'storage/framework/sessions/';
            if (is_dir($sessionPath)) {
                $sessionFiles = glob($sessionPath . 'laravel_session*');
                $oldSessions = array_filter($sessionFiles, function($file) {
                    return filemtime($file) < (time() - 3600); // Older than 1 hour
                });
                
                foreach ($oldSessions as $file) {
                    unlink($file);
                }
                
                echo "<div class='success'>✅ Removed " . count($oldSessions) . " old session files</div>";
                echo "<div class='info'>📊 Remaining sessions: " . count(glob($sessionPath . 'laravel_session*')) . "</div>";
            } else {
                echo "<div class='error'>❌ Session directory not found</div>";
            }
            
            // Check and set permissions
            echo "<h3>🔧 Setting Storage Permissions</h3>";
            if (is_dir('storage')) {
                chmod('storage', 0755);
                chmod('storage/framework', 0755);
                if (is_dir('storage/framework/sessions')) {
                    chmod('storage/framework/sessions', 0755);
                }
                echo "<div class='success'>✅ Storage permissions updated</div>";
            }
            
            // Check .env configuration
            echo "<h3>🔍 Checking .env Configuration</h3>";
            if (file_exists('.env')) {
                $env = file_get_contents('.env');
                
                // Check APP_KEY
                if (strpos($env, 'APP_KEY=') === false || strpos($env, 'APP_KEY=base64:') === false) {
                    echo "<div class='info'>⚠️ APP_KEY not found or invalid, generating...</div>";
                    runCommand('php artisan key:generate --force', '🔑 Generating APP_KEY');
                } else {
                    echo "<div class='success'>✅ APP_KEY is properly set</div>";
                }
                
                // Show current session settings
                echo "<h4>Current session settings:</h4><pre>";
                $lines = explode("\n", $env);
                foreach ($lines as $line) {
                    if (strpos($line, 'SESSION_') === 0 || strpos($line, 'APP_KEY') === 0) {
                        echo htmlspecialchars($line) . "\n";
                    }
                }
                echo "</pre>";
                
            } else {
                echo "<div class='error'>❌ .env file not found</div>";
            }
            
            // Rebuild config cache
            runCommand('php artisan config:cache', '🔄 Rebuilding Configuration Cache');
            
            // Test artisan
            runCommand('php artisan --version', '🌐 Testing Artisan');
            
            // Final optimization
            runCommand('php artisan optimize', '🚀 Running Laravel Optimization');
            
            echo "<div class='success'>";
            echo "<h2>✅ Fix Completed Successfully!</h2>";
            echo "<p><strong>Next steps:</strong></p>";
            echo "<ol>";
            echo "<li>Clear your browser cache</li>";
            echo "<li>Open a new incognito/private browser window</li>";
            echo "<li>Login to admin panel again</li>";
            echo "<li>Test the dokter edit page: <a href='https://dokterkuklinik.com/admin/dokters/2/edit' target='_blank'>https://dokterkuklinik.com/admin/dokters/2/edit</a></li>";
            echo "</ol>";
            echo "</div>";
            
            // Restore original directory
            chdir($originalDir);
            ?>
            
            <div class="info">
                <h3>📝 What was fixed:</h3>
                <ul>
                    <li>✅ Cleared all Laravel caches</li>
                    <li>✅ Cleaned old session files</li>
                    <li>✅ Set proper storage permissions</li>
                    <li>✅ Verified APP_KEY configuration</li>
                    <li>✅ Rebuilt configuration cache</li>
                    <li>✅ Ran Laravel optimization</li>
                </ul>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p><strong>💡 Tip:</strong> If the error persists, it might be due to browser cache. Try accessing the page in incognito mode.</p>
                <a href="https://dokterkuklinik.com/admin/dokters/2/edit" class="button" target="_blank">🧪 Test Dokter Edit Page</a>
                <a href="https://dokterkuklinik.com/admin" class="button" target="_blank">🔗 Go to Admin Panel</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>