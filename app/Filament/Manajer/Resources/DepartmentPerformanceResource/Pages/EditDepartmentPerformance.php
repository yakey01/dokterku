<?php

namespace App\Filament\Manajer\Resources\DepartmentPerformanceResource\Pages;

use App\Filament\Manajer\Resources\DepartmentPerformanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepartmentPerformance extends EditRecord
{
    protected static string $resource = DepartmentPerformanceResource::class;

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