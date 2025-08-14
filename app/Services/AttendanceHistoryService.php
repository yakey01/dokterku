<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendanceHistoryService
{
    /**
     * Get optimized attendance history for user with pagination
     */
    public function getUserAttendanceHistory(
        int $userId,
        array $filters = [],
        int $perPage = 25,
        int $page = 1
    ): LengthAwarePaginator {
        $query = $this->buildOptimizedQuery($userId, $filters);
        
        $result = $query->with(['jadwalJaga.shiftTemplate'])->paginate($perPage, [
            'id', 'user_id', 'date', 'time_in', 'time_out', 
            'status', 'latitude', 'longitude', 'location_name_in',
            'location_name_out', 'notes', 'created_at', 'jadwal_jaga_id'
        ], 'page', $page);

        // Transform data to include kekurangan menit dan jadwal jaga
        $result->getCollection()->transform(function ($attendance) {
            // Get shift schedule information with enhanced jam jaga data
            $shiftInfo = null;
            if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
                $jadwalJaga = $attendance->jadwalJaga;
                $shift = $jadwalJaga->shiftTemplate;
                
                // Enhanced shift info with jam jaga details
                $shiftInfo = [
                    'shift_name' => $shift->nama_shift ?? 'Shift Umum',
                    'shift_start' => $shift->jam_masuk ? \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') : null,
                    'shift_end' => $shift->jam_pulang ? \Carbon\Carbon::parse($shift->jam_pulang)->format('H:i') : null,
                    'shift_duration' => $shift->jam_masuk && $shift->jam_pulang ? 
                        $this->calculateShiftDuration($shift->jam_masuk, $shift->jam_pulang) : null,
                    
                    // ✅ ADDED: Enhanced jam jaga information
                    'jam_jaga' => $jadwalJaga->jam_shift ?? null, // Formatted time range
                    'jam_masuk_effective' => $jadwalJaga->effective_start_time ?? null, // Custom or default start
                    'jam_pulang_effective' => $jadwalJaga->effective_end_time ?? null, // Custom or default end
                    'unit_kerja' => $jadwalJaga->unit_kerja ?? null, // Unit kerja (Dokter Jaga, Pendaftaran, etc)
                    'peran' => $jadwalJaga->peran ?? null, // Peran (Dokter, Paramedis, NonParamedis)
                    'status_jaga' => $jadwalJaga->status_jaga ?? null, // Status jaga (Aktif, Cuti, Izin, OnCall)
                    'is_custom_schedule' => !empty($jadwalJaga->jam_jaga_custom), // Flag for custom schedule
                    'custom_reason' => $jadwalJaga->keterangan ?? null, // Custom schedule reason
                ];
            } else {
                // ✅ ADDED: Fallback jam jaga data when no jadwal jaga
                $fallbackJadwal = \App\Models\JadwalJaga::where('pegawai_id', $attendance->user_id)
                    ->whereDate('tanggal_jaga', $attendance->date)
                    ->with('shiftTemplate')
                    ->first();
                
                if ($fallbackJadwal && $fallbackJadwal->shiftTemplate) {
                    $shift = $fallbackJadwal->shiftTemplate;
                    $shiftInfo = [
                        'shift_name' => $shift->nama_shift ?? 'Shift Umum',
                        'shift_start' => $shift->jam_masuk ? \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') : '08:00',
                        'shift_end' => $shift->jam_pulang ? \Carbon\Carbon::parse($shift->jam_pulang)->format('H:i') : '16:00',
                        'shift_duration' => $shift->jam_masuk && $shift->jam_pulang ? 
                            $this->calculateShiftDuration($shift->jam_masuk, $shift->jam_pulang) : '8j 0m',
                        
                        // ✅ ADDED: Fallback jam jaga information
                        'jam_jaga' => $fallbackJadwal->jam_shift ?? '08:00 - 16:00',
                        'jam_masuk_effective' => $fallbackJadwal->effective_start_time ?? '08:00',
                        'jam_pulang_effective' => $fallbackJadwal->effective_end_time ?? '16:00',
                        'unit_kerja' => $fallbackJadwal->unit_kerja ?? 'Dokter Jaga',
                        'peran' => $fallbackJadwal->peran ?? 'Dokter',
                        'status_jaga' => $fallbackJadwal->status_jaga ?? 'Aktif',
                        'is_custom_schedule' => !empty($fallbackJadwal->jam_jaga_custom),
                        'custom_reason' => $fallbackJadwal->keterangan ?? 'Jadwal default',
                    ];
                } else {
                    // ✅ ADDED: Default jam jaga data when no jadwal found
                    $shiftInfo = [
                        'shift_name' => 'Shift Default',
                        'shift_start' => '08:00',
                        'shift_end' => '16:00',
                        'shift_duration' => '8j 0m',
                        
                        // ✅ ADDED: Default jam jaga information
                        'jam_jaga' => '08:00 - 16:00',
                        'jam_masuk_effective' => '08:00',
                        'jam_pulang_effective' => '16:00',
                        'unit_kerja' => 'Dokter Jaga',
                        'peran' => 'Dokter',
                        'status_jaga' => 'Aktif',
                        'is_custom_schedule' => false,
                        'custom_reason' => 'Jadwal standar 8 jam',
                    ];
                }
            }

            return [
                'id' => $attendance->id,
                'date' => $attendance->date,
                'time_in' => $attendance->time_in ? $attendance->time_in->format('H:i') : null,
                'time_out' => $attendance->time_out ? $attendance->time_out->format('H:i') : null,
                'status' => $attendance->status,
                'duration_formatted' => $attendance->formatted_work_duration,
                'duration_minutes' => $attendance->work_duration,
                'target_minutes' => $attendance->target_work_duration,
                'shortfall_minutes' => $attendance->shortfall_minutes,
                'shortfall_formatted' => $attendance->formatted_shortfall,
                'location_name_in' => $attendance->location_name_in,
                'location_name_out' => $attendance->location_name_out,
                'notes' => $attendance->notes,
                'shift_info' => $shiftInfo,
                // Enhanced duration data
                'effective_start_time' => $attendance->effective_start_time?->format('H:i'),
                'effective_end_time' => $attendance->effective_end_time?->format('H:i'),
                'break_deduction_minutes' => $attendance->break_time_deduction,
                'attendance_percentage' => $attendance->attendance_percentage,
                'work_duration_breakdown' => $attendance->work_duration_breakdown,
            ];
        });

        return $result;
    }

    /**
     * Get attendance summary statistics for user
     */
    public function getUserAttendanceSummary(int $userId, string $period = 'this_month'): array
    {
        $query = $this->getBasePeriodQuery($userId, $period);
        
        $summary = $query->selectRaw('
            COUNT(*) as total_days,
            SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NOT NULL THEN 1 ELSE 0 END) as complete_days,
            SUM(CASE WHEN time_in IS NOT NULL AND time_out IS NULL THEN 1 ELSE 0 END) as incomplete_days,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = "sick" THEN 1 ELSE 0 END) as sick_days,
            SUM(CASE WHEN status = "permission" THEN 1 ELSE 0 END) as permission_days
        ')->first();

        // Calculate total working hours for completed days
        $totalMinutes = $query->whereNotNull('time_in')
            ->whereNotNull('time_out')
            ->get(['time_in', 'time_out'])
            ->sum(function ($attendance) {
                $timeIn = Carbon::parse($attendance->time_in);
                $timeOut = Carbon::parse($attendance->time_out);
                return $timeOut->diffInMinutes($timeIn);
            });

        $totalHours = (int) floor($totalMinutes / 60);
        $remainingMinutes = (int) ($totalMinutes % 60);

        return [
            'total_days' => $summary->total_days ?? 0,
            'complete_days' => $summary->complete_days ?? 0,
            'incomplete_days' => $summary->incomplete_days ?? 0,
            'present_days' => $summary->present_days ?? 0,
            'late_days' => $summary->late_days ?? 0,
            'absent_days' => $summary->absent_days ?? 0,
            'sick_days' => $summary->sick_days ?? 0,
            'permission_days' => $summary->permission_days ?? 0,
            'total_working_hours' => $totalHours,
            'total_working_minutes' => $remainingMinutes,
            'total_working_time_formatted' => sprintf('%d jam %d menit', $totalHours, $remainingMinutes),
            'attendance_rate' => $summary->total_days > 0 ? 
                round(($summary->present_days + $summary->late_days) / $summary->total_days * 100, 1) : 0,
        ];
    }

    /**
     * Get attendance data for calendar view
     */
    public function getUserAttendanceCalendar(int $userId, Carbon $month): Collection
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        return Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                'date', 'time_in', 'time_out', 'status',
                'latitude', 'longitude', 'location_name_in'
            ])
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($attendance) {
                return [
                    $attendance->date->format('Y-m-d') => [
                        'date' => $attendance->date,
                        'time_in' => $attendance->time_in,
                        'time_out' => $attendance->time_out,
                        'status' => $attendance->status,
                        'has_location' => !empty($attendance->location_name_in) || 
                                       (!empty($attendance->latitude) && !empty($attendance->longitude)),
                        'is_complete' => !empty($attendance->time_in) && !empty($attendance->time_out),
                    ]
                ];
            });
    }

    /**
     * Build optimized query with filters
     */
    protected function buildOptimizedQuery(int $userId, array $filters = []): Builder
    {
        $query = Attendance::where('user_id', $userId)
            ->with(['user:id,name'])
            ->orderBy('date', 'desc');

        // Apply filters
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        if (!empty($filters['period'])) {
            $query = $this->applyPeriodFilter($query, $filters['period']);
        }

        if (!empty($filters['incomplete_only'])) {
            $query->whereNotNull('time_in')->whereNull('time_out');
        }

        return $query;
    }

    /**
     * Apply period-based filters
     */
    protected function applyPeriodFilter(Builder $query, string $period): Builder
    {
        return match ($period) {
            'today' => $query->where('date', Carbon::today()),
            'yesterday' => $query->where('date', Carbon::yesterday()),
            'this_week' => $query->whereBetween('date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ]),
            'last_week' => $query->whereBetween('date', [
                Carbon::now()->subWeek()->startOfWeek(),
                Carbon::now()->subWeek()->endOfWeek()
            ]),
            'this_month' => $query->whereYear('date', Carbon::now()->year)
                ->whereMonth('date', Carbon::now()->month),
            'last_month' => $query->whereYear('date', Carbon::now()->subMonth()->year)
                ->whereMonth('date', Carbon::now()->subMonth()->month),
            'this_year' => $query->whereYear('date', Carbon::now()->year),
            'last_90_days' => $query->where('date', '>=', Carbon::now()->subDays(90)),
            default => $query,
        };
    }

    /**
     * Get base query for period
     */
    protected function getBasePeriodQuery(int $userId, string $period): Builder
    {
        $query = Attendance::where('user_id', $userId);
        return $this->applyPeriodFilter($query, $period);
    }

    /**
     * Get incomplete check-outs for user
     */
    public function getIncompleteCheckouts(int $userId): Collection
    {
        return Attendance::where('user_id', $userId)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->orderBy('date', 'desc')
            ->get(['id', 'date', 'time_in', 'location_name_in']);
    }

    /**
     * Calculate shift duration in formatted string
     */
    protected function calculateShiftDuration(string $startTime, string $endTime): string
    {
        try {
            $start = \Carbon\Carbon::parse($startTime);
            $end = \Carbon\Carbon::parse($endTime);
            
            // Handle overnight shifts
            if ($end->lt($start)) {
                $end->addDay();
            }
            
            $totalMinutes = $end->diffInMinutes($start);
            $hours = intval($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            
            return sprintf('%dj %dm', $hours, $minutes);
        } catch (\Exception $e) {
            return '8j 0m'; // Default fallback
        }
    }

    /**
     * Get attendance streaks (consecutive working days)
     */
    public function getAttendanceStreaks(int $userId, int $limit = 5): array
    {
        $attendances = Attendance::where('user_id', $userId)
            ->whereIn('status', ['present', 'late'])
            ->orderBy('date', 'desc')
            ->take(30) // Look at last 30 records for performance
            ->get(['date', 'status']);

        $streaks = [];
        $currentStreak = 0;
        $maxStreak = 0;
        $previousDate = null;

        foreach ($attendances as $attendance) {
            if ($previousDate === null || $attendance->date->diffInDays($previousDate) === 1) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                if ($currentStreak > 0) {
                    $streaks[] = $currentStreak;
                }
                $currentStreak = 1;
            }
            $previousDate = $attendance->date;
        }

        if ($currentStreak > 0) {
            $streaks[] = $currentStreak;
        }

        return [
            'current_streak' => $attendances->isNotEmpty() && 
                $attendances->first()->date->isToday() ? $currentStreak : 0,
            'max_streak' => $maxStreak,
            'recent_streaks' => array_slice(array_reverse($streaks), 0, $limit),
        ];
    }
}