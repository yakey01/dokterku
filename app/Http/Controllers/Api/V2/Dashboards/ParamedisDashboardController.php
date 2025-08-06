<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Tindakan;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Paramedis Dashboard",
 *     description="Dashboard endpoints for paramedis users"
 * )
 */
class ParamedisDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v2/dashboards/paramedis",
     *     summary="Get paramedis dashboard overview",
     *     tags={"Paramedis Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/StandardResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Not a paramedis user",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(Request $request)
    {
        // TRACE LOGGING: Track exact endpoint being called
        \Log::info('🚀 DASHBOARD ENDPOINT CALLED', [
            'endpoint' => 'ParamedisDashboardController@index',
            'url' => $request->url(),
            'user_id' => Auth::id(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ]);
        
        try {
            $user = Auth::user();
            $paramedis = Pegawai::where('user_id', $user->id)
                ->where('jenis_pegawai', 'Paramedis')
                ->first();
            
            if (!$paramedis) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data paramedis tidak ditemukan',
                    'data' => null
                ], 404);
            }

            // Cache dashboard stats untuk 1 menit (reduced to ensure fresh work location data)
            $cacheKey = "paramedis_dashboard_stats_{$user->id}";
            // Force clear cache when work location might have changed
            if (request()->get('refresh_location')) {
                Cache::forget($cacheKey);
            }
            $stats = Cache::remember($cacheKey, 60, function () use ($paramedis, $user) {
                // Always ensure we have fresh work location data in cache
                $user->load('workLocation');
                $today = Carbon::today();
                $thisMonth = Carbon::now()->startOfMonth();
                $thisWeek = Carbon::now()->startOfWeek();

                // Hitung stats real
                $patientsToday = Tindakan::where('paramedis_id', $paramedis->id)
                    ->whereDate('tanggal_tindakan', $today)
                    ->distinct('pasien_id')
                    ->count();

                $tindakanToday = Tindakan::where('paramedis_id', $paramedis->id)
                    ->whereDate('tanggal_tindakan', $today)
                    ->count();

                // WORLD-CLASS: Use Enhanced Jaspel Service for consistent calculation
                $enhancedService = app(\App\Services\EnhancedJaspelService::class);
                $currentJaspelData = $enhancedService->getComprehensiveJaspelData($user, date('n'), date('Y'));
                $lastMonthJaspelData = $enhancedService->getComprehensiveJaspelData($user, date('n') - 1, date('Y'));
                
                $jaspelMonth = $currentJaspelData['summary']['total_paid'];
                $jaspelLastMonth = $lastMonthJaspelData['summary']['total_paid'];
                
                // DEBUG LOGGING: Track exact calculation values
                \Log::info('🔍 PARAMEDIS DASHBOARD JASPEL DEBUG', [
                    'endpoint' => 'ParamedisDashboardController@index',
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'current_month' => date('n'),
                    'current_year' => date('Y'),
                    'current_jaspel_data' => $currentJaspelData,
                    'last_month_jaspel_data' => $lastMonthJaspelData,
                    'final_jaspel_month' => $jaspelMonth,
                    'final_jaspel_last_month' => $jaspelLastMonth,
                    'timestamp' => now()->toISOString()
                ]);
                
                // Calculate growth percentage
                $growthPercent = 0;
                if ($jaspelLastMonth > 0) {
                    $growthPercent = (($jaspelMonth - $jaspelLastMonth) / $jaspelLastMonth) * 100;
                } elseif ($jaspelMonth > 0) {
                    $growthPercent = 100; // 100% if no data last month but has data this month
                }

                $shiftsWeek = JadwalJaga::where('pegawai_id', $user->id)
                    ->where('tanggal_jaga', '>=', $thisWeek)
                    ->where('tanggal_jaga', '<=', Carbon::now()->endOfWeek())
                    ->count();

                // Attendance hari ini dengan status detail
                $attendanceStatus = Attendance::getTodayStatus($user->id);
                $attendanceToday = $attendanceStatus['attendance'];

                return [
                    'patients_today' => $patientsToday,
                    'tindakan_today' => $tindakanToday,
                    'jaspel_month' => $jaspelMonth,
                    'jaspel_last_month' => $jaspelLastMonth,
                    'jaspel_growth_percent' => round($growthPercent, 1),
                    'shifts_week' => $shiftsWeek,
                    'attendance_today' => [
                        'status' => $attendanceStatus['status'],
                        'message' => $attendanceStatus['message'],
                        'can_check_in' => $attendanceStatus['can_check_in'],
                        'can_check_out' => $attendanceStatus['can_check_out'],
                        'check_in_time' => $attendanceToday ? $attendanceToday->time_in?->format('H:i') : null,
                        'check_out_time' => $attendanceToday ? $attendanceToday->time_out?->format('H:i') : null,
                        'work_duration' => $attendanceToday ? $attendanceToday->formatted_work_duration : null,
                        'work_duration_minutes' => $attendanceToday && $attendanceToday->time_in && $attendanceToday->time_out 
                            ? $attendanceToday->work_duration 
                            : ($attendanceToday && $attendanceToday->time_in ? Carbon::now()->diffInMinutes($attendanceToday->time_in) : null),
                        'location_in' => $attendanceToday ? $attendanceToday->location_name_in : null,
                        'location_out' => $attendanceToday ? $attendanceToday->location_name_out : null,
                        'is_late' => $attendanceToday ? $attendanceToday->status === 'late' : false
                    ]
                ];
            });

            // Performance metrics
            $performanceStats = $this->getPerformanceStats($paramedis);
            
            // Next schedule
            $nextSchedule = $this->getNextSchedule($user);

            // FINAL RESPONSE LOGGING: Track what data is actually returned
            $responseData = [
                'success' => true,
                'message' => 'Dashboard data berhasil dimuat',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'jenis_pegawai' => $paramedis->jenis_pegawai,
                        'unit_kerja' => $paramedis->unit_kerja ?? 'Tidak ditentukan',
                        'avatar' => null,
                        'initials' => strtoupper(substr($user->name, 0, 2)),
                        'work_location' => ($freshWorkLocation = $user->workLocation?->fresh()) ? [
                            'id' => $freshWorkLocation->id,
                            'name' => $freshWorkLocation->name,
                            'address' => $freshWorkLocation->address,
                            'coordinates' => [
                                'latitude' => (float) $freshWorkLocation->latitude,
                                'longitude' => (float) $freshWorkLocation->longitude,
                            ],
                            'radius_meters' => $freshWorkLocation->radius_meters,
                            'location_type' => $freshWorkLocation->location_type_label,
                            'is_active' => $freshWorkLocation->is_active,
                            'tolerance_settings' => $freshWorkLocation->getToleranceSettings(),
                        ] : ($user->location ? [
                            'id' => $user->location->id,
                            'name' => $user->location->name,
                            'coordinates' => [
                                'latitude' => (float) $user->location->latitude,
                                'longitude' => (float) $user->location->longitude,
                            ],
                            'radius_meters' => $user->location->radius,
                            'legacy' => true
                        ] : null)
                    ],
                    'paramedis' => [
                        'id' => $paramedis->id,
                        'nama_lengkap' => $paramedis->nama_lengkap ?? $user->name,
                        'nik' => $paramedis->nik,
                        'jenis_pegawai' => $paramedis->jenis_pegawai,
                        'unit_kerja' => $paramedis->unit_kerja,
                        'status' => 'Aktif'
                    ],
                    'stats' => $stats,
                    'performance' => $performanceStats,
                    'next_schedule' => $nextSchedule,
                    'current_time' => Carbon::now()->format('H:i'),
                    'current_date' => Carbon::now()->format('Y-m-d'),
                    'greeting' => $this->getGreeting()
                ],
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            ];
            
            // FINAL DEBUG: Log the response being sent
            \Log::info('📤 FINAL DASHBOARD RESPONSE', [
                'user_id' => $user->id,
                'jaspel_month' => $responseData['data']['stats']['jaspel_month'] ?? 'not_set',
                'jaspel_last_month' => $responseData['data']['stats']['jaspel_last_month'] ?? 'not_set',
                'jaspel_growth_percent' => $responseData['data']['stats']['jaspel_growth_percent'] ?? 'not_set',
                'endpoint' => 'ParamedisDashboardController@index',
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json($responseData);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dashboard: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get real-time attendance status
     */
    public function getAttendanceStatus(Request $request)
    {
        try {
            $user = Auth::user();
            $attendanceStatus = Attendance::getTodayStatus($user->id);
            $attendance = $attendanceStatus['attendance'];
            
            // Debug logging
            if ($attendance) {
                \Log::info('🔍 Attendance Debug', [
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id,
                    'time_in' => $attendance->time_in,
                    'time_out' => $attendance->time_out,
                    'status' => $attendanceStatus['status'],
                    'can_check_out' => $attendanceStatus['can_check_out']
                ]);
            }
            
            // Get next schedule info
            $nextSchedule = $this->getNextSchedule($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Status presensi berhasil dimuat',
                'data' => [
                    'status' => $attendanceStatus['status'],
                    'message' => $attendanceStatus['message'],
                    'can_check_in' => $attendanceStatus['can_check_in'],
                    'can_check_out' => $attendanceStatus['can_check_out'],
                    'attendance' => $attendance ? [
                        'id' => $attendance->id,
                        'date' => $attendance->date->format('Y-m-d'),
                        'check_in_time' => $attendance->time_in?->format('H:i'),
                        'check_out_time' => $attendance->time_out?->format('H:i'),
                        'work_duration' => $attendance->formatted_work_duration,
                        'work_duration_minutes' => $attendance->time_in && $attendance->time_out 
                            ? $attendance->work_duration 
                            : ($attendance->time_in ? Carbon::now()->diffInMinutes($attendance->time_in) : null),
                        'location_in' => $attendance->location_name_in,
                        'location_out' => $attendance->location_name_out,
                        'status' => $attendance->status,
                        'is_late' => $attendance->status === 'late',
                        'coordinates_in' => [
                            'latitude' => $attendance->latitude,
                            'longitude' => $attendance->longitude,
                        ],
                        'coordinates_out' => $attendance->checkout_latitude && $attendance->checkout_longitude ? [
                            'latitude' => $attendance->checkout_latitude,
                            'longitude' => $attendance->checkout_longitude,
                        ] : null
                    ] : null,
                    'next_schedule' => $nextSchedule,
                    'work_location' => $user->workLocation ? [
                        'id' => $user->workLocation->id,
                        'name' => $user->workLocation->name,
                        'address' => $user->workLocation->address,
                        'coordinates' => [
                            'latitude' => (float) $user->workLocation->latitude,
                            'longitude' => (float) $user->workLocation->longitude,
                        ],
                        'radius_meters' => $user->workLocation->radius_meters,
                        'is_active' => $user->workLocation->is_active,
                    ] : null
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'current_time' => Carbon::now()->format('H:i:s'),
                    'current_date' => Carbon::now()->format('Y-m-d'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat status presensi: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Jadwal jaga paramedis
     */
    public function getJadwalJaga(Request $request)
    {
        try {
            $user = Auth::user();
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            // Jadwal untuk bulan tertentu
            $jadwalJaga = JadwalJaga::where('pegawai_id', $user->id)
                ->whereMonth('tanggal_jaga', $month)
                ->whereYear('tanggal_jaga', $year)
                ->orderBy('tanggal_jaga')
                ->get();

            // Format untuk calendar dengan fallback
            $calendarEvents = $jadwalJaga->map(function ($jadwal) {
                return [
                    'id' => $jadwal->id,
                    'title' => 'Shift Jaga',
                    'start' => $jadwal->tanggal_jaga->format('Y-m-d'),
                    'end' => $jadwal->tanggal_jaga->format('Y-m-d'),
                    'color' => '#10b981',
                    'description' => $jadwal->unit_kerja ?? 'Unit Kerja',
                    'shift_info' => [
                        'nama_shift' => 'Shift Pagi',
                        'jam_masuk' => '08:00',
                        'jam_pulang' => '16:00',
                        'unit_kerja' => $jadwal->unit_kerja ?? 'Unit Kerja',
                        'status' => 'aktif'
                    ]
                ];
            });

            // Jadwal minggu ini
            $weeklySchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->whereBetween('tanggal_jaga', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
                ->orderBy('tanggal_jaga')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal jaga berhasil dimuat',
                'data' => [
                    'calendar_events' => $calendarEvents,
                    'weekly_schedule' => $weeklySchedule,
                    'month' => $month,
                    'year' => $year,
                    'total_shifts' => $jadwalJaga->count(),
                    'next_shift' => $this->getNextSchedule($user)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal jaga: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Detail jaspel paramedis
     */
    public function getJaspel(Request $request)
    {
        try {
            $user = Auth::user();
            $paramedis = Pegawai::where('user_id', $user->id)
                ->where('jenis_pegawai', 'Paramedis')
                ->first();
            
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            // WORLD-CLASS: Use Enhanced Jaspel Service for consistent data across all endpoints
            $enhancedService = app(\App\Services\EnhancedJaspelService::class);
            $comprehensiveData = $enhancedService->getComprehensiveJaspelData($user, $month, $year);
            
            // Extract data from enhanced service
            $summary = $comprehensiveData['summary'];
            
            // Old Jaspel query for backwards compatibility with existing breakdown logic
            $jaspelQuery = \App\Models\Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year);

            // Use Enhanced Service summary for accurate totals
            $totalPending = $summary['total_pending'];
            
            $jaspelStats = [
                'total' => $summary['total_paid'] + $summary['total_pending'] + $summary['total_rejected'],
                'approved' => $summary['total_paid'],
                'pending' => $summary['total_pending'],
                'pending_breakdown' => [
                    'jaspel_records' => $jaspelQuery->where('status_validasi', 'pending')->sum('nominal'),
                    'tindakan_awaiting_jaspel' => $summary['total_pending'] - $jaspelQuery->where('status_validasi', 'pending')->sum('nominal'),
                    'total' => $summary['total_pending']
                ],
                'rejected' => $summary['total_rejected'],
                'count_tindakan' => $summary['count_paid'] + $summary['count_pending'] + $summary['count_rejected']
            ];

            // Breakdown per hari using Jaspel model
            $dailyBreakdown = $jaspelQuery->select(
                DB::raw('DATE(tanggal) as date'),
                DB::raw('COUNT(*) as total_tindakan'),
                DB::raw('SUM(nominal) as total_jaspel'),
                DB::raw('SUM(CASE WHEN status_validasi IN ("disetujui", "approved") THEN nominal ELSE 0 END) as approved_jaspel')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // Breakdown per jenis jaspel
            $tindakanBreakdown = $jaspelQuery->select(
                'jenis_jaspel as jenis_tindakan',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(nominal) as total_jaspel')
            )
            ->groupBy('jenis_jaspel')
            ->orderByDesc('total_jaspel')
            ->get();

            // Riwayat jaspel using Jaspel model
            $recentJaspel = $jaspelQuery->with(['tindakan.pasien:id,nama_pasien'])
                ->orderByDesc('tanggal')
                ->limit(10)
                ->get()
                ->map(function($jaspel) {
                    $tindakan = $jaspel->tindakan;
                    return [
                        'id' => $jaspel->id,
                        'tanggal_tindakan' => $jaspel->tanggal,
                        'nominal' => $jaspel->nominal,
                        'jasa_paramedis' => $jaspel->nominal, // For compatibility
                        'status_validasi' => $jaspel->status_validasi,
                        'jenis_tindakan' => $jaspel->jenis_jaspel,
                        'pasien' => $tindakan && $tindakan->pasien ? $tindakan->pasien : null
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Data jaspel berhasil dimuat',
                'data' => [
                    'stats' => $jaspelStats,
                    'daily_breakdown' => $dailyBreakdown,
                    'tindakan_breakdown' => $tindakanBreakdown,
                    'recent_jaspel' => $recentJaspel,
                    'month' => $month,
                    'year' => $year
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data jaspel: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Riwayat tindakan paramedis
     */
    public function getTindakan(Request $request)
    {
        try {
            $user = Auth::user();
            $paramedis = Pegawai::where('user_id', $user->id)
                ->where('jenis_pegawai', 'Paramedis')
                ->first();
            
            $limit = min($request->get('limit', 15), 50);
            $status = $request->get('status');
            $search = $request->get('search');

            $query = Tindakan::where('paramedis_id', $paramedis->id)
                ->with(['pasien:id,nama_pasien,nomor_pasien']);

            if ($status) {
                $query->where('status_validasi', $status);
            }

            if ($search) {
                $query->whereHas('pasien', function($q) use ($search) {
                    $q->where('nama_pasien', 'like', "%{$search}%")
                      ->orWhere('nomor_pasien', 'like', "%{$search}%");
                });
            }

            $tindakan = $query->orderByDesc('tanggal_tindakan')
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'message' => 'Data tindakan berhasil dimuat',
                'data' => $tindakan,
                'meta' => [
                    'summary' => [
                        'total' => Tindakan::where('paramedis_id', $paramedis->id)->count(),
                        'approved' => Tindakan::where('paramedis_id', $paramedis->id)->whereIn('status_validasi', ['disetujui', 'approved'])->count(),
                        'pending' => Tindakan::where('paramedis_id', $paramedis->id)->where('status_validasi', 'pending')->count(),
                        'rejected' => Tindakan::where('paramedis_id', $paramedis->id)->whereIn('status_validasi', ['ditolak', 'rejected'])->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data tindakan: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Status dan history presensi
     */
    public function getPresensi(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            $filter = $request->get('filter', 'month'); // default to month
            
            // Presensi hari ini
            $attendanceToday = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            // Build query based on filter
            $query = Attendance::where('user_id', $user->id);
            
            switch ($filter) {
                case 'today':
                    $query->whereDate('date', $today);
                    break;
                case 'week':
                    $query->whereBetween('date', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                default:
                    $query->whereMonth('date', Carbon::now()->month)
                          ->whereYear('date', Carbon::now()->year);
                    break;
            }

            // History presensi based on filter
            $attendanceHistory = $query->orderByDesc('date')->get();

            // Stats presensi based on filtered data
            $totalWorkDuration = 0;
            foreach ($attendanceHistory as $attendance) {
                if ($attendance->work_duration) {
                    $totalWorkDuration += $attendance->work_duration;
                }
            }
            
            // Calculate scheduled shifts instead of all working days
            // "Tidak Hadir" should only count when there's a scheduled shift but no attendance
            $scheduledShifts = 0;
            $startDate = null;
            $endDate = null;
            
            switch ($filter) {
                case 'today':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today();
                    break;
                case 'week':
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                    break;
                case 'month':
                default:
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    break;
            }
            
            // Count scheduled shifts (jadwal jaga) for this period
            // BUT only count those where the shift has already ended (completed)
            $scheduledShifts = \App\Models\JadwalJaga::where('pegawai_id', $user->id)
                ->whereBetween('tanggal_jaga', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->with('shiftTemplate') // Load shift template to get end time
                ->get();
            
            // Filter shifts to only count those that have already ended
            $completedShifts = $scheduledShifts->filter(function ($shift) {
                if (!$shift->shiftTemplate || !$shift->shiftTemplate->jam_pulang) {
                    return false; // Skip shifts without end time
                }
                
                $shiftDate = Carbon::parse($shift->tanggal_jaga);
                $shiftEndTime = Carbon::parse($shift->shiftTemplate->jam_pulang);
                
                // Handle night shifts that end the next day (like 23:59 - 00:15)
                $shiftStartTime = Carbon::parse($shift->shiftTemplate->jam_masuk);
                if ($shiftEndTime->hour < 12 && $shiftStartTime->hour > 12) {
                    // This is a night shift that crosses midnight - end time is on next day
                    $shiftEndDateTime = $shiftDate->copy()->addDay()
                        ->setHour($shiftEndTime->hour)
                        ->setMinute($shiftEndTime->minute)
                        ->setSecond(0);
                } else {
                    // Regular shift - end time is on same day
                    $shiftEndDateTime = $shiftDate->copy()
                        ->setHour($shiftEndTime->hour)
                        ->setMinute($shiftEndTime->minute)
                        ->setSecond(0);
                }
                
                // Only count shifts that have already ended
                return Carbon::now()->gte($shiftEndDateTime);
            });
            
            // Use completed shifts as working days
            $workingDays = $completedShifts->count();
            
            // If no shifts completed yet, working days should be 0
            // This means no absent days for shifts that haven't ended
            if ($workingDays == 0) {
                // No completed shifts = no working days = no absent days
                $workingDays = 0;
            }
            
            $attendanceStats = [
                'total_days' => $attendanceHistory->count(),
                'working_days' => $workingDays, // Added: actual working days (completed shifts only)
                'scheduled_shifts' => $scheduledShifts->count(), // Added: total scheduled shifts
                'completed_shifts' => $completedShifts->count(), // Added: shifts that have ended
                'on_time' => $attendanceHistory->where('status', 'on_time')->count(),
                'late' => $attendanceHistory->where('status', 'late')->count(),
                'early_leave' => $attendanceHistory->where('status', 'early_leave')->count(),
                'absent' => $attendanceHistory->where('status', 'absent')->count(),
                'total_hours' => round($totalWorkDuration / 60, 1),
                'filter_applied' => $filter,
                'filter_period' => $this->getFilterPeriodLabel($filter)
            ];

            return response()->json([
                'success' => true,
                'message' => 'Data presensi berhasil dimuat',
                'data' => [
                    'today' => $attendanceToday ? [
                        'date' => $attendanceToday->date->format('Y-m-d'),
                        'time_in' => $attendanceToday->time_in?->format('H:i'),
                        'time_out' => $attendanceToday->time_out?->format('H:i'),
                        'status' => $attendanceToday->status,
                        'work_duration' => $attendanceToday->formatted_work_duration,
                        'can_check_in' => false,
                        'can_check_out' => !$attendanceToday->time_out
                    ] : [
                        'date' => $today->format('Y-m-d'),
                        'time_in' => null,
                        'time_out' => null,
                        'status' => null,
                        'work_duration' => null,
                        'can_check_in' => true,
                        'can_check_out' => false
                    ],
                    'history' => $attendanceHistory,
                    'stats' => $attendanceStats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data presensi: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Enhanced Check-in using AttendanceValidationService
     */
    public function checkIn(Request $request)
    {
        try {
            // Use the same validation and logic as AttendanceController
            $attendanceController = new \App\Http\Controllers\Api\V2\Attendance\AttendanceController(
                new \App\Services\AttendanceValidationService()
            );
            
            return $attendanceController->checkin($request);

        } catch (\Exception $e) {
            \Log::error('ParamedisDashboardController::checkIn error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal check-in: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Enhanced Check-out using AttendanceValidationService
     */
    public function checkOut(Request $request)
    {
        try {
            // Use the same validation and logic as AttendanceController
            $attendanceController = new \App\Http\Controllers\Api\V2\Attendance\AttendanceController(
                new \App\Services\AttendanceValidationService()
            );
            
            return $attendanceController->checkout($request);

        } catch (\Exception $e) {
            \Log::error('ParamedisDashboardController::checkOut error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal check-out: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Endpoint untuk schedule API (untuk mobile app)
     */
    public function schedules(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get upcoming schedules for mobile app with shift template data
            $schedules = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', Carbon::today())
                ->with('shiftTemplate') // Load shift template relationship
                ->orderBy('tanggal_jaga')
                ->limit(10)
                ->get()
                ->map(function ($jadwal) {
                    // Get shift template data
                    $waktu = '08:00 - 16:00'; // Default fallback
                    $jenis = 'pagi'; // Default fallback
                    
                    if ($jadwal->shiftTemplate) {
                        // Format time from shift template
                        $jamMasuk = $jadwal->shiftTemplate->jam_masuk ? 
                            Carbon::parse($jadwal->shiftTemplate->jam_masuk)->format('H:i') : '08:00';
                        $jamPulang = $jadwal->shiftTemplate->jam_pulang ? 
                            Carbon::parse($jadwal->shiftTemplate->jam_pulang)->format('H:i') : '16:00';
                        $waktu = $jamMasuk . ' - ' . $jamPulang;
                        
                        // Determine shift type based on start time
                        if ($jadwal->shiftTemplate->jam_masuk) {
                            $hour = Carbon::parse($jadwal->shiftTemplate->jam_masuk)->hour;
                            if ($hour >= 6 && $hour < 14) {
                                $jenis = 'pagi';
                            } elseif ($hour >= 14 && $hour < 22) {
                                $jenis = 'siang';
                            } else {
                                $jenis = 'malam';
                            }
                        }
                    }
                    
                    // Determine status
                    $status = 'scheduled';
                    if ($jadwal->status_jaga === 'Selesai') {
                        $status = 'completed';
                    } elseif ($jadwal->status_jaga === 'Batal') {
                        $status = 'missed';
                    }
                    
                    return [
                        'id' => (string) $jadwal->id,
                        'tanggal' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'waktu' => $waktu,
                        'lokasi' => $jadwal->unit_kerja ?? 'Unit Kerja',
                        'jenis' => $jenis,
                        'status' => $status
                    ];
                });

            return response()->json($schedules);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }


    /**
     * Get performance stats
     */
    private function getPerformanceStats($paramedis)
    {
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $user = Auth::user();
        
        // Get attendance ranking from AttendanceRecap
        $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Paramedis');
        
        // Find current user's ranking
        $currentUserRank = null;
        $totalParamedis = $attendanceData->count();
        
        foreach ($attendanceData as $staff) {
            if ($staff['staff_id'] == $user->id) {
                $currentUserRank = $staff['rank'];
                break;
            }
        }
        
        // Calculate attendance rate
        $attendanceRate = $this->getAttendanceRate($user);
        
        // Debug logging
        \Log::info('🔍 DEBUG: getPerformanceStats', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'month' => $month,
            'year' => $year,
            'attendance_data_count' => $attendanceData->count(),
            'current_user_rank' => $currentUserRank,
            'total_paramedis' => $totalParamedis,
            'attendance_rate' => $attendanceRate,
        ]);
        
        return [
            'attendance_rank' => $currentUserRank ?? $totalParamedis + 1,
            'total_staff' => $totalParamedis,
            'attendance_percentage' => round($attendanceRate, 1),
            'patient_satisfaction' => 92,
            'attendance_rate' => $attendanceRate
        ];
    }

    /**
     * Get attendance rate using AttendanceRecap calculation method
     */
    private function getAttendanceRate($user)
    {
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        
        \Log::info('🔍 DEBUG: getAttendanceRate start', [
            'user_id' => $user->id,
            'month' => $month,
            'year' => $year,
        ]);
        
        // Get attendance data from AttendanceRecap for current user
        $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Paramedis');
        
        \Log::info('🔍 DEBUG: AttendanceRecap data', [
            'count' => $attendanceData->count(),
            'data' => $attendanceData->toArray(),
        ]);
        
        // Find current user's attendance percentage
        foreach ($attendanceData as $staff) {
            \Log::info('🔍 DEBUG: Checking staff', [
                'staff_id' => $staff['staff_id'],
                'user_id' => $user->id,
                'attendance_percentage' => $staff['attendance_percentage'] ?? 'not set',
            ]);
            
            if ($staff['staff_id'] == $user->id) {
                \Log::info('✅ Found user attendance', [
                    'attendance_percentage' => $staff['attendance_percentage'],
                ]);
                return $staff['attendance_percentage'];
            }
        }
        
        \Log::info('🔄 Using fallback calculation');
        
        // Fallback: calculate manually using same method as AttendanceRecap
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        // Count working days (Monday to Saturday, exclude Sunday)
        $workingDays = 0;
        $tempDate = $startDate->copy();
        while ($tempDate->lte($endDate)) {
            if ($tempDate->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $tempDate->addDay();
        }
        
        // Count attendance days for the full month
        $attendanceDays = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->distinct('date')
            ->count();
        
        $fallbackRate = $workingDays > 0 ? round(($attendanceDays / $workingDays) * 100, 2) : 0;
        
        \Log::info('🔍 DEBUG: Fallback calculation', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'working_days' => $workingDays,
            'attendance_days' => $attendanceDays,
            'fallback_rate' => $fallbackRate,
        ]);
        
        return $fallbackRate;
    }

    /**
     * Get next schedule
     */
    private function getNextSchedule($user)
    {
        try {
            $nextSchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', Carbon::today())
                ->with('shiftTemplate')
                ->orderBy('tanggal_jaga')
                ->first();

            if (!$nextSchedule) {
                return null;
            }

            // Get shift template information
            $shiftTemplate = $nextSchedule->shiftTemplate;
            $shiftName = $shiftTemplate ? $shiftTemplate->nama_shift : 'Shift Tidak Ditentukan';
            $startTime = $shiftTemplate ? $shiftTemplate->jam_masuk : '08:00';
            $endTime = $shiftTemplate ? $shiftTemplate->jam_keluar : '16:00';

            // Get work location for validation
            $workLocation = $user->workLocation;
            $locationInfo = null;
            
            if ($workLocation) {
                $locationInfo = [
                    'id' => $workLocation->id,
                    'name' => $workLocation->name,
                    'address' => $workLocation->address,
                    'coordinates' => [
                        'latitude' => (float) $workLocation->latitude,
                        'longitude' => (float) $workLocation->longitude,
                    ],
                    'radius_meters' => $workLocation->radius_meters,
                    'shift_allowed' => $shiftTemplate ? $workLocation->isShiftAllowed($shiftTemplate->nama_shift) : true
                ];
            }

            return [
                'id' => $nextSchedule->id,
                'date' => $nextSchedule->tanggal_jaga->format('Y-m-d'),
                'formatted_date' => $nextSchedule->tanggal_jaga->format('l, d F Y'),
                'shift_name' => $shiftName,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'unit_kerja' => $nextSchedule->unit_kerja ?? 'Unit Kerja',
                'status_jaga' => $nextSchedule->status_jaga,
                'days_until' => Carbon::today()->diffInDays($nextSchedule->tanggal_jaga),
                'is_today' => $nextSchedule->tanggal_jaga->isToday(),
                'work_location' => $locationInfo,
                'can_checkin' => $nextSchedule->tanggal_jaga->isToday() && $nextSchedule->status_jaga === 'aktif' && $workLocation && $workLocation->is_active
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get IGD schedules with dynamic unit kerja data
     * Same implementation as DokterDashboardController for consistency
     */
    public function getIgdSchedules(Request $request)
    {
        try {
            $user = Auth::user();
            $category = $request->get('category', 'all');
            $date = $request->get('date', now()->format('Y-m-d'));
            
            // Map category to unit_kerja values - same as dokter implementation
            $unitKerjaMap = [
                'all' => ['Pendaftaran', 'Pelayanan', 'Dokter Jaga'],
                'pendaftaran' => ['Pendaftaran'],
                'pelayanan' => ['Pelayanan'],
                'dokter_jaga' => ['Dokter Jaga']
            ];
            
            $unitKerjaFilter = $unitKerjaMap[$category] ?? $unitKerjaMap['all'];
            
            // SECURITY: For paramedis, exclude "Dokter Jaga" unit even if "all" is requested
            $unitKerjaFilter = array_diff($unitKerjaFilter, ['Dokter Jaga']);
            
            // SECURITY FIX: Only show schedules for the logged-in user
            $query = JadwalJaga::with(['pegawai', 'shiftTemplate'])
                ->join('pegawais', 'jadwal_jagas.pegawai_id', '=', 'pegawais.user_id')
                ->join('users', 'pegawais.user_id', '=', 'users.id')
                ->leftJoin('shift_templates', 'jadwal_jagas.shift_template_id', '=', 'shift_templates.id')
                ->where('jadwal_jagas.pegawai_id', $user->id)
                ->where('pegawais.jenis_pegawai', 'Paramedis')
                ->whereIn('jadwal_jagas.unit_kerja', $unitKerjaFilter)
                ->whereDate('jadwal_jagas.tanggal_jaga', $date)
                ->select([
                    'jadwal_jagas.*',
                    'users.name as nama_paramedis',
                    'shift_templates.nama_shift as shift_name',
                    'shift_templates.jam_masuk',
                    'shift_templates.jam_pulang'
                ])
                ->orderByRaw("
                    FIELD(jadwal_jagas.unit_kerja, 'Pendaftaran', 'Pelayanan', 'Dokter Jaga'),
                    CASE 
                        WHEN shift_templates.nama_shift = 'Pagi' THEN 1
                        WHEN shift_templates.nama_shift = 'Siang' THEN 2
                        WHEN shift_templates.nama_shift = 'Malam' THEN 3
                        ELSE 4
                    END,
                    users.name ASC
                ");

            $schedules = $query->get()->map(function($schedule) {
                // Format time display
                $timeDisplay = 'TBA';
                if ($schedule->jam_masuk && $schedule->jam_pulang) {
                    $timeDisplay = Carbon::parse($schedule->jam_masuk)->format('H:i') . 
                                  ' - ' . 
                                  Carbon::parse($schedule->jam_pulang)->format('H:i');
                }

                return [
                    'id' => $schedule->id,
                    'tanggal' => Carbon::parse($schedule->tanggal_jaga)->format('Y-m-d'),
                    'tanggal_formatted' => Carbon::parse($schedule->tanggal_jaga)->format('l, d F Y'),
                    'unit_kerja' => $schedule->unit_kerja ?: 'Unit Kerja',
                    'paramedis_name' => $schedule->nama_paramedis ?: 'Unknown',
                    'shift_name' => $schedule->shift_name ?: 'Shift',
                    'jam_masuk' => $schedule->jam_masuk,
                    'jam_keluar' => $schedule->jam_pulang,
                    'waktu_display' => $timeDisplay,
                    'status' => $schedule->status_jaga ?? 'scheduled',
                    'created_at' => $schedule->created_at
                ];
            });

            // Group by unit_kerja for better organization
            $groupedSchedules = $schedules->groupBy('unit_kerja');

            return response()->json([
                'success' => true,
                'message' => 'Jadwal paramedis berhasil dimuat',
                'data' => [
                    'schedules' => $schedules,
                    'grouped_schedules' => $groupedSchedules,
                    'category' => $category,
                    'date' => $date,
                    'total_count' => $schedules->count(),
                    'units_available' => $schedules->pluck('unit_kerja')->unique()->values(),
                    'filters_applied' => [
                        'unit_kerja' => $unitKerjaFilter,
                        'date' => $date,
                        'staff_type' => 'Paramedis'
                    ]
                ],
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('ParamedisDashboardController::getIgdSchedules error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal paramedis: ' . $e->getMessage(),
                'data' => [
                    'schedules' => [],
                    'grouped_schedules' => [],
                    'total_count' => 0
                ]
            ], 500);
        }
    }

    /**
     * Get weekly schedules with dynamic data
     * Same pattern as dokter implementation
     */
    public function getWeeklySchedule(Request $request)
    {
        try {
            $user = Auth::user();
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
            
            // SECURITY FIX: Only show schedules for the logged-in user
            $schedules = JadwalJaga::with(['shiftTemplate'])
                ->join('pegawais', 'jadwal_jagas.pegawai_id', '=', 'pegawais.user_id')
                ->where('jadwal_jagas.pegawai_id', $user->id)
                ->where('pegawais.jenis_pegawai', 'Paramedis')
                ->whereIn('jadwal_jagas.unit_kerja', ['Pendaftaran', 'Pelayanan']) // EXCLUDE Dokter Jaga
                ->whereBetween('jadwal_jagas.tanggal_jaga', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ])
                ->select([
                    'jadwal_jagas.*',
                    'pegawais.nama_lengkap as nama_paramedis'
                ])
                ->orderBy('jadwal_jagas.tanggal_jaga')
                ->orderByRaw("FIELD(jadwal_jagas.unit_kerja, 'Pendaftaran', 'Pelayanan', 'Dokter Jaga')")
                ->get()
                ->map(function($schedule) {
                    return [
                        'id' => $schedule->id,
                        'tanggal' => Carbon::parse($schedule->tanggal_jaga)->format('Y-m-d'),
                        'unit_kerja' => $schedule->unit_kerja ?: 'Unit Kerja',
                        'paramedis_name' => $schedule->nama_paramedis ?: 'Unknown',
                        'shift_name' => $schedule->shiftTemplate ? $schedule->shiftTemplate->nama_shift : 'Shift',
                        'jam_masuk' => $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_masuk : null,
                        'jam_keluar' => $schedule->shiftTemplate ? $schedule->shiftTemplate->jam_pulang : null,
                        'status' => $schedule->status_jaga ?? 'scheduled'
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Jadwal minggu ini berhasil dimuat',
                'data' => [
                    'schedules' => $schedules,
                    'week_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                        'start_formatted' => $startDate->format('d M Y'),
                        'end_formatted' => $endDate->format('d M Y')
                    ],
                    'total_count' => $schedules->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('ParamedisDashboardController::getWeeklySchedule error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal minggu ini: ' . $e->getMessage(),
                'data' => [
                    'schedules' => [],
                    'total_count' => 0
                ]
            ], 500);
        }
    }

    /**
     * Force refresh work location data by clearing relevant caches
     */
    public function refreshWorkLocation(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Clear relevant caches
            $cacheKeys = [
                "paramedis_dashboard_stats_{$user->id}",
                "user_work_location_{$user->id}",
                "attendance_status_{$user->id}"
            ];
            
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
            
            // Force reload user work location
            $user->load('workLocation');
            
            return response()->json([
                'success' => true,
                'message' => 'Work location data refreshed successfully',
                'data' => [
                    'work_location' => $user->workLocation ? [
                        'id' => $user->workLocation->id,
                        'name' => $user->workLocation->name,
                        'address' => $user->workLocation->address,
                        'coordinates' => [
                            'latitude' => (float) $user->workLocation->latitude,
                            'longitude' => (float) $user->workLocation->longitude,
                        ],
                        'radius_meters' => $user->workLocation->radius_meters,
                        'is_active' => $user->workLocation->is_active,
                        'updated_at' => $user->workLocation->updated_at->toISOString(),
                    ] : null,
                    'cache_cleared' => $cacheKeys,
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh work location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get greeting based on time
     */
    private function getGreeting()
    {
        $hour = Carbon::now()->hour;
        
        if ($hour < 12) {
            return 'Selamat Pagi';
        } elseif ($hour < 17) {
            return 'Selamat Siang';
        } else {
            return 'Selamat Malam';
        }
    }

    /**
     * Get work location status only (lightweight endpoint for polling)
     */
    public function getWorkLocationStatus(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get fresh work location data
            $user->load('workLocation');
            $workLocation = $user->workLocation?->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Work location status retrieved',
                'data' => [
                    'work_location' => $workLocation ? [
                        'id' => $workLocation->id,
                        'name' => $workLocation->name,
                        'address' => $workLocation->address,
                        'coordinates' => [
                            'latitude' => (float) $workLocation->latitude,
                            'longitude' => (float) $workLocation->longitude,
                        ],
                        'radius_meters' => $workLocation->radius_meters,
                        'is_active' => $workLocation->is_active,
                        'updated_at' => $workLocation->updated_at?->toISOString(),
                    ] : null,
                    'user_id' => $user->id,
                    'timestamp' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching work location status', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch work location status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check and auto-assign work location if available
     */
    public function checkAndAssignWorkLocation(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Check if user already has work location
            if ($user->work_location_id) {
                $workLocation = WorkLocation::find($user->work_location_id);
                if ($workLocation && $workLocation->is_active) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Work location already assigned',
                        'data' => [
                            'work_location' => [
                                'id' => $workLocation->id,
                                'name' => $workLocation->name,
                                'address' => $workLocation->address,
                                'is_active' => $workLocation->is_active
                            ]
                        ]
                    ]);
                }
            }
            
            // Try to find a suitable work location based on user's pegawai data
            $pegawai = $user->pegawai;
            if (!$pegawai) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pegawai data found for user',
                    'data' => null
                ], 404);
            }
            
            // Look for work location based on unit_kerja or name matching
            $workLocation = WorkLocation::where('is_active', true)
                ->where(function($query) use ($pegawai, $user) {
                    // Try to match by unit kerja
                    if ($pegawai->unit_kerja) {
                        $query->where('name', 'LIKE', '%' . $pegawai->unit_kerja . '%')
                              ->orWhere('unit_kerja', $pegawai->unit_kerja);
                    }
                    // Try to match by location type
                    if ($pegawai->jenis_pegawai) {
                        $query->orWhere('location_type', $pegawai->jenis_pegawai);
                    }
                })
                ->first();
            
            // If no match found, get the first active work location (default)
            if (!$workLocation) {
                $workLocation = WorkLocation::where('is_active', true)
                    ->orderBy('created_at', 'asc')
                    ->first();
            }
            
            if ($workLocation) {
                // Assign work location to user
                $user->work_location_id = $workLocation->id;
                $user->save();
                
                // Clear caches
                $cacheKeys = [
                    "paramedis_dashboard_stats_{$user->id}",
                    "user_work_location_{$user->id}",
                    "attendance_status_{$user->id}"
                ];
                
                foreach ($cacheKeys as $key) {
                    Cache::forget($key);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Work location assigned successfully',
                    'data' => [
                        'work_location' => [
                            'id' => $workLocation->id,
                            'name' => $workLocation->name,
                            'address' => $workLocation->address,
                            'coordinates' => [
                                'latitude' => (float) $workLocation->latitude,
                                'longitude' => (float) $workLocation->longitude,
                            ],
                            'radius_meters' => $workLocation->radius_meters,
                            'is_active' => $workLocation->is_active
                        ],
                        'assignment_reason' => $pegawai->unit_kerja ? 
                            "Matched by unit kerja: {$pegawai->unit_kerja}" : 
                            'Assigned default active location'
                    ]
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No active work location found in the system',
                'data' => null
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('Error in checkAndAssignWorkLocation', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check/assign work location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get filter period label for UI display
     */
    private function getFilterPeriodLabel($filter)
    {
        switch ($filter) {
            case 'today':
                return 'Hari Ini';
            case 'week':
                return 'Minggu Ini';
            case 'month':
            default:
                return 'Bulan Ini';
        }
    }
}