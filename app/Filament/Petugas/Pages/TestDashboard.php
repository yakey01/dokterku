<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;

class TestDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.petugas.pages.test-world-class';
    
    protected static ?string $title = 'Test Dashboard';
    
    protected static ?string $navigationLabel = 'Test Dashboard';
    
    protected static ?int $navigationSort = -2;
    
    protected static ?string $navigationGroup = null;

    public static function canAccess(): bool
    {
        // Allow all authenticated users in petugas panel - simplified for debugging
        return auth()->check();
    }

    public function mount(): void
    {
        // Simple test dashboard
    }
}