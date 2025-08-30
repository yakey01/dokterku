<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JagaAttendanceRecapResource\Pages;
use App\Models\AttendanceJagaRecap;
use App\Services\AttendanceJagaCalculationService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JagaAttendanceRecapResource extends Resource
{
    protected static ?string $model = AttendanceJagaRecap::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = '📊 Rekapitulasi Jaga';

    protected static ?string $modelLabel = 'Rekapitulasi Jaga';

    protected static ?string $pluralModelLabel = 'Rekapitulasi Jaga';

    protected static ?string $navigationGroup = '📍 PRESENSI';

    protected static ?int $navigationSort = 34;

    protected static ?string $recordTitleAttribute = 'staff_name';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Staff')
                    ->schema([
                        Forms\Components\TextInput::make('staff_name')
                            ->label('Nama Staff')
                            ->disabled(),
                        Forms\Components\TextInput::make('profession')
                            ->label('Profesi')
                            ->disabled(),
                        Forms\Components\TextInput::make('position')
                            ->label('Jabatan')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Statistik Kehadiran')
                    ->schema([
                        Forms\Components\TextInput::make('attendance_percentage')
                            ->label('Persentase Kehadiran')
                            ->suffix('%')
                            ->disabled(),
                        Forms\Components\TextInput::make('schedule_compliance_rate')
                            ->label('Tingkat Kepatuhan Jadwal')
                            ->suffix('%')
                            ->disabled(),
                        Forms\Components\TextInput::make('gps_validation_rate')
                            ->label('Tingkat Validasi GPS')
                            ->suffix('%')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Detail Jam Kerja')
                    ->schema([
                        Forms\Components\TextInput::make('total_working_hours')
                            ->label('Total Jam Kerja')
                            ->suffix(' jam')
                            ->disabled(),
                        Forms\Components\TextInput::make('average_check_in')
                            ->label('Rata-rata Check In')
                            ->disabled(),
                        Forms\Components\TextInput::make('average_check_out')
                            ->label('Rata-rata Check Out')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff_name')
                    ->label('Nama Staff')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->position),

                TextColumn::make('profession')
                    ->label('Profesi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dokter' => 'success',
                        'Paramedis' => 'info',
                        'NonParamedis' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('jadwal_info')
                    ->label('Jadwal Jaga')
                    ->state(function ($record) {
                        $scheduled = $record->total_scheduled_days ?? 0;
                        $present = $record->days_present ?? 0;

                        return "{$present}/{$scheduled} hari";
                    })
                    ->description('Hadir/Dijadwalkan')
                    ->alignCenter(),

                TextColumn::make('average_check_in')
                    ->label('Rata² Check In')
                    ->alignCenter()
                    ->placeholder('--:--')
                    ->color(function ($record) {
                        if (! $record->average_check_in) {
                            return 'gray';
                        }

                        $time = Carbon::parse($record->average_check_in);

                        return match (true) {
                            $time->hour < 8 => 'success',      // Early
                            $time->hour == 8 && $time->minute <= 15 => 'info', // On time
                            default => 'warning'              // Late
                        };
                    }),

                TextColumn::make('average_check_out')
                    ->label('Rata² Check Out')
                    ->alignCenter()
                    ->placeholder('--:--'),

                TextColumn::make('total_shortfall_minutes')
                    ->label('Kekurangan Menit')
                    ->alignCenter()
                    ->formatStateUsing(function ($state) {
                        if ($state <= 0) {
                            return 'Target tercapai';
                        }

                        $hours = intval($state / 60);
                        $minutes = $state % 60;

                        return sprintf('%dj %dm', $hours, $minutes);
                    })
                    ->color(fn ($state) => $state <= 0 ? 'success' : 'danger')
                    ->description('Kurang dari target'),

                TextColumn::make('total_working_hours')
                    ->label('Total Jam Kerja')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state, 1).' jam')
                    ->color('info')
                    ->sortable(),

                TextColumn::make('attendance_percentage')
                    ->label('Persentase Kehadiran')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 1).'%')
                    ->color(fn ($record) => match (true) {
                        $record->attendance_percentage >= 95 => Color::Green,
                        $record->attendance_percentage >= 85 => Color::Blue,
                        $record->attendance_percentage >= 75 => Color::Yellow,
                        default => Color::Red,
                    })
                    ->weight('bold')
                    ->description('Basis ranking'),

                TextColumn::make('schedule_compliance_rate')
                    ->label('Kepatuhan Jadwal')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state, 1).'%')
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 80 => 'info',
                        $state >= 70 => 'warning',
                        default => 'danger',
                    })
                    ->description('Sesuai jadwal'),

                TextColumn::make('gps_validation_rate')
                    ->label('Validasi GPS')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state, 1).'%')
                    ->color(fn ($state) => match (true) {
                        $state >= 95 => 'success',
                        $state >= 85 => 'info',
                        default => 'warning',
                    })
                    ->description('GPS valid'),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => match ($record->status) {
                        'excellent' => 'Excellent (≥95%)',
                        'good' => 'Good (85-94%)',
                        'average' => 'Average (75-84%)',
                        'poor' => 'Poor (<75%)',
                        default => 'Unknown'
                    })
                    ->badge()
                    ->color(fn ($record) => match ($record->status) {
                        'excellent' => 'success',
                        'good' => 'info',
                        'average' => 'warning',
                        'poor' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('profession')
                    ->label('Profesi')
                    ->options([
                        'Dokter' => 'Dokter',
                        'Paramedis' => 'Paramedis',
                        'NonParamedis' => 'Non-Paramedis',
                    ])
                    ->placeholder('Semua Profesi'),

                SelectFilter::make('month')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ])
                    ->default(now()->month),

                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }

                        return $years;
                    })
                    ->default(now()->year),

                SelectFilter::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'excellent' => 'Excellent (≥95%)',
                        'good' => 'Good (85-94%)',
                        'average' => 'Average (75-84%)',
                        'poor' => 'Poor (<75%)',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('detail')
                        ->label('📊 Detail Lengkap')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->color('info')
                        ->modalHeading(fn ($record) => '📊 Detail Komprehensif: '.$record->staff_name)
                        ->modalContent(function ($record) {
                            return view('filament.pages.jaga-attendance-comprehensive-detail', [
                                'record' => $record,
                            ]);
                        })
                        ->modalWidth('7xl')
                        ->slideOver()
                        ->visible(fn ($record) => !empty($record->staff_name)),

                    Action::make('quick_view')
                        ->label('👁️ Ringkasan')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->modalHeading(fn ($record) => '👁️ Ringkasan: '.$record->staff_name)
                        ->modalContent(function ($record) {
                            return view('filament.pages.jaga-attendance-detail', [
                                'record' => $record,
                            ]);
                        })
                        ->modalWidth('5xl')
                        ->visible(fn ($record) => !empty($record->staff_name)),

                    Action::make('export_individual')
                        ->label('📤 Export')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($record) {
                            // Individual export functionality
                            return redirect()->back();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('📤 Export Data Individual')
                        ->modalDescription(fn ($record) => 'Apakah Anda ingin mengexport data detail untuk '.$record->staff_name.'?')
                        ->visible(fn ($record) => !empty($record->staff_name)),
                        
                    // Debug action to test if actions are working
                    Action::make('debug')
                        ->label('🐛 Debug')
                        ->icon('heroicon-o-bug-ant')
                        ->color('warning')
                        ->action(function ($record) {
                            \Filament\Notifications\Notification::make()
                                ->title('Debug Info')
                                ->body('Record ID: ' . $record->getKey() . ', Name: ' . $record->staff_name)
                                ->success()
                                ->send();
                        })
                        ->visible(true), // Always visible for debugging
                ])
                    ->label('⚙️ Actions')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button()
                    ->visible(fn ($record) => !empty($record->user_id)),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        // Export functionality will be implemented
                        return redirect()->back();
                    }),

                Action::make('refresh')
                    ->label('Refresh Data')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function () {
                        // Clear cache and refresh
                        app(AttendanceJagaCalculationService::class)->clearUserCache(0);

                        return redirect()->back();
                    }),
            ])
            ->defaultSort('attendance_percentage', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('60s'); // Auto refresh every minute
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJagaAttendanceRecaps::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $currentMonth = now()->month;
            $currentYear = now()->year;

            $data = AttendanceJagaRecap::getJagaRecapData(null, $currentMonth, $currentYear);
            $totalStaff = $data->count();
            $excellentStaff = $data->where('status', 'excellent')->count();

            return "$excellentStaff/$totalStaff";
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $currentMonth = now()->month;
            $currentYear = now()->year;

            $data = AttendanceJagaRecap::getJagaRecapData(null, $currentMonth, $currentYear);
            $totalStaff = $data->count();
            $excellentStaff = $data->where('status', 'excellent')->count();

            if ($totalStaff === 0) {
                return 'gray';
            }

            $excellentPercentage = ($excellentStaff / $totalStaff) * 100;

            return match (true) {
                $excellentPercentage >= 80 => 'success',
                $excellentPercentage >= 60 => 'warning',
                default => 'danger',
            };
        } catch (\Exception $e) {
            return 'gray';
        }
    }
}
