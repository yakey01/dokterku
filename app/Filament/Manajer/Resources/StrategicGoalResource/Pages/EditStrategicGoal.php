<?php

namespace App\Filament\Manajer\Resources\StrategicGoalResource\Pages;

use App\Filament\Manajer\Resources\StrategicGoalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStrategicGoal extends EditRecord
{
    protected static string $resource = StrategicGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return '✏️ Edit Strategic Goal';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '✅ Strategic goal updated successfully!';
    }
}