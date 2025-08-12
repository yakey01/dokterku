<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\ValidasiPendapatanResource\Pages;
use App\Models\Pendapatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ValidasiPendapatanResource extends Resource
{
    protected static ?string $model = Pendapatan::class;

    protected static ?string $navigationIcon = null;
    
    protected static ?string $navigationLabel = 'Validasi Pendapatan';
    
    protected static ?string $navigationGroup = '💰 Manajemen Transaksi';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('keterangan')
                            ->label('Keterangan Pendapatan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Pembayaran konsultasi, Tindakan medis, dll'),
                        
                        Forms\Components\TextInput::make('nominal')
                            ->label('Jumlah (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('100000')
                            ->minValue(0),
                        
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Input')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                        
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->maxLength(500)
                            ->placeholder('Catatan atau keterangan tambahan (opsional)')
                            ->columnSpanFull(),
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
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    }),
                
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
                
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Pendapatan $record): bool => $record->status_validasi === 'pending'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Pendapatan $record): bool => $record->status_validasi === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::check() && Auth::user()->can('delete_any_pendapatan')),
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('input_by', Auth::id());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendapatans::route('/'),
            'create' => Pages\CreatePendapatan::route('/create'),
            'view' => Pages\ViewPendapatan::route('/{record}'),
            'edit' => Pages\EditPendapatan::route('/{record}/edit'),
        ];
    }
}