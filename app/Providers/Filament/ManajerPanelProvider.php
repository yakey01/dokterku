<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Auth\CustomLogin;

class ManajerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('manajer')
            ->path('manajer')
            ->login()
            ->brandName('🏢 Executive Suite')
            ->viteTheme('resources/css/filament/manajer/theme.css')
            ->assets([
                // Disable default Filament assets to prevent conflicts
            ])
            ->colors([
                'primary' => Color::hex('#6366F1'), // Elegant Indigo
                'secondary' => Color::hex('#818CF8'), // Indigo Light
                'success' => Color::hex('#22C55E'), // Modern Green
                'warning' => Color::hex('#FBBF24'), // Refined Amber
                'danger' => Color::hex('#EF4444'), // Elegant Red
                'info' => Color::hex('#3B82F6'), // Professional Blue
                'gray' => Color::hex('#6B7280'), // Sophisticated Gray
            ])
            ->darkMode(true)
            ->resources([
                // 🎯 Strategic Planning & Goals
                \App\Filament\Manajer\Resources\StrategicGoalResource::class,
                
                // 📊 Performance Analytics  
                \App\Filament\Manajer\Resources\DepartmentPerformanceResource::class,
                \App\Filament\Manajer\Resources\OperationalAnalyticsResource::class,
                
                // ✅ Approval Workflows
                \App\Filament\Manajer\Resources\HighValueApprovalResource::class,
                
                // 👥 Staff Management
                \App\Filament\Manajer\Resources\EmployeePerformanceResource::class,
                \App\Filament\Manajer\Resources\LeaveApprovalResource::class,
                
                // 💰 Financial Control
                \App\Filament\Manajer\Resources\FinancialOversightResource::class,
            ])
            ->pages([
                // New unified dashboard
                \App\Filament\Manajer\Pages\Dashboard::class,
            ])
            ->widgets([
                // Minimal widgets - dashboard is handled by React
                Widgets\AccountWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('📊 Dashboard & Analytics')
                    ->collapsed(false),
                NavigationGroup::make('🎯 Strategic Planning')
                    ->collapsed(false),
                NavigationGroup::make('📊 Performance Analytics')
                    ->collapsed(false),
                NavigationGroup::make('✅ Approval Workflows')
                    ->collapsed(false),
                NavigationGroup::make('👥 Staff Management')
                    ->collapsed(true),
                NavigationGroup::make('💰 Financial Control')
                    ->collapsed(true),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \App\Http\Middleware\SessionCleanupMiddleware::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                \App\Http\Middleware\RefreshCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                \App\Http\Middleware\RedirectToUnifiedAuth::class,
                Authenticate::class,
                \App\Http\Middleware\ManajerMiddleware::class,
            ])
            ->authGuard('web');
    }
}