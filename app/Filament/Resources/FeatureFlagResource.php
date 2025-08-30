<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeatureFlagResource\Pages;
use App\Filament\Resources\FeatureFlagResource\RelationManagers;
use App\Models\FeatureFlag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\TernaryFilter;

class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    
    protected static ?string $navigationLabel = 'Feature Flags';
    
    protected static ?string $modelLabel = 'Feature Flag';
    
    protected static ?string $pluralModelLabel = 'Feature Flags';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Feature Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('Unique identifier for this feature flag'),
                                
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Human-readable name for this feature'),
                            ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->helperText('Description of what this feature does'),
                    ]),
                
                Section::make('Feature Configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_enabled')
                                    ->label('Enabled')
                                    ->default(false)
                                    ->helperText('Enable or disable this feature globally'),
                                
                                Forms\Components\Select::make('environment')
                                    ->options([
                                        'production' => 'Production',
                                        'staging' => 'Staging',
                                        'development' => 'Development',
                                        'testing' => 'Testing',
                                    ])
                                    ->nullable()
                                    ->helperText('Limit to specific environment (leave empty for all)'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Start Date')
                                    ->nullable()
                                    ->helperText('When this feature should become available'),
                                
                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('End Date')
                                    ->nullable()
                                    ->helperText('When this feature should stop being available'),
                            ]),
                        
                        Forms\Components\Toggle::make('is_permanent')
                            ->label('Permanent')
                            ->helperText('Cannot be disabled via UI (requires code change)'),
                    ]),
                
                Section::make('Access Conditions')
                    ->schema([
                        Forms\Components\Textarea::make('conditions')
                            ->label('Conditions (JSON)')
                            ->helperText('JSON conditions for feature access (users, roles, percentage rollout)')
                            ->rows(4)
                            ->placeholder('{"roles": ["admin", "manager"], "percentage": 50}'),
                    ]),
                
                Section::make('Metadata')
                    ->schema([
                        Forms\Components\Textarea::make('meta')
                            ->label('Additional Metadata (JSON)')
                            ->helperText('Extra configuration data for this feature')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                IconColumn::make('is_enabled')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->label('Status'),
                
                TextColumn::make('environment')
                    ->badge()
                    ->colors([
                        'danger' => 'production',
                        'warning' => 'staging',
                        'success' => 'development',
                        'info' => 'testing',
                    ])
                    ->placeholder('All environments'),
                
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('No start date'),
                
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('No end date'),
                
                IconColumn::make('is_permanent')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->label('Permanent'),
                
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')
                    ->label('Enabled'),
                
                SelectFilter::make('environment')
                    ->options([
                        'production' => 'Production',
                        'staging' => 'Staging',
                        'development' => 'Development',
                        'testing' => 'Testing',
                    ]),
                
                TernaryFilter::make('is_permanent')
                    ->label('Permanent'),
            ])
            ->actions([
                Action::make('toggle')
                    ->icon(fn ($record) => $record->is_enabled ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->is_enabled ? 'warning' : 'success')
                    ->label(fn ($record) => $record->is_enabled ? 'Disable' : 'Enable')
                    ->visible(fn ($record) => !$record->is_permanent)
                    ->action(function ($record) {
                        if ($record->is_enabled) {
                            FeatureFlag::disable($record->key);
                            Notification::make()
                                ->title('Feature Disabled')
                                ->body("Feature '{$record->name}' has been disabled.")
                                ->warning()
                                ->send();
                        } else {
                            FeatureFlag::enable($record->key);
                            Notification::make()
                                ->title('Feature Enabled')
                                ->body("Feature '{$record->name}' has been enabled.")
                                ->success()
                                ->send();
                        }
                    }),
                
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\ViewAction::make(),
                
                Action::make('clear_cache')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function ($record) {
                        FeatureFlag::clearCache();
                        Notification::make()
                            ->title('Cache Cleared')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Clear feature flag cache'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $enabled = 0;
                            foreach ($records as $record) {
                                if (!$record->is_permanent) {
                                    FeatureFlag::enable($record->key);
                                    $enabled++;
                                }
                            }
                            Notification::make()
                                ->title('Features Enabled')
                                ->body("{$enabled} features have been enabled.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('disable')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function ($records) {
                            $disabled = 0;
                            foreach ($records as $record) {
                                if (!$record->is_permanent) {
                                    FeatureFlag::disable($record->key);
                                    $disabled++;
                                }
                            }
                            Notification::make()
                                ->title('Features Disabled')
                                ->body("{$disabled} features have been disabled.")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn ($records) => $records && $records->filter(fn ($record) => !$record->is_permanent)->count() > 0),
                    
                    Tables\Actions\BulkAction::make('clear_cache')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function () {
                            FeatureFlag::clearCache();
                            Notification::make()
                                ->title('All Feature Flag Cache Cleared')
                                ->success()
                                ->send();
                        })
                        ->label('Clear Cache'),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeatureFlags::route('/'),
            'create' => Pages\CreateFeatureFlag::route('/create'),
            'edit' => Pages\EditFeatureFlag::route('/{record}/edit'),
        ];
    }
}
