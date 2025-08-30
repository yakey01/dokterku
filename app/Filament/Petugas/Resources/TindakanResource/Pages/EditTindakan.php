<?php

namespace App\Filament\Petugas\Resources\TindakanResource\Pages;

use App\Filament\Petugas\Resources\TindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTindakan extends EditRecord
{
    protected static string $resource = TindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // VALIDATION STATUS RESET LOGIC
        // If currently approved record is being edited, reset to pending
        $isCurrentlyApproved = in_array($this->record->status_validasi, ['disetujui', 'approved']);
        if ($this->record && $isCurrentlyApproved) {
            // Critical fields that require re-validation
            $criticalFields = [
                'pasien_id',
                'jenis_tindakan_id',
                'dokter_id',
                'paramedis_id',
                'tanggal_tindakan',
                'tarif',
                'jasa_dokter',
                'jasa_paramedis',
                'jasa_non_paramedis',
                'shift_id'
            ];
            
            // Check if any critical field was changed
            $hasChanges = false;
            $changedFields = [];
            
            foreach ($criticalFields as $field) {
                if (isset($data[$field])) {
                    // Handle date comparison for tanggal_tindakan
                    if ($field === 'tanggal_tindakan') {
                        $newDate = is_string($data[$field]) ? $data[$field] : $data[$field]->format('Y-m-d H:i:s');
                        $oldDate = $this->record->{$field}?->format('Y-m-d H:i:s');
                        if ($newDate != $oldDate) {
                            $hasChanges = true;
                            $changedFields[] = $field;
                        }
                    } else {
                        // Regular field comparison
                        if ($data[$field] != $this->record->{$field}) {
                            $hasChanges = true;
                            $changedFields[] = $field;
                        }
                    }
                }
            }
            
            if ($hasChanges) {
                // Reset validation status
                $data['status_validasi'] = 'pending';
                $data['validated_by'] = null;
                $data['validated_at'] = null;
                $data['komentar_validasi'] = 'Data diubah oleh petugas - perlu validasi ulang. Fields: ' . implode(', ', $changedFields);
                
                \Log::info('Tindakan validation status reset in Filament edit', [
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
                        'model_type' => 'Tindakan',
                        'model_id' => $this->record->id,
                        'original_status' => $this->record->status_validasi, 
                        'new_status' => 'pending',
                        'changed_fields' => $changedFields,
                        'edited_by' => auth()->id(),
                        'user_name' => auth()->user()?->name ?? 'System',
                        'patient' => $this->record->pasien?->nama ?? 'Unknown',
                        'procedure' => $this->record->jenisTindakan?->nama ?? 'Unknown',
                        'doctor' => $this->record->dokter?->nama ?? 'Unknown',
                        'tarif' => $data['tarif'] ?? $this->record->tarif,
                        'date' => isset($data['tanggal_tindakan']) 
                            ? (is_string($data['tanggal_tindakan']) ? $data['tanggal_tindakan'] : $data['tanggal_tindakan']->format('d/m/Y'))
                            : $this->record->tanggal_tindakan?->format('d/m/Y')
                    ]));
                } catch (\Exception $e) {
                    \Log::error('Failed to fire ValidationStatusReset event', [
                        'model' => 'Tindakan',
                        'id' => $this->record->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Show notification to user
                Notification::make()
                    ->warning()
                    ->title('Status Validasi Di-reset')
                    ->body('Data tindakan yang sudah disetujui telah diubah. Status dikembalikan ke "Menunggu" dan akan dikirim notifikasi ke bendahara.')
                    ->persistent()
                    ->send();
            }
        }
        
        return $data;
    }
}