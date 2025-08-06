<?php

namespace App\Filament\Resources\AttendanceToleranceSettingResource\Pages;

use App\Filament\Resources\AttendanceToleranceSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceToleranceSetting extends CreateRecord
{
    protected static string $resource = AttendanceToleranceSettingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}