<?php

namespace App\Filament\Manajer\Resources;

use App\Filament\Manajer\Resources\StrategicGoalResource\Pages;
use App\Models\StrategicGoal;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StrategicGoalResource extends Resource
{
    protected static ?string $model = StrategicGoal::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    
    protected static ?string $navigationLabel = 'Strategic Goals';
    
    protected static ?string $navigationGroup = '🎯 Strategic Planning';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🎯 Strategic Goal Details')
                    ->description('Define strategic objectives and key performance indicators')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Goal Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Increase monthly revenue by 25%'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(3)
                            ->placeholder('Detailed description of the strategic goal...'),

                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options(StrategicGoal::getCategoryOptions())
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('period')
                            ->label('Time Period')
                            ->options(StrategicGoal::getPeriodOptions())
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('📊 Targets & Metrics')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->after('start_date'),

                        Forms\Components\TextInput::make('target_value')
                            ->label('Target Value')
                            ->required()
                            ->numeric()
                            ->step(0.01),

                        Forms\Components\TextInput::make('current_value')
                            ->label('Current Value')
                            ->numeric()
                            ->step(0.01)
                            ->default(0),

                        Forms\Components\Select::make('unit')
                            ->label('Unit')
                            ->options([
                                'IDR' => '💰 Indonesian Rupiah',
                                'count' => '🔢 Count/Number',
                                'percentage' => '📊 Percentage',
                                'hours' => '⏰ Hours',
                                'days' => '📅 Days',
                                'score' => '⭐ Score',
                            ])
                            ->default('IDR')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('priority')
                            ->label('Priority Level')
                            ->options([
                                1 => '🚨 Critical (1)',
                                2 => '🔴 High (2)',
                                3 => '🟠 Medium-High (3)',
                                4 => '🟡 Medium (4)',
                                5 => '🟢 Normal (5)',
                                6 => '🔵 Low (6)',
                            ])
                            ->default(5)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('👥 Assignment & Status')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Select responsible person'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(StrategicGoal::getStatusOptions())
                            ->default('draft')
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Additional notes or comments...'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('✅ Success Criteria')
                    ->description('Define measurable success criteria')
                    ->schema([
                        Forms\Components\Repeater::make('success_criteria')
                            ->label('Success Criteria')
                            ->schema([
                                Forms\Components\TextInput::make('criterion')
                                    ->label('Criterion')
                                    ->required()
                                    ->placeholder('e.g., Revenue growth rate > 20%'),
                                Forms\Components\Toggle::make('achieved')
                                    ->label('Achieved')
                                    ->default(false),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add Criterion')
                            ->collapsible(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('🎯 Goal Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50)
                    ->tooltip(fn (StrategicGoal $record): string => $record->description),

                Tables\Columns\TextColumn::make('category')
                    ->label('📂 Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'financial' => 'success',
                        'operational' => 'info',
                        'quality' => 'warning',
                        'growth' => 'primary',
                        'staff' => 'secondary',
                        'patient_satisfaction' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => 
                        StrategicGoal::getCategoryOptions()[$state] ?? $state
                    ),

                Tables\Columns\TextColumn::make('period')
                    ->label('📅 Period')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => 
                        StrategicGoal::getPeriodOptions()[$state] ?? $state
                    ),

                Tables\Columns\ProgressColumn::make('progress_percentage')
                    ->label('📊 Progress')
                    ->state(fn (StrategicGoal $record): float => $record->progress_percentage)
                    ->color(fn (StrategicGoal $record): string => match (true) {
                        $record->progress_percentage >= 90 => 'success',
                        $record->progress_percentage >= 70 => 'info',
                        $record->progress_percentage >= 50 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('current_value')
                    ->label('💹 Current')
                    ->state(fn (StrategicGoal $record): string => 
                        $record->unit === 'IDR' 
                            ? 'Rp ' . number_format($record->current_value, 0, ',', '.')
                            : number_format($record->current_value, 2) . ' ' . $record->unit
                    )
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('target_value')
                    ->label('🎯 Target')
                    ->state(fn (StrategicGoal $record): string => 
                        $record->unit === 'IDR' 
                            ? 'Rp ' . number_format($record->target_value, 0, ',', '.')
                            : number_format($record->target_value, 2) . ' ' . $record->unit
                    )
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('📍 Status')
                    ->badge()
                    ->color(fn (StrategicGoal $record): string => $record->status_color)
                    ->formatStateUsing(fn (string $state): string => 
                        StrategicGoal::getStatusOptions()[$state] ?? $state
                    ),

                Tables\Columns\TextColumn::make('priority')
                    ->label('⚡ Priority')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 2 => 'danger',
                        $state <= 4 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => '🚨 Critical',
                        2 => '🔴 High',
                        3 => '🟠 Med-High',
                        4 => '🟡 Medium',
                        5 => '🟢 Normal',
                        6 => '🔵 Low',
                        default => (string) $state,
                    }),

                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('⏰ Days Left')
                    ->state(fn (StrategicGoal $record): string => 
                        $record->is_overdue 
                            ? '⚠️ Overdue'
                            : ($record->days_remaining . ' days')
                    )
                    ->color(fn (StrategicGoal $record): string => 
                        $record->is_overdue ? 'danger' : ($record->days_remaining <= 7 ? 'warning' : 'success')
                    ),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('👤 Assigned To')
                    ->placeholder('Unassigned')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(StrategicGoal::getCategoryOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->options(StrategicGoal::getStatusOptions()),

                Tables\Filters\SelectFilter::make('period')
                    ->options(StrategicGoal::getPeriodOptions()),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Goals')
                    ->query(fn (Builder $query): Builder => $query->overdue()),

                Tables\Filters\Filter::make('high_priority')
                    ->label('High Priority')
                    ->query(fn (Builder $query): Builder => $query->highPriority()),

                Tables\Filters\Filter::make('current_period')
                    ->label('Current Period Goals')
                    ->query(function (Builder $query): Builder {
                        $now = Carbon::now();
                        return $query->where('start_date', '<=', $now)
                                   ->where('end_date', '>=', $now);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('👁️')
                    ->tooltip('View Details'),

                Tables\Actions\EditAction::make()
                    ->label('✏️')
                    ->tooltip('Edit Goal'),

                Tables\Actions\Action::make('update_progress')
                    ->label('📊')
                    ->tooltip('Update Progress')
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('new_value')
                            ->label('Current Value')
                            ->required()
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\Textarea::make('progress_notes')
                            ->label('Progress Notes')
                            ->rows(3),
                    ])
                    ->action(function (StrategicGoal $record, array $data) {
                        $record->updateProgress($data['new_value'], $data['progress_notes']);
                        
                        Notification::make()
                            ->title('✅ Progress Updated')
                            ->body('Strategic goal progress has been updated successfully.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (StrategicGoal $record): bool => $record->status === 'active'),

                Tables\Actions\Action::make('complete')
                    ->label('✅')
                    ->tooltip('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (StrategicGoal $record) {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => Carbon::now(),
                            'current_value' => $record->target_value,
                        ]);
                        
                        Notification::make()
                            ->title('🎉 Goal Completed')
                            ->body('Strategic goal has been marked as completed!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (StrategicGoal $record): bool => 
                        $record->status === 'active' && $record->progress_percentage >= 90
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options(StrategicGoal::getStatusOptions())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                            
                            Notification::make()
                                ->title('✅ Bulk Update Complete')
                                ->body('Selected goals have been updated.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'asc')
            ->poll('60s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['createdBy', 'assignedTo']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $overdueCount = StrategicGoal::overdue()->count();
        return $overdueCount > 0 ? (string) $overdueCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrategicGoals::route('/'),
            'create' => Pages\CreateStrategicGoal::route('/create'),
            'edit' => Pages\EditStrategicGoal::route('/{record}/edit'),
        ];
    }
}
