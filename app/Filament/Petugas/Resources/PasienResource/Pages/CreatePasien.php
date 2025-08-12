<?php

namespace App\Filament\Petugas\Resources\PasienResource\Pages;

use App\Filament\Petugas\Resources\PasienResource;
use App\Models\Pasien;
use Filament\Resources\Pages\CreateRecord;

class CreatePasien extends CreateRecord
{
    protected static string $resource = PasienResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate no_rekam_medis if not provided
        if (empty($data['no_rekam_medis'])) {
            $data['no_rekam_medis'] = 'RM-' . date('Y') . '-' . str_pad(Pasien::count() + 1, 3, '0', STR_PAD_LEFT);
        }
        
        // Set input_by to current authenticated user
        $data['input_by'] = auth()->id();
        
        // Set status to verified (auto-active)
        $data['status'] = 'verified';
        $data['verified_at'] = now();
        $data['verified_by'] = auth()->id();
        
        return $data;
    }

    protected function getFormSchema(): array
    {
        return [
            'no_rekam_medis' => 'nullable|string|max:20|unique:pasien,no_rekam_medis',
            'nama' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date|before_or_equal:today',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'nullable|string|max:500',
            'no_telepon' => 'nullable|string|max:20|regex:/^[\d\-\+\(\)\s]+$/',
            'email' => 'nullable|email|max:255|unique:pasien,email',
            'pekerjaan' => 'nullable|string|max:255',
            'status_pernikahan' => 'nullable|in:belum_menikah,menikah,janda,duda',
            'kontak_darurat_nama' => 'nullable|string|max:255',
            'kontak_darurat_telepon' => 'nullable|string|max:20|regex:/^[\d\-\+\(\)\s]+$/',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('create');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pasien berhasil disimpan dan langsung aktif!';
    }

    protected function afterCreate(): void
    {
        // Clear the form for next input
        $this->form->fill();
        
        // Show success message
        \Filament\Notifications\Notification::make()
            ->title('✅ Data Pasien Berhasil Disimpan')
            ->body('Pasien telah terdaftar dan langsung aktif. Form siap untuk input pasien berikutnya.')
            ->success()
            ->persistent()
            ->send();
    }
}