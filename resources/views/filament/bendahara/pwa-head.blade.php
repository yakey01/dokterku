{{-- PWA Meta Tags for Bendahara Dashboard --}}
<meta name="application-name" content="Dokterku Bendahara">
<meta name="description" content="Dashboard bendahara untuk validasi transaksi dan manajemen keuangan Dokterku">
<meta name="theme-color" content="#fbbf23">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Bendahara">
<meta name="mobile-web-app-capable" content="yes">
<meta name="msapplication-TileColor" content="#fbbf23">
<meta name="msapplication-tap-highlight" content="no">

{{-- PWA Manifest --}}
<link rel="manifest" href="{{ asset('pwa/bendahara-manifest.json') }}">

{{-- iOS Specific Meta Tags --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Bendahara">

{{-- Apple Touch Icons --}}
<link rel="apple-touch-icon" href="{{ asset('pwa/icon-192x192.png') }}">
<link rel="apple-touch-icon" sizes="152x152" href="{{ asset('pwa/icon-152x152.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('pwa/icon-192x192.png') }}">
<link rel="apple-touch-icon" sizes="167x167" href="{{ asset('pwa/icon-192x192.png') }}">

{{-- Splash Screen Images for iOS --}}
<link rel="apple-touch-startup-image" 
      href="{{ asset('pwa/splash-640x1136.png') }}" 
      media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" 
      href="{{ asset('pwa/splash-750x1334.png') }}" 
      media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" 
      href="{{ asset('pwa/splash-1242x2208.png') }}" 
      media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
<link rel="apple-touch-startup-image" 
      href="{{ asset('pwa/splash-1125x2436.png') }}" 
      media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">

{{-- Microsoft Tiles --}}
<meta name="msapplication-TileImage" content="{{ asset('pwa/icon-144x144.png') }}">
<meta name="msapplication-TileColor" content="#fbbf23">
<meta name="msapplication-config" content="{{ asset('pwa/browserconfig.xml') }}">

{{-- Preload critical resources --}}
<link rel="modulepreload" href="{{ asset('pwa/service-worker.js') }}">
{{-- Theme CSS will be loaded by Filament automatically --}}

{{-- Cache control for PWA resources --}}
<meta http-equiv="Cache-Control" content="max-age=31536000, immutable" data-pwa-resource>

{{-- Viewport optimizations for mobile --}}
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

{{-- Security headers for PWA --}}
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: ui-avatars.com; font-src 'self' data:; connect-src 'self' ws: wss:;">

{{-- Performance hints --}}
{{-- Using built-in system fonts instead of external fonts --}}

{{-- Touch and interaction optimizations --}}
<style>
    /* PWA-specific optimizations scoped to bendahara panel */
    @media (display-mode: standalone) {
        [data-filament-panel-id="bendahara"] body {
            -webkit-user-select: none;
            -webkit-touch-callout: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Hide scrollbars in standalone mode */
        [data-filament-panel-id="bendahara"] ::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }
        
        /* Optimize touch targets for bendahara */
        [data-filament-panel-id="bendahara"] button, 
        [data-filament-panel-id="bendahara"] .fi-btn, 
        [data-filament-panel-id="bendahara"] [role="button"] {
            min-height: 44px;
            min-width: 44px;
        }
        
        /* Improve mobile interactions for bendahara */
        [data-filament-panel-id="bendahara"] .fi-ta-cell {
            padding: 0.75rem 1rem;
        }
        
        /* Enhanced mobile support for bendahara - using Filament responsive classes */
        @media (max-width: 768px) {
            [data-filament-panel-id="bendahara"] {
                /* Let Filament handle mobile responsiveness */
            }
        }
    }
    
    /* Specific panel isolation styles */
    [data-filament-panel-id="bendahara"] {
        /* Override any global Filament styles */
        isolation: isolate;
        contain: layout style;
    }
    
    /* Minimal bendahara panel styling - let Filament handle the rest */
    [data-filament-panel-id="bendahara"] {
        /* Keep only essential branding overrides */
    }
</style>