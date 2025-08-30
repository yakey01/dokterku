<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\UserSession;
use App\Models\TwoFactorAuth;
use App\Services\TwoFactorAuthService;
use App\Services\SecurityService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SecuritySettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static string $view = 'filament.pages.security-settings';
    
    protected static ?string $navigationLabel = 'Security Settings';
    
    protected static ?string $title = 'Security Settings';
    
    protected static ?string $navigationGroup = '⚙️ SYSTEM ADMINISTRATION';
    
    protected static ?int $navigationSort = 6;
    
    protected static ?string $slug = 'security-settings';
    
    // Page properties
    public $twoFactorStatus = [];
    public $activeSessions = [];
    public $securityStats = [];
    public $lastUpdate;
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['super-admin', 'admin']);
    }
    
    public function mount(): void
    {
        $this->loadSecurityData();
    }
    
    public function loadSecurityData(): void
    {
        $twoFactorService = app(TwoFactorAuthService::class);
        $securityService = app(SecurityService::class);
        
        $this->twoFactorStatus = $twoFactorService->getComplianceReport();
        $this->activeSessions = $securityService->getActiveSessions(Auth::user());
        $this->securityStats = $securityService->getSecurityStats();
        $this->lastUpdate = now()->format('Y-m-d H:i:s');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('loadSecurityData'),
                
            Action::make('setup_2fa')
                ->icon('heroicon-o-device-tablet')
                ->color('primary')
                ->visible(fn() => !app(TwoFactorAuthService::class)->isEnabled(Auth::user()))
                ->url(route('filament.admin.pages.two-factor-setup')),
                
            Action::make('manage_sessions')
                ->icon('heroicon-o-computer-desktop')
                ->color('warning')
                ->modalHeading('Manage Active Sessions')
                ->modalContent(view('filament.modals.active-sessions', ['sessions' => $this->activeSessions]))
                ->modalWidth('4xl')
                ->modalActions([
                    Action::make('terminate_all')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function () {
                            $count = app(SecurityService::class)->terminateAllSessions(Auth::user());
                            Notification::make()
                                ->title("Terminated {$count} sessions")
                                ->success()
                                ->send();
                        }),
                ]),
        ];
    }
    
    public function changePassword(): void
    {
        $this->mountAction('changePassword');
    }
    
    public function getChangePasswordAction(): Action
    {
        return Action::make('changePassword')
            ->label('Change Password')
            ->icon('heroicon-o-key')
            ->color('warning')
            ->form([
                Grid::make(1)->schema([
                    TextInput::make('current_password')
                        ->label('Current Password')
                        ->password()
                        ->required()
                        ->rules(['required', 'min:8']),
                    TextInput::make('new_password')
                        ->label('New Password')
                        ->password()
                        ->required()
                        ->rules(['required', 'min:8', 'confirmed']),
                    TextInput::make('new_password_confirmation')
                        ->label('Confirm New Password')
                        ->password()
                        ->required(),
                ])
            ])
            ->action(function (array $data) {
                $securityService = app(SecurityService::class);
                $result = $securityService->changePassword(
                    Auth::user(),
                    $data['current_password'],
                    $data['new_password']
                );
                
                if ($result['success']) {
                    Notification::make()
                        ->title($result['message'])
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title($result['message'])
                        ->danger()
                        ->send();
                }
            });
    }
    
    public function terminateSession(string $sessionId): void
    {
        $securityService = app(SecurityService::class);
        $success = $securityService->terminateSession($sessionId, 'manual');
        
        if ($success) {
            $this->loadSecurityData();
            Notification::make()
                ->title('Session terminated successfully')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to terminate session')
                ->danger()
                ->send();
        }
    }
    
    public function terminateAllSessions(): void
    {
        $securityService = app(SecurityService::class);
        $count = $securityService->terminateAllSessions(Auth::user());
        
        $this->loadSecurityData();
        
        Notification::make()
            ->title("Terminated {$count} sessions")
            ->success()
            ->send();
    }
    
    public function getSecurityScore(): int
    {
        $score = 100;
        
        // Deduct points for missing 2FA
        if (!app(TwoFactorAuthService::class)->isEnabled(Auth::user())) {
            $score -= 30;
        }
        
        // Deduct points for multiple active sessions
        $sessionCount = $this->activeSessions->count();
        if ($sessionCount > 3) {
            $score -= min(20, ($sessionCount - 3) * 5);
        }
        
        // Deduct points for recent failed logins
        $recentFailures = $this->securityStats['failed_logins_24h'] ?? 0;
        if ($recentFailures > 0) {
            $score -= min(15, $recentFailures * 3);
        }
        
        return max(0, $score);
    }
    
    public function getSecurityScoreColor(): string
    {
        $score = $this->getSecurityScore();
        
        if ($score >= 80) return 'success';
        if ($score >= 60) return 'warning';
        return 'danger';
    }
    
    public function getSecurityRecommendations(): array
    {
        $recommendations = [];
        
        if (!app(TwoFactorAuthService::class)->isEnabled(Auth::user())) {
            $recommendations[] = [
                'title' => 'Enable Two-Factor Authentication',
                'description' => 'Secure your account with 2FA for enhanced security.',
                'priority' => 'high',
                'action' => 'setup_2fa'
            ];
        }
        
        if ($this->activeSessions->count() > 3) {
            $recommendations[] = [
                'title' => 'Review Active Sessions',
                'description' => 'You have multiple active sessions. Consider terminating unused ones.',
                'priority' => 'medium',
                'action' => 'manage_sessions'
            ];
        }
        
        if (($this->securityStats['failed_logins_24h'] ?? 0) > 0) {
            $recommendations[] = [
                'title' => 'Review Failed Login Attempts',
                'description' => 'Recent failed login attempts detected. Monitor for suspicious activity.',
                'priority' => 'high',
                'action' => null
            ];
        }
        
        return $recommendations;
    }
}