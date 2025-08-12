<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;

class SimpleDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationGroup = '🏠 Dashboard';
    
    protected static ?string $title = 'Dashboard Petugas';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.petugas.pages.simple-dashboard';
    
    protected static ?string $slug = 'dashboard-simple';
}