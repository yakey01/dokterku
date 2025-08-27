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
            ->brandName('Bendahara Dashboard')
            ->colors([
                'primary' => Color::Gray, // FIXED: Changed from Slate to Gray to eliminate navy blue flash
                'success' => Color::Green,
                'warning' => Color::Yellow,
                'danger' => Color::Red,
                'info' => Color::Blue,
            ])
            ->darkMode()
            ->sidebarCollapsibleOnDesktop(false)
            ->renderHook(
                'panels::head.start',
                fn (): string => '
                    <style>
                        /* MODERN SAAS 2025 SPACING OPTIMIZATION FOR BENDAHARA */
                        html body div[data-filament-panel-id="bendahara"] .fi-sidebar-nav {
                            padding-top: 0.75rem !important; /* Modern SaaS spacing - closer to top */
                            padding-bottom: 1.5rem !important;
                            gap: 0.75rem !important;
                        }
                        
                        html body div[data-filament-panel-id="bendahara"] .fi-sidebar-nav-groups {
                            gap: 0.75rem !important; /* Tighter group spacing */
                            margin-top: 0.25rem !important; /* Bring content closer to header */
                            margin-bottom: 0 !important;
                        }
                        
                        html body div[data-filament-panel-id="bendahara"] .fi-sidebar-header {
                            height: 4rem !important;
                            padding: 0.75rem 1.5rem !important;
                            margin-bottom: 0.25rem !important;
                        }
                        
                        /* Navigation group labels */
                        html body div[data-filament-panel-id="bendahara"] .fi-sidebar-group-label {
                            padding: 0.5rem 1rem !important;
                            margin-bottom: 0.375rem !important;
                        }
                        
                        /* Navigation items */
                        html body div[data-filament-panel-id="bendahara"] .fi-sidebar-nav-item {
                            margin-bottom: 0.125rem !important;
                        }
                        
                        html body div[data-filament-panel-id="bendahara"] .fi-sidebar-nav-item > a,
                        html body div[data-filament-panel-id="bendahara"] .fi-sidebar-nav-item > button {
                            padding: 0.625rem 1rem !important;
                            gap: 0.75rem !important;
                        }
                    </style>
                '
            )
            // DISABLED: viteTheme to prevent CSS conflicts
            // Using pure inline styles instead
            // ->viteTheme([
            //     'resources/css/filament/bendahara/isolated-theme.css',
            // ])
            ->pages([
                \App\Filament\Bendahara\Pages\BendaharaDashboard::class,
            ])
            ->resources([
                // Validation Centers - Clean navigation structure
                \App\Filament\Bendahara\Resources\ValidationCenterResource::class,
                \App\Filament\Bendahara\Resources\DailyFinancialValidationResource::class,
                
                // DISABLED: Manajemen Jaspel Group - Per request
                // \App\Filament\Bendahara\Resources\ValidasiJaspelResource::class,
                // \App\Filament\Bendahara\Resources\BudgetPlanningResource::class, // SKIP: Missing budget_plans table
                
                // NEW: Laporan Keuangan Report Resource
                \App\Filament\Bendahara\Resources\LaporanKeuanganReportResource::class,
                
                // RESTORED: Audit & Kontrol Group (PURE FILAMENT)
                \App\Filament\Bendahara\Resources\AuditTrailResource::class,
                // DISABLED: FinancialAlertResource - Missing model causes errors
                // \App\Filament\Bendahara\Resources\FinancialAlertResource::class,
                
                // RESTORED: Validasi Data Group (PURE FILAMENT)
                \App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource::class,
            ])
            ->widgets([
                // ALL WIDGETS TEMPORARILY DISABLED - Debugging Livewire multiple root elements error
                // \App\Filament\Bendahara\Widgets\ModernFinancialMetricsWidget::class,
                // \App\Filament\Bendahara\Widgets\InteractiveDashboardWidget::class,
                // \App\Filament\Bendahara\Widgets\BudgetTrackingWidget::class,
                // \App\Filament\Bendahara\Widgets\LanguageSwitcherWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Dashboard')
                    ->collapsed(false),
                NavigationGroup::make('Validasi Transaksi')
                    ->collapsed(false),
                NavigationGroup::make('Laporan Keuangan')
                    ->collapsed(false),
                NavigationGroup::make('Audit & Kontrol')
                    ->collapsed(false),
                NavigationGroup::make('Validasi Data')
                    ->collapsed(false),
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
                \App\Http\Middleware\BendaharaMiddleware::class,
            ])
            ->authGuard('web')
            ->renderHook(
                'panels::head.end',
                fn (): string => view('filament.bendahara.components.professional-topbar')->render()
            );
    }
}