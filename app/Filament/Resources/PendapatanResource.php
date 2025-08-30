<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendapatanResource\Pages;
use App\Filament\Resources\PendapatanResource\Pages\ListPendapatan;
use App\Filament\Resources\PendapatanResource\Pages\CreatePendapatan;
use App\Filament\Resources\PendapatanResource\Pages\ViewPendapatan;
use App\Filament\Resources\PendapatanResource\Pages\EditPendapatan;
use App\Models\Pendapatan;
use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PendapatanResource extends Resource
{
    protected static ?string $model = Pendapatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 20;
    
    protected static ?string $modelLabel = 'Pendapatan';
    
    protected static ?string $pluralModelLabel = 'Master Pendapatan';
    
    protected static ?string $navigationBadgeTooltip = 'Jumlah pendapatan pending';
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count() > 0 ? 'warning' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\TextInput::make('kode_pendapatan')
                            ->label('Kode Pendapatan')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Auto-generated: PND-0001')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Kode akan dibuat otomatis saat menyimpan data'),
                        
                        Forms\Components\TextInput::make('nama_pendapatan')
                            ->label('Nama Pendapatan')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Masukkan nama pendapatan')
                            ->prefixIcon('heroicon-o-document-text')
                            ->rules([
                                function ($context, $record) {
                                    $rule = 'unique:pendapatan,nama_pendapatan';
                                    
                                    // Jika sedang edit, abaikan record yang sedang diedit
                                    if ($context === 'edit' && $record) {
                                        $rule .= ',' . $record->id;
                                    }
                                    
                                    return $rule;
                                }
                            ])
                            ->helperText('Nama pendapatan harus unik dan tidak boleh sama dengan yang sudah ada')
                            ->validationAttribute('nama pendapatan'),
                        
                        Forms\Components\Select::make('sumber_pendapatan')
                            ->label('Sumber Pendapatan')
                            ->required()
                            ->options([
                                'Umum' => 'Umum',
                                'Gigi' => 'Gigi',
                            ])
                            ->placeholder('Pilih sumber pendapatan')
                            ->native(false)
                            ->prefixIcon('heroicon-o-folder-open'),

                        Forms\Components\Select::make('is_aktif')
                            ->label('Status Aktif')
                            ->options([
                                1 => 'Aktif',
                                0 => 'Tidak Aktif'
                            ])
                            ->default(1)
                            ->helperText('Tentukan apakah pendapatan ini masih aktif digunakan')
                            ->native(false)
                            ->prefixIcon('heroicon-o-check-circle'),
                    ])
                    ->columnSpan('full')
                    ->extraAttributes([
                        'class' => 'gap-6',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_pendapatan')
                    ->label('Kode Pendapatan')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-hashtag')
                    ->weight('medium'),
                
                Tables\Columns\TextColumn::make('nama_pendapatan')
                    ->label('Nama Pendapatan')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->nama_pendapatan)
                    ->weight('medium'),
                
                Tables\Columns\IconColumn::make('is_aktif')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('sumber_pendapatan')
                    ->label('Sumber')
                    ->colors([
                        'primary' => 'Umum',
                        'success' => 'Gigi',
                    ])
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-o-calendar'),
                
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->default('Rp 0')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total'),
                    ]),
                
                Tables\Columns\BadgeColumn::make('kategori')
                    ->label('Kategori')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'tindakan_medis' => 'Tindakan Medis',
                        'konsultasi' => 'Konsultasi',
                        'obat' => 'Penjualan Obat',
                        'laboratorium' => 'Laboratorium',
                        'radiologi' => 'Radiologi',
                        'rawat_inap' => 'Rawat Inap',
                        'rawat_jalan' => 'Rawat Jalan',
                        'administrasi' => 'Administrasi',
                        'asuransi' => 'Klaim Asuransi',
                        'lain_lain' => 'Lain-lain',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'tindakan_medis',
                        'success' => 'konsultasi',
                        'warning' => 'obat',
                        'info' => 'laboratorium',
                        'secondary' => 'radiologi',
                        'danger' => 'rawat_inap',
                        'gray' => 'lain_lain',
                    ])
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'disetujui',
                        'heroicon-o-x-circle' => 'ditolak',
                    ])
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('tindakan.id')
                    ->label('ID Tindakan')
                    ->prefix('#')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Diinput Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('validasiBy.name')
                    ->label('Divalidasi Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('validasi_at')
                    ->label('Tanggal Validasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_aktif')
                    ->label('Status Aktif')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Non-Aktif',
                    ])
                    ->placeholder('Semua Status'),
                
                SelectFilter::make('sumber_pendapatan')
                    ->label('Sumber Pendapatan')
                    ->options([
                        'Umum' => 'Umum',
                        'Gigi' => 'Gigi',
                    ])
                    ->multiple(),
                
                SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ])
                    ->multiple(),
                
                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_dari')
                            ->label('Dari Tanggal')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('tanggal_sampai')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['tanggal_dari'], fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date))
                            ->when($data['tanggal_sampai'], fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if ($data['tanggal_dari'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['tanggal_dari'])->format('d/m/Y');
                        }
                        
                        if ($data['tanggal_sampai'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['tanggal_sampai'])->format('d/m/Y');
                        }
                        
                        return $indicators;
                    }),
                
                Filter::make('nominal')
                    ->label('Rentang Nominal')
                    ->form([
                        Forms\Components\TextInput::make('nominal_min')
                            ->label('Nominal Minimum')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('nominal_max')
                            ->label('Nominal Maximum')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['nominal_min'], fn (Builder $query, $amount): Builder => $query->where('nominal', '>=', $amount))
                            ->when($data['nominal_max'], fn (Builder $query, $amount): Builder => $query->where('nominal', '<=', $amount));
                    }),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->color('warning')
                    ->icon('heroicon-o-pencil')
                    ->tooltip('Edit Pendapatan'),
                Tables\Actions\DeleteAction::make()
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->tooltip('Hapus Pendapatan'),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_aktif ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn ($record) => $record->is_aktif ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_aktif ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_aktif' => !$record->is_aktif]);
                        
                        Notification::make()
                            ->title('Status Berhasil Diubah')
                            ->body($record->is_aktif ? 'Pendapatan diaktifkan' : 'Pendapatan dinonaktifkan')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                    
                Tables\Actions\Action::make('toggle_validation')
                    ->label(fn ($record) => match($record->status_validasi) {
                        'pending' => 'Setujui',
                        'disetujui' => 'Pending',
                        'ditolak' => 'Setujui',
                        default => 'Setujui'
                    })
                    ->icon(fn ($record) => match($record->status_validasi) {
                        'pending' => 'heroicon-o-check-circle',
                        'disetujui' => 'heroicon-o-clock',
                        'ditolak' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-check-circle'
                    })
                    ->color(fn ($record) => match($record->status_validasi) {
                        'pending' => 'success',
                        'disetujui' => 'warning',
                        'ditolak' => 'success',
                        default => 'success'
                    })
                    ->action(function ($record) {
                        $newStatus = match($record->status_validasi) {
                            'pending' => 'disetujui',
                            'disetujui' => 'pending',
                            'ditolak' => 'disetujui',
                            default => 'disetujui'
                        };
                        
                        $updateData = [
                            'status_validasi' => $newStatus,
                            'validasi_by' => Auth::id(),
                            'validasi_at' => now(),
                        ];
                        
                        $record->update($updateData);
                        
                        $statusLabel = match($newStatus) {
                            'pending' => 'Pending',
                            'disetujui' => 'Disetujui',
                            'ditolak' => 'Ditolak',
                            default => $newStatus
                        };
                        
                        Notification::make()
                            ->title('Status Validasi Berhasil Diubah')
                            ->body("Status validasi diubah menjadi: {$statusLabel}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status_validasi === 'pending') {
                                    $record->update([
                                        'status_validasi' => 'disetujui',
                                        'validasi_by' => Auth::id(),
                                        'validasi_at' => now(),
                                    ]);
                                }
                            }
                            
                            Notification::make()
                                ->title('Pendapatan Terpilih Disetujui')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->poll('30s')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferLoading();
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
            'index' => ListPendapatan::route('/'),
            'create' => CreatePendapatan::route('/create'),
            'view' => ViewPendapatan::route('/{record}'),
            'edit' => EditPendapatan::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}