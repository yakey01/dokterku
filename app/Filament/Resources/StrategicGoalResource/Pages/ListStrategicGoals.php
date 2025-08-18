<?php

namespace App\Filament\Resources\StrategicGoalResource\Pages;

use App\Filament\Resources\StrategicGoalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStrategicGoals extends ListRecords
{
    protected static string $resource = StrategicGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
