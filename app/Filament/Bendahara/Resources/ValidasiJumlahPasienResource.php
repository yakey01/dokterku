<?php

namespace App\Filament\Bendahara\Resources;

use App\Filament\Concerns\HasMonthlyArchive;
use App\Models\JumlahPasienHarian;
use App\Services\ValidationWorkflowService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\Alignment;
use Carbon\Carbon;
use App\Constants\ValidationStatus;

class ValidasiJumlahPasienResource extends Resource
{
    use HasMonthlyArchive;
    
    protected static ?string $model = JumlahPasienHarian::class;
    
    // Configure monthly archive to use tanggal column  
    public static function getArchiveDateColumn(): string
    {
        return 'tanggal';
    }

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Validasi Transaksi';

    protected static ?string $navigationLabel = 'Validasi Jumlah Pasien';

    protected static ?string $modelLabel = 'Jumlah Pasien Harian';

    protected static ?string $pluralModelLabel = 'Validasi Jumlah Pasien';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Jumlah Pasien')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->disabled(),

                        Forms\Components\Select::make('poli')
                            ->label('Poli')
                            ->options([
                                'umum' => 'Poli Umum',
                                'gigi' => 'Poli Gigi',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('dokter_id')
                            ->label('Nama Dokter')
                            ->relationship('dokter', 'nama_lengkap')
                            ->disabled(),

                        Forms\Components\TextInput::make('jumlah_pasien_umum')
                            ->label('Pasien Umum')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('jumlah_pasien_bpjs')
                            ->label('Pasien BPJS')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('jaspel_rupiah')
                            ->label('💰 Jaspel (Rupiah)')
                            ->numeric()
                            ->minValue(0)
                            ->step(100)
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->helperText('Nominal jasa pelayanan dalam Rupiah')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : '')
                            ->dehydrateStateUsing(fn ($state) => $state ? str_replace(['.', ','], '', $state) : null),

                        Forms\Components\Select::make('input_by')
                            ->label('Input Oleh')
                            ->relationship('inputBy', 'name')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validasi Bendahara')
                    ->schema([
                        Forms\Components\Select::make('status_validasi')
                            ->label('Status Validasi')
                            ->options(ValidationStatus::labels())
                            ->required(),

                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Catatan Validasi')
                            ->placeholder('Tambahkan catatan validasi...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('dokter.nama_lengkap')
                    ->label('Nama Dokter')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->icon('heroicon-o-user')
                    ->description(fn ($record) => $record->dokter?->jabatan_display ?? '-'),

                Tables\Columns\TextColumn::make('poli')
                    ->label('Poli')
                    ->color(fn (string $state): string => match ($state) {
                        'umum' => 'primary',
                        'gigi' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'umum' => '🏥 Umum',
                        'gigi' => '🦷 Gigi',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('total_pasien')
                    ->label('Total Pasien')
                    ->numeric()
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state > 100 => 'danger',
                        $state > 50 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('jumlah_pasien_umum')
                    ->label('Pasien Umum')
                    ->numeric()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('jumlah_pasien_bpjs')
                    ->label('Pasien BPJS')
                    ->numeric()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('calculated_jaspel')
                    ->label('💰 Jaspel (Terhitung)')
                    ->getStateUsing(function ($record) {
                        // Calculate from procedures + patient count
                        $procedureRevenue = 0;
                        $patientJaspel = 0;
                        
                        // Get procedure revenue for this doctor on this date
                        if ($record->dokter_id) {
                            $procedureRevenue = \App\Models\Tindakan::where('dokter_id', $record->dokter_id)
                                ->whereDate('tanggal_tindakan', $record->tanggal)
                                ->where('status_validasi', 'disetujui')
                                ->sum('jasa_dokter') ?? 0;
                        }
                        
                        // Calculate patient-based jaspel
                        try {
                            $jaspelService = app(\App\Services\Jaspel\UnifiedJaspelCalculationService::class);
                            $patientCalculation = $jaspelService->calculateForPasienRecord($record);
                            $patientJaspel = $patientCalculation['total'] ?? 0;
                        } catch (\Exception $e) {
                            \Log::warning('Failed to calculate patient jaspel', [
                                'record_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                            $patientJaspel = 0;
                        }
                        
                        return $procedureRevenue + $patientJaspel;
                    })
                    ->formatStateUsing(function ($state) {
                        // Handle null, empty string, or zero values
                        if (is_null($state) || $state === '' || $state == 0) {
                            return 'Rp 0';
                        }
                        // Format as Indonesian Rupiah
                        return 'Rp ' . number_format((float)$state, 0, ',', '.');
                    })
                    ->alignment(Alignment::End)
                    ->sortable(false)
                    ->color(fn ($state) => !is_null($state) && $state > 0 ? 'success' : 'gray')
                    ->icon('heroicon-o-calculator')
                    ->description(function ($record) {
                        // Show breakdown in description
                        $procedureRevenue = 0;
                        $patientJaspel = 0;
                        
                        if ($record->dokter_id) {
                            $procedureRevenue = \App\Models\Tindakan::where('dokter_id', $record->dokter_id)
                                ->whereDate('tanggal_tindakan', $record->tanggal)
                                ->where('status_validasi', 'disetujui')
                                ->sum('jasa_dokter') ?? 0;
                        }
                        
                        try {
                            $jaspelService = app(\App\Services\Jaspel\UnifiedJaspelCalculationService::class);
                            $patientCalculation = $jaspelService->calculateForPasienRecord($record);
                            $patientJaspel = $patientCalculation['total'] ?? 0;
                        } catch (\Exception $e) {
                            $patientJaspel = 0;
                        }
                        
                        return 'Tindakan: Rp ' . number_format($procedureRevenue, 0, ',', '.') . ' | Pasien: Rp ' . number_format($patientJaspel, 0, ',', '.');
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('jaspel_rupiah')
                    ->label('📝 Jaspel Manual')
                    ->formatStateUsing(function ($state) {
                        // Handle null, empty string, or zero values
                        if (is_null($state) || $state === '' || $state == 0) {
                            return '-';
                        }
                        // Format as Indonesian Rupiah
                        return 'Rp ' . number_format((float)$state, 0, ',', '.');
                    })
                    ->alignment(Alignment::End)
                    ->sortable()
                    ->color(fn ($state) => !is_null($state) && $state > 0 ? 'warning' : 'gray')
                    ->icon('heroicon-o-pencil-square')
                    ->description('Manual Entry (Legacy)')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'need_revision' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Menunggu',
                        'approved' => '✅ Disetujui',
                        'rejected' => '❌ Ditolak',
                        'need_revision' => '📝 Revisi',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Input Oleh')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(array_merge(
                // Monthly Archive Filters - defaults to current month
                static::getMonthlyArchiveFilters(),
                [

                Tables\Filters\SelectFilter::make('poli')
                    ->label('Poli')
                    ->options([
                        'umum' => 'Poli Umum',
                        'gigi' => 'Poli Gigi',
                    ]),

                Tables\Filters\SelectFilter::make('dokter_id')
                    ->label('Dokter')
                    ->relationship('dokter', 'nama_lengkap')
                    ->preload(),

                // Status filter moved to tabs in ListValidasiJumlahPasien page
                // Tables\Filters\SelectFilter::make('status_validasi') - removed in favor of tabs

                Tables\Filters\Filter::make('pasien_banyak')
                    ->label('Pasien > 50')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('(jumlah_pasien_umum + jumlah_pasien_bpjs) > 50')),

                Tables\Filters\Filter::make('jaspel_filter')
                    ->label('Filter Jaspel')
                    ->form([
                        Forms\Components\TextInput::make('min_jaspel')
                            ->label('Minimal Jaspel (Rp)')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('max_jaspel')
                            ->label('Maksimal Jaspel (Rp)')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_jaspel'],
                                fn (Builder $query, $amount): Builder => $query->where('jaspel_rupiah', '>=', $amount),
                            )
                            ->when(
                                $data['max_jaspel'],
                                fn (Builder $query, $amount): Builder => $query->where('jaspel_rupiah', '<=', $amount),
                            );
                    }),

                Tables\Filters\Filter::make('ada_jaspel')
                    ->label('Ada Jaspel')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('jaspel_rupiah')->where('jaspel_rupiah', '>', 0)),
                ]
            ))
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('✅ Setujui')
                        ->color('success')
                        ->action(function (JumlahPasienHarian $record) {
                            try {
                                $record->update([
                                    'status_validasi' => 'approved',
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                // Clear manager dashboard cache for real-time updates
                                \Illuminate\Support\Facades\Cache::forget('manajer_today_stats_' . now()->format('Y-m-d'));
                                \Illuminate\Support\Facades\Cache::forget('manajer.today_stats');
                                
                                // Trigger real-time update event for patient count approval
                                event(new \App\Events\DataInputDisimpan($record, Auth::user()));

                                Notification::make()
                                    ->title('✅ Data Pasien Disetujui')
                                    ->body("Data pasien tanggal {$record->tanggal->format('d/m/Y')} poli {$record->poli} dari dr. {$record->dokter?->nama_lengkap} disetujui")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('❌ Gagal Menyetujui')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn (JumlahPasienHarian $record): bool => $record->status_validasi === 'pending'),

                    Tables\Actions\Action::make('reject')
                        ->label('❌ Tolak')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->placeholder('Jelaskan alasan penolakan...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (JumlahPasienHarian $record, array $data) {
                            try {
                                $record->update([
                                    'status_validasi' => 'rejected',
                                    'catatan_validasi' => $data['rejection_reason'],
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('❌ Data Pasien Ditolak')
                                    ->body("Data pasien ditolak")
                                    ->warning()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('❌ Gagal Menolak')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn (JumlahPasienHarian $record): bool => $record->status_validasi === 'pending'),
                        
                    Tables\Actions\ViewAction::make()->label('👁️ Lihat'),
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->visible(fn (JumlahPasienHarian $record): bool => 
                            in_array($record->status_validasi, ['pending', 'need_revision'])
                        ),
                ])
                ->label('Aksi')
                ->button()
                ->size('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('✅ Setujui Massal')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Data Pasien Massal')
                        ->modalDescription(fn (array $records) => 
                            'Anda akan menyetujui ' . count($records) . ' data pasien. Total pasien: ' . 
                            collect($records)->sum(fn($r) => $r->jumlah_pasien_umum + $r->jumlah_pasien_bpjs) . ' pasien.'
                        )
                        ->action(function (array $records) {
                            $approved = 0;
                            $totalPatients = 0;
                            
                            foreach ($records as $record) {
                                if ($record->status_validasi === 'pending') {
                                    $record->update([
                                        'status_validasi' => 'approved',
                                        'validasi_by' => Auth::id(),
                                        'validasi_at' => now(),
                                    ]);
                                    $approved++;
                                    $totalPatients += $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs;
                                }
                            }
                            
                            // Clear manager dashboard cache
                            \Illuminate\Support\Facades\Cache::forget('manajer_today_stats_' . now()->format('Y-m-d'));
                            
                            Notification::make()
                                ->title('✅ Validasi Massal Berhasil')
                                ->body("{$approved} data pasien disetujui dengan total {$totalPatients} pasien")
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('❌ Tolak Massal')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('bulk_rejection_reason')
                                ->label('Alasan Penolakan Massal')
                                ->placeholder('Jelaskan alasan penolakan untuk semua data...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (array $records, array $data) {
                            $rejected = 0;
                            
                            foreach ($records as $record) {
                                if ($record->status_validasi === 'pending') {
                                    $record->update([
                                        'status_validasi' => 'rejected',
                                        'catatan_validasi' => $data['bulk_rejection_reason'],
                                        'validasi_by' => Auth::id(),
                                        'validasi_at' => now(),
                                    ]);
                                    $rejected++;
                                }
                            }
                            
                            Notification::make()
                                ->title('❌ Penolakan Massal Berhasil')
                                ->body("{$rejected} data pasien ditolak")
                                ->warning()
                                ->send();
                        }),
                ])
            ])
            ->headerActions([
                Action::make('auto_validation_setup')
                    ->label('⚡ Validasi Otomatis')
                    ->color('warning')
                    ->icon('heroicon-o-bolt')
                    ->form([
                        Forms\Components\Toggle::make('enable_auto_validation')
                            ->label('Aktifkan Validasi Otomatis')
                            ->helperText('Auto-approve data yang memenuhi kriteria standard'),
                        Forms\Components\TextInput::make('max_patient_threshold')
                            ->label('Batas Maksimal Pasien')
                            ->numeric()
                            ->default(50)
                            ->helperText('Data dengan pasien ≤ nilai ini akan di-approve otomatis'),
                        Forms\Components\TextInput::make('max_jaspel_threshold') 
                            ->label('Batas Maksimal JASPEL (Rp)')
                            ->numeric()
                            ->default(500000)
                            ->prefix('Rp')
                            ->helperText('Data dengan JASPEL ≤ nilai ini akan di-approve otomatis'),
                    ])
                    ->action(function (array $data) {
                        if ($data['enable_auto_validation']) {
                            $autoApproved = JumlahPasienHarian::where('status_validasi', 'pending')
                                ->whereRaw('(jumlah_pasien_umum + jumlah_pasien_bpjs) <= ?', [$data['max_patient_threshold']])
                                ->where('jaspel_rupiah', '<=', $data['max_jaspel_threshold'])
                                ->update([
                                    'status_validasi' => 'approved',
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                    'catatan_validasi' => 'Auto-approved: Memenuhi kriteria standard (≤' . $data['max_patient_threshold'] . ' pasien, ≤Rp' . number_format($data['max_jaspel_threshold']) . ')'
                                ]);
                            
                            // Clear manager dashboard cache
                            \Illuminate\Support\Facades\Cache::forget('manajer_today_stats_' . now()->format('Y-m-d'));
                            
                            Notification::make()
                                ->title('⚡ Validasi Otomatis Selesai')
                                ->body("{$autoApproved} data pasien di-approve otomatis berdasarkan kriteria")
                                ->success()
                                ->send();
                        }
                    }),
                    
                Action::make('patient_summary')
                    ->label('👥 Ringkasan Pasien')
                    ->color('info')
                    ->action(function () {
                        $today = now()->toDateString();
                        $summary = [
                            'total_today' => JumlahPasienHarian::whereDate('tanggal', $today)
                                ->selectRaw('SUM(jumlah_pasien_umum + jumlah_pasien_bpjs) as total')
                                ->value('total') ?? 0,
                            'avg_per_poli' => JumlahPasienHarian::whereDate('tanggal', $today)
                                ->selectRaw('AVG(jumlah_pasien_umum + jumlah_pasien_bpjs) as avg')
                                ->value('avg') ?? 0,
                            'pending_count' => JumlahPasienHarian::where('status_validasi', 'pending')->count(),
                            'monthly_avg' => JumlahPasienHarian::whereMonth('tanggal', now()->month)
                                ->selectRaw('AVG(jumlah_pasien_umum + jumlah_pasien_bpjs) as avg')
                                ->value('avg') ?? 0,
                            'total_jaspel_today' => static::calculateTotalJaspelForDate($today),
                            'avg_jaspel_monthly' => static::calculateAvgJaspelForMonth(now()->month, now()->year),
                        ];

                        $message = "👥 **RINGKASAN PASIEN HARIAN**\n\n";
                        $message .= "📅 Hari Ini: {$summary['total_today']} pasien\n";
                        $message .= "📊 Rata-rata per Poli: " . round($summary['avg_per_poli'], 1) . " pasien\n";
                        $message .= "📈 Rata-rata Bulanan: " . round($summary['monthly_avg'], 1) . " pasien\n";
                        $message .= "💰 Total Jaspel Hari Ini: Rp " . number_format($summary['total_jaspel_today'], 0, ',', '.') . "\n";
                        $message .= "💵 Rata-rata Jaspel Bulanan: Rp " . number_format($summary['avg_jaspel_monthly'], 0, ',', '.') . "\n";
                        $message .= "⏳ Pending Validasi: {$summary['pending_count']}";

                        Notification::make()
                            ->title('👥 Ringkasan Pasien & Jaspel')
                            ->body($message)
                            ->info()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['inputBy', 'validasiBy', 'dokter']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status_validasi', ['pending', 'menunggu'])->count() ?: null;
    }

    public static function canAccess(): bool
    {
        // Proper role-based access control
        return auth()->check() && auth()->user()->hasRole('bendahara');
    }
    
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ValidasiJumlahPasienResource\Pages\ListValidasiJumlahPasien::route('/'),
        ];
    }

    /**
     * Calculate total jaspel for a specific date using procedure + patient calculation
     */
    private static function calculateTotalJaspelForDate(string $date): float
    {
        $records = JumlahPasienHarian::whereDate('tanggal', $date)->get();
        $total = 0;
        
        foreach ($records as $record) {
            // Calculate procedure revenue
            $procedureRevenue = 0;
            if ($record->dokter_id) {
                $procedureRevenue = \App\Models\Tindakan::where('dokter_id', $record->dokter_id)
                    ->whereDate('tanggal_tindakan', $record->tanggal)
                    ->where('status_validasi', 'disetujui')
                    ->sum('jasa_dokter') ?? 0;
            }
            
            // Calculate patient jaspel
            $patientJaspel = 0;
            try {
                $jaspelService = app(\App\Services\Jaspel\UnifiedJaspelCalculationService::class);
                $patientCalculation = $jaspelService->calculateForPasienRecord($record);
                $patientJaspel = $patientCalculation['total'] ?? 0;
            } catch (\Exception $e) {
                $patientJaspel = 0;
            }
            
            $total += $procedureRevenue + $patientJaspel;
        }
        
        return $total;
    }

    /**
     * Calculate average jaspel for a specific month using procedure + patient calculation
     */
    private static function calculateAvgJaspelForMonth(int $month, int $year): float
    {
        $records = JumlahPasienHarian::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->get();
            
        if ($records->isEmpty()) {
            return 0;
        }
        
        $total = 0;
        $count = 0;
        
        foreach ($records as $record) {
            // Calculate procedure revenue
            $procedureRevenue = 0;
            if ($record->dokter_id) {
                $procedureRevenue = \App\Models\Tindakan::where('dokter_id', $record->dokter_id)
                    ->whereDate('tanggal_tindakan', $record->tanggal)
                    ->where('status_validasi', 'disetujui')
                    ->sum('jasa_dokter') ?? 0;
            }
            
            // Calculate patient jaspel
            $patientJaspel = 0;
            try {
                $jaspelService = app(\App\Services\Jaspel\UnifiedJaspelCalculationService::class);
                $patientCalculation = $jaspelService->calculateForPasienRecord($record);
                $patientJaspel = $patientCalculation['total'] ?? 0;
            } catch (\Exception $e) {
                $patientJaspel = 0;
            }
            
            $total += $procedureRevenue + $patientJaspel;
            $count++;
        }
        
        return $count > 0 ? $total / $count : 0;
    }
}