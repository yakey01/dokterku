<?php

namespace App\Filament\Bendahara\Resources;

use App\Filament\Concerns\HasMonthlyArchive;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\ValidationWorkflowService;

class DailyFinancialValidationResource extends Resource
{
    use HasMonthlyArchive;
    
    protected static ?string $model = PendapatanHarian::class; // Default to PendapatanHarian
    
    // Configure monthly archive to use tanggal_input column
    public static function getArchiveDateColumn(): string
    {
        return 'tanggal_input';
    }
    
    // Override to ensure proper date column resolution
    protected static function getArchiveDateColumnName(): string
    {
        return 'tanggal_input';
    }
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationLabel = 'Validasi Transaksi Harian';
    
    protected static ?string $navigationGroup = 'Validasi Transaksi';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'daily-financial-validation';

    // Dynamic model switching based on tab selection
    public static function getModel(): string
    {
        $activeTab = session('daily_financial_validation_tab', request()->get('activeTab', 'pendapatan'));
        return $activeTab === 'pengeluaran' ? PengeluaranHarian::class : PendapatanHarian::class;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Transaksi Harian')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal_input')
                                    ->label('Tanggal Input')
                                    ->disabled(),
                                    
                                Forms\Components\Select::make('shift')
                                    ->label('Shift')
                                    ->options([
                                        'Pagi' => 'Pagi',
                                        'Sore' => 'Sore',
                                    ])
                                    ->disabled(),
                                    
                                // Dynamic field based on model type
                                Forms\Components\TextInput::make('transaction_type')
                                    ->label(fn () => static::getModel() === PendapatanHarian::class ? 'Jenis Pendapatan' : 'Jenis Pengeluaran')
                                    ->formatStateUsing(function ($record) {
                                        if (!$record) return '';
                                        return $record instanceof PendapatanHarian 
                                            ? ($record->pendapatan?->nama_pendapatan ?? '-')
                                            : ($record->pengeluaran?->nama_pengeluaran ?? '-');
                                    })
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('nominal')
                                    ->label('Nominal')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),
                            ]),
                            
                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->disabled()
                            ->columnSpanFull(),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('user.name')
                                    ->label('Input Oleh')
                                    ->disabled(),
                                    
                                Forms\Components\DateTimePicker::make('created_at')
                                    ->label('Waktu Input')
                                    ->disabled(),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Informasi Validasi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status_validasi')
                                    ->label('Status Validasi')
                                    ->options([
                                        'pending' => 'Menunggu Validasi',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        'revision' => 'Perlu Revisi',
                                    ])
                                    ->required()
                                    ->native(false),
                                    
                                Forms\Components\TextInput::make('validasiBy.name')
                                    ->label('Divalidasi Oleh')
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['approved', 'rejected', 'revision'])),
                            ]),
                            
                        Forms\Components\DateTimePicker::make('validasi_at')
                            ->label('Tanggal Validasi')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['approved', 'rejected', 'revision'])),
                            
                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Catatan Validasi')
                            ->placeholder('Tambahkan catatan validasi...')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('status_validasi') !== 'pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_input')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipe_transaksi')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pendapatan' => 'success',
                        'pengeluaran' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pendapatan' => '📈 Pendapatan',
                        'pengeluaran' => '📉 Pengeluaran',
                        default => '-',
                    })
                    ->visible(function () {
                        // Only show Tipe column when displaying combined data (status tabs)
                        $activeTab = request()->get('activeTab', 'pendapatan');
                        return in_array($activeTab, ['approved', 'rejected', 'all_pending']);
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Sore' => 'warning',
                        default => 'gray',
                    }),


                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total'),
                    ])
                    ->alignEnd(),


                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'revision',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                        'heroicon-o-exclamation-triangle' => 'revision',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'revision' => 'Perlu Revisi',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Input Oleh')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validasiBy.name')
                    ->label('Validator')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validasi_at')
                    ->label('Tgl Validasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Quick Status Filters
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'revision' => 'Perlu Revisi',
                    ])
                    ->placeholder('Semua Status')
                    ->default('pending'), // Default to show pending items

                // Shift Filter
                Tables\Filters\SelectFilter::make('shift')
                    ->label('Shift')
                    ->options([
                        'Pagi' => 'Pagi',
                        'Sore' => 'Sore',
                    ])
                    ->placeholder('Semua Shift'),

                // Monthly Archive Filters - defaults to current month
                ...static::getMonthlyArchiveFilters(),

                // Value-based Filters  
                Tables\Filters\Filter::make('high_value')
                    ->label('Nilai Tinggi (>500K)')
                    ->query(fn (Builder $query): Builder => $query->where('nominal', '>', 500000))
                    ->toggle(),

                Tables\Filters\Filter::make('very_high_value')
                    ->label('Nilai Sangat Tinggi (>1M)')
                    ->query(fn (Builder $query): Builder => $query->where('nominal', '>', 1000000))
                    ->toggle(),

            ])
            ->actions([
                ActionGroup::make([
                    // Quick Validation Actions (Pending only)
                    Action::make('quick_approve')
                        ->label('⚡ Quick Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Model $record): bool => $record->status_validasi === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('⚡ Quick Approve')
                        ->modalDescription('Approve this daily transaction without additional comments?')
                        ->modalSubmitActionLabel('Approve')
                        ->action(function (Model $record) {
                            static::quickValidate($record, 'approved');
                        }),

                    Action::make('quick_reject')
                        ->label('⚡ Quick Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Model $record): bool => $record->status_validasi === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->required()
                                ->placeholder('Berikan alasan penolakan...')
                        ])
                        ->action(function (Model $record, array $data) {
                            static::quickValidate($record, 'rejected', $data['rejection_reason']);
                        }),

                    Action::make('approve_with_comment')
                        ->label('✅ Approve with Comment')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->visible(fn (Model $record): bool => $record->status_validasi === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('approval_comment')
                                ->label('Catatan Persetujuan')
                                ->placeholder('Tambahkan catatan persetujuan...')
                        ])
                        ->action(function (Model $record, array $data) {
                            static::quickValidate($record, 'approved', $data['approval_comment'] ?? null);
                        }),

                    Action::make('request_revision')
                        ->label('📝 Request Revision')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn (Model $record): bool => $record->status_validasi === 'pending')
                        ->form([
                            Forms\Components\Textarea::make('revision_notes')
                                ->label('Catatan Revisi')
                                ->required()
                                ->placeholder('Apa yang perlu direvisi?')
                        ])
                        ->action(function (Model $record, array $data) {
                            static::quickValidate($record, 'revision', $data['revision_notes']);
                        }),

                    // Review Actions (Processed items)
                    Action::make('revert_to_pending')
                        ->label('🔄 Revert to Pending')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Model $record): bool => in_array($record->status_validasi, ['approved', 'rejected', 'revision']))
                        ->requiresConfirmation()
                        ->modalHeading('🔄 Revert to Pending Status')
                        ->modalDescription('This will return the transaction to pending status for re-validation.')
                        ->modalSubmitActionLabel('Revert')
                        ->form([
                            Forms\Components\Textarea::make('revert_reason')
                                ->label('Alasan Revert')
                                ->required()
                                ->placeholder('Mengapa ini dikembalikan ke pending?')
                        ])
                        ->action(function (Model $record, array $data) {
                            static::revertToPending($record, $data['revert_reason']);
                        }),

                    // Universal Actions
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ View Details')
                        ->modalWidth('4xl'),

                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->visible(fn (Model $record): bool => Auth::user()->hasRole(['admin', 'bendahara']))
                        ->modalWidth('4xl'),
                ])
                ->label('⚙️ Actions')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk Validation Actions
                    BulkAction::make('bulk_approve')
                        ->label('✅ Bulk Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('✅ Bulk Approve Daily Transactions')
                        ->modalDescription('Are you sure you want to approve all selected transactions?')
                        ->modalSubmitActionLabel('Approve All')
                        ->action(function (Collection $records) {
                            static::bulkValidate($records->where('status_validasi', 'pending'), 'approved');
                        }),

                    BulkAction::make('bulk_reject')
                        ->label('❌ Bulk Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('bulk_rejection_reason')
                                ->label('Alasan Penolakan')
                                ->required()
                                ->placeholder('Berikan alasan penolakan massal...')
                        ])
                        ->action(function (Collection $records, array $data) {
                            static::bulkValidate(
                                $records->where('status_validasi', 'pending'),
                                'rejected',
                                $data['bulk_rejection_reason']
                            );
                        }),

                    // Export Actions
                    BulkAction::make('export_selected')
                        ->label('📤 Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('export_format')
                                ->label('Export Format')
                                ->options([
                                    'xlsx' => 'Excel (.xlsx)',
                                    'csv' => 'CSV (.csv)',
                                    'pdf' => 'PDF (.pdf)'
                                ])
                                ->default('xlsx')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            static::exportRecords($records, $data['export_format']);
                        }),
                ]),
            ])
            ->defaultSort('tanggal_input', 'desc')
            ->poll('30s') // Real-time updates
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'validasiBy'])
            ->when(
                static::getModel() === PendapatanHarian::class,
                fn ($query) => $query->with('pendapatan'),
                fn ($query) => $query->with('pengeluaran')
            );
    }

    // Helper Methods for Actions
    protected static function quickValidate(Model $record, string $status, ?string $comment = null): void
    {
        try {
            $record->update([
                'status_validasi' => $status,
                'validasi_by' => Auth::id(),
                'validasi_at' => now(),
                'catatan_validasi' => $comment ?? ($status === 'approved' ? 'Quick approved' : 'Quick processed'),
            ]);

            // UNIFIED SYNC TO MAIN TABLES: Handle both PendapatanHarian and PengeluaranHarian
            $syncMessage = '';
            if ($status === 'approved') {
                $validationService = app(ValidationWorkflowService::class);
                
                if ($record instanceof PendapatanHarian) {
                    $syncResult = $validationService->syncPendapatanHarianToMainTable($record);
                    $syncMessage = $syncResult 
                        ? ' & synced to main pendapatan table' 
                        : ' (sync to pendapatan table failed - check logs)';
                        
                } elseif ($record instanceof PengeluaranHarian) {
                    $syncResult = $validationService->syncPengeluaranHarianToMainTable($record);
                    $syncMessage = $syncResult 
                        ? ' & synced to main pengeluaran table' 
                        : ' (sync to pengeluaran table failed - check logs)';
                }
            }

            $message = match($status) {
                'approved' => 'Transaksi harian berhasil disetujui' . $syncMessage,
                'rejected' => 'Transaksi harian berhasil ditolak',
                'revision' => 'Permintaan revisi berhasil dikirim',
                default => 'Transaksi harian berhasil diproses'
            };
            
            Notification::make()
                ->title('✅ Success')
                ->body($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error')
                ->body('Validation failed: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function bulkValidate(Collection $records, string $status, ?string $comment = null): void
    {
        try {
            $count = $records->count();
            
            $syncedCount = 0;
            
            foreach ($records as $record) {
                $record->update([
                    'status_validasi' => $status,
                    'validasi_by' => Auth::id(),
                    'validasi_at' => now(),
                    'catatan_validasi' => $comment ?? "Bulk {$status} by " . Auth::user()->name,
                ]);
                
                // UNIFIED BULK SYNC TO MAIN TABLES: Handle both PendapatanHarian and PengeluaranHarian
                if ($status === 'approved') {
                    $validationService = app(ValidationWorkflowService::class);
                    
                    if ($record instanceof PendapatanHarian) {
                        $syncResult = $validationService->syncPendapatanHarianToMainTable($record);
                        if ($syncResult) {
                            $syncedCount++;
                        }
                    } elseif ($record instanceof PengeluaranHarian) {
                        $syncResult = $validationService->syncPengeluaranHarianToMainTable($record);
                        if ($syncResult) {
                            $syncedCount++;
                        }
                    }
                }
            }

            $message = match($status) {
                'approved' => $syncedCount > 0 
                    ? "Berhasil menyetujui {$count} transaksi harian & sync {$syncedCount} ke tabel utama"
                    : "Berhasil menyetujui {$count} transaksi harian",
                'rejected' => "Berhasil menolak {$count} transaksi harian",
                default => "Berhasil memproses {$count} transaksi harian"
            };
            
            Notification::make()
                ->title('✅ Bulk Operation Complete')
                ->body($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Bulk Operation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function revertToPending(Model $record, string $reason): void
    {
        try {
            $record->update([
                'status_validasi' => 'pending',
                'validasi_by' => null,
                'validasi_at' => null,
                'catatan_validasi' => "Reverted by " . Auth::user()->name . ": {$reason}",
            ]);

            Notification::make()
                ->title('🔄 Reverted Successfully')
                ->body('Transaksi harian telah dikembalikan ke status pending')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Revert Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function exportRecords(Collection $records, string $format): void
    {
        // Export functionality placeholder
        Notification::make()
            ->title('📤 Export Initiated')
            ->body("Exporting {$records->count()} records to {$format} format")
            ->info()
            ->send();
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingPendapatan = PendapatanHarian::where('status_validasi', 'pending')->count();
        $pendingPengeluaran = PengeluaranHarian::where('status_validasi', 'pending')->count();
        $total = $pendingPendapatan + $pendingPengeluaran;
        
        return $total > 0 ? (string) $total : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['admin', 'bendahara']);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Bendahara\Resources\DailyFinancialValidationResource\Pages\ListDailyFinancialValidations::route('/'),
        ];
    }
}