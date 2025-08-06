<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserScheduleResource\Pages;
use App\Models\UserSchedule;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserScheduleResource extends Resource
{
    protected static ?string $model = UserSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = '📋 SCHEDULE MANAGEMENT';

    protected static ?string $navigationLabel = 'User Schedules';

    protected static ?string $modelLabel = 'User Schedule';

    protected static ?string $pluralModelLabel = 'User Schedules';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('schedule_name')
                            ->label('Schedule Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Morning Shift, Doctor Schedule'),

                        Forms\Components\Select::make('schedule_type')
                            ->label('Schedule Type')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'custom' => 'Custom Date',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('day_of_week', null)),

                        Forms\Components\Select::make('day_of_week')
                            ->label('Day of Week')
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->visible(fn (callable $get) => $get('schedule_type') === 'weekly')
                            ->required(fn (callable $get) => $get('schedule_type') === 'weekly'),

                        Forms\Components\DatePicker::make('schedule_date')
                            ->label('Schedule Date')
                            ->visible(fn (callable $get) => $get('schedule_type') === 'custom')
                            ->required(fn (callable $get) => $get('schedule_type') === 'custom'),
                    ])->columns(2),

                Forms\Components\Section::make('Time Configuration')
                    ->schema([
                        Forms\Components\TimePicker::make('check_in_time')
                            ->label('Check-in Time')
                            ->required()
                            ->seconds(false),

                        Forms\Components\TimePicker::make('check_out_time')
                            ->label('Check-out Time')
                            ->required()
                            ->seconds(false)
                            ->after('check_in_time'),

                        Forms\Components\TextInput::make('work_duration_minutes')
                            ->label('Work Duration (Minutes)')
                            ->numeric()
                            ->placeholder('Auto-calculated if empty'),
                    ])->columns(3),

                Forms\Components\Section::make('Schedule Validity')
                    ->schema([
                        Forms\Components\DatePicker::make('effective_from')
                            ->label('Effective From')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('effective_until')
                            ->label('Effective Until')
                            ->after('effective_from'),

                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Is Recurring')
                            ->default(true)
                            ->helperText('Whether this schedule repeats'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                            ])
                            ->default('active')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('work_location')
                            ->label('Work Location')
                            ->maxLength(255)
                            ->placeholder('e.g., Main Clinic, Branch Office'),

                        Forms\Components\TextInput::make('role_context')
                            ->label('Role Context')
                            ->maxLength(255)
                            ->placeholder('e.g., Doctor, Nurse, Admin'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\KeyValue::make('exceptions')
                            ->label('Exception Dates')
                            ->helperText('Dates when this schedule does not apply (YYYY-MM-DD format)'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('schedule_name')
                    ->label('Schedule')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('schedule_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'daily',
                        'success' => 'weekly',
                        'warning' => 'custom',
                    ]),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn (string $state = null): string => 
                        $state ? ucfirst($state) : '-'
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('check_in_time')
                    ->label('Check-in')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_out_time')
                    ->label('Check-out')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('work_location')
                    ->label('Location')
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'suspended',
                    ]),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Recurring')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('effective_from')
                    ->label('From')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('effective_until')
                    ->label('Until')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('schedule_type')
                    ->options([
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'custom' => 'Custom',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ]),

                Tables\Filters\Filter::make('is_recurring')
                    ->toggle()
                    ->query(fn ($query) => $query->where('is_recurring', true)),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUserSchedules::route('/'),
            'create' => Pages\CreateUserSchedule::route('/create'),
            'view' => Pages\ViewUserSchedule::route('/{record}'),
            'edit' => Pages\EditUserSchedule::route('/{record}/edit'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}