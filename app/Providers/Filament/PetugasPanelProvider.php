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
            ->login(CustomLogin::class)
            ->brandName('Petugas Dashboard')
            ->viteTheme([
                'resources/css/filament/petugas/theme.css',
            ])
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->resources([
                // Patient Management
                \App\Filament\Petugas\Resources\PasienResource::class,
                \App\Filament\Petugas\Resources\JumlahPasienHarianResource::class,
                
                // Medical Actions
                \App\Filament\Petugas\Resources\TindakanResource::class,
                
                // Financial Management
                \App\Filament\Petugas\Resources\PendapatanHarianResource::class,
                \App\Filament\Petugas\Resources\PengeluaranHarianResource::class,
                \App\Filament\Petugas\Resources\ValidasiPendapatanResource::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Dashboard')
                    ->collapsed(false),
                NavigationGroup::make('Manajemen Pasien')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Tindakan Medis')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Keuangan')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Laporan & Analytics')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('Quick Actions')
                    ->collapsed(true)
                    ->collapsible(),
                NavigationGroup::make('System')
                    ->collapsed(true)
                    ->collapsible(),
            ])
            ->pages([
                // Main Bendahara-Style Dashboard
                \App\Filament\Petugas\Pages\BendaharaStyleDashboard::class,
            ])
            ->widgets([
                // Widgets are now managed by Dashboard page directly
                // Commented out to prevent duplicate widgets and charts
                // \App\Filament\Petugas\Widgets\PetugasInteractiveDashboardWidget::class,
                // \App\Filament\Petugas\Widgets\PatientStatsWidget::class,
                // \App\Filament\Petugas\Widgets\DailyActivitiesWidget::class,
                // \App\Filament\Petugas\Widgets\FinancialSummaryWidget::class,
                // \App\Filament\Petugas\Widgets\PetugasStatsWidget::class,
                // \App\Filament\Petugas\Widgets\PetugasHeroStatsWidget::class,
            ])
            ->plugins([
                // Plugin commented out due to incorrect instantiation
                // \BezhanSalleh\FilamentShield\FilamentShieldPlugin::class,
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
            ]);
    }
}