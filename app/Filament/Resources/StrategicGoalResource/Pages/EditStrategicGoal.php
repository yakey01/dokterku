<?php

namespace App\Filament\Resources\StrategicGoalResource\Pages;

use App\Filament\Resources\StrategicGoalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStrategicGoal extends EditRecord
{
    protected static string $resource = StrategicGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
