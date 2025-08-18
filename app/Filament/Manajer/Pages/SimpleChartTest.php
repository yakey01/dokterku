<?php

namespace App\Filament\Manajer\Pages;

use Filament\Pages\Page;

class SimpleChartTest extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    
    protected static string $view = 'filament.manajer.pages.simple-chart-test';
    
    protected static ?string $title = '🧪 Simple Chart Test';
    
    protected static ?string $navigationLabel = '🧪 Simple Chart Test';
    
    protected static ?int $navigationSort = 100;
    
    protected static ?string $navigationGroup = '📊 Dashboard & Analytics';
    
    protected static ?string $slug = 'simple-chart-test';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true; // Always show in navigation
    }
}