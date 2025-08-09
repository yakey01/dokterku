<?php

namespace App\Filament\Resources\JadwalJagaResource\Pages;

use App\Filament\Resources\JadwalJagaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJadwalJaga extends CreateRecord
{
    protected static string $resource = JadwalJagaResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Debug: Log semua data yang diterima
        \Log::info('Form data received:', $data);
        
        return $data;
    }
    
    protected function beforeValidate(): void
    {
        // Debug: Log validation rules yang aktif
        \Log::info('Validation about to run');
    }
    
    // Override validation to skip problematic rules
    protected function getFormValidationAttributes(): array
    {
        return [
            'tanggal_jaga' => 'Tanggal Jaga',
            'shift_template_id' => 'Template Shift',
            'pegawai_id' => 'Pegawai',
            'unit_kerja' => 'Unit Kerja',
            'peran' => 'Peran',
            'status_jaga' => 'Status Jaga'
        ];
    }
    
    // Override form validation rules
    protected function getFormValidationRules(): array
    {
        return [
            'tanggal_jaga' => ['required', 'date'],
            'shift_template_id' => ['required', 'exists:shift_templates,id'],
            'pegawai_id' => ['required', 'exists:users,id'],
            'unit_kerja' => ['required', 'string'],
            'peran' => ['required', 'string'],
            'status_jaga' => ['required', 'string'],
            'keterangan' => ['nullable', 'string'],
            'jam_jaga_custom' => ['nullable', 'date_format:H:i'],
            // Custom validation to prevent duplicates
            'pegawai_id' => [
                'required', 
                'exists:users,id',
                function (string $attribute, $value, \Closure $fail) {
                    $tanggalJaga = request()->input('tanggal_jaga');
                    $shiftTemplateId = request()->input('shift_template_id');
                    
                    if ($tanggalJaga && $shiftTemplateId && $value) {
                        $exists = \App\Models\JadwalJaga::whereDate('tanggal_jaga', $tanggalJaga)
                            ->where('shift_template_id', $shiftTemplateId)
                            ->where('pegawai_id', $value)
                            ->exists();
                            
                        if ($exists) {
                            $pegawai = \App\Models\User::find($value);
                            $shiftTemplate = \App\Models\ShiftTemplate::find($shiftTemplateId);
                            $fail("Pegawai {$pegawai->name} sudah memiliki jadwal jaga untuk shift {$shiftTemplate->nama_shift} pada tanggal " . \Carbon\Carbon::parse($tanggalJaga)->format('d/m/Y') . ".");
                        }
                    }
                }
            ]
        ];
    }
    
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation
                $pegawai = \App\Models\User::find($data['pegawai_id']);
                $shiftTemplate = \App\Models\ShiftTemplate::find($data['shift_template_id']);
                $tanggal = \Carbon\Carbon::parse($data['tanggal_jaga'])->format('d/m/Y');
                
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('Jadwal Jaga Sudah Ada')
                    ->body("Pegawai {$pegawai->name} sudah memiliki jadwal jaga untuk shift {$shiftTemplate->nama_shift} pada tanggal {$tanggal}.")
                    ->send();
                    
                throw new \Exception("Jadwal jaga sudah ada untuk pegawai ini pada tanggal dan shift yang sama.");
            }
            
            throw $e;
        }
    }

    /**
     * Clear cache after creating a new schedule
     * This ensures the schedule appears immediately in the mobile app
     */
    protected function afterCreate(): void
    {
        $record = $this->record;
        
        // Clear multiple cache keys to ensure fresh data
        $tanggal = \Carbon\Carbon::parse($record->tanggal_jaga);
        $month = $tanggal->month;
        $year = $tanggal->year;
        
        // Clear schedule cache for the specific user and month
        \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_{$record->pegawai_id}_{$month}_{$year}");
        
        // Clear dashboard stats cache
        \Illuminate\Support\Facades\Cache::forget("dokter_dashboard_stats_{$record->pegawai_id}");
        
        // Clear weekly cache (if exists)
        \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_weekly_{$record->pegawai_id}");
        
        // Clear test cache (if exists)
        \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_test_{$record->pegawai_id}_{$month}_{$year}");
        
        // Log cache clearing for debugging
        \Log::info("Cache cleared after creating jadwal jaga", [
            'pegawai_id' => $record->pegawai_id,
            'tanggal_jaga' => $record->tanggal_jaga,
            'month' => $month,
            'year' => $year,
            'cache_keys_cleared' => [
                "jadwal_jaga_{$record->pegawai_id}_{$month}_{$year}",
                "dokter_dashboard_stats_{$record->pegawai_id}",
                "jadwal_jaga_weekly_{$record->pegawai_id}",
                "jadwal_jaga_test_{$record->pegawai_id}_{$month}_{$year}"
            ]
        ]);
        
        // Show success notification
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Jadwal berhasil dibuat')
            ->body('Cache telah dibersihkan, jadwal akan segera muncul di aplikasi.')
            ->send();
    }
}
