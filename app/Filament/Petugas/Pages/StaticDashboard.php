<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;

class StaticDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationGroup = '🏠 Dashboard';
    
    protected static ?string $title = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $slug = '/';

    protected static string $view = 'filament.petugas.pages.static-dashboard';
}
