<?php

namespace App\Filament\Bendahara\Resources;

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

class ValidasiJumlahPasienResource extends Resource
{
    protected static ?string $model = JumlahPasienHarian::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = 'Validasi Data';

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
                            ->options([
                                'pending' => 'Menunggu Validasi',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                                'need_revision' => 'Perlu Revisi',
                            ])
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

                Tables\Columns\TextColumn::make('jaspel_rupiah')
                    ->label('💰 Jaspel')
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
                    ->color(fn ($state) => !is_null($state) && $state > 0 ? 'success' : 'gray')
                    ->icon('heroicon-o-banknotes')
                    ->description('Jasa Pelayanan')
                    ->toggleable(isToggledHiddenByDefault: false),

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
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Tanggal Dari'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Tanggal Sampai'),
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

                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'need_revision' => 'Perlu Revisi',
                    ]),

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
            ])
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
            ->headerActions([
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
                            'total_jaspel_today' => JumlahPasienHarian::whereDate('tanggal', $today)
                                ->sum('jaspel_rupiah') ?? 0,
                            'avg_jaspel_monthly' => JumlahPasienHarian::whereMonth('tanggal', now()->month)
                                ->avg('jaspel_rupiah') ?? 0,
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
        return static::getModel()::where('status_validasi', 'pending')->count();
    }

    public static function canAccess(): bool
    {
        return true; // Override access control for bendahara
    }

    public static function getPages(): array
    {
        return [
            'index' => ValidasiJumlahPasienResource\Pages\ListValidasiJumlahPasien::route('/'),
        ];
    }
}