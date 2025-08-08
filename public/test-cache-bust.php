<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Bust Test - Dokterku</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
    </style>
</head>
<body>
    <h1>🚀 Dokterku Cache Bust Test</h1>
    
    <div id="status">
        <h2>Status Check</h2>
        <p><strong>Build Time:</strong> <span id="buildTime"><?php echo time(); ?></span></p>
        <p><strong>Current Time:</strong> <span id="currentTime"></span></p>
        <p><strong>Cache Bust ID:</strong> <span id="cacheBustId"><?php echo md5(time() . rand()); ?></span></p>
        <p><strong>Session ID:</strong> <span id="sessionId"><?php echo session_id(); ?></span></p>
    </div>

    <div id="actions">
        <h2>Actions</h2>
        <button class="btn-primary" onclick="testCacheBust()">🧪 Test Cache Bust</button>
        <button class="btn-success" onclick="forceReload()">🔄 Force Reload</button>
        <button class="btn-warning" onclick="clearAllCaches()">🗑️ Clear All Caches</button>
        <button class="btn-primary" onclick="checkPresensiFile()">📁 Check Presensi File</button>
    </div>

    <div id="results">
        <h2>Results</h2>
        <pre id="output">Click a button to see results...</pre>
    </div>

    <div id="navigation">
        <h2>Navigation</h2>
        <p><a href="/dokter/mobile-app" target="_blank">🔗 Dokter Mobile App</a></p>
        <p><a href="/force-reload.js" target="_blank">🔗 Force Reload Script</a></p>
    </div>

    <script>
        // Update current time
        function updateTime() {
            document.getElementById('currentTime').textContent = Math.floor(Date.now() / 1000);
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Test cache bust functionality
        function testCacheBust() {
            const output = document.getElementById('output');
            output.innerHTML = '🧪 Testing cache bust functionality...\n\n';
            
            // Check if caches API is available
            if ('caches' in window) {
                output.innerHTML += '✅ Cache API available\n';
                caches.keys().then(names => {
                    output.innerHTML += `📁 Found ${names.length} caches: ${names.join(', ')}\n`;
                });
            } else {
                output.innerHTML += '❌ Cache API not available\n';
            }
            
            // Check localStorage
            const localStorageKeys = Object.keys(localStorage);
            output.innerHTML += `📦 localStorage keys: ${localStorageKeys.length}\n`;
            localStorageKeys.forEach(key => {
                output.innerHTML += `  - ${key}\n`;
            });
            
            // Check sessionStorage
            const sessionStorageKeys = Object.keys(sessionStorage);
            output.innerHTML += `📦 sessionStorage keys: ${sessionStorageKeys.length}\n`;
            
            // Check if we're on HTTPS
            if (location.protocol === 'https:') {
                output.innerHTML += '✅ HTTPS detected (required for GPS)\n';
            } else {
                output.innerHTML += '⚠️ HTTP detected (GPS may not work)\n';
            }
        }

        // Force reload with cache busting
        function forceReload() {
            const output = document.getElementById('output');
            output.innerHTML = '🔄 Force reloading with cache busting...\n';
            
            const currentUrl = window.location.href;
            const separator = currentUrl.includes('?') ? '&' : '?';
            const newUrl = currentUrl + separator + 'force-reload=' + Date.now() + '&cache-bust=' + Math.random();
            
            output.innerHTML += `📍 New URL: ${newUrl}\n`;
            output.innerHTML += '🔄 Reloading in 2 seconds...\n';
            
            setTimeout(() => {
                window.location.href = newUrl;
            }, 2000);
        }

        // Clear all caches
        function clearAllCaches() {
            const output = document.getElementById('output');
            output.innerHTML = '🗑️ Clearing all caches...\n\n';
            
            // Clear caches
            if ('caches' in window) {
                caches.keys().then(names => {
                    output.innerHTML += `🗑️ Clearing ${names.length} caches...\n`;
                    return Promise.all(names.map(name => caches.delete(name)));
                }).then(() => {
                    output.innerHTML += '✅ All caches cleared\n';
                }).catch(error => {
                    output.innerHTML += `❌ Cache clearing failed: ${error.message}\n`;
                });
            }
            
            // Clear localStorage (keep auth tokens)
            try {
                const keysToKeep = ['auth_token', 'csrf_token', 'user_preferences'];
                const keysToRemove = Object.keys(localStorage).filter(key => !keysToKeep.includes(key));
                keysToRemove.forEach(key => {
                    localStorage.removeItem(key);
                    output.innerHTML += `🗑️ Removed localStorage: ${key}\n`;
                });
                output.innerHTML += '✅ localStorage cleared (keeping auth tokens)\n';
            } catch (error) {
                output.innerHTML += `❌ localStorage clearing failed: ${error.message}\n`;
            }
            
            // Clear sessionStorage
            try {
                sessionStorage.clear();
                output.innerHTML += '✅ sessionStorage cleared\n';
            } catch (error) {
                output.innerHTML += `❌ sessionStorage clearing failed: ${error.message}\n`;
            }
        }

        // Check Presensi file
        function checkPresensiFile() {
            const output = document.getElementById('output');
            output.innerHTML = '📁 Checking Presensi file...\n\n';
            
            // Try to fetch the manifest
            fetch('/build/manifest.json')
                .then(response => response.json())
                .then(manifest => {
                    output.innerHTML += '📋 Manifest loaded successfully\n';
                    
                    // Look for Presensi files
                    const presensiFiles = Object.keys(manifest).filter(key => key.includes('Presensi'));
                    output.innerHTML += `📁 Found ${presensiFiles.length} Presensi files:\n`;
                    
                    presensiFiles.forEach(file => {
                        const fileInfo = manifest[file];
                        output.innerHTML += `  - ${file}: ${fileInfo.file}\n`;
                    });
                    
                    // Check if the file exists
                    const presensiJs = presensiFiles.find(key => key.includes('.js'));
                    if (presensiJs) {
                        const filePath = '/build/' + manifest[presensiJs].file;
                        output.innerHTML += `\n🔍 Checking file: ${filePath}\n`;
                        
                        fetch(filePath, { method: 'HEAD' })
                            .then(response => {
                                if (response.ok) {
                                    output.innerHTML += `✅ File exists (${response.status})\n`;
                                    output.innerHTML += `📅 Last Modified: ${response.headers.get('last-modified')}\n`;
                                    output.innerHTML += `📦 Content Length: ${response.headers.get('content-length')} bytes\n`;
                                } else {
                                    output.innerHTML += `❌ File not found (${response.status})\n`;
                                }
                            })
                            .catch(error => {
                                output.innerHTML += `❌ Error checking file: ${error.message}\n`;
                            });
                    }
                })
                .catch(error => {
                    output.innerHTML += `❌ Error loading manifest: ${error.message}\n`;
                });
        }

        // Auto-run test on page load
        window.addEventListener('load', () => {
            testCacheBust();
        });
    </script>
</body>
</html>
