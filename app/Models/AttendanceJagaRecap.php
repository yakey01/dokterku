<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceJagaRecap extends Model
{
    protected $table = 'attendance_jaga_recaps'; // Will use view or computed data

    public $timestamps = false; // This is a computed model
    
    // Ensure Filament can work with computed models
    protected static $unguarded = true;
    
    protected $primaryKey = 'user_id'; // Use user_id as primary key for actions
    
    public $incrementing = false; // Non-incrementing key
    
    protected $keyType = 'int'; // Key type for Filament

    protected $fillable = [
        'user_id',
        'staff_name',
        'profession',
        'position',
        'total_scheduled_days',
        'days_present',
        'total_scheduled_hours',
        'total_working_hours',
        'total_shortfall_minutes',
        'average_check_in',
        'average_check_out',
        'attendance_percentage',
        'gps_validation_rate',
        'schedule_compliance_rate',
        'status',
        'month',
        'year',
    ];

    protected $casts = [
        'total_scheduled_days' => 'integer',
        'days_present' => 'integer',
        'total_scheduled_hours' => 'decimal:2',
        'total_working_hours' => 'decimal:2',
        'total_shortfall_minutes' => 'integer',
        'attendance_percentage' => 'decimal:1',
        'gps_validation_rate' => 'decimal:1',
        'schedule_compliance_rate' => 'decimal:1',
        'month' => 'integer',
        'year' => 'integer',
    ];

    /**
     * Override getKey() to ensure Filament actions work properly
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }
    
    /**
     * Override getRouteKey() for proper routing
     */
    public function getRouteKey()
    {
        return $this->getAttribute($this->getKeyName());
    }
    
    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get profession-specific attendance data for admin panel
     */
    public static function getJagaRecapData(?string $profession = null, ?int $month = null, ?int $year = null): Collection
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        // Build base query with relationships
        $query = DB::table('users as u')
            ->leftJoin('roles as r', 'u.role_id', '=', 'r.id')
            ->leftJoin('jadwal_jagas as jj', function ($join) use ($month, $year) {
                $join->on('u.id', '=', 'jj.pegawai_id')
                    ->whereMonth('jj.tanggal_jaga', $month)
                    ->whereYear('jj.tanggal_jaga', $year);
            })
            ->leftJoin('shift_templates as st', 'jj.shift_template_id', '=', 'st.id')
            ->leftJoin('attendances as a', function ($join) use ($month, $year) {
                $join->on('u.id', '=', 'a.user_id')
                    ->whereMonth('a.date', $month)
                    ->whereYear('a.date', $year);
            })
            ->where('u.is_active', true)
            ->whereNotNull('r.name');

        // Filter by profession if specified
        if ($profession && $profession !== 'all') {
            $query->where('jj.peran', $profession);
        }

        // Select and group by user
        $results = $query->select([
            'u.id as user_id',
            'u.name as staff_name',
            'r.display_name as position',
            'jj.peran as profession',
            DB::raw('COUNT(DISTINCT jj.id) as total_scheduled_days'),
            DB::raw('COUNT(DISTINCT a.id) as days_present'),
            DB::raw('AVG(CASE WHEN a.time_in IS NOT NULL THEN TIME_TO_SEC(a.time_in) END) as avg_check_in_seconds'),
            DB::raw('AVG(CASE WHEN a.time_out IS NOT NULL THEN TIME_TO_SEC(a.time_out) END) as avg_check_out_seconds'),
            DB::raw('SUM(CASE WHEN a.work_duration IS NOT NULL THEN a.work_duration ELSE 0 END) as total_work_minutes'),
            DB::raw('COUNT(CASE WHEN a.is_location_valid_in = 1 THEN 1 END) as valid_gps_checkins'),
            DB::raw('COUNT(CASE WHEN a.time_in IS NOT NULL THEN 1 END) as total_checkins'),
        ])
            ->groupBy('u.id', 'u.name', 'r.display_name', 'jj.peran')
            ->having('total_scheduled_days', '>', 0)
            ->get();

        // Transform results into AttendanceJagaRecap objects
        return $results->map(function ($item) use ($month, $year) {
            return new static([
                'user_id' => $item->user_id,
                'staff_name' => $item->staff_name,
                'profession' => $item->profession ?? 'Unknown',
                'position' => $item->position,
                'total_scheduled_days' => $item->total_scheduled_days,
                'days_present' => $item->days_present,
                'total_working_hours' => round($item->total_work_minutes / 60, 2),
                'average_check_in' => $item->avg_check_in_seconds ?
                    gmdate('H:i', $item->avg_check_in_seconds) : null,
                'average_check_out' => $item->avg_check_out_seconds ?
                    gmdate('H:i', $item->avg_check_out_seconds) : null,
                'attendance_percentage' => $item->total_scheduled_days > 0 ?
                    round(($item->days_present / $item->total_scheduled_days) * 100, 1) : 0,
                'gps_validation_rate' => $item->total_checkins > 0 ?
                    round(($item->valid_gps_checkins / $item->total_checkins) * 100, 1) : 0,
                'month' => $month,
                'year' => $year,
            ]);
        })->map(function ($recap) {
            // Calculate additional fields
            $recap->total_shortfall_minutes = static::calculateShortfallMinutes(
                $recap->user_id,
                $recap->month,
                $recap->year
            );

            $recap->schedule_compliance_rate = static::calculateScheduleCompliance(
                $recap->user_id,
                $recap->month,
                $recap->year
            );

            $recap->status = static::determineAttendanceStatus($recap->attendance_percentage);

            return $recap;
        })->sortByDesc('attendance_percentage')->values();
    }

    /**
     * Calculate shortfall minutes for a user
     */
    private static function calculateShortfallMinutes(int $userId, int $month, int $year): int
    {
        $attendances = DB::table('attendances as a')
            ->join('jadwal_jagas as jj', 'a.jadwal_jaga_id', '=', 'jj.id')
            ->join('shift_templates as st', 'jj.shift_template_id', '=', 'st.id')
            ->where('a.user_id', $userId)
            ->whereMonth('a.date', $month)
            ->whereYear('a.date', $year)
            ->whereNotNull('a.time_in')
            ->whereNotNull('a.time_out')
            ->select([
                'a.work_duration',
                DB::raw('(TIME_TO_SEC(st.jam_pulang) - TIME_TO_SEC(st.jam_masuk))/60 as target_minutes'),
            ])
            ->get();

        $totalShortfall = 0;
        foreach ($attendances as $attendance) {
            $actualMinutes = $attendance->work_duration ?? 0;
            $targetMinutes = $attendance->target_minutes ?? 0;

            if ($targetMinutes > $actualMinutes) {
                $totalShortfall += ($targetMinutes - $actualMinutes);
            }
        }

        return round($totalShortfall);
    }

    /**
     * Calculate schedule compliance rate
     */
    private static function calculateScheduleCompliance(int $userId, int $month, int $year): float
    {
        $scheduleData = DB::table('jadwal_jagas as jj')
            ->leftJoin('attendances as a', function ($join) {
                $join->on('jj.pegawai_id', '=', 'a.user_id')
                    ->on('jj.tanggal_jaga', '=', 'a.date');
            })
            ->join('shift_templates as st', 'jj.shift_template_id', '=', 'st.id')
            ->where('jj.pegawai_id', $userId)
            ->whereMonth('jj.tanggal_jaga', $month)
            ->whereYear('jj.tanggal_jaga', $year)
            ->select([
                'jj.tanggal_jaga',
                'st.jam_masuk',
                'st.jam_pulang',
                'a.time_in',
                'a.time_out',
            ])
            ->get();

        if ($scheduleData->isEmpty()) {
            return 0.0;
        }

        $compliantShifts = 0;
        $totalShifts = $scheduleData->count();

        foreach ($scheduleData as $shift) {
            if (! $shift->time_in || ! $shift->time_out) {
                continue; // No attendance = not compliant
            }

            $scheduledStart = Carbon::parse($shift->jam_masuk);
            $scheduledEnd = Carbon::parse($shift->jam_pulang);
            $actualStart = Carbon::parse($shift->time_in);
            $actualEnd = Carbon::parse($shift->time_out);

            // Allow 15-minute grace period
            $startCompliant = $actualStart->diffInMinutes($scheduledStart, false) <= 15;
            $endCompliant = $actualEnd->diffInMinutes($scheduledEnd, false) >= -15;

            if ($startCompliant && $endCompliant) {
                $compliantShifts++;
            }
        }

        return $totalShifts > 0 ? round(($compliantShifts / $totalShifts) * 100, 1) : 0.0;
    }

    /**
     * Determine attendance status based on percentage
     */
    private static function determineAttendanceStatus(float $percentage): string
    {
        return match (true) {
            $percentage >= 95 => 'excellent',
            $percentage >= 85 => 'good',
            $percentage >= 75 => 'average',
            default => 'poor'
        };
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'excellent' => 'Excellent (≥95%)',
            'good' => 'Good (85-94%)',
            'average' => 'Average (75-84%)',
            'poor' => 'Poor (<75%)',
            default => 'Unknown'
        };
    }

    /**
     * Get profession-specific statistics
     */
    public static function getProfessionStats(?int $month = null, ?int $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $stats = [];
        $professions = ['Dokter', 'Paramedis', 'NonParamedis'];

        foreach ($professions as $profession) {
            $data = static::getJagaRecapData($profession, $month, $year);

            $stats[$profession] = [
                'total_staff' => $data->count(),
                'excellent_count' => $data->where('status', 'excellent')->count(),
                'good_count' => $data->where('status', 'good')->count(),
                'average_count' => $data->where('status', 'average')->count(),
                'poor_count' => $data->where('status', 'poor')->count(),
                'avg_attendance' => $data->avg('attendance_percentage') ?? 0,
                'avg_compliance' => $data->avg('schedule_compliance_rate') ?? 0,
            ];
        }

        return $stats;
    }

    /**
     * Get formatted shortfall time
     */
    public function getFormattedShortfallAttribute(): string
    {
        if ($this->total_shortfall_minutes <= 0) {
            return 'Target tercapai';
        }

        $hours = intval($this->total_shortfall_minutes / 60);
        $minutes = $this->total_shortfall_minutes % 60;

        return sprintf('Kurang %dj %dm', $hours, $minutes);
    }

    /**
     * Get profession color for UI
     */
    public function getProfessionColorAttribute(): string
    {
        return match ($this->profession) {
            'Dokter' => 'success',
            'Paramedis' => 'info',
            'NonParamedis' => 'warning',
            default => 'gray'
        };
    }

    /**
     * Get comprehensive detailed data for a specific user
     */
    public static function getUserDetailedData(int $userId, int $month, int $year): array
    {
        $user = \App\Models\User::with(['role'])->find($userId);
        if (! $user) {
            return static::getEmptyDetailedData();
        }

        // Get user's scheduled shifts for the period
        $scheduledShifts = static::getUserScheduledShifts($userId, $month, $year);

        // Get user's actual attendances
        $attendances = static::getUserAttendances($userId, $month, $year);

        // Get daily breakdown
        $dailyBreakdown = static::getDailyAttendanceBreakdown($userId, $month, $year);

        // Get monthly trends (last 6 months)
        $monthlyTrends = static::getMonthlyTrends($userId, $month, $year);

        // Get performance insights
        $performanceInsights = static::getPerformanceInsights($userId, $scheduledShifts, $attendances);

        // Get professional standards compliance
        $professionalCompliance = static::getProfessionalStandardsCompliance($user, $attendances, $scheduledShifts);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->no_telepon ?? $user->phone,
                'position' => $user->role?->display_name ?? 'Unknown',
                'profession' => static::getUserProfessionFromSchedule($userId) ?? 'Unknown',
                'join_date' => $user->tanggal_bergabung?->format('d M Y'),
                'is_active' => $user->is_active,
            ],
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
                'total_days' => Carbon::createFromDate($year, $month, 1)->daysInMonth,
                'working_days' => $scheduledShifts->count(),
            ],
            'summary' => [
                'total_scheduled_shifts' => $scheduledShifts->count(),
                'attended_shifts' => $attendances->count(),
                'missed_shifts' => $scheduledShifts->count() - $attendances->count(),
                'attendance_percentage' => $scheduledShifts->count() > 0 ?
                    round(($attendances->count() / $scheduledShifts->count()) * 100, 1) : 0,
                'total_scheduled_hours' => static::calculateTotalScheduledHours($scheduledShifts),
                'total_working_hours' => static::calculateTotalWorkingHours($attendances),
                'total_shortfall_minutes' => static::calculateUserShortfall($scheduledShifts, $attendances),
                'overtime_hours' => static::calculateOvertimeHours($attendances),
                'average_check_in' => static::calculateAverageTime($attendances, 'time_in'),
                'average_check_out' => static::calculateAverageTime($attendances, 'time_out'),
            ],
            'compliance' => [
                'schedule_compliance_rate' => static::calculateUserScheduleCompliance($scheduledShifts, $attendances),
                'punctuality_score' => static::calculatePunctualityScore($scheduledShifts, $attendances),
                'gps_validation_rate' => static::calculateGpsValidationRate($attendances),
                'early_arrivals' => static::countEarlyArrivals($scheduledShifts, $attendances),
                'late_arrivals' => static::countLateArrivals($scheduledShifts, $attendances),
                'early_departures' => static::countEarlyDepartures($scheduledShifts, $attendances),
            ],
            'daily_breakdown' => $dailyBreakdown,
            'monthly_trends' => $monthlyTrends,
            'performance_insights' => $performanceInsights,
            'professional_compliance' => $professionalCompliance,
            'recommendations' => static::generateRecommendations($performanceInsights, $professionalCompliance),
        ];
    }

    /**
     * Get user's scheduled shifts for the period
     */
    private static function getUserScheduledShifts(int $userId, int $month, int $year): Collection
    {
        return DB::table('jadwal_jagas as jj')
            ->join('shift_templates as st', 'jj.shift_template_id', '=', 'st.id')
            ->join('users as u', 'jj.pegawai_id', '=', 'u.id')
            ->where('jj.pegawai_id', $userId)
            ->whereMonth('jj.tanggal_jaga', $month)
            ->whereYear('jj.tanggal_jaga', $year)
            ->select([
                'jj.*',
                'st.nama_shift',
                'st.jam_masuk',
                'st.jam_pulang',
                'st.break_duration_minutes',
                'u.name as staff_name',
            ])
            ->orderBy('jj.tanggal_jaga')
            ->get();
    }

    /**
     * Get user's actual attendances for the period
     */
    private static function getUserAttendances(int $userId, int $month, int $year): Collection
    {
        return DB::table('attendances as a')
            ->leftJoin('jadwal_jagas as jj', function ($join) {
                $join->on('a.user_id', '=', 'jj.pegawai_id')
                    ->on('a.date', '=', 'jj.tanggal_jaga');
            })
            ->leftJoin('shift_templates as st', 'jj.shift_template_id', '=', 'st.id')
            ->where('a.user_id', $userId)
            ->whereMonth('a.date', $month)
            ->whereYear('a.date', $year)
            ->select([
                'a.*',
                'jj.tanggal_jaga',
                'st.nama_shift',
                'st.jam_masuk as scheduled_start',
                'st.jam_pulang as scheduled_end',
            ])
            ->orderBy('a.date')
            ->get();
    }

    /**
     * Get daily attendance breakdown
     */
    private static function getDailyAttendanceBreakdown(int $userId, int $month, int $year): array
    {
        $scheduledShifts = static::getUserScheduledShifts($userId, $month, $year);
        $attendances = static::getUserAttendances($userId, $month, $year);

        $attendancesByDate = $attendances->keyBy('date');
        $breakdown = [];

        foreach ($scheduledShifts as $shift) {
            $date = Carbon::parse($shift->tanggal_jaga);
            $attendance = $attendancesByDate->get($date->format('Y-m-d'));

            $dayData = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'formatted_date' => $date->format('d M Y'),
                'shift_name' => $shift->nama_shift,
                'scheduled_start' => $shift->jam_masuk,
                'scheduled_end' => $shift->jam_pulang,
                'status' => $attendance ? 'present' : 'absent',
            ];

            if ($attendance) {
                $actualStart = Carbon::parse($attendance->time_in);
                $actualEnd = $attendance->time_out ? Carbon::parse($attendance->time_out) : null;
                $scheduledStart = Carbon::parse($shift->jam_masuk);

                $dayData = array_merge($dayData, [
                    'actual_check_in' => $actualStart->format('H:i'),
                    'actual_check_out' => $actualEnd?->format('H:i'),
                    'work_duration' => $attendance->work_duration ?? 0,
                    'is_late' => $actualStart->greaterThan($scheduledStart->addMinutes(15)),
                    'late_minutes' => max(0, $actualStart->diffInMinutes($scheduledStart)),
                    'gps_valid' => $attendance->is_location_valid_in ?? false,
                    'location_name' => $attendance->location_name_in,
                ]);
            }

            $breakdown[] = $dayData;
        }

        return $breakdown;
    }

    /**
     * Get monthly trends for the last 6 months
     */
    private static function getMonthlyTrends(int $userId, int $currentMonth, int $currentYear): array
    {
        $trends = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::createFromDate($currentYear, $currentMonth, 1)->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $scheduledShifts = static::getUserScheduledShifts($userId, $month, $year);
            $attendances = static::getUserAttendances($userId, $month, $year);

            $trends[] = [
                'month' => $month,
                'year' => $year,
                'month_name' => $date->format('M Y'),
                'scheduled_shifts' => $scheduledShifts->count(),
                'attended_shifts' => $attendances->count(),
                'attendance_percentage' => $scheduledShifts->count() > 0 ?
                    round(($attendances->count() / $scheduledShifts->count()) * 100, 1) : 0,
                'total_hours' => static::calculateTotalWorkingHours($attendances),
            ];
        }

        return $trends;
    }

    /**
     * Get performance insights
     */
    private static function getPerformanceInsights(int $userId, Collection $scheduledShifts, Collection $attendances): array
    {
        $insights = [];

        // Attendance pattern analysis
        $dayOfWeekStats = [];
        foreach ($attendances as $attendance) {
            $dayOfWeek = Carbon::parse($attendance->date)->dayOfWeek;
            $dayName = Carbon::parse($attendance->date)->format('l');

            if (! isset($dayOfWeekStats[$dayName])) {
                $dayOfWeekStats[$dayName] = ['attended' => 0, 'scheduled' => 0];
            }
            $dayOfWeekStats[$dayName]['attended']++;
        }

        foreach ($scheduledShifts as $shift) {
            $dayName = Carbon::parse($shift->tanggal_jaga)->format('l');
            if (! isset($dayOfWeekStats[$dayName])) {
                $dayOfWeekStats[$dayName] = ['attended' => 0, 'scheduled' => 0];
            }
            $dayOfWeekStats[$dayName]['scheduled']++;
        }

        $insights['day_of_week_performance'] = $dayOfWeekStats;

        // Check-in time patterns
        $checkinPatterns = $attendances->groupBy(function ($attendance) {
            $hour = Carbon::parse($attendance->time_in)->hour;

            return match (true) {
                $hour < 7 => 'Very Early (< 7 AM)',
                $hour < 8 => 'Early (7-8 AM)',
                $hour < 9 => 'On Time (8-9 AM)',
                $hour < 10 => 'Late (9-10 AM)',
                default => 'Very Late (> 10 AM)'
            };
        })->map->count();

        $insights['checkin_patterns'] = $checkinPatterns->toArray();

        return $insights;
    }

    /**
     * Get professional standards compliance
     */
    private static function getProfessionalStandardsCompliance($user, Collection $attendances, Collection $scheduledShifts): array
    {
        $profession = static::getUserProfessionFromRole($user);

        // Define professional standards
        $standards = match ($profession) {
            'Dokter' => [
                'minimum_attendance' => 95,
                'maximum_late_per_month' => 2,
                'required_gps_accuracy' => 95,
                'shift_coverage_requirement' => 'Critical',
            ],
            'Paramedis' => [
                'minimum_attendance' => 90,
                'maximum_late_per_month' => 3,
                'required_gps_accuracy' => 90,
                'shift_coverage_requirement' => 'High',
            ],
            'NonParamedis' => [
                'minimum_attendance' => 85,
                'maximum_late_per_month' => 4,
                'required_gps_accuracy' => 85,
                'shift_coverage_requirement' => 'Standard',
            ],
            default => [
                'minimum_attendance' => 80,
                'maximum_late_per_month' => 5,
                'required_gps_accuracy' => 80,
                'shift_coverage_requirement' => 'Basic',
            ]
        };

        // Calculate current compliance
        $attendanceRate = $scheduledShifts->count() > 0 ?
            ($attendances->count() / $scheduledShifts->count()) * 100 : 0;

        $lateCount = $attendances->filter(function ($attendance) use ($scheduledShifts) {
            $attendance_date = $attendance->date;
            $scheduled = $scheduledShifts->firstWhere('tanggal_jaga', $attendance_date);
            if (! $scheduled) {
                return false;
            }

            $actualStart = Carbon::parse($attendance->time_in);
            $scheduledStart = Carbon::parse($scheduled->jam_masuk);

            return $actualStart->greaterThan($scheduledStart->addMinutes(15));
        })->count();

        $gpsValidRate = $attendances->count() > 0 ?
            ($attendances->where('is_location_valid_in', true)->count() / $attendances->count()) * 100 : 0;

        return [
            'profession' => $profession,
            'standards' => $standards,
            'current_performance' => [
                'attendance_rate' => round($attendanceRate, 1),
                'late_count' => $lateCount,
                'gps_validation_rate' => round($gpsValidRate, 1),
            ],
            'compliance_status' => [
                'attendance_compliant' => $attendanceRate >= $standards['minimum_attendance'],
                'punctuality_compliant' => $lateCount <= $standards['maximum_late_per_month'],
                'gps_compliant' => $gpsValidRate >= $standards['required_gps_accuracy'],
            ],
        ];
    }

    /**
     * Generate personalized recommendations
     */
    private static function generateRecommendations(array $performanceInsights, array $professionalCompliance): array
    {
        $recommendations = [];

        // Attendance recommendations
        if (! $professionalCompliance['compliance_status']['attendance_compliant']) {
            $gap = $professionalCompliance['standards']['minimum_attendance'] -
                   $professionalCompliance['current_performance']['attendance_rate'];
            $recommendations[] = [
                'type' => 'attendance',
                'priority' => 'high',
                'title' => 'Tingkatkan Kehadiran',
                'description' => "Perlu meningkatkan kehadiran sebesar {$gap}% untuk memenuhi standar profesi.",
                'action' => 'Fokus pada konsistensi kehadiran dan komunikasi proaktif untuk perubahan jadwal.',
            ];
        }

        // Punctuality recommendations
        if (! $professionalCompliance['compliance_status']['punctuality_compliant']) {
            $lateCount = $professionalCompliance['current_performance']['late_count'];
            $maxAllowed = $professionalCompliance['standards']['maximum_late_per_month'];
            $recommendations[] = [
                'type' => 'punctuality',
                'priority' => 'medium',
                'title' => 'Perbaiki Ketepatan Waktu',
                'description' => "Sudah terlambat {$lateCount} kali bulan ini (maks: {$maxAllowed}).",
                'action' => 'Rencanakan perjalanan lebih awal dan gunakan reminder untuk check-in.',
            ];
        }

        // GPS compliance recommendations
        if (! $professionalCompliance['compliance_status']['gps_compliant']) {
            $recommendations[] = [
                'type' => 'gps',
                'priority' => 'medium',
                'title' => 'Perbaiki Validasi GPS',
                'description' => 'Tingkat validasi GPS masih di bawah standar yang diperlukan.',
                'action' => 'Pastikan GPS aktif dan akurat sebelum melakukan check-in.',
            ];
        }

        // Performance pattern recommendations
        if (isset($performanceInsights['day_of_week_performance'])) {
            $worstDay = null;
            $worstRate = 100;

            foreach ($performanceInsights['day_of_week_performance'] as $day => $stats) {
                if ($stats['scheduled'] > 0) {
                    $rate = ($stats['attended'] / $stats['scheduled']) * 100;
                    if ($rate < $worstRate) {
                        $worstRate = $rate;
                        $worstDay = $day;
                    }
                }
            }

            if ($worstDay && $worstRate < 80) {
                $recommendations[] = [
                    'type' => 'pattern',
                    'priority' => 'low',
                    'title' => "Perhatikan Kehadiran Hari {$worstDay}",
                    'description' => "Kehadiran pada hari {$worstDay} cenderung lebih rendah ({$worstRate}%).",
                    'action' => "Evaluasi dan rencanakan lebih baik untuk shift hari {$worstDay}.",
                ];
            }
        }

        return $recommendations;
    }

    // Helper methods for calculations
    private static function calculateTotalScheduledHours(Collection $shifts): float
    {
        $totalMinutes = 0;
        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift->jam_masuk);
            $end = Carbon::parse($shift->jam_pulang);
            if ($end->lessThan($start)) {
                $end->addDay();
            }
            $totalMinutes += $start->diffInMinutes($end);
            if ($shift->break_duration_minutes) {
                $totalMinutes -= $shift->break_duration_minutes;
            }
        }

        return round($totalMinutes / 60, 2);
    }

    private static function calculateTotalWorkingHours(Collection $attendances): float
    {
        return round($attendances->sum('work_duration') / 60, 2);
    }

    private static function calculateAverageTime(Collection $attendances, string $timeField): ?string
    {
        $times = $attendances->filter($timeField)->map(function ($attendance) use ($timeField) {
            $time = Carbon::parse($attendance->$timeField);

            return $time->hour * 3600 + $time->minute * 60 + $time->second;
        });

        return $times->count() > 0 ? gmdate('H:i', $times->avg()) : null;
    }

    private static function getUserProfessionFromSchedule(int $userId): ?string
    {
        $recentSchedule = DB::table('jadwal_jagas')
            ->where('pegawai_id', $userId)
            ->whereNotNull('peran')
            ->orderBy('tanggal_jaga', 'desc')
            ->first();

        return $recentSchedule?->peran;
    }

    private static function getUserProfessionFromRole($user): string
    {
        $roleName = $user->role?->name ?? '';

        return match ($roleName) {
            'dokter' => 'Dokter',
            'paramedis', 'perawat' => 'Paramedis',
            default => 'NonParamedis'
        };
    }

    private static function getEmptyDetailedData(): array
    {
        return [
            'user' => null,
            'period' => null,
            'summary' => null,
            'compliance' => null,
            'daily_breakdown' => [],
            'monthly_trends' => [],
            'performance_insights' => [],
            'professional_compliance' => [],
            'recommendations' => [],
        ];
    }

    // Additional calculation methods (simplified for brevity)
    private static function calculateUserShortfall($shifts, $attendances)
    {
        return 0;
    }

    private static function calculateOvertimeHours($attendances)
    {
        return 0;
    }

    private static function calculateUserScheduleCompliance($shifts, $attendances)
    {
        return 0;
    }

    private static function calculatePunctualityScore($shifts, $attendances)
    {
        return 0;
    }

    private static function calculateGpsValidationRate($attendances)
    {
        return $attendances->count() > 0 ?
            ($attendances->where('is_location_valid_in', true)->count() / $attendances->count()) * 100 : 0;
    }

    private static function countEarlyArrivals($shifts, $attendances)
    {
        return 0;
    }

    private static function countLateArrivals($shifts, $attendances)
    {
        return 0;
    }

    private static function countEarlyDepartures($shifts, $attendances)
    {
        return 0;
    }
}
