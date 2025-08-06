<?php

namespace App\Filament\Resources\UserScheduleResource\Pages;

use App\Filament\Resources\UserScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserSchedule extends EditRecord
{
    protected static string $resource = UserScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        // Auto-calculate work duration if not provided
        if (!$data['work_duration_minutes'] && $data['check_in_time'] && $data['check_out_time']) {
            $checkIn = \Carbon\Carbon::createFromFormat('H:i', $data['check_in_time']);
            $checkOut = \Carbon\Carbon::createFromFormat('H:i', $data['check_out_time']);
            $data['work_duration_minutes'] = $checkOut->diffInMinutes($checkIn);
        }
        
        return $data;
    }
}