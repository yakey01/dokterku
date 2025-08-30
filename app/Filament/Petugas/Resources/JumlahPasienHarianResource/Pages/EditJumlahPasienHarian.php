<?php

namespace App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditJumlahPasienHarian extends EditRecord
{
    protected static string $resource = JumlahPasienHarianResource::class;
    // Using standard Filament edit view to avoid conflicts
    // protected static string $view = 'filament.petugas.pages.elegant-black-edit';
    
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        \Log::info('EditJumlahPasienHarian: Loading form data', [
            'record_id' => $this->record->id,
            'has_shift_template_id' => isset($data['shift_template_id']),
            'shift_value' => $data['shift'] ?? 'NULL',
            'auth_user_id' => auth()->id()
        ]);
        
        // Ensure shift_template_id is set for backward compatibility
        if (!isset($data['shift_template_id']) && isset($data['shift'])) {
            // Try to find matching shift template by name
            $shiftTemplate = \App\Models\ShiftTemplate::where('nama_shift', $data['shift'])->first();
            if ($shiftTemplate) {
                $data['shift_template_id'] = $shiftTemplate->id;
                \Log::info('Auto-mapped shift to shift_template_id', [
                    'shift' => $data['shift'],
                    'shift_template_id' => $shiftTemplate->id
                ]);
            }
        }
        
        return $data;
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
        \Log::info('EditJumlahPasienHarian: Starting mutateFormDataBeforeSave', [
            'record_id' => $this->record->id,
            'auth_user_id' => auth()->id(),
            'auth_user_name' => auth()->user()?->name,
            'data_keys' => array_keys($data)
        ]);
        
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
        
        // VALIDATION STATUS RESET LOGIC
        // If currently approved record is being edited, reset to pending
        $isCurrentlyApproved = in_array($this->record->status_validasi, ['disetujui', 'approved']);
        if ($this->record && $isCurrentlyApproved) {
            // Critical fields that require re-validation
            $criticalFields = [
                'jumlah_pasien_umum',
                'jumlah_pasien_bpjs', 
                'jaspel_rupiah',
                'dokter_id',
                'tanggal',
                'shift',
                'poli'
            ];
            
            // Check if any critical field was changed
            $hasChanges = false;
            $changedFields = [];
            
            foreach ($criticalFields as $field) {
                if (isset($data[$field]) && $data[$field] != $this->record->{$field}) {
                    $hasChanges = true;
                    $changedFields[] = $field;
                }
            }
            
            if ($hasChanges) {
                // Reset validation status
                $data['status_validasi'] = 'pending';
                $data['validasi_by'] = null;
                $data['validasi_at'] = null;
                $data['catatan_validasi'] = 'Data diubah oleh petugas - perlu validasi ulang. Fields: ' . implode(', ', $changedFields);
                
                \Log::info('JumlahPasienHarian validation status reset in Filament edit', [
                    'id' => $this->record->id,
                    'original_status' => $this->record->status_validasi,
                    'new_status' => 'pending',
                    'changed_fields' => $changedFields,
                    'edited_by' => auth()->id(),
                    'user_name' => auth()->user()?->name ?? 'Unknown'
                ]);

                // Fire event for bendahara notification
                try {
                    event(new \App\Events\ValidationStatusReset([
                        'model_type' => 'JumlahPasienHarian',
                        'model_id' => $this->record->id,
                        'original_status' => $this->record->status_validasi, 
                        'new_status' => 'pending',
                        'changed_fields' => $changedFields,
                        'edited_by' => auth()->id(),
                        'user_name' => auth()->user()?->name ?? 'System',
                        'date' => $this->record->tanggal?->format('d/m/Y'),
                        'doctor' => $this->record->dokter?->nama ?? 'Unknown',
                        'total_pasien' => ($data['jumlah_pasien_umum'] ?? 0) + ($data['jumlah_pasien_bpjs'] ?? 0)
                    ]));
                } catch (\Exception $e) {
                    \Log::error('Failed to fire ValidationStatusReset event', [
                        'model' => 'JumlahPasienHarian',
                        'id' => $this->record->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Show notification to user
                Notification::make()
                    ->warning()
                    ->title('Status Validasi Di-reset')
                    ->body('Data yang sudah disetujui telah diubah. Status dikembalikan ke "Menunggu" dan akan dikirim notifikasi ke bendahara.')
                    ->persistent()
                    ->send();
            }
        }
        
        \Log::info('EditJumlahPasienHarian: Completed mutateFormDataBeforeSave', [
            'record_id' => $this->record->id,
            'final_data_keys' => array_keys($data),
            'auth_still_valid' => auth()->check()
        ]);
        
        return $data;
    }
}