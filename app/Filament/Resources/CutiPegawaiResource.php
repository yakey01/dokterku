<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CutiPegawaiResource\Pages;
use App\Models\CutiPegawai;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CutiPegawaiResource extends Resource
{
    protected static ?string $model = CutiPegawai::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Leave Management';
    
    protected static ?string $navigationLabel = 'Cuti Pegawai';
    
    protected static ?string $modelLabel = 'Cuti Pegawai';
    
    protected static ?string $pluralModelLabel = 'Permohonan Cuti';

    protected static ?int $navigationSort = 51;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Permohonan Cuti')
                    ->schema([
                        Forms\Components\Select::make('pegawai_id')
                            ->label('Nama Pegawai')
                            ->relationship('pegawai', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih pegawai'),

                        Forms\Components\DatePicker::make('tanggal_awal')
                            ->label('Tanggal Awal')
                            ->required()
                            ->native(false)
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                $tanggalAkhir = $get('tanggal_akhir');
                                if ($state && $tanggalAkhir) {
                                    $jumlahHari = \Carbon\Carbon::parse($state)->diffInDays(\Carbon\Carbon::parse($tanggalAkhir)) + 1;
                                    $set('jumlah_hari', $jumlahHari);
                                }
                            })
                            ->reactive(),

                        Forms\Components\DatePicker::make('tanggal_akhir')
                            ->label('Tanggal Akhir')
                            ->required()
                            ->native(false)
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                $tanggalAwal = $get('tanggal_awal');
                                if ($state && $tanggalAwal) {
                                    $jumlahHari = \Carbon\Carbon::parse($tanggalAwal)->diffInDays(\Carbon\Carbon::parse($state)) + 1;
                                    $set('jumlah_hari', $jumlahHari);
                                }
                            })
                            ->reactive(),

                        Forms\Components\TextInput::make('jumlah_hari')
                            ->label('Jumlah Hari')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),

                        Forms\Components\Textarea::make('alasan')
                            ->label('Alasan Cuti')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Jelaskan alasan pengajuan cuti...')
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => Auth::id()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Validasi')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'menunggu' => 'Menunggu',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                            ])
                            ->default('menunggu')
                            ->disabled(fn ($record) => !$record) // Disable for new records
                            ->visible(fn ($record) => $record), // Only show when editing

                        Forms\Components\Textarea::make('komentar_admin')
                            ->label('Komentar Admin')
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('Tambahkan komentar validasi...')
                            ->visible(fn ($record) => $record)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record), // Only show when editing
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pegawai.name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tanggal_awal')
                    ->label('Tgl Awal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_akhir')
                    ->label('Tgl Akhir')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_hari')
                    ->label('Hari')
                    ->suffix(' hari')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('alasan')
                    ->label('Alasan')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->alasan),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'menunggu' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => ucfirst($state)
                    }),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),

                Tables\Filters\Filter::make('tanggal_cuti')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_awal', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_akhir', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('bulan_ini')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('tanggal_awal', now()->month)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(function (CutiPegawai $record, array $data) {
                        $record->update([
                            'status' => 'disetujui',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                            'komentar_admin' => $data['komentar'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Cuti pegawai disetujui')
                            ->success()
                            ->send();
                    })
                    ->form([
                        Forms\Components\Textarea::make('komentar')
                            ->label('Komentar')
                            ->placeholder('Tambahkan komentar (opsional)'),
                    ])
                    ->visible(fn (CutiPegawai $record): bool => $record->status === 'menunggu'),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->action(function (CutiPegawai $record, array $data) {
                        $record->update([
                            'status' => 'ditolak',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                            'komentar_admin' => $data['komentar'] ?? 'Cuti ditolak',
                        ]);

                        Notification::make()
                            ->title('Cuti pegawai ditolak')
                            ->warning()
                            ->send();
                    })
                    ->form([
                        Forms\Components\Textarea::make('komentar')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Jelaskan alasan penolakan...'),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (CutiPegawai $record): bool => $record->status === 'menunggu'),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_all')
                        ->label('Setujui Semua')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'menunggu') {
                                    $record->update([
                                        'status' => 'disetujui',
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title('Semua cuti berhasil disetujui')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'menunggu')->count();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCutiPegawais::route('/'),
            'create' => Pages\CreateCutiPegawai::route('/create'),
            'view' => Pages\ViewCutiPegawai::route('/{record}'),
            'edit' => Pages\EditCutiPegawai::route('/{record}/edit'),
        ];
    }
}