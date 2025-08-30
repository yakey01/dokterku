<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BulkOperationResource\Pages;
use App\Models\BulkOperation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ProgressColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BulkOperationResource extends Resource
{
    protected static ?string $model = BulkOperation::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    
    protected static ?string $navigationLabel = 'Operation History';
    
    protected static ?string $modelLabel = 'Bulk Operation';
    
    protected static ?string $pluralModelLabel = 'Bulk Operations';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?int $navigationSort = 9;
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['super-admin', 'admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')
                    ->label('User')
                    ->disabled(),
                Forms\Components\TextInput::make('operation_type')
                    ->label('Operation Type')
                    ->disabled(),
                Forms\Components\TextInput::make('model_type')
                    ->label('Model Type')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->label('Status')
                    ->disabled(),
                Forms\Components\TextInput::make('total_records')
                    ->label('Total Records')
                    ->disabled(),
                Forms\Components\TextInput::make('processed_records')
                    ->label('Processed Records')
                    ->disabled(),
                Forms\Components\TextInput::make('successful_records')
                    ->label('Successful Records')
                    ->disabled(),
                Forms\Components\TextInput::make('failed_records')
                    ->label('Failed Records')
                    ->disabled(),
                Forms\Components\KeyValue::make('operation_data')
                    ->label('Operation Data')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('filters')
                    ->label('Filters')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('error_details')
                    ->label('Error Details')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('started_at')
                    ->label('Started At')
                    ->disabled(),
                Forms\Components\TextInput::make('completed_at')
                    ->label('Completed At')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('operation_type')
                    ->label('Operation')
                    ->badge()
                    ->color(fn ($record) => match($record->operation_type) {
                        'update' => 'warning',
                        'delete' => 'danger',
                        'export' => 'info',
                        'import' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($record) => $record->getTypeIcon())
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('model_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->getStatusColor())
                    ->icon(fn ($record) => $record->getStatusIcon())
                    ->sortable(),
                    
                TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable(),
                    
                TextColumn::make('total_records')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                    
                TextColumn::make('processed_records')
                    ->label('Processed')
                    ->numeric()
                    ->sortable(),
                    
                TextColumn::make('successful_records')
                    ->label('Success')
                    ->numeric()
                    ->color('success')
                    ->sortable(),
                    
                TextColumn::make('failed_records')
                    ->label('Failed')
                    ->numeric()
                    ->color('danger')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
                    
                TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(fn ($record) => $record->getDuration())
                    ->placeholder('N/A'),
            ])
            ->filters([
                SelectFilter::make('operation_type')
                    ->options([
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'export' => 'Export',
                        'import' => 'Import',
                        'sync' => 'Sync',
                        'backup' => 'Backup',
                        'restore' => 'Restore',
                        'cleanup' => 'Cleanup',
                    ]),
                    
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        'paused' => 'Paused',
                    ]),
                    
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                    
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From Date'),
                        DatePicker::make('created_until')
                            ->label('To Date'),
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
                    
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->active())
                    ->label('Active Operations'),
                    
                Tables\Filters\Filter::make('failed')
                    ->query(fn (Builder $query): Builder => $query->failed())
                    ->label('Failed Operations'),
                    
                Tables\Filters\Filter::make('recent')
                    ->query(fn (Builder $query): Builder => $query->recent(24))
                    ->label('Last 24 Hours'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->canCancel())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $bulkService = app(\App\Services\BulkOperationService::class);
                        try {
                            $bulkService->cancelOperation($record);
                            Notification::make()
                                ->title('Operation cancelled successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to cancel operation')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn ($record) => $record->operation_type === 'export' && $record->isCompleted())
                    ->action(function ($record) {
                        $filePath = $record->operation_data['export_file'] ?? null;
                        
                        if (!$filePath || !Storage::exists($filePath)) {
                            Notification::make()
                                ->title('Export file not found')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        return Storage::download($filePath);
                    }),
                    
                Action::make('view_errors')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn ($record) => $record->failed_records > 0)
                    ->modalHeading('Operation Errors')
                    ->modalContent(function ($record) {
                        return view('filament.modals.bulk-operation-errors', ['record' => $record]);
                    })
                    ->modalWidth('4xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('15s')
            ->deferLoading();
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
            'index' => Pages\ListBulkOperations::route('/'),
            'view' => Pages\ViewBulkOperation::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $activeCount = static::getEloquentQuery()->active()->count();
        return $activeCount > 0 ? (string) $activeCount : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'warning' : null;
    }
}