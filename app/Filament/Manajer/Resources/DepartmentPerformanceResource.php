<?php

namespace App\Filament\Manajer\Resources;

use App\Filament\Manajer\Resources\DepartmentPerformanceResource\Pages;
use App\Models\DepartmentPerformanceMetric;
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

class DepartmentPerformanceResource extends Resource
{
    protected static ?string $model = DepartmentPerformanceMetric::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static ?string $navigationLabel = 'Department Performance';
    
    protected static ?string $navigationGroup = '📊 Performance Analytics';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📊 Performance Metric Details')
                    ->description('Track department performance indicators')
                    ->schema([
                        Forms\Components\Select::make('department')
                            ->label('Department')
                            ->options(DepartmentPerformanceMetric::getDepartmentOptions())
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('metric_name')
                            ->label('Metric Name')
                            ->required()
                            ->placeholder('e.g., Patient Satisfaction Score')
                            ->helperText('Descriptive name for this performance indicator'),

                        Forms\Components\TextInput::make('metric_value')
                            ->label('Metric Value')
                            ->required()
                            ->numeric()
                            ->step(0.01),

                        Forms\Components\Select::make('metric_unit')
                            ->label('Unit')
                            ->options([
                                'IDR' => '💰 Indonesian Rupiah',
                                'count' => '🔢 Count/Number',
                                'percentage' => '📊 Percentage',
                                'hours' => '⏰ Hours',
                                'minutes' => '⏱️ Minutes',
                                'score' => '⭐ Score (1-100)',
                                'rating' => '🌟 Rating (1-5)',
                                'ratio' => '📈 Ratio',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('📅 Measurement Period')
                    ->schema([
                        Forms\Components\DatePicker::make('measurement_date')
                            ->label('Measurement Date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('period_type')
                            ->label('Period Type')
                            ->options(DepartmentPerformanceMetric::getPeriodTypeOptions())
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('🎯 Targets & Benchmarks')
                    ->schema([
                        Forms\Components\TextInput::make('target_value')
                            ->label('Target Value')
                            ->numeric()
                            ->step(0.01)
                            ->helperText('Expected performance target'),

                        Forms\Components\TextInput::make('benchmark_value')
                            ->label('Benchmark Value')
                            ->numeric()
                            ->step(0.01)
                            ->helperText('Industry or historical benchmark'),

                        Forms\Components\Select::make('trend')
                            ->label('Performance Trend')
                            ->options(DepartmentPerformanceMetric::getTrendOptions())
                            ->native(false),

                        Forms\Components\TextInput::make('score')
                            ->label('Performance Score')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Overall performance score (1-100)'),

                        Forms\Components\Toggle::make('is_kpi')
                            ->label('Key Performance Indicator')
                            ->helperText('Mark as KPI for executive dashboard')
                            ->default(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('📝 Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Additional context or observations...'),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Additional Data')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel('Add Metadata')
                            ->reorderable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department')
                    ->label('🏢 Department')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'medical' => 'success',
                        'administrative' => 'info',
                        'financial' => 'warning',
                        'support' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => 
                        DepartmentPerformanceMetric::getDepartmentOptions()[$state] ?? $state
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('metric_name')
                    ->label('📊 Metric')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                Tables\Columns\TextColumn::make('formatted_value')
                    ->label('💹 Value')
                    ->state(fn (DepartmentPerformanceMetric $record): string => $record->formatted_value)
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('performance_percentage')
                    ->label('🎯 vs Target')
                    ->state(fn (DepartmentPerformanceMetric $record): string => 
                        $record->target_value 
                            ? number_format($record->performance_percentage, 1) . '%'
                            : 'No Target'
                    )
                    ->color(fn (DepartmentPerformanceMetric $record): string => match (true) {
                        !$record->target_value => 'gray',
                        $record->performance_percentage >= 100 => 'success',
                        $record->performance_percentage >= 80 => 'info',
                        $record->performance_percentage >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('trend')
                    ->label('📈 Trend')
                    ->badge()
                    ->color(fn (DepartmentPerformanceMetric $record): string => $record->trend_color)
                    ->formatStateUsing(fn (string $state): string => 
                        DepartmentPerformanceMetric::getTrendOptions()[$state] ?? $state
                    ),

                Tables\Columns\IconColumn::make('is_kpi')
                    ->label('🎯 KPI')
                    ->boolean()
                    ->tooltip('Key Performance Indicator')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('score')
                    ->label('⭐ Score')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        !$state => 'gray',
                        $state >= 80 => 'success',
                        $state >= 60 => 'info',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->placeholder('No Score'),

                Tables\Columns\TextColumn::make('measurement_date')
                    ->label('📅 Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_type')
                    ->label('📆 Period')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => 
                        DepartmentPerformanceMetric::getPeriodTypeOptions()[$state] ?? $state
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->options(DepartmentPerformanceMetric::getDepartmentOptions()),

                Tables\Filters\SelectFilter::make('period_type')
                    ->options(DepartmentPerformanceMetric::getPeriodTypeOptions()),

                Tables\Filters\SelectFilter::make('trend')
                    ->options(DepartmentPerformanceMetric::getTrendOptions()),

                Tables\Filters\Filter::make('kpi_only')
                    ->label('KPI Only')
                    ->query(fn (Builder $query): Builder => $query->kpiOnly()),

                Tables\Filters\Filter::make('current_month')
                    ->label('Current Month')
                    ->query(fn (Builder $query): Builder => $query->currentMonth()),

                Tables\Filters\Filter::make('high_performance')
                    ->label('High Performance (Score ≥ 80)')
                    ->query(fn (Builder $query): Builder => $query->where('score', '>=', 80)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('👁️')
                    ->tooltip('View Details'),

                Tables\Actions\EditAction::make()
                    ->label('✏️')
                    ->tooltip('Edit Metric'),

                Tables\Actions\Action::make('set_as_kpi')
                    ->label('⭐')
                    ->tooltip('Mark as KPI')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function (DepartmentPerformanceMetric $record) {
                        $record->update(['is_kpi' => true]);
                        
                        Notification::make()
                            ->title('⭐ KPI Updated')
                            ->body('Metric has been marked as Key Performance Indicator.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (DepartmentPerformanceMetric $record): bool => !$record->is_kpi),

                Tables\Actions\Action::make('calculate_trend')
                    ->label('📈')
                    ->tooltip('Calculate Trend')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->action(function (DepartmentPerformanceMetric $record) {
                        $trend = DepartmentPerformanceMetric::getDepartmentTrend($record->department);
                        $record->update(['trend' => $trend]);
                        
                        Notification::make()
                            ->title('📈 Trend Calculated')
                            ->body("Department trend updated to: {$trend}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_kpi')
                        ->label('Mark as KPI')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_kpi' => true]);
                            }
                            
                            Notification::make()
                                ->title('⭐ KPIs Updated')
                                ->body('Selected metrics marked as KPIs.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('measurement_date', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['recordedBy']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $lowPerformingCount = DepartmentPerformanceMetric::where('score', '<', 60)
            ->kpiOnly()
            ->currentMonth()
            ->count();
            
        return $lowPerformingCount > 0 ? (string) $lowPerformingCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartmentPerformances::route('/'),
            'create' => Pages\CreateDepartmentPerformance::route('/create'),
            'edit' => Pages\EditDepartmentPerformance::route('/{record}/edit'),
        ];
    }
}