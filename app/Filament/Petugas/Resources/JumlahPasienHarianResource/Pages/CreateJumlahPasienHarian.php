<?php

namespace App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateJumlahPasienHarian extends CreateRecord
{
    protected static string $resource = JumlahPasienHarianResource::class;

    protected function getRedirectUrl(): string
    {
        // Smart redirect based on user intent
        if (request()->has('create_another')) {
            return $this->getResource()::getUrl('create');
        }
        
        return $this->getResource()::getUrl('index');
    }
    
    protected function afterCreate(): void
    {
        // Log successful creation for audit trail
        \Log::info('Jumlah Pasien Harian created successfully', [
            'record_id' => $this->record->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'tanggal' => $this->record->tanggal,
            'poli' => $this->record->poli,
            'shift' => $this->record->shift,
            'dokter_id' => $this->record->dokter_id,
            'total_pasien' => $this->record->jumlah_pasien_umum + $this->record->jumlah_pasien_bpjs,
            'created_at' => now()
        ]);
    }
    
    
    /**
     * Customize page subtitle with healthcare context
     */
    public function getSubheading(): ?string
    {
        return '🩺 Masukkan data jumlah pasien per hari untuk perhitungan jaspel yang akurat. Data akan digunakan untuk sistem jasa pelayanan dokter.';
    }
    
    /**
     * Add healthcare breadcrumbs
     */
    public function getBreadcrumbs(): array
    {
        return [
            '/petugas' => '🏠 Dashboard',
            '/petugas/jumlah-pasien-harians' => '📊 Data Jumlah Pasien',
            '' => '➕ Input Data Baru',
        ];
    }
    
    /**
     * World-Class Healthcare Form Actions
     * Professional medical interface with enhanced UX patterns
     */
    protected function getFormActions(): array
    {
        return [
            // Primary Save Action - Enhanced Healthcare UX
            $this->getCreateFormAction()
                ->label('💾 Simpan Data Pasien')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->size('lg')
                ->extraAttributes([
                    'class' => 'world-class-save-btn healthcare-primary-btn',
                    'data-tooltip' => 'Simpan data pasien harian (Ctrl+S)',
                ])
                ->keyBindings(['mod+s'])
                ->requiresConfirmation()
                ->modalHeading('🏥 Konfirmasi Penyimpanan Data Medis')
                ->modalDescription('Pastikan data jumlah pasien sudah benar dan sesuai dengan catatan medis. Data ini akan digunakan untuk perhitungan jasa pelayanan dokter.')
                ->modalSubmitActionLabel('✅ Ya, Simpan ke Sistem')
                ->modalCancelActionLabel('📝 Periksa Kembali')
                ->modalIcon('heroicon-o-shield-check'),

            // Continue Working Action
            $this->getCreateAnotherFormAction()
                ->label('➕ Simpan & Input Lagi')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->size('lg')
                ->extraAttributes([
                    'class' => 'world-class-continue-btn healthcare-secondary-btn',
                    'data-tooltip' => 'Simpan dan lanjut input data berikutnya (Ctrl+Shift+S)',
                ])
                ->keyBindings(['mod+shift+s'])
                ->modalHeading('🔄 Lanjutkan Input Data')
                ->modalDescription('Data akan disimpan dan form akan dikosongkan untuk input data berikutnya.')
                ->modalSubmitActionLabel('✅ Simpan & Lanjutkan')
                ->modalCancelActionLabel('❌ Batal'),

            // Professional Cancel Action
            $this->getCancelFormAction()
                ->label('🔙 Batal & Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->size('lg')
                ->extraAttributes([
                    'class' => 'world-class-cancel-btn healthcare-cancel-btn',
                    'data-tooltip' => 'Kembali tanpa menyimpan (Esc)',
                ])
                ->keyBindings(['escape'])
                ->requiresConfirmation()
                ->modalHeading('⚠️ Batalkan Input Data?')
                ->modalDescription('Data yang sudah diisi akan hilang dan tidak dapat dikembalikan. Pastikan Anda sudah menyimpan data penting.')
                ->modalSubmitActionLabel('🗑️ Ya, Buang Data')
                ->modalCancelActionLabel('📝 Tetap Di Halaman')
                ->modalIcon('heroicon-o-exclamation-triangle'),
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('🏥 Data Pasien Berhasil Disimpan')
            ->body('Data jumlah pasien harian telah berhasil ditambahkan ke sistem. Jaspel akan dihitung otomatis.')
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->duration(5000)
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('📊 Lihat Data')
                    ->url(fn () => $this->getResource()::getUrl('index'))
                    ->button(),
                \Filament\Notifications\Actions\Action::make('create_another')
                    ->label('➕ Input Lagi')
                    ->url(fn () => $this->getResource()::getUrl('create'))
                    ->button()
                    ->color('warning'),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set audit trail
        $data['input_by'] = auth()->id();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        
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
                    
                    // Log successful calculation for audit
                    \Log::info('Jaspel calculated successfully', [
                        'user_id' => auth()->id(),
                        'pasien_umum' => $data['jumlah_pasien_umum'],
                        'pasien_bpjs' => $data['jumlah_pasien_bpjs'],
                        'shift' => $data['shift'],
                        'jaspel_total' => $calculation['total'],
                        'timestamp' => now()
                    ]);
                } else {
                    // Log calculation error
                    \Log::warning('Jaspel calculation returned error', [
                        'error' => $calculation['error'],
                        'data' => $data,
                        'user_id' => auth()->id()
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the creation
                \Log::error('Failed to calculate jaspel during record creation', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'user_id' => auth()->id(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Show warning notification
                Notification::make()
                    ->warning()
                    ->title('⚠️ Peringatan Perhitungan')
                    ->body('Jaspel tidak dapat dihitung otomatis. Silakan hitung manual atau hubungi admin.')
                    ->persistent()
                    ->send();
            }
        }
        
        return $data;
    }
}