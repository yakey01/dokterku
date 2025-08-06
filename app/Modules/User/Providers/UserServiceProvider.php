<?php

namespace App\Modules\User\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Interfaces\UserServiceInterface;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Services\UserService;

/**
 * User Module Service Provider
 * Registers all dependencies and bindings for User module
 */
class UserServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // Bind service interface to implementation
        $this->app->bind(
            UserServiceInterface::class,
            UserService::class
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Load module routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // Load module migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Load module views
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'user');

        // Load module translations
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/Lang', 'user');

        // Publish module config
        $this->publishes([
            __DIR__ . '/../Config/user.php' => config_path('modules/user.php'),
        ], 'user-config');

        // Register module commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Add module-specific commands here
            ]);
        }
    }
}