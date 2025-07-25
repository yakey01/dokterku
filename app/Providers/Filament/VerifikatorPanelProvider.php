<?php

namespace App\Providers\Filament;

use App\Filament\Verifikator\Widgets\PasienVerificationStatsWidget;
use App\Http\Middleware\VerifikatorMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class VerifikatorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('verifikator')
            ->path('verifikator')
            ->login()
            ->brandName('Verifikator Pasien')
            ->favicon(public_path('favicon.ico'))
            ->colors([
                'primary' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Verifikator/Resources'), for: 'App\\Filament\\Verifikator\\Resources')
            ->discoverPages(in: app_path('Filament/Verifikator/Pages'), for: 'App\\Filament\\Verifikator\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Verifikator/Widgets'), for: 'App\\Filament\\Verifikator\\Widgets')
            ->widgets([
                PasienVerificationStatsWidget::class,
                Widgets\AccountWidget::class,
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
                VerifikatorMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                '📋 Verifikasi Pasien',
                '📊 Laporan',
                '⚙️ Pengaturan',
            ]);
    }
}
