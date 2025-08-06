<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DokterPresensiResource\Pages;
use App\Models\DokterPresensi;
use App\Models\Dokter;
use App\Models\User;
use App\Models\JadwalJaga;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DokterPresensiResource extends Resource
{
    protected static ?string $model = DokterPresensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-check';

    protected static ?string $navigationGroup = '👨‍⚕️ DOCTOR MANAGEMENT';

    protected static ?string $navigationLabel = 'Doctor Attendance';

    protected static ?string $modelLabel = 'Doctor Attendance';

    protected static ?string $pluralModelLabel = 'Doctor Attendance Records';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Information')
                    ->schema([
                        Forms\Components\Select::make('dokter_id')
                            ->label('Doctor')
                            ->relationship('dokter', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nama} - {$record->spesialisasi}"),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Date')
                            ->required()
                            ->default(today())
                            ->maxDate(today()),

                        Forms\Components\TimePicker::make('jam_masuk')
                            ->label('Check-in Time')
                            ->required()
                            ->seconds(false)
                            ->default(now()->format('H:i')),

                        Forms\Components\TimePicker::make('jam_pulang')
                            ->label('Check-out Time')
                            ->seconds(false)
                            ->after('jam_masuk')
                            ->placeholder('Leave empty if still working'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Placeholder::make('duration_display')
                            ->label('Work Duration')
                            ->content(function ($get) {
                                $jamMasuk = $get('jam_masuk');
                                $jamPulang = $get('jam_pulang');
                                
                                if ($jamMasuk && $jamPulang) {
                                    $masuk = Carbon::createFromFormat('H:i', $jamMasuk);
                                    $pulang = Carbon::createFromFormat('H:i', $jamPulang);
                                    $diff = $pulang->diff($masuk);
                                    return sprintf('%02d:%02d:%02d', $diff->h, $diff->i, $diff->s);
                                }
                                
                                return 'Not available (check-out time required)';
                            })
                            ->visible(fn ($get) => $get('jam_masuk') && $get('jam_pulang')),
                            
                        Forms\Components\Placeholder::make('status_display')
                            ->label('Current Status')
                            ->content(function ($get) {
                                $jamMasuk = $get('jam_masuk');
                                $jamPulang = $get('jam_pulang');
                                
                                if (!$jamMasuk) {
                                    return '🔴 Not checked in';
                                }
                                
                                if (!$jamPulang) {
                                    return '🟡 Currently working';
                                }
                                
                                return '🟢 Completed shift';
                            }),
                    ])
                    ->columns(2)
                    ->hidden(fn ($operation) => $operation === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dokter.nama')
                    ->label('Doctor Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->dokter->spesialisasi ?? 'General')
                    ->url(fn ($record) => $record->dokter ? route('filament.admin.resources.dokters.view', ['record' => $record->dokter]) : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->description(fn ($record) => $record->tanggal->format('l')),
                    
                Tables\Columns\TextColumn::make('jadwal_jaga_info')
                    ->label('Schedule Info')
                    ->getStateUsing(function ($record) {
                        $jadwal = JadwalJaga::whereDate('tanggal_jaga', $record->tanggal)
                            ->where('pegawai_id', $record->dokter->user_id ?? null)
                            ->with('shiftTemplate')
                            ->first();
                        
                        if ($jadwal) {
                            return $jadwal->shift_template->nama_shift ?? 'Unknown Shift';
                        }
                        
                        return 'No Schedule';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'No Schedule' => 'gray',
                        default => 'info',
                    })
                    ->tooltip(function ($record) {
                        $jadwal = JadwalJaga::whereDate('tanggal_jaga', $record->tanggal)
                            ->where('pegawai_id', $record->dokter->user_id ?? null)
                            ->with('shiftTemplate')
                            ->first();
                        
                        if ($jadwal) {
                            return "Unit: {$jadwal->unit_kerja} | Role: {$jadwal->peran} | Status: {$jadwal->status_jaga}";
                        }
                        
                        return 'No scheduled shift found for this date';
                    }),

                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Check-in')
                    ->time('H:i')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('jam_pulang')
                    ->label('Check-out')
                    ->time('H:i')
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->placeholder('Still Working'),

                Tables\Columns\TextColumn::make('durasi')
                    ->label('Duration')
                    ->getStateUsing(fn ($record) => $record->durasi)
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->placeholder('In Progress'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $record->status)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Selesai' => 'success',
                        'Sedang Bertugas' => 'warning',
                        'Belum Hadir' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('dokter_id')
                    ->label('Doctor')
                    ->relationship('dokter', 'nama')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('tanggal')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Belum Hadir' => 'Not Present',
                        'Sedang Bertugas' => 'Currently Working',
                        'Selesai' => 'Completed',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $status = $data['value'] ?? null;
                        if (!$status) return $query;

                        return $query->whereHas('*', function ($q) use ($status) {
                            // This is a computed attribute, so we need custom logic
                            switch ($status) {
                                case 'Belum Hadir':
                                    return $q->whereNull('jam_masuk');
                                case 'Sedang Bertugas':
                                    return $q->whereNotNull('jam_masuk')->whereNull('jam_pulang');
                                case 'Selesai':
                                    return $q->whereNotNull('jam_masuk')->whereNotNull('jam_pulang');
                            }
                        });
                    }),

                Tables\Filters\Filter::make('today')
                    ->label('Today Only')
                    ->query(fn (Builder $query): Builder => $query->whereDate('tanggal', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('tanggal', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]))
                    ->toggle(),

                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('tanggal', now()->month)
                        ->whereYear('tanggal', now()->year))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('view_schedule')
                    ->label('View Schedule')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->url(function ($record) {
                        $jadwal = JadwalJaga::whereDate('tanggal_jaga', $record->tanggal)
                            ->where('pegawai_id', $record->dokter->user_id ?? null)
                            ->first();
                        
                        if ($jadwal) {
                            return route('filament.admin.resources.jadwal-jagas.view', ['record' => $jadwal]);
                        }
                        
                        return null;
                    })
                    ->visible(function ($record) {
                        return JadwalJaga::whereDate('tanggal_jaga', $record->tanggal)
                            ->where('pegawai_id', $record->dokter->user_id ?? null)
                            ->exists();
                    })
                    ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('checkout')
                    ->label('Check Out')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn ($record) => $record->jam_masuk && !$record->jam_pulang)
                    ->form([
                        Forms\Components\TimePicker::make('jam_pulang')
                            ->label('Check-out Time')
                            ->required()
                            ->default(now()->format('H:i'))
                            ->after(fn ($record) => $record->jam_masuk),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'jam_pulang' => $data['jam_pulang']
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Check-out Successful')
                            ->body("Doctor {$record->dokter->nama} checked out at {$data['jam_pulang']}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('bulk_checkout')
                        ->label('Bulk Check Out')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->form([
                            Forms\Components\TimePicker::make('jam_pulang')
                                ->label('Check-out Time for All Selected')
                                ->required()
                                ->default(now()->format('H:i')),
                        ])
                        ->action(function ($records, array $data) {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->jam_masuk && !$record->jam_pulang) {
                                    $record->update(['jam_pulang' => $data['jam_pulang']]);
                                    $updated++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Bulk Check-out Successful')
                                ->body("Updated {$updated} attendance records")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->defaultGroup('tanggal')
            ->groupingSettingsHidden()
            ->poll('30s') // Auto-refresh every 30 seconds
            ->deferLoading()
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Doctor Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('dokter.nama')
                            ->label('Doctor Name')
                            ->size('lg')
                            ->weight('bold'),
                            
                        Infolists\Components\TextEntry::make('dokter.spesialisasi')
                            ->label('Specialization')
                            ->badge()
                            ->color('info'),
                            
                        Infolists\Components\TextEntry::make('dokter.user.email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope'),
                            
                        Infolists\Components\TextEntry::make('dokter.user.no_telepon')
                            ->label('Phone')
                            ->icon('heroicon-o-phone'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Attendance Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('tanggal')
                            ->label('Date')
                            ->date('l, d F Y')
                            ->size('lg'),
                            
                        Infolists\Components\TextEntry::make('jam_masuk')
                            ->label('Check-in Time')
                            ->time('H:i:s')
                            ->badge()
                            ->color('success'),
                            
                        Infolists\Components\TextEntry::make('jam_pulang')
                            ->label('Check-out Time')
                            ->time('H:i:s')
                            ->badge()
                            ->color('warning')
                            ->placeholder('Still Working'),
                            
                        Infolists\Components\TextEntry::make('durasi')
                            ->label('Work Duration')
                            ->getStateUsing(fn ($record) => $record->durasi ?: 'In Progress')
                            ->badge()
                            ->color(fn ($state) => $state !== 'In Progress' ? 'info' : 'gray'),
                            
                        Infolists\Components\TextEntry::make('status')
                            ->label('Current Status')
                            ->getStateUsing(fn ($record) => $record->status)
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Selesai' => 'success',
                                'Sedang Bertugas' => 'warning',
                                'Belum Hadir' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('System Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d M Y H:i:s'),
                            
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('d M Y H:i:s'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDokterPresensis::route('/'),
            'create' => Pages\CreateDokterPresensi::route('/create'),
            'view' => Pages\ViewDokterPresensi::route('/{record}'),
            'edit' => Pages\EditDokterPresensi::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $today = DokterPresensi::whereDate('tanggal', today())->count();
        return $today > 0 ? (string) $today : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['dokter.user']) // Eager load relationships
            ->latest('tanggal');
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['dokter']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['dokter.nama', 'dokter.spesialisasi', 'tanggal'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Doctor' => $record->dokter->nama ?? 'Unknown',
            'Date' => $record->tanggal->format('d M Y'),
            'Status' => $record->status,
        ];
    }
}