<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ValidationCenterResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Validasi Tindakan';
    
    protected static ?string $navigationGroup = 'Validasi Transaksi';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'validation-center';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Validation Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('jenisTindakan.nama')
                                    ->label('Jenis Tindakan')
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('pasien.nama')
                                    ->label('Nama Pasien')
                                    ->disabled(),
                                    
                                Forms\Components\DateTimePicker::make('tanggal_tindakan')
                                    ->label('Tanggal Tindakan')
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('tarif')
                                    ->label('Tarif')
                                    ->prefix('Rp')
                                    ->disabled(),
                            ]),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('jasa_dokter')
                                    ->label('Jasa Dokter')
                                    ->prefix('Rp')
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('jasa_paramedis')
                                    ->label('Jasa Paramedis')
                                    ->prefix('Rp')
                                    ->disabled(),
                                    
                                Forms\Components\TextInput::make('jasa_non_paramedis')
                                    ->label('Jasa Non-Paramedis')
                                    ->prefix('Rp')
                                    ->disabled(),
                            ]),
                            
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Tindakan')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Validation Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status_validasi')
                                    ->label('Status Validasi')
                                    ->options([
                                        'pending' => 'Menunggu Validasi',
                                        'disetujui' => 'Disetujui',
                                        'ditolak' => 'Ditolak',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        // Auto-sync status field when status_validasi changes
                                        $newStatus = match($state) {
                                            'disetujui' => 'selesai',
                                            'ditolak' => 'batal', 
                                            'pending' => 'pending',
                                            default => 'pending'
                                        };
                                        $set('status', $newStatus);
                                    }),
                                    
                                Forms\Components\TextInput::make('validatedBy.name')
                                    ->label('Divalidasi Oleh')
                                    ->disabled()
                                    ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['disetujui', 'ditolak'])),
                            ]),
                            
                        Forms\Components\DateTimePicker::make('validated_at')
                            ->label('Tanggal Validasi')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => in_array($get('status_validasi'), ['disetujui', 'ditolak'])),
                            
                        Forms\Components\Textarea::make('komentar_validasi')
                            ->label('Komentar Validasi')
                            ->placeholder('Tambahkan komentar validasi...')
                            ->columnSpanFull(),
                    ]),

                // Status field synchronized with status_validasi
                Forms\Components\Section::make('Status Tindakan')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Tindakan')
                            ->options([
                                'pending' => '⏳ Menunggu',
                                'selesai' => '✅ Selesai',
                                'batal' => '❌ Batal',
                            ])
                            ->required()
                            ->disabled()
                            ->helperText('Status ini akan berubah otomatis sesuai status validasi')
                            ->dehydrated(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 25 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->limit(20)
                    ->description(fn (Tindakan $record): string => $record->pasien->no_rekam_medis ?? ''),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jaspel_diterima')
                    ->label('Jaspel Diterima')
                    ->money('IDR')
                    ->sortable(false)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'disetujui',
                        'heroicon-o-x-circle' => 'ditolak',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('pelaksana_tindakan')
                    ->label('Pelaksana Tindakan')
                    ->getStateUsing(function (Tindakan $record): string {
                        $pelaksana = [];
                        if ($record->dokter) {
                            $pelaksana[] = 'Dr. ' . $record->dokter->nama_lengkap;
                        }
                        if ($record->paramedis) {
                            $pelaksana[] = $record->paramedis->nama_lengkap . ' (Paramedis)';
                        }
                        if ($record->nonParamedis) {
                            $pelaksana[] = $record->nonParamedis->nama_lengkap . ' (Non-Paramedis)';
                        }
                        return empty($pelaksana) ? '-' : implode(', ', $pelaksana);
                    })
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Petugas Input')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validated_at')
                    ->label('Tgl Validasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validatedBy.name')
                    ->label('Validator')
                    ->searchable()
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
                        'disetujui' => 'Disetujui', 
                        'ditolak' => 'Ditolak',
                    ])
                    ->placeholder('All Status'),

                // Quick Date Range Filters
                Tables\Filters\SelectFilter::make('date_range')
                    ->label('Periode')
                    ->options([
                        'today' => 'Hari Ini',
                        'yesterday' => 'Kemarin',
                        'this_week' => 'Minggu Ini',
                        'last_week' => 'Minggu Lalu',
                        'this_month' => 'Bulan Ini',
                        'last_month' => 'Bulan Lalu',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) return $query;
                        
                        return match ($data['value']) {
                            'today' => $query->whereDate('tanggal_tindakan', today()),
                            'yesterday' => $query->whereDate('tanggal_tindakan', yesterday()),
                            'this_week' => $query->whereBetween('tanggal_tindakan', [
                                now()->startOfWeek(),
                                now()->endOfWeek()
                            ]),
                            'last_week' => $query->whereBetween('tanggal_tindakan', [
                                now()->subWeek()->startOfWeek(),
                                now()->subWeek()->endOfWeek()
                            ]),
                            'this_month' => $query->whereMonth('tanggal_tindakan', now()->month)
                                ->whereYear('tanggal_tindakan', now()->year),
                            'last_month' => $query->whereMonth('tanggal_tindakan', now()->subMonth()->month)
                                ->whereYear('tanggal_tindakan', now()->subMonth()->year),
                            default => $query
                        };
                    }),

                // Value-based Filters
                Tables\Filters\Filter::make('high_value')
                    ->label('High Value (>500K)')
                    ->query(fn (Builder $query): Builder => $query->where('tarif', '>', 500000))
                    ->toggle(),

                Tables\Filters\Filter::make('very_high_value')
                    ->label('Very High Value (>1M)')
                    ->query(fn (Builder $query): Builder => $query->where('tarif', '>', 1000000))
                    ->toggle(),

                // Validator Filter
                Tables\Filters\SelectFilter::make('validated_by')
                    ->label('Validator')
                    ->relationship('validatedBy', 'name')
                    ->searchable()
                    ->preload(),

                // Pelaksana Filter (Dokter)
                Tables\Filters\SelectFilter::make('dokter_id')
                    ->label('Dokter')
                    ->relationship('dokter', 'nama_lengkap')
                    ->searchable()
                    ->preload(),

                // Pelaksana Filter (Paramedis)
                Tables\Filters\SelectFilter::make('paramedis_id')
                    ->label('Paramedis')
                    ->relationship('paramedis', 'nama_lengkap')
                    ->searchable()
                    ->preload(),

                // Custom Date Range Filter
                Tables\Filters\Filter::make('custom_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                // INLINE ACTIONS - No Dropdown, Direct Visibility
                
                // Quick Approve - Green (Pending Only)
                Action::make('approve')
                    ->label('✅')
                    ->tooltip('Quick Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->size('sm')
                    ->button()
                    ->visible(fn (Tindakan $record): bool => $record->status_validasi === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('✅ Quick Approve')
                    ->modalDescription('Approve this medical procedure?')
                    ->modalSubmitActionLabel('Approve')
                    ->action(function (Tindakan $record) {
                        static::quickValidate($record, 'approved');
                    }),

                // Quick Reject - Red (Pending Only)  
                Action::make('reject')
                    ->label('❌')
                    ->tooltip('Quick Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->size('sm')
                    ->button()
                    ->visible(fn (Tindakan $record): bool => $record->status_validasi === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Please provide reason for rejection...')
                            ->rows(3)
                    ])
                    ->action(function (Tindakan $record, array $data) {
                        static::quickValidate($record, 'rejected', $data['rejection_reason']);
                    }),

                // View Details - Blue (All Records)
                Tables\Actions\ViewAction::make()
                    ->label('👁️')
                    ->tooltip('View Details')
                    ->color('info')
                    ->size('sm')
                    ->button()
                    ->modalWidth('4xl'),

                // Edit - Yellow (Admin/Bendahara Only)
                Tables\Actions\EditAction::make()
                    ->label('✏️')
                    ->tooltip('Edit Record')
                    ->color('warning')
                    ->size('sm')
                    ->button()
                    ->visible(fn (Tindakan $record): bool => Auth::user()->hasRole(['admin', 'bendahara']))
                    ->modalWidth('4xl'),

                // Revert - Orange (Approved/Rejected Only)
                Action::make('revert')
                    ->label('🔄')
                    ->tooltip('Revert to Pending')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->size('sm')
                    ->button()
                    ->visible(fn (Tindakan $record): bool => in_array($record->status_validasi, ['disetujui', 'ditolak']))
                    ->form([
                        Forms\Components\Textarea::make('revert_reason')
                            ->label('Revert Reason')
                            ->required()
                            ->placeholder('Why is this being reverted?')
                            ->rows(3)
                    ])
                    ->action(function (Tindakan $record, array $data) {
                        static::revertToPending($record, $data['revert_reason']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk Validation Actions
                    BulkAction::make('bulk_approve')
                        ->label('✅ Bulk Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('✅ Bulk Approve Tindakan')
                        ->modalDescription('Are you sure you want to approve all selected tindakan?')
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
                                ->label('Rejection Reason')
                                ->required()
                                ->placeholder('Provide reason for bulk rejection...')
                        ])
                        ->action(function (Collection $records, array $data) {
                            static::bulkValidate(
                                $records->where('status_validasi', 'pending'),
                                'rejected',
                                $data['bulk_rejection_reason']
                            );
                        }),

                    // Export functionality removed for cleaner interface

                    // Bulk Assignment
                    BulkAction::make('bulk_assign_validator')
                        ->label('👤 Assign Validator')
                        ->icon('heroicon-o-user-plus')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('validator_id')
                                ->label('Assign to Validator')
                                ->options(function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'bendahara');
                                    })->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            static::bulkAssignValidator($records, $data['validator_id']);
                        }),

                    // Bulk Delete
                    BulkAction::make('bulk_delete')
                        ->label('🗑️ Bulk Delete')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('🗑️ Hapus Data Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua data validasi yang dipilih? Data yang telah dihapus tidak dapat dikembalikan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->modalCancelActionLabel('Batal')
                        ->form([
                            Forms\Components\Textarea::make('deletion_reason')
                                ->label('Alasan Penghapusan')
                                ->placeholder('Berikan alasan penghapusan data ini...')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            static::handleBulkDelete($records, $data['deletion_reason']);
                        }),
                ]),
            ])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->poll('30s') // Real-time updates
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('input_by')
            ->with([
                'jenisTindakan',
                'pasien',
                'dokter',
                'paramedis',
                'nonParamedis',
                'inputBy',
                'validatedBy',
                'jaspel'
            ]);
    }

    // Helper Methods for Actions
    protected static function quickValidate(Tindakan $record, string $status, ?string $comment = null): void
    {
        try {
            // Map validation status to consistent format
            $mappedStatus = $status === 'approved' ? 'disetujui' : ($status === 'rejected' ? 'ditolak' : $status);
            
            $record->update([
                'status_validasi' => $mappedStatus,
                'status' => $mappedStatus === 'disetujui' ? 'selesai' : 'batal',
                'validated_by' => Auth::id(),
                'validated_at' => now(),
                'komentar_validasi' => $comment ?? ($mappedStatus === 'disetujui' ? 'Quick approved' : 'Quick rejected'),
            ]);

            $message = $mappedStatus === 'disetujui' 
                ? 'Tindakan berhasil disetujui'
                : 'Tindakan berhasil ditolak';
            
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
            
            \DB::transaction(function() use ($records, $status, $comment) {
                foreach ($records as $record) {
                    // Map validation status to consistent format
                    $mappedStatus = $status === 'approved' ? 'disetujui' : ($status === 'rejected' ? 'ditolak' : $status);
                    
                    $record->update([
                        'status_validasi' => $mappedStatus,
                        'status' => $mappedStatus === 'disetujui' ? 'selesai' : 'batal',
                        'validated_by' => Auth::id(),
                        'validated_at' => now(),
                        'komentar_validasi' => $comment ?? "Bulk {$mappedStatus} by " . Auth::user()->name,
                    ]);

                    // Handle Jaspel synchronization
                    if ($mappedStatus === 'disetujui') {
                        try {
                            $jaspelService = app(\App\Services\JaspelCalculationService::class);
                            $createdJaspel = $jaspelService->calculateJaspelFromTindakan($record);
                            
                            // Update newly created Jaspel records to 'disetujui' status
                            if (is_array($createdJaspel)) {
                                foreach ($createdJaspel as $jaspel) {
                                    if ($jaspel instanceof \App\Models\Jaspel) {
                                        $jaspel->update([
                                            'status_validasi' => 'disetujui',
                                            'validasi_by' => Auth::id(),
                                            'validasi_at' => now(),
                                            'catatan_validasi' => 'Auto-approved with bulk Tindakan validation'
                                        ]);
                                    }
                                }
                            }
                        } catch (\Exception $jaspelError) {
                            \Log::warning('Failed to auto-generate Jaspel for bulk operation: ' . $jaspelError->getMessage());
                        }
                    } else {
                        // If rejected, also reject existing Jaspel records
                        $record->jaspel()->update([
                            'status_validasi' => 'ditolak',
                            'validasi_by' => Auth::id(),
                            'validasi_at' => now(),
                            'catatan_validasi' => 'Rejected due to bulk Tindakan rejection: ' . ($comment ?? 'Bulk rejected')
                        ]);
                    }
                }
            });

            $message = $status === 'approved' 
                ? "Successfully approved {$count} tindakan and synchronized Jaspel records"
                : "Successfully rejected {$count} tindakan and updated related Jaspel records";
            
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

    protected static function revertToPending(Tindakan $record, string $reason): void
    {
        try {
            $record->update([
                'status_validasi' => 'pending',
                'status' => 'pending',
                'validated_by' => null,
                'validated_at' => null,
                'komentar_validasi' => "Reverted by " . Auth::user()->name . ": {$reason}",
            ]);

            Notification::make()
                ->title('🔄 Reverted Successfully')
                ->body('Tindakan has been returned to pending status')
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

    // Export functionality removed for cleaner world-class interface

    protected static function bulkAssignValidator(Collection $records, int $validatorId): void
    {
        try {
            $validator = \App\Models\User::find($validatorId);
            $count = $records->count();
            
            // For now, just add a comment about assignment
            foreach ($records as $record) {
                $currentComment = $record->komentar_validasi ?? '';
                $assignmentNote = "Assigned to {$validator->name} by " . Auth::user()->name . " on " . now()->format('d/m/Y H:i');
                
                $record->update([
                    'komentar_validasi' => $currentComment ? "{$currentComment}\n{$assignmentNote}" : $assignmentNote,
                ]);
            }

            Notification::make()
                ->title('👤 Assignment Complete')
                ->body("Assigned {$count} tindakan to {$validator->name}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Assignment Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function handleDelete(Tindakan $record): void
    {
        try {
            \DB::transaction(function() use ($record) {
                // Check if this is a critical record (already approved with related Jaspel)
                if ($record->status_validasi === 'disetujui' && $record->jaspel()->exists()) {
                    // If approved and has related Jaspel, also soft delete the related Jaspel records
                    $record->jaspel()->delete();
                }
                
                // Log the deletion activity
                \Log::info('Tindakan deleted', [
                    'id' => $record->id,
                    'jenis_tindakan' => $record->jenisTindakan->nama ?? 'Unknown',
                    'pasien' => $record->pasien->nama ?? 'Unknown',
                    'tanggal_tindakan' => $record->tanggal_tindakan,
                    'deleted_by' => Auth::id(),
                    'deleted_by_name' => Auth::user()->name,
                    'deleted_at' => now(),
                ]);
                
                // Soft delete the Tindakan record
                $record->delete();
            });

            Notification::make()
                ->title('✅ Data Berhasil Dihapus')
                ->body('Data validasi tindakan telah berhasil dihapus. Data terkait juga telah diperbarui.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Log::error('Failed to delete Tindakan', [
                'id' => $record->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            Notification::make()
                ->title('❌ Gagal Menghapus Data')
                ->body('Terjadi kesalahan saat menghapus data: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function handleBulkDelete(Collection $records, string $reason): void
    {
        try {
            $count = $records->count();
            $deletedCount = 0;
            $relatedJaspelCount = 0;

            \DB::transaction(function() use ($records, $reason, &$deletedCount, &$relatedJaspelCount) {
                foreach ($records as $record) {
                    // Check if this record has related Jaspel
                    if ($record->status_validasi === 'disetujui' && $record->jaspel()->exists()) {
                        $jaspelCount = $record->jaspel()->count();
                        $record->jaspel()->delete();
                        $relatedJaspelCount += $jaspelCount;
                    }
                    
                    // Log each deletion
                    \Log::info('Bulk Tindakan deletion', [
                        'id' => $record->id,
                        'jenis_tindakan' => $record->jenisTindakan->nama ?? 'Unknown',
                        'pasien' => $record->pasien->nama ?? 'Unknown',
                        'tanggal_tindakan' => $record->tanggal_tindakan,
                        'reason' => $reason,
                        'deleted_by' => Auth::id(),
                        'deleted_by_name' => Auth::user()->name,
                        'deleted_at' => now(),
                    ]);
                    
                    // Soft delete the record
                    $record->delete();
                    $deletedCount++;
                }
            });

            $message = "Berhasil menghapus {$deletedCount} data validasi tindakan";
            if ($relatedJaspelCount > 0) {
                $message .= " dan {$relatedJaspelCount} data Jaspel terkait";
            }

            Notification::make()
                ->title('✅ Bulk Delete Berhasil')
                ->body($message)
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Log::error('Failed to bulk delete Tindakan', [
                'records_count' => $records->count(),
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'reason' => $reason,
            ]);

            Notification::make()
                ->title('❌ Gagal Menghapus Data')
                ->body('Terjadi kesalahan saat menghapus data: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status_validasi', 'pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
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
            'index' => \App\Filament\Bendahara\Resources\ValidationCenterResource\Pages\ListValidations::route('/'),
            'view' => \App\Filament\Bendahara\Resources\ValidationCenterResource\Pages\ViewValidation::route('/{record}'),
            'edit' => \App\Filament\Bendahara\Resources\ValidationCenterResource\Pages\EditValidation::route('/{record}/edit'),
        ];
    }
}