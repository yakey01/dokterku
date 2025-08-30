<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JenisTindakanResource\Pages;
use App\Models\JenisTindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JenisTindakanResource extends Resource
{
    protected static ?string $model = JenisTindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 71;

    protected static ?string $modelLabel = 'Jenis Tindakan';

    protected static ?string $pluralModelLabel = 'Jenis Tindakan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('kode')
                            ->label('Kode Tindakan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Auto-generated: TDK-001, TDK-002, dst.')
                            ->helperText('Kode akan dibuat otomatis dengan format TDK-XXX. Anda dapat mengedit jika diperlukan.')
                            ->maxLength(20)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Tindakan')
                            ->required()
                            ->placeholder('Contoh: Konsultasi Umum')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori')
                            ->required()
                            ->options([
                                'konsultasi' => 'Konsultasi',
                                'pemeriksaan' => 'Pemeriksaan',
                                'tindakan' => 'Tindakan',
                                'obat' => 'Obat',
                                'lainnya' => 'Lainnya',
                            ])
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->placeholder('Deskripsi singkat tentang tindakan ini...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Struktur Tarif')
                    ->schema([
                        Forms\Components\TextInput::make('tarif')
                            ->label('Tarif Total (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $tarif = floatval($state ?? 0);
                                $persentaseJaspel = floatval($get('persentase_jaspel') ?? 40);
                                
                                $jasaPetugas = $tarif * ($persentaseJaspel / 100);
                                $pendapatanKlinik = $tarif - $jasaPetugas;
                                
                                $set('jasa_petugas_calculated', number_format($jasaPetugas, 0, ',', '.'));
                                $set('pendapatan_klinik_calculated', number_format($pendapatanKlinik, 0, ',', '.'));
                            }),

                        Forms\Components\TextInput::make('persentase_jaspel')
                            ->label('Persentase Jaspel (%)')
                            ->numeric()
                            ->default(40)
                            ->suffix('%')
                            ->placeholder('40')
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $tarif = floatval($get('tarif') ?? 0);
                                $persentaseJaspel = floatval($state ?? 40);
                                
                                $jasaPetugas = $tarif * ($persentaseJaspel / 100);
                                $pendapatanKlinik = $tarif - $jasaPetugas;
                                
                                $set('jasa_petugas_calculated', number_format($jasaPetugas, 0, ',', '.'));
                                $set('pendapatan_klinik_calculated', number_format($pendapatanKlinik, 0, ',', '.'));
                            }),

                        Forms\Components\TextInput::make('jasa_petugas_calculated')
                            ->label('Jaspel Petugas (Rp)')
                            ->prefix('Rp')
                            ->disabled()
                            ->placeholder('0')
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('pendapatan_klinik_calculated')
                            ->label('Pendapatan Klinik (Rp)')
                            ->prefix('Rp')
                            ->disabled()
                            ->placeholder('0')
                            ->dehydrated(false),
                    ])
                    ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Tindakan')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'konsultasi' => 'info',
                        'pemeriksaan' => 'warning',
                        'tindakan' => 'success',
                        'obat' => 'danger',
                        'lainnya' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'konsultasi' => 'Konsultasi',
                        'pemeriksaan' => 'Pemeriksaan',
                        'tindakan' => 'Tindakan',
                        'obat' => 'Obat',
                        'lainnya' => 'Lainnya',
                    }),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('persentase_jaspel')
                    ->label('% Jaspel')
                    ->suffix('%')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('jasa_dokter')
                    ->label('Jasa Dokter')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jasa_paramedis')
                    ->label('Jasa Paramedis')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jasa_non_paramedis')
                    ->label('Jasa Non-Paramedis')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'konsultasi' => 'Konsultasi',
                        'pemeriksaan' => 'Pemeriksaan',
                        'tindakan' => 'Tindakan',
                        'obat' => 'Obat',
                        'lainnya' => 'Lainnya',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label('Toggle Status')
                    ->icon(fn (JenisTindakan $record) => $record->is_active ? 'heroicon-m-eye-slash' : 'heroicon-m-eye')
                    ->color(fn (JenisTindakan $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (JenisTindakan $record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation()
                    ->modalDescription(fn (JenisTindakan $record) => 
                        $record->is_active ? 
                        'Apakah Anda yakin ingin menonaktifkan tindakan ini?' : 
                        'Apakah Anda yakin ingin mengaktifkan tindakan ini?'
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-m-eye')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => true])))
                        ->requiresConfirmation()
                        ->modalDescription('Apakah Anda yakin ingin mengaktifkan semua tindakan yang dipilih?'),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-m-eye-slash')
                        ->color('warning')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => false])))
                        ->requiresConfirmation()
                        ->modalDescription('Apakah Anda yakin ingin menonaktifkan semua tindakan yang dipilih?'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJenisTindakan::route('/'),
            'create' => Pages\CreateJenisTindakan::route('/create'),
            'edit' => Pages\EditJenisTindakan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}