<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Auth\CustomLogin;

class BendaharaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('bendahara')
            ->path('bendahara')
            ->login(false)
            ->brandName('💰 Bendahara Dashboard')
            ->viteTheme('resources/css/filament/bendahara/theme.css')
            ->colors([
                'primary' => Color::Amber,
                'success' => Color::Green,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'info' => Color::Blue,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->pages([
                // 📊 Dashboard Page
                \App\Filament\Bendahara\Pages\BendaharaDashboard::class,
            ])
            ->resources([
                // 💵 Validasi Transaksi Group
                \App\Filament\Bendahara\Resources\ValidasiPendapatanResource::class,
                \App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource::class,
                \App\Filament\Bendahara\Resources\ValidasiPengeluaranResource::class,
                \App\Filament\Bendahara\Resources\ValidasiPengeluaranHarianResource::class,
                \App\Filament\Bendahara\Resources\ValidasiTindakanResource::class,
                
                // 💰 Manajemen Jaspel Group
                \App\Filament\Bendahara\Resources\ValidasiJaspelResource::class,
                \App\Filament\Bendahara\Resources\BudgetPlanningResource::class,
                
                // 📈 Laporan Keuangan Group
                \App\Filament\Bendahara\Resources\LaporanKeuanganResource::class,
                
                // 📋 Audit & Kontrol Group
                \App\Filament\Bendahara\Resources\AuditTrailResource::class,
                \App\Filament\Bendahara\Resources\FinancialAlertResource::class,
                
                // 🏥 Validasi Data Group
                \App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource::class,
            ])
            ->widgets([
                \Filament\Widgets\AccountWidget::class,
                \App\Filament\Bendahara\Widgets\InteractiveDashboardWidget::class,
                \App\Filament\Bendahara\Widgets\BudgetTrackingWidget::class,
                \App\Filament\Bendahara\Widgets\LanguageSwitcherWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('📊 Dashboard')
                    ->collapsed(false),
                NavigationGroup::make('💵 Validasi Transaksi')
                    ->collapsed(false),
                NavigationGroup::make('💰 Manajemen Jaspel')
                    ->collapsed(false),
                NavigationGroup::make('📈 Laporan Keuangan')
                    ->collapsed(true),
                NavigationGroup::make('📋 Audit & Kontrol')
                    ->collapsed(true),
                NavigationGroup::make('🏥 Validasi Data')
                    ->collapsed(true),
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
            ])
            ->authGuard('web');
    }
}