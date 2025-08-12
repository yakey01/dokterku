<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;
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
    protected static ?string $model = JumlahPasienHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'Input Jumlah Pasien';
    
    protected static ?string $navigationGroup = '👥 Manajemen Pasien';
    
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
        // Allow editing own records
        return auth()->check() && $record->input_by === auth()->id();
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
                Forms\Components\Section::make('📋 Data Pasien Harian')
                    ->description('Input data jumlah pasien per hari untuk perhitungan jaspel')
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

                        Forms\Components\Select::make('dokter_id')
                            ->label('Dokter Pelaksana')
                            ->relationship('dokter', 'nama_lengkap', fn (Builder $query, $get) => 
                                $query->when(
                                    $get('poli'),
                                    fn ($q, $poli) => $q->where('spesialisasi', $poli === 'gigi' ? 'Dokter Gigi' : 'Dokter Umum')
                                )
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->helperText('Pilih dokter yang bertugas')
                            ->placeholder('Pilih dokter pelaksana'),

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
                                        '🧮 Total: ' . (($get('jumlah_pasien_umum') ?? 0) + ($get('jumlah_pasien_bpjs') ?? 0)) . ' pasien'
                                    )
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan (Opsional)')
                            ->rows(2)
                            ->placeholder('Catatan tambahan jika diperlukan...')
                            ->maxLength(500),
                    ]),

                Forms\Components\Section::make('💡 Informasi Perhitungan Jaspel')
                    ->description('Rumus perhitungan jasa pelayanan')
                    ->schema([
                        Forms\Components\Placeholder::make('info_jaspel')
                            ->content(fn ($get) => view('filament.petugas.components.jaspel-info', [
                                'pasien_umum' => $get('jumlah_pasien_umum') ?? 0,
                                'pasien_bpjs' => $get('jumlah_pasien_bpjs') ?? 0,
                            ])),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                        'umum' => '🏥 Poli Umum',
                        'gigi' => '🦷 Poli Gigi',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('dokter.nama_lengkap')
                    ->label('Dokter Pelaksana')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

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
                        'approved' => '✅ Disetujui',
                        'rejected' => '❌ Ditolak',
                        'pending' => '⏳ Menunggu',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Input Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('poli')
                    ->label('Filter Poli')
                    ->options([
                        'umum' => 'Poli Umum',
                        'gigi' => 'Poli Gigi',
                    ]),

                SelectFilter::make('dokter')
                    ->label('Filter Dokter')
                    ->relationship('dokter', 'nama_lengkap')
                    ->searchable()
                    ->preload(),

                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            ->where('input_by', auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}