<?php

namespace App\Filament\Petugas\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PetugasHeaderWidget extends Widget
{
    protected static string $view = 'filament.petugas.widgets.header-widget';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    public function getViewData(): array
    {
        $user = Auth::user();
        
        return [
            'user' => $user,
            'greeting' => $this->getGreeting(),
            'currentTime' => now()->format('H:i'),
            'currentDate' => now()->format('l, d F Y'),
            'userRole' => $user->roles->pluck('name')->first() ?? 'Petugas',
            'totalPatientsToday' => $this->getTodayPatientCount(),
            'systemStatus' => $this->getSystemStatus(),
            'lastLoginTime' => $user->last_login_at ? $user->last_login_at->format('H:i, d M Y') : 'Pertama kali',
        ];
    }
    
    protected function getTodayPatientCount(): int
    {
        try {
            // Get today's patient count from JumlahPasienHarian
            return \App\Models\JumlahPasienHarian::whereDate('tanggal', today())->sum('jumlah_pasien') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    protected function getSystemStatus(): array
    {
        return [
            'database' => 'Connected',
            'cache' => 'Active',
            'queue' => 'Running',
            'storage' => 'Available'
        ];
    }
    
    protected function getGreeting(): string
    {
        $hour = now()->hour;
        
        if ($hour < 12) {
            return '🌅 Selamat Pagi';
        } elseif ($hour < 17) {
            return '🌞 Selamat Siang';
        } elseif ($hour < 20) {
            return '🌆 Selamat Sore';
        } else {
            return '🌙 Selamat Malam';
        }
    }
}