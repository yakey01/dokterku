<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Base dashboard controller with common dashboard functionality
 */
class BaseDashboardController extends BaseApiController
{
    /**
     * Get basic attendance statistics for a user
     */
    protected function getAttendanceStats(int $userId, ?Carbon $month = null): array
    {
        $month = $month ?? Carbon::now();
        
        $cacheKey = "attendance_stats:{$userId}:{$month->format('Y-m')}";
        
        return Cache::remember($cacheKey, config('api.cache.dashboard_ttl', 300), function () use ($userId, $month) {
            $attendances = Attendance::where('user_id', $userId)
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->get();

            $totalDays = $attendances->count();
            $presentDays = $attendances->where('status', 'present')->count();
            $lateDays = $attendances->where('status', 'late')->count();
            $totalMinutes = $attendances->sum(function ($attendance) {
                return $attendance->time_out ? $attendance->time_in->diffInMinutes($attendance->time_out) : 0;
            });

            return [
                'this_month' => $totalDays,
                'present_days' => $presentDays,
                'late_days' => $lateDays,
                'absent_days' => max(0, $month->daysInMonth - $totalDays),
                'total_hours' => round($totalMinutes / 60, 2),
                'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get today's attendance for a user
     */
    protected function getTodayAttendance(int $userId): ?array
    {
        $cacheKey = "today_attendance:{$userId}:" . Carbon::today()->format('Y-m-d');
        
        return Cache::remember($cacheKey, 60, function () use ($userId) {
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', Carbon::today())
                ->first();

            if (!$attendance) {
                return null;
            }

            return [
                'id' => $attendance->id,
                'status' => $attendance->time_out ? 'checked_out' : 'checked_in',
                'time_in' => $attendance->time_in?->format('H:i'),
                'time_out' => $attendance->time_out?->format('H:i'),
                'work_duration' => $attendance->time_out ? [
                    'minutes' => $attendance->time_in->diffInMinutes($attendance->time_out),
                    'formatted' => $this->formatWorkDuration($attendance->time_in->diffInMinutes($attendance->time_out)),
                ] : null,
            ];
        });
    }

    /**
     * Get basic user statistics (for managers/admin)
     */
    protected function getUserStats(): array
    {
        return Cache::remember('user_stats', config('api.cache.dashboard_ttl', 300), function () {
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', true)->count();
            $todayPresent = Attendance::whereDate('date', Carbon::today())->distinct('user_id')->count();

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $totalUsers - $activeUsers,
                'present_today' => $todayPresent,
            ];
        });
    }

    /**
     * Get attendance statistics for all users (for managers/admin)
     */
    protected function getOverallAttendanceStats(?Carbon $month = null): array
    {
        $month = $month ?? Carbon::now();
        $cacheKey = "overall_attendance_stats:{$month->format('Y-m')}";
        
        return Cache::remember($cacheKey, config('api.cache.dashboard_ttl', 300), function () use ($month) {
            $attendances = Attendance::whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->get();

            $totalDays = $attendances->count();
            $presentDays = $attendances->where('status', 'present')->count();
            $lateDays = $attendances->where('status', 'late')->count();

            return [
                'total_attendance_records' => $totalDays,
                'present_records' => $presentDays,
                'late_records' => $lateDays,
                'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get mock Jaspel data (placeholder - implement actual logic)
     */
    protected function getJaspelStats(int $userId, ?Carbon $month = null): array
    {
        $month = $month ?? Carbon::now();
        
        // TODO: Implement actual Jaspel calculation logic
        // For now, return mock data
        return [
            'this_month' => rand(5000000, 15000000),
            'pending' => rand(1000000, 3000000),
            'approved' => rand(8000000, 12000000),
            'last_calculation' => $month->endOfMonth()->subDays(rand(1, 5))->toISOString(),
        ];
    }

    /**
     * Format work duration
     */
    protected function formatWorkDuration(int $minutes): string
    {
        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return $hours . ' jam ' . $remainingMinutes . ' menit';
        }

        return $remainingMinutes . ' menit';
    }

    /**
     * Get quick actions based on user role
     */
    protected function getQuickActions(string $role): array
    {
        $commonActions = [
            [
                'id' => 'checkin',
                'title' => 'Check In',
                'icon' => 'login',
                'type' => 'attendance',
                'endpoint' => '/api/v2/attendance/checkin',
            ],
            [
                'id' => 'checkout',
                'title' => 'Check Out',
                'icon' => 'logout',
                'type' => 'attendance',
                'endpoint' => '/api/v2/attendance/checkout',
            ],
        ];

        $roleSpecificActions = match($role) {
            'admin' => [
                [
                    'id' => 'user_management',
                    'title' => 'Kelola User',
                    'icon' => 'users',
                    'type' => 'navigation',
                    'endpoint' => '/api/v2/users',
                ],
                [
                    'id' => 'system_reports',
                    'title' => 'Laporan Sistem',
                    'icon' => 'chart',
                    'type' => 'navigation',
                    'endpoint' => '/api/v2/reports',
                ],
            ],
            'manajer' => [
                [
                    'id' => 'team_overview',
                    'title' => 'Tim Overview',
                    'icon' => 'team',
                    'type' => 'navigation',
                    'endpoint' => '/api/v2/dashboards/team',
                ],
                [
                    'id' => 'approve_jaspel',
                    'title' => 'Approval Jaspel',
                    'icon' => 'check',
                    'type' => 'navigation',
                    'endpoint' => '/api/v2/jaspel/approvals',
                ],
            ],
            'bendahara' => [
                [
                    'id' => 'financial_reports',
                    'title' => 'Laporan Keuangan',
                    'icon' => 'money',
                    'type' => 'navigation',
                    'endpoint' => '/api/v2/reports/financial',
                ],
                [
                    'id' => 'jaspel_payments',
                    'title' => 'Pembayaran Jaspel',
                    'icon' => 'payment',
                    'type' => 'navigation',
                    'endpoint' => '/api/v2/jaspel/payments',
                ],
            ],
            'dokter' => [
                [
                    'id' => 'patient_schedule',
                    'title' => 'Jadwal Pasien',
                    'icon' => 'calendar',
                    'type' => 'navigation',
                    'endpoint' => '/api/v2/schedules/patients',
                ],
                [
                    'id' => 'jaspel_tracking',
                    'title' => 'Tracking Jaspel',
                    'icon' => 'tracking',
                    'type' => 'navigation',
                    'endpoint' => '/api/v2/jaspel/tracking',
                ],
            ],
            default => [],
        };

        return array_merge($commonActions, $roleSpecificActions);
    }
}