<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">
    <meta name="user-data" content="{{ auth()->check() ? json_encode($userData ?? []) : '{}' }}">
    <meta name="api-token" content="{{ $token ?? '' }}">
    <title>KLINIK PARAMEDISKU - {{ auth()->user()->name ?? 'Paramedis' }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Using built-in system fonts -->
    
    <!-- ULTRAFIX: Force clear all caches before loading app -->
    <!-- DISABLED: <script src="/ultrafix.js.disabled"></script> -->
    
    <!-- DEBUG MONITOR: Ultimate debugging (temporary) -->
    <!-- DISABLED: <script src="/debug-monitor.js"></script> -->
    
    <!-- Vite Assets -->
    @vite(['resources/js/paramedis-mobile-app.tsx'])
    
    <style>
        /* Dark mode initialization script */
        :root {
            color-scheme: light dark;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
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
        
        #paramedis-app {
            min-height: 100vh;
            width: 100%;
        }
        
        /* Loading state */
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
        
        /* PWA styles */
        @media screen and (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 0 16px;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
                color: #f1f5f9;
            }
        }
        
        /* Safe area support for iOS */
        @supports (padding-top: env(safe-area-inset-top)) {
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
            }
        }
    </style>
    
    <!-- ULTIMATE+ theme initialization with Alpine.js isolation -->
    <script>
        // ULTIMATE+ FIX: Complete isolation from Alpine.js and external scripts
        (function() {
            'use strict';
            
            // 🚨 CRITICAL: Block Alpine.js from accessing document.body on this page
            if (typeof window !== 'undefined') {
                // Isolate this page from global Alpine.js
                window.__PARAMEDISKU_ISOLATED__ = true;
                
                // Override any potential document.body.classList access
                const originalBody = document.body;
                if (originalBody) {
                    const originalClassList = originalBody.classList;
                    const safeClassList = {
                        add: function() { 
                            console.warn('🛡️ PARAMEDISKU: Blocked document.body.classList.add - using documentElement instead');
                            return document.documentElement.classList.add.apply(document.documentElement.classList, arguments);
                        },
                        remove: function() { 
                            console.warn('🛡️ PARAMEDISKU: Blocked document.body.classList.remove - using documentElement instead');
                            return document.documentElement.classList.remove.apply(document.documentElement.classList, arguments);
                        },
                        toggle: function() { 
                            console.warn('🛡️ PARAMEDISKU: Blocked document.body.classList.toggle - using documentElement instead');
                            return document.documentElement.classList.toggle.apply(document.documentElement.classList, arguments);
                        },
                        contains: function() { 
                            return document.documentElement.classList.contains.apply(document.documentElement.classList, arguments);
                        },
                        length: originalClassList ? originalClassList.length : 0,
                        item: originalClassList ? originalClassList.item.bind(originalClassList) : function() { return null; },
                        toString: originalClassList ? originalClassList.toString.bind(originalClassList) : function() { return ''; }
                    };
                    
                    // Override classList access for this page only
                    Object.defineProperty(originalBody, 'classList', {
                        get: function() { return safeClassList; },
                        configurable: true
                    });
                }
            }
            
            // Create our own isolated scope and error handling
            const ULTIMATE_SAFE_INIT = function() {
                try {
                    // NEVER touch document.body - ONLY documentElement
                    if (typeof document !== 'undefined' && 
                        document.documentElement && 
                        document.documentElement.classList) {
                        
                        let theme = 'light';
                        
                        // Safe localStorage access
                        try {
                            const savedTheme = localStorage.getItem('theme');
                            if (savedTheme === 'dark' || savedTheme === 'light') {
                                theme = savedTheme;
                            }
                        } catch (e) {
                            // localStorage blocked - use system preference
                        }
                        
                        // Safe system preference check
                        if (theme === 'light') {
                            try {
                                if (window.matchMedia && 
                                    window.matchMedia('(prefers-color-scheme: dark)').matches) {
                                    theme = 'dark';
                                }
                            } catch (e) {
                                // matchMedia not available - keep light
                            }
                        }
                        
                        // Safe theme application
                        if (theme === 'dark') {
                            document.documentElement.classList.add('dark');
                        }
                        
                        // Store for React (completely isolated)
                        window.__PARAMEDISKU_THEME__ = theme;
                        
                        console.log('🎯 PARAMEDISKU: Theme initialized safely with Alpine.js protection');
                    }
                } catch (e) {
                    // Complete error isolation - set safe defaults
                    window.__PARAMEDISKU_THEME__ = 'light';
                    console.error('🔥 PARAMEDISKU: Theme init failed, using safe defaults:', e);
                }
            };
            
            // Execute immediately with complete error isolation
            ULTIMATE_SAFE_INIT();
            
        })();
    </script>
    
    <!-- Alpine.js Isolation Script -->
    <script>
        // Prevent Alpine.js from running on this specific page
        if (typeof window !== 'undefined') {
            // Block Alpine.js initialization for this page
            document.addEventListener('alpine:init', function(e) {
                if (window.__PARAMEDISKU_ISOLATED__) {
                    console.log('🛡️ PARAMEDISKU: Blocked Alpine.js initialization on isolated page');
                    e.stopImmediatePropagation();
                    e.preventDefault();
                }
            }, true);
            
            // Block Alpine.js from processing this page
            if (document.documentElement) {
                document.documentElement.setAttribute('data-no-alpine', 'true');
            }
        }
    </script>
</head>
<body>
    <!-- Loading State -->
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- React App Container -->
    <div id="paramedis-app"></div>
    
    <!-- Fallback for non-JS users -->
    <noscript>
        <div style="padding: 20px; text-align: center; background: #fee2e2; color: #991b1b; margin: 20px; border-radius: 8px;">
            <h3>JavaScript Required</h3>
            <p>This application requires JavaScript to run properly. Please enable JavaScript in your browser settings.</p>
        </div>
    </noscript>
    
    <!-- Service Worker Registration -->
    <script>
        // Hide loading screen once app is ready
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.style.display = 'none';
                }
            }, 1000);
        });
        
        // Register service worker for PWA capabilities (disabled temporarily)
        if ('serviceWorker' in navigator && false) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
</body>
</html>