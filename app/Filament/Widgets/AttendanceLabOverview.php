<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\FaceRecognition;
use App\Models\AbsenceRequest;
use App\Models\UserDevice;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class AttendanceLabOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Today's Statistics
        $todayAttendance = Attendance::whereDate('date', $today)->count();
        $todayCheckouts = Attendance::whereDate('date', $today)->whereNotNull('time_out')->count();
        $todayLate = Attendance::whereDate('date', $today)->where('status', 'late')->count();
        
        // Face Recognition Stats
        $totalFaceRecognitions = FaceRecognition::count();
        $verifiedFaces = FaceRecognition::where('is_verified', true)->count();
        $activeFaces = FaceRecognition::where('is_active', true)->count();
        
        // Absence Requests  
        $pendingAbsences = AbsenceRequest::where('status', 'pending')->count();
        $thisMonthAbsences = AbsenceRequest::whereDate('absence_date', '>=', $thisMonth)->count();
        
        // Device Management
        $totalDevices = UserDevice::count();
        $verifiedDevices = UserDevice::whereNotNull('verified_at')->count();
        $activeDevices = UserDevice::where('is_active', true)->count();
        
        // Weekly Attendance Rate
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $totalUsers = User::count();
        $weeklyAttendance = Attendance::whereBetween('date', [$weekStart, $weekEnd])->distinct('user_id')->count();
        $attendanceRate = $totalUsers > 0 ? round(($weeklyAttendance / $totalUsers) * 100, 1) : 0;
        
        return [
            Stat::make('Hari Ini - Presensi', $todayAttendance)
                ->description($todayCheckouts . ' sudah check-out')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
                
            Stat::make('Terlambat Hari Ini', $todayLate)
                ->description('Dari ' . $todayAttendance . ' total presensi')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($todayLate > 0 ? 'warning' : 'success')
                ->chart([3, 1, 4, 2, 5, 1, 2]),
                
            Stat::make('Face Recognition', $totalFaceRecognitions)
                ->description($verifiedFaces . ' terverifikasi, ' . $activeFaces . ' aktif')
                ->descriptionIcon('heroicon-m-face-smile')
                ->color('info')
                ->chart([2, 5, 3, 8, 12, 7, 15]),
                
            Stat::make('Absence Requests', $pendingAbsences)
                ->description($thisMonthAbsences . ' permohonan bulan ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($pendingAbsences > 0 ? 'warning' : 'success')
                ->chart([1, 3, 2, 4, 1, 2, 0]),
                
            Stat::make('Device Management', $totalDevices)
                ->description($verifiedDevices . ' verified, ' . $activeDevices . ' active')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('gray')
                ->chart([5, 3, 8, 6, 12, 10, 15]),
                
            Stat::make('Weekly Attendance Rate', $attendanceRate . '%')
                ->description('Tingkat kehadiran mingguan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($attendanceRate >= 80 ? 'success' : ($attendanceRate >= 60 ? 'warning' : 'danger'))
                ->chart([60, 65, 70, 75, 80, 85, $attendanceRate]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
    
    public function getDisplayName(): string
    {
        return "Attendance Lab Overview";
    }
}