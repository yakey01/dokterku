<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingResource\Pages;
use App\Filament\Resources\SystemSettingResource\RelationManagers;
use App\Models\SystemSetting;
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

class SystemSettingResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'System Settings';
    
    protected static ?string $modelLabel = 'System Setting';
    
    protected static ?string $pluralModelLabel = 'System Settings';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Setting Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('Unique identifier for this setting'),
                                
                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Human-readable label for this setting'),
                            ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->helperText('Optional description explaining what this setting does'),
                        
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('group')
                                    ->required()
                                    ->options([
                                        'general' => 'General',
                                        'security' => 'Security',
                                        'notification' => 'Notification',
                                        'system' => 'System',
                                        'ui' => 'User Interface',
                                        'api' => 'API',
                                        'backup' => 'Backup',
                                        'maintenance' => 'Maintenance',
                                    ])
                                    ->default('general'),
                                
                                Forms\Components\Select::make('type')
                                    ->required()
                                    ->options([
                                        'string' => 'String',
                                        'boolean' => 'Boolean',
                                        'integer' => 'Integer',
                                        'float' => 'Float',
                                        'array' => 'Array',
                                        'object' => 'Object',
                                    ])
                                    ->default('string')
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('value', null)),
                            ]),
                    ]),
                
                Section::make('Setting Value')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->required()
                            ->visible(fn ($get) => in_array($get('type'), ['string', 'integer', 'float']))
                            ->helperText('The actual value for this setting'),
                        
                        Forms\Components\Toggle::make('value')
                            ->label('Value')
                            ->visible(fn ($get) => $get('type') === 'boolean')
                            ->helperText('Enable or disable this setting'),
                        
                        Forms\Components\Textarea::make('value')
                            ->label('Value')
                            ->visible(fn ($get) => in_array($get('type'), ['array', 'object']))
                            ->helperText('JSON formatted value for array/object types')
                            ->rows(4),
                    ]),
                
                Section::make('Configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_public')
                                    ->label('Public Access')
                                    ->helperText('Allow access without authentication'),
                                
                                Forms\Components\Toggle::make('is_readonly')
                                    ->label('Read Only')
                                    ->helperText('Prevent modification via UI'),
                            ]),
                        
                        Forms\Components\Textarea::make('validation_rules')
                            ->label('Validation Rules (JSON)')
                            ->helperText('JSON formatted validation rules')
                            ->rows(2),
                        
                        Forms\Components\Textarea::make('meta')
                            ->label('Metadata (JSON)')
                            ->helperText('Additional metadata for this setting')
                            ->rows(2),
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
                
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('group')
                    ->badge()
                    ->colors([
                        'primary' => 'general',
                        'danger' => 'security',
                        'warning' => 'notification',
                        'success' => 'system',
                        'info' => 'ui',
                    ])
                    ->sortable(),
                
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                
                TextColumn::make('value')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    }),
                
                IconColumn::make('is_public')
                    ->boolean()
                    ->tooltip('Public Access'),
                
                IconColumn::make('is_readonly')
                    ->boolean()
                    ->tooltip('Read Only'),
                
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options([
                        'general' => 'General',
                        'security' => 'Security',
                        'notification' => 'Notification',
                        'system' => 'System',
                        'ui' => 'User Interface',
                        'api' => 'API',
                        'backup' => 'Backup',
                        'maintenance' => 'Maintenance',
                    ]),
                
                SelectFilter::make('type')
                    ->options([
                        'string' => 'String',
                        'boolean' => 'Boolean',
                        'integer' => 'Integer',
                        'float' => 'Float',
                        'array' => 'Array',
                        'object' => 'Object',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public Access'),
                
                Tables\Filters\TernaryFilter::make('is_readonly')
                    ->label('Read Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->is_readonly),
                
                Tables\Actions\ViewAction::make(),
                
                Action::make('clear_cache')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function ($record) {
                        SystemSetting::clearCache();
                        Notification::make()
                            ->title('Cache Cleared')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Clear setting cache'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn ($records) => $records && $records->filter(fn ($record) => !$record->is_readonly)->count() > 0),
                    
                    Tables\Actions\BulkAction::make('clear_cache')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function () {
                            SystemSetting::clearCache();
                            Notification::make()
                                ->title('All Settings Cache Cleared')
                                ->success()
                                ->send();
                        })
                        ->label('Clear Cache'),
                ]),
            ])
            ->defaultSort('group')
            ->defaultGroup('group');
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
            'index' => Pages\ListSystemSettings::route('/'),
            'create' => Pages\CreateSystemSetting::route('/create'),
            'edit' => Pages\EditSystemSetting::route('/{record}/edit'),
        ];
    }
}
