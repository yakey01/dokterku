<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\TindakanResource\Pages;
use App\Models\JenisTindakan;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\ShiftTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Services\BulkOperationService;
use App\Services\ExportImportService;
use App\Services\ValidationWorkflowService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Exception;

class TindakanResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationLabel = 'Input Tindakan';
    
    protected static ?string $navigationGroup = '🏥 Manajemen Pasien';

    protected static ?string $modelLabel = 'Tindakan';

    protected static ?string $pluralModelLabel = 'Input Tindakan';

    protected static ?int $navigationSort = 1;

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
                Forms\Components\Section::make('Input Tindakan Medis')
                    ->schema([
                        Forms\Components\Select::make('jenis_tindakan_id')
                            ->label('Jenis Tindakan')
                            ->required()
                            ->relationship('jenisTindakan', 'nama', fn (Builder $query) => $query->where('is_active', true)->orderBy('nama'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih jenis tindakan')
                            ->getSearchResultsUsing(function (string $search): array {
                                return JenisTindakan::where('nama', 'like', "%{$search}%")
                                    ->where('is_active', true)
                                    ->orderBy('nama')
                                    ->limit(50)
                                    ->pluck('nama', 'id')
                                    ->toArray();
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $jenisTindakan = JenisTindakan::find($state);
                                    if ($jenisTindakan) {
                                        $tarif = $jenisTindakan->tarif;

                                        // Get JASPEL percentage from config or use default
                                        $persentaseJaspel = config('app.default_jaspel_percentage', 40);

                                        // Calculate JASPEL Petugas (same as admin calculation)
                                        $jasaPetugas = $tarif * ($persentaseJaspel / 100);

                                        $set('tarif', $tarif);
                                        $set('calculated_jaspel', $jasaPetugas); // Store calculated JASPEL

                                        // Reset all jasa fields initially
                                        $set('jasa_dokter', 0);
                                        $set('jasa_paramedis', 0);
                                        $set('jasa_non_paramedis', $jenisTindakan->jasa_non_paramedis);

                                        // Set hidden field to show the percentage used
                                        $set('persentase_jaspel_info', $persentaseJaspel);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('pasien_id')
                            ->label('Pasien')
                            ->required()
                            ->relationship('pasien', 'nama')
                            ->searchable()
                            ->preload(false)
                            ->placeholder('Pilih pasien')
                            ->getSearchResultsUsing(function (string $search): array {
                                return Pasien::where('nama', 'like', "%{$search}%")
                                    ->orWhere('no_rekam_medis', 'like', "%{$search}%")
                                    ->orderBy('nama')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Pasien $pasien) => [$pasien->id => "{$pasien->no_rekam_medis} - {$pasien->nama}"])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $pasien = Pasien::find($value);
                                return $pasien ? "{$pasien->no_rekam_medis} - {$pasien->nama}" : null;
                            }),

                        Forms\Components\DateTimePicker::make('tanggal_tindakan')
                            ->label('Tanggal Tindakan')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\Select::make('shift_id')
                            ->label('Shift')
                            ->options(function () {
                                return ShiftTemplate::query()
                                    ->orderBy('nama_shift')
                                    ->pluck('nama_shift', 'id');
                            })
                            ->required()
                            ->native(false)
                            ->preload()
                            ->placeholder('Pilih shift')
                            ->helperText('Data shift dikelola di Admin → Template Shift'),

                        Forms\Components\Select::make('dokter_id')
                            ->label('Dokter Pelaksana')
                            ->relationship('dokter', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih dokter (opsional)')
                            ->getSearchResultsUsing(function (string $search): array {
                                return \App\Models\Dokter::where('nama_lengkap', 'like', "%{$search}%")
                                    ->where('aktif', true)
                                    ->orderBy('nama_lengkap')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($dokter) => [
                                        $dokter->id => $dokter->nama_lengkap.
                                        ($dokter->spesialisasi ? ' ('.$dokter->spesialisasi.')' : ''),
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $dokter = \App\Models\Dokter::find($value);
                                return $dokter ? $dokter->nama_lengkap . ($dokter->spesialisasi ? ' ('.$dokter->spesialisasi.')' : '') : null;
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $calculatedJaspel = $get('calculated_jaspel') ?? 0;

                                if ($state) {
                                    // Doctor selected, give JASPEL to doctor
                                    $set('jasa_dokter', $calculatedJaspel);
                                    $set('jasa_paramedis', 0); // Remove from paramedic
                                } else {
                                    // No doctor selected, remove JASPEL from doctor
                                    $set('jasa_dokter', 0);

                                    // Check if paramedic is selected to give them JASPEL
                                    if ($get('paramedis_id')) {
                                        $set('jasa_paramedis', $calculatedJaspel);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('paramedis_id')
                            ->label('Paramedis Pelaksana')
                            ->relationship('paramedis', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih paramedis (opsional)')
                            ->getSearchResultsUsing(function (string $search): array {
                                return \App\Models\Pegawai::where('nama_lengkap', 'like', "%{$search}%")
                                    ->where('jenis_pegawai', 'Paramedis')
                                    ->where('aktif', true)
                                    ->orderBy('nama_lengkap')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($pegawai) => [
                                        $pegawai->id => $pegawai->nama_lengkap.
                                        ($pegawai->jabatan ? ' ('.$pegawai->jabatan.')' : ''),
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $pegawai = \App\Models\Pegawai::find($value);
                                return $pegawai ? $pegawai->nama_lengkap . ($pegawai->jabatan ? ' ('.$pegawai->jabatan.')' : '') : null;
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $calculatedJaspel = $get('calculated_jaspel') ?? 0;

                                if ($state && ! $get('dokter_id')) {
                                    // Paramedic selected and no doctor selected, give JASPEL to paramedic
                                    $set('jasa_paramedis', $calculatedJaspel);
                                } elseif (! $state) {
                                    // No paramedic selected, remove JASPEL from paramedic
                                    $set('jasa_paramedis', 0);
                                } elseif ($get('dokter_id')) {
                                    // Doctor has priority, remove JASPEL from paramedic
                                    $set('jasa_paramedis', 0);
                                }
                            }),

                        Forms\Components\Select::make('non_paramedis_id')
                            ->label('Non-Paramedis Pelaksana')
                            ->relationship('nonParamedis', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih non-paramedis (opsional)')
                            ->getSearchResultsUsing(function (string $search): array {
                                return \App\Models\Pegawai::where('nama_lengkap', 'like', "%{$search}%")
                                    ->where('jenis_pegawai', 'Non-Paramedis')
                                    ->where('aktif', true)
                                    ->orderBy('nama_lengkap')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($pegawai) => [
                                        $pegawai->id => $pegawai->nama_lengkap.
                                        ($pegawai->jabatan ? ' ('.$pegawai->jabatan.')' : ''),
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $pegawai = \App\Models\Pegawai::find($value);
                                return $pegawai ? $pegawai->nama_lengkap . ($pegawai->jabatan ? ' ('.$pegawai->jabatan.')' : '') : null;
                            }),

                        Forms\Components\TextInput::make('tarif')
                            ->label('Tarif (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('100000')
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Hidden::make('persentase_jaspel_info')
                            ->default(config('app.default_jaspel_percentage', 40)),

                        Forms\Components\Hidden::make('calculated_jaspel')
                            ->default(0),

                        Forms\Components\TextInput::make('jasa_dokter')
                            ->label('Jasa Dokter (Rp)')
                            ->helperText('JASPEL diberikan kepada dokter pelaksana (jika dipilih)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('jasa_paramedis')
                            ->label('Jasa Paramedis (Rp)')
                            ->helperText('JASPEL diberikan kepada paramedis pelaksana (jika tidak ada dokter)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('jasa_non_paramedis')
                            ->label('Jasa Non-Paramedis (Rp)')
                            ->helperText('Jasa untuk non-paramedis yang terlibat')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->maxLength(500)
                            ->placeholder('Catatan tindakan (opsional)')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status Tindakan')
                            ->options([
                                'pending' => 'Pending',
                                'selesai' => 'Selesai',
                                'batal' => 'Batal',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Hidden::make('input_by')
                            ->default(fn () => Auth::id()),

                        Forms\Components\Hidden::make('status_validasi')
                            ->default('pending'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->limit(30)
                    ->description(fn (Tindakan $record): string => $record->pasien->no_rekam_medis ?? ''),

                Tables\Columns\TextColumn::make('dokter.nama_lengkap')
                    ->label('Dokter')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('paramedis.nama_lengkap')
                    ->label('Paramedis')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shift.nama_shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Siang' => 'warning',
                        'Sore' => 'warning',
                        'Malam' => 'primary',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'selesai' => 'success',
                        'batal' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal_tindakan')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ]),

                Tables\Filters\SelectFilter::make('jenis_tindakan_id')
                    ->label('Jenis Tindakan')
                    ->options(JenisTindakan::where('is_active', true)->orderBy('nama')->pluck('nama', 'id')),

                Tables\Filters\SelectFilter::make('dokter_id')
                    ->label('Dokter')
                    ->options(\App\Models\Dokter::where('aktif', true)->orderBy('nama_lengkap')->pluck('nama_lengkap', 'id')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ Lihat')
                        ->color('info'),
                    
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->color('warning')
                        ->visible(fn (Tindakan $record): bool => $record->status === 'pending' && $record->status_validasi !== 'approved'),
                    
                    Tables\Actions\DeleteAction::make()
                        ->label('🗑️ Hapus')
                        ->color('danger')
                        ->visible(fn (Tindakan $record): bool => $record->status === 'pending' && $record->status_validasi !== 'approved'),
                    
                    // Submit for validation
                    Tables\Actions\Action::make('submit_validation')
                        ->label('📤 Ajukan Validasi')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->visible(fn (Tindakan $record): bool => $record->status_validasi === 'pending' && $record->submitted_at === null)
                        ->requiresConfirmation()
                        ->modalHeading('📤 Ajukan Validasi Tindakan')
                        ->modalDescription('Pastikan semua data sudah benar sebelum mengajukan validasi.')
                        ->modalSubmitActionLabel('Ajukan')
                        ->action(function (Tindakan $record) {
                            try {
                                $validationService = new ValidationWorkflowService(new \App\Services\TelegramService());
                                $result = $validationService->submitForValidation($record);
                                
                                if ($result['auto_approved']) {
                                    Notification::make()
                                        ->title('✅ Auto-Approved')
                                        ->body('Tindakan berhasil disetujui otomatis')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('📤 Berhasil Diajukan')
                                        ->body('Tindakan berhasil diajukan untuk validasi')
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
                    
                    // Approve action (for supervisors/managers)
                    Tables\Actions\Action::make('approve')
                        ->label('✅ Setujui')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Tindakan $record): bool => 
                            $record->status_validasi === 'pending' && 
                            $record->submitted_at !== null &&
                            Auth::user()->hasAnyRole(['supervisor', 'manager', 'admin'])
                        )
                        ->requiresConfirmation()
                        ->modalHeading('✅ Setujui Tindakan')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui tindakan ini?')
                        ->modalSubmitActionLabel('Setujui')
                        ->form([
                            Textarea::make('approval_reason')
                                ->label('Alasan Persetujuan (Opsional)')
                                ->placeholder('Masukkan alasan persetujuan...')
                                ->rows(3),
                        ])
                        ->action(function (Tindakan $record, array $data) {
                            try {
                                $validationService = new ValidationWorkflowService(new \App\Services\TelegramService());
                                $result = $validationService->approve($record, [
                                    'reason' => $data['approval_reason'] ?? 'Approved by ' . Auth::user()->name
                                ]);
                                
                                Notification::make()
                                    ->title('✅ Berhasil Disetujui')
                                    ->body('Tindakan berhasil disetujui')
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
                    
                    // Reject action (for supervisors/managers)
                    Tables\Actions\Action::make('reject')
                        ->label('❌ Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Tindakan $record): bool => 
                            $record->status_validasi === 'pending' && 
                            $record->submitted_at !== null &&
                            Auth::user()->hasAnyRole(['supervisor', 'manager', 'admin'])
                        )
                        ->requiresConfirmation()
                        ->modalHeading('❌ Tolak Tindakan')
                        ->modalDescription('Berikan alasan penolakan yang jelas.')
                        ->modalSubmitActionLabel('Tolak')
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->placeholder('Masukkan alasan penolakan...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Tindakan $record, array $data) {
                            try {
                                $validationService = new ValidationWorkflowService(new \App\Services\TelegramService());
                                $result = $validationService->reject($record, $data['rejection_reason']);
                                
                                Notification::make()
                                    ->title('❌ Berhasil Ditolak')
                                    ->body('Tindakan berhasil ditolak')
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
                    
                    // Request revision action (for supervisors/managers)
                    Tables\Actions\Action::make('request_revision')
                        ->label('🔄 Minta Revisi')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Tindakan $record): bool => 
                            $record->status_validasi === 'pending' && 
                            $record->submitted_at !== null &&
                            Auth::user()->hasAnyRole(['supervisor', 'manager', 'admin'])
                        )
                        ->requiresConfirmation()
                        ->modalHeading('🔄 Minta Revisi Tindakan')
                        ->modalDescription('Berikan catatan revisi yang jelas.')
                        ->modalSubmitActionLabel('Minta Revisi')
                        ->form([
                            Textarea::make('revision_reason')
                                ->label('Catatan Revisi')
                                ->placeholder('Masukkan catatan revisi...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Tindakan $record, array $data) {
                            try {
                                $validationService = new ValidationWorkflowService(new \App\Services\TelegramService());
                                $result = $validationService->requestRevision($record, $data['revision_reason']);
                                
                                Notification::make()
                                    ->title('🔄 Revisi Diminta')
                                    ->body('Permintaan revisi berhasil dikirim')
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
                ])
                ->label('⚙️ Aksi')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->can('delete_any_tindakan')),
                    
                    // Export selected treatments
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('📤 Export Terpilih')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Export Data Tindakan')
                        ->modalDescription('Export data tindakan yang dipilih ke format file.')
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
                                ->helperText('Sertakan data pasien, dokter, dan relasi lainnya')
                                ->default(true),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $exportService = new ExportImportService();
                                $ids = $records->pluck('id')->toArray();
                                
                                // Create temporary filtered export
                                $result = $exportService->exportData(
                                    Tindakan::class,
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
                        }),
                    
                    // Bulk update status
                    Tables\Actions\BulkAction::make('bulk_update_status')
                        ->label('🔄 Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Update Status Tindakan')
                        ->modalDescription('Update status untuk tindakan yang dipilih.')
                        ->modalSubmitActionLabel('Update')
                        ->form([
                            Select::make('status')
                                ->label('Status Tindakan')
                                ->options([
                                    'pending' => 'Menunggu',
                                    'selesai' => 'Selesai',
                                    'batal' => 'Batal',
                                ])
                                ->nullable(),
                            Select::make('status_validasi')
                                ->label('Status Validasi')
                                ->options([
                                    'pending' => 'Menunggu Validasi',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
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
                                    Tindakan::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Update Berhasil')
                                    ->body("Berhasil update {$result['updated']} tindakan.")
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
                        ->modalHeading('Assign Tindakan ke User')
                        ->modalDescription('Assign tindakan yang dipilih ke user tertentu.')
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
                                        'input_by' => $data['user_id']
                                    ];
                                })->toArray();
                                
                                $result = $bulkService->bulkUpdate(
                                    Tindakan::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Assign Berhasil')
                                    ->body("Berhasil assign {$result['updated']} tindakan.")
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
                    
                    // Bulk approve treatments
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('✅ Approve Tindakan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Tindakan')
                        ->modalDescription('Approve tindakan yang dipilih untuk validasi.')
                        ->modalSubmitActionLabel('Approve')
                        ->visible(fn (): bool => Auth::user()->can('approve_tindakan'))
                        ->action(function (Collection $records) {
                            try {
                                $bulkService = new BulkOperationService();
                                $updates = $records->map(function ($record) {
                                    return [
                                        'id' => $record->id,
                                        'status_validasi' => 'approved',
                                        'status' => 'selesai'
                                    ];
                                })->toArray();
                                
                                $result = $bulkService->bulkUpdate(
                                    Tindakan::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Approve Berhasil')
                                    ->body("Berhasil approve {$result['updated']} tindakan.")
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
                ]),
            ])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('input_by', Auth::id())
            ->with(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'nonParamedis', 'shift']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTindakans::route('/'),
            'create' => Pages\CreateTindakan::route('/create'),
            'view' => Pages\ViewTindakan::route('/{record}'),
            'edit' => Pages\EditTindakan::route('/{record}/edit'),
        ];
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        $panel = $panel ?? 'petugas';

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }
}
