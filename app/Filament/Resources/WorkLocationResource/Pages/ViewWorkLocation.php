<?php

namespace App\Filament\Resources\WorkLocationResource\Pages;

use App\Filament\Resources\WorkLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkLocation extends ViewRecord
{
    protected static string $resource = WorkLocationResource::class;
    
    /**
     * Resolve the record including soft-deleted records
     */
    public function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getModel()::withTrashed()->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('✏️ Edit Lokasi')
                ->color('warning'),
            Actions\DeleteAction::make()
                ->label('🗑️ Hapus Lokasi')
                ->color('danger'),
        ];
    }

    public function getTitle(): string
    {
        return '👁️ Detail Lokasi Kerja';
    }

    public function getHeading(): string
    {
        return '👁️ Detail Lokasi Kerja';
    }

    public function getSubheading(): ?string
    {
        $baseSubheading = 'Informasi lengkap lokasi kerja dan pengaturan geofencing';
        
        // Add warning for soft-deleted records
        if ($this->record && $this->record->trashed()) {
            return '🗑️ Lokasi ini telah dihapus (Soft Delete). Gunakan "Restore" untuk mengaktifkan kembali. ' . $baseSubheading;
        }
        
        return $baseSubheading;
    }
}