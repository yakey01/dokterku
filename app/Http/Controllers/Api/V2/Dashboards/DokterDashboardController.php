<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Jaspel;
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
            
            // Handle missing user gracefully
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'data' => null
                ], 401);
            }
            
            // Try to find dokter record, but don't fail if it doesn't exist
            $dokter = Dokter::where('user_id', $user->id)
                ->where('aktif', true)
                ->first();

            // Cache dashboard stats untuk 2 menit (reduced from 5 minutes)
            $cacheKey = "dokter_dashboard_stats_{$user->id}";
            $stats = Cache::remember($cacheKey, 120, function () use ($dokter, $user) {
                $today = Carbon::today();
                $thisMonth = Carbon::now()->startOfMonth();
                $thisWeek = Carbon::now()->startOfWeek();

                // Calculate stats - handle case where dokter record doesn't exist
                $patientsToday = 0;
                $tindakanToday = 0;
                
                if ($dokter) {
                    // Only calculate if dokter record exists
                    $patientsToday = Tindakan::where('dokter_id', $dokter->id)
                        ->whereDate('tanggal_tindakan', $today)
                        ->distinct('pasien_id')
                        ->count();

                    $tindakanToday = Tindakan::where('dokter_id', $dokter->id)
                        ->whereDate('tanggal_tindakan', $today)
                        ->count();
                }

                // WORLD-CLASS: Use Jaspel model for consistent calculation with Jaspel page
                // This uses user_id so it works even without dokter record
                $jaspelMonth = \App\Models\Jaspel::where('user_id', $user->id)
                    ->whereMonth('tanggal', $thisMonth->month)
                    ->whereYear('tanggal', $thisMonth->year)
                    ->whereIn('status_validasi', ['disetujui', 'approved'])
                    ->sum('nominal');

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

            // Performance metrics - handle null dokter
            $performanceStats = $dokter ? $this->getPerformanceStats($dokter) : [
                'average_response_time' => 0,
                'patient_satisfaction' => 0,
                'treatment_success_rate' => 0
            ];
            
            // Next schedule
            $nextSchedule = $this->getNextSchedule($user);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data berhasil dimuat',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $dokter ? $dokter->nama_lengkap : $user->name,
                        'email' => $dokter ? $dokter->email : $user->email,
                        'phone' => $dokter ? $dokter->no_telepon : $user->phone,
                        'address' => $dokter ? $dokter->alamat : $user->address,
                        'date_of_birth' => $dokter ? $dokter->tanggal_lahir?->format('d F Y') : $user->date_of_birth?->format('d F Y'),
                        'gender' => $this->formatGender($dokter ? $dokter->jenis_kelamin : $user->gender),
                        'bio' => $dokter ? $dokter->keterangan : $user->bio,
                        'nik' => $dokter ? $dokter->nik : null,
                        'nomor_sip' => $dokter ? $dokter->nomor_sip : null,
                        'jabatan' => $dokter ? $dokter->jabatan : 'Dokter',
                        'spesialisasi' => $dokter ? $dokter->spesialisasi : null,
                        'tanggal_bergabung' => $dokter ? $dokter->tanggal_bergabung?->format('d F Y') : null,
                        'status_akun' => $dokter ? $dokter->status_akun : null,
                        'avatar' => $dokter ? $dokter->foto : $user->profile_photo_path,
                        'initials' => strtoupper(substr($dokter ? $dokter->nama_lengkap : $user->name, 0, 2))
                    ],
                    'dokter' => $dokter ? [
                        'id' => $dokter->id,
                        'nama_lengkap' => $dokter->nama_lengkap ?? $user->name,
                        'nik' => $dokter->nik,
                        'jenis_pegawai' => $dokter->jenis_pegawai,
                        'unit_kerja' => $dokter->unit_kerja,
                        'status' => 'Aktif'
                    ] : [
                        'id' => null,
                        'nama_lengkap' => $user->name,
                        'nik' => null,
                        'jenis_pegawai' => 'Dokter',
                        'unit_kerja' => 'Tidak ditentukan',
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
     * Jadwal jaga dokter with proper relationship integration
     */
    public function getJadwalJaga(Request $request)
    {
        try {
            $user = Auth::user();
            
            \Log::info('JadwalJaga API called', [
                'user_id' => $user ? $user->id : 'null',
                'user_name' => $user ? $user->name : 'null',
                'request_url' => $request->url(),
                'request_method' => $request->method(),
                'headers' => $request->headers->all()
            ]);
            
            if (!$user) {
                \Log::warning('JadwalJaga API: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }
            
            $nowJakarta = Carbon::now()->setTimezone('Asia/Jakarta');
            $month = $request->get('month', $nowJakarta->month);
            $year = $request->get('year', $nowJakarta->year);
            $today = $nowJakarta->copy()->startOfDay();
            
            // Check if this is a refresh request
            $isRefresh = $request->has('refresh') || $request->header('Cache-Control') === 'no-cache';
            
            // Cache key for jadwal jaga with short TTL for quick refresh
            $cacheKey = "jadwal_jaga_{$user->id}_{$month}_{$year}";
            $cacheTTL = $isRefresh ? 3 : 10; // 3 seconds for refresh, 10 seconds for normal (faster updates)
            
            // Clear cache if refresh requested
            if ($isRefresh) {
                Cache::forget($cacheKey);
                \Log::info("🔄 Cleared jadwal jaga cache for user {$user->id} due to refresh request");
            }
            
            // Helper to compute fresh jadwal data (used for both refresh/no-refresh)
            $computeJadwalData = function () use ($user, $month, $year, $today, $cacheTTL, $isRefresh, $nowJakarta) {
                // Get the pegawai_id from the relationship
                $pegawaiId = $user->pegawai_id ?: ($user->pegawai ? $user->pegawai->id : null);
                
                // Enhanced query with proper relationships
                $jadwalJaga = collect();
                
                if ($pegawaiId) {
                    // Query using the correct pegawai_id from pegawais table
                    $jadwalJaga = JadwalJaga::where('pegawai_id', $pegawaiId)
                        ->whereMonth('tanggal_jaga', $month)
                        ->whereYear('tanggal_jaga', $year)
                        ->with(['shiftTemplate', 'pegawai'])
                        ->orderBy('tanggal_jaga')
                        ->get();
                } else {
                    // Fallback: try querying with user_id (for backward compatibility)
                    $jadwalJaga = JadwalJaga::where('pegawai_id', $user->id)
                        ->whereMonth('tanggal_jaga', $month)
                        ->whereYear('tanggal_jaga', $year)
                        ->with(['shiftTemplate', 'pegawai'])
                        ->orderBy('tanggal_jaga')
                        ->get();
                }

                \Log::info('JadwalJaga query result', [
                    'user_id' => $user->id,
                    'pegawai_id' => $pegawaiId,
                    'month' => $month,
                    'year' => $year,
                    'jadwal_count' => $jadwalJaga->count(),
                    'jadwal_ids' => $jadwalJaga->pluck('id')->toArray(),
                    'query_type' => $pegawaiId ? 'Using pegawai_id' : 'Using user_id fallback'
                ]);

                // Format untuk calendar dengan proper shift template data
                $calendarEvents = $jadwalJaga->map(function ($jadwal) use ($user) {
                    $shiftTemplate = $jadwal->shiftTemplate;
                    
                    return [
                        'id' => $jadwal->id,
                        'title' => $shiftTemplate ? $shiftTemplate->nama_shift : 'Shift Jaga',
                        'start' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'end' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'color' => $jadwal->color,
                        'description' => $jadwal->unit_kerja ?? 'Unit Kerja',
                        'shift_info' => [
                            'id' => $shiftTemplate ? $shiftTemplate->id : null,
                            'nama_shift' => $shiftTemplate ? $shiftTemplate->nama_shift : 'Shift',
                            'jam_masuk' => $shiftTemplate ? $shiftTemplate->jam_masuk_format : '08:00',
                            'jam_pulang' => $shiftTemplate ? $shiftTemplate->jam_pulang_format : '16:00',
                            'durasi_jam' => $shiftTemplate ? $shiftTemplate->durasi_jam : 8,
                            'warna' => $shiftTemplate ? $shiftTemplate->warna : '#3b82f6',
                            'unit_kerja' => $jadwal->unit_kerja ?? 'Unit Kerja',
                            'status' => $jadwal->status_jaga ?? 'aktif',
                            'peran' => $jadwal->peran ?? 'Dokter Jaga',
                            'employee_name' => $user->name,
                            'keterangan' => $jadwal->keterangan
                        ]
                    ];
                });

                // Enhanced weekly schedule with full relationship data
                $weeklySchedule = JadwalJaga::where('pegawai_id', $pegawaiId ?: $user->id)
                    ->whereBetween('tanggal_jaga', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])
                    ->with(['shiftTemplate', 'pegawai'])
                    ->orderBy('tanggal_jaga')
                    ->get()
                    ->map(function ($jadwal) use ($user) {
                        $shiftTemplate = $jadwal->shiftTemplate;
                        
                        return [
                            'id' => $jadwal->id,
                            'tanggal_jaga' => $jadwal->tanggal_jaga->format('Y-m-d'),
                            'tanggal_formatted' => $jadwal->tanggal_jaga->format('l, d F Y'),
                            'unit_kerja' => $jadwal->unit_kerja,
                            'peran' => $jadwal->peran,
                            'status_jaga' => $jadwal->status_jaga,
                            'keterangan' => $jadwal->keterangan,
                            'employee_name' => $user->name,
                            'shift_template' => $shiftTemplate ? [
                                'id' => $shiftTemplate->id,
                                'nama_shift' => $shiftTemplate->nama_shift,
                                'jam_masuk' => $shiftTemplate->jam_masuk_format,
                                'jam_pulang' => $shiftTemplate->jam_pulang_format,
                                'durasi_jam' => $shiftTemplate->durasi_jam,
                                'warna' => $shiftTemplate->warna ?? '#3b82f6'
                            ] : null
                        ];
                    });

                // FIXED: Get today's schedule specifically for attendance validation
                $todaySchedule = JadwalJaga::where('pegawai_id', $pegawaiId ?: $user->id)
                    ->whereDate('tanggal_jaga', $today)
                    ->with(['shiftTemplate', 'pegawai'])
                    ->get()
                    ->map(function ($jadwal) use ($user) {
                        $shiftTemplate = $jadwal->shiftTemplate;
                        
                        return [
                            'id' => $jadwal->id,
                            'tanggal_jaga' => $jadwal->tanggal_jaga->format('Y-m-d'),
                            'tanggal_formatted' => $jadwal->tanggal_jaga->format('d/m/Y'),
                            'pegawai_id' => $jadwal->pegawai_id,
                            'employee_name' => $jadwal->pegawai->name ?? $user->name,
                            'shift_template_id' => $jadwal->shift_template_id,
                            'unit_kerja' => $jadwal->unit_kerja,
                            'unit_instalasi' => $jadwal->unit_instalasi,
                            'peran' => $jadwal->peran,
                            'status_jaga' => $jadwal->status_jaga,
                            'keterangan' => $jadwal->keterangan,
                            'shift_template' => $shiftTemplate ? [
                                'id' => $shiftTemplate->id,
                                'nama_shift' => $shiftTemplate->nama_shift,
                                'jam_masuk' => $shiftTemplate->jam_masuk_format,
                                'jam_pulang' => $shiftTemplate->jam_pulang_format,
                                'durasi_jam' => $shiftTemplate->durasi_jam,
                                'warna' => $shiftTemplate->warna ?? '#3b82f6'
                            ] : null
                        ];
                    });

                // Get current active shift for today
                $currentShift = $todaySchedule->where('status_jaga', 'Aktif')->first();

                // ENHANCED: Calculate schedule card statistics
                $now = $nowJakarta;
                $todayDate = $now->toDateString();
                $currentTime = $now->format('H:i:s');
                
                // All schedules for the requested month/year with shift templates
                $allSchedules = JadwalJaga::where('pegawai_id', $pegawaiId ?: $user->id)
                    ->whereMonth('tanggal_jaga', $month)
                    ->whereYear('tanggal_jaga', $year)
                    ->with(['shiftTemplate'])
                    ->get();
                
                // Completed shifts (past dates OR today's shifts that have ended)
                $completedShifts = $allSchedules->filter(function ($jadwal) use ($todayDate, $currentTime) {
                    $shiftDate = $jadwal->tanggal_jaga->format('Y-m-d');
                    
                    // Past dates are automatically completed
                    if ($shiftDate < $todayDate) {
                        return true;
                    }
                    
                    // For today's shifts, check if shift has ended based on shift template
                    if ($shiftDate === $todayDate && $jadwal->shiftTemplate && $jadwal->shiftTemplate->jam_pulang) {
                        $shiftEndTime = $jadwal->shiftTemplate->jam_pulang;
                        return $currentTime >= $shiftEndTime;
                    }
                    
                    return false;
                });
                
                // Upcoming shifts (future dates OR today's shifts that haven't started/ended)
                $upcomingShifts = $allSchedules->filter(function ($jadwal) use ($todayDate, $currentTime) {
                    $shiftDate = $jadwal->tanggal_jaga->format('Y-m-d');
                    
                    // Future dates are upcoming
                    if ($shiftDate > $todayDate) {
                        return true;
                    }
                    
                    // For today's shifts, check if shift hasn't ended yet
                    if ($shiftDate === $todayDate && $jadwal->shiftTemplate && $jadwal->shiftTemplate->jam_pulang) {
                        $shiftEndTime = $jadwal->shiftTemplate->jam_pulang;
                        return $currentTime < $shiftEndTime;
                    }
                    
                    return false;
                });
                
                // Calculate total hours from completed shifts (using actual attendance data)
                $totalHours = $completedShifts->sum(function ($jadwal) use ($user) {
                    // First, try to get actual hours from attendance records
                    $attendance = \App\Models\Attendance::where('user_id', $user->id)
                        ->whereDate('date', $jadwal->tanggal_jaga)
                        ->first();
                    
                    if ($attendance && $attendance->time_in && $attendance->time_out) {
                        // Use actual worked hours from attendance (check-out - check-in)
                        $timeIn = Carbon::parse($attendance->time_in);
                        $timeOut = Carbon::parse($attendance->time_out);
                        return $timeOut->diffInHours($timeIn);
                    }
                    
                    // If no attendance record, calculate from shift template
                    if ($jadwal->shiftTemplate && $jadwal->shiftTemplate->durasi_jam) {
                        return $jadwal->shiftTemplate->durasi_jam;
                    }
                    
                    // Fallback: calculate from jam_masuk and jam_pulang
                    if ($jadwal->shiftTemplate && $jadwal->shiftTemplate->jam_masuk && $jadwal->shiftTemplate->jam_pulang) {
                        $startTime = Carbon::parse($jadwal->shiftTemplate->jam_masuk);
                        $endTime = Carbon::parse($jadwal->shiftTemplate->jam_pulang);
                        
                        // Handle overnight shifts
                        if ($endTime->lt($startTime)) {
                            $endTime->addDay();
                        }
                        
                        return $startTime->diffInHours($endTime);
                    }
                    
                    // Don't assume default hours - return 0 if no data available
                    return 0;
                });

                // Schedule card statistics
                $scheduleStats = [
                    'completed' => $completedShifts->count(),
                    'upcoming' => $upcomingShifts->count(),
                    'total_hours' => $totalHours,
                    'total_shifts' => $allSchedules->count(),
                    'current_month' => $month,
                    'current_year' => $year,
                    'month_name' => Carbon::create($year, $month, 1)->format('F Y')
                ];

                return [
                    'calendar_events' => $calendarEvents,
                    'weekly_schedule' => $weeklySchedule,
                    'today' => $todaySchedule,
                    'currentShift' => $currentShift,
                    'schedule_stats' => $scheduleStats,
                    'month' => $month,
                    'year' => $year,
                    'cache_info' => [
                        'cached_at' => now()->toISOString(),
                        'cache_ttl' => $cacheTTL,
                        'is_refresh' => $isRefresh
                    ]
                ];
            };

            // Use cache for jadwal jaga data, but bypass cache entirely when refresh is requested
            if ($isRefresh) {
                $jadwalData = $computeJadwalData();
            } else {
                $jadwalData = Cache::remember($cacheKey, $cacheTTL, $computeJadwalData);
            }

            \Log::info('JadwalJaga API response', [
                'user_id' => $user->id,
                'calendar_events_count' => count($jadwalData['calendar_events'] ?? []),
                'weekly_schedule_count' => count($jadwalData['weekly_schedule'] ?? []),
                'today_count' => count($jadwalData['today'] ?? []),
                'schedule_stats' => $jadwalData['schedule_stats'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal jaga berhasil dimuat',
                'data' => $jadwalData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getJadwalJaga', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal jaga: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail jaspel dokter - WORLD-CLASS implementation with Jaspel model integration
     */
    public function getJaspel(Request $request)
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
            
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            // WORLD-CLASS: Use Jaspel model with multi-status support
            $jaspelQuery = Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->whereHas('tindakan', function($query) {
                    $query->whereIn('status_validasi', ['disetujui', 'approved']);
                });

            // WORLD-CLASS: Enhanced pending calculation including bendahara validation queue
            $pendingJaspelRecords = (clone $jaspelQuery)->where('status_validasi', 'pending')->sum('nominal');
            
            // Add approved Tindakan awaiting Jaspel generation (dokter portion)
            $pendingFromTindakan = \App\Models\Tindakan::where('dokter_id', $dokter->id)
                ->whereMonth('tanggal_tindakan', $month)
                ->whereYear('tanggal_tindakan', $year)
                ->whereIn('status_validasi', ['approved', 'disetujui'])
                ->whereDoesntHave('jaspel', function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->whereIn('jenis_jaspel', ['dokter_umum', 'dokter_spesialis']);
                })
                ->where('jasa_dokter', '>', 0)
                ->sum('jasa_dokter');
                
            $totalPending = $pendingJaspelRecords + $pendingFromTindakan; // Dokter gets 100% of jasa_dokter
            
            // Clone queries to avoid interference with multi-status support
            $jaspelStats = [
                'total' => (clone $jaspelQuery)->sum('nominal'),
                'disetujui' => (clone $jaspelQuery)->whereIn('status_validasi', ['disetujui', 'approved'])->sum('nominal'),
                'pending' => $totalPending,
                'pending_breakdown' => [
                    'jaspel_records' => $pendingJaspelRecords,
                    'tindakan_awaiting_jaspel' => $pendingFromTindakan,
                    'total' => $totalPending
                ],
                'rejected' => (clone $jaspelQuery)->whereIn('status_validasi', ['ditolak', 'rejected'])->sum('nominal'),
                'count_jaspel' => (clone $jaspelQuery)->count()
            ];

            // WORLD-CLASS: Enhanced breakdown with proper Jaspel relations
            $dailyBreakdown = (clone $jaspelQuery)->select(
                DB::raw('DATE(tanggal) as date'),
                DB::raw('COUNT(*) as total_jaspel'),
                DB::raw('SUM(nominal) as total_amount'),
                DB::raw('SUM(CASE WHEN status_validasi = "disetujui" THEN nominal ELSE 0 END) as disetujui_amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // WORLD-CLASS: Breakdown by Jaspel type with enhanced data
            $jaspelTypeBreakdown = (clone $jaspelQuery)->select(
                'jenis_jaspel',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(nominal) as total_amount'),
                DB::raw('AVG(nominal) as avg_amount')
            )
            ->groupBy('jenis_jaspel')
            ->orderByDesc('total_amount')
            ->get();

            // WORLD-CLASS: Recent Jaspel with full relation data
            $recentJaspel = (clone $jaspelQuery)->with([
                'tindakan.jenisTindakan:id,nama',
                'tindakan.pasien:id,nama',
                'validasiBy:id,name'
            ])
                ->orderByDesc('tanggal')
                ->limit(10)
                ->get()
                ->map(function($jaspel) {
                    $tindakan = $jaspel->tindakan;
                    return [
                        'id' => $jaspel->id,
                        'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                        'nominal' => $jaspel->nominal,
                        'jenis_jaspel' => $jaspel->jenis_jaspel,
                        'status_validasi' => $jaspel->status_validasi,
                        'jenis_tindakan' => $tindakan && $tindakan->jenisTindakan ? $tindakan->jenisTindakan->nama : null,
                        'pasien_nama' => $tindakan && $tindakan->pasien ? $tindakan->pasien->nama : null,
                        'validator' => $jaspel->validasiBy ? $jaspel->validasiBy->name : null,
                        'validated_at' => $jaspel->validasi_at ? $jaspel->validasi_at->format('Y-m-d H:i') : null
                    ];
                });

            // WORLD-CLASS: Additional statistics
            $performanceMetrics = [
                'avg_jaspel_per_day' => $jaspelStats['count_jaspel'] > 0 ? round($jaspelStats['total'] / max(1, Carbon::now()->day), 0) : 0,
                'highest_daily_earning' => $dailyBreakdown->max('disetujui_amount') ?? 0,
                'most_profitable_type' => $jaspelTypeBreakdown->first()->jenis_jaspel ?? null,
                'total_validated_tindakan' => Jaspel::where('user_id', $user->id)
                    ->whereHas('tindakan', function($q) { $q->where('status_validasi', 'disetujui'); })
                    ->count()
            ];

            // Transform data for mobile app compatibility
            $jaspelItems = $recentJaspel->map(function($item) {
                return [
                    'id' => (string)$item['id'],
                    'tanggal' => $item['tanggal'],
                    'jenis' => $item['jenis_jaspel'] ?? 'Jaspel Dokter',
                    'jumlah' => $item['nominal'],
                    'status' => $item['status_validasi'] === 'disetujui' ? 'paid' : 
                               ($item['status_validasi'] === 'pending' ? 'pending' : 'rejected'),
                    'keterangan' => $item['jenis_tindakan'] ?? 'Tindakan Medis',
                    'validated_by' => $item['validator'],
                    'validated_at' => $item['validated_at']
                ];
            });

            $summary = [
                'total_paid' => $jaspelStats['disetujui'],
                'total_pending' => $jaspelStats['pending'],
                'total_rejected' => $jaspelStats['rejected'],
                'count_paid' => (clone $jaspelQuery)->where('status_validasi', 'disetujui')->count(),
                'count_pending' => (clone $jaspelQuery)->where('status_validasi', 'pending')->count(),
                'count_rejected' => (clone $jaspelQuery)->where('status_validasi', 'ditolak')->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Data jaspel dokter berhasil dimuat',
                'data' => [
                    'jaspel_items' => $jaspelItems,
                    'summary' => $summary,
                    'stats' => $jaspelStats,
                    'daily_breakdown' => $dailyBreakdown,
                    'jaspel_type_breakdown' => $jaspelTypeBreakdown,
                    'performance_metrics' => $performanceMetrics
                ],
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'user_name' => $user->name,
                    'dokter_id' => $dokter->id,
                    'dokter_nama' => $dokter->nama_lengkap ?? $user->name,
                    'specialization' => $dokter->spesialisasi ?? 'Umum',
                    'version' => '2.0',
                    'timestamp' => now()->toISOString()
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
                        'disetujui' => Tindakan::where('dokter_id', $dokter->id)->where('status_validasi', 'disetujui')->count(),
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
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }
            
            $today = Carbon::today('Asia/Jakarta');
            
            // Presensi hari ini (multi-shift aware)
            $todayRecords = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->orderBy('time_in')
                ->get();

            $anyOpen = $todayRecords->contains(function ($a) {
                return $a->time_in && !$a->time_out;
            });
            // Choose a representative record: prefer the open one, otherwise last of today
            $attendanceToday = $todayRecords->firstWhere(function ($a) {
                return $a->time_in && !$a->time_out;
            }) ?? $todayRecords->last();

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
                        'date' => optional($attendanceToday->date)->format('Y-m-d') ?? $today->format('Y-m-d'),
                        'time_in' => $attendanceToday->time_in?->format('H:i'),
                        'time_out' => $attendanceToday->time_out?->format('H:i'),
                        'status' => $attendanceToday->status,
                        'work_duration' => $attendanceToday->formatted_work_duration,
                        'can_check_in' => false,
                        'can_check_out' => $anyOpen
                    ] : [
                        'date' => $today->format('Y-m-d'),
                        'time_in' => null,
                        'time_out' => null,
                        'status' => null,
                        'work_duration' => null,
                        'can_check_in' => true,
                        'can_check_out' => false
                    ],
                    'today_records' => $todayRecords->map(function ($a) {
                        return [
                            'id' => $a->id,
                            'jadwal_jaga_id' => $a->jadwal_jaga_id,
                            'time_in' => $a->time_in?->format('H:i'),
                            'time_out' => $a->time_out?->format('H:i'),
                            'status' => $a->status,
                        ];
                    })->values(),
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
            $currentTime = Carbon::now()->setTimezone('Asia/Jakarta');
            $todayJakarta = $currentTime->copy()->startOfDay();
            $today = $todayJakarta->toDateString();

            // Ambil SEMUA jadwal jaga hari ini (Aktif) lalu pilih yang paling relevan berdasarkan waktu sekarang
            $jadwalList = JadwalJaga::where('pegawai_id', $user->id)
                ->whereDate('tanggal_jaga', $todayJakarta)
                // Do not filter by status_jaga; choose based on time to avoid stale/mis-set statuses
                ->with('shiftTemplate')
                ->get();

            if ($jadwalList->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki jadwal jaga hari ini. Hubungi admin untuk informasi lebih lanjut.',
                    'code' => 'NO_SCHEDULE'
                ], 422);
            }

            // Tentukan shift yang sedang berlangsung (atau terdekat) dengan buffer
            $selectedJadwal = null;
            $selectedMeta = null;
            foreach ($jadwalList as $item) {
                $tpl = $item->shiftTemplate;
                if (!$tpl) {
                    continue;
                }
                $startTimeStr = $tpl->jam_masuk_format ?? '08:00';
                $endTimeStr = $tpl->jam_pulang_format ?? '16:00';
                $start = Carbon::parse($todayJakarta->toDateString() . ' ' . $startTimeStr, 'Asia/Jakarta');
                $end = Carbon::parse($todayJakarta->toDateString() . ' ' . $endTimeStr, 'Asia/Jakarta');
                // Overnight handling
                if ($end->lt($start)) {
                    $end->addDay();
                }

                // Buffer logic (≥60 menit untuk shift ≤30 menit)
                $durationMinutes = $end->diffInMinutes($start);
                $bufferMinutes = $durationMinutes <= 30 ? 60 : 30;
                $startBuf = $start->copy()->subMinutes($bufferMinutes);
                $endBuf = $end->copy()->addMinutes($bufferMinutes);

                $isCurrent = $currentTime->between($startBuf, $endBuf, true);
                $isUpcoming = !$isCurrent && $currentTime->lt($start);

                $selectedMeta = [
                    'start' => $start,
                    'end' => $end,
                    'start_buf' => $startBuf,
                    'end_buf' => $endBuf,
                    'duration' => $durationMinutes,
                    'is_current' => $isCurrent,
                    'is_upcoming' => $isUpcoming,
                ];

                // Prioritas: current → upcoming terdekat → terakhir lewat
                if ($isCurrent) {
                    $selectedJadwal = $item;
                    break; // langsung pakai ini
                }
            }

            if (!$selectedJadwal) {
                // Cari upcoming terdekat
                $upcoming = $jadwalList->map(function ($item) use ($todayJakarta) {
                    $tpl = $item->shiftTemplate;
                    if (!$tpl) return null;
                    $start = Carbon::parse($todayJakarta->toDateString() . ' ' . ($tpl->jam_masuk_format ?? '08:00'), 'Asia/Jakarta');
                    $end = Carbon::parse($todayJakarta->toDateString() . ' ' . ($tpl->jam_pulang_format ?? '16:00'), 'Asia/Jakarta');
                    if ($end->lt($start)) { $end->addDay(); }
                    return [
                        'item' => $item,
                        'start' => $start,
                        'end' => $end,
                    ];
                })->filter()->filter(function ($row) use ($currentTime) {
                    return $currentTime->lt($row['start']);
                })->sortBy('start')->first();

                if ($upcoming) {
                    $selectedJadwal = $upcoming['item'];
                } else {
                    // Ambil shift terakhir yang sudah lewat (terdekat ke sekarang)
                    $past = $jadwalList->map(function ($item) use ($todayJakarta) {
                        $tpl = $item->shiftTemplate;
                        if (!$tpl) return null;
                        $start = Carbon::parse($todayJakarta->toDateString() . ' ' . ($tpl->jam_masuk_format ?? '08:00'), 'Asia/Jakarta');
                        return [ 'item' => $item, 'start' => $start ];
                    })->filter()->sortByDesc('start')->first();
                    $selectedJadwal = $past ? $past['item'] : $jadwalList->first();
                }
            }

            // Jika tetap tidak ada jadwal valid yang memiliki shift template, kembalikan 422 dengan debug info
            if (!$selectedJadwal || !$selectedJadwal->shiftTemplate) {
                $debugSchedules = $jadwalList->map(function ($item) use ($todayJakarta) {
                    $tpl = $item->shiftTemplate;
                    return [
                        'id' => $item->id,
                        'tanggal_jaga' => optional($item->tanggal_jaga)->format('Y-m-d'),
                        'shift_template' => $tpl ? [
                            'id' => $tpl->id,
                            'nama_shift' => $tpl->nama_shift,
                            'jam_masuk' => $tpl->jam_masuk,
                            'jam_pulang' => $tpl->jam_pulang,
                        ] : null,
                        'unit_kerja' => $item->unit_kerja,
                        'status_jaga' => $item->status_jaga,
                    ];
                });

                        return response()->json([
                            'success' => false,
                    'message' => 'Tidak ada shift valid untuk check-in hari ini',
                    'code' => 'NO_VALID_SHIFT_TODAY',
                    'data' => [
                                'current_time' => $currentTime->toISOString(),
                        'schedules' => $debugSchedules,
                            ]
                        ], 422);
                    }

            // VALIDASI MENGGUNAKAN AttendanceValidationService (hormati toleransi Work Location untuk early check-in/late check-out)
            $latitude = (float) $request->input('latitude');
            $longitude = (float) $request->input('longitude');
            $location = $request->input('location_name') ?? $request->input('location');
            $accuracy = $request->input('accuracy');

            if (!is_numeric($latitude) || !is_numeric($longitude)) {
                        return response()->json([
                            'success' => false,
                    'message' => 'Koordinat tidak valid',
                    'code' => 'INVALID_COORDINATES'
                        ], 422);
                    }

            /** @var \App\Services\AttendanceValidationService $validationService */
            $validationService = app(\App\Services\AttendanceValidationService::class);
            // 1) Validate work location (with override support)
            $locationValidation = $validationService->validateWorkLocationWithOverride($user, $latitude, $longitude, $accuracy);
            if (!$locationValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $locationValidation['message'],
                    'code' => $locationValidation['code'] ?? 'LOCATION_INVALID',
                    'data' => $locationValidation['data'] ?? null,
                ], 422);
            }

            // 2) Validate shift time against the SELECTED JADWAL (not the first of the day)
            $jadwalJaga = $selectedJadwal;
            $timeValidation = $validationService->validateShiftTime($jadwalJaga);
            if (!$timeValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $timeValidation['message'],
                    'code' => $timeValidation['code'] ?? 'TIME_INVALID',
                    'data' => $timeValidation['data'] ?? null,
                ], 422);
            }

            // 3) Validate shift-location compatibility
            $workLocation = $locationValidation['work_location'] ?? $locationValidation['location'] ?? null;
            if ($workLocation) {
                $compatValidation = $validationService->validateShiftLocationCompatibility($jadwalJaga, $workLocation);
                if (!$compatValidation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => $compatValidation['message'],
                        'code' => $compatValidation['code'] ?? 'COMPATIBILITY_INVALID',
                        'data' => $compatValidation['data'] ?? null,
                    ], 422);
                }
            }

            // Multiple shifts per day support
            // 1) Block if there is an open attendance (checked-in but not checked-out) for ANY recent day
            // Check for unclosed attendance from today OR previous days (up to 7 days back)
            $openAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', '>=', Carbon::now()->subDays(7)->startOfDay())
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->orderByDesc('date')
                ->orderByDesc('time_in')
                ->first();

            if ($openAttendance) {
                // Format date for better user message
                $attendanceDate = Carbon::parse($openAttendance->date);
                $dateStr = $attendanceDate->isToday() ? 'hari ini' : 
                          ($attendanceDate->isYesterday() ? 'kemarin' : 
                           $attendanceDate->format('d M Y'));
                
                return response()->json([
                    'success' => false,
                    'message' => "Masih ada presensi yang belum check-out dari $dateStr. Silakan check-out terlebih dahulu atau hubungi admin.",
                    'code' => 'OPEN_ATTENDANCE_EXISTS',
                    'data' => [
                        'open_attendance' => [
                            'date' => $openAttendance->date->format('Y-m-d'),
                            'time_in' => $openAttendance->time_in?->format('H:i'),
                            'jadwal_jaga_id' => $openAttendance->jadwal_jaga_id,
                        ]
                    ]
                ], 422);
            }

            // 2) Prevent duplicate check-in for the same shift (jadwal_jaga) on the same day
            $attendanceForShift = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->where('jadwal_jaga_id', $jadwalJaga->id)
                ->orderByDesc('time_in')
                ->first();

            if ($attendanceForShift && $attendanceForShift->time_in && !$attendanceForShift->time_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah check-in untuk shift ini dan belum check-out',
                    'code' => 'ALREADY_CHECKED_IN_OPEN',
                ], 422);
            }

            // Buat record attendance dengan jadwal jaga ID
            
            // Format latlon_in as "latitude,longitude"
            $latlonIn = null;
            if ($latitude && $longitude) {
                $latlonIn = $latitude . ',' . $longitude;
            }
            
            // 3) Create a new attendance row for this shift (allow multiple rows per day for multiple shifts)
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'time_in' => $currentTime,
                'latlon_in' => $latlonIn,
                'location_name_in' => $location,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
                'jadwal_jaga_id' => $jadwalJaga->id, // Link ke jadwal jaga
                'status' => ($validation['code'] ?? '') === 'VALID_BUT_LATE' ? 'late' : 'present',
                'work_location_id' => $workLocation->id ?? null,
            ]);

            $shiftTemplate = $jadwalJaga->shiftTemplate; // ensure available for response payload
            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil',
                'data' => [
                    'attendance' => $attendance,
                    'schedule' => [
                        'id' => $jadwalJaga->id,
                        'shift_name' => $shiftTemplate ? $shiftTemplate->nama_shift : 'Shift',
                        'start_time' => $shiftTemplate ? $shiftTemplate->jam_masuk : '08:00',
                        'end_time' => $shiftTemplate ? $shiftTemplate->jam_pulang : '16:00',
                        'unit_kerja' => $jadwalJaga->unit_kerja
                    ]
                ]
            ]);

            // Ensure schedule caches are fresh for realtime UI updates
            try {
                $month = Carbon::now()->month;
                $year = Carbon::now()->year;
                $cacheKey = "jadwal_jaga_{$user->id}_{$month}_{$year}";
                Cache::forget($cacheKey);
                Cache::forget("dokter_dashboard_stats_{$user->id}");
            } catch (\Throwable $t) {
                // no-op on cache clear failure
            }

        } catch (\Exception $e) {
            \Log::error('Check-in error for user ' . $user->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            $today = Carbon::today('Asia/Jakarta');
            $currentTime = Carbon::now('Asia/Jakarta');
            
            // Cari attendance yang masih "open" (sudah check-in, belum check-out)
            // Prioritas: hari ini dulu, kalau tidak ada cek kemarin atau hari sebelumnya (max 7 hari)
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', '>=', Carbon::now()->subDays(7)->startOfDay())
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->orderByDesc('date')
                ->orderByDesc('time_in')
                ->with('jadwalJaga.shiftTemplate')
                ->first();

            if (!$attendance) {
                // Fallback diagnosa: apakah sudah check-out atau memang belum check-in
                $attendanceToday = Attendance::where('user_id', $user->id)
                    ->whereDate('date', $today)
                    ->orderByDesc('time_in')
                    ->first();
                if ($attendanceToday && $attendanceToday->time_in && $attendanceToday->time_out) {
                return response()->json([
                    'success' => false,
                        'message' => 'Anda sudah melakukan check-out hari ini.',
                        'code' => 'ALREADY_CHECKED_OUT',
                        'data' => [
                            'time_in' => $attendanceToday->time_in?->format('H:i'),
                            'time_out' => $attendanceToday->time_out?->format('H:i'),
                        ]
                    ], 422);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan check-in hari ini.',
                    'code' => 'NOT_CHECKED_IN'
                ], 422);
            }

            // VALIDASI CHECKOUT
            $latitudeInput = $request->input('latitude');
            $longitudeInput = $request->input('longitude');
            $accuracy = $request->input('accuracy');
            $hasCoords = is_numeric($latitudeInput) && is_numeric($longitudeInput);

            if ($hasCoords) {
                $latitude = (float) $latitudeInput;
                $longitude = (float) $longitudeInput;
                /** @var \App\Services\AttendanceValidationService $validationService */
                $validationService = app(\App\Services\AttendanceValidationService::class);
                $validation = $validationService->validateCheckout($user, $latitude, $longitude, $accuracy, $today);
                if (!$validation['valid']) {
                            return response()->json([
                                'success' => false,
                        'message' => $validation['message'],
                        'code' => $validation['code'],
                        'data' => $validation['data'] ?? null,
                            ], 422);
                        }

                $attendance->update([
                    'time_out' => $currentTime,
                    'checkout_latitude' => $latitude,
                    'checkout_longitude' => $longitude,
                    'checkout_accuracy' => $accuracy,
                    'latlon_out' => $latitude . ',' . $longitude,
                    'location_name_out' => ($validation['work_location']->name ?? 'Location'),
                ]);
                    } else {
                // Graceful fallback: allow checkout without GPS coordinates, but validate time window
                $shiftTemplate = optional($attendance->jadwalJaga)->shiftTemplate;
                if ($shiftTemplate) {
                    $jamKeluar = $shiftTemplate->jam_pulang ?? $shiftTemplate->jam_keluar ?? null;
                    if ($jamKeluar) {
                        try {
                            $shiftEnd = strlen((string) $jamKeluar) > 5
                                ? Carbon::parse($jamKeluar)->setTimezone('Asia/Jakarta')
                                : Carbon::createFromFormat('H:i', $jamKeluar, 'Asia/Jakarta');
                        } catch (\Exception $e) {
                            $shiftEnd = Carbon::parse($jamKeluar)->setTimezone('Asia/Jakarta');
                        }
                        $currentTimeOnly = Carbon::createFromFormat('H:i:s', $currentTime->copy()->setTimezone('Asia/Jakarta')->format('H:i:s'), 'Asia/Jakarta');
                        $workLocation = $user->workLocation;
                        $earlyDepartureToleranceMinutes = $workLocation->early_departure_tolerance_minutes ?? 15;
                        $checkoutAfterShiftMinutes = $workLocation->checkout_after_shift_minutes ?? 60;
                        $checkoutEarliestTime = $shiftEnd->copy()->subMinutes($earlyDepartureToleranceMinutes);
                        $checkoutLatestTime = $shiftEnd->copy()->addMinutes($checkoutAfterShiftMinutes);
                        if ($currentTimeOnly->lt($checkoutEarliestTime)) {
                            $earlyMinutes = $checkoutEarliestTime->diffInMinutes($currentTimeOnly);
                            return response()->json([
                                'success' => false,
                                'message' => "Check-out terlalu awal. Anda dapat check-out mulai pukul {$checkoutEarliestTime->format('H:i')} ({$earlyMinutes} menit lagi).",
                                'code' => 'CHECKOUT_TOO_EARLY',
                            ], 422);
                        }
                        // Too late: still allow
                    }
                }
            $attendance->update([
                'time_out' => $currentTime,
                    // leave checkout coordinates null
                ]);
            }

            // Determine next shift today for READY_TO_CHECK_IN state
            $state = 'ALL_DONE';
            $nextShiftPayload = null;
            try {
                $todayDate = $today->toDateString();
                $jadwalHariIni = \App\Models\JadwalJaga::where('pegawai_id', $user->id)
                    ->whereDate('tanggal_jaga', $todayDate)
                    ->with('shiftTemplate')
                    ->get()
                    ->filter(fn($j) => $j->shiftTemplate);

                if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
                    $currentStartStr = $attendance->jadwalJaga->shiftTemplate->jam_masuk ?? '00:00';
                    $currentStart = \Carbon\Carbon::parse($todayDate . ' ' . $currentStartStr, 'Asia/Jakarta');
                    $next = $jadwalHariIni->filter(function ($j) use ($todayDate, $currentStart) {
                        $tpl = $j->shiftTemplate;
                        $start = \Carbon\Carbon::parse($todayDate . ' ' . ($tpl->jam_masuk ?? '00:00'), 'Asia/Jakarta');
                        return $start->gt($currentStart);
                    })->sortBy(function ($j) use ($todayDate) {
                        return \Carbon\Carbon::parse($todayDate . ' ' . ($j->shiftTemplate->jam_masuk ?? '00:00'), 'Asia/Jakarta')->timestamp;
                    })->first();

                    if ($next) {
                        // Compute check_in_available_at using work location tolerance
                        $workLocation = $user->workLocation;
                        $beforeMinutes = $workLocation?->checkin_before_shift_minutes;
                        if ($beforeMinutes === null && is_array($workLocation?->tolerance_settings)) {
                            $beforeMinutes = (int) ($workLocation->tolerance_settings['checkin_before_shift_minutes'] ?? 0);
                        }
                        $beforeMinutes = $beforeMinutes ?? 30;
                        $nextStart = \Carbon\Carbon::parse($todayDate . ' ' . ($next->shiftTemplate->jam_masuk ?? '00:00'), 'Asia/Jakarta');
                        $checkInAvailableAt = $nextStart->copy()->subMinutes($beforeMinutes)->toIso8601String();
                        $state = 'READY_TO_CHECK_IN';
                        $nextShiftPayload = [
                            'id' => $next->id,
                            'shift_name' => $next->shiftTemplate->nama_shift ?? 'Shift',
                            'start_time' => $next->shiftTemplate->jam_masuk ?? '00:00',
                            'end_time' => $next->shiftTemplate->jam_pulang ?? '00:00',
                            'check_in_available_at' => $checkInAvailableAt,
                        ];
                    }
                }
            } catch (\Throwable $t) {
                // ignore next-shift computation errors
            }

            return response()->json([
                'success' => true,
                'message' => 'Check-out berhasil',
                'state' => $state,
                'next_shift' => $nextShiftPayload,
                'data' => [
                    'attendance' => $attendance,
                    'schedule' => $attendance->jadwalJaga ? [
                        'id' => $attendance->jadwalJaga->id,
                        'shift_name' => $shiftTemplate ? $shiftTemplate->nama_shift : 'Shift',
                        'start_time' => $shiftTemplate ? $shiftTemplate->jam_masuk : '08:00',
                        'end_time' => $shiftTemplate ? $shiftTemplate->jam_pulang : '16:00',
                        'unit_kerja' => $attendance->jadwalJaga->unit_kerja
                    ] : null
                ]
            ]);

            // Ensure schedule caches are fresh for realtime UI updates
            try {
                $month = Carbon::now()->month;
                $year = Carbon::now()->year;
                $cacheKey = "jadwal_jaga_{$user->id}_{$month}_{$year}";
                Cache::forget($cacheKey);
                Cache::forget("dokter_dashboard_stats_{$user->id}");
            } catch (\Throwable $t) {
                // no-op on cache clear failure
            }

        } catch (\Exception $e) {
            \Log::error('Check-out error for user ' . $user->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
     * Get next schedule with proper relationship data
     */
    private function getNextSchedule($user)
    {
        try {
            $nextSchedule = JadwalJaga::where('pegawai_id', $user->id)
                ->where('tanggal_jaga', '>=', Carbon::today())
                ->with(['shiftTemplate'])
                ->orderBy('tanggal_jaga')
                ->first();

            if (!$nextSchedule) {
                return null;
            }

            $shiftTemplate = $nextSchedule->shiftTemplate;

            return [
                'id' => $nextSchedule->id,
                'date' => $nextSchedule->tanggal_jaga->format('Y-m-d'),
                'formatted_date' => $nextSchedule->tanggal_jaga->format('l, d F Y'),
                'shift_name' => $shiftTemplate ? $shiftTemplate->nama_shift : 'Shift',
                'start_time' => $shiftTemplate ? $shiftTemplate->jam_masuk : '08:00',
                'end_time' => $shiftTemplate ? $shiftTemplate->jam_pulang : '16:00',
                'durasi_jam' => $shiftTemplate ? $shiftTemplate->durasi_jam : 8,
                'warna' => $shiftTemplate ? $shiftTemplate->warna : '#3b82f6',
                'unit_kerja' => $nextSchedule->unit_kerja ?? 'Unit Kerja',
                'peran' => $nextSchedule->peran ?? 'Dokter Jaga',
                'status_jaga' => $nextSchedule->status_jaga ?? 'aktif',
                'keterangan' => $nextSchedule->keterangan,
                'days_until' => Carbon::today()->diffInDays($nextSchedule->tanggal_jaga)
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting next schedule', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
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
            
            // For dokter IGD schedules, follow category filter
            // Already handled by unitKerjaMap above
            
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
                // NOTE: unit_kerja filter removed to show ALL schedules (Dokter Jaga, Pelayanan, Pendaftaran)
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

    /**
     * Force refresh work location data by clearing relevant caches
     */
    public function refreshWorkLocation(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Clear relevant caches
            $cacheKeys = [
                "dokter_dashboard_stats_{$user->id}",
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
     * Get work location status only (lightweight endpoint for polling)
     */
    public function getWorkLocationStatus(Request $request)
    {
        try {
            $user = Auth::user();
            // Fallback: support Bearer token (Sanctum) when no web session is present
            if (!$user) {
                $bearer = $request->bearerToken();
                if ($bearer) {
                    $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($bearer);
                    if ($pat) {
                        $user = $pat->tokenable;
                    }
                }
            }
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get fresh work location data
            $user->load('workLocation');
            $workLocation = $user->workLocation?->fresh();

            // Prepare tolerance fields with fallback to JSON tolerance_settings if individual columns are null
            $lateTol = $workLocation?->late_tolerance_minutes;
            $earlyDepTol = $workLocation?->early_departure_tolerance_minutes;
            $beforeShift = $workLocation?->checkin_before_shift_minutes;
            $afterShift = $workLocation?->checkout_after_shift_minutes;
            $tolJson = is_array($workLocation?->tolerance_settings) ? $workLocation->tolerance_settings : [];
            if ($lateTol === null && isset($tolJson['late_tolerance_minutes'])) { $lateTol = (int) $tolJson['late_tolerance_minutes']; }
            if ($earlyDepTol === null && isset($tolJson['early_departure_tolerance_minutes'])) { $earlyDepTol = (int) $tolJson['early_departure_tolerance_minutes']; }
            if ($beforeShift === null && isset($tolJson['checkin_before_shift_minutes'])) { $beforeShift = (int) $tolJson['checkin_before_shift_minutes']; }
            if ($afterShift === null && isset($tolJson['checkout_after_shift_minutes'])) { $afterShift = (int) $tolJson['checkout_after_shift_minutes']; }

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
                        // expose tolerance configuration for frontend hints (with fallback JSON)
                        'late_tolerance_minutes' => $lateTol,
                        'checkin_before_shift_minutes' => $beforeShift,
                        'early_departure_tolerance_minutes' => $earlyDepTol,
                        'checkout_after_shift_minutes' => $afterShift,
                        'strict_geofence' => $workLocation->strict_geofence,
                    ] : null,
                    'user_id' => $user->id,
                    'timestamp' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching work location status', [
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
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|max:20',
                'address' => 'sometimes|string|max:500',
                'date_of_birth' => 'sometimes|date',
                'gender' => 'sometimes|in:male,female',
                'bio' => 'sometimes|string|max:1000',
                'nik' => 'sometimes|string|max:255',
                'nomor_sip' => 'sometimes|string|max:255',
                'jabatan' => 'sometimes|string|max:255',
                'spesialisasi' => 'sometimes|string|max:255'
            ]);

            // Update user profile
            $user->update($validated);

            // Update dokter profile if user is a dokter
            $dokter = Dokter::where('user_id', $user->id)->first();
            if ($dokter) {
                $dokterData = [];
                
                if (isset($validated['name'])) {
                    $dokterData['nama_lengkap'] = $validated['name'];
                }
                
                if (isset($validated['email'])) {
                    $dokterData['email'] = $validated['email'];
                }
                
                if (isset($validated['phone'])) {
                    $dokterData['no_telepon'] = $validated['phone'];
                }
                
                if (isset($validated['address'])) {
                    $dokterData['alamat'] = $validated['address'];
                }
                
                if (isset($validated['date_of_birth'])) {
                    $dokterData['tanggal_lahir'] = $validated['date_of_birth'];
                }
                
                if (isset($validated['gender'])) {
                    // Convert gender format from male/female to Laki-laki/Perempuan for dokter table
                    $dokterData['jenis_kelamin'] = $validated['gender'] === 'male' ? 'Laki-laki' : 'Perempuan';
                }
                
                if (isset($validated['bio'])) {
                    $dokterData['keterangan'] = $validated['bio'];
                }
                
                if (isset($validated['nik'])) {
                    $dokterData['nik'] = $validated['nik'];
                }
                
                if (isset($validated['nomor_sip'])) {
                    $dokterData['nomor_sip'] = $validated['nomor_sip'];
                }
                
                if (isset($validated['jabatan'])) {
                    $dokterData['jabatan'] = $validated['jabatan'];
                }
                
                if (isset($validated['spesialisasi'])) {
                    $dokterData['spesialisasi'] = $validated['spesialisasi'];
                }
                
                if (!empty($dokterData)) {
                    $dokter->update($dokterData);
                }
            }

            // Refresh user and dokter data
            $user->refresh();
            $dokter = $dokter ? $dokter->fresh() : null;

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $dokter ? $dokter->nama_lengkap : $user->name,
                        'email' => $dokter ? $dokter->email : $user->email,
                        'phone' => $dokter ? $dokter->no_telepon : $user->phone,
                        'address' => $dokter ? $dokter->alamat : $user->address,
                        'date_of_birth' => $dokter ? $dokter->tanggal_lahir?->format('d F Y') : $user->date_of_birth?->format('d F Y'),
                        'gender' => $this->formatGender($dokter ? $dokter->jenis_kelamin : $user->gender),
                        'bio' => $dokter ? $dokter->keterangan : $user->bio,
                        'nik' => $dokter ? $dokter->nik : null,
                        'nomor_sip' => $dokter ? $dokter->nomor_sip : null,
                        'jabatan' => $dokter ? $dokter->jabatan : 'Dokter',
                        'spesialisasi' => $dokter ? $dokter->spesialisasi : null,
                        'tanggal_bergabung' => $dokter ? $dokter->tanggal_bergabung?->format('d F Y') : null,
                        'status_akun' => $dokter ? $dokter->status_akun : null
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format gender from database format to display format
     */
    private function formatGender($gender)
    {
        if (!$gender) {
            return 'Tidak ditentukan';
        }
        
        switch (strtolower($gender)) {
            case 'male':
                return 'Laki-laki';
            case 'female':
                return 'Perempuan';
            case 'laki-laki':
                return 'Laki-laki';
            case 'perempuan':
                return 'Perempuan';
            default:
                return $gender;
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
                $workLocation = \App\Models\WorkLocation::find($user->work_location_id);
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
            
            // Try to find a suitable work location based on user's dokter data
            $dokter = $user->dokter;
            if (!$dokter) {
                // Try pegawai relation if dokter not found
                $pegawai = $user->pegawai;
                if (!$pegawai) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No dokter or pegawai data found for user',
                        'data' => null
                    ], 404);
                }
                
                // Use pegawai data for matching
                $workLocation = \App\Models\WorkLocation::where('is_active', true)
                    ->where(function($query) use ($pegawai) {
                        if ($pegawai->unit_kerja) {
                            $query->where('name', 'LIKE', '%' . $pegawai->unit_kerja . '%')
                                  ->orWhere('unit_kerja', $pegawai->unit_kerja);
                        }
                        if ($pegawai->jenis_pegawai) {
                            $query->orWhere('location_type', $pegawai->jenis_pegawai);
                        }
                    })
                    ->first();
            } else {
                // Look for work location based on dokter's unit_kerja or specialization
                $workLocation = \App\Models\WorkLocation::where('is_active', true)
                    ->where(function($query) use ($dokter) {
                        // Try to match by unit kerja
                        if ($dokter->unit_kerja) {
                            $query->where('name', 'LIKE', '%' . $dokter->unit_kerja . '%')
                                  ->orWhere('unit_kerja', $dokter->unit_kerja);
                        }
                        // Try to match by location type
                        $query->orWhere('location_type', 'Dokter')
                              ->orWhere('location_type', 'dokter');
                    })
                    ->first();
            }
            
            // If no match found, get the first active work location (default)
            if (!$workLocation) {
                $workLocation = \App\Models\WorkLocation::where('is_active', true)
                    ->orderBy('created_at', 'asc')
                    ->first();
            }
            
            if ($workLocation) {
                // Assign work location to user
                $user->work_location_id = $workLocation->id;
                $user->save();
                
                // Clear caches
                $cacheKeys = [
                    "dokter_dashboard_stats_{$user->id}",
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
                        'assignment_reason' => $dokter && $dokter->unit_kerja ? 
                            "Matched by unit kerja: {$dokter->unit_kerja}" : 
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
     * Debug endpoint untuk memeriksa data jadwal jaga
     */
    public function debugSchedule(Request $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::today();
            
            // Debug: Cek data user
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : 'no role',
                'created_at' => $user->created_at
            ];
            
            // Debug: Cek jadwal jaga hari ini
            $jadwalJaga = JadwalJaga::where('pegawai_id', $user->id)
                ->whereDate('tanggal_jaga', $today)
                ->with(['shiftTemplate', 'pegawai'])
                ->get();
            
            $jadwalData = $jadwalJaga->map(function ($jadwal) {
                return [
                    'id' => $jadwal->id,
                    'tanggal_jaga' => $jadwal->tanggal_jaga->format('Y-m-d'),
                    'pegawai_id' => $jadwal->pegawai_id,
                    'shift_template_id' => $jadwal->shift_template_id,
                    'unit_kerja' => $jadwal->unit_kerja,
                    'unit_instalasi' => $jadwal->unit_instalasi,
                    'peran' => $jadwal->peran,
                    'status_jaga' => $jadwal->status_jaga,
                    'keterangan' => $jadwal->keterangan,
                    'shift_template' => $jadwal->shiftTemplate ? [
                        'id' => $jadwal->shiftTemplate->id,
                        'nama_shift' => $jadwal->shiftTemplate->nama_shift,
                        'jam_masuk' => $jadwal->shiftTemplate->jam_masuk,
                        'jam_pulang' => $jadwal->shiftTemplate->jam_pulang,
                        'durasi_jam' => $jadwal->shiftTemplate->durasi_jam
                    ] : null,
                    'pegawai' => $jadwal->pegawai ? [
                        'id' => $jadwal->pegawai->id,
                        'name' => $jadwal->pegawai->name,
                        'email' => $jadwal->pegawai->email
                    ] : null
                ];
            });
            
            // Debug: Cek jadwal jaga aktif
            $activeJadwal = JadwalJaga::where('pegawai_id', $user->id)
                ->whereDate('tanggal_jaga', $today)
                ->where('status_jaga', 'Aktif')
                ->with(['shiftTemplate'])
                ->first();
            
            $activeJadwalData = null;
            if ($activeJadwal) {
                $activeJadwalData = [
                    'id' => $activeJadwal->id,
                    'tanggal_jaga' => $activeJadwal->tanggal_jaga->format('Y-m-d'),
                    'status_jaga' => $activeJadwal->status_jaga,
                    'shift_template' => $activeJadwal->shiftTemplate ? [
                        'id' => $activeJadwal->shiftTemplate->id,
                        'nama_shift' => $activeJadwal->shiftTemplate->nama_shift,
                        'jam_masuk' => $activeJadwal->shiftTemplate->jam_masuk,
                        'jam_pulang' => $activeJadwal->shiftTemplate->jam_pulang
                    ] : null
                ];
            }
            
            // Debug: Cek attendance hari ini
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();
            
            $attendanceData = null;
            if ($attendance) {
                $attendanceData = [
                    'id' => $attendance->id,
                    'time_in' => $attendance->time_in?->format('H:i:s'),
                    'time_out' => $attendance->time_out?->format('H:i:s'),
                    'status' => $attendance->status,
                    'jadwal_jaga_id' => $attendance->jadwal_jaga_id
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Debug data berhasil dimuat',
                'data' => [
                    'user' => $userData,
                    'today' => $today->format('Y-m-d'),
                    'current_time' => Carbon::now()->format('Y-m-d H:i:s'),
                    'all_schedules_today' => $jadwalData,
                    'active_schedule_today' => $activeJadwalData,
                    'attendance_today' => $attendanceData,
                    'debug_info' => [
                        'total_schedules' => $jadwalJaga->count(),
                        'active_schedules' => $jadwalJaga->where('status_jaga', 'Aktif')->count(),
                        'has_active_schedule' => $activeJadwal ? true : false,
                        'has_attendance' => $attendance ? true : false
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Debug schedule error', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Debug error: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Test endpoint for authentication debugging
     */
    
    /**
     * Get server time for frontend validation
     */
    public function getServerTime(Request $request)
    {
        try {
            $currentTime = Carbon::now()->setTimezone('Asia/Jakarta');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current_time' => $currentTime->toISOString(),
                    'current_time_formatted' => $currentTime->format('H:i:s'),
                    'timezone' => $currentTime->timezone->getName(),
                    'date' => $currentTime->toDateString(),
                    'timestamp' => $currentTime->timestamp
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint for jadwal jaga without authentication
     */
    public function testJadwalJaga(Request $request)
    {
        try {
            $userId = $request->get('user_id', 13); // Default to user 13 who has jadwal jaga
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $today = Carbon::today();
            
            // Check if this is a refresh request
            $isRefresh = $request->has('refresh') || $request->header('Cache-Control') === 'no-cache';
            
            // Cache key for jadwal jaga with short TTL for quick refresh
            $cacheKey = "jadwal_jaga_test_{$userId}_{$month}_{$year}";
            $cacheTTL = $isRefresh ? 10 : 60; // 10 seconds for refresh, 60 seconds for normal
            
            // Clear cache if refresh requested
            if ($isRefresh) {
                Cache::forget($cacheKey);
                \Log::info("🔄 Cleared test jadwal jaga cache for user {$userId} due to refresh request");
            }
            
            // Use cache for jadwal jaga data
            $jadwalData = Cache::remember($cacheKey, $cacheTTL, function () use ($userId, $month, $year, $today, $cacheTTL, $isRefresh) {
                // Enhanced query with proper relationships
                $jadwalJaga = JadwalJaga::where('pegawai_id', $userId)
                    ->whereMonth('tanggal_jaga', $month)
                    ->whereYear('tanggal_jaga', $year)
                    ->with(['shiftTemplate', 'pegawai'])
                    ->orderBy('tanggal_jaga')
                    ->get();

                // Format untuk calendar dengan proper shift template data
                $calendarEvents = $jadwalJaga->map(function ($jadwal) {
                    $shiftTemplate = $jadwal->shiftTemplate;
                    $user = $jadwal->pegawai;
                    
                    return [
                        'id' => $jadwal->id,
                        'title' => $shiftTemplate ? $shiftTemplate->nama_shift : 'Shift Jaga',
                        'start' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'end' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'color' => $jadwal->color,
                        'description' => $jadwal->unit_kerja ?? 'Unit Kerja',
                        'shift_info' => [
                            'id' => $shiftTemplate ? $shiftTemplate->id : null,
                            'nama_shift' => $shiftTemplate ? $shiftTemplate->nama_shift : 'Shift',
                            'jam_masuk' => $shiftTemplate ? $shiftTemplate->jam_masuk_format : '08:00',
                            'jam_pulang' => $shiftTemplate ? $shiftTemplate->jam_pulang_format : '16:00',
                            'durasi_jam' => $shiftTemplate ? $shiftTemplate->durasi_jam : 8,
                            'warna' => $shiftTemplate ? $shiftTemplate->warna : '#3b82f6',
                            'unit_kerja' => $jadwal->unit_kerja ?? 'Unit Kerja',
                            'status' => $jadwal->status_jaga ?? 'aktif',
                            'peran' => $jadwal->peran ?? 'Dokter Jaga',
                            'employee_name' => $user ? $user->name : 'Unknown',
                            'keterangan' => $jadwal->keterangan
                        ]
                    ];
                });

                // Enhanced weekly schedule with full relationship data
                $weeklySchedule = JadwalJaga::where('pegawai_id', $userId)
                    ->whereBetween('tanggal_jaga', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])
                    ->with(['shiftTemplate', 'pegawai'])
                    ->orderBy('tanggal_jaga')
                    ->get()
                    ->map(function ($jadwal) {
                        $shiftTemplate = $jadwal->shiftTemplate;
                        $user = $jadwal->pegawai;
                        
                        return [
                            'id' => $jadwal->id,
                            'tanggal_jaga' => $jadwal->tanggal_jaga->format('Y-m-d'),
                            'tanggal_formatted' => $jadwal->tanggal_jaga->format('l, d F Y'),
                            'unit_kerja' => $jadwal->unit_kerja,
                            'peran' => $jadwal->peran,
                            'status_jaga' => $jadwal->status_jaga,
                            'keterangan' => $jadwal->keterangan,
                            'employee_name' => $user ? $user->name : 'Unknown',
                            'shift_template' => $shiftTemplate ? [
                                'id' => $shiftTemplate->id,
                                'nama_shift' => $shiftTemplate->nama_shift,
                                'jam_masuk' => $shiftTemplate->jam_masuk_format,
                                'jam_pulang' => $shiftTemplate->jam_pulang_format,
                                'durasi_jam' => $shiftTemplate->durasi_jam,
                                'warna' => $shiftTemplate->warna ?? '#3b82f6'
                            ] : null
                        ];
                    });

                // FIXED: Get today's schedule specifically for attendance validation
                $todaySchedule = JadwalJaga::where('pegawai_id', $userId)
                    ->whereDate('tanggal_jaga', $today)
                    ->with(['shiftTemplate', 'pegawai'])
                    ->get()
                    ->map(function ($jadwal) {
                        $shiftTemplate = $jadwal->shiftTemplate;
                        $user = $jadwal->pegawai;
                        
                        return [
                            'id' => $jadwal->id,
                            'tanggal_jaga' => $jadwal->tanggal_jaga->format('Y-m-d'),
                            'tanggal_formatted' => $jadwal->tanggal_jaga->format('d/m/Y'),
                            'pegawai_id' => $jadwal->pegawai_id,
                            'employee_name' => $user ? $user->name : 'Unknown',
                            'shift_template_id' => $jadwal->shift_template_id,
                            'unit_kerja' => $jadwal->unit_kerja,
                            'unit_instalasi' => $jadwal->unit_instalasi,
                            'peran' => $jadwal->peran,
                            'status_jaga' => $jadwal->status_jaga,
                            'keterangan' => $jadwal->keterangan,
                            'shift_template' => $shiftTemplate ? [
                                'id' => $shiftTemplate->id,
                                'nama_shift' => $shiftTemplate->nama_shift,
                                'jam_masuk' => $shiftTemplate->jam_masuk_format,
                                'jam_pulang' => $shiftTemplate->jam_pulang_format,
                                'durasi_jam' => $shiftTemplate->durasi_jam,
                                'warna' => $shiftTemplate->warna ?? '#3b82f6'
                            ] : null
                        ];
                    });

                // Get current active shift for today
                $currentShift = $todaySchedule->where('status_jaga', 'Aktif')->first();

                // ENHANCED: Calculate schedule card statistics
                $now = Carbon::now();
                $todayDate = $now->toDateString();
                $currentTime = $now->format('H:i:s');
                
                // All schedules for the requested month/year with shift templates
                $allSchedules = JadwalJaga::where('pegawai_id', $userId)
                    ->whereMonth('tanggal_jaga', $month)
                    ->whereYear('tanggal_jaga', $year)
                    ->with(['shiftTemplate'])
                    ->get();
                
                // Completed shifts (past dates OR today's shifts that have ended)
                $completedShifts = $allSchedules->filter(function ($jadwal) use ($todayDate, $currentTime) {
                    $shiftDate = $jadwal->tanggal_jaga->format('Y-m-d');
                    
                    // Past dates are automatically completed
                    if ($shiftDate < $todayDate) {
                        return true;
                    }
                    
                    // For today's shifts, check if shift has ended based on shift template
                    if ($shiftDate === $todayDate && $jadwal->shiftTemplate && $jadwal->shiftTemplate->jam_pulang) {
                        $shiftEndTime = $jadwal->shiftTemplate->jam_pulang;
                        return $currentTime >= $shiftEndTime;
                    }
                    
                    return false;
                });
                
                // Upcoming shifts (future dates OR today's shifts that haven't started/ended)
                $upcomingShifts = $allSchedules->filter(function ($jadwal) use ($todayDate, $currentTime) {
                    $shiftDate = $jadwal->tanggal_jaga->format('Y-m-d');
                    
                    // Future dates are upcoming
                    if ($shiftDate > $todayDate) {
                        return true;
                    }
                    
                    // For today's shifts, check if shift hasn't ended yet
                    if ($shiftDate === $todayDate && $jadwal->shiftTemplate && $jadwal->shiftTemplate->jam_pulang) {
                        $shiftEndTime = $jadwal->shiftTemplate->jam_pulang;
                        return $currentTime < $shiftEndTime;
                    }
                    
                    return false;
                });
                
                // Calculate total hours from completed shifts (using actual attendance data)
                $totalHours = $completedShifts->sum(function ($jadwal) use ($user) {
                    // First, try to get actual hours from attendance records
                    $attendance = \App\Models\Attendance::where('user_id', $user->id)
                        ->whereDate('date', $jadwal->tanggal_jaga)
                        ->first();
                    
                    if ($attendance && $attendance->time_in && $attendance->time_out) {
                        // Use actual worked hours from attendance (check-out - check-in)
                        $timeIn = Carbon::parse($attendance->time_in);
                        $timeOut = Carbon::parse($attendance->time_out);
                        return $timeOut->diffInHours($timeIn);
                    }
                    
                    // If no attendance record, calculate from shift template
                    if ($jadwal->shiftTemplate && $jadwal->shiftTemplate->durasi_jam) {
                        return $jadwal->shiftTemplate->durasi_jam;
                    }
                    
                    // Fallback: calculate from jam_masuk and jam_pulang
                    if ($jadwal->shiftTemplate && $jadwal->shiftTemplate->jam_masuk && $jadwal->shiftTemplate->jam_pulang) {
                        $startTime = Carbon::parse($jadwal->shiftTemplate->jam_masuk);
                        $endTime = Carbon::parse($jadwal->shiftTemplate->jam_pulang);
                        
                        // Handle overnight shifts
                        if ($endTime->lt($startTime)) {
                            $endTime->addDay();
                        }
                        
                        return $startTime->diffInHours($endTime);
                    }
                    
                    // Don't assume default hours - return 0 if no data available
                    return 0;
                });

                // Schedule card statistics
                $scheduleStats = [
                    'completed' => $completedShifts->count(),
                    'upcoming' => $upcomingShifts->count(),
                    'total_hours' => $totalHours,
                    'total_shifts' => $allSchedules->count(),
                    'current_month' => $month,
                    'current_year' => $year,
                    'month_name' => Carbon::create($year, $month, 1)->format('F Y')
                ];

                return [
                    'calendar_events' => $calendarEvents,
                    'weekly_schedule' => $weeklySchedule,
                    'today' => $todaySchedule,
                    'currentShift' => $currentShift,
                    'schedule_stats' => $scheduleStats,
                    'month' => $month,
                    'year' => $year,
                    'cache_info' => [
                        'cached_at' => now()->toISOString(),
                        'cache_ttl' => $cacheTTL,
                        'is_refresh' => $isRefresh
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Test jadwal jaga berhasil dimuat',
                'data' => $jadwalData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in testJadwalJaga', [
                'user_id' => $request->get('user_id', 13),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat test jadwal jaga: ' . $e->getMessage()
            ], 500);
        }
    }
}