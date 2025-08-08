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
    
    <!-- Vite Assets -->
    @vite(['resources/js/dokter-mobile-app-simple.tsx'])
    
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
    </style>
</head>
<body>
    <!-- Loading State -->
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
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
        // Simple initialization
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 DOKTERKU: DOM loaded, app container ready');
            console.log('🎯 Container element:', document.getElementById('dokter-app'));
            
            // Hide loading after 3 seconds
            setTimeout(function() {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.classList.add('hidden');
                }
            }, 3000);
        });
    </script>
</body>
</html>
