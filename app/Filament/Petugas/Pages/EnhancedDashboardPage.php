<?php

namespace App\Filament\Petugas\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Redirect;

class EnhancedDashboardPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    
    protected static ?string $navigationLabel = 'Enhanced Dashboard';
    
    protected static ?string $title = 'Enhanced Dashboard';
    
    protected static ?string $slug = 'enhanced-dashboard-redirect';
    
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.petugas.pages.enhanced-dashboard';
    
    protected static bool $shouldRegisterNavigation = true;
    
    public static function canAccess(): bool
    {
        return true;
    }
    
    public function mount(): void
    {
        // Redirect to the enhanced dashboard with full sidebar
        redirect('/petugas/enhanced-dashboard');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('go_to_enhanced')
                ->label('Buka Enhanced Dashboard')
                ->url('/petugas/enhanced-dashboard')
                ->openUrlInNewTab()
                ->color('success')
                ->icon('heroicon-o-arrow-top-right-on-square'),
        ];
    }
}
