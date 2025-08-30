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
use Filament\Navigation\MenuItem;
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
            ->brandName('')
            ->brandLogo('')
            ->brandLogoHeight('0')
            // ->viteTheme('resources/css/filament/petugas/theme.css') // DISABLED: Preventing navy blue conflicts - using pure inline styles
            ->favicon('/favicon.ico')
            ->spa()
            ->topNavigation(true)
            ->sidebarCollapsibleOnDesktop(false)
            ->sidebarFullyCollapsibleOnDesktop(false)
            ->sidebarWidth('280px')
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->renderHook(
                'panels::head.start',
                fn (): string => '
                    <!-- ELEGANT BLACK THEME WITH LIGHT/DARK MODE SUPPORT -->
                    <style id="petugas-elegant-black-immediate">
                        /* ULTIMATE BLACK CARDS - COMPREHENSIVE TARGETING */
                        [data-filament-panel-id="petugas"] .fi-wi,
                        [data-filament-panel-id="petugas"] .fi-section,
                        [data-filament-panel-id="petugas"] .fi-sta-overview-stat,
                        [data-filament-panel-id="petugas"] .bg-white,
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
                        
                        /* COMPREHENSIVE WHITE ELEMENT ELIMINATION */
                        [data-filament-panel-id="petugas"] .bg-white,
                        [data-filament-panel-id="petugas"] .bg-gray-50,
                        [data-filament-panel-id="petugas"] .bg-gray-100,
                        [data-filament-panel-id="petugas"] [style*="background: white"],
                        [data-filament-panel-id="petugas"] [style*="background: #ffffff"],
                        [data-filament-panel-id="petugas"] [style*="background-color: white"],
                        [data-filament-panel-id="petugas"] [style*="background-color: #ffffff"] {
                            background: linear-gradient(135deg, rgba(10, 10, 11, 0.8) 0%, rgba(17, 17, 24, 0.9) 100%) !important;
                            backdrop-filter: blur(16px) saturate(150%) !important;
                            border: 1px solid rgba(255, 255, 255, 0.12) !important;
                            color: #ffffff !important;
                        }
                        
                        /* ELIMINATE NAVY BLUE (#475569) COMPLETELY */
                        [data-filament-panel-id="petugas"] {
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
                            color-scheme: dark !important;
                        }
                        
                    </style>
                    
                '
            )
            ->renderHook(
                'panels::head.end',
                fn (): string => '
                    <!-- ELEGANT BLACK GLASSMORPHISM - DETAILED BENDAHARA APPROACH -->
                    <style id="petugas-elegant-complete">
                        /* COMPLETE MAIN BACKGROUND ELEGANT BLACK */
                        [data-filament-panel-id="petugas"],
                        [data-filament-panel-id="petugas"] .fi-main,
                        [data-filament-panel-id="petugas"] .fi-page,
                        [data-filament-panel-id="petugas"] .fi-page-content,
                        [data-filament-panel-id="petugas"] .fi-body,
                        [data-filament-panel-id="petugas"] .fi-layout {
                            background: linear-gradient(135deg, #0a0a0b 0%, #111118 50%, #0a0a0b 100%) !important;
                            background-color: #0a0a0b !important;
                            color: #ffffff !important;
                        }
                        
                        /* COMPREHENSIVE WHITE ELIMINATION - EXACT BENDAHARA APPROACH */
                        [data-filament-panel-id="petugas"] .bg-white,
                        [data-filament-panel-id="petugas"] .bg-gray-50,
                        [data-filament-panel-id="petugas"] .bg-gray-100,
                        [data-filament-panel-id="petugas"] .dark\\:bg-gray-800,
                        [data-filament-panel-id="petugas"] .fi-section,
                        [data-filament-panel-id="petugas"] .fi-form,
                        [data-filament-panel-id="petugas"] .fi-ta-table,
                        [data-filament-panel-id="petugas"] .overflow-x-auto,
                        [data-filament-panel-id="petugas"] .fi-wi,
                        [data-filament-panel-id="petugas"] .fi-panel-card,
                        [data-filament-panel-id="petugas"] .fi-modal,
                        [data-filament-panel-id="petugas"] .fi-dropdown-panel,
                        [data-filament-panel-id="petugas"] [style*="background: white"],
                        [data-filament-panel-id="petugas"] [style*="background-color: white"],
                        [data-filament-panel-id="petugas"] [style*="background: #ffffff"],
                        [data-filament-panel-id="petugas"] [style*="background-color: #ffffff"] {
                            background: linear-gradient(135deg, rgba(10, 10, 11, 0.8) 0%, rgba(17, 17, 24, 0.9) 100%) !important;
                            backdrop-filter: blur(16px) saturate(150%) !important;
                            -webkit-backdrop-filter: blur(16px) saturate(150%) !important;
                            border: 1px solid rgba(255, 255, 255, 0.12) !important;
                            border-radius: 1rem !important;
                            box-shadow: 0 8px 32px -8px rgba(0, 0, 0, 0.6), inset 0 1px 0 0 rgba(255, 255, 255, 0.08) !important;
                            transition: all 0.3s ease !important;
                            color: #ffffff !important;
                        }
                        
                        /* ALL WHITE TEXT TO WHITE */
                        [data-filament-panel-id="petugas"] .bg-white *,
                        [data-filament-panel-id="petugas"] .fi-section *,
                        [data-filament-panel-id="petugas"] .fi-wi * {
                            color: #ffffff !important;
                            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
                        }
                        
                        /* ELEGANT HOVER EFFECTS */
                        [data-filament-panel-id="petugas"] .fi-ta-table:hover,
                        [data-filament-panel-id="petugas"] .fi-section:hover,
                        [data-filament-panel-id="petugas"] .fi-wi:hover {
                            backdrop-filter: blur(20px) saturate(180%) !important;
                            border-color: rgba(255, 255, 255, 0.16) !important;
                            transform: translateY(-2px) !important;
                            box-shadow: 0 12px 48px -12px rgba(0, 0, 0, 0.7), inset 0 1px 0 0 rgba(255, 255, 255, 0.1) !important;
                        }
                        
                        /* SIDEBAR ELEGANT BLACK */
                        [data-filament-panel-id="petugas"] .fi-sidebar {
                            background: linear-gradient(180deg, #0a0a0b 0%, #111118 50%, #0a0a0b 100%) !important;
                            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
                            color: #fafafa !important;
                        }
                        
                        [data-filament-panel-id="petugas"] .fi-sidebar-nav-item {
                            background: transparent !important;
                            color: #fafafa !important;
                            transition: all 0.3s ease !important;
                        }
                        
                        [data-filament-panel-id="petugas"] .fi-sidebar-nav-item:hover {
                            background: linear-gradient(135deg, rgba(17, 17, 24, 0.6) 0%, rgba(26, 26, 32, 0.4) 100%) !important;
                            backdrop-filter: blur(8px) !important;
                        }
                        
                        [data-filament-panel-id="petugas"] .fi-sidebar-nav-item.fi-active {
                            background: linear-gradient(135deg, rgba(26, 26, 32, 0.8) 0%, rgba(42, 42, 50, 0.6) 100%) !important;
                            backdrop-filter: blur(12px) !important;
                            border-left: 3px solid rgba(255, 255, 255, 0.3) !important;
                        }
                        
                        /* ULTIMATE WHITE KILLER - FORCE ALL ELEMENTS BLACK */
                        [data-filament-panel-id="petugas"] * {
                            background-color: transparent !important;
                        }
                        
                        [data-filament-panel-id="petugas"] *[class*="bg-white"],
                        [data-filament-panel-id="petugas"] *[class*="bg-gray"],
                        [data-filament-panel-id="petugas"] div,
                        [data-filament-panel-id="petugas"] section,
                        [data-filament-panel-id="petugas"] article {
                            background: rgba(10, 10, 11, 0.8) !important;
                            backdrop-filter: blur(16px) saturate(150%) !important;
                            border: 1px solid rgba(255, 255, 255, 0.12) !important;
                            border-radius: 1rem !important;
                            color: #ffffff !important;
                        }
                        
                        /* FORCE DARK BACKGROUND ON EVERYTHING */
                        [data-filament-panel-id="petugas"] .fi-page,
                        [data-filament-panel-id="petugas"] .fi-main,
                        [data-filament-panel-id="petugas"] .fi-body {
                            background: #0a0a0b !important;
                        }
                        
                        /* TOPBAR FORCE BLACK - CRITICAL FIX */
                        [data-filament-panel-id="petugas"] .fi-topbar,
                        [data-filament-panel-id="petugas"] .fi-header,
                        [data-filament-panel-id="petugas"] nav,
                        [data-filament-panel-id="petugas"] header {
                            background: linear-gradient(135deg, #0a0a0b 0%, #111118 100%) !important;
                            backdrop-filter: blur(16px) saturate(150%) !important;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
                            color: #ffffff !important;
                        }
                        
                        /* TOPBAR CONTENT BLACK */
                        [data-filament-panel-id="petugas"] .fi-topbar *,
                        [data-filament-panel-id="petugas"] .fi-header *,
                        [data-filament-panel-id="petugas"] nav *,
                        [data-filament-panel-id="petugas"] header * {
                            color: #ffffff !important;
                            background: transparent !important;
                        }
                        
                        /* NAVIGATION ITEMS IN TOPBAR */
                        [data-filament-panel-id="petugas"] .fi-topbar .fi-tabs,
                        [data-filament-panel-id="petugas"] .fi-topbar .fi-tabs-tab,
                        [data-filament-panel-id="petugas"] .fi-topbar button,
                        [data-filament-panel-id="petugas"] .fi-topbar a {
                            background: rgba(255, 255, 255, 0.05) !important;
                            color: #ffffff !important;
                            border: 1px solid rgba(255, 255, 255, 0.1) !important;
                            border-radius: 0.5rem !important;
                        }
                        
                        /* DEBUG: VERIFY CSS IS LOADING */
                        body::before {
                            content: "🔍 PETUGAS CSS LOADED ✅";
                            position: fixed;
                            top: 10px;
                            left: 10px;
                            background: #22c55e;
                            color: white;
                            padding: 5px 10px;
                            border-radius: 5px;
                            font-size: 12px;
                            z-index: 9999;
                            animation: fadeOut 3s forwards;
                        }
                        
                        @keyframes fadeOut {
                            0% { opacity: 1; }
                            70% { opacity: 1; }
                            100% { opacity: 0; }
                        }
                    </style>
                '
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => '<div class="petugas-dashboard-wrapper">'
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => '</div>'
            )
            ->colors([
                'primary' => Color::Stone, // ULTIMATE NUCLEAR: Stone has ZERO blue undertones, pure gray-brown
                'success' => Color::Green,
                'warning' => Color::Yellow,
                'danger' => Color::Red,
                'info' => Color::Cyan, // Changed from Blue to eliminate ALL blue references
            ])
            ->darkMode()
            ->maxContentWidth('full')
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
                NavigationGroup::make('Manajemen Pasien')
                    ->collapsed(false)
                    ->collapsible(false),
                NavigationGroup::make('Tindakan Medis')
                    ->collapsed(false)  
                    ->collapsible(false),
                NavigationGroup::make('Keuangan')
                    ->collapsed(false)
                    ->collapsible(false),
                NavigationGroup::make('Laporan & Analytics')
                    ->icon('heroicon-o-chart-bar-square')
                    ->collapsed(false)
                    ->collapsible(false),
                NavigationGroup::make('Quick Actions')
                    ->icon('heroicon-o-bolt')
                    ->collapsed(false)
                    ->collapsible(false),
                NavigationGroup::make('System')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(false)
                    ->collapsible(false),
            ])
            ->homeUrl('/petugas')
            ->pages([
                \App\Filament\Petugas\Pages\PetugasDashboard::class,
            ])
            ->widgets([
                // Global widgets (appear on all pages)
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