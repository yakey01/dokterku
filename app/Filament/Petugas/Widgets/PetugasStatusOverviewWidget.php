<?php

namespace App\Filament\Petugas\Widgets;

use App\Services\PetugasStatsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class PetugasStatusOverviewWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.status-overview-widget';
    
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = '30s';
    
    protected int | string | array $columnSpan = 'full';
    
    protected PetugasStatsService $statsService;
    
    public function __construct()
    {
        $this->statsService = new PetugasStatsService();
    }
    
    public function getViewData(): array
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                Log::warning('PetugasStatusOverviewWidget: No authenticated user');
                return $this->getEmptyViewData('Tidak ada user yang terautentikasi');
            }
            
            return [
                'notifications' => $this->getNotifications(),
                'pending_tasks' => $this->getPendingTasks(),
                'today_schedule' => $this->getTodaySchedule(),
                'patient_queue' => $this->getPatientQueue(),
                'system_alerts' => $this->getSystemAlerts(),
                'last_updated' => now()->format('H:i:s'),
            ];
            
        } catch (Exception $e) {
            Log::error('PetugasStatusOverviewWidget: Failed to get view data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            return $this->getEmptyViewData('Terjadi kesalahan saat memuat data');
        }
    }
    
    protected function getNotifications(): array
    {
        // Demo notifications - replace with real data
        return [
            [
                'id' => 1,
                'type' => 'urgent',
                'title' => 'Pasien Emergency',
                'message' => '3 pasien emergency menunggu penanganan',
                'icon' => 'heroicon-o-exclamation-triangle',
                'time' => '5 menit lalu',
                'color' => 'red',
            ],
            [
                'id' => 2,
                'type' => 'info',
                'title' => 'Jadwal Konsultasi',
                'message' => 'Dr. Ahmad tersedia untuk konsultasi',
                'icon' => 'heroicon-o-calendar',
                'time' => '15 menit lalu',
                'color' => 'blue',
            ],
            [
                'id' => 3,
                'type' => 'success',
                'title' => 'Laporan Selesai',
                'message' => 'Laporan harian telah disetujui',
                'icon' => 'heroicon-o-check-circle',
                'time' => '1 jam lalu',
                'color' => 'green',
            ],
        ];
    }
    
    protected function getPendingTasks(): array
    {
        // Demo pending tasks - replace with real data
        return [
            [
                'id' => 1,
                'title' => 'Validasi Data Pasien',
                'description' => '12 data pasien perlu divalidasi',
                'priority' => 'high',
                'due_date' => 'Hari ini',
                'progress' => 65,
            ],
            [
                'id' => 2,
                'title' => 'Update Rekam Medis',
                'description' => '8 rekam medis belum diupdate',
                'priority' => 'medium',
                'due_date' => 'Besok',
                'progress' => 30,
            ],
            [
                'id' => 3,
                'title' => 'Persiapan Laporan Bulanan',
                'description' => 'Kompilasi data untuk laporan bulan ini',
                'priority' => 'low',
                'due_date' => 'Minggu depan',
                'progress' => 10,
            ],
        ];
    }
    
    protected function getTodaySchedule(): array
    {
        return [
            [
                'time' => '08:00',
                'activity' => 'Briefing Pagi',
                'type' => 'meeting',
                'status' => 'completed',
            ],
            [
                'time' => '09:30',
                'activity' => 'Pemeriksaan Rutin',
                'type' => 'patient_care',
                'status' => 'completed',
            ],
            [
                'time' => '11:00',
                'activity' => 'Konsultasi Tim',
                'type' => 'consultation',
                'status' => 'in_progress',
            ],
            [
                'time' => '14:00',
                'activity' => 'Update Data Sistem',
                'type' => 'admin',
                'status' => 'pending',
            ],
            [
                'time' => '16:00',
                'activity' => 'Review Laporan',
                'type' => 'review',
                'status' => 'pending',
            ],
        ];
    }
    
    protected function getPatientQueue(): array
    {
        return [
            [
                'number' => 'A001',
                'name' => 'Budi Santoso',
                'type' => 'Konsultasi Umum',
                'priority' => 'normal',
                'wait_time' => '15 menit',
                'status' => 'waiting',
            ],
            [
                'number' => 'A002',
                'name' => 'Siti Rahma',
                'type' => 'Follow Up',
                'priority' => 'normal',
                'wait_time' => '8 menit',
                'status' => 'waiting',
            ],
            [
                'number' => 'E001',
                'name' => 'Ahmad Yusuf',
                'type' => 'Emergency',
                'priority' => 'urgent',
                'wait_time' => '2 menit',
                'status' => 'urgent',
            ],
        ];
    }
    
    protected function getSystemAlerts(): array
    {
        return [
            [
                'type' => 'warning',
                'message' => 'Kapasitas server mencapai 85%',
                'action' => 'Monitor sistem',
                'time' => '10 menit lalu',
            ],
            [
                'type' => 'info',
                'message' => 'Update sistem dijadwalkan malam ini',
                'action' => 'Siapkan backup',
                'time' => '2 jam lalu',
            ],
        ];
    }
    
    protected function getEmptyViewData(string $error = ''): array
    {
        return [
            'notifications' => [],
            'pending_tasks' => [],
            'today_schedule' => [],
            'patient_queue' => [],
            'system_alerts' => [],
            'last_updated' => now()->format('H:i:s'),
            'error' => $error,
        ];
    }
}