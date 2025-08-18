<?php

namespace App\Filament\Manajer\Resources\StrategicGoalResource\Pages;

use App\Filament\Manajer\Resources\StrategicGoalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStrategicGoal extends CreateRecord
{
    protected static string $resource = StrategicGoalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return '🎯 Create Strategic Goal';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Strategic goal created successfully!';
    }
}