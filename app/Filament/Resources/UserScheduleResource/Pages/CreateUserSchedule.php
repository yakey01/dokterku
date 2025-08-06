<?php

namespace App\Filament\Resources\UserScheduleResource\Pages;

use App\Filament\Resources\UserScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserSchedule extends CreateRecord
{
    protected static string $resource = UserScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Auto-calculate work duration if not provided
        if (!$data['work_duration_minutes'] && $data['check_in_time'] && $data['check_out_time']) {
            $checkIn = \Carbon\Carbon::createFromFormat('H:i', $data['check_in_time']);
            $checkOut = \Carbon\Carbon::createFromFormat('H:i', $data['check_out_time']);
            $data['work_duration_minutes'] = $checkOut->diffInMinutes($checkIn);
        }
        
        return $data;
    }
}