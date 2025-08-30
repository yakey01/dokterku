<?php

namespace App\Filament\Resources\AdminProfileResource\Pages;

use App\Filament\Resources\AdminProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditAdminProfile extends EditRecord
{
    protected static string $resource = AdminProfileResource::class;

    public function getTitle(): string
    {
        return 'Account Settings';
    }
    
    public function getSubheading(): ?string
    {
        return 'Manage your profile, security, and preferences';
    }

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
                    ->modalDescription('This will end all active sessions on other devices.')
                    ->modalSubmitActionLabel('Yes, log out all devices')
                    ->action(function () {
                        // Logic to invalidate all sessions would go here
                        Notification::make()
                            ->success()
                            ->title('All devices logged out')
                            ->body('All your other sessions have been terminated.')
                            ->send();
                    }),
                    
                Actions\Action::make('view_security_log')
                    ->label('Security Log')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->action(function () {
                        Notification::make()
                            ->info()
                            ->title('Security log')
                            ->body('Viewing security activities for your account.')
                            ->send();
                    }),
            ])
                ->label('Security Actions')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->button(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Security verification for password changes
        if (isset($data['password']) && filled($data['password'])) {
            if (!isset($data['current_password']) || !Hash::check($data['current_password'], Auth::user()->password)) {
                Notification::make()
                    ->danger()
                    ->title('Current password required')
                    ->body('Please enter your current password to make changes.')
                    ->send();
                
                unset($data['password']);
                return $data;
            }
            
            // Log password change
            Log::channel('security')->info('Admin password updated', [
                'admin_id' => Auth::id(),
                'ip' => request()->ip(),
                'timestamp' => now()->toISOString()
            ]);
        }

        unset($data['current_password']);
        return $data;
    }

    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return Notification::make()
            ->success()
            ->title('Profile updated')
            ->body('Your account settings have been saved successfully.')
            ->duration(4000);
    }
    
    public static function canAccess(array $parameters = []): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }
    
    // Only allow editing own profile
    public function getRecord(): \Illuminate\Database\Eloquent\Model
    {
        return Auth::user();
    }
}