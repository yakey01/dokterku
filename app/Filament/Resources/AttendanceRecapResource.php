<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceRecapResource\Pages;
use App\Models\AttendanceRecap;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Columns\BadgeColumn; // Commented out if not available
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Support\Colors\Color;
use Carbon\Carbon;

class AttendanceRecapResource extends Resource
{
    protected static ?string $model = AttendanceRecap::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Rekapitulasi Absensi';

    protected static ?string $modelLabel = 'Rekapitulasi Absensi';

    protected static ?string $pluralModelLabel = 'Rekapitulasi Absensi';

    protected static ?string $navigationGroup = 'System Administration';

    protected static ?int $navigationSort = 33;

    protected static ?string $recordTitleAttribute = 'staff_name';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('staff_name')
                    ->label('Nama Staff')
                    ->disabled(),
                Forms\Components\TextInput::make('staff_type')
                    ->label('Tipe Staff')
                    ->disabled(),
                Forms\Components\TextInput::make('position')
                    ->label('Jabatan')
                    ->disabled(),
                Forms\Components\TextInput::make('attendance_percentage')
                    ->label('Persentase Kehadiran')
                    ->suffix('%')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rank')
                    ->label('No Urut Kehadiran')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->rank <= 3 => Color::Amber,
                        $record->rank <= 10 => Color::Blue,
                        default => Color::Gray,
                    })
                    ->formatStateUsing(fn ($state) => "#$state")
                    ->description('Ranking berdasarkan persentase kehadiran'),

                TextColumn::make('staff_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('staff_type')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dokter' => 'success',
                        'Paramedis' => 'info',
                        'Non-Paramedis' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Jabatan')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 20 ? $state : null;
                    }),

                TextColumn::make('total_working_days')
                    ->label('Total Hari Kerja')
                    ->alignCenter(),

                TextColumn::make('days_present')
                    ->label('Hari Hadir')
                    ->alignCenter()
                    ->color('success'),

                TextColumn::make('average_check_in')
                    ->label('Rata-rata Check In')
                    ->alignCenter()
                    ->time('H:i')
                    ->placeholder('--:--'),

                TextColumn::make('average_check_out')
                    ->label('Rata-rata Check Out')
                    ->alignCenter()
                    ->time('H:i')
                    ->placeholder('--:--'),

                TextColumn::make('total_working_hours')
                    ->label('Total Jam Kerja')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' jam' : '0 jam')
                    ->color('info'),

                TextColumn::make('attendance_percentage')
                    ->label('Persentase Kehadiran')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->color(fn ($record) => match (true) {
                        $record->attendance_percentage >= 95 => Color::Green,
                        $record->attendance_percentage >= 85 => Color::Blue,
                        $record->attendance_percentage >= 75 => Color::Yellow,
                        default => Color::Red,
                    })
                    ->weight('bold')
                    ->description('Basis ranking kehadiran'),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => $record->getStatusLabel())
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
                SelectFilter::make('staff_type')
                    ->label('Kategori Staff')
                    ->options([
                        'Dokter' => 'Dokter',
                        'Paramedis' => 'Paramedis',
                        'Non-Paramedis' => 'Non-Paramedis',
                    ])
                    ->placeholder('Semua Kategori'),

                SelectFilter::make('month')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ])
                    ->default(now()->month)
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value'])) {
                            request()->merge(['month' => $data['value']]);
                        }
                        return $query;
                    }),

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
                    ->default(now()->year)
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value'])) {
                            request()->merge(['year' => $data['value']]);
                        }
                        return $query;
                    }),

                SelectFilter::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'excellent' => 'Excellent (≥95%)',
                        'good' => 'Good (85-94%)',
                        'average' => 'Average (75-84%)',
                        'poor' => 'Poor (<75%)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value'])) {
                            request()->merge(['status_filter' => $data['value']]);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                // Alternative 1: Direct URL-based action (more reliable for virtual models)
                Action::make('view_detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('Lihat detail rekapitulasi absensi')
                    ->url(function ($record) {
                        $params = http_build_query([
                            'staff_id' => $record->staff_id ?? $record->id,
                            'month' => session('attendance_recap_month', now()->month),
                            'year' => session('attendance_recap_year', now()->year),
                            'staff_type' => session('attendance_recap_staff_type'),
                            'staff_name' => $record->staff_name,
                            'return_url' => request()->fullUrl()
                        ]);
                        return route('admin.attendance-recap.detail') . '?' . $params;
                    })
                    ->openUrlInNewTab(false),
                    
                // Alternative 2: Simplified modal with embedded data
                Action::make('view_detail_modal')
                    ->label('Detail (Modal)')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('primary')
                    ->tooltip('Lihat detail dalam modal')
                    ->action(function ($record) {
                        // Store record data in session for modal display
                        session(['current_attendance_detail' => $record->toArray()]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Detail Rekapitulasi')
                            ->body("Detail untuk {$record->staff_name}: {$record->attendance_percentage}% kehadiran (Rank #{$record->rank})")
                            ->success()
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view_full')
                                    ->label('Lihat Lengkap')
                                    ->url(function () use ($record) {
                                        $params = http_build_query([
                                            'staff_id' => $record->staff_id ?? $record->id,
                                            'month' => session('attendance_recap_month', now()->month),
                                            'year' => session('attendance_recap_year', now()->year),
                                            'staff_type' => session('attendance_recap_staff_type'),
                                        ]);
                                        return route('admin.attendance-recap.detail') . '?' . $params;
                                    })
                                    ->openUrlInNewTab(false),
                            ])
                            ->send();
                    }),
                    
                // Alternative 3: Simple info action that works reliably
                Action::make('quick_info')
                    ->label('Info')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->tooltip('Informasi cepat')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => "Informasi {$record->staff_name}")
                    ->modalDescription(function ($record) {
                        return "Kategori: {$record->staff_type}\n" .
                               "Jabatan: {$record->position}\n" .
                               "Kehadiran: {$record->days_present}/{$record->total_working_days} hari ({$record->attendance_percentage}%)\n" .
                               "Ranking: #{$record->rank}\n" .
                               "Status: {$record->getStatusLabel()}";
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->defaultSort('rank', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s'); // Auto refresh setiap 30 detik
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceRecaps::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $currentMonth = now()->month;
            $currentYear = now()->year;
            
            $data = AttendanceRecap::getRecapData($currentMonth, $currentYear);
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
            
            $data = AttendanceRecap::getRecapData($currentMonth, $currentYear);
            $totalStaff = $data->count();
            $excellentStaff = $data->where('status', 'excellent')->count();
            
            if ($totalStaff === 0) return 'gray';
            
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