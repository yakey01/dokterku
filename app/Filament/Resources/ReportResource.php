<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $modelLabel = 'Report';

    protected static ?string $pluralModelLabel = 'Reports';

    protected static ?string $navigationGroup = 'System Administration';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('category')
                                    ->options([
                                        Report::CATEGORY_FINANCIAL => 'Financial',
                                        Report::CATEGORY_OPERATIONAL => 'Operational',
                                        Report::CATEGORY_MEDICAL => 'Medical',
                                        Report::CATEGORY_ADMINISTRATIVE => 'Administrative',
                                        Report::CATEGORY_SECURITY => 'Security',
                                        Report::CATEGORY_PERFORMANCE => 'Performance',
                                        Report::CATEGORY_CUSTOM => 'Custom',
                                    ])
                                    ->required(),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('report_type')
                                    ->options([
                                        Report::TYPE_TABLE => 'Table',
                                        Report::TYPE_CHART => 'Chart',
                                        Report::TYPE_DASHBOARD => 'Dashboard',
                                        Report::TYPE_EXPORT => 'Export',
                                        Report::TYPE_KPI => 'KPI',
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        Report::STATUS_DRAFT => 'Draft',
                                        Report::STATUS_ACTIVE => 'Active',
                                        Report::STATUS_INACTIVE => 'Inactive',
                                        Report::STATUS_ARCHIVED => 'Archived',
                                    ])
                                    ->default(Report::STATUS_DRAFT)
                                    ->required(),
                            ]),
                    ])
                    ->columnSpan(2),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\KeyValue::make('query_config')
                            ->label('Query Configuration')
                            ->helperText('JSON configuration for query parameters'),
                        Forms\Components\KeyValue::make('chart_config')
                            ->label('Chart Configuration')
                            ->helperText('JSON configuration for chart settings'),
                        Forms\Components\KeyValue::make('filters')
                            ->label('Filters')
                            ->helperText('Default filters for the report'),
                    ])
                    ->columnSpan(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_public')
                                    ->label('Public Report')
                                    ->helperText('Allow other users to view this report'),
                                Forms\Components\Toggle::make('is_cached')
                                    ->label('Enable Caching')
                                    ->default(true),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('cache_duration')
                                    ->label('Cache Duration (seconds)')
                                    ->numeric()
                                    ->default(300)
                                    ->minValue(0),
                                Forms\Components\TextInput::make('max_runtime')
                                    ->label('Max Runtime (seconds)')
                                    ->numeric()
                                    ->default(60)
                                    ->minValue(1),
                            ]),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->helperText('Add tags to organize your reports'),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Report::CATEGORY_FINANCIAL => 'success',
                        Report::CATEGORY_OPERATIONAL => 'primary',
                        Report::CATEGORY_MEDICAL => 'info',
                        Report::CATEGORY_ADMINISTRATIVE => 'warning',
                        Report::CATEGORY_SECURITY => 'danger',
                        Report::CATEGORY_PERFORMANCE => 'secondary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('report_type')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Report::TYPE_TABLE => 'Table',
                        Report::TYPE_CHART => 'Chart',
                        Report::TYPE_DASHBOARD => 'Dashboard',
                        Report::TYPE_EXPORT => 'Export',
                        Report::TYPE_KPI => 'KPI',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Report::STATUS_DRAFT => 'gray',
                        Report::STATUS_ACTIVE => 'success',
                        Report::STATUS_INACTIVE => 'warning',
                        Report::STATUS_ARCHIVED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->label('Public'),
                Tables\Columns\TextColumn::make('executions_count')
                    ->counts('executions')
                    ->label('Executions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_generated_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        Report::CATEGORY_FINANCIAL => 'Financial',
                        Report::CATEGORY_OPERATIONAL => 'Operational',
                        Report::CATEGORY_MEDICAL => 'Medical',
                        Report::CATEGORY_ADMINISTRATIVE => 'Administrative',
                        Report::CATEGORY_SECURITY => 'Security',
                        Report::CATEGORY_PERFORMANCE => 'Performance',
                        Report::CATEGORY_CUSTOM => 'Custom',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('report_type')
                    ->options([
                        Report::TYPE_TABLE => 'Table',
                        Report::TYPE_CHART => 'Chart',
                        Report::TYPE_DASHBOARD => 'Dashboard',
                        Report::TYPE_EXPORT => 'Export',
                        Report::TYPE_KPI => 'KPI',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Report::STATUS_DRAFT => 'Draft',
                        Report::STATUS_ACTIVE => 'Active',
                        Report::STATUS_INACTIVE => 'Inactive',
                        Report::STATUS_ARCHIVED => 'Archived',
                    ])
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public Reports'),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Created By')
                    ->multiple(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Action::make('execute')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->tooltip('Execute Report')
                    ->action(function (Report $record) {
                        $reportService = app(ReportService::class);
                        try {
                            $execution = $reportService->executeReport($record, Auth::user());
                            Notification::make()
                                ->title('Report executed successfully')
                                ->body("Execution completed in {$execution->getFormattedExecutionTime()}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Report execution failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('secondary')
                    ->tooltip('Duplicate Report')
                    ->action(function (Report $record) {
                        $newReport = $record->replicate();
                        $newReport->name = $record->name . ' (Copy)';
                        $newReport->user_id = Auth::id();
                        $newReport->is_public = false;
                        $newReport->save();
                        
                        Notification::make()
                            ->title('Report duplicated successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('share')
                    ->icon('heroicon-o-share')
                    ->color('info')
                    ->tooltip('Share Report')
                    ->form([
                        Forms\Components\Select::make('users')
                            ->label('Share with Users')
                            ->options(User::pluck('name', 'id'))
                            ->multiple()
                            ->required(),
                        Forms\Components\Select::make('permissions')
                            ->label('Permissions')
                            ->options([
                                'view' => 'View Only',
                                'execute' => 'Execute',
                                'edit' => 'Edit',
                                'delete' => 'Delete',
                            ])
                            ->multiple()
                            ->default(['view']),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->helperText('Leave empty for no expiration'),
                    ])
                    ->action(function (Report $record, array $data) {
                        foreach ($data['users'] as $userId) {
                            $record->shares()->updateOrCreate(
                                ['user_id' => $userId],
                                [
                                    'shared_by' => Auth::id(),
                                    'permissions' => $data['permissions'],
                                    'expires_at' => $data['expires_at'],
                                ]
                            );
                        }
                        
                        Notification::make()
                            ->title('Report shared successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function (Report $record) {
                                $record->update(['status' => Report::STATUS_ACTIVE]);
                            });
                            
                            Notification::make()
                                ->title('Reports activated successfully')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function (Report $record) {
                                $record->update(['status' => Report::STATUS_INACTIVE]);
                            });
                            
                            Notification::make()
                                ->title('Reports deactivated successfully')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('archive')
                        ->label('Archive')
                        ->icon('heroicon-o-archive-box')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(function (Report $record) {
                                $record->update(['status' => Report::STATUS_ARCHIVED]);
                            });
                            
                            Notification::make()
                                ->title('Reports archived successfully')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Report Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')
                                ->weight(FontWeight::Bold),
                            TextEntry::make('category')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    Report::CATEGORY_FINANCIAL => 'success',
                                    Report::CATEGORY_OPERATIONAL => 'primary',
                                    Report::CATEGORY_MEDICAL => 'info',
                                    Report::CATEGORY_ADMINISTRATIVE => 'warning',
                                    Report::CATEGORY_SECURITY => 'danger',
                                    Report::CATEGORY_PERFORMANCE => 'secondary',
                                    default => 'gray',
                                }),
                        ]),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        Grid::make(3)->schema([
                            TextEntry::make('report_type')
                                ->badge()
                                ->color('primary')
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    Report::TYPE_TABLE => 'Table',
                                    Report::TYPE_CHART => 'Chart',
                                    Report::TYPE_DASHBOARD => 'Dashboard',
                                    Report::TYPE_EXPORT => 'Export',
                                    Report::TYPE_KPI => 'KPI',
                                    default => ucfirst($state),
                                }),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    Report::STATUS_DRAFT => 'gray',
                                    Report::STATUS_ACTIVE => 'success',
                                    Report::STATUS_INACTIVE => 'warning',
                                    Report::STATUS_ARCHIVED => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('user.name')
                                ->label('Created By'),
                        ]),
                    ])
                    ->columnSpan(2),

                Section::make('Configuration')
                    ->schema([
                        TextEntry::make('query_config')
                            ->label('Query Config')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->html(),
                        TextEntry::make('chart_config')
                            ->label('Chart Config')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'None')
                            ->html(),
                        TextEntry::make('filters')
                            ->label('Filters')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'None')
                            ->html(),
                    ])
                    ->columnSpan(2),

                Section::make('Statistics')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('executions_count')
                                ->label('Total Executions')
                                ->numeric(),
                            TextEntry::make('last_generated_at')
                                ->label('Last Generated')
                                ->dateTime(),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('cache_duration')
                                ->label('Cache Duration')
                                ->formatStateUsing(fn ($state) => $state ? "{$state} seconds" : 'Not cached'),
                            TextEntry::make('max_runtime')
                                ->label('Max Runtime')
                                ->formatStateUsing(fn ($state) => $state ? "{$state} seconds" : 'No limit'),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('is_public')
                                ->label('Public')
                                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                            TextEntry::make('is_cached')
                                ->label('Cached')
                                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        ]),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'view' => Pages\ViewReport::route('/{record}'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}