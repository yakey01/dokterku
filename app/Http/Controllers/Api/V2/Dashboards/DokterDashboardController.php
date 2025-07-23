<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DokterDashboardController extends Controller
{
    /**
     * Dashboard utama dokter dengan stats real
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)
                ->where('aktif', true)
                ->first();
            
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan',
                    'data' => null
                ], 404);
            }

            // Cache dashboard stats untuk 5 menit
            $cacheKey = "dokter_dashboard_stats_{$user->id}";
            $stats = Cache::remember($cacheKey, 300, function () use ($dokter, $user) {
                $today = Carbon::today();
                $thisMonth = Carbon::now()->startOfMonth();
                $thisWeek = Carbon::now()->startOfWeek();

                // Hitung stats real
                $patientsToday = Tindakan::where('dokter_id', $dokter->id)
                    ->whereDate('tanggal_tindakan', $today)
                    ->distinct('pasien_id')
                    ->count();

                $tindakanToday = Tindakan::where('dokter_id', $dokter->id)
                    ->whereDate('tanggal_tindakan', $today)
                    ->count();

                $jaspelMonth = Tindakan::where('dokter_id', $dokter->id)
                    ->where('tanggal_tindakan', '>=', $thisMonth)
                    ->where('status_validasi', 'disetujui')
                    ->sum('jasa_dokter');

                $shiftsWeek = JadwalJaga::where('pegawai_id', $user->id)
                    ->where('tanggal_jaga', '>=', $thisWeek)
                    ->where('tanggal_jaga', '<=', Carbon::now()->endOfWeek())
                    ->count();

                // Attendance hari ini
                $attendanceToday = Attendance::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->first();

                return [
                    'patients_today' => $patientsToday,
                    'tindakan_today' => $tindakanToday,
                    'jaspel_month' => $jaspelMonth,
                    'shifts_week' => $shiftsWeek,
                    'attendance_today' => $attendanceToday ? [
                        'check_in' => $attendanceToday->time_in?->format('H:i'),
                        'check_out' => $attendanceToday->time_out?->format('H:i'),
                        'status' => $attendanceToday->time_out ? 'checked_out' : 'checked_in',
                        'duration' => $attendanceToday->formatted_work_duration
                    ] : null
                ];
            });

            // Performance metrics
            $performanceStats = $this->getPerformanceStats($dokter);
            
            // Next schedule
            $nextSchedule = $this->getNextSchedule($user);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data berhasil dimuat',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'jenis_pegawai' => $dokter->jenis_pegawai,
                        'unit_kerja' => $dokter->unit_kerja ?? 'Tidak ditentukan',
                        'avatar' => null,
                        'initials' => strtoupper(substr($user->name, 0, 2))
                    ],
                    'dokter' => [
                        'id' => $dokter->id,
                        'nama_lengkap' => $dokter->nama_lengkap ?? $user->name,
                        'nik' => $dokter->nik,
                        'jenis_pegawai' => $dokter->jenis_pegawai,
                        'unit_kerja' => $dokter->unit_kerja,
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
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dashboard: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Jadwal jaga dokter
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
     * Detail jaspel dokter
     */
    public function getJaspel(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)
                ->where('aktif', true)
                ->first();
            
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            // Jaspel bulan ini
            $jaspelQuery = Tindakan::where('dokter_id', $dokter->id)
                ->whereMonth('tanggal_tindakan', $month)
                ->whereYear('tanggal_tindakan', $year);

            $jaspelStats = [
                'total' => $jaspelQuery->sum('jasa_dokter'),
                'approved' => $jaspelQuery->where('status_validasi', 'disetujui')->sum('jasa_dokter'),
                'pending' => $jaspelQuery->where('status_validasi', 'pending')->sum('jasa_dokter'),
                'rejected' => $jaspelQuery->where('status_validasi', 'ditolak')->sum('jasa_dokter'),
                'count_tindakan' => $jaspelQuery->count()
            ];

            // Breakdown per hari
            $dailyBreakdown = $jaspelQuery->select(
                DB::raw('DATE(tanggal_tindakan) as date'),
                DB::raw('COUNT(*) as total_tindakan'),
                DB::raw('SUM(jasa_paramedis) as total_jaspel'),
                DB::raw('SUM(CASE WHEN status_validasi = "disetujui" THEN jasa_paramedis ELSE 0 END) as approved_jaspel')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // Breakdown per jenis tindakan
            $tindakanBreakdown = $jaspelQuery->select(
                'jenis_tindakan',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(jasa_paramedis) as total_jaspel')
            )
            ->groupBy('jenis_tindakan')
            ->orderByDesc('total_jaspel')
            ->get();

            // Riwayat jaspel
            $recentJaspel = $jaspelQuery->with(['pasien:id,nama_pasien'])
                ->orderByDesc('tanggal_tindakan')
                ->limit(10)
                ->get();

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
     * Riwayat tindakan dokter
     */
    public function getTindakan(Request $request)
    {
        try {
            $user = Auth::user();
            $dokter = Dokter::where('user_id', $user->id)
                ->where('aktif', true)
                ->first();
            
            $limit = min($request->get('limit', 15), 50);
            $status = $request->get('status');
            $search = $request->get('search');

            $query = Tindakan::where('dokter_id', $dokter->id)
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
                        'total' => Tindakan::where('dokter_id', $dokter->id)->count(),
                        'approved' => Tindakan::where('dokter_id', $dokter->id)->where('status_validasi', 'disetujui')->count(),
                        'pending' => Tindakan::where('dokter_id', $dokter->id)->where('status_validasi', 'pending')->count(),
                        'rejected' => Tindakan::where('dokter_id', $dokter->id)->where('status_validasi', 'ditolak')->count()
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
            
            // Presensi hari ini
            $attendanceToday = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            // History presensi bulan ini
            $attendanceHistory = Attendance::where('user_id', $user->id)
                ->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year)
                ->orderByDesc('date')
                ->get();

            // Stats presensi
            $attendanceStats = [
                'total_days' => $attendanceHistory->count(),
                'on_time' => $attendanceHistory->where('status', 'on_time')->count(),
                'late' => $attendanceHistory->where('status', 'late')->count(),
                'early_leave' => $attendanceHistory->where('status', 'early_leave')->count(),
                'total_hours' => $attendanceHistory->sum('work_duration_minutes') / 60
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
     * Check-in/Check-out
     */
    public function checkIn(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            // Cek apakah sudah check-in hari ini
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if ($attendance && $attendance->time_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah check-in hari ini'
                ], 422);
            }

            // Buat record attendance
            $attendance = Attendance::updateOrCreate([
                'user_id' => $user->id,
                'date' => $today
            ], [
                'time_in' => Carbon::now(),
                'location_in' => $request->get('location'),
                'latitude_in' => $request->get('latitude'),
                'longitude_in' => $request->get('longitude'),
                'status' => 'on_time'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkOut(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum check-in atau sudah check-out'
                ], 422);
            }

            $attendance->update([
                'time_out' => Carbon::now(),
                'location_out' => $request->get('location'),
                'latitude_out' => $request->get('latitude'),
                'longitude_out' => $request->get('longitude')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-out berhasil',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal check-out: ' . $e->getMessage()
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
            
            // Get upcoming schedules for mobile app
            $schedules = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', Carbon::today())
                ->orderBy('tanggal_jaga')
                ->limit(10)
                ->get()
                ->map(function ($jadwal) {
                    return [
                        'id' => $jadwal->id,
                        'tanggal' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'waktu' => '08:00 - 16:00', // Default fallback
                        'lokasi' => $jadwal->unit_kerja ?? 'Unit Kerja',
                        'jenis' => 'pagi', // Default fallback
                        'status' => 'scheduled'
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
    private function getPerformanceStats($dokter)
    {
        try {
            $month = Carbon::now()->month;
            $year = Carbon::now()->year;
            $user = Auth::user();
            
            // Get attendance ranking from AttendanceRecap with error handling
            $attendanceData = collect(); // Default empty collection
            $attendanceRate = 0; // Default rate
            
            try {
                $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Dokter');
                $attendanceRate = $this->getAttendanceRate($user);
            } catch (\Exception $e) {
                \Log::warning('AttendanceRecap error in getPerformanceStats', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year
                ]);
                // Continue with default values
            }
            
            // Find current user's ranking
            $currentUserRank = null;
            $totalDokter = $attendanceData->count();
            
            foreach ($attendanceData as $staff) {
                if ($staff['staff_id'] == $user->id) {
                    $currentUserRank = $staff['rank'];
                    break;
                }
            }
            
            // Debug logging
            \Log::info('🔍 DEBUG: getPerformanceStats', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'month' => $month,
                'year' => $year,
                'attendance_data_count' => $attendanceData->count(),
                'current_user_rank' => $currentUserRank,
                'total_dokter' => $totalDokter,
                'attendance_rate' => $attendanceRate,
            ]);
            
            return [
                'attendance_rank' => $currentUserRank ?? max($totalDokter + 1, 1),
                'total_staff' => max($totalDokter, 1),
                'attendance_percentage' => round($attendanceRate, 1),
                'patient_satisfaction' => 92,
                'attendance_rate' => $attendanceRate
            ];
        } catch (\Exception $e) {
            \Log::error('getPerformanceStats complete failure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return safe defaults
            return [
                'attendance_rank' => 1,
                'total_staff' => 1,
                'attendance_percentage' => 0,
                'patient_satisfaction' => 92,
                'attendance_rate' => 0
            ];
        }
    }

    /**
     * Get attendance rate using AttendanceRecap calculation method
     */
    private function getAttendanceRate($user)
    {
        try {
            $month = Carbon::now()->month;
            $year = Carbon::now()->year;
            
            \Log::info('🔍 DEBUG: getAttendanceRate start', [
                'user_id' => $user->id,
                'month' => $month,
                'year' => $year,
            ]);
            
            // Try to get attendance data from AttendanceRecap for current user
            try {
                $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Dokter');
                
                \Log::info('🔍 DEBUG: AttendanceRecap data', [
                    'count' => $attendanceData->count(),
                ]);
                
                // Find current user's attendance percentage
                foreach ($attendanceData as $staff) {
                    if ($staff['staff_id'] == $user->id) {
                        \Log::info('✅ Found user attendance', [
                            'attendance_percentage' => $staff['attendance_percentage'],
                        ]);
                        return $staff['attendance_percentage'];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('AttendanceRecap query failed in getAttendanceRate', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
                // Continue to fallback calculation
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
            
            // Count attendance days for the full month with error handling
            $attendanceDays = 0;
            try {
                $attendanceDays = Attendance::where('user_id', $user->id)
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->distinct('date')
                    ->count();
            } catch (\Exception $e) {
                \Log::warning('Attendance table query failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
                // Return 0 as safe default
                return 0;
            }
            
            $fallbackRate = $workingDays > 0 ? round(($attendanceDays / $workingDays) * 100, 2) : 0;
            
            \Log::info('🔍 DEBUG: Fallback calculation', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'working_days' => $workingDays,
                'attendance_days' => $attendanceDays,
                'fallback_rate' => $fallbackRate,
            ]);
            
            return $fallbackRate;
        } catch (\Exception $e) {
            \Log::error('getAttendanceRate complete failure', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown'
            ]);
            
            // Return safe default
            return 0;
        }
    }

    /**
     * Get next schedule
     */
    private function getNextSchedule($user)
    {
        try {
            $nextSchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', Carbon::today())
                ->orderBy('tanggal_jaga')
                ->first();

            if (!$nextSchedule) {
                return null;
            }

            return [
                'id' => $nextSchedule->id,
                'date' => $nextSchedule->tanggal_jaga->format('Y-m-d'),
                'formatted_date' => $nextSchedule->tanggal_jaga->format('l, d F Y'),
                'shift_name' => 'Shift Pagi', // Fallback
                'start_time' => '08:00',
                'end_time' => '16:00',
                'unit_kerja' => $nextSchedule->unit_kerja ?? 'Unit Kerja',
                'days_until' => Carbon::today()->diffInDays($nextSchedule->tanggal_jaga)
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
            
            // For dokter, only include "Dokter Jaga" unit
            $unitKerjaFilter = ['Dokter Jaga'];
            
            // SECURITY FIX: Only show schedules for the logged-in user
            $query = JadwalJaga::with(['pegawai', 'shiftTemplate'])
                ->join('pegawais', 'jadwal_jagas.pegawai_id', '=', 'pegawais.user_id')
                ->join('users', 'pegawais.user_id', '=', 'users.id')
                ->leftJoin('shift_templates', 'jadwal_jagas.shift_template_id', '=', 'shift_templates.id')
                ->where('jadwal_jagas.pegawai_id', $user->id)
                ->where('pegawais.jenis_pegawai', 'Dokter')
                ->whereIn('jadwal_jagas.unit_kerja', $unitKerjaFilter)
                ->whereDate('jadwal_jagas.tanggal_jaga', $date)
                ->select([
                    'jadwal_jagas.*',
                    'users.name as nama_dokter',
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
                    'dokter_name' => $schedule->nama_dokter ?: 'Unknown',
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
                'message' => 'Jadwal dokter berhasil dimuat',
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
                        'staff_type' => 'Dokter'
                    ]
                ],
                'meta' => [
                    'version' => '2.0',
                    'timestamp' => now()->toISOString(),
                    'request_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('DokterDashboardController::getIgdSchedules error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal dokter: ' . $e->getMessage(),
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
                ->where('pegawais.jenis_pegawai', 'Dokter')
                ->where('jadwal_jagas.unit_kerja', 'Dokter Jaga') // INCLUDE only Dokter Jaga
                ->whereBetween('jadwal_jagas.tanggal_jaga', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ])
                ->select([
                    'jadwal_jagas.*',
                    'pegawais.nama_lengkap as nama_dokter'
                ])
                ->orderBy('jadwal_jagas.tanggal_jaga')
                ->orderByRaw("FIELD(jadwal_jagas.unit_kerja, 'Pendaftaran', 'Pelayanan', 'Dokter Jaga')")
                ->get()
                ->map(function($schedule) {
                    return [
                        'id' => $schedule->id,
                        'tanggal' => Carbon::parse($schedule->tanggal_jaga)->format('Y-m-d'),
                        'unit_kerja' => $schedule->unit_kerja ?: 'Unit Kerja',
                        'dokter_name' => $schedule->nama_dokter ?: 'Unknown',
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
            \Log::error('DokterDashboardController::getWeeklySchedule error: ' . $e->getMessage());

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
}