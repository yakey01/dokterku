<?php

namespace App\Filament\Resources\AttendanceToleranceSettingResource\Pages;

use App\Filament\Resources\AttendanceToleranceSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceToleranceSetting extends ViewRecord
{
    protected static string $resource = AttendanceToleranceSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}