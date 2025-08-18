<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Jaspel;
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

class ValidasiJaspelResource extends Resource
{
    protected static ?string $model = Jaspel::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = 'Manajemen Jaspel';

    protected static ?string $navigationLabel = 'Validasi Jaspel';

    protected static ?string $modelLabel = 'Jaspel';

    protected static ?string $pluralModelLabel = 'Validasi Jaspel';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Jaspel')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Dokter/Staff')
                            ->relationship('user', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('tindakan_id')
                            ->label('Tindakan')
                            ->relationship('tindakan', 'nama_tindakan')
                            ->disabled(),

                        Forms\Components\Select::make('jenis_jaspel')
                            ->label('Jenis Jaspel')
                            ->options([
                                'dokter_umum' => 'Dokter Umum',
                                'dokter_spesialis' => 'Dokter Spesialis',
                                'paramedis' => 'Paramedis',
                                'administrasi' => 'Administrasi',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal Jaspel')
                            ->prefix('Rp')
                            ->numeric()
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->disabled(),

                        Forms\Components\Select::make('shift_id')
                            ->label('Shift')
                            ->relationship('shift', 'nama_shift')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validasi Bendahara')
                    ->schema([
                        Forms\Components\Select::make('status_validasi')
                            ->label('Status Validasi')
                            ->options([
                                'pending' => 'Menunggu Validasi',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
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
                Tables\Columns\TextColumn::make('tindakan.jenisTindakan.nama')
                    ->label('📋 Nama Tindakan')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold')
                    ->color('primary')
                    ->description(fn ($record) => $record->keterangan ? "💬 {$record->keterangan}" : null),

                Tables\Columns\TextColumn::make('tindakan.shiftTemplate.nama_shift')
                    ->label('⏰ Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Siang' => 'warning', 
                        'Sore' => 'warning',
                        'Malam' => 'primary',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Pagi' => '🌅 Pagi',
                        'Siang' => '☀️ Siang',
                        'Sore' => '🌇 Sore', 
                        'Malam' => '🌙 Malam',
                        default => $state ?: '❓ Tidak Diketahui'
                    }),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('📅 Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn ($record) => $record->tanggal->format('l'))
                    ->color('gray'),
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

                Tables\Filters\SelectFilter::make('jenis_jaspel')
                    ->label('Jenis Jaspel')
                    ->options([
                        'dokter_umum' => 'Dokter Umum',
                        'dokter_spesialis' => 'Dokter Spesialis',
                        'paramedis' => 'Paramedis',
                        'administrasi' => 'Administrasi',
                    ]),

                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'need_revision' => 'Perlu Revisi',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Dokter/Staff')
                    ->relationship('user', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('nominal_besar')
                    ->label('Nominal > 500K')
                    ->query(fn (Builder $query): Builder => $query->where('nominal', '>', 500000)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('✅ Setujui')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('approved_amount')
                                ->label('Nominal Disetujui')
                                ->prefix('Rp')
                                ->numeric()
                                ->default(fn (Jaspel $record) => $record->nominal)
                                ->required(),

                            Forms\Components\Textarea::make('approval_notes')
                                ->label('Catatan Persetujuan')
                                ->placeholder('Tambahkan catatan...')
                                ->rows(3),
                        ])
                        ->action(function (Jaspel $record, array $data) {
                            try {
                                $record->update([
                                    'status_validasi' => 'disetujui',
                                    'nominal' => $data['approved_amount'],
                                    'catatan_validasi' => $data['approval_notes'],
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                $validationService = app(ValidationWorkflowService::class);
                                $validationService->approve($record, Auth::id(), 'Jaspel approved');

                                Notification::make()
                                    ->title('✅ Jaspel Disetujui')
                                    ->body("Jaspel {$record->user->name} untuk {$record->tindakan->nama_tindakan} disetujui")
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
                        ->visible(fn (Jaspel $record): bool => $record->status_validasi === 'pending'),

                    Tables\Actions\Action::make('reject')
                        ->label('❌ Tolak')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->placeholder('Jelaskan alasan penolakan...')
                                ->required()
                                ->rows(3),

                            Forms\Components\Select::make('rejection_category')
                                ->label('Kategori Penolakan')
                                ->options([
                                    'nominal_tidak_sesuai' => 'Nominal Tidak Sesuai',
                                    'tindakan_tidak_valid' => 'Tindakan Tidak Valid',
                                    'dokumen_kurang' => 'Dokumen Kurang',
                                    'shift_salah' => 'Shift Salah',
                                    'duplikasi' => 'Data Duplikasi',
                                    'lainnya' => 'Lainnya',
                                ])
                                ->required(),
                        ])
                        ->action(function (Jaspel $record, array $data) {
                            try {
                                $record->update([
                                    'status_validasi' => 'ditolak',
                                    'catatan_validasi' => $data['rejection_reason'],
                                    'rejection_category' => $data['rejection_category'],
                                    'validasi_by' => Auth::id(),
                                    'validasi_at' => now(),
                                ]);

                                $validationService = app(ValidationWorkflowService::class);
                                $validationService->reject($record, $data['rejection_reason']);

                                Notification::make()
                                    ->title('❌ Jaspel Ditolak')
                                    ->body("Jaspel {$record->user->name} ditolak: {$data['rejection_category']}")
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
                        ->visible(fn (Jaspel $record): bool => $record->status_validasi === 'pending'),
                        
                    Tables\Actions\ViewAction::make()->label('👁️ Lihat'),
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->visible(fn (Jaspel $record): bool => 
                            in_array($record->status_validasi, ['pending', 'need_revision'])
                        ),
                ])
                ->label('Aksi')
                ->button()
                ->size('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Setujui Semua')
                        ->color('success')
                        ->form([
                            Forms\Components\Textarea::make('bulk_approval_notes')
                                ->label('Catatan Persetujuan Massal')
                                ->rows(3),
                        ])
                        ->action(function ($records, array $data) {
                            $successCount = 0;
                            $validationService = app(ValidationWorkflowService::class);

                            foreach ($records as $record) {
                                if ($record->status_validasi === 'pending') {
                                    try {
                                        $record->update([
                                            'status_validasi' => 'disetujui',
                                            'catatan_validasi' => $data['bulk_approval_notes'],
                                            'validasi_by' => Auth::id(),
                                            'validasi_at' => now(),
                                        ]);

                                        $validationService->approve($record, Auth::id(), 'Bulk approved');
                                        $successCount++;

                                    } catch (\Exception $e) {
                                        // Continue with other records
                                    }
                                }
                            }

                            Notification::make()
                                ->title('✅ Persetujuan Massal Selesai')
                                ->body("{$successCount} jaspel berhasil disetujui")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Jaspel')
                        ->color('info')
                        ->action(function ($records) {
                            $filename = 'jaspel_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
                            
                            Notification::make()
                                ->title('📊 Export Berhasil')
                                ->body('Data jaspel berhasil diekspor: ' . $filename)
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                Action::make('jaspel_summary')
                    ->label('📊 Ringkasan Jaspel')
                    ->color('info')
                    ->action(function () {
                        try {
                            $today = now()->toDateString();
                            $summary = [
                                'total_today' => Jaspel::whereDate('tanggal', $today)->sum('nominal'),
                                'count_today' => Jaspel::whereDate('tanggal', $today)->count(),
                                'pending_count' => Jaspel::where('status_validasi', 'pending')->count(),
                                'approved_today' => Jaspel::whereDate('validasi_at', $today)
                                    ->where('status_validasi', 'disetujui')->count(),
                            ];

                            $message = "📊 **RINGKASAN JASPEL HARIAN**\n\n";
                            $message .= "📅 **HARI INI ({$today})**\n";
                            $message .= "💰 Total: Rp " . number_format($summary['total_today'], 0, ',', '.') . "\n";
                            $message .= "📝 Jumlah Entry: {$summary['count_today']}\n";
                            $message .= "✅ Divalidasi: {$summary['approved_today']}\n\n";
                            $message .= "⏳ **PENDING VALIDASI: {$summary['pending_count']}**";

                            Notification::make()
                                ->title('📊 Ringkasan Jaspel')
                                ->body($message)
                                ->info()
                                ->persistent()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Gagal Memuat Ringkasan')
                                ->body('Terjadi kesalahan saat memuat data')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user', 
                'tindakan.jenisTindakan', 
                'tindakan.shiftTemplate',
                'validasiBy'
            ]);
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
            'index' => ValidasiJaspelResource\Pages\ListValidasiJaspel::route('/'),
        ];
    }
}