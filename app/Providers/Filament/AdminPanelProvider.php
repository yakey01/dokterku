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
use Filament\Support\Enums\ThemeMode;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Facades\FilamentView;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use App\Filament\Pages\Auth\CustomLogin;
use App\Filament\Pages\Auth\SimpleAdminProfile;
use Hasnayeen\Themes\ThemesPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()  
            ->profile(false) // Disable top-right profile menu
            ->authGuard('web')
            ->brandName('🏥 Dokterku Admin Portal')
            ->viteTheme('resources/css/filament/admin/theme-pure.css')
            ->renderHook(
                'panels::head.start',
                fn (): string => '
                    <style>
                        /* MODERN SAAS 2025 SPACING OPTIMIZATION FOR ADMIN */
                        html body div[data-filament-panel-id="admin"] .fi-sidebar-nav {
                            padding-top: 0.75rem !important; /* Modern SaaS spacing - closer to top */
                            padding-bottom: 1.5rem !important;
                            gap: 0.75rem !important;
                        }
                        
                        html body div[data-filament-panel-id="admin"] .fi-sidebar-nav-groups {
                            gap: 0.75rem !important; /* Tighter group spacing */
                            margin-top: 0.25rem !important; /* Bring content closer to header */
                            margin-bottom: 0 !important;
                        }
                        
                        html body div[data-filament-panel-id="admin"] .fi-sidebar-header {
                            height: 4rem !important;
                            padding: 0.75rem 1.5rem !important;
                            margin-bottom: 0.25rem !important;
                        }
                        
                        /* Navigation group labels */
                        html body div[data-filament-panel-id="admin"] .fi-sidebar-nav-group-label {
                            padding: 0.5rem 1rem !important;
                            margin-bottom: 0.375rem !important;
                        }
                        
                        /* Navigation items */
                        html body div[data-filament-panel-id="admin"] .fi-sidebar-nav-item {
                            margin-bottom: 0.125rem !important;
                        }
                        
                        html body div[data-filament-panel-id="admin"] .fi-sidebar-nav-item > a,
                        html body div[data-filament-panel-id="admin"] .fi-sidebar-nav-item > button {
                            padding: 0.625rem 1rem !important;
                            gap: 0.75rem !important;
                        }
                    </style>
                '
            )
            ->colors([
                'primary' => Color::Blue,
                'secondary' => Color::Purple,
                'success' => Color::Green,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'info' => Color::Cyan,
            ])
            ->darkMode()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->resources([
                // 👤 Account Group (Sidebar Profile)
                \App\Filament\Resources\AdminProfileResource::class,
                \App\Filament\Resources\AdminSecurityResource::class,
                
                // 👥 User Management Group
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\RoleResource::class,
                \App\Filament\Resources\PegawaiResource::class,
                
                // 📋 Medical Records Group  
                \App\Filament\Resources\DokterResource::class,
                \App\Filament\Resources\PasienResource::class,
                \App\Filament\Resources\TindakanResource::class,
                \App\Filament\Resources\JenisTindakanResource::class,
                
                // 💰 Financial Management Group
                \App\Filament\Resources\PendapatanResource::class,
                \App\Filament\Resources\PengeluaranResource::class,
                \App\Filament\Resources\DokterUmumJaspelResource::class,
                
                // 📊 Reports & Analytics Group
                \App\Filament\Resources\ReportResource::class,
                \App\Filament\Resources\AuditLogResource::class,
                \App\Filament\Resources\BulkOperationResource::class,
                
                // System Administration Group (cleaned)
                \App\Filament\Resources\SystemSettingResource::class,
                \App\Filament\Resources\FeatureFlagResource::class,
                \App\Filament\Resources\TelegramSettingResource::class,
                \App\Filament\Resources\FaceRecognitionResource::class,
                \App\Filament\Resources\GpsSpoofingDetectionResource::class,
                \App\Filament\Resources\GpsSpoofingConfigResource::class,
                // \App\Filament\Resources\UserDeviceResource::class, // REMOVED: Admin panel access for user devices
                \App\Filament\Resources\EmployeeCardResource::class,
                // \App\Filament\Resources\LocationResource::class, // REMOVED: Admin panel access for locations
                \App\Filament\Resources\WorkLocationResource::class,
                \App\Filament\Resources\WorkLocationAssignmentResource::class,
                \App\Filament\Resources\KalenderKerjaResource::class,
                \App\Filament\Resources\JadwalJagaResource::class,
                \App\Filament\Resources\ShiftTemplateResource::class,
                \App\Filament\Resources\AttendanceRecapResource::class,
                \App\Filament\Resources\AttendanceToleranceSettingResource::class, // Added tolerance settings
                \App\Filament\Resources\PermohonanCutiResource::class,
                \App\Filament\Resources\CutiPegawaiResource::class,
                \App\Filament\Resources\LeaveTypeResource::class,
                \App\Filament\Resources\AbsenceRequestResource::class,
            ])
            ->pages([
                \App\Filament\Pages\EnhancedAdminDashboard::class,
                \App\Filament\Pages\SystemMonitoring::class,
            ])
            ->widgets([
                \App\Filament\Widgets\AdminProfileWidget::class, // Profile widget for sidebar
                \App\Filament\Widgets\AdminInteractiveDashboardWidget::class,
                \App\Filament\Widgets\AdminOverviewWidget::class,
                \App\Filament\Widgets\SystemHealthWidget::class,
                \App\Filament\Widgets\ClinicStatsWidget::class,
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
                \App\Http\Middleware\AdminMiddleware::class,
            ])
            ->databaseNotifications()
            ->plugins([
                FilamentFullCalendarPlugin::make(),
            ])
            ->profile(SimpleAdminProfile::class)
            ->tenant(null) // Disable multi-tenancy for now
            ->navigationGroups([
                NavigationGroup::make('Dashboard')
                    ->collapsed(false),
                NavigationGroup::make('User Management')
                    ->collapsed(false),
                NavigationGroup::make('Financial Management')
                    ->collapsed(false),
                NavigationGroup::make('Leave Management')
                    ->collapsed(true),
                NavigationGroup::make('Schedule Management')
                    ->collapsed(true),
                NavigationGroup::make('Notifications')
                    ->collapsed(true),
                NavigationGroup::make('Attendance')
                    ->collapsed(true),
                NavigationGroup::make('System Administration')
                    ->collapsed(true),
                NavigationGroup::make('👤 Account')
                    ->collapsed(false),
            ]);
    }
}