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

class PetugasPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('petugas')
            ->path('petugas')
            ->login(false)
            ->brandName('📋 Dokterku - Petugas')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::rgb('rgb(102, 126, 234)'),
                'secondary' => Color::rgb('rgb(118, 75, 162)'),
                'success' => Color::rgb('rgb(16, 185, 129)'),
                'warning' => Color::rgb('rgb(251, 189, 35)'),
                'danger' => Color::rgb('rgb(239, 68, 68)'),
                'info' => Color::rgb('rgb(58, 191, 248)'),
            ])
            ->darkMode()
            ->spa()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->breadcrumbs(false)
            ->topNavigation(false)
            ->resources([
                \App\Filament\Petugas\Resources\TindakanResource::class,
                \App\Filament\Petugas\Resources\PendapatanHarianResource::class,
                \App\Filament\Petugas\Resources\PengeluaranHarianResource::class,
                \App\Filament\Petugas\Resources\JumlahPasienHarianResource::class,
                \App\Filament\Petugas\Resources\PendapatanResource::class,
                \App\Filament\Petugas\Resources\PasienResource::class,
            ])
            ->pages([
                \App\Filament\Petugas\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Petugas/Widgets'), for: 'App\\Filament\\Petugas\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Petugas\Widgets\NotificationWidget::class,
                \App\Filament\Petugas\Widgets\PetugasStatsWidget::class,
                \App\Filament\Petugas\Widgets\QuickActionsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\PetugasMiddleware::class,
            ])
            ->authGuard('web')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->profile()
            ->tenant(null)
            ->navigationGroups([
                NavigationGroup::make('🏠 Dashboard')
                    ->collapsed(false),
                NavigationGroup::make('📝 Input Data')
                    ->collapsed(false),
                NavigationGroup::make('👥 Manajemen Pasien')
                    ->collapsed(false),
            ]);
    }
}