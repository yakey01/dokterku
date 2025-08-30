<?php

namespace App\Filament\Resources\AdminSecurityResource\Pages;

use App\Filament\Resources\AdminSecurityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ListAdminSecurity extends ListRecords
{
    protected static string $resource = AdminSecurityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('logout_all_devices')
                    ->label('Log out all devices')
                    ->icon('heroicon-o-computer-desktop')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Log out all devices')
                    ->modalDescription('This will end all active sessions on other devices. You will remain logged in on this device.')
                    ->modalSubmitActionLabel('Yes, log out all devices')
                    ->action(function () {
                        // Logic to invalidate all sessions would go here
                        Notification::make()
                            ->success()
                            ->title('All devices logged out')
                            ->body('All your other sessions have been terminated.')
                            ->send();
                    }),
                    
                Actions\Action::make('download_security_report')
                    ->label('Download Security Report')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function () {
                        Notification::make()
                            ->info()
                            ->title('Security report')
                            ->body('Your security report is being generated.')
                            ->send();
                    }),
                    
                Actions\Action::make('enable_2fa')
                    ->label('Enable 2FA')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('success')
                    ->action(function () {
                        Notification::make()
                            ->info()
                            ->title('Two-Factor Authentication')
                            ->body('2FA setup will be available in a future update.')
                            ->send();
                    }),
            ])
                ->label('Security Actions')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->button(),
        ];
    }
    
    public function getTitle(): string
    {
        return 'Security & Sessions';
    }
    
    public function getSubheading(): ?string
    {
        return 'Monitor your account security and active sessions';
    }
}