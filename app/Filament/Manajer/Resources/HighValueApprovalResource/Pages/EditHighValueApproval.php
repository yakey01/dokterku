<?php

namespace App\Filament\Manajer\Resources\HighValueApprovalResource\Pages;

use App\Filament\Manajer\Resources\HighValueApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHighValueApproval extends EditRecord
{
    protected static string $resource = HighValueApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}