<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationGroup = null; // Top level
    
    protected static ?string $title = 'Dashboard Petugas';
    
    protected static ?int $navigationSort = -1; // First in list
    
    protected static ?string $slug = '/'; // Default page

    public function getWidgets(): array
    {
        return [
            // Completely static widget - no database calls, no Livewire state
            \App\Filament\Petugas\Widgets\PetugasStaticDashboardWidget::class,
        ];
    }
    
    public function getWidgetsColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}