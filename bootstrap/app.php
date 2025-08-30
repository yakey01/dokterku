<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/health.php'));
                
            // Load bendahara-specific routes
            Route::middleware('web')
                ->group(base_path('routes/bendahara.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'enhanced.role' => \App\Http\Middleware\EnhancedRoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'manajer' => \App\Http\Middleware\ManajerMiddleware::class,
            'petugas' => \App\Http\Middleware\PetugasMiddleware::class,
            'paramedis' => \App\Http\Middleware\ParamedisMiddleware::class,
            'verifikator' => \App\Http\Middleware\VerifikatorMiddleware::class,
            'device.binding' => \App\Http\Middleware\DeviceBindingMiddleware::class,
            'anti.gps.spoofing' => \App\Http\Middleware\AntiGpsSpoofingMiddleware::class,
            // API v2 middleware
            'api.rate.limit' => \App\Http\Middleware\Api\ApiRateLimitMiddleware::class,
            'api.response.headers' => \App\Http\Middleware\Api\ApiResponseHeadersMiddleware::class,
            // Performance middleware
            'cache.response' => \App\Http\Middleware\CacheResponseMiddleware::class,
            'log.requests' => \App\Http\Middleware\LogRequestsMiddleware::class,
            // CSRF token refresh middleware
            'refresh.csrf' => \App\Http\Middleware\RefreshCsrfToken::class,
            // Session cleanup middleware
            'session.cleanup' => \App\Http\Middleware\SessionCleanupMiddleware::class,
            // Clear stale session middleware
            'clear.stale.session' => \App\Http\Middleware\ClearStaleSessionMiddleware::class,
            // Debug API requests middleware
            'log.api.requests' => \App\Http\Middleware\LogApiRequests::class,
            // Filament CSRF protection
            'filament.csrf' => \App\Http\Middleware\FilamentCsrfProtection::class,
            // Livewire debug middleware
            'livewire.debug' => \App\Http\Middleware\LivewireDebugMiddleware::class,
        ]);
        
        // Add security headers to all responses
        // Temporarily disabled for debugging
        // $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        
        // Configure web middleware group with essential middleware - FIXED ORDER
        $middleware->web([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\ForceLocalSession::class, // MOVED BEFORE ShareErrors
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\PersistentCsrfToken::class, // CSRF re-enabled
            \App\Http\Middleware\VerifyCsrfToken::class, // CSRF verification re-enabled
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\LivewireDebugMiddleware::class, // Livewire debugging
            // \App\Http\Middleware\ClearStaleSessionMiddleware::class, // TEMPORARILY DISABLED
        ]);
        
        // Add API request logging to API group for debugging
        $middleware->api(append: [
            \App\Http\Middleware\LogApiRequests::class,
        ]);
        
        // Add rate limiting to authentication routes
        $middleware->group('auth', [
            'throttle:60,1',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
