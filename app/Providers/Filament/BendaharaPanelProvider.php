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
            ->login(CustomLogin::class)
            ->brandName('')
            ->brandLogo('')
            ->brandLogoHeight('0')
            // ->viteTheme('resources/css/filament/bendahara/theme.css') // DISABLED: Following petugas approach - preventing CSS conflicts
            ->favicon('/favicon.ico')
            ->spa()
            ->topNavigation()
            ->sidebarCollapsibleOnDesktop(false)
            ->sidebarFullyCollapsibleOnDesktop(true)
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->homeUrl('/bendahara/bendahara-dashboard')
            ->colors([
                'primary' => Color::Stone, // Following petugas approach - Stone has zero blue undertones
                'success' => Color::Green,
                'warning' => Color::Yellow,
                'danger' => Color::Red,
                'info' => Color::Cyan, // Changed from Blue to eliminate blue references
            ])
            ->darkMode()
            ->maxContentWidth('full')
            ->userMenuItems([])
            ->renderHook(
                'panels::head.start',
                fn (): string => '
                    <!-- ELEGANT BLACK THEME - PETUGAS APPROACH APPLIED TO BENDAHARA -->
                    <style id="bendahara-elegant-black-immediate">
                        /* ULTIMATE BLACK CARDS - COMPREHENSIVE TARGETING */
                        [data-filament-panel-id="bendahara"] .fi-wi,
                        [data-filament-panel-id="bendahara"] .fi-section,
                        [data-filament-panel-id="bendahara"] .fi-sta-overview-stat,
                        [data-filament-panel-id="bendahara"] .bg-white,
                        /* Global fallbacks */
                        .fi-wi:not(.fi-sidebar *):not(.fi-topbar *),
                        .fi-section:not(.fi-sidebar *):not(.fi-topbar *),
                        .bg-white:not(.fi-sidebar *):not(.fi-topbar *):not(.fi-ta-text) {
                            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
                            border: 1px solid #333340 !important;
                            border-radius: 1rem !important;
                            box-shadow: 
                                0 4px 12px -2px rgba(0, 0, 0, 0.8),
                                0 2px 6px -2px rgba(0, 0, 0, 0.6),
                                inset 0 1px 0 0 rgba(255, 255, 255, 0.08) !important;
                            color: #fafafa !important;
                            transition: all 0.3s ease !important;
                        }

                        /* ELEGANT HOVER EFFECTS */
                        .fi-wi:hover:not(.fi-sidebar *):not(.fi-topbar *) {
                            background: linear-gradient(135deg, #111118 0%, #1a1a20 100%) !important;
                            transform: translateY(-2px) !important;
                            box-shadow: 
                                0 8px 24px -4px rgba(0, 0, 0, 0.9),
                                0 4px 12px -2px rgba(0, 0, 0, 0.7),
                                inset 0 1px 0 0 rgba(255, 255, 255, 0.12) !important;
                        }

                        /* ALL TEXT WHITE IN CARDS */
                        .fi-wi *:not(.fi-sidebar *):not(.fi-topbar *),
                        .fi-section *:not(.fi-sidebar *):not(.fi-topbar *),
                        .bg-white *:not(.fi-sidebar *):not(.fi-topbar *):not(.fi-btn *) {
                            color: #fafafa !important;
                            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
                        }
                        
                        /* ELIMINATE NAVY BLUE COMPLETELY */
                        [data-filament-panel-id="bendahara"] {
                            --primary: 10 10 11 !important; /* Deep Black RGB */
                            --primary-50: #0a0a0b !important;
                            --primary-100: #111118 !important;
                            --primary-200: #1a1a20 !important;
                            --primary-300: #2a2a32 !important;
                            --primary-400: #333340 !important;
                            --primary-500: #404050 !important;
                            --primary-600: #4a4a5a !important;
                            --primary-700: #555564 !important;
                            --primary-800: #60606e !important;
                            --primary-900: #6b6b78 !important;
                            --primary-950: #767682 !important;
                        }
                        
                        /* FORCE DARK MODE ONLY - HIDE THEME SWITCHER */
                        [data-filament-panel-id="bendahara"] .fi-theme-switcher,
                        [data-filament-panel-id="bendahara"] .fi-user-menu .fi-dropdown-list-item:has([data-theme]),
                        [data-filament-panel-id="bendahara"] [data-theme-switcher],
                        [data-filament-panel-id="bendahara"] button[aria-label*="theme"],
                        [data-filament-panel-id="bendahara"] button[aria-label*="Theme"],
                        [data-filament-panel-id="bendahara"] .theme-toggle,
                        [data-filament-panel-id="bendahara"] .dark-mode-toggle {
                            display: none !important;
                            visibility: hidden !important;
                            opacity: 0 !important;
                        }
                        
                        /* FORCE DARK MODE CSS VARIABLES */
                        [data-filament-panel-id="bendahara"] {
                            color-scheme: dark !important;
                        }
                        
                        /* SIMPLE CLEAN SOLUTION: HIDE DEFAULT USER MENU */
                        [data-filament-panel-id="bendahara"] .fi-topbar .fi-user-menu,
                        [data-filament-panel-id="bendahara"] .fi-user-menu,
                        [data-filament-panel-id="bendahara"] .fi-topbar-user-menu {
                            display: none !important;
                            visibility: hidden !important;
                        }
                        
                        /* ULTIMATE SIDEBAR ELIMINATION - COMPREHENSIVE TARGETING */
                        [data-filament-panel-id="bendahara"] .fi-sidebar,
                        [data-filament-panel-id="bendahara"] .fi-sidebar-nav,
                        [data-filament-panel-id="bendahara"] .fi-sidebar-header,
                        [data-filament-panel-id="bendahara"] .fi-sidebar-content,
                        [data-filament-panel-id="bendahara"] .fi-sidebar-nav-group,
                        [data-filament-panel-id="bendahara"] .fi-sidebar-nav-items,
                        [data-filament-panel-id="bendahara"] .fi-topbar-nav,
                        [data-filament-panel-id="bendahara"] nav[role="navigation"] {
                            display: none !important;
                            visibility: hidden !important;
                            width: 0 !important;
                            height: 0 !important;
                            opacity: 0 !important;
                            pointer-events: none !important;
                            position: absolute !important;
                            left: -9999px !important;
                        }
                        
                        /* FORCE MAIN CONTENT TO FULL WIDTH - COMPREHENSIVE */
                        [data-filament-panel-id="bendahara"] .fi-main,
                        [data-filament-panel-id="bendahara"] .fi-main-content,
                        [data-filament-panel-id="bendahara"] .fi-page,
                        [data-filament-panel-id="bendahara"] .fi-page-content {
                            margin-left: 0 !important;
                            margin-right: 0 !important;
                            width: 100% !important;
                            max-width: 100% !important;
                            padding-left: 1rem !important;
                            padding-right: 1rem !important;
                        }
                        
                        /* ELIMINATE ANY LAYOUT GRID GAPS FOR SIDEBAR */
                        [data-filament-panel-id="bendahara"] .fi-layout {
                            grid-template-columns: 1fr !important;
                            grid-template-areas: "header" "main" !important;
                        }
                        
                        /* FINAL FALLBACK - REMOVE ANY SIDEBAR ELEMENTS */
                        [data-filament-panel-id="bendahara"] aside,
                        [data-filament-panel-id="bendahara"] [role="navigation"]:not([data-topbar]),
                        [data-filament-panel-id="bendahara"] .sidebar,
                        [data-filament-panel-id="bendahara"] .navigation-sidebar {
                            display: none !important;
                        }
                        
                        /* FORCE SINGLE COLUMN LAYOUT */
                        [data-filament-panel-id="bendahara"] .fi-layout-container {
                            display: flex !important;
                            flex-direction: column !important;
                        }
                    </style>
                    
                '
            )
            ->renderHook(
                'panels::head.end',
                fn (): string => '
                    <!-- ELEGANT BLACK GLASSMORPHISM - PETUGAS PATTERN -->
                    <style id="bendahara-elegant-complete">
                        /* ELEGANT GLASS TABLES WITH GLASSMORPHISM */
                        [data-filament-panel-id="bendahara"] .fi-ta-table,
                        [data-filament-panel-id="bendahara"] .fi-section,
                        [data-filament-panel-id="bendahara"] .overflow-x-auto {
                            background: rgba(10, 10, 11, 0.8) !important;
                            backdrop-filter: blur(16px) saturate(150%) !important;
                            -webkit-backdrop-filter: blur(16px) saturate(150%) !important;
                            border: 1px solid rgba(255, 255, 255, 0.12) !important;
                            border-radius: 1rem !important;
                            box-shadow: 0 8px 32px -8px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08) !important;
                            transition: all 0.3s ease !important;
                            color: #ffffff !important;
                        }
                        
                        /* SIDEBAR ELEGANT BLACK */
                        [data-filament-panel-id="bendahara"] .fi-sidebar {
                            background: linear-gradient(180deg, #0a0a0b 0%, #111118 50%, #0a0a0b 100%) !important;
                            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
                            color: #fafafa !important;
                        }
                        
                        [data-filament-panel-id="bendahara"] .fi-sidebar-nav-item {
                            background: transparent !important;
                            color: #fafafa !important;
                            transition: all 0.3s ease !important;
                        }
                        
                        [data-filament-panel-id="bendahara"] .fi-sidebar-nav-item:hover {
                            background: linear-gradient(135deg, rgba(17, 17, 24, 0.6) 0%, rgba(26, 26, 32, 0.4) 100%) !important;
                            backdrop-filter: blur(8px) !important;
                        }
                        
                        [data-filament-panel-id="bendahara"] .fi-sidebar-nav-item.fi-active {
                            background: linear-gradient(135deg, rgba(26, 26, 32, 0.8) 0%, rgba(42, 42, 50, 0.6) 100%) !important;
                            backdrop-filter: blur(12px) !important;
                            border-left: 3px solid rgba(255, 255, 255, 0.3) !important;
                        }
                        
                        /* FORM ELEMENTS ELEGANT BLACK */
                        [data-filament-panel-id="bendahara"] .bg-white,
                        [data-filament-panel-id="bendahara"] .fi-form {
                            background: rgba(10, 10, 11, 0.6) !important;
                            backdrop-filter: blur(12px) saturate(120%) !important;
                            border: 1px solid rgba(255, 255, 255, 0.08) !important;
                            border-radius: 1rem !important;
                            box-shadow: 0 4px 16px -4px rgba(0, 0, 0, 0.4), inset 0 1px 0 0 rgba(255, 255, 255, 0.06) !important;
                            color: #ffffff !important;
                        }
                    </style>
                '
            )
            ->renderHook(
                'panels::topbar.end',
                fn (): string => ''
            )
            ->renderHook(
                'panels::page.header.actions.before',
                fn (): string => ''
            )
            ->renderHook(
                'panels::page.header.actions.after', 
                fn (): string => ''
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => '<div class="bendahara-dashboard-wrapper">'
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => '</div>'
            )
            // DISABLED: viteTheme to prevent CSS conflicts following petugas approach
            // Using pure inline styles instead
            // ->viteTheme([
            //     'resources/css/filament/bendahara/isolated-theme.css',
            // ])
            ->pages([
                \App\Filament\Bendahara\Pages\BendaharaDashboard::class,
            ])
            // RESOURCES WITH NAVIGATION DISABLED
            // Resources are registered but shouldRegisterNavigation() = false
            // This eliminates sidebar while keeping resource functionality
            ->resources([
                \App\Filament\Bendahara\Resources\ValidationCenterResource::class,
                \App\Filament\Bendahara\Resources\DailyFinancialValidationResource::class,
                // \App\Filament\Bendahara\Resources\ValidasiJaspelResource::class, // REMOVED: Replaced by ValidationCenterResource
                \App\Filament\Bendahara\Resources\LaporanKeuanganReportResource::class,
                \App\Filament\Bendahara\Resources\AuditTrailResource::class,
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
                // REMOVED DASHBOARD GROUP - Dashboard is now standalone navigation item
                NavigationGroup::make('Validasi Transaksi')
                    ->collapsed(false),
                NavigationGroup::make('Laporan')
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
                // \App\Http\Middleware\SessionCleanupMiddleware::class, // TEMPORARILY DISABLED
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                // \App\Http\Middleware\RefreshCsrfToken::class, // TEMPORARILY DISABLED  
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->tenantMiddleware([
                // Use Filament's native authorization instead of custom middleware
            ], isPersistent: true);
    }
}