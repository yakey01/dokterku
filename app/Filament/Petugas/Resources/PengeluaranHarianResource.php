<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\PengeluaranHarianResource\Pages;
use App\Models\PengeluaranHarian;
use App\Models\Pengeluaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Services\BulkOperationService;
use App\Services\ExportImportService;
use App\Services\ValidationWorkflowService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Exception;

class PengeluaranHarianResource extends Resource
{
    protected static ?string $model = PengeluaranHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    
    protected static ?string $navigationLabel = 'Input Pengeluaran';
    
    protected static ?string $modelLabel = 'Pengeluaran Harian';
    
    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_input')
                            ->label('Tanggal Input')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),
                            
                        Forms\Components\Select::make('shift')
                            ->label('Shift')
                            ->options([
                                'Pagi' => 'Pagi',
                                'Sore' => 'Sore',
                            ])
                            ->required()
                            ->columnSpan(1),
                    ]),
                
                Forms\Components\Select::make('pengeluaran_id')
                    ->label('Nama Pengeluaran')
                    ->relationship(
                        name: 'pengeluaran',
                        titleAttribute: 'nama_pengeluaran',
                        modifyQueryUsing: fn (Builder $query) =>
                            $query->whereNotNull('nama_pengeluaran')
                                  ->where('nama_pengeluaran', '!=', '')
                    )
                    ->searchable()
                    ->required()
                    ->preload()
                    ->columnSpanFull()
                    ->helperText('Pilih jenis pengeluaran dari data master yang tersedia'),
                
                Forms\Components\TextInput::make('nominal')
                    ->label('Nominal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->placeholder('0')
                    ->columnSpanFull(),
                
                Forms\Components\Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->placeholder('Keterangan tambahan (opsional)')
                    ->maxLength(255)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Pengeluaran Harian Saya')
            ->description('Kelola pengeluaran harian Anda dengan mudah dan efisien')
            ->headerActions([
                Tables\Actions\Action::make('summary')
                    ->label('Ringkasan')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->button()
                    ->outlined()
                    ->modalHeading('Ringkasan Pengeluaran Harian')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                // Remove duplicate CreateAction - it's already in ListPengeluaranHarians page
            ])
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_input')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'success',
                        'Sore' => 'warning',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Pagi' => 'heroicon-o-sun',
                        'Sore' => 'heroicon-o-moon',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Pagi' => 'Pagi',
                        'Sore' => 'Sore',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('pengeluaran.nama_pengeluaran')
                    ->label('Jenis Pengeluaran')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->weight('medium')
                    ->limit(30)
                    ->wrap(),
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->weight('bold')
                    ->size('lg')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total Pengeluaran'),
                    ]),
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status Validasi')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'disetujui' => 'heroicon-o-check-circle',
                        'ditolak' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(40)
                    ->placeholder('Tidak ada deskripsi')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shift')
                    ->label('Filter Shift')
                    ->options([
                        'Pagi' => 'Shift Pagi',
                        'Sore' => 'Shift Sore',
                    ])
                    ->placeholder('Semua Shift')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ])
                    ->placeholder('Semua Status'),
                Tables\Filters\Filter::make('tanggal_input')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('dari')
                                    ->label('Dari Tanggal'),
                                Forms\Components\DatePicker::make('sampai')
                                    ->label('Sampai Tanggal'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat')
                        ->color('info')
                        ->icon('heroicon-o-eye'),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Edit')
                        ->color('warning')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn ($record): bool => $record->status_validasi === 'pending')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Berhasil!')
                                ->body('Pengeluaran harian berhasil diperbarui.')
                        ),
                    
                    // Submit for validation
                    Tables\Actions\Action::make('submit_validation')
                        ->label('Ajukan Validasi')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->visible(fn ($record): bool => $record->status_validasi === 'pending' && !$record->submitted_at)
                        ->requiresConfirmation()
                        ->modalHeading('Ajukan Validasi Pengeluaran')
                        ->modalDescription('Pastikan semua data sudah benar sebelum mengajukan validasi.')
                        ->modalSubmitActionLabel('Ajukan')
                        ->action(function ($record) {
                            try {
                                $validationService = new ValidationWorkflowService(new \App\Services\TelegramService());
                                $result = $validationService->submitForValidation($record);
                                
                                if ($result['auto_approved']) {
                                    Notification::make()
                                        ->title('Auto-Approved')
                                        ->body('Pengeluaran berhasil disetujui otomatis')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Berhasil Diajukan')
                                        ->body('Pengeluaran berhasil diajukan untuk validasi')
                                        ->success()
                                        ->send();
                                }
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Approve action
                    Tables\Actions\Action::make('approve')
                        ->label('Setujui')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record): bool => 
                            $record->status_validasi === 'pending' && 
                            $record->submitted_at !== null &&
                            auth()->user()->hasAnyRole(['supervisor', 'manager', 'admin'])
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Pengeluaran')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui pengeluaran ini?')
                        ->modalSubmitActionLabel('Setujui')
                        ->form([
                            Textarea::make('approval_reason')
                                ->label('Alasan Persetujuan (Opsional)')
                                ->placeholder('Masukkan alasan persetujuan...')
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                $validationService = new ValidationWorkflowService(new \App\Services\TelegramService());
                                $result = $validationService->approve($record, [
                                    'reason' => $data['approval_reason'] ?? 'Approved by ' . auth()->user()->name
                                ]);
                                
                                Notification::make()
                                    ->title('Berhasil Disetujui')
                                    ->body('Pengeluaran berhasil disetujui')
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Reject action
                    Tables\Actions\Action::make('reject')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record): bool => 
                            $record->status_validasi === 'pending' && 
                            $record->submitted_at !== null &&
                            auth()->user()->hasAnyRole(['supervisor', 'manager', 'admin'])
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Pengeluaran')
                        ->modalDescription('Berikan alasan penolakan yang jelas.')
                        ->modalSubmitActionLabel('Tolak')
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->placeholder('Masukkan alasan penolakan...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                $validationService = new ValidationWorkflowService(new \App\Services\TelegramService());
                                $result = $validationService->reject($record, $data['rejection_reason']);
                                
                                Notification::make()
                                    ->title('Berhasil Ditolak')
                                    ->body('Pengeluaran berhasil ditolak')
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\DeleteAction::make()
                        ->label('🗑️ Hapus')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->visible(fn ($record): bool => $record->status_validasi === 'pending')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('🗑️ Terhapus!')
                                ->body('Pengeluaran harian berhasil dihapus.')
                        ),
                ])
                ->label('⚙️ Aksi')
                ->icon('heroicon-o-ellipsis-vertical')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Export selected records
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Terpilih')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Export Data Pengeluaran')
                        ->modalDescription('Export data pengeluaran yang dipilih ke format file.')
                        ->modalSubmitActionLabel('Export')
                        ->form([
                            Select::make('format')
                                ->label('Format File')
                                ->options([
                                    'xlsx' => 'Excel (.xlsx)',
                                    'csv' => 'CSV (.csv)',
                                    'json' => 'JSON (.json)',
                                ])
                                ->default('xlsx')
                                ->required(),
                            Toggle::make('include_relations')
                                ->label('Sertakan Data Terkait')
                                ->helperText('Sertakan data pengeluaran master dan user')
                                ->default(true),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $exportService = new ExportImportService();
                                $ids = $records->pluck('id')->toArray();
                                
                                // Create temporary filtered export
                                $result = $exportService->exportData(
                                    PengeluaranHarian::class,
                                    [
                                        'format' => $data['format'],
                                        'include_relations' => $data['include_relations'],
                                        'filters' => ['id' => $ids]
                                    ]
                                );
                                
                                // Trigger download
                                return response()->download(
                                    storage_path('app/' . $result['file_path']),
                                    $result['file_name']
                                );
                                
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Export Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    // Bulk update status
                    Tables\Actions\BulkAction::make('bulk_update_status')
                        ->label('🔄 Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Update Status Pengeluaran')
                        ->modalDescription('Update status validasi untuk pengeluaran yang dipilih.')
                        ->modalSubmitActionLabel('Update')
                        ->form([
                            Select::make('status_validasi')
                                ->label('Status Validasi')
                                ->options([
                                    'pending' => 'Menunggu Validasi',
                                    'disetujui' => 'Disetujui',
                                    'ditolak' => 'Ditolak',
                                ])
                                ->required(),
                            Select::make('shift')
                                ->label('Shift')
                                ->options([
                                    'Pagi' => 'Pagi',
                                    'Sore' => 'Sore',
                                ])
                                ->nullable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $updateData = array_filter($data);
                                if (empty($updateData)) {
                                    Notification::make()
                                        ->title('⚠️ Tidak Ada Data')
                                        ->body('Pilih minimal satu field untuk diupdate.')
                                        ->warning()
                                        ->send();
                                    return;
                                }
                                
                                $bulkService = new BulkOperationService();
                                $updates = $records->map(function ($record) use ($updateData) {
                                    return array_merge(['id' => $record->id], $updateData);
                                })->toArray();
                                
                                $result = $bulkService->bulkUpdate(
                                    PengeluaranHarian::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Update Berhasil')
                                    ->body("Berhasil update {$result['updated']} pengeluaran.")
                                    ->success()
                                    ->send();
                                    
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Update Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Bulk assign to user
                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('👤 Assign ke User')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Assign Pengeluaran ke User')
                        ->modalDescription('Assign pengeluaran yang dipilih ke user tertentu.')
                        ->modalSubmitActionLabel('Assign')
                        ->form([
                            Select::make('user_id')
                                ->label('User')
                                ->options(function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'petugas');
                                    })->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $bulkService = new BulkOperationService();
                                $updates = $records->map(function ($record) use ($data) {
                                    return [
                                        'id' => $record->id,
                                        'user_id' => $data['user_id']
                                    ];
                                })->toArray();
                                
                                $result = $bulkService->bulkUpdate(
                                    PengeluaranHarian::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Assign Berhasil')
                                    ->body("Berhasil assign {$result['updated']} pengeluaran.")
                                    ->success()
                                    ->send();
                                    
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Assign Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Bulk approve
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('✅ Approve Pengeluaran')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Pengeluaran')
                        ->modalDescription('Approve pengeluaran yang dipilih untuk validasi.')
                        ->modalSubmitActionLabel('Approve')
                        ->visible(fn (): bool => auth()->user()->can('validate_transactions'))
                        ->action(function (Collection $records) {
                            try {
                                $bulkService = new BulkOperationService();
                                $updates = $records->map(function ($record) {
                                    return [
                                        'id' => $record->id,
                                        'status_validasi' => 'disetujui'
                                    ];
                                })->toArray();
                                
                                $result = $bulkService->bulkUpdate(
                                    PengeluaranHarian::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Approve Berhasil')
                                    ->body("Berhasil approve {$result['updated']} pengeluaran.")
                                    ->success()
                                    ->send();
                                    
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Approve Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('🗑️ Hapus Terpilih')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('🗑️ Data Terhapus!')
                                ->body('Semua data terpilih berhasil dihapus.')
                        ),
                ])
                ->label('🔧 Aksi Massal')
                ->color('gray')
                ->button()
            ])
            ->defaultSort('tanggal_input', 'desc')
            ->striped()
            ->poll('30s')
            ->emptyStateHeading('📝 Belum Ada Data Pengeluaran')
            ->emptyStateDescription('Mulai tambahkan pengeluaran harian Anda.')
            ->emptyStateIcon('heroicon-o-arrow-trending-down')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->id()));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['pengeluaran', 'user'])
            ->orderBy('tanggal_input', 'desc')
            ->orderBy('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengeluaranHarians::route('/'),
            'create' => Pages\CreatePengeluaranHarian::route('/create'),
            'edit' => Pages\EditPengeluaranHarian::route('/{record}/edit'),
        ];
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        $panel = $panel ?? 'petugas';
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }
}