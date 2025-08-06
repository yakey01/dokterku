<?php

namespace App\Filament\Resources\AttendanceToleranceSettingResource\Pages;

use App\Filament\Resources\AttendanceToleranceSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceToleranceSettings extends ListRecords
{
    protected static string $resource = AttendanceToleranceSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}