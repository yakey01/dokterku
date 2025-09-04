<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;
use App\Filament\Concerns\HasMonthlyArchive;
use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;
use Filament\Support\Enums\FontWeight;

class JumlahPasienHarianResource extends Resource
{
    use HasMonthlyArchive;
    
    protected static ?string $model = JumlahPasienHarian::class;
    
    // Configure monthly archive to use tanggal column
    public static function getArchiveDateColumn(): string
    {
        return 'tanggal';
    }

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'Input Jumlah Pasien';
    
    protected static ?string $navigationGroup = 'Manajemen Pasien';
    
    protected static ?string $modelLabel = 'Jumlah Pasien Harian';
    
    protected static ?string $pluralModelLabel = 'Data Jumlah Pasien Harian';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        // Always show in navigation for petugas
        return true;
    }

    public static function canViewAny(): bool
    {
        // Allow all petugas to view
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        // Allow all petugas to create
        return auth()->check();
    }
    
    public static function canEdit($record): bool
    {
        // Allow editing with better error handling
        if (!auth()->check()) {
            \Log::warning('canEdit failed: User not authenticated', [
                'record_id' => $record?->id,
                'session_id' => session()->getId()
            ]);
            return false;
        }
        
        $canEdit = $record->input_by === auth()->id();
        if (!$canEdit) {
            \Log::info('canEdit denied: User cannot edit record', [
                'record_id' => $record->id,
                'record_input_by' => $record->input_by,
                'auth_user_id' => auth()->id(),
                'auth_user_name' => auth()->user()?->name
            ]);
        }
        
        return $canEdit;
    }
    
    public static function canDelete($record): bool
    {
        // Allow deleting own records
        return auth()->check() && $record->input_by === auth()->id();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📊 Data Pasien Harian')
                    ->description('🩺 Input data jumlah pasien per hari untuk perhitungan jasa pelayanan medis')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                        return $rule->where('poli', $get('poli'))
                                                   ->where('shift', $get('shift'))
                                                   ->where('dokter_id', $get('dokter_id'));
                                    })
                                    ->helperText('Tanggal pelayanan pasien')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('poli')
                                    ->label('Poli')
                                    ->options([
                                        'umum' => 'Poli Umum',
                                        'gigi' => 'Poli Gigi',
                                    ])
                                    ->required()
                                    ->default('umum')
                                    ->reactive()
                                    ->helperText('Pilih poli pelayanan')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Select::make('shift_template_id')
                            ->label('Template Shift')
                            ->relationship('shiftTemplate', 'nama_shift')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                "{$record->nama_shift} ({$record->jam_masuk_format} - {$record->jam_pulang_format})"
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->helperText('Pilih template shift dari admin - sama seperti sistem admin')
                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                // Auto-sync shift name when template is selected
                                if ($state) {
                                    $shiftTemplate = \App\Models\ShiftTemplate::find($state);
                                    if ($shiftTemplate) {
                                        $set('shift', $shiftTemplate->nama_shift);
                                    }
                                }
                            }),

                        Forms\Components\Hidden::make('shift'),
                            // Shift name will be auto-filled from selected template

                        Forms\Components\Select::make('dokter_id')
                            ->label('Dokter Pelaksana')
                            ->relationship(
                                'dokter', 
                                'nama_lengkap',
                                fn (Builder $query, $get) => 
                                    $query->where('aktif', true) // Only active doctors
                                        ->when(
                                            $get('poli'),
                                            fn ($q, $poli) => $q->where('jabatan', 
                                                $poli === 'gigi' ? 'dokter_gigi' : 'dokter_umum'
                                            )
                                        )
                                        ->orderBy('nama_lengkap', 'asc')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                "{$record->nama_lengkap} - {$record->nik}" . 
                                ($record->nomor_sip ? " (SIP: {$record->nomor_sip})" : '')
                            )
                            ->searchable(['nama_lengkap', 'nik', 'nomor_sip'])
                            ->preload()
                            ->required()
                            ->reactive()
                            ->helperText('Data dokter diambil dari Manajemen Dokter')
                            ->placeholder('Ketik untuk mencari dokter...')
                            ->noSearchResultsMessage('Dokter tidak ditemukan. Pastikan dokter sudah terdaftar di Manajemen Dokter.'),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('jumlah_pasien_umum')
                                    ->label('Jumlah Pasien Umum')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(500)
                                    ->step(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, Forms\Set $set, $get) => 
                                        $set('total_pasien_display', ($state ?? 0) + ($get('jumlah_pasien_bpjs') ?? 0))
                                    )
                                    ->suffix('pasien')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('jumlah_pasien_bpjs')
                                    ->label('Jumlah Pasien BPJS')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(500)
                                    ->step(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, Forms\Set $set, $get) => 
                                        $set('total_pasien_display', ($get('jumlah_pasien_umum') ?? 0) + ($state ?? 0))
                                    )
                                    ->suffix('pasien')
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('total_pasien_display')
                                    ->label('Total Pasien')
                                    ->content(fn ($get) => 
                                        'Total: ' . (($get('jumlah_pasien_umum') ?? 0) + ($get('jumlah_pasien_bpjs') ?? 0)) . ' pasien'
                                    )
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan (Opsional)')
                            ->rows(2)
                            ->placeholder('Catatan tambahan jika diperlukan...')
                            ->maxLength(500),
                    ]),

                Forms\Components\Section::make('💰 Informasi Perhitungan Jaspel')
                    ->description('📋 Rumus dan estimasi perhitungan jasa pelayanan dokter')
                    ->schema([
                        Forms\Components\Placeholder::make('info_jaspel')
                            ->content(fn ($get, $record) => view('filament.petugas.components.jaspel-info-auto', [
                                'pasien_umum' => $get('jumlah_pasien_umum') ?? 0,
                                'pasien_bpjs' => $get('jumlah_pasien_bpjs') ?? 0,
                                'shift' => $get('shift'),
                                'record' => $record,
                            ])),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses(fn ($record) => 'elegant-black-table-row hover:elegant-black-table-row-hover')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('poli')
                    ->label('Poli')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'umum' => 'primary',
                        'gigi' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'umum' => 'Poli Umum',
                        'gigi' => 'Poli Gigi',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn ($record) => $record->shift_badge_color)
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Pagi' => '🌅 Pagi',
                        'Sore' => '🌇 Sore',
                        'Hari Libur Besar' => '🏖️ Hari Libur Besar',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('dokter.nama_lengkap')
                    ->label('Dokter Pelaksana')
                    ->searchable(['dokter.nama_lengkap', 'dokter.nik'])
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->description(fn ($record) => 
                        $record->dokter ? 
                        "NIK: {$record->dokter->nik}" . 
                        ($record->dokter->nomor_sip ? " | SIP: {$record->dokter->nomor_sip}" : '') : 
                        null
                    )
                    ->color(fn ($record) => 
                        $record->dokter?->aktif ? 'success' : 'danger'
                    ),

                Tables\Columns\TextColumn::make('jumlah_pasien_umum')
                    ->label('Pasien Umum')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('jumlah_pasien_bpjs')
                    ->label('Pasien BPJS')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_pasien')
                    ->label('Total Pasien')
                    ->getStateUsing(fn (JumlahPasienHarian $record): int => 
                        $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs
                    )
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('jaspel_rupiah')
                    ->label('Nominal Jaspel')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->badge()
                    ->color(fn ($record) => 
                        ($record->jaspel_rupiah ?? 0) > 0 ? 'success' : 'gray'
                    )
                    ->formatStateUsing(fn ($state) => 
                        $state ? 'Rp ' . number_format($state, 0, ',', '.') : 'Rp 0'
                    )
                    ->description(fn ($record) => 
                        $record->jaspel_rupiah > 0 ? '💰 Jaspel Terhitung' : '⏳ Belum Dihitung'
                    ),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'pending' => 'Menunggu',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Input Oleh')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => 
                        $record->input_by === auth()->id() ? 'success' : 'gray'
                    )
                    ->icon(fn ($record) => 
                        $record->input_by === auth()->id() ? 'heroicon-m-user-circle' : 'heroicon-m-users'
                    )
                    ->description(fn ($record) => 
                        $record->input_by === auth()->id() ? '✅ Data Anda' : '👥 Data Rekan'
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('data_scope')
                    ->label('Tampilkan Data')
                    ->options([
                        'all' => '👥 Semua Data (History Lengkap)',
                        'mine' => '✅ Data Saya Saja',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value']) && $data['value'] === 'mine') {
                            return $query->where('input_by', auth()->id());
                        }
                        return $query; // Show all data by default
                    }),

                SelectFilter::make('poli')
                    ->label('Filter Poli')
                    ->options([
                        'umum' => 'Poli Umum',
                        'gigi' => 'Poli Gigi',
                    ]),

                SelectFilter::make('shift')
                    ->label('Filter Shift')
                    ->options(JumlahPasienHarian::getShiftOptions()),

                SelectFilter::make('dokter')
                    ->label('Filter Dokter')
                    ->relationship('dokter', 'nama_lengkap', 
                        fn (Builder $query) => $query->where('aktif', true)->orderBy('nama_lengkap')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        "{$record->nama_lengkap} ({$record->nik})"
                    )
                    ->searchable()
                    ->preload(),

                // Monthly Archive Filters - defaults to current month
                ...static::getMonthlyArchiveFilters(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Data Pasien Harian')
            ->emptyStateDescription('Mulai input data jumlah pasien untuk perhitungan jaspel')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Input Data Pasien')
                    ->button()
                    ->color('warning'),
            ]);
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
            'index' => Pages\ListJumlahPasienHarians::route('/'),
            'create' => Pages\CreateJumlahPasienHarian::route('/create'),
            'edit' => Pages\EditJumlahPasienHarian::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}