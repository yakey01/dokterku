<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register GPS validation service
        $this->app->singleton(\App\Services\GpsValidationService::class);
        
        // Register session manager service
        $this->app->singleton(\App\Services\SessionManager::class);
        
        // Register token service
        $this->app->singleton(\App\Services\TokenService::class);
        
        // Register biometric service
        $this->app->singleton(\App\Services\BiometricService::class);
        
        // Register custom logout response for all Filament panels
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LogoutResponse::class,
            \App\Http\Responses\Auth\LogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production or when using ngrok
        if (str_contains(config('app.url'), 'https://')) {
            \URL::forceScheme('https');
        }
        
        // Set Carbon locale to Indonesian
        \Carbon\Carbon::setLocale(config('app.locale', 'id'));
        
        // Set default timezone for Carbon
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
        
        // Add CSRF token to Filament views
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => '<meta name="csrf-token" content="' . csrf_token() . '">'
        );
        
        // Register audit observer for automated logging
        $this->registerAuditObserver();
        
        // Register work location observer for real-time updates
        $this->registerWorkLocationObserver();
        
        // Register tindakan observer for real-time JASPEL sync
        $this->registerTindakanObserver();
    }

    /**
     * Register audit observer for automatic model logging
     */
    private function registerAuditObserver(): void
    {
        $auditableModels = [
            \App\Models\User::class,
            \App\Models\SystemSetting::class,
            \App\Models\FeatureFlag::class,
            \App\Models\Pasien::class,
            \App\Models\Tindakan::class,
            \App\Models\Pendapatan::class,
            \App\Models\Pengeluaran::class,
            \App\Models\Role::class,
            \App\Models\Pegawai::class,
            \App\Models\Dokter::class,
            \App\Models\TelegramSetting::class,
        ];

        foreach ($auditableModels as $model) {
            if (class_exists($model)) {
                $model::observe(\App\Observers\AuditObserver::class);
            }
        }
    }

    /**
     * Register work location observer for real-time geofencing updates
     */
    private function registerWorkLocationObserver(): void
    {
        \App\Models\WorkLocation::observe(\App\Observers\WorkLocationObserver::class);
    }
    
    /**
     * Register tindakan observer for real-time JASPEL synchronization
     */
    private function registerTindakanObserver(): void
    {
        \App\Models\Tindakan::observe(\App\Observers\TindakanObserver::class);
    }
}
