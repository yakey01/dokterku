<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AdminProfileWidget extends Widget
{
    protected static string $view = 'filament.widgets.admin-profile-widget';
    
    protected static ?int $sort = -10; // Show at top of sidebar
    
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }
    
    public function getData(): array
    {
        $user = Auth::user();
        
        return [
            'user' => $user,
            'name' => $user->name ?? 'Admin',
            'email' => $user->email,
            'role' => $user->role ?? 'admin',
            'avatar' => $user->avatar_url ?? null,
            'last_login' => $user->last_login_at ?? now(),
        ];
    }
}