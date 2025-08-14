<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\PendapatanHarianResource\Pages;
use App\Filament\Petugas\Resources\PendapatanHarianResource\RelationManagers;
use App\Models\PendapatanHarian;
use App\Models\Pendapatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\BulkOperationService;
use App\Services\ExportImportService;
use App\Services\ValidationWorkflowService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Exception;

class PendapatanHarianResource extends Resource
{
    protected static ?string $model = PendapatanHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    
    protected static ?string $navigationLabel = 'Input Pendapatan';
    
    protected static ?string $modelLabel = 'Pendapatan Harian';
    
    protected static ?string $navigationGroup = 'Keuangan';
    
    protected static ?int $navigationSort = 2;

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
                
                Forms\Components\Select::make('pendapatan_id')
                    ->label('Nama Pendapatan')
                    ->relationship(
                        name: 'pendapatan',
                        titleAttribute: 'nama_pendapatan',
                        modifyQueryUsing: fn (Builder $query) =>
                            $query->where('is_aktif', true)
                                  ->whereNotNull('nama_pendapatan')
                                  ->where('nama_pendapatan', '!=', '')
                    )
                    ->searchable()
                    ->required()
                    ->preload()
                    ->columnSpanFull()
                    ->helperText('Pilih jenis pendapatan dari data master yang tersedia'),
                
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
            ->heading('Pendapatan Harian Saya')
            ->description('Kelola pendapatan harian Anda dengan mudah dan efisien')
            ->headerActions([
                Tables\Actions\Action::make('summary')
                    ->label('Ringkasan')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->button()
                    ->outlined()
                    ->modalHeading('Ringkasan Pendapatan Harian')
                    ->modalContent(fn () => view('filament.widgets.pendapatan-summary'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pendapatan')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->button()
            ])
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_input')
                    ->label('📅 Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->weight('semibold')
                    ->tooltip('Tanggal input pendapatan'),
                Tables\Columns\TextColumn::make('shift')
                    ->label('⏰ Shift')
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
                        'Pagi' => '🌅 Pagi',
                        'Sore' => '🌅 Sore',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('pendapatan.nama_pendapatan')
                    ->label('💼 Jenis Pendapatan')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->weight('medium')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->pendapatan?->nama_pendapatan)
                    ->wrap(),
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->weight('bold')
                    ->size('lg')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total Pendapatan'),
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
                        'pending' => '⏳ Menunggu',
                        'disetujui' => '✅ Disetujui',
                        'ditolak' => '❌ Ditolak',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('👤 Input Oleh')
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->color('gray')
                    ->weight('medium')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('📝 Deskripsi')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->deskripsi)
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('gray')
                    ->placeholder('Tidak ada deskripsi')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('🕒 Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shift')
                    ->label('⏰ Filter Shift')
                    ->options([
                        'Pagi' => '🌅 Shift Pagi',
                        'Sore' => '🌆 Shift Sore',
                    ])
                    ->placeholder('Semua Shift')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => '⏳ Menunggu Validasi',
                        'disetujui' => '✅ Disetujui',
                        'ditolak' => '❌ Ditolak',
                    ])
                    ->placeholder('Semua Status'),
                Tables\Filters\Filter::make('tanggal_input')
                    ->label('📅 Rentang Tanggal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('dari')
                                    ->label('Dari Tanggal')
                                    ->placeholder('Pilih tanggal mulai')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                                Forms\Components\DatePicker::make('sampai')
                                    ->label('Sampai Tanggal')
                                    ->placeholder('Pilih tanggal akhir')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari'] ?? null) {
                            $indicators[] = '📅 Dari: ' . \Carbon\Carbon::parse($data['dari'])->format('d/m/Y');
                        }
                        if ($data['sampai'] ?? null) {
                            $indicators[] = '📅 Sampai: ' . \Carbon\Carbon::parse($data['sampai'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
                Tables\Filters\Filter::make('nominal')
                    ->label('Rentang Nominal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nominal_min')
                                    ->label('Nominal Minimum')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('nominal_max')
                                    ->label('Nominal Maximum')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('1,000,000'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['nominal_min'], fn (Builder $query, $amount): Builder => $query->where('nominal', '>=', $amount))
                            ->when($data['nominal_max'], fn (Builder $query, $amount): Builder => $query->where('nominal', '<=', $amount));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ Lihat')
                        ->color('info')
                        ->icon('heroicon-o-eye')
                        ->tooltip('Lihat detail pendapatan'),
                    
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->color('warning')
                        ->icon('heroicon-o-pencil-square')
                        ->tooltip('Edit pendapatan')
                        ->visible(fn ($record): bool => $record->status_validasi === 'pending')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('✅ Berhasil!')
                                ->body('Pendapatan harian berhasil diperbarui.')
                                ->duration(3000)
                        ),
                    
                    // Submit for validation
                    Tables\Actions\Action::make('submit_validation')
                        ->label('📤 Ajukan Validasi')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->visible(fn ($record): bool => $record->status_validasi === 'pending' && !$record->submitted_at)
                        ->requiresConfirmation()
                        ->modalHeading('📤 Ajukan Validasi Pendapatan')
                        ->modalDescription('Pastikan semua data sudah benar sebelum mengajukan validasi.')
                        ->modalSubmitActionLabel('Ajukan')
                        ->action(function ($record) {
                            try {
                                $validationService = new ValidationWorkflowService(new \App\Services\TelegramService());
                                $result = $validationService->submitForValidation($record);
                                
                                if ($result['auto_approved']) {
                                    Notification::make()
                                        ->title('✅ Auto-Approved')
                                        ->body('Pendapatan berhasil disetujui otomatis')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('📤 Berhasil Diajukan')
                                        ->body('Pendapatan berhasil diajukan untuk validasi')
                                        ->success()
                                        ->send();
                                }
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Approve action
                    Tables\Actions\Action::make('approve')
                        ->label('✅ Setujui')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record): bool => 
                            $record->status_validasi === 'pending' && 
                            $record->submitted_at !== null &&
                            auth()->user()->hasAnyRole(['supervisor', 'manager', 'admin'])
                        )
                        ->requiresConfirmation()
                        ->modalHeading('✅ Setujui Pendapatan')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui pendapatan ini?')
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
                                    ->title('✅ Berhasil Disetujui')
                                    ->body('Pendapatan berhasil disetujui')
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Reject action
                    Tables\Actions\Action::make('reject')
                        ->label('❌ Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record): bool => 
                            $record->status_validasi === 'pending' && 
                            $record->submitted_at !== null &&
                            auth()->user()->hasAnyRole(['supervisor', 'manager', 'admin'])
                        )
                        ->requiresConfirmation()
                        ->modalHeading('❌ Tolak Pendapatan')
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
                                    ->title('❌ Berhasil Ditolak')
                                    ->body('Pendapatan berhasil ditolak')
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('duplicate')
                        ->label('📋 Duplikat')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('success')
                        ->tooltip('Duplikat data pendapatan')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            $newRecord->tanggal_input = now()->toDateString();
                            $newRecord->user_id = auth()->id();
                            $newRecord->save();
                            
                            Notification::make()
                                ->success()
                                ->title('📋 Data Diduplikat!')
                                ->body('Pendapatan berhasil diduplikat dengan tanggal hari ini.')
                                ->duration(3000)
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('📋 Duplikat Pendapatan')
                        ->modalDescription('Apakah Anda yakin ingin menduplikat data pendapatan ini dengan tanggal hari ini?')
                        ->modalSubmitActionLabel('Ya, Duplikat')
                        ->modalCancelActionLabel('Batal'),
                    
                    Tables\Actions\DeleteAction::make()
                        ->label('🗑️ Hapus')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->tooltip('Hapus pendapatan')
                        ->visible(fn ($record): bool => $record->status_validasi === 'pending')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('🗑️ Terhapus!')
                                ->body('Pendapatan harian berhasil dihapus.')
                                ->duration(3000)
                        )
                        ->modalHeading('🗑️ Hapus Pendapatan')
                        ->modalDescription('Data yang dihapus tidak dapat dikembalikan. Apakah Anda yakin?')
                        ->modalSubmitActionLabel('Ya, Hapus')
                        ->modalCancelActionLabel('Batal'),
                ])
                ->label('⚙️ Aksi')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
                ->tooltip('Menu aksi')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Export selected records
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Terpilih')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Export Data Pendapatan')
                        ->modalDescription('Export data pendapatan yang dipilih ke format file.')
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
                                ->helperText('Sertakan data pendapatan master dan user')
                                ->default(true),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $exportService = new ExportImportService();
                                $ids = $records->pluck('id')->toArray();
                                
                                // Create temporary filtered export
                                $result = $exportService->exportData(
                                    PendapatanHarian::class,
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
                        ->modalHeading('Update Status Pendapatan')
                        ->modalDescription('Update status validasi untuk pendapatan yang dipilih.')
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
                                    PendapatanHarian::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Update Berhasil')
                                    ->body("Berhasil update {$result['updated']} pendapatan.")
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
                        ->modalHeading('Assign Pendapatan ke User')
                        ->modalDescription('Assign pendapatan yang dipilih ke user tertentu.')
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
                                    PendapatanHarian::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Assign Berhasil')
                                    ->body("Berhasil assign {$result['updated']} pendapatan.")
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
                        ->label('✅ Approve Pendapatan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Pendapatan')
                        ->modalDescription('Approve pendapatan yang dipilih untuk validasi.')
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
                                    PendapatanHarian::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Approve Berhasil')
                                    ->body("Berhasil approve {$result['updated']} pendapatan.")
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
                        ->modalHeading('🗑️ Hapus Data Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua data yang dipilih? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->modalCancelActionLabel('Batal')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('🗑️ Data Terhapus!')
                                ->body('Semua data terpilih berhasil dihapus.')
                                ->duration(3000)
                        ),
                ])
                ->label('🔧 Aksi Massal')
                ->color('gray')
                ->button()
            ])
            ->defaultSort('tanggal_input', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->emptyStateHeading('📝 Belum Ada Data Pendapatan')
            ->emptyStateDescription('Mulai tambahkan pendapatan harian Anda dengan klik tombol "Tambah Pendapatan" di atas.')
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('➕ Tambah Pendapatan Pertama')
                    ->color('success')
                    ->button()
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->id()));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['pendapatan', 'user'])
            ->orderBy('tanggal_input', 'desc')
            ->orderBy('created_at', 'desc');
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
            'index' => Pages\ListPendapatanHarians::route('/'),
            'create' => Pages\CreatePendapatanHarian::route('/create'),
            'edit' => Pages\EditPendapatanHarian::route('/{record}/edit'),
        ];
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        $panel = $panel ?? 'petugas';
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }
}
