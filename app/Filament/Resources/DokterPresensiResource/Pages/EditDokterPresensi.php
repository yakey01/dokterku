<?php

namespace App\Filament\Resources\DokterPresensiResource\Pages;

use App\Filament\Resources\DokterPresensiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDokterPresensi extends EditRecord
{
    protected static string $resource = DokterPresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('quick_checkout')
                ->label('Quick Check Out')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn () => $this->record->jam_masuk && !$this->record->jam_pulang)
                ->action(function () {
                    $this->record->update([
                        'jam_pulang' => now()->format('H:i:s')
                    ]);
                    
                    Notification::make()
                        ->title('Quick Check-out Completed')
                        ->body("Dr. {$this->record->dokter->nama} checked out at " . now()->format('H:i'))
                        ->success()
                        ->send();
                        
                    return redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function beforeSave(): void
    {
        // Validate that check-out time is after check-in time
        if (isset($this->data['jam_pulang']) && isset($this->data['jam_masuk'])) {
            $checkIn = \Carbon\Carbon::createFromFormat('H:i', $this->data['jam_masuk']);
            $checkOut = \Carbon\Carbon::createFromFormat('H:i', $this->data['jam_pulang']);
            
            if ($checkOut->lte($checkIn)) {
                Notification::make()
                    ->title('Invalid Time')
                    ->body('Check-out time must be after check-in time.')
                    ->danger()
                    ->send();
                    
                $this->halt();
            }
        }
        
        // Prevent editing future dates
        if (isset($this->data['tanggal']) && \Carbon\Carbon::parse($this->data['tanggal'])->isFuture()) {
            Notification::make()
                ->title('Invalid Date')
                ->body('Cannot set attendance for future dates.')
                ->danger()
                ->send();
                
            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Attendance Updated')
            ->body("Attendance record updated for Dr. {$this->record->dokter->nama}")
            ->success()
            ->send();
    }

    public function getTitle(): string
    {
        return "Edit Attendance - Dr. {$this->record->dokter->nama}";
    }

    public function getSubheading(): ?string
    {
        return $this->record->tanggal->format('l, d F Y') . ' • Status: ' . $this->record->status;
    }
}