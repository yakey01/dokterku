<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">
    <meta name="user-data" content="{{ auth()->check() ? json_encode($userData ?? []) : '{}' }}">
    <meta name="api-token" content="{{ $token ?? '' }}">
    <title>KLINIK DOKTERKU - {{ auth()->user()->name ?? 'Dokter' }}</title>
    
    <!-- 🚀 DYNAMIC BUNDLE LOADING WITH TDZ PROTECTION -->
    <script>
        // Enhanced bundle loading with manifest-based resolution
        async function loadDokterBundle() {
            try {
                console.log('🔍 Loading dokter bundle manifest...');
                
                // Clean up old scripts
                document.querySelectorAll('script[src*="dokter-mobile-app"]').forEach(s => {
                    console.log('🧹 Removing old script:', s.src);
                    s.remove();
                });
                
                // Fetch current manifest to get actual file names
                const manifest = await fetch('/build/manifest.json?_=' + Date.now())
                    .then(res => res.ok ? res.json() : null)
                    .catch(() => null);
                
                let jsFile, cssFiles;
                
                if (manifest && manifest['resources/js/dokter-mobile-app.tsx']) {
                    const entry = manifest['resources/js/dokter-mobile-app.tsx'];
                    jsFile = '/build/' + entry.file;
                    cssFiles = entry.css || [];
                    console.log('✅ Manifest loaded:', { jsFile, cssFiles });
                } else {
                    // Fallback to current known file
                    jsFile = '/build/assets/js/dokter-mobile-app-CvOO7H1f.js';
                    cssFiles = ['assets/css/dokter-mobile-app-Bj_ExP9V.css', 'assets/css/app-BSR2ULlx.css'];
                    console.warn('⚠️ Using fallback file paths');
                }
                
                // Load CSS first
                cssFiles.forEach(cssFile => {
                    const existing = document.querySelector(`link[href*="${cssFile.split('/').pop().split('-')[0]}"]`);
                    if (!existing) {
                        const link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = '/build/' + cssFile + '?_=' + Date.now();
                        document.head.appendChild(link);
                        console.log('📄 CSS loaded:', link.href);
                    }
                });
                
                // Load JS with enhanced error handling
                const script = document.createElement('script');
                script.type = 'module';
                script.src = jsFile + '?_=' + Date.now();
                
                script.onload = function() {
                    console.log('✅ Dokter bundle loaded successfully:', jsFile);
                    // Hide loading immediately
                    const loading = document.getElementById('loading');
                    if (loading) {
                        loading.style.transition = 'opacity 0.3s ease-out';
                        loading.style.opacity = '0';
                        setTimeout(() => loading.style.display = 'none', 300);
                    }
                };
                
                script.onerror = function(error) {
                    console.error('❌ Failed to load dokter bundle:', jsFile, error);
                    showCriticalError('Script loading failed', 'Could not load ' + jsFile);
                };
                
                document.head.appendChild(script);
                
            } catch (error) {
                console.error('🚨 Bundle loading error:', error);
                showCriticalError('Bundle loading failed', error.message);
            }
        }
        
        function showCriticalError(title, message) {
            const container = document.getElementById('dokter-app') || document.body;
            container.innerHTML = `
                <div style="min-height: 100vh; background: linear-gradient(135deg, #0f172a 0%, #581c87 50%, #0f172a 100%); color: white; display: flex; align-items: center; justify-content: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px;">
                    <div style="background: rgba(30, 41, 59, 0.9); border: 2px solid #8b5cf6; border-radius: 16px; padding: 40px; max-width: 500px; text-align: center; backdrop-filter: blur(10px);">
                        <div style="font-size: 48px; margin-bottom: 20px;">🚨</div>
                        <h1 style="margin: 0 0 20px 0; font-size: 24px; color: #ef4444;">${title}</h1>
                        <p style="margin: 0 0 30px 0; color: #d1d5db; line-height: 1.6;">${message}</p>
                        <button onclick="location.reload()" style="background: linear-gradient(to right, #06b6d4, #8b5cf6); border: none; color: white; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: bold;">🔄 Reload Page</button>
                    </div>
                </div>
            `;
        }
        
        // Initialize with delay to prevent TDZ issues
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(loadDokterBundle, 100);
            });
        } else {
            setTimeout(loadDokterBundle, 100);
        }
    </script>
    
    <!-- Using built-in system fonts -->
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
        }
        
        #dokter-app {
            min-height: 100vh;
            width: 100%;
        }
        
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
    </div>
    
    <div id="dokter-app"></div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.style.display = 'none';
                }
            }, 1000);
        });
    </script>
</body>
</html>