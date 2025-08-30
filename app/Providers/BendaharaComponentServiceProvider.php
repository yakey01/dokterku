<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Filament\Bendahara\Components\BlackThemeCard;

class BendaharaComponentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register Bendahara-specific Blade components
        Blade::component('filament-bendahara::black-theme-card', BlackThemeCard::class);
    }
}