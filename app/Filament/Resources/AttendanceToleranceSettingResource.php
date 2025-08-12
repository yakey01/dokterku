<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceToleranceSettingResource\Pages;
use App\Models\AttendanceToleranceSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceToleranceSettingResource extends Resource
{
    protected static ?string $model = AttendanceToleranceSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = '📋 SCHEDULE MANAGEMENT';

    protected static ?string $navigationLabel = 'Tolerance Settings';

    protected static ?string $modelLabel = 'Tolerance Setting';

    protected static ?string $pluralModelLabel = 'Tolerance Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('setting_name')
                            ->label('Setting Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Standard Working Hours, Doctor Schedule'),

                        Forms\Components\Select::make('scope_type')
                            ->label('Scope Type')
                            ->options([
                                'global' => 'Global (All Users)',
                                'role' => 'Role-based',
                                'user' => 'Specific User',
                                'location' => 'Location-based',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('scope_value')
                            ->label('Scope Value')
                            ->placeholder('Role name, User ID, or Location name')
                            ->visible(fn (callable $get) => in_array($get('scope_type'), ['role', 'user', 'location']))
                            ->required(fn (callable $get) => in_array($get('scope_type'), ['role', 'user', 'location'])),

                        Forms\Components\TextInput::make('priority')
                            ->label('Priority')
                            ->numeric()
                            ->default(10)
                            ->helperText('Lower numbers = higher priority (1 is highest)'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Check-in Tolerance Settings')
                    ->schema([
                        Forms\Components\TextInput::make('check_in_early_tolerance')
                            ->label('Early Check-in Tolerance (minutes)')
                            ->numeric()
                            ->default(15)
                            ->helperText('How many minutes early user can check-in'),

                        Forms\Components\TextInput::make('check_in_late_tolerance')
                            ->label('Late Check-in Tolerance (minutes)')
                            ->numeric()
                            ->default(15)
                            ->helperText('How many minutes late user can check-in'),

                        Forms\Components\Toggle::make('allow_early_checkin')
                            ->label('Allow Early Check-in')
                            ->default(true)
                            ->helperText('Allow check-in before scheduled time'),

                        Forms\Components\Toggle::make('allow_late_checkin')
                            ->label('Allow Late Check-in')
                            ->default(true)
                            ->helperText('Allow check-in after scheduled time'),
                    ])->columns(2),

                Forms\Components\Section::make('Check-out Tolerance Settings')
                    ->schema([
                        Forms\Components\TextInput::make('check_out_early_tolerance')
                            ->label('Early Check-out Tolerance (minutes)')
                            ->numeric()
                            ->default(30)
                            ->helperText('How many minutes early user can check-out'),

                        Forms\Components\TextInput::make('check_out_late_tolerance')
                            ->label('Late Check-out Tolerance (minutes)')
                            ->numeric()
                            ->default(30)
                            ->helperText('How many minutes late user can check-out'),

                        Forms\Components\Toggle::make('allow_early_checkout')
                            ->label('Allow Early Check-out')
                            ->default(false)
                            ->helperText('Allow check-out before scheduled time'),

                        Forms\Components\Toggle::make('allow_late_checkout')
                            ->label('Allow Late Check-out')
                            ->default(true)
                            ->helperText('Allow check-out after scheduled time'),
                    ])->columns(2),

                Forms\Components\Section::make('Weekend Settings')
                    ->schema([
                        Forms\Components\Toggle::make('weekend_different_tolerance')
                            ->label('Different Weekend Tolerance')
                            ->default(false)
                            ->reactive()
                            ->helperText('Use different tolerance for weekends'),

                        Forms\Components\TextInput::make('weekend_check_in_tolerance')
                            ->label('Weekend Check-in Tolerance (minutes)')
                            ->numeric()
                            ->default(30)
                            ->visible(fn (callable $get) => $get('weekend_different_tolerance')),

                        Forms\Components\TextInput::make('weekend_check_out_tolerance')
                            ->label('Weekend Check-out Tolerance (minutes)')
                            ->numeric()
                            ->default(60)
                            ->visible(fn (callable $get) => $get('weekend_different_tolerance')),
                    ])->columns(3),

                Forms\Components\Section::make('Holiday Settings')
                    ->schema([
                        Forms\Components\Toggle::make('holiday_different_tolerance')
                            ->label('Different Holiday Tolerance')
                            ->default(false)
                            ->reactive()
                            ->helperText('Use different tolerance for holidays'),

                        Forms\Components\TextInput::make('holiday_check_in_tolerance')
                            ->label('Holiday Check-in Tolerance (minutes)')
                            ->numeric()
                            ->default(30)
                            ->visible(fn (callable $get) => $get('holiday_different_tolerance')),

                        Forms\Components\TextInput::make('holiday_check_out_tolerance')
                            ->label('Holiday Check-out Tolerance (minutes)')
                            ->numeric()  
                            ->default(60)
                            ->visible(fn (callable $get) => $get('holiday_different_tolerance')),
                    ])->columns(3),

                Forms\Components\Section::make('Advanced Settings')
                    ->schema([
                        Forms\Components\Toggle::make('require_schedule_match')
                            ->label('Require Schedule Match')
                            ->default(true)
                            ->helperText('User must have an active schedule to perform attendance'),

                        Forms\Components\Toggle::make('allow_emergency_override')
                            ->label('Allow Emergency Override')
                            ->default(false)
                            ->reactive()
                            ->helperText('Allow authorized users to override violations'),

                        Forms\Components\CheckboxList::make('emergency_override_roles')
                            ->label('Emergency Override Roles')
                            ->options([
                                'admin' => 'Admin',
                                'manager' => 'Manager',
                                'supervisor' => 'Supervisor',
                                'hr' => 'HR Staff',
                            ])
                            ->visible(fn (callable $get) => $get('allow_emergency_override'))
                            ->columns(2),

                        Forms\Components\TextInput::make('emergency_override_duration')
                            ->label('Override Duration (hours)')
                            ->numeric()
                            ->default(24)
                            ->visible(fn (callable $get) => $get('allow_emergency_override'))
                            ->helperText('How long override permission lasts'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Optional description for this setting'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('setting_name')
                    ->label('Setting Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('scope_type')
                    ->label('Scope')
                    ->colors([
                        'success' => 'global',
                        'primary' => 'role',
                        'warning' => 'user',
                        'secondary' => 'location',
                    ]),

                Tables\Columns\TextColumn::make('scope_value')
                    ->label('Scope Value')
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 3 => 'success',
                        $state <= 7 => 'warning',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('check_in_early_tolerance')
                    ->label('Check-in Early')
                    ->suffix(' min')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('check_in_late_tolerance')
                    ->label('Check-in Late')
                    ->suffix(' min')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('check_out_early_tolerance')
                    ->label('Check-out Early')
                    ->suffix(' min')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('check_out_late_tolerance')
                    ->label('Check-out Late')
                    ->suffix(' min')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('allow_early_checkin')
                    ->label('Early Check-in')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('allow_late_checkin')
                    ->label('Late Check-in')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('allow_emergency_override')
                    ->label('Emergency Override')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scope_type')
                    ->options([
                        'global' => 'Global',
                        'role' => 'Role-based',
                        'user' => 'User-specific',
                        'location' => 'Location-based',
                    ]),

                Tables\Filters\Filter::make('is_active')
                    ->toggle()
                    ->query(fn ($query) => $query->where('is_active', true)),

                Tables\Filters\Filter::make('allow_emergency_override')
                    ->toggle()
                    ->query(fn ($query) => $query->where('allow_emergency_override', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'asc');
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
            'index' => Pages\ListAttendanceToleranceSettings::route('/'),
            'create' => Pages\CreateAttendanceToleranceSetting::route('/create'),
            'view' => Pages\ViewAttendanceToleranceSetting::route('/{record}'),
            'edit' => Pages\EditAttendanceToleranceSetting::route('/{record}/edit'),
        ];
    }
    
    /**
     * Required for navigation visibility
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    /**
     * Required for resource access
     */
    public static function canViewAny(): bool
    {
        return true;
    }
}