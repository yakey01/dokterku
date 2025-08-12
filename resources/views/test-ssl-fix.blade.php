<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL Configuration Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Load Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/dokter-mobile-app.tsx'])
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: white;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
        }
        .status-box {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            font-family: monospace;
        }
        .success { color: #51cf66; }
        .error { color: #ff6b6b; }
        .warning { color: #ffd93d; }
        .info { color: #74c0fc; }
        h1 { margin-bottom: 30px; }
        .check-item {
            margin: 10px 0;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 SSL/HTTPS Configuration Test</h1>
        
        <div class="status-box">
            <h3>Configuration Status</h3>
            <div class="check-item">
                <strong>APP_URL:</strong> <span class="info">{{ config('app.url') }}</span>
            </div>
            <div class="check-item">
                <strong>Current URL:</strong> <span class="info" id="current-url"></span>
            </div>
            <div class="check-item">
                <strong>Protocol:</strong> <span class="info" id="protocol"></span>
            </div>
            <div class="check-item">
                <strong>Session Secure:</strong> <span class="{{ config('session.secure') ? 'warning' : 'success' }}">
                    {{ config('session.secure') ? 'Enabled (HTTPS Only)' : 'Disabled (HTTP Allowed)' }}
                </span>
            </div>
            <div class="check-item">
                <strong>Session Domain:</strong> <span class="info">{{ config('session.domain') }}</span>
            </div>
        </div>
        
        <div class="status-box">
            <h3>Asset Loading Test</h3>
            <div id="asset-status">Checking assets...</div>
        </div>
        
        <div class="status-box">
            <h3>React App Mount Test</h3>
            <div id="dokter-app">
                <div class="info">Waiting for React app to mount...</div>
            </div>
        </div>
        
        <div class="status-box">
            <h3>JavaScript Module Test</h3>
            <div id="js-test">Testing JavaScript modules...</div>
        </div>
    </div>
    
    <script>
        // Display current URL info
        document.getElementById('current-url').textContent = window.location.href;
        document.getElementById('protocol').textContent = window.location.protocol;
        
        // Test asset loading
        function testAssetLoading() {
            const statusEl = document.getElementById('asset-status');
            let loadedCount = 0;
            let errorCount = 0;
            const assets = [];
            
            // Get all script tags
            document.querySelectorAll('script[src]').forEach(script => {
                assets.push({type: 'JavaScript', url: script.src});
            });
            
            // Get all link tags (CSS)
            document.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
                assets.push({type: 'CSS', url: link.href});
            });
            
            // Get all module preloads
            document.querySelectorAll('link[rel="modulepreload"]').forEach(link => {
                assets.push({type: 'Module Preload', url: link.href});
            });
            
            if (assets.length === 0) {
                statusEl.innerHTML = '<div class="warning">No assets found to test</div>';
                return;
            }
            
            statusEl.innerHTML = `<div class="info">Testing ${assets.length} assets...</div>`;
            
            // Test each asset
            assets.forEach((asset, index) => {
                fetch(asset.url, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            loadedCount++;
                            statusEl.innerHTML += `<div class="success">✅ ${asset.type}: ${asset.url.split('/').pop()}</div>`;
                        } else {
                            errorCount++;
                            statusEl.innerHTML += `<div class="error">❌ ${asset.type}: ${asset.url.split('/').pop()} (${response.status})</div>`;
                        }
                        
                        if (loadedCount + errorCount === assets.length) {
                            statusEl.innerHTML += `<div style="margin-top: 10px; font-weight: bold;">
                                Summary: ${loadedCount} loaded, ${errorCount} failed
                            </div>`;
                        }
                    })
                    .catch(error => {
                        errorCount++;
                        statusEl.innerHTML += `<div class="error">❌ ${asset.type}: ${error.message}</div>`;
                    });
            });
        }
        
        // Test JavaScript execution
        function testJavaScript() {
            const testEl = document.getElementById('js-test');
            try {
                // Test if React is loaded
                if (typeof React !== 'undefined') {
                    testEl.innerHTML = '<div class="success">✅ React is loaded</div>';
                } else {
                    testEl.innerHTML = '<div class="warning">⚠️ React not yet loaded</div>';
                }
                
                // Test if our app code is loaded
                if (typeof window.dokterKuDebug !== 'undefined') {
                    testEl.innerHTML += '<div class="success">✅ DokterKu Debug utilities loaded</div>';
                } else {
                    testEl.innerHTML += '<div class="warning">⚠️ DokterKu utilities not yet loaded</div>';
                }
                
                // Check for SSL errors in console
                testEl.innerHTML += '<div class="info">Check browser console for SSL errors</div>';
                
            } catch (error) {
                testEl.innerHTML = `<div class="error">❌ JavaScript Error: ${error.message}</div>`;
            }
        }
        
        // Run tests when page loads
        window.addEventListener('load', () => {
            testAssetLoading();
            setTimeout(testJavaScript, 1000); // Give React time to load
        });
        
        // Listen for SSL errors
        window.addEventListener('error', (event) => {
            if (event.message && event.message.includes('SSL')) {
                const statusEl = document.getElementById('asset-status');
                statusEl.innerHTML += `<div class="error">🚨 SSL Error: ${event.message}</div>`;
            }
        }, true);
    </script>
</body>
</html>