<?php

namespace App\Filament\Bendahara\Resources\ValidationCenterResource\Pages;

use App\Filament\Bendahara\Resources\ValidationCenterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditValidation extends EditRecord
{
    protected static string $resource = ValidationCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // AUDIT NOTE: Status synchronization is handled by model boot method
        // This ensures consistent behavior across all update methods
        // No need to duplicate logic here - model will handle it automatically
        
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Log the status change for audit trail
        if ($record->isDirty('status_validasi')) {
            \Log::info('Validation status changed via edit form', [
                'record_id' => $record->id,
                'old_status_validasi' => $record->getOriginal('status_validasi'),
                'new_status_validasi' => $data['status_validasi'],
                'auto_updated_status' => $data['status'],
                'updated_by' => Auth::id(),
                'updated_by_name' => Auth::user()?->name,
                'timestamp' => now()
            ]);
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}