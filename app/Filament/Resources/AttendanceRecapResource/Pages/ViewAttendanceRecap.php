<?php

namespace App\Filament\Resources\AttendanceRecapResource\Pages;

use App\Filament\Resources\AttendanceRecapResource;
use App\Models\AttendanceRecap;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Colors\Color;
use Illuminate\Contracts\Support\Htmlable;

class ViewAttendanceRecap extends ViewRecord
{
    protected static string $resource = AttendanceRecapResource::class;
    
    protected function resolveRecord($key): AttendanceRecap
    {
        // Get filter values from session or use defaults
        $month = request('month', session('tableFilters.month.value', now()->month));
        $year = request('year', session('tableFilters.year.value', now()->year));
        $staffType = request('staff_type', session('tableFilters.staff_type.value'));
        
        // Get all recap data
        $data = AttendanceRecap::getRecapData($month, $year, $staffType);
        
        // Find the specific record by staff_id
        $recordData = $data->firstWhere('staff_id', $key);
        
        if (!$recordData) {
            abort(404, 'Record not found');
        }
        
        // Create virtual model instance
        $model = new AttendanceRecap();
        $model->fill($recordData);
        $model->exists = true;
        $model->id = $recordData['staff_id'];
        
        return $model;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_individual')
                ->label('Export Individual Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    // Individual export logic will be implemented later
                    \Filament\Notifications\Notification::make()
                        ->title('Export Individual Report')
                        ->body('Fitur export laporan individu akan segera tersedia')
                        ->info()
                        ->send();
                }),

            Actions\Action::make('send_report')
                ->label('Send Report')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->action(function () {
                    // Email report logic will be implemented later
                    \Filament\Notifications\Notification::make()
                        ->title('Send Report')
                        ->body('Fitur kirim laporan akan segera tersedia')
                        ->info()
                        ->send();
                }),

            Actions\Action::make('back')
                ->label('Back to List')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AttendanceRecapResource::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Staff Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('staff_name')
                                    ->label('Nama Lengkap')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-m-user'),

                                Infolists\Components\TextEntry::make('rank')
                                    ->label('Peringkat')
                                    ->badge()
                                    ->color(fn ($record) => match (true) {
                                        $record->rank <= 3 => Color::Amber,
                                        $record->rank <= 10 => Color::Blue,
                                        default => Color::Gray,
                                    })
                                    ->formatStateUsing(fn ($state) => "#$state"),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('staff_type')
                                    ->label('Kategori Staff')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Dokter' => 'success',
                                        'Paramedis' => 'info',
                                        'Non-Paramedis' => 'warning',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('position')
                                    ->label('Jabatan')
                                    ->icon('heroicon-m-briefcase'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Attendance Summary')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_working_days')
                                    ->label('Total Hari Kerja')
                                    ->icon('heroicon-m-calendar-days')
                                    ->suffix(' hari'),

                                Infolists\Components\TextEntry::make('days_present')
                                    ->label('Hari Hadir')
                                    ->icon('heroicon-m-check-circle')
                                    ->suffix(' hari')
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('attendance_percentage')
                                    ->label('Persentase Kehadiran')
                                    ->icon('heroicon-m-chart-bar')
                                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                                    ->badge()
                                    ->color(fn ($record) => match (true) {
                                        $record->attendance_percentage >= 95 => Color::Green,
                                        $record->attendance_percentage >= 85 => Color::Blue,
                                        $record->attendance_percentage >= 75 => Color::Yellow,
                                        default => Color::Red,
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Time Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('average_check_in')
                                    ->label('Rata-rata Check In')
                                    ->icon('heroicon-m-clock')
                                    ->time('H:i')
                                    ->placeholder('--:--'),

                                Infolists\Components\TextEntry::make('average_check_out')
                                    ->label('Rata-rata Check Out')
                                    ->icon('heroicon-m-clock')
                                    ->time('H:i')
                                    ->placeholder('--:--'),

                                Infolists\Components\TextEntry::make('total_working_hours')
                                    ->label('Total Jam Kerja')
                                    ->icon('heroicon-m-clock')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' jam' : '0 jam')
                                    ->color('info'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Performance Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Kehadiran')
                            ->formatStateUsing(fn ($record) => $record->getStatusLabel())
                            ->badge()
                            ->color(fn ($record) => match ($record->status) {
                                'excellent' => 'success',
                                'good' => 'info',
                                'average' => 'warning',
                                'poor' => 'danger',
                                default => 'gray',
                            })
                            ->size('lg'),
                    ]),

                Infolists\Components\Section::make('Performance Insights')
                    ->schema([
                        Infolists\Components\TextEntry::make('performance_notes')
                            ->label('Catatan Performa')
                            ->formatStateUsing(function ($record) {
                                $notes = [];
                                
                                if ($record->attendance_percentage >= 95) {
                                    $notes[] = '✅ Kehadiran sangat baik, pertahankan!';
                                } elseif ($record->attendance_percentage >= 85) {
                                    $notes[] = '✅ Kehadiran baik';
                                } elseif ($record->attendance_percentage >= 75) {
                                    $notes[] = '⚠️ Kehadiran perlu ditingkatkan';
                                } else {
                                    $notes[] = '❌ Kehadiran buruk, perlu perhatian khusus';
                                }

                                $avgCheckIn = $record->average_check_in;
                                if ($avgCheckIn && $avgCheckIn->format('H:i') <= '08:00') {
                                    $notes[] = '✅ Selalu tepat waktu masuk';
                                } elseif ($avgCheckIn && $avgCheckIn->format('H:i') > '08:30') {
                                    $notes[] = '⚠️ Sering terlambat masuk';
                                }

                                if ($record->total_working_hours >= 160) {
                                    $notes[] = '✅ Jam kerja memenuhi standar';
                                } else {
                                    $notes[] = '⚠️ Jam kerja kurang dari standar';
                                }

                                return implode("\n", $notes);
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Detail Rekapitulasi Absensi';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->record->staff_name . ' - ' . $this->record->staff_type;
    }
}