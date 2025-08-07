<?php

namespace App\Filament\Resources\GPSValidationResource\Pages;

use App\Filament\Resources\GPSValidationResource;
use App\Models\User;
use App\Services\AttendanceValidationService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Get;

class CreateGPSValidation extends CreateRecord
{
    protected static string $resource = GPSValidationResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getTestCoordinatesAction(),
            $this->getCreateAction(),
            $this->getCancelAction(),
        ];
    }

    protected function getTestCoordinatesAction(): Action
    {
        return Action::make('test_coordinates')
            ->label('Test GPS Coordinates')
            ->icon('heroicon-o-map-pin')
            ->color('info')
            ->action(function (array $data) {
                $this->testGPSCoordinates($data);
            });
    }

    protected function testGPSCoordinates(array $data): void
    {
        if (empty($data['user_id']) || empty($data['latitude']) || empty($data['longitude'])) {
            Notification::make()
                ->title('Validation Error')
                ->body('Please fill in User, Latitude, and Longitude fields before testing')
                ->danger()
                ->send();
            return;
        }

        $user = User::find($data['user_id']);
        if (!$user) {
            Notification::make()
                ->title('Error')
                ->body('User not found')
                ->danger()
                ->send();
            return;
        }

        if (!$user->workLocation) {
            Notification::make()
                ->title('Error')
                ->body('User does not have an assigned work location')
                ->danger()
                ->send();
            return;
        }

        $validationService = app(AttendanceValidationService::class);
        
        // Get comprehensive GPS diagnostics
        $diagnostics = $validationService->getGPSDiagnosticInfo(
            $data['latitude'],
            $data['longitude'],
            $data['accuracy'] ?? null,
            $user->workLocation
        );

        // Test validation
        $validation = $validationService->validateWorkLocation(
            $user,
            $data['latitude'],
            $data['longitude'],
            $data['accuracy'] ?? null
        );

        // Create detailed test result message
        $workLocationAnalysis = $diagnostics['work_location_analysis'] ?? [];
        $locationAnalysis = $diagnostics['location_analysis'] ?? [];
        
        $message = sprintf(
            "GPS Test Results for %s:\n\n" .
            "📍 Distance: %.0f meters (Limit: %d meters)\n" .
            "✅ Within Geofence: %s\n" .
            "🔍 Coordinate Quality: %s\n" .
            "🌍 Estimated Region: %s\n" .
            "🔒 VPN/Proxy Risk: %s\n\n" .
            "Validation: %s\n" .
            "Message: %s",
            $user->name,
            $workLocationAnalysis['distance_meters'] ?? 0,
            $user->workLocation->radius_meters,
            ($workLocationAnalysis['within_geofence'] ?? false) ? 'Yes' : 'No',
            ucfirst($locationAnalysis['coordinate_quality']['quality'] ?? 'unknown'),
            ucfirst($locationAnalysis['estimated_region']['region'] ?? 'unknown'),
            ucfirst($locationAnalysis['potential_vpn_proxy']['risk_level'] ?? 'unknown'),
            $validation['valid'] ? 'PASSED' : 'FAILED',
            $validation['message']
        );

        $notificationColor = $validation['valid'] ? 'success' : 'danger';
        $notificationTitle = $validation['valid'] ? 'GPS Test Passed' : 'GPS Test Failed';

        Notification::make()
            ->title($notificationTitle)
            ->body($message)
            ->color($notificationColor)
            ->duration(15000)
            ->send();

        // Add troubleshooting tips if validation failed
        if (!$validation['valid'] && !empty($validation['data']['troubleshooting_tips'] ?? [])) {
            $tips = collect($validation['data']['troubleshooting_tips'])
                ->map(fn($tip) => "• " . ($tip['title'] ?? 'Unknown') . ": " . ($tip['description'] ?? ''))
                ->take(3)
                ->implode("\n");

            Notification::make()
                ->title('🔧 Troubleshooting Tips')
                ->body($tips)
                ->info()
                ->duration(10000)
                ->send();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = User::find($data['user_id']);
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Create the GPS override using the validation service
        $admin = auth()->user();
        $validationService = app(AttendanceValidationService::class);
        
        $result = $validationService->createAdminGPSOverride(
            $admin,
            $user,
            $data['latitude'],
            $data['longitude'],
            $data['reason']
        );

        if (!$result['success']) {
            throw new \Exception($result['message']);
        }

        Notification::make()
            ->title('GPS Override Created')
            ->body("GPS validation override created for {$user->name}")
            ->success()
            ->send();

        // Since this is a virtual resource, we don't actually create a record
        // Instead, we redirect back to the list page
        $this->redirect(route('filament.admin.resources.gps-validations.index'));
        
        return $data;
    }

    public function getTitle(): string
    {
        return 'Create GPS Validation Override';
    }

    public function getHeading(): string
    {
        return 'Create GPS Validation Override';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Override to prevent actual record creation since this is a virtual resource
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // This method won't be called because we handle everything in mutateFormDataBeforeCreate
        // But we need to return something to satisfy the interface
        return new class extends \Illuminate\Database\Eloquent\Model {
            protected $fillable = ['*'];
        };
    }
}