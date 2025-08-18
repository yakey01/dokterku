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

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = '🩺 Input Tindakan';
    
    protected static ?string $navigationGroup = 'Tindakan Medis';

    protected static ?string $modelLabel = 'Tindakan';

    protected static ?string $pluralModelLabel = 'Input Tindakan';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Informasi Dasar Tindakan
                Forms\Components\Section::make('📋 Informasi Dasar Tindakan')
                    ->description('Pilih jenis tindakan dan pasien yang akan menjalani tindakan')
                    ->schema([
                        Forms\Components\Select::make('jenis_tindakan_id')
                            ->label('🩺 Jenis Tindakan')
                            ->required()
                            ->relationship('jenisTindakan', 'nama', fn (Builder $query) => $query->where('is_active', true)->orderBy('nama'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Ketik untuk mencari jenis tindakan...')
                            ->helperText('Pilih jenis tindakan yang akan dilakukan')
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $jenisTindakan = JenisTindakan::find($state);
                                    if ($jenisTindakan) {
                                        $tarif = $jenisTindakan->tarif;
                                        $persentaseJaspel = config('app.default_jaspel_percentage', 40);
                                        $jasaPetugas = $tarif * ($persentaseJaspel / 100);

                                        $set('tarif', $tarif);
                                        $set('calculated_jaspel', $jasaPetugas);
                                        $set('persentase_jaspel_info', $persentaseJaspel);
                                        
                                        // Re-calculate and allocate JASPEL based on current staff selection
                                        $dokterId = $get('dokter_id');
                                        $paramedisId = $get('paramedis_id');
                                        $nonParamedisId = $get('non_paramedis_id');
                                        
                                        if ($dokterId) {
                                            // Doctor selected - give JASPEL to doctor
                                            $set('jasa_dokter', $jasaPetugas);
                                            $set('jasa_paramedis', 0);
                                            $set('jasa_non_paramedis', 0);
                                        } elseif ($paramedisId) {
                                            // Only paramedis selected - give JASPEL to paramedis
                                            $set('jasa_dokter', 0);
                                            $set('jasa_paramedis', $jasaPetugas);
                                            $set('jasa_non_paramedis', 0);
                                        } elseif ($nonParamedisId) {
                                            // Only non-paramedis selected - give base fee to non-paramedis
                                            $set('jasa_dokter', 0);
                                            $set('jasa_paramedis', 0);
                                            $set('jasa_non_paramedis', $jenisTindakan->jasa_non_paramedis);
                                        } else {
                                            // No staff selected - all fees are 0
                                            $set('jasa_dokter', 0);
                                            $set('jasa_paramedis', 0);
                                            $set('jasa_non_paramedis', 0);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Select::make('pasien_id')
                            ->label('👤 Pasien')
                            ->required()
                            ->relationship('pasien', 'nama')
                            ->searchable()
                            ->preload(false)
                            ->placeholder('Ketik nama atau nomor rekam medis...')
                            ->helperText('Cari berdasarkan nama atau nomor rekam medis')
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
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Waktu dan Shift
                Forms\Components\Section::make('🕐 Waktu dan Shift')
                    ->description('Tentukan waktu pelaksanaan tindakan dan shift kerja')
                    ->schema([
                        Forms\Components\DateTimePicker::make('tanggal_tindakan')
                            ->label('📅 Tanggal & Waktu Tindakan')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->helperText('Maksimal tanggal hari ini')
                            ->native(false),

                        Forms\Components\Select::make('shift_id')
                            ->label('⏰ Shift Kerja')
                            ->options(function () {
                                return ShiftTemplate::query()
                                    ->orderBy('nama_shift')
                                    ->pluck('nama_shift', 'id');
                            })
                            ->required()
                            ->native(false)
                            ->preload()
                            ->placeholder('Pilih shift kerja...')
                            ->helperText('Data shift dikelola di Admin → Template Shift'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Tim Pelaksana
                Forms\Components\Section::make('👥 Tim Pelaksana Tindakan')
                    ->description('Pilih tim medis yang akan melaksanakan tindakan (opsional)')
                    ->schema([
                        Forms\Components\Select::make('dokter_id')
                            ->label('👨‍⚕️ Dokter Pelaksana')
                            ->options(function () {
                                return \App\Models\Dokter::where('aktif', true)
                                    ->orderBy('nama_lengkap')
                                    ->pluck('nama_lengkap', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih dokter (opsional)...')
                            ->helperText('Jika dipilih, JASPEL akan diberikan ke dokter')
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    // Doctor selected - calculate JASPEL from current jenis_tindakan
                                    $jenisTindakanId = $get('jenis_tindakan_id');
                                    if ($jenisTindakanId) {
                                        $jenisTindakan = JenisTindakan::find($jenisTindakanId);
                                        if ($jenisTindakan) {
                                            $tarif = $jenisTindakan->tarif;
                                            $persentaseJaspel = config('app.default_jaspel_percentage', 40);
                                            $calculatedJaspel = $tarif * ($persentaseJaspel / 100);
                                            
                                            // Give JASPEL to doctor only
                                            $set('jasa_dokter', $calculatedJaspel);
                                            $set('jasa_paramedis', 0);
                                            $set('jasa_non_paramedis', 0);
                                            
                                            // Update calculated_jaspel for consistency
                                            $set('calculated_jaspel', $calculatedJaspel);
                                        }
                                    }
                                } else {
                                    $set('jasa_dokter', 0);
                                    
                                    // If paramedis exists, give JASPEL to paramedis
                                    if ($get('paramedis_id')) {
                                        $jenisTindakanId = $get('jenis_tindakan_id');
                                        if ($jenisTindakanId) {
                                            $jenisTindakan = JenisTindakan::find($jenisTindakanId);
                                            if ($jenisTindakan) {
                                                $tarif = $jenisTindakan->tarif;
                                                $persentaseJaspel = config('app.default_jaspel_percentage', 40);
                                                $calculatedJaspel = $tarif * ($persentaseJaspel / 100);
                                                
                                                $set('jasa_paramedis', $calculatedJaspel);
                                                $set('jasa_non_paramedis', 0);
                                            }
                                        }
                                    } else {
                                        // No doctor, no paramedis - give fee to non-paramedis if selected
                                        $set('jasa_paramedis', 0);
                                        if ($get('non_paramedis_id')) {
                                            $jenisTindakanId = $get('jenis_tindakan_id');
                                            if ($jenisTindakanId) {
                                                $jenisTindakan = JenisTindakan::find($jenisTindakanId);
                                                if ($jenisTindakan) {
                                                    $set('jasa_non_paramedis', $jenisTindakan->jasa_non_paramedis);
                                                }
                                            }
                                        } else {
                                            $set('jasa_non_paramedis', 0);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Select::make('paramedis_id')
                            ->label('👩‍⚕️ Paramedis Pelaksana')
                            ->options(function () {
                                return \App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')
                                    ->where('aktif', true)
                                    ->orderBy('nama_lengkap')
                                    ->pluck('nama_lengkap', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih paramedis (opsional)...')
                            ->helperText('Jika tidak ada dokter, JASPEL akan diberikan ke paramedis')
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && ! $get('dokter_id')) {
                                    // Only paramedis selected - calculate JASPEL from current jenis_tindakan
                                    $jenisTindakanId = $get('jenis_tindakan_id');
                                    if ($jenisTindakanId) {
                                        $jenisTindakan = JenisTindakan::find($jenisTindakanId);
                                        if ($jenisTindakan) {
                                            $tarif = $jenisTindakan->tarif;
                                            $persentaseJaspel = config('app.default_jaspel_percentage', 40);
                                            $calculatedJaspel = $tarif * ($persentaseJaspel / 100);
                                            
                                            $set('jasa_paramedis', $calculatedJaspel);
                                            $set('jasa_non_paramedis', 0);
                                            
                                            // Update calculated_jaspel for consistency
                                            $set('calculated_jaspel', $calculatedJaspel);
                                        }
                                    }
                                } elseif (! $state) {
                                    $set('jasa_paramedis', 0);
                                    // If no paramedis and no doctor, give fee to non-paramedis if selected
                                    if (!$get('dokter_id') && $get('non_paramedis_id')) {
                                        $jenisTindakanId = $get('jenis_tindakan_id');
                                        if ($jenisTindakanId) {
                                            $jenisTindakan = JenisTindakan::find($jenisTindakanId);
                                            if ($jenisTindakan) {
                                                $set('jasa_non_paramedis', $jenisTindakan->jasa_non_paramedis);
                                            }
                                        }
                                    } else {
                                        $set('jasa_non_paramedis', 0);
                                    }
                                } elseif ($get('dokter_id')) {
                                    $set('jasa_paramedis', 0);
                                }
                            }),

                        Forms\Components\Select::make('non_paramedis_id')
                            ->label('👨‍💼 Non-Paramedis Pelaksana')
                            ->options(function () {
                                return \App\Models\Pegawai::where('jenis_pegawai', 'Non-Paramedis')
                                    ->where('aktif', true)
                                    ->orderBy('nama_lengkap')
                                    ->pluck('nama_lengkap', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih non-paramedis (opsional)...')
                            ->helperText('Staff pendukung yang terlibat dalam tindakan')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Only give fee to non-paramedis if no doctor or paramedis is selected
                                if ($state && !$get('dokter_id') && !$get('paramedis_id')) {
                                    $jenisTindakanId = $get('jenis_tindakan_id');
                                    if ($jenisTindakanId) {
                                        $jenisTindakan = JenisTindakan::find($jenisTindakanId);
                                        if ($jenisTindakan) {
                                            $set('jasa_non_paramedis', $jenisTindakan->jasa_non_paramedis);
                                        }
                                    }
                                } elseif (!$state) {
                                    $set('jasa_non_paramedis', 0);
                                }
                            }),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // Informasi Keuangan
                Forms\Components\Section::make('💰 Informasi Keuangan')
                    ->description('Tarif dan pembagian jasa pelayanan (JASPEL) - dihitung otomatis')
                    ->schema([
                        Forms\Components\TextInput::make('tarif')
                            ->label('💵 Tarif Tindakan')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Otomatis terisi dari jenis tindakan')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Tarif otomatis dari master jenis tindakan'),

                        Forms\Components\TextInput::make('jasa_dokter')
                            ->label('👨‍⚕️ Jasa Dokter')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('JASPEL untuk dokter pelaksana'),

                        Forms\Components\TextInput::make('jasa_paramedis')
                            ->label('👩‍⚕️ Jasa Paramedis')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('JASPEL untuk paramedis pelaksana'),

                        Forms\Components\TextInput::make('jasa_non_paramedis')
                            ->label('👨‍💼 Jasa Non-Paramedis')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Jasa untuk non-paramedis'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Status dan Catatan
                Forms\Components\Section::make('📝 Status dan Catatan')
                    ->description('Status tindakan dan catatan tambahan')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('📊 Status Tindakan')
                            ->options([
                                'pending' => '⏳ Menunggu',
                                'selesai' => '✅ Selesai',
                                'batal' => '❌ Batal',
                            ])
                            ->default('pending')
                            ->required()
                            ->helperText('Status pelaksanaan tindakan'),

                        Forms\Components\Textarea::make('catatan')
                            ->label('📋 Catatan Tambahan')
                            ->maxLength(500)
                            ->placeholder('Masukkan catatan atau keterangan khusus (opsional)...')
                            ->helperText('Maksimal 500 karakter'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Hidden Fields
                Forms\Components\Hidden::make('persentase_jaspel_info')
                    ->default(config('app.default_jaspel_percentage', 40)),
                Forms\Components\Hidden::make('calculated_jaspel')
                    ->default(0),
                Forms\Components\Hidden::make('input_by')
                    ->default(fn () => Auth::id()),
                Forms\Components\Hidden::make('status_validasi')
                    ->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('📅 Tanggal Tindakan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->copyable(),

                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('🩺 Jenis Tindakan')
                    ->searchable()
                    ->limit(35)
                    ->tooltip(fn (Tindakan $record): string => $record->jenisTindakan->nama ?? '')
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('pasien.nama')
                    ->label('👤 Pasien')
                    ->searchable()
                    ->limit(25)
                    ->description(fn (Tindakan $record): string => 
                        '📋 RM: ' . ($record->pasien->no_rekam_medis ?? '-')
                    )
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('dokter.nama_lengkap')
                    ->label('👨‍⚕️ Dokter')
                    ->searchable()
                    ->placeholder('➖ Tidak ada')
                    ->toggleable()
                    ->limit(20)
                    ->color('success')
                    ->default('➖'),

                Tables\Columns\TextColumn::make('paramedis.nama_lengkap')
                    ->label('👩‍⚕️ Paramedis')
                    ->searchable()
                    ->placeholder('➖ Tidak ada')
                    ->toggleable()
                    ->limit(20)
                    ->color('info')
                    ->default('➖'),

                Tables\Columns\TextColumn::make('shift.nama_shift')
                    ->label('⏰ Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Siang' => 'warning',
                        'Sore' => 'warning', 
                        'Malam' => 'primary',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Pagi' => '🌅 Pagi',
                        'Siang' => '☀️ Siang',
                        'Sore' => '🌇 Sore',
                        'Malam' => '🌙 Malam',
                        default => $state
                    }),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('💰 Tarif')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('📊 Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'selesai' => 'success',
                        'batal' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Menunggu',
                        'selesai' => '✅ Selesai',
                        'batal' => '❌ Batal',
                        default => $state
                    }),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('✔️ Validasi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Pending',
                        'approved' => '✅ Disetujui',
                        'rejected' => '❌ Ditolak',
                        default => $state
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📝 Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),
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
                // Quick actions (visible)
                Tables\Actions\ViewAction::make()
                    ->label('👁️')
                    ->tooltip('Lihat Detail')
                    ->color('info')
                    ->size('sm'),
                    
                Tables\Actions\EditAction::make()
                    ->label('✏️')
                    ->tooltip('Edit Tindakan')
                    ->color('warning')
                    ->size('sm')
                    ->visible(fn (Tindakan $record): bool => 
                        $record->status === 'pending' && $record->status_validasi !== 'approved'
                    ),
                
                // More actions in dropdown
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\DeleteAction::make()
                        ->label('🗑️ Hapus')
                        ->color('danger')
                        ->visible(fn (Tindakan $record): bool => 
                            $record->status === 'pending' && $record->status_validasi !== 'approved'
                        ),
                    
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
                            Auth::check() && Auth::user()->hasAnyRole(['supervisor', 'manager', 'admin'])
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
                                    'reason' => $data['approval_reason'] ?? 'Approved by ' . (Auth::check() ? Auth::user()->name : 'System')
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
                            Auth::check() && Auth::user()->hasAnyRole(['supervisor', 'manager', 'admin'])
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
                            Auth::check() && Auth::user()->hasAnyRole(['supervisor', 'manager', 'admin'])
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
                        ->visible(fn (): bool => Auth::check() && Auth::user()->can('delete_any_tindakan')),
                    
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
                        ->visible(fn (): bool => Auth::check() && Auth::user()->can('approve_tindakan'))
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
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('📋 Belum Ada Data Tindakan')
            ->emptyStateDescription('Mulai dengan menambahkan tindakan medis pertama Anda')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('🩺 Tambah Tindakan Pertama')
                    ->color('primary'),
            ])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->poll('30s')
            ->striped()
            ->defaultPaginationPageOption(25);
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
