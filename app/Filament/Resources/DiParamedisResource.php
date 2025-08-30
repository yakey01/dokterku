<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiParamedisResource\Pages;
use App\Filament\Resources\DiParamedisResource\RelationManagers;
use App\Models\DiParamedis;
use App\Models\Pegawai;
use App\Models\JadwalJaga;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DiParamedisResource extends Resource
{
    protected static ?string $model = DiParamedis::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'DI Paramedis';
    
    protected static ?string $modelLabel = 'Daftar Isian Paramedis';
    
    protected static ?string $pluralModelLabel = 'Daftar Isian Paramedis';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\Select::make('pegawai_id')
                            ->label('Paramedis')
                            ->relationship('pegawai', 'nama_lengkap', fn (Builder $query) => 
                                $query->where('jenis_pegawai', 'Paramedis')
                                      ->where('aktif', true)
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->user()?->pegawai_id)
                            ->disabled(fn () => !auth()->user()?->hasRole(['admin', 'manajer']))
                            ->columnSpan(2),
                            
                        Forms\Components\Select::make('jadwal_jaga_id')
                            ->label('Jadwal Jaga')
                            ->relationship('jadwalJaga', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                "{$record->tanggal} - {$record->shift} ({$record->jam_mulai} - {$record->jam_selesai})"
                            )
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                            
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Tugas')
                            ->required()
                            ->native(false)
                            ->default(now()),
                            
                        Forms\Components\TimePicker::make('jam_mulai')
                            ->label('Jam Mulai')
                            ->required()
                            ->seconds(false)
                            ->default(now()->format('H:i')),
                            
                        Forms\Components\TimePicker::make('jam_selesai')
                            ->label('Jam Selesai')
                            ->seconds(false)
                            ->after('jam_mulai'),
                            
                        Forms\Components\Select::make('shift')
                            ->label('Shift')
                            ->options([
                                'Pagi' => 'Pagi',
                                'Siang' => 'Siang',
                                'Malam' => 'Malam',
                            ])
                            ->required(),
                            
                        Forms\Components\TextInput::make('lokasi_tugas')
                            ->label('Lokasi Tugas')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                    ])
                    ->columns(4),
                    
                Forms\Components\Section::make('Kegiatan Pelayanan Pasien')
                    ->schema([
                        Forms\Components\TextInput::make('jumlah_pasien_dilayani')
                            ->label('Jumlah Pasien Dilayani')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                            
                        Forms\Components\TextInput::make('jumlah_tindakan_medis')
                            ->label('Jumlah Tindakan Medis')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                            
                        Forms\Components\TextInput::make('jumlah_observasi_pasien')
                            ->label('Jumlah Observasi Pasien')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                            
                        Forms\Components\TextInput::make('jumlah_kasus_emergency')
                            ->label('Jumlah Kasus Emergency')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(4),
                    
                Forms\Components\Section::make('Detail Tindakan Medis')
                    ->schema([
                        Forms\Components\Repeater::make('tindakan_medis')
                            ->label('Tindakan Medis yang Dilakukan')
                            ->schema([
                                Forms\Components\TextInput::make('nama_tindakan')
                                    ->label('Nama Tindakan')
                                    ->required(),
                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->default(1),
                                Forms\Components\Textarea::make('keterangan')
                                    ->label('Keterangan')
                                    ->rows(2),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Tindakan')
                            ->columnSpan('full'),
                            
                        Forms\Components\Repeater::make('obat_diberikan')
                            ->label('Obat yang Diberikan')
                            ->schema([
                                Forms\Components\TextInput::make('nama_obat')
                                    ->label('Nama Obat')
                                    ->required(),
                                Forms\Components\TextInput::make('dosis')
                                    ->label('Dosis'),
                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric(),
                                Forms\Components\TextInput::make('cara_pemberian')
                                    ->label('Cara Pemberian'),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Obat')
                            ->columnSpan('full'),
                            
                        Forms\Components\Repeater::make('alat_medis_digunakan')
                            ->label('Alat Medis yang Digunakan')
                            ->schema([
                                Forms\Components\TextInput::make('nama_alat')
                                    ->label('Nama Alat')
                                    ->required(),
                                Forms\Components\TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->default(1),
                                Forms\Components\Textarea::make('kondisi')
                                    ->label('Kondisi/Catatan')
                                    ->rows(2),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Alat Medis')
                            ->columnSpan('full'),
                    ]),
                    
                Forms\Components\Section::make('Laporan dan Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('catatan_kasus_emergency')
                            ->label('Catatan Kasus Emergency')
                            ->rows(3)
                            ->columnSpan('full')
                            ->visible(fn (Forms\Get $get) => $get('jumlah_kasus_emergency') > 0),
                            
                        Forms\Components\RichEditor::make('laporan_kegiatan')
                            ->label('Laporan Kegiatan Harian')
                            ->columnSpan('full')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ]),
                            
                        Forms\Components\Textarea::make('kendala_hambatan')
                            ->label('Kendala/Hambatan')
                            ->rows(3)
                            ->columnSpan('full'),
                            
                        Forms\Components\Textarea::make('saran_perbaikan')
                            ->label('Saran Perbaikan')
                            ->rows(3)
                            ->columnSpan('full'),
                    ]),
                    
                Forms\Components\Section::make('Status Persetujuan')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'submitted' => 'Menunggu Persetujuan',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])
                            ->required()
                            ->disabled()
                            ->default('draft'),
                            
                        Forms\Components\Select::make('approved_by')
                            ->label('Disetujui Oleh')
                            ->relationship('approvedBy', 'name')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['approved', 'rejected'])),
                            
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Waktu Persetujuan')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['approved', 'rejected'])),
                            
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => $get('status') === 'rejected')
                            ->columnSpan('full'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record !== null),
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
                    
                Tables\Columns\TextColumn::make('pegawai.nama_lengkap')
                    ->label('Paramedis')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Siang' => 'warning',
                        'Malam' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('lokasi_tugas')
                    ->label('Lokasi')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('jam_mulai')
                    ->label('Jam Kerja')
                    ->formatStateUsing(fn ($record) => 
                        $record->jam_mulai . ' - ' . ($record->jam_selesai ?? 'Belum selesai')
                    ),
                    
                Tables\Columns\TextColumn::make('total_activities')
                    ->label('Total Aktivitas')
                    ->state(fn ($record) => $record->total_activities)
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('jumlah_pasien_dilayani')
                    ->label('Pasien')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('jumlah_tindakan_medis')
                    ->label('Tindakan')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\IconColumn::make('jumlah_kasus_emergency')
                    ->label('Emergency')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('danger')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Draft',
                        'submitted' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Menunggu Persetujuan',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
                    
                Tables\Filters\SelectFilter::make('shift')
                    ->label('Shift')
                    ->options([
                        'Pagi' => 'Pagi',
                        'Siang' => 'Siang',
                        'Malam' => 'Malam',
                    ]),
                    
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
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
                    
                Tables\Filters\SelectFilter::make('pegawai_id')
                    ->label('Paramedis')
                    ->relationship('pegawai', 'nama_lengkap')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                    
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->visible(fn ($record) => $record->is_editable),
                    
                Tables\Actions\Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Submit Daftar Isian?')
                    ->modalDescription('Setelah disubmit, data tidak dapat diedit lagi tanpa persetujuan.')
                    ->modalSubmitActionLabel('Ya, Submit')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $record->submit();
                        Notification::make()
                            ->title('Data berhasil disubmit')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => 
                        $record->can_be_approved && 
                        auth()->user()->hasRole(['admin', 'manajer'])
                    )
                    ->action(function ($record) {
                        $record->approve(auth()->id());
                        Notification::make()
                            ->title('Data berhasil disetujui')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => 
                        $record->can_be_approved && 
                        auth()->user()->hasRole(['admin', 'manajer'])
                    )
                    ->action(function ($record, array $data) {
                        $record->reject(auth()->id(), $data['rejection_reason']);
                        Notification::make()
                            ->title('Data berhasil ditolak')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('admin')),
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
            'index' => Pages\ListDiParamedis::route('/'),
            'create' => Pages\CreateDiParamedis::route('/create'),
            'view' => Pages\ViewDiParamedis::route('/{record}'),
            'edit' => Pages\EditDiParamedis::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Non-admin users can only see their own records
        if (!auth()->user()->hasRole(['admin', 'manajer'])) {
            $query->where('pegawai_id', auth()->user()->pegawai_id);
        }
        
        return $query;
    }
    
    public static function canCreate(): bool
    {
        // Only paramedis can create their own DI
        return auth()->user()?->pegawai?->jenis_pegawai === 'Paramedis';
    }
    
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // Can edit if draft/rejected and own record, or admin/manajer
        if (auth()->user()->hasRole(['admin', 'manajer'])) {
            return true;
        }
        
        return $record->is_editable && $record->pegawai_id === auth()->user()->pegawai_id;
    }
    
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // Only admin can delete
        return auth()->user()->hasRole('admin');
    }
    
    public static function getNavigationBadge(): ?string
    {
        // Show count of pending approvals for admin/manajer
        if (auth()->user()->hasRole(['admin', 'manajer'])) {
            $count = static::getModel()::where('status', 'submitted')->count();
            return $count > 0 ? (string) $count : null;
        }
        
        return null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
