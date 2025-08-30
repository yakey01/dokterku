<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\EditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

class AdminEditProfile extends EditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('AdminProfileTabs')
                    ->tabs([
                        Tabs\Tab::make('👑 Profile Information')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Section::make('🏥 Administrator Details')
                                    ->description('Manage administrator profile with enhanced security')
                                    ->icon('heroicon-o-user-circle')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                FileUpload::make('avatar_url')
                                                    ->label('👤 Admin Avatar')
                                                    ->image()
                                                    ->avatar()
                                                    ->directory('admin-avatars')
                                                    ->maxSize(2048)
                                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                    ->helperText('Upload admin profile photo (max 2MB)')
                                                    ->columnSpan(1),
                                                    
                                                Grid::make(1)
                                                    ->schema([
                                                        $this->getNameFormComponent()
                                                            ->helperText('Full administrator name displayed in system'),
                                                            
                                                        TextInput::make('username')
                                                            ->label('🎯 Username')
                                                            ->maxLength(50)
                                                            ->placeholder('Unique admin username')
                                                            ->suffixIcon('heroicon-o-finger-print')
                                                            ->helperText('Alternative login username')
                                                            ->unique(ignoreRecord: true),
                                                    ])
                                                    ->columnSpan(2),
                                            ]),
                                            
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('no_telepon')
                                                    ->label('📱 Phone Number')
                                                    ->tel()
                                                    ->placeholder('+62 812-3456-7890')
                                                    ->suffixIcon('heroicon-o-phone')
                                                    ->helperText('Emergency contact number'),
                                                    
                                                TextInput::make('nip')
                                                    ->label('🏥 Employee ID')
                                                    ->placeholder('Employee identification number')
                                                    ->suffixIcon('heroicon-o-identification')
                                                    ->helperText('Hospital employee ID number'),
                                            ]),
                                    ]),
                                    
                                Section::make('📧 Email & Password Security')
                                    ->description('Secure email and password management')
                                    ->icon('heroicon-o-shield-check')
                                    ->schema([
                                        $this->getEmailFormComponent()
                                            ->helperText('Primary email for system notifications and login')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state) {
                                                if ($state && $state !== Auth::user()->email) {
                                                    Notification::make()
                                                        ->warning()
                                                        ->title('⚠️ Email Change Detected')
                                                        ->body('Admin email will be changed. Ensure new email is accessible.')
                                                        ->send();
                                                }
                                            }),
                                            
                                        TextInput::make('current_password')
                                            ->label('🔓 Current Password')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Enter current password for verification')
                                            ->suffixIcon('heroicon-o-key')
                                            ->helperText('Required for password changes')
                                            ->dehydrated(false)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $component) {
                                                if ($state && !Hash::check($state, Auth::user()->password)) {
                                                    $component->state('');
                                                    Notification::make()
                                                        ->danger()
                                                        ->title('❌ Incorrect Password')
                                                        ->body('Current password does not match.')
                                                        ->send();
                                                } else if ($state) {
                                                    Notification::make()
                                                        ->success()
                                                        ->title('✅ Password Verified')
                                                        ->body('Current password verified successfully.')
                                                        ->send();
                                                }
                                            }),
                                            
                                        Grid::make(2)
                                            ->schema([
                                                $this->getPasswordFormComponent()
                                                    ->label('🔐 New Password')
                                                    ->rule(Password::default()->min(12)->mixedCase()->numbers()->symbols()->uncompromised())
                                                    ->helperText('Min 12 chars, mixed case, numbers, symbols, not compromised'),
                                                    
                                                $this->getPasswordConfirmationFormComponent()
                                                    ->label('🔒 Confirm New Password')
                                                    ->helperText('Must match the new password'),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),
                            
                        Tabs\Tab::make('🛡️ Security Settings')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('🔐 Security Preferences')
                                    ->description('Configure security and session settings')
                                    ->icon('heroicon-o-cog-8-tooth')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('email_notifications')
                                                    ->label('📧 Email Notifications')
                                                    ->helperText('Receive security alerts via email')
                                                    ->default(true),
                                                    
                                                Toggle::make('push_notifications')
                                                    ->label('🔔 Push Notifications')
                                                    ->helperText('Browser notifications for urgent alerts')
                                                    ->default(true),
                                                    
                                                Select::make('language')
                                                    ->label('🌍 Interface Language')
                                                    ->options([
                                                        'id' => '🇮🇩 Bahasa Indonesia',
                                                        'en' => '🇺🇸 English',
                                                        'es' => '🇪🇸 Español',
                                                        'fr' => '🇫🇷 Français',
                                                    ])
                                                    ->default('id')
                                                    ->selectablePlaceholder(false),
                                                    
                                                Select::make('timezone')
                                                    ->label('⏰ Timezone')
                                                    ->options([
                                                        'Asia/Jakarta' => '🇮🇩 Jakarta (WIB)',
                                                        'Asia/Makassar' => '🇮🇩 Makassar (WITA)',
                                                        'Asia/Jayapura' => '🇮🇩 Jayapura (WIT)',
                                                        'UTC' => '🌍 UTC',
                                                    ])
                                                    ->default('Asia/Jakarta')
                                                    ->selectablePlaceholder(false),
                                            ]),
                                    ])
                                    ->collapsible(),
                                    
                                Section::make('📊 Security Status')
                                    ->description('Current security status and activity')
                                    ->icon('heroicon-o-eye')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('security_score')
                                                    ->label('🛡️ Security Score')
                                                    ->content(fn () => 
                                                        $this->calculateSecurityScore() . '/100 • ' .
                                                        $this->getSecurityRecommendations()
                                                    ),
                                                    
                                                Placeholder::make('last_activity')
                                                    ->label('🕐 Recent Activity')
                                                    ->content(fn () => 
                                                        'Last Login: ' . (Auth::user()->last_login_at ? Auth::user()->last_login_at->format('d M Y H:i') : 'Never') . '<br>' .
                                                        'IP Address: ' . (Auth::user()->last_admin_ip ?? 'Unknown') . '<br>' .
                                                        'Session: Active'
                                                    ),
                                            ])
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
            ])
            ->operation('edit')
            ->model($this->getUser())
            ->statePath('data');
    }

    protected function calculateSecurityScore(): int
    {
        $score = 80; // Base score for admin
        
        // Recent activity
        if (Auth::user()->last_login_at && Auth::user()->last_login_at->gt(now()->subDays(7))) {
            $score += 10;
        }
        
        // Account age
        if (Auth::user()->created_at->lt(now()->subMonths(3))) {
            $score += 10;
        }
        
        return min($score, 100);
    }
    
    protected function getSecurityRecommendations(): string
    {
        $score = $this->calculateSecurityScore();
        
        if ($score >= 95) return '🟢 Excellent';
        if ($score >= 85) return '🟡 Good';
        if ($score >= 75) return '🟠 Fair';
        return '🔴 Needs Improvement';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Verify current password for password changes
        if (isset($data['password']) && filled($data['password'])) {
            if (!isset($data['current_password']) || !Hash::check($data['current_password'], Auth::user()->password)) {
                Notification::make()
                    ->danger()
                    ->title('🚨 Password Verification Failed')
                    ->body('Current password must be verified to change password.')
                    ->persistent()
                    ->send();
                
                unset($data['password']);
                return $data;
            }
            
            // Log password change
            Log::channel('security')->info('Admin password changed', [
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()->email,
                'ip' => request()->ip(),
                'timestamp' => now()->toISOString()
            ]);
        }

        // Log email changes
        if (isset($data['email']) && $data['email'] !== Auth::user()->email) {
            Log::channel('security')->info('Admin email changed', [
                'admin_id' => Auth::id(),
                'old_email' => Auth::user()->email,
                'new_email' => $data['email'],
                'ip' => request()->ip(),
                'timestamp' => now()->toISOString()
            ]);
        }

        // Remove current_password from save data
        unset($data['current_password']);

        return $data;
    }

    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return Notification::make()
            ->success()
            ->title('🎉 Admin Profile Updated')
            ->body('Profile changes saved successfully with security logging.')
            ->duration(5000);
    }
}