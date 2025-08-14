<?php

namespace App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditJumlahPasienHarian extends EditRecord
{
    protected static string $resource = JumlahPasienHarianResource::class;

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

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Pasien Berhasil Diupdate')
            ->body('Data jumlah pasien harian telah berhasil diperbarui.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-calculate jaspel_rupiah using UnifiedJaspelCalculationService
        if (isset($data['jumlah_pasien_umum']) && isset($data['jumlah_pasien_bpjs']) && isset($data['shift'])) {
            try {
                $calculationService = app(\App\Services\Jaspel\UnifiedJaspelCalculationService::class);
                $calculation = $calculationService->calculateEstimated(
                    $data['jumlah_pasien_umum'],
                    $data['jumlah_pasien_bpjs'],
                    $data['shift']
                );
                
                if (!isset($calculation['error'])) {
                    $data['jaspel_rupiah'] = $calculation['total'];
                }
            } catch (\Exception $e) {
                // Log error but don't fail the update
                \Log::warning('Failed to calculate jaspel during record update', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'record_id' => $this->record->id
                ]);
            }
        }
        
        return $data;
    }
}