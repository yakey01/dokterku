<?php

namespace App\Filament\Resources\GPSValidationResource\Pages;

use App\Filament\Resources\GPSValidationResource;
use App\Models\User;
use App\Services\AttendanceValidationService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;

class ListGPSValidations extends ListRecords
{
    protected static string $resource = GPSValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_override')
                ->label('Create GPS Override')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(route('filament.admin.resources.gps-validations.create')),
                
            Actions\Action::make('system_diagnostics')
                ->label('System Diagnostics')
                ->icon('heroicon-o-cpu-chip')
                ->color('info')
                ->action(function () {
                    $this->runSystemDiagnostics();
                }),
                
            Actions\Action::make('clear_expired_overrides')
                ->label('Clear Expired')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->clearExpiredOverrides();
                }),
        ];
    }

    protected function runSystemDiagnostics(): void
    {
        $diagnostics = [
            'total_users_with_work_locations' => 0,
            'active_overrides' => 0,
            'expired_overrides' => 0,
            'users_without_locations' => 0,
            'recent_validation_failures' => 0,
        ];

        // Count users with work locations
        $usersWithLocations = User::whereNotNull('work_location_id')->get();
        $diagnostics['total_users_with_work_locations'] = $usersWithLocations->count();
        
        // Count users without work locations
        $diagnostics['users_without_locations'] = User::whereNull('work_location_id')->count();

        $validationService = app(AttendanceValidationService::class);
        
        foreach ($usersWithLocations as $user) {
            $overrideCheck = $validationService->hasActiveGPSOverride($user);
            
            if ($overrideCheck['has_override']) {
                $override = $overrideCheck['override_data'];
                $expiresAt = Carbon::parse($override['expires_at']);
                
                if ($expiresAt->isFuture()) {
                    $diagnostics['active_overrides']++;
                } else {
                    $diagnostics['expired_overrides']++;
                }
            }
        }

        $message = sprintf(
            "System Diagnostics:\n• Users with work locations: %d\n• Users without locations: %d\n• Active GPS overrides: %d\n• Expired overrides: %d",
            $diagnostics['total_users_with_work_locations'],
            $diagnostics['users_without_locations'],
            $diagnostics['active_overrides'],
            $diagnostics['expired_overrides']
        );

        Notification::make()
            ->title('System Diagnostics Complete')
            ->body($message)
            ->info()
            ->duration(10000)
            ->send();
    }

    protected function clearExpiredOverrides(): void
    {
        $cleared = 0;
        $usersWithLocations = User::whereNotNull('work_location_id')->get();
        $validationService = app(AttendanceValidationService::class);
        
        foreach ($usersWithLocations as $user) {
            $overrideCheck = $validationService->hasActiveGPSOverride($user);
            
            if ($overrideCheck['has_override']) {
                $override = $overrideCheck['override_data'];
                $expiresAt = Carbon::parse($override['expires_at']);
                
                if ($expiresAt->isPast()) {
                    $cacheKey = "gps_override_{$user->id}_" . now()->format('Y-m-d');
                    \Cache::forget($cacheKey);
                    $cleared++;
                }
            }
        }

        Notification::make()
            ->title('Expired Overrides Cleared')
            ->body("Cleared {$cleared} expired GPS overrides")
            ->success()
            ->send();
    }

    public function getTitle(): string
    {
        return 'GPS Validation Management';
    }

    public function getHeading(): string
    {
        return 'GPS Validation & Override Management';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Could add custom widgets here for GPS statistics
        ];
    }

    // Override the view to add custom content
    public function render(): View
    {
        $data = $this->getViewData();
        
        // Add custom GPS validation statistics
        $usersWithLocations = User::whereNotNull('work_location_id')->count();
        $usersWithoutLocations = User::whereNull('work_location_id')->count();
        $activeOverrides = 0;
        
        $validationService = app(AttendanceValidationService::class);
        $usersToCheck = User::whereNotNull('work_location_id')->get();
        
        foreach ($usersToCheck as $user) {
            $overrideCheck = $validationService->hasActiveGPSOverride($user);
            if ($overrideCheck['has_override']) {
                $activeOverrides++;
            }
        }
        
        $data['gps_stats'] = [
            'users_with_locations' => $usersWithLocations,
            'users_without_locations' => $usersWithoutLocations,
            'active_overrides' => $activeOverrides,
            'total_users' => User::count(),
        ];
        
        return view('filament.resources.gps-validation-resource.pages.list-gps-validations', $data);
    }
}