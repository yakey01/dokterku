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
    
    <!-- CREATIVE CACHE PREVENTION -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="build-time" content="{{ time() }}">
    <meta name="cache-bust" content="{{ md5(time() . rand()) }}">
    <meta name="creative-mode" content="true">
    <meta name="emergency-version" content="2.0">
    
    <title>KLINIK DOKTERKU - {{ auth()->user()->name ?? 'Dokter' }} (CREATIVE MODE)</title>
    
    <!-- PWA Meta Tags for Better iOS Experience -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Dokterku">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#6b21a8">
    
    <!-- CREATIVE MODE - Advanced Cache Busting -->
    <script>
        console.log('🎨 CREATIVE MODE ACTIVATED');
        console.log('🕐 Build Time:', '{{ time() }}');
        console.log('🆔 Creative ID:', '{{ md5(time() . rand()) }}');
        console.log('🚀 Emergency Version: 2.0');
        
        // Advanced cache clearing
        (async function() {
            try {
                // Clear all caches
                if ('caches' in window) {
                    const cacheNames = await caches.keys();
                    console.log('🗑️ Clearing caches:', cacheNames);
                    await Promise.all(cacheNames.map(name => caches.delete(name)));
                    console.log('✅ All caches cleared');
                }
                
                // Clear all storage
                localStorage.clear();
                sessionStorage.clear();
                console.log('✅ All storage cleared');
                
                // Clear IndexedDB if available
                if ('indexedDB' in window) {
                    const databases = await indexedDB.databases();
                    databases.forEach(db => {
                        if (db.name) {
                            indexedDB.deleteDatabase(db.name);
                            console.log('🗑️ Deleted IndexedDB:', db.name);
                        }
                    });
                }
                
                console.log('✅ Advanced cache clearing completed');
            } catch (error) {
                console.warn('⚠️ Cache clearing failed:', error);
            }
        })();
    </script>
    
    <!-- Load React and dependencies -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    
    <!-- Vite Assets with Creative Cache Busting -->
    @vite(['resources/js/dokter-mobile-app-emergency.tsx'])
    
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
        
        /* Creative mode indicator */
        .creative-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
            background-size: 400% 400%;
            animation: gradientShift 3s ease infinite;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            z-index: 9999;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Creative loading animation */
        .creative-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientShift 3s ease infinite;
            color: white;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .creative-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body>
    <!-- Creative Mode Indicator -->
    <div class="creative-indicator">
        🎨 CREATIVE MODE v2.0
    </div>
    
    <!-- Creative Loading State -->
    <div class="creative-loading" id="loading">
        <div>
            <div class="creative-spinner"></div>
            <h2>🎨 Creative Mode</h2>
            <p>Loading Dokterku Mobile App with Creative Cache Busting...</p>
            <p><small>Build: {{ time() }} | Version: 2.0 | ID: {{ md5(time() . rand()) }}</small></p>
        </div>
    </div>
    
    <!-- Main App Container -->
    <div id="dokter-app"></div>
    
    <!-- Creative Fallback -->
    <script>
        // Creative fallback if component fails to load
        setTimeout(function() {
            const app = document.getElementById('dokter-app');
            const loading = document.getElementById('loading');
            
            if (!app.innerHTML.trim()) {
                console.log('🎨 Component failed to load, showing creative fallback');
                
                if (loading) {
                    loading.style.display = 'none';
                }
                
                app.innerHTML = `
                    <div style="padding: 20px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
                        <div style="max-width: 500px;">
                            <h2 style="margin-bottom: 20px;">🎨 Creative Mode Active</h2>
                            <p style="margin-bottom: 20px;">The application is running in creative mode with advanced cache busting.</p>
                            <p style="margin-bottom: 20px;">If you see this message, the creative component failed to load.</p>
                            <div style="display: flex; flex-direction: column; gap: 10px; align-items: center;">
                                <button onclick="window.location.reload()" style="padding: 12px 24px; background: rgba(255,255,255,0.2); color: white; border: 2px solid white; border-radius: 25px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">
                                    🔄 Retry Loading
                                </button>
                                <button onclick="window.location.href='/dokter/mobile-app'" style="padding: 12px 24px; background: rgba(255,255,255,0.2); color: white; border: 2px solid white; border-radius: 25px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">
                                    🏠 Go to Main App
                                </button>
                                <button onclick="window.location.href='/test-cache-bust.php'" style="padding: 12px 24px; background: rgba(255,255,255,0.2); color: white; border: 2px solid white; border-radius: 25px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">
                                    🧪 Test Cache Bust
                                </button>
                                <button onclick="window.location.href='/dokter/mobile-app-emergency'" style="padding: 12px 24px; background: rgba(255,255,255,0.2); color: white; border: 2px solid white; border-radius: 25px; cursor: pointer; font-weight: bold; transition: all 0.3s ease;">
                                    🚨 Emergency Mode
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Hide loading if component loaded successfully
                if (loading) {
                    loading.style.display = 'none';
                }
            }
        }, 15000); // 15 second timeout
    </script>
</body>
</html>
