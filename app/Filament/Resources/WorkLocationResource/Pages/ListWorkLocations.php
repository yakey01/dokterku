<?php

namespace App\Filament\Resources\WorkLocationResource\Pages;

use App\Filament\Resources\WorkLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkLocations extends ListRecords
{
    protected static string $resource = WorkLocationResource::class;
    
    /**
     * Resolve the record including soft-deleted records
     * This is essential for ForceDeleteAction to work with trashed records
     */
    public function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getModel()::withTrashed()->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('➕ Tambah Lokasi Kerja')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),
        ];
    }

    public function getTitle(): string
    {
        return '📍 Validasi Lokasi (Geofencing)';
    }

    public function getHeading(): string
    {
        return '📍 Validasi Lokasi (Geofencing)';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola lokasi kerja yang diizinkan untuk absensi dengan teknologi geofencing GPS';
    }
}