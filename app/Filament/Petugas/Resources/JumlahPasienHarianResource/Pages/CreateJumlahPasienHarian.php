<?php

namespace App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateJumlahPasienHarian extends CreateRecord
{
    protected static string $resource = JumlahPasienHarianResource::class;
    
    protected static string $view = 'filament.petugas.pages.jumlah-pasien-create';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    /**
     * World-Class Form Actions - Professional Healthcare UX
     * Extends default Filament actions with enhanced user experience
     */
    protected function getFormActions(): array
    {
        return [
            // Enhanced Primary Create Action
            $this->getCreateFormAction()
                ->label('Simpan Data Pasien')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->size('lg')
                ->extraAttributes([
                    'class' => 'world-class-save-btn',
                    'style' => 'color: #000000 !important; -webkit-text-fill-color: #000000 !important; text-shadow: none !important;',
                ])
                ->keyBindings(['mod+s'])
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Penyimpanan Data Pasien')
                ->modalDescription('Pastikan data jumlah pasien sudah benar sebelum disimpan. Data ini akan digunakan untuk perhitungan jaspel.')
                ->modalSubmitActionLabel('Ya, Simpan Data')
                ->modalCancelActionLabel('Periksa Kembali'),

            // Save & Create Another Action
            $this->getCreateAnotherFormAction()
                ->label('Simpan & Tambah Lagi')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->size('lg')
                ->extraAttributes([
                    'class' => 'world-class-continue-btn',
                    'style' => 'color: #000000 !important; -webkit-text-fill-color: #000000 !important; text-shadow: none !important;',
                ])
                ->keyBindings(['mod+shift+s']),

            // Professional Cancel Action
            $this->getCancelFormAction()
                ->label('❌ Batal & Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->size('lg')
                ->extraAttributes([
                    'class' => 'world-class-cancel-btn',
                    'style' => 'color: #000000 !important; -webkit-text-fill-color: #000000 !important; text-shadow: none !important;',
                ])
                ->keyBindings(['escape'])
                ->requiresConfirmation()
                ->modalHeading('⚠️ Batalkan Input Data?')
                ->modalDescription('Data yang sudah diisi akan hilang. Pastikan Anda sudah menyimpan jika diperlukan.')
                ->modalSubmitActionLabel('Ya, Batalkan')
                ->modalCancelActionLabel('Tetap Di Halaman Ini')
                ->modalIcon('heroicon-o-exclamation-triangle'),
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Data Pasien Berhasil Disimpan')
            ->body('Data jumlah pasien harian telah berhasil ditambahkan.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['input_by'] = auth()->id();
        
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
                // Log error but don't fail the creation
                \Log::warning('Failed to calculate jaspel during record creation', [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
            }
        }
        
        return $data;
    }
}