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
    
    <!-- ULTRA AGGRESSIVE CACHE PREVENTION -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="build-time" content="{{ time() }}">
    <meta name="cache-bust" content="{{ md5(time() . rand()) }}">
    <meta name="emergency-mode" content="true">
    
    <title>KLINIK DOKTERKU - {{ auth()->user()->name ?? 'Dokter' }} (EMERGENCY MODE)</title>
    
    <!-- PWA Meta Tags for Better iOS Experience -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Dokterku">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#6b21a8">
    
    <!-- EMERGENCY MODE - Direct Script Loading -->
    <script>
        console.log('🚨 EMERGENCY MODE ACTIVATED');
        console.log('🕐 Build Time:', '{{ time() }}');
        console.log('🆔 Emergency ID:', '{{ md5(time() . rand()) }}');
        
        // Force clear all caches immediately
        if ('caches' in window) {
            caches.keys().then(names => {
                console.log('🗑️ Emergency cache clear:', names);
                return Promise.all(names.map(name => caches.delete(name)));
            });
        }
        
        // Clear localStorage except auth tokens
        const keysToKeep = ['auth_token', 'csrf_token', 'user_preferences'];
        const keysToRemove = Object.keys(localStorage).filter(key => !keysToKeep.includes(key));
        keysToRemove.forEach(key => localStorage.removeItem(key));
        
        // Clear sessionStorage
        sessionStorage.clear();
        
        console.log('✅ Emergency cache clear completed');
    </script>
    
    <!-- Load React and dependencies directly -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    
    <!-- Load the latest Presensi component directly -->
    <script>
        // Emergency component loader
        (function() {
            'use strict';
            
            console.log('🚨 Loading Presensi component in emergency mode...');
            
            // Load the component directly from the build
            const script = document.createElement('script');
            script.src = '/build/assets/js/Presensi-D5wrZFaU.js?v={{ time() }}&emergency=true';
            script.onload = function() {
                console.log('✅ Presensi component loaded successfully');
            };
            script.onerror = function() {
                console.error('❌ Failed to load Presensi component');
                // Fallback to original app
                window.location.href = '/dokter/mobile-app?emergency-fallback=true';
            };
            document.head.appendChild(script);
        })();
    </script>
    
    <style>
        /* Dark mode initialization script */
        :root {
            color-scheme: light dark;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            transition: background-color 0.3s ease, color 0.3s ease;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .dark body {
            background: #0f172a;
            color: #f1f5f9;
        }
        
        #dokter-app {
            min-height: 100vh;
            width: 100%;
            /* Prevent content from going under bottom navigation */
            padding-bottom: calc(5rem + max(env(safe-area-inset-bottom), 44px));
        }
        
        /* iOS Safari specific fixes */
        @supports (-webkit-touch-callout: none) {
            /* iOS only styles */
            #dokter-app {
                /* Extra padding for Safari browser UI */
                padding-bottom: calc(6rem + env(safe-area-inset-bottom, 44px));
            }
            
            /* Prevent Safari bounce scrolling */
            body {
                position: fixed;
                width: 100%;
                height: 100%;
                overflow: hidden;
            }
            
            #dokter-app {
                position: fixed;
                width: 100%;
                height: 100%;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        
        /* Loading state */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Emergency mode indicator */
        .emergency-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 9999;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Emergency Mode Indicator -->
    <div class="emergency-indicator">
        🚨 EMERGENCY MODE
    </div>
    
    <!-- Loading State -->
    <div class="loading" id="loading">
        <div>
            <div class="loading-spinner"></div>
            <h2>🚨 Emergency Mode</h2>
            <p>Loading Dokterku Mobile App...</p>
            <p><small>Build: {{ time() }} | Version: {{ md5(time() . rand()) }}</small></p>
        </div>
    </div>
    
    <!-- Main App Container -->
    <div id="dokter-app"></div>
    
    <!-- Emergency Fallback -->
    <script>
        // Emergency fallback if component fails to load
        setTimeout(function() {
            const app = document.getElementById('dokter-app');
            if (!app.innerHTML.trim()) {
                console.log('🚨 Component failed to load, showing fallback');
                app.innerHTML = `
                    <div style="padding: 20px; text-align: center;">
                        <h2>🚨 Emergency Mode Active</h2>
                        <p>The application is loading in emergency mode due to cache issues.</p>
                        <p>If this persists, please:</p>
                        <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
                            <li>Clear your browser cache completely</li>
                            <li>Try a different browser</li>
                            <li>Use incognito/private mode</li>
                            <li>Contact support if the issue persists</li>
                        </ul>
                        <br>
                        <button onclick="window.location.reload()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            🔄 Retry Loading
                        </button>
                        <br><br>
                        <button onclick="window.location.href='/dokter/mobile-app'" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            🏠 Go to Main App
                        </button>
                    </div>
                `;
            }
        }, 10000); // 10 second timeout
    </script>
</body>
</html>
