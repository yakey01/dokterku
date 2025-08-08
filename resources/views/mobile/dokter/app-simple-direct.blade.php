<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">
    <meta name="user-data" content="{{ auth()->check() ? json_encode($userData ?? []) : '{}' }}">
    <meta name="api-token" content="{{ $token ?? (auth()->user()?->createToken('mobile-app')?->plainTextToken ?? '') }}">
    <meta name="user-id" content="{{ auth()->id() ?? '' }}">
    <meta name="user-name" content="{{ auth()->user()?->name ?? '' }}">
    
    <!-- PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Dokterku">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#6b21a8">
    
    <title>KLINIK DOKTERKU - {{ auth()->user()->name ?? 'Dokter' }}</title>
    
    <!-- Direct Asset Loading -->
    <link rel="stylesheet" href="/build/assets/css/app-Da1NQV5x.css">
    <script type="module" src="/build/assets/js/dokter-mobile-app-simple-CO-BG53B.js"></script>
    
    <style>
        /* Simple loading styles */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #6b21a8;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Hide loading when app is ready */
        .loading.hidden {
            display: none;
        }
        
        /* Error state */
        .error-state {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #fee2e2;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }
        
        .error-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .error-content h2 {
            color: #dc2626;
            margin-bottom: 15px;
        }
        
        .error-content p {
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .retry-button {
            background: #dc2626;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .retry-button:hover {
            background: #b91c1c;
        }
    </style>
</head>
<body>
    <!-- Loading State -->
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- Error State (hidden by default) -->
    <div id="error-state" class="error-state" style="display: none;">
        <div class="error-content">
            <h2>⚠️ Application Error</h2>
            <p>Failed to load the application. This might be due to missing assets or JavaScript errors.</p>
            <button class="retry-button" onclick="window.location.reload()">🔄 Retry</button>
        </div>
    </div>
    
    <!-- React App Container -->
    <div id="dokter-app"></div>
    
    <!-- Fallback for non-JS users -->
    <noscript>
        <div style="padding: 20px; text-align: center; background: #fee2e2; color: #991b1b; margin: 20px; border-radius: 8px;">
            <h3>JavaScript Required</h3>
            <p>This application requires JavaScript to run properly. Please enable JavaScript in your browser settings.</p>
        </div>
    </noscript>
    
    <script>
        // Simple initialization with error handling
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 DOKTERKU: DOM loaded, app container ready');
            console.log('🎯 Container element:', document.getElementById('dokter-app'));
            
            // Check if container exists
            const container = document.getElementById('dokter-app');
            if (!container) {
                console.error('❌ Container element #dokter-app not found');
                showError('Container element not found');
                return;
            }
            
            // Check if assets are loaded
            setTimeout(function() {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.classList.add('hidden');
                }
                
                // Check if React app was mounted
                if (container.children.length === 0) {
                    console.error('❌ React app was not mounted');
                    showError('Application failed to load. Please refresh the page.');
                } else {
                    console.log('✅ React app mounted successfully');
                }
            }, 5000); // Wait 5 seconds for app to load
        });
        
        function showError(message) {
            const loading = document.getElementById('loading');
            const errorState = document.getElementById('error-state');
            
            if (loading) {
                loading.style.display = 'none';
            }
            
            if (errorState) {
                errorState.style.display = 'flex';
                const errorText = errorState.querySelector('p');
                if (errorText) {
                    errorText.textContent = message;
                }
            }
        }
        
        // Global error handler
        window.addEventListener('error', function(event) {
            console.error('❌ Global error:', event.error);
            showError('Application failed to load due to an error. Please refresh the page.');
        });
        
        // Unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(event) {
            console.error('❌ Unhandled promise rejection:', event.reason);
            showError('Application failed to load due to an error. Please refresh the page.');
        });
    </script>
</body>
</html>
