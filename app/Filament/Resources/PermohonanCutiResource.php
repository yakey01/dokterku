<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermohonanCutiResource\Pages;
use App\Filament\Resources\PermohonanCutiResource\RelationManagers;
use App\Models\PermohonanCuti;
use App\Services\LeaveNotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PermohonanCutiResource extends Resource
{
    protected static ?string $model = PermohonanCuti::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Leave Management';
    
    protected static ?string $navigationLabel = 'Permohonan Cuti';
    
    protected static ?string $modelLabel = 'Permohonan Cuti';
    
    protected static ?string $pluralModelLabel = 'Permohonan Cuti';

    protected static ?int $navigationSort = 50;
    
    public static function canAccess(): bool
    {
        // All authenticated users can access leave management
        return auth()->check();
    }
    
    public static function canCreate(): bool
    {
        // Only non-admin users can create leave requests
        // Admin can only view and approve
        return !auth()->user()?->hasRole('admin') ?? true;
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // If user is not admin or manajer, only show their own leave requests
        if (!auth()->user()?->hasRole(['admin', 'manajer']) ?? true) {
            $query->where('pegawai_id', auth()->id());
        }
        
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pemohon')
                    ->schema([
                        Forms\Components\Select::make('pegawai_id')
                            ->label('Pegawai')
                            ->relationship('pegawai', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} - {$record->email}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id())
                            ->hidden(fn (): bool => !auth()->user()?->hasRole(['admin', 'manajer']))
                            ->disabled(fn (): bool => !auth()->user()?->hasRole(['admin', 'manajer'])),
                    ]),
                    
                Forms\Components\Section::make('Detail Cuti')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(now()->addDay())
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('tanggal_selesai', null))
                            ->rules(['after:today']),
                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (callable $get) => $get('tanggal_mulai') ? $get('tanggal_mulai') : now()->addDay())
                            ->reactive()
                            ->rules(['after_or_equal:tanggal_mulai']),
                        Forms\Components\Select::make('jenis_cuti')
                            ->label('Jenis Cuti')
                            ->options(fn () => \App\Models\LeaveType::active()->pluck('nama', 'nama')->toArray())
                            ->required()
                            ->native(false)
                            ->helperText('Pilih jenis cuti yang sesuai dengan keperluan Anda')
                            ->searchable(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Keterangan')
                    ->schema([
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Alasan/Keterangan')
                            ->placeholder('Jelaskan alasan pengajuan cuti...')
                            ->required()
                            ->rows(4),
                    ]),
                    
                Forms\Components\Section::make('Status Approval')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'Menunggu' => 'Menunggu',
                                'Disetujui' => 'Disetujui',
                                'Ditolak' => 'Ditolak'
                            ])
                            ->default('Menunggu')
                            ->disabled(fn (string $operation): bool => $operation === 'create')
                            ->required(),
                        Forms\Components\Select::make('disetujui_oleh')
                            ->label('Disetujui Oleh')
                            ->relationship('approver', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(fn (string $operation): bool => $operation === 'create'),
                        Forms\Components\Textarea::make('catatan_approval')
                            ->label('Catatan Approval')
                            ->placeholder('Catatan dari atasan...')
                            ->disabled(fn (string $operation): bool => $operation === 'create')
                            ->rows(3),
                    ])->columns(2)
                    ->hidden(fn (string $operation): bool => $operation === 'create'),
            ])
            ->mutateFormDataBeforeCreate(function (array $data): array {
                // Add current user as pegawai_id if not set
                if (!isset($data['pegawai_id'])) {
                    $data['pegawai_id'] = auth()->id();
                }
                
                // Add tanggal_pengajuan
                $data['tanggal_pengajuan'] = now();
                
                // Validate leave request
                $errors = PermohonanCuti::validateLeaveRequest($data);
                if (!empty($errors)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Validasi Gagal')
                        ->body(implode('<br>', $errors))
                        ->danger()
                        ->send();
                    
                    throw new \Exception(implode(' ', $errors));
                }
                
                return $data;
            })
            ->mutateFormDataBeforeSave(function (array $data): array {
                // Send notification for new requests
                if (!isset($data['id'])) { // New record
                    $leave = new PermohonanCuti($data);
                    LeaveNotificationService::notifyNewRequest($leave);
                }
                return $data;
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pegawai.name')
                    ->label('Nama Pegawai')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    ->copyable()
                    ->tooltip('Klik untuk copy nama pegawai')
                    ->description(fn (PermohonanCuti $record): string => $record->pegawai->email),
                    
                Tables\Columns\TextColumn::make('periode_cuti')
                    ->label('Tgl Awal - Tgl Akhir')
                    ->getStateUsing(fn (PermohonanCuti $record): string => 
                        $record->tanggal_mulai->format('d/m/Y') . ' - ' . $record->tanggal_selesai->format('d/m/Y')
                    )
                    ->sortable(['tanggal_mulai'])
                    ->searchable()
                    ->icon('heroicon-m-calendar-days')
                    ->color('info')
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('jenis_cuti')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cuti Tahunan' => 'success',
                        'Sakit' => 'danger',
                        'Izin' => 'warning',
                        'Dinas Luar' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Cuti Tahunan' => 'heroicon-m-calendar',
                        'Sakit' => 'heroicon-m-heart',
                        'Izin' => 'heroicon-m-clock',
                        'Dinas Luar' => 'heroicon-m-building-office',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('durasicuti')
                    ->label('Durasi')
                    ->suffix(' hari')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Alasan')
                    ->limit(30)
                    ->tooltip(fn (PermohonanCuti $record): string => $record->keterangan ?? '')
                    ->wrap()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Menunggu' => '🟡 Menunggu',
                        'Disetujui' => '🟢 Disetujui',
                        'Ditolak' => '🔴 Ditolak',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Menunggu' => 'warning',
                        'Disetujui' => 'success', 
                        'Ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                    
                    
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('Belum ada')
                    ->icon('heroicon-m-user-check')
                    ->color('success')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'Menunggu' => '🟡 Menunggu Persetujuan',
                        'Disetujui' => '🟢 Sudah Disetujui',
                        'Ditolak' => '🔴 Ditolak',
                    ])
                    ->default('Menunggu')
                    ->indicator('Status')
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('jenis_cuti')
                    ->label('Filter Jenis Permohonan')
                    ->options(fn () => \App\Models\LeaveType::active()->get()->mapWithKeys(function ($leaveType) {
                        $icon = match($leaveType->nama) {
                            'Cuti Tahunan' => '📅',
                            'Sakit' => '🏥',
                            'Izin' => '⏰',
                            'Dinas Luar' => '🏢',
                            'Ibadah' => '🕌',
                            'Melahirkan' => '👶',
                            'Besar' => '🎉',
                            default => '📝'
                        };
                        return [$leaveType->nama => $icon . ' ' . $leaveType->nama];
                    })->toArray())
                    ->indicator('Jenis')
                    ->multiple(),
                    
                Tables\Filters\Filter::make('bulan')
                    ->label('Bulan')
                    ->form([
                        Forms\Components\Select::make('bulan')
                            ->label('Pilih Bulan')
                            ->options([
                                '01' => 'Januari',
                                '02' => 'Februari', 
                                '03' => 'Maret',
                                '04' => 'April',
                                '05' => 'Mei',
                                '06' => 'Juni',
                                '07' => 'Juli',
                                '08' => 'Agustus',
                                '09' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember',
                            ])
                            ->placeholder('Semua bulan'),
                        Forms\Components\Select::make('tahun')
                            ->label('Tahun')
                            ->options(array_combine(
                                range(date('Y') - 2, date('Y') + 1),
                                range(date('Y') - 2, date('Y') + 1)
                            ))
                            ->default(date('Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['bulan'] ?? null,
                                fn (Builder $query, $bulan): Builder => $query->whereMonth('tanggal_mulai', $bulan),
                            )
                            ->when(
                                $data['tahun'] ?? null,
                                fn (Builder $query, $tahun): Builder => $query->whereYear('tanggal_mulai', $tahun),
                            );
                    })
                    ->indicator('Bulan/Tahun'),
                    
                Tables\Filters\SelectFilter::make('pegawai_id')
                    ->label('Unit/Pegawai')
                    ->relationship('pegawai', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} - {$record->email}")
                    ->searchable()
                    ->preload()
                    ->indicator('Pegawai')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->modalHeading('Detail Permohonan Cuti')
                    ->modalDescription(fn (PermohonanCuti $record): string => "Detail lengkap cuti untuk {$record->pegawai->name}")
                    ->modalContent(fn (PermohonanCuti $record): \Illuminate\View\View => view('filament.modals.leave-detail', [
                        'record' => $record,
                        'leaveHistory' => PermohonanCuti::where('pegawai_id', $record->pegawai_id)
                            ->where('status', 'Disetujui')
                            ->whereYear('tanggal_mulai', date('Y'))
                            ->get(),
                        'totalCutiTerpakai' => PermohonanCuti::where('pegawai_id', $record->pegawai_id)
                            ->where('status', 'Disetujui')
                            ->whereYear('tanggal_mulai', date('Y'))
                            ->sum('durasicuti'),
                        'sisaCuti' => 12 - PermohonanCuti::where('pegawai_id', $record->pegawai_id)
                            ->where('status', 'Disetujui')
                            ->whereYear('tanggal_mulai', date('Y'))
                            ->sum('durasicuti'),
                    ]))
                    ->modalWidth('4xl'),
                    
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('✅ Setujui')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function (PermohonanCuti $record, array $data): void {
                            $record->approve(auth()->id(), $data['catatan'] ?? null);
                            
                            // Send notification
                            LeaveNotificationService::notifyApproval($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Cuti Disetujui')
                                ->body("Permohonan cuti {$record->pegawai->name} telah disetujui")
                                ->success()
                                ->send();
                        })
                        ->form([
                            Forms\Components\Textarea::make('catatan')
                                ->label('Catatan Persetujuan')
                                ->placeholder('Catatan untuk pemohon (opsional)...')
                                ->rows(3),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Permohonan Cuti')
                        ->modalDescription(fn (PermohonanCuti $record): string => "Apakah Anda yakin ingin menyetujui cuti {$record->pegawai->name} dari {$record->tanggal_mulai->format('d/m/Y')} sampai {$record->tanggal_selesai->format('d/m/Y')}?")
                        ->modalSubmitActionLabel('Setujui Cuti')
                        ->visible(fn (PermohonanCuti $record): bool => auth()->user()->can('approve', $record)),
                        
                    Tables\Actions\Action::make('reject')
                        ->label('❌ Tolak')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(function (PermohonanCuti $record, array $data): void {
                            $record->reject(auth()->id(), $data['catatan']);
                            
                            // Send notification
                            LeaveNotificationService::notifyRejection($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Cuti Ditolak')
                                ->body("Permohonan cuti {$record->pegawai->name} telah ditolak")
                                ->warning()
                                ->send();
                        })
                        ->form([
                            Forms\Components\Textarea::make('catatan')
                                ->label('Alasan Penolakan')
                                ->placeholder('Jelaskan alasan penolakan...')
                                ->required()
                                ->rows(3),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Permohonan Cuti')
                        ->modalDescription(fn (PermohonanCuti $record): string => "Apakah Anda yakin ingin menolak cuti {$record->pegawai->name}?")
                        ->modalSubmitActionLabel('Tolak Cuti')
                        ->visible(fn (PermohonanCuti $record): bool => auth()->user()->can('approve', $record)),
                        
                    Tables\Actions\Action::make('add_comment')
                        ->label('Komentar')
                        ->icon('heroicon-m-chat-bubble-left-ellipsis')
                        ->color('warning')
                        ->action(function (PermohonanCuti $record, array $data): void {
                            $record->update([
                                'catatan_approval' => ($record->catatan_approval ? $record->catatan_approval . "\n\n" : '') . 
                                    "Komentar oleh " . auth()->user()->name . " (" . now()->format('d/m/Y H:i') . "):\n" . $data['komentar']
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Komentar Ditambahkan')
                                ->body('Komentar berhasil ditambahkan ke permohonan cuti')
                                ->success()
                                ->send();
                        })
                        ->form([
                            Forms\Components\Textarea::make('komentar')
                                ->label('Komentar Tambahan')
                                ->placeholder('Tulis komentar atau pertanyaan...')
                                ->required()
                                ->rows(3),
                        ])
                        ->modalHeading('Tambah Komentar')
                        ->modalSubmitActionLabel('Tambah Komentar')
                        ->visible(fn (PermohonanCuti $record): bool => auth()->user()->can('addComment', $record)),
                ])
                ->label('Aksi')
                ->color('primary')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm'),
                
                Tables\Actions\EditAction::make()
                    ->visible(fn (PermohonanCuti $record): bool => auth()->user()->can('update', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasRole('admin')),
                        
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('✅ Setujui Semua')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $approved = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'Menunggu' && auth()->user()->can('approve', $record)) {
                                    $record->approve(auth()->id(), 'Disetujui secara massal');
                                    $approved++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Persetujuan Massal')
                                ->body("{$approved} permohonan cuti telah disetujui")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(fn (): bool => auth()->user()?->hasRole(['admin', 'manajer'])),
                ]),
            ])
            ->defaultSort('tanggal_pengajuan', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListPermohonanCutis::route('/'),
            'create' => Pages\CreatePermohonanCuti::route('/create'),
            'edit' => Pages\EditPermohonanCuti::route('/{record}/edit'),
        ];
    }
}
