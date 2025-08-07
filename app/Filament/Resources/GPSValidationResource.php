<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GPSValidationResource\Pages;
use App\Models\User;
use App\Services\AttendanceValidationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GPSValidationResource extends Resource
{
    protected static ?string $model = null; // This is a virtual resource
    
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'GPS Validation';
    protected static ?string $navigationGroup = 'System Management';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('GPS Override Creation')
                    ->description('Create temporary GPS validation override for testing or troubleshooting')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(User::whereNotNull('work_location_id')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user && $user->workLocation) {
                                        $set('work_location_name', $user->workLocation->name);
                                        $set('work_location_address', $user->workLocation->address);
                                    }
                                }
                            }),
                            
                        Forms\Components\TextInput::make('work_location_name')
                            ->label('Work Location')
                            ->disabled()
                            ->dehydrated(false),
                            
                        Forms\Components\TextInput::make('work_location_address')
                            ->label('Work Location Address')
                            ->disabled()
                            ->dehydrated(false),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Test Latitude')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->required()
                                    ->helperText('GPS coordinates to test (e.g., -6.2088)'),
                                    
                                Forms\Components\TextInput::make('longitude')
                                    ->label('Test Longitude')
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->required()
                                    ->helperText('GPS coordinates to test (e.g., 106.8456)'),
                            ]),
                            
                        Forms\Components\TextInput::make('accuracy')
                            ->label('GPS Accuracy (meters)')
                            ->numeric()
                            ->min(1)
                            ->max(1000)
                            ->default(10)
                            ->helperText('Simulated GPS accuracy in meters'),
                            
                        Forms\Components\Textarea::make('reason')
                            ->label('Override Reason')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->helperText('Explain why this override is necessary'),
                            
                        Forms\Components\Select::make('duration_hours')
                            ->label('Override Duration')
                            ->options([
                                1 => '1 Hour',
                                4 => '4 Hours',
                                8 => '8 Hours (Work Day)',
                                24 => '24 Hours',
                                48 => '48 Hours',
                                72 => '72 Hours (3 Days)',
                            ])
                            ->default(8)
                            ->required()
                            ->helperText('How long the override should remain active'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Since this is a virtual resource, we'll show active overrides
        $activeOverrides = collect();
        
        // Get all users with work locations
        $usersWithLocations = User::whereNotNull('work_location_id')->get();
        
        foreach ($usersWithLocations as $user) {
            $validationService = app(AttendanceValidationService::class);
            $overrideCheck = $validationService->hasActiveGPSOverride($user);
            
            if ($overrideCheck['has_override']) {
                $override = $overrideCheck['override_data'];
                $override['user'] = $user;
                $activeOverrides->push((object) $override);
            }
        }

        return $table
            ->query(function () use ($activeOverrides) {
                // Create a fake query builder for the collection
                return new class($activeOverrides) {
                    public $items;
                    public function __construct($items) { $this->items = $items; }
                    public function paginate($perPage = 15) { return $this->items->paginate($perPage); }
                    public function get() { return $this->items; }
                };
            })
            ->columns([
                Tables\Columns\TextColumn::make('target_user_name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('admin_name')
                    ->label('Created By')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->reason ?? '';
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(function ($record) {
                        $expiresAt = Carbon::parse($record->expires_at ?? now());
                        $hoursLeft = now()->diffInHours($expiresAt, false);
                        
                        if ($hoursLeft < 1) return 'danger';
                        if ($hoursLeft < 4) return 'warning';
                        return 'success';
                    }),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $expiresAt = Carbon::parse($record->expires_at ?? now());
                        return $expiresAt->isFuture() ? 'Active' : 'Expired';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Expired' => 'danger',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('test_coordinates')
                    ->label('Test GPS')
                    ->icon('heroicon-o-map-pin')
                    ->action(function ($record) {
                        // Test the GPS coordinates
                        $validationService = app(AttendanceValidationService::class);
                        $user = User::find($record->target_user_id);
                        
                        if (!$user || !$user->workLocation) {
                            Notification::make()
                                ->title('Error')
                                ->body('User or work location not found')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $coordinates = $record->coordinates ?? [];
                        $latitude = $coordinates['latitude'] ?? 0;
                        $longitude = $coordinates['longitude'] ?? 0;
                        
                        $validation = $validationService->validateWorkLocation($user, $latitude, $longitude);
                        
                        $status = $validation['valid'] ? 'Success' : 'Failed';
                        $color = $validation['valid'] ? 'success' : 'danger';
                        
                        Notification::make()
                            ->title("GPS Test: {$status}")
                            ->body($validation['message'])
                            ->color($color)
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('revoke_override')
                    ->label('Revoke')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Revoke the override by removing it from cache
                        $user = User::find($record->target_user_id);
                        if ($user) {
                            $cacheKey = "gps_override_{$user->id}_" . now()->format('Y-m-d');
                            Cache::forget($cacheKey);
                            
                            Notification::make()
                                ->title('Override Revoked')
                                ->body("GPS override for {$user->name} has been revoked")
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_override')
                    ->label('Create GPS Override')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->url(route('filament.admin.resources.gps-validations.create')),
                    
                Tables\Actions\Action::make('view_logs')
                    ->label('View GPS Logs')
                    ->icon('heroicon-o-document-text')
                    ->url('#') // Would link to a dedicated logs page
                    ->disabled(), // Disabled for now
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGPSValidations::route('/'),
            'create' => Pages\CreateGPSValidation::route('/create'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        // Count active overrides
        $activeCount = 0;
        $usersWithLocations = User::whereNotNull('work_location_id')->get();
        
        foreach ($usersWithLocations as $user) {
            $validationService = app(AttendanceValidationService::class);
            $overrideCheck = $validationService->hasActiveGPSOverride($user);
            
            if ($overrideCheck['has_override']) {
                $activeCount++;
            }
        }
        
        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}