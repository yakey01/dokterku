<?php

namespace App\Filament\Resources\DokterPresensiResource\Pages;

use App\Filament\Resources\DokterPresensiResource;
use App\Models\DokterPresensi;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateDokterPresensi extends CreateRecord
{
    protected static string $resource = DokterPresensiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        // Check if doctor already has attendance for today
        $existingAttendance = DokterPresensi::where('dokter_id', $this->data['dokter_id'])
            ->whereDate('tanggal', $this->data['tanggal'])
            ->first();

        if ($existingAttendance) {
            Notification::make()
                ->title('Attendance Already Exists')
                ->body('This doctor already has an attendance record for the selected date.')
                ->danger()
                ->send();
                
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        
        Notification::make()
            ->title('Attendance Created Successfully')
            ->body("Attendance record created for Dr. {$record->dokter->nama} on {$record->tanggal->format('d M Y')}")
            ->success()
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure the date is today or earlier
        if (isset($data['tanggal']) && \Carbon\Carbon::parse($data['tanggal'])->isFuture()) {
            Notification::make()
                ->title('Invalid Date')
                ->body('Cannot create attendance for future dates.')
                ->danger()
                ->send();
                
            $this->halt();
        }

        return $data;
    }

    public function getTitle(): string
    {
        return 'Create Doctor Attendance';
    }

    public function getSubheading(): ?string
    {
        return 'Record a new attendance entry for a doctor';
    }
}