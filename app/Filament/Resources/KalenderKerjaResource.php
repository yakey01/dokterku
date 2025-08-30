<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KalenderKerjaResource\Pages;
use App\Filament\Resources\KalenderKerjaResource\RelationManagers;
use App\Models\KalenderKerja;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class KalenderKerjaResource extends Resource
{
    protected static ?string $model = KalenderKerja::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Schedule Management';
    
    protected static ?string $navigationLabel = 'Kalender Kerja';
    
    protected static ?string $modelLabel = 'Jadwal Kerja';
    
    protected static ?string $pluralModelLabel = 'Kalender Kerja';

    protected static ?int $navigationSort = 32;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Jadwal Kerja')
                    ->schema([
                        Forms\Components\Select::make('pegawai_id')
                            ->label('Nama Pegawai')
                            ->relationship('pegawai', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih pegawai'),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->default(now())
                            ->native(false),

                        Forms\Components\Select::make('shift')
                            ->label('Shift')
                            ->options([
                                'Pagi' => 'Pagi',
                                'Sore' => 'Sore', 
                                'Malam' => 'Malam',
                                'Off' => 'Off',
                            ])
                            ->required()
                            ->placeholder('Pilih shift'),

                        Forms\Components\TextInput::make('unit')
                            ->label('Unit/Instalasi')
                            ->placeholder('Contoh: IGD, Rawat Inap, dll')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Keterangan tambahan (opsional)')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => Auth::id()),
                    ])
                    ->columns(2),
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
                    ->searchable(),

                Tables\Columns\TextColumn::make('pegawai.name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('shift')
                    ->label('Shift')
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Sore' => 'warning', 
                        'Malam' => 'primary',
                        'Off' => 'gray',
                        default => 'gray'
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Pagi' => 'heroicon-o-sun',
                        'Sore' => 'heroicon-o-sun',
                        'Malam' => 'heroicon-o-moon',
                        'Off' => 'heroicon-o-x-mark',
                        default => 'heroicon-o-clock'
                    }),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit/Instalasi')
                    ->searchable()
                    ->placeholder('Tidak ada unit')
                    ->limit(20),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->placeholder('Tidak ada keterangan')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('shift')
                    ->options([
                        'Pagi' => 'Pagi',
                        'Sore' => 'Sore',
                        'Malam' => 'Malam', 
                        'Off' => 'Off',
                    ]),

                Tables\Filters\SelectFilter::make('unit')
                    ->options([
                        'IGD' => 'IGD',
                        'Rawat Inap' => 'Rawat Inap',
                        'Poliklinik' => 'Poliklinik',
                        'Farmasi' => 'Farmasi',
                        'Laboratorium' => 'Laboratorium',
                    ])
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc');
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
            'index' => Pages\ListKalenderKerjas::route('/'),
            'create' => Pages\CreateKalenderKerja::route('/create'),
            'view' => Pages\ViewKalenderKerja::route('/{record}'),
            'edit' => Pages\EditKalenderKerja::route('/{record}/edit'),
        ];
    }
}
