<?php

namespace App\Filament\Resources\DokterPresensiResource\Pages;

use App\Filament\Resources\DokterPresensiResource;
use App\Models\DokterPresensi;
use App\Models\Dokter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ListDokterPresensis extends ListRecords
{
    protected static string $resource = DokterPresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Attendance')
                ->icon('heroicon-o-plus'),
                
            Actions\Action::make('bulk_checkout')
                ->label('Emergency Checkout All')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Emergency Checkout All Active Sessions')
                ->modalDescription('This will check out all doctors who are currently working. This action should only be used in emergency situations.')
                ->modalSubmitActionLabel('Emergency Checkout')
                ->form([
                    \Filament\Forms\Components\TimePicker::make('checkout_time')
                        ->label('Checkout Time')
                        ->required()
                        ->default(now()->format('H:i')),
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Emergency Reason')
                        ->required()
                        ->placeholder('Explain why emergency checkout is needed...'),
                ])
                ->action(function (array $data) {
                    $activeAttendances = DokterPresensi::whereNotNull('jam_masuk')
                        ->whereNull('jam_pulang')
                        ->whereDate('tanggal', today())
                        ->get();
                    
                    $count = 0;
                    foreach ($activeAttendances as $attendance) {
                        $attendance->update(['jam_pulang' => $data['checkout_time']]);
                        $count++;
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Emergency Checkout Completed')
                        ->body("Checked out {$count} active sessions. Reason: {$data['reason']}")
                        ->warning()
                        ->persistent()
                        ->send();
                }),
                
            Actions\Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('admin.attendance.export', ['type' => 'dokter']))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Attendance')
                ->badge(fn () => $this->getModel()::count()),
                
            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('tanggal', today()))
                ->badge(fn () => $this->getModel()::whereDate('tanggal', today())->count())
                ->badgeColor('success'),
                
            'active' => Tab::make('Currently Working')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNotNull('jam_masuk')
                    ->whereNull('jam_pulang')
                    ->whereDate('tanggal', today())
                )
                ->badge(fn () => $this->getModel()::whereNotNull('jam_masuk')
                    ->whereNull('jam_pulang')
                    ->whereDate('tanggal', today())
                    ->count())
                ->badgeColor('warning'),
                
            'completed_today' => Tab::make('Completed Today')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNotNull('jam_masuk')
                    ->whereNotNull('jam_pulang')
                    ->whereDate('tanggal', today())
                )
                ->badge(fn () => $this->getModel()::whereNotNull('jam_masuk')
                    ->whereNotNull('jam_pulang')
                    ->whereDate('tanggal', today())
                    ->count())
                ->badgeColor('info'),
                
            'this_week' => Tab::make('This Week')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('tanggal', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]))
                ->badge(fn () => $this->getModel()::whereBetween('tanggal', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count()),
                
            'this_month' => Tab::make('This Month')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                )
                ->badge(fn () => $this->getModel()::whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                    ->count()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DokterPresensiResource\Widgets\DokterAttendanceOverview::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Doctor Attendance Management';
    }

    public function getSubheading(): ?string
    {
        $today = now()->format('l, d F Y');
        $activeCount = DokterPresensi::whereNotNull('jam_masuk')
            ->whereNull('jam_pulang')
            ->whereDate('tanggal', today())
            ->count();
            
        return "Today: {$today} • {$activeCount} doctors currently working";
    }
}