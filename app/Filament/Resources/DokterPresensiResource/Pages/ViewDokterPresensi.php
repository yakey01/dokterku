<?php

namespace App\Filament\Resources\DokterPresensiResource\Pages;

use App\Filament\Resources\DokterPresensiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;

class ViewDokterPresensi extends ViewRecord
{
    protected static string $resource = DokterPresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('checkout')
                ->label('Check Out Now')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn () => $this->record->jam_masuk && !$this->record->jam_pulang)
                ->requiresConfirmation()
                ->modalHeading('Check Out Doctor')
                ->modalDescription('This will record the current time as check-out time.')
                ->form([
                    \Filament\Forms\Components\TimePicker::make('jam_pulang')
                        ->label('Check-out Time')
                        ->required()
                        ->default(now()->format('H:i'))
                        ->after(fn () => $this->record->jam_masuk),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'jam_pulang' => $data['jam_pulang']
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Check-out Successful')
                        ->body("Dr. {$this->record->dokter->nama} checked out at {$data['jam_pulang']}")
                        ->success()
                        ->send();
                }),
                
            Actions\DeleteAction::make(),
        ];
    }

    protected function getInfolistActions(): array
    {
        return [
            InfolistAction::make('viewSchedule')
                ->label('View Schedule')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->url(fn () => route('filament.admin.resources.user-schedules.index', [
                    'filter' => ['user_id' => $this->record->dokter->user_id ?? null]
                ]))
                ->visible(fn () => $this->record->dokter->user_id),
                
            InfolistAction::make('viewAllAttendance')
                ->label('All Attendance')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(fn () => route('filament.admin.resources.dokter-presensis.index', [
                    'filter' => ['dokter_id' => $this->record->dokter_id]
                ])),
        ];
    }

    public function getTitle(): string
    {
        return "Attendance Details - Dr. {$this->record->dokter->nama}";
    }

    public function getSubheading(): ?string
    {
        return $this->record->tanggal->format('l, d F Y') . ' • ' . $this->record->status;
    }
}