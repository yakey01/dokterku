<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/health.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Force local session configuration for development
        $middleware->prepend(\App\Http\Middleware\ForceLocalSession::class);
        
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
        ]);
        
        // Add security headers to all responses
        // Temporarily disabled for debugging
        // $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        
        // Add session cleanup middleware to web group
        $middleware->web(append: [
            \App\Http\Middleware\ClearStaleSessionMiddleware::class,
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
