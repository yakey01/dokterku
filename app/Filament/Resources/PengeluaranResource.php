<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengeluaranResource\Pages;
use App\Filament\Resources\PengeluaranResource\RelationManagers;
use App\Models\Pengeluaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class PengeluaranResource extends Resource
{
    protected static ?string $model = Pengeluaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    
    protected static ?string $navigationGroup = 'Financial Management';
    
    protected static ?string $modelLabel = 'Pengeluaran';
    
    protected static ?string $pluralModelLabel = 'Master Pengeluaran';

    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Master Pengeluaran')
                    ->schema([
                        Forms\Components\TextInput::make('kode_pengeluaran')
                            ->label('Kode Pengeluaran')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Auto-generated: PNG-0001')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Kode akan dibuat otomatis saat menyimpan data'),

                        Forms\Components\TextInput::make('nama_pengeluaran')
                            ->label('Nama Pengeluaran')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Alat Tulis Kantor')
                            ->unique(ignoreRecord: true)
                            ->helperText('Nama pengeluaran harus unik dan tidak boleh sama dengan yang sudah ada')
                            ->validationAttribute('nama pengeluaran'),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori')
                            ->options([
                                'konsumsi' => 'Konsumsi / Minuman / Makanan',
                                'alat_bahan' => 'Belanja Alat & Bahan Habis Pakai',
                                'akomodasi' => 'Akomodasi & Transportasi',
                                'medis' => 'Obat & Alkes',
                                'honor' => 'Honor & Fee',
                                'promosi' => 'Promosi & Kegiatan',
                                'operasional' => 'Operasional',
                                'maintenance' => 'Maintenance',
                                'administrasi' => 'Administrasi',
                                'lainnya' => 'Lainnya',
                            ])
                            ->required()
                            ->searchable(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->maxLength(500)
                            ->placeholder('Deskripsi lengkap pengeluaran ini')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Data Sistem')
                    ->schema([
                        Forms\Components\Hidden::make('input_by')
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('tanggal')
                            ->default(fn () => now()),

                        Forms\Components\Hidden::make('nominal')
                            ->default(0),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_pengeluaran')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nama_pengeluaran')
                    ->label('Nama Pengeluaran')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->nama_pengeluaran),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'konsumsi' => 'success',
                        'alat_bahan' => 'warning',
                        'akomodasi' => 'info',
                        'medis' => 'danger',
                        'honor' => 'primary',
                        'promosi' => 'secondary',
                        'operasional' => 'success',
                        'maintenance' => 'warning',
                        'administrasi' => 'info',
                        'lainnya' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'konsumsi' => 'Konsumsi',
                        'alat_bahan' => 'Alat & Bahan',
                        'akomodasi' => 'Akomodasi',
                        'medis' => 'Medis',
                        'honor' => 'Honor & Fee',
                        'promosi' => 'Promosi',
                        'operasional' => 'Operasional',
                        'maintenance' => 'Maintenance',
                        'administrasi' => 'Administrasi',
                        'lainnya' => 'Lainnya',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->keterangan)
                    ->placeholder('Tidak ada keterangan'),


                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'konsumsi' => 'Konsumsi',
                        'alat_bahan' => 'Alat & Bahan',
                        'akomodasi' => 'Akomodasi',
                        'medis' => 'Medis',
                        'honor' => 'Honor & Fee',
                        'promosi' => 'Promosi',
                        'operasional' => 'Operasional',
                        'maintenance' => 'Maintenance',
                        'administrasi' => 'Administrasi',
                        'lainnya' => 'Lainnya',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->color('warning')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->icon('heroicon-o-trash'),
                    
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPengeluarans::route('/'),
            'create' => Pages\CreatePengeluaran::route('/create'),
            'view' => Pages\ViewPengeluaran::route('/{record}'),
            'edit' => Pages\EditPengeluaran::route('/{record}/edit'),
        ];
    }
}
