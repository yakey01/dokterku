<?php

namespace App\Filament\Manajer\Resources\HighValueApprovalResource\Pages;

use App\Filament\Manajer\Resources\HighValueApprovalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateHighValueApproval extends CreateRecord
{
    protected static string $resource = HighValueApprovalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = Auth::id();
        $data['requester_role'] = Auth::user()?->roles?->first()?->name ?? 'manajer';
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}