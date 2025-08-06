<?php

namespace App\Filament\Paramedis\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.paramedis.pages.dashboard';
    
    protected static ?string $title = '🩺 Dashboard Paramedis';
    
    protected static ?int $navigationSort = 1;
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Paramedis\Widgets\QuickAccessWidget::class,
            \App\Filament\Paramedis\Widgets\AttendanceHistoryStatsWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 1,
            'lg' => 2,
            'xl' => 2,
        ];
    }
}