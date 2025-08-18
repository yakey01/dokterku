<?php

namespace App\Filament\Manajer\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class ModernManagerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static string $view = 'filament.manajer.pages.modern-dashboard';
    
    protected static ?string $title = 'Executive Dashboard';
    
    protected static ?string $navigationLabel = 'Modern Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationGroup = '📊 Executive Dashboard';
    
    protected static ?string $slug = 'modern-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
    
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    
    // Disable default Filament layout components
    public function hasLogo(): bool
    {
        return false;
    }
    
    public function hasTopbar(): bool
    {
        return false;
    }
}