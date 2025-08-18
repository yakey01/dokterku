<?php

namespace App\Filament\Manajer\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.manajer.pages.dashboard';
    
    protected static ?string $title = 'Dashboard';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $slug = 'dashboard';
    
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }
    
    public function mount(): void
    {
        // Pass auth token to the view
        $this->generateAuthToken();
    }
    
    protected function generateAuthToken(): void
    {
        $user = Auth::user();
        if ($user) {
            // Get or create a Sanctum token for API access
            $token = $user->createToken('manajer-dashboard', ['*']);
            session(['manajer_api_token' => $token->plainTextToken]);
        }
    }
    
    public function getMaxContentWidth(): MaxWidth
    {
        // Full width for mobile-responsive dashboard
        return MaxWidth::Full;
    }
    
    protected function getViewData(): array
    {
        return [
            'apiToken' => session('manajer_api_token'),
            'user' => Auth::user(),
        ];
    }
}