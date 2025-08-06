<?php

namespace App\Filament\Resources\AttendanceToleranceSettingResource\Pages;

use App\Filament\Resources\AttendanceToleranceSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceToleranceSetting extends EditRecord
{
    protected static string $resource = AttendanceToleranceSettingResource::class;

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
        return $data;
    }
}