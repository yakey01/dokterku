<?php

namespace App\Filament\Resources\JadwalJagaResource\Pages;

use App\Filament\Resources\JadwalJagaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJadwalJaga extends EditRecord
{
    protected static string $resource = JadwalJagaResource::class;

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
    
    // Debug logging for edit
    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::info('Edit form data received:', $data);
        return $data;
    }
    
    protected function beforeValidate(): void
    {
        \Log::info('Edit validation about to run');
    }
    
    // Override validation attributes
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
    
    // Override form validation rules - SAME as Create
    protected function getFormValidationRules(): array
    {
        return [
            'tanggal_jaga' => ['required', 'date'], // NO timestamp validation
            'shift_template_id' => ['required', 'exists:shift_templates,id'],
            'pegawai_id' => ['required', 'exists:users,id'],
            'unit_kerja' => ['required', 'string'],
            'peran' => ['required', 'string'],
            'status_jaga' => ['required', 'string'],
            'keterangan' => ['nullable', 'string'],
            'jam_jaga_custom' => ['nullable', 'date_format:H:i']
        ];
    }

    /**
     * Clear cache after updating a schedule
     * This ensures the schedule updates appear immediately in the mobile app
     */
    protected function afterSave(): void
    {
        $record = $this->record;
        
        // Clear cache for both old and new dates (in case date was changed)
        $oldTanggal = $record->getOriginal('tanggal_jaga');
        $newTanggal = $record->tanggal_jaga;
        
        // Clear cache for new date
        $tanggal = \Carbon\Carbon::parse($newTanggal);
        $month = $tanggal->month;
        $year = $tanggal->year;
        
        \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_{$record->pegawai_id}_{$month}_{$year}");
        
        // If date changed, also clear cache for old date
        if ($oldTanggal && $oldTanggal != $newTanggal) {
            $oldDate = \Carbon\Carbon::parse($oldTanggal);
            $oldMonth = $oldDate->month;
            $oldYear = $oldDate->year;
            \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_{$record->pegawai_id}_{$oldMonth}_{$oldYear}");
        }
        
        // Clear dashboard and other caches
        \Illuminate\Support\Facades\Cache::forget("dokter_dashboard_stats_{$record->pegawai_id}");
        \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_weekly_{$record->pegawai_id}");
        \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_test_{$record->pegawai_id}_{$month}_{$year}");
        
        // If pegawai changed, clear cache for old pegawai too
        $oldPegawaiId = $record->getOriginal('pegawai_id');
        if ($oldPegawaiId && $oldPegawaiId != $record->pegawai_id) {
            \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_{$oldPegawaiId}_{$month}_{$year}");
            \Illuminate\Support\Facades\Cache::forget("dokter_dashboard_stats_{$oldPegawaiId}");
            \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_weekly_{$oldPegawaiId}");
        }
        
        // Log cache clearing for debugging
        \Log::info("Cache cleared after updating jadwal jaga", [
            'pegawai_id' => $record->pegawai_id,
            'old_pegawai_id' => $oldPegawaiId,
            'tanggal_jaga' => $newTanggal,
            'old_tanggal' => $oldTanggal,
            'caches_cleared' => true
        ]);
        
        // Show success notification
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Jadwal berhasil diperbarui')
            ->body('Cache telah dibersihkan, perubahan akan segera muncul di aplikasi.')
            ->send();
    }

    /**
     * Clear cache after deleting a schedule
     */
    protected function afterDelete(): void
    {
        $record = $this->record;
        
        $tanggal = \Carbon\Carbon::parse($record->tanggal_jaga);
        $month = $tanggal->month;
        $year = $tanggal->year;
        
        // Clear all related caches
        \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_{$record->pegawai_id}_{$month}_{$year}");
        \Illuminate\Support\Facades\Cache::forget("dokter_dashboard_stats_{$record->pegawai_id}");
        \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_weekly_{$record->pegawai_id}");
        \Illuminate\Support\Facades\Cache::forget("jadwal_jaga_test_{$record->pegawai_id}_{$month}_{$year}");
        
        \Log::info("Cache cleared after deleting jadwal jaga", [
            'pegawai_id' => $record->pegawai_id,
            'tanggal_jaga' => $record->tanggal_jaga
        ]);
    }
}
