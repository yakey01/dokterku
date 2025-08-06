<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProviderWorldClass extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureRoutePatterns();
        $this->configureRouteMiddleware();

        $this->routes(function () {
            // API Routes with versioning
            Route::prefix('api/v1')
                ->middleware(['api', 'throttle:api'])
                ->namespace($this->namespace)
                ->group(base_path('routes/api/v1.php'));
                
            Route::prefix('api/v2')
                ->middleware(['api', 'throttle:api'])
                ->namespace($this->namespace)
                ->group(base_path('routes/api/v2.php'));

            // Web Routes with security headers
            Route::middleware(['web', 'security.headers'])
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
                
            // Admin Routes with enhanced security
            Route::prefix('admin')
                ->middleware(['web', 'auth', 'role:admin', 'security.headers', 'log.activity'])
                ->namespace($this->namespace)
                ->group(base_path('routes/admin.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Authentication rate limiting
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Global rate limiting
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(1000)->by($request->ip());
        });
    }

    /**
     * Configure route patterns for consistent parameter validation.
     */
    protected function configureRoutePatterns(): void
    {
        Route::pattern('id', '[0-9]+');
        Route::pattern('slug', '[a-z0-9-]+');
        Route::pattern('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
        Route::pattern('year', '[0-9]{4}');
        Route::pattern('month', '[0-9]{1,2}');
        Route::pattern('day', '[0-9]{1,2}');
    }

    /**
     * Configure route-specific middleware.
     */
    protected function configureRouteMiddleware(): void
    {
        // Add route-specific middleware aliases if needed
        Route::aliasMiddleware('role', \App\Http\Middleware\CheckRole::class);
        Route::aliasMiddleware('permission', \App\Http\Middleware\CheckPermission::class);
        Route::aliasMiddleware('security.headers', \App\Http\Middleware\SecurityHeaders::class);
        Route::aliasMiddleware('log.activity', \App\Http\Middleware\LogActivity::class);
    }
}