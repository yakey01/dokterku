<?php

namespace App\Filament\Resources\WorkLocationResource\Pages;

use App\Filament\Resources\WorkLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EditWorkLocation extends EditRecord
{
    protected static string $resource = WorkLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('👁️ Lihat Detail')
                ->color('info'),
            Actions\DeleteAction::make()
                ->label('🗑️ Hapus Lokasi')
                ->color('danger'),
        ];
    }

    public function getTitle(): string
    {
        return '✏️ Edit Lokasi Kerja';
    }

    public function getHeading(): string
    {
        return '✏️ Edit Lokasi Kerja';
    }

    public function getSubheading(): ?string
    {
        return 'Perbarui konfigurasi lokasi kerja dan pengaturan geofencing';
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('✅ Lokasi Kerja Berhasil Diperbarui!')
            ->body('Perubahan konfigurasi lokasi kerja telah disimpan.')
            ->duration(4000);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Handle after save to ensure immediate cache clearing and real-time updates
     */
    protected function afterSave(): void
    {
        $workLocation = $this->record;
        
        Log::info('WorkLocation updated via Filament Admin', [
            'id' => $workLocation->id,
            'name' => $workLocation->name,
            'admin_user' => auth()->user()?->name,
            'timestamp' => now()
        ]);

        // Manual cache clearing for immediate effect
        $this->clearWorkLocationCaches($workLocation);
        
        // Send additional notification about real-time update
        Notification::make()
            ->success()
            ->title('🔄 Update Real-time Berhasil!')
            ->body('Semua dashboard pengguna akan otomatis ter-update dengan lokasi terbaru.')
            ->duration(3000)
            ->send();
    }

    /**
     * Clear all caches related to this work location
     */
    private function clearWorkLocationCaches($workLocation): void
    {
        // Clear specific location cache
        Cache::forget("work_location_{$workLocation->id}");
        
        // Get affected users and clear their caches
        $users = $workLocation->users()->get(['id', 'name']);
        
        foreach ($users as $user) {
            $cacheKeys = [
                "paramedis_dashboard_stats_{$user->id}",
                "user_work_location_{$user->id}",
                "attendance_status_{$user->id}",
                "user_attendance_validation_{$user->id}",
                "user_geofence_data_{$user->id}",
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
        }

        // Clear general caches
        Cache::forget('work_locations_active');
        Cache::forget('work_locations_all');
        Cache::forget('geofence_locations');

        Log::info('Work location caches cleared from Filament admin', [
            'work_location_id' => $workLocation->id,
            'affected_users_count' => $users->count(),
            'cleared_at' => now()
        ]);
    }
}