<?php

namespace App\Filament\Manajer\Resources\DepartmentPerformanceResource\Pages;

use App\Filament\Manajer\Resources\DepartmentPerformanceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDepartmentPerformance extends CreateRecord
{
    protected static string $resource = DepartmentPerformanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = Auth::id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}