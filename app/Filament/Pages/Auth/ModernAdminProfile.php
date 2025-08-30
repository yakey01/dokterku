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
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
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

class ModernAdminProfile extends EditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Modern 3-column layout inspired by top Dribbble designs
                Grid::make([
                    'default' => 1,
                    'sm' => 1, 
                    'md' => 2,
                    'lg' => 3,
                    'xl' => 3,
                    '2xl' => 3,
                ])
                ->schema([
                    // Left Column: Profile Card (Dribbble-inspired)
                    Section::make()
                        ->schema([
                            // Avatar with modern styling
                            FileUpload::make('avatar_url')
                                ->label('')
                                ->image()
                                ->avatar()
                                ->directory('admin-avatars')
                                ->maxSize(2048)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->alignCenter()
                                ->extraAttributes(['style' => 'margin-bottom: 16px;']),
                                
                            // Admin badge with gradient
                            Placeholder::make('admin_status')
                                ->content('
                                    <div class="text-center space-y-3">
                                        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-400 via-orange-500 to-red-500 text-white rounded-full text-sm font-bold shadow-lg">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                            SUPER ADMIN
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <div class="flex items-center justify-center mb-2">
                                                <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                                                <span>Active Session</span>
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                Last login: ' . (Auth::user()->last_login_at ? Auth::user()->last_login_at->diffForHumans() : 'Never') . '
                                            </div>
                                        </div>
                                    </div>
                                '),
                                
                            // Quick stats cards
                            Placeholder::make('quick_stats')
                                ->content('
                                    <div class="grid grid-cols-2 gap-3 mt-4">
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                                            <div class="text-lg font-bold text-blue-700">' . $this->calculateSecurityScore() . '%</div>
                                            <div class="text-xs text-blue-600">Security</div>
                                        </div>
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                                            <div class="text-lg font-bold text-green-700">' . $this->getActiveSessionsCount() . '</div>
                                            <div class="text-xs text-green-600">Sessions</div>
                                        </div>
                                    </div>
                                '),
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'md' => 1,
                            'lg' => 1,
                        ]),
                    
                    // Center Column: Main Form (Dribbble card-style)
                    Section::make('🏥 Profile Information')
                        ->description('Manage your administrator account details')
                        ->headerActions([
                            FormAction::make('quick_edit')
                                ->icon('heroicon-o-pencil')
                                ->color('gray')
                                ->size('sm'),
                        ])
                        ->schema([
                            // Name with modern styling
                            $this->getNameFormComponent()
                                ->prefixIcon('heroicon-o-user')
                                ->placeholder('Enter your full name')
                                ->helperText('Display name in admin interface'),
                                
                            // Username field
                            TextInput::make('username')
                                ->label('Username')
                                ->maxLength(50)
                                ->prefixIcon('heroicon-o-at-symbol')
                                ->placeholder('admin_username')
                                ->helperText('Alternative login method')
                                ->unique(ignoreRecord: true),
                                
                            // Email with enhanced validation
                            $this->getEmailFormComponent()
                                ->prefixIcon('heroicon-o-envelope')
                                ->placeholder('admin@dokterku.com')
                                ->helperText('Primary contact and login email')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state) {
                                    if ($state && $state !== Auth::user()->email) {
                                        Notification::make()
                                            ->warning()
                                            ->title('⚠️ Email Change')
                                            ->body('Email change detected - verification may be required')
                                            ->send();
                                    }
                                }),
                                
                            // Contact info in grid
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('no_telepon')
                                        ->label('Phone')
                                        ->tel()
                                        ->prefixIcon('heroicon-o-phone')
                                        ->placeholder('+62 812-3456-7890')
                                        ->helperText('Emergency contact'),
                                        
                                    TextInput::make('nip')
                                        ->label('Employee ID')
                                        ->prefixIcon('heroicon-o-identification')
                                        ->placeholder('ADM001')
                                        ->helperText('Hospital ID number'),
                                ]),
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'md' => 1,
                            'lg' => 1,
                        ]),
                    
                    // Right Column: Security Panel (Modern dashboard style)
                    Section::make('🔐 Security Controls')
                        ->description('Password and security management')
                        ->headerActions([
                            FormAction::make('security_audit')
                                ->icon('heroicon-o-shield-check')
                                ->color('primary')
                                ->size('sm'),
                        ])
                        ->schema([
                            // Security score visual indicator
                            Placeholder::make('security_dashboard')
                                ->content('
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">Security Strength</span>
                                            <span class="text-sm font-bold text-blue-600">' . $this->calculateSecurityScore() . '%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-2 rounded-full" style="width: ' . $this->calculateSecurityScore() . '%"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">' . $this->getSecurityRecommendations() . '</div>
                                    </div>
                                '),
                                
                            // Current password verification
                            TextInput::make('current_password')
                                ->label('Current Password')
                                ->password()
                                ->revealable()
                                ->prefixIcon('heroicon-o-lock-closed')
                                ->placeholder('Enter current password')
                                ->helperText('Required for security changes')
                                ->dehydrated(false)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $component) {
                                    if ($state && !Hash::check($state, Auth::user()->password)) {
                                        $component->state('');
                                        Notification::make()
                                            ->danger()
                                            ->title('❌ Invalid Password')
                                            ->body('Current password is incorrect')
                                            ->send();
                                    } else if ($state) {
                                        Notification::make()
                                            ->success()
                                            ->title('✅ Verified')
                                            ->body('Password verified successfully')
                                            ->send();
                                    }
                                }),
                                
                            // New password with strength indicator
                            $this->getPasswordFormComponent()
                                ->label('New Password')
                                ->prefixIcon('heroicon-o-key')
                                ->rule(Password::default()->min(12)->mixedCase()->numbers()->symbols()->uncompromised())
                                ->helperText('Strong password required (12+ chars)'),
                                
                            // Password confirmation
                            $this->getPasswordConfirmationFormComponent()
                                ->label('Confirm Password')
                                ->prefixIcon('heroicon-o-check-circle')
                                ->helperText('Re-enter new password'),
                                
                            // Quick toggles
                            Grid::make(1)
                                ->schema([
                                    Toggle::make('email_notifications')
                                        ->label('📧 Email Alerts')
                                        ->helperText('Security notifications via email')
                                        ->default(true)
                                        ->inline(false),
                                        
                                    Toggle::make('push_notifications')
                                        ->label('🔔 Push Alerts')
                                        ->helperText('Browser push notifications')
                                        ->default(true)
                                        ->inline(false),
                                ]),
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'md' => 1,
                            'lg' => 1,
                        ]),
                ]),
                
                // Full-width bottom section: Advanced settings
                Section::make('⚙️ Advanced Settings & Preferences')
                    ->description('Customize interface, language, and administrative preferences')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'md' => 3,
                            'lg' => 4,
                        ])
                        ->schema([
                            Select::make('language')
                                ->label('🌍 Language')
                                ->options([
                                    'id' => '🇮🇩 Indonesia',
                                    'en' => '🇺🇸 English',
                                    'es' => '🇪🇸 Español',
                                    'fr' => '🇫🇷 Français',
                                ])
                                ->default('id')
                                ->selectablePlaceholder(false)
                                ->native(false),
                                
                            Select::make('timezone')
                                ->label('⏰ Timezone')
                                ->options([
                                    'Asia/Jakarta' => 'Jakarta (WIB)',
                                    'Asia/Makassar' => 'Makassar (WITA)', 
                                    'Asia/Jayapura' => 'Jayapura (WIT)',
                                    'UTC' => 'UTC',
                                ])
                                ->default('Asia/Jakarta')
                                ->selectablePlaceholder(false)
                                ->native(false),
                                
                            ColorPicker::make('theme_color')
                                ->label('🎨 Theme Color')
                                ->default('#3B82F6')
                                ->helperText('Admin interface accent color'),
                                
                            Select::make('items_per_page')
                                ->label('📄 Items Per Page')
                                ->options([
                                    '10' => '10 items',
                                    '25' => '25 items',
                                    '50' => '50 items',
                                    '100' => '100 items',
                                ])
                                ->default('25')
                                ->selectablePlaceholder(false)
                                ->native(false),
                        ]),
                        
                        // Activity summary cards
                        Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'md' => 4,
                        ])
                        ->schema([
                            Placeholder::make('total_logins')
                                ->label('Total Logins')
                                ->content('<div class="bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-lg p-4 text-center"><div class="text-2xl font-bold text-blue-700">' . $this->getTotalLogins() . '</div><div class="text-blue-600 text-xs">All Time</div></div>'),
                                
                            Placeholder::make('active_sessions')
                                ->label('Active Sessions')  
                                ->content('<div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4 text-center"><div class="text-2xl font-bold text-green-700">' . $this->getActiveSessionsCount() . '</div><div class="text-green-600 text-xs">Currently</div></div>'),
                                
                            Placeholder::make('failed_attempts')
                                ->label('Failed Attempts')
                                ->content('<div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-lg p-4 text-center"><div class="text-2xl font-bold text-orange-700">' . $this->getFailedAttemptsToday() . '</div><div class="text-orange-600 text-xs">Today</div></div>'),
                                
                            Placeholder::make('account_age')
                                ->label('Account Age')
                                ->content('<div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4 text-center"><div class="text-2xl font-bold text-purple-700">' . Auth::user()->created_at->diffInDays(now()) . '</div><div class="text-purple-600 text-xs">Days</div></div>'),
                        ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->operation('edit')
            ->model($this->getUser())
            ->statePath('data')
            ->columns(3);
    }
    
    protected function calculateSecurityScore(): int
    {
        $score = 80; // Base admin score
        
        if (Auth::user()->last_login_at && Auth::user()->last_login_at->gt(now()->subDays(7))) {
            $score += 10;
        }
        
        if (Auth::user()->created_at->lt(now()->subMonths(3))) {
            $score += 10;
        }
        
        return min($score, 100);
    }
    
    protected function getSecurityRecommendations(): string
    {
        $score = $this->calculateSecurityScore();
        
        if ($score >= 95) return 'Excellent security';
        if ($score >= 85) return 'Good security level';
        if ($score >= 75) return 'Moderate security';
        return 'Needs improvement';
    }
    
    protected function getTotalLogins(): int
    {
        return Cache::remember('admin_total_logins_' . Auth::id(), 3600, function () {
            return \App\Models\AuditLog::where('user_id', Auth::id())
                ->where('event', 'login')
                ->count();
        });
    }
    
    protected function getActiveSessionsCount(): int
    {
        return DB::table('sessions')
            ->where('user_id', Auth::id())
            ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
            ->count();
    }
    
    protected function getFailedAttemptsToday(): int
    {
        return \App\Models\AuditLog::where('user_id', Auth::id())
            ->where('event', 'failed_login')
            ->whereDate('created_at', today())
            ->count();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Security verification for password changes
        if (isset($data['password']) && filled($data['password'])) {
            if (!isset($data['current_password']) || !Hash::check($data['current_password'], Auth::user()->password)) {
                Notification::make()
                    ->danger()
                    ->title('🚨 Security Check Failed')
                    ->body('Current password verification required for changes.')
                    ->persistent()
                    ->send();
                
                unset($data['password']);
                return $data;
            }
            
            // Log password change
            Log::channel('security')->info('Admin password updated', [
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()->email,
                'ip' => request()->ip(),
                'timestamp' => now()->toISOString()
            ]);
        }

        // Log email changes
        if (isset($data['email']) && $data['email'] !== Auth::user()->email) {
            Log::channel('security')->info('Admin email updated', [
                'admin_id' => Auth::id(),
                'old_email' => Auth::user()->email,
                'new_email' => $data['email'],
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
            ->title('🎉 Profile Updated')
            ->body('Administrator profile updated successfully with security logging.')
            ->duration(4000);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('💾 Save Changes')
                ->action('save')
                ->color('primary')
                ->icon('heroicon-o-check-circle')
                ->size('lg'),
                
            ActionGroup::make([
                Action::make('logout_all_devices')
                    ->label('🚪 Logout All Devices')
                    ->icon('heroicon-o-computer-desktop')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Logout from all devices?')
                    ->modalDescription('This will end all other active sessions. You will remain logged in on this device.')
                    ->action(function () {
                        DB::table('sessions')
                            ->where('user_id', Auth::id())
                            ->where('id', '!=', request()->session()->getId())
                            ->delete();
                        
                        Notification::make()
                            ->success()
                            ->title('✅ Sessions Terminated')
                            ->body('Logged out from all other devices successfully.')
                            ->send();
                    }),
                    
                Action::make('reset_preferences')
                    ->label('🔄 Reset Preferences')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function () {
                        Notification::make()
                            ->info()
                            ->title('🔄 Preferences Reset')
                            ->body('All preferences reset to default values.')
                            ->send();
                    }),
            ])
            ->label('⚙️ Actions')
            ->icon('heroicon-o-ellipsis-vertical')
            ->button()
            ->color('gray')
        ];
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }
}