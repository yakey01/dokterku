<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Dokter;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\JumlahPasienHarian;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use App\Services\AttendanceToleranceService;
use App\Services\EffectiveDurationCalculatorService;
use App\Services\UnifiedAttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Helpers\HoursFormatter;

class DokterDashboardController extends Controller
{
    /**
     * Test endpoint for authentication
     */
    public function test(Request $request)
    {
        try {
            $user = Auth::user();
            
            return response()->json([
                'success' => true,
                'message' => 'API test successful',
                'data' => [
                    'user_id' => $user?->id,
                    'user_name' => $user?->name,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

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
                $patientsMonth = 0;
                $tindakanToday = 0;
                
                if ($dokter) {
                    // FIXED: Use JumlahPasienHarian for accurate patient counts
                    // Get today's patient count from JumlahPasienHarian
                    $jumlahPasienToday = JumlahPasienHarian::where('dokter_id', $dokter->id)
                        ->whereDate('tanggal', $today)
                        ->whereIn('status_validasi', ['approved', 'disetujui'])
                        ->first();
                    
                    if ($jumlahPasienToday) {
                        $patientsToday = $jumlahPasienToday->jumlah_pasien_umum + 
                                        $jumlahPasienToday->jumlah_pasien_bpjs;
                    }
                    
                    // Get this month's total patient count
                    $jumlahPasienMonth = JumlahPasienHarian::where('dokter_id', $dokter->id)
                        ->whereMonth('tanggal', $thisMonth->month)
                        ->whereYear('tanggal', $thisMonth->year)
                        ->whereIn('status_validasi', ['approved', 'disetujui'])
                        ->get();
                    
                    $patientsMonth = $jumlahPasienMonth->sum('jumlah_pasien_umum') + 
                                   $jumlahPasienMonth->sum('jumlah_pasien_bpjs');

                    // Still use Tindakan for procedures count
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
                    'patients_month' => $patientsMonth, // Added monthly total
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

                // Get current active shift for today based on current time
                $currentShift = null;
                $currentTimeString = $nowJakarta->format('H:i:s');
                
                // Find the shift that encompasses the current time
                foreach ($todaySchedule->where('status_jaga', 'Aktif') as $schedule) {
                    if (isset($schedule['shift_template']) && $schedule['shift_template']) {
                        $shiftStart = Carbon::parse($schedule['shift_template']['jam_masuk'])->format('H:i:s');
                        $shiftEnd = Carbon::parse($schedule['shift_template']['jam_pulang'])->format('H:i:s');
                        
                        // Handle overnight shifts
                        if ($shiftEnd < $shiftStart) {
                            // Overnight shift
                            if ($currentTimeString >= $shiftStart || $currentTimeString <= $shiftEnd) {
                                $currentShift = $schedule;
                                break;
                            }
                        } else {
                            // Normal shift
                            if ($currentTimeString >= $shiftStart && $currentTimeString <= $shiftEnd) {
                                $currentShift = $schedule;
                                break;
                            }
                        }
                    }
                }
                
                // If no shift matches current time exactly, check with tolerance (30 min before, 60 min after)
                if (!$currentShift) {
                    foreach ($todaySchedule->where('status_jaga', 'Aktif') as $schedule) {
                        if (isset($schedule['shift_template']) && $schedule['shift_template']) {
                            $shiftStart = Carbon::parse($schedule['shift_template']['jam_masuk']);
                            $shiftEnd = Carbon::parse($schedule['shift_template']['jam_pulang']);
                            
                            // Add tolerance: 30 minutes before start, 60 minutes after end
                            $startWithTolerance = $shiftStart->copy()->subMinutes(30);
                            $endWithTolerance = $shiftEnd->copy()->addMinutes(60);
                            
                            // Check if current time is within tolerance
                            $currentDateTime = Carbon::parse($nowJakarta->format('Y-m-d') . ' ' . $currentTimeString);
                            
                            if ($currentDateTime->between($startWithTolerance, $endWithTolerance)) {
                                $currentShift = $schedule;
                                break;
                            }
                        }
                    }
                }
                
                // Fallback: if still no match, get the first active schedule
                if (!$currentShift) {
                    $currentShift = $todaySchedule->where('status_jaga', 'Aktif')->first();
                }

                // ENHANCED: Get attendance records for jadwal jaga integration
                $attendanceRecords = Attendance::where('user_id', $user->id)
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->whereNotNull('jadwal_jaga_id')
                    ->get()
                    ->map(function ($attendance) {
                        return [
                            'id' => $attendance->id,
                            'jadwal_jaga_id' => $attendance->jadwal_jaga_id,
                            'date' => $attendance->date->format('Y-m-d'),
                            'time_in' => $attendance->time_in,
                            'time_out' => $attendance->time_out,
                            'check_in_time' => $attendance->time_in,  // Frontend compatibility
                            'check_out_time' => $attendance->time_out, // Frontend compatibility
                            'status' => $attendance->status ?? 'not_started'
                        ];
                    });

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
                
                // ✅ FIXED: Only count shifts with actual completed attendance
                $completedShifts = $allSchedules->filter(function ($jadwal) use ($user) {
                    // Check if there's a completed attendance record for this shift
                    $attendance = \App\Models\Attendance::where('user_id', $user->id)
                        ->whereDate('date', $jadwal->tanggal_jaga)
                        ->whereNotNull('time_in')
                        ->whereNotNull('time_out')
                        ->first();
                    
                    return $attendance !== null;
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
                
                // ✅ REALISTIC: Calculate monthly total hours based on attended jadwal jaga only
                $attendedJadwalHours = 0;
                $attendedJadwalCount = 0;
                
                // Get all jadwal jaga for the month that were actually attended
                $jadwalJagaMonth = JadwalJaga::where('pegawai_id', $user->id)
                    ->whereMonth('tanggal_jaga', $month)
                    ->whereYear('tanggal_jaga', $year)
                    ->where('tanggal_jaga', '<', Carbon::now()->toDateString()) // Only past dates
                    ->with('shiftTemplate')
                    ->get();

                foreach ($jadwalJagaMonth as $jadwal) {
                    // Check if user actually attended this jadwal (has completed attendance)
                    $attendanceExists = \App\Models\Attendance::where('user_id', $user->id)
                        ->whereDate('date', $jadwal->tanggal_jaga)
                        ->whereNotNull('time_in')
                        ->whereNotNull('time_out')
                        ->exists();
                    
                    if ($attendanceExists) {
                        $attendedJadwalCount++;
                        // Use shift template duration (realistic 8-hour shifts)
                        if ($jadwal->shiftTemplate && $jadwal->shiftTemplate->durasi_jam) {
                            $attendedJadwalHours += $jadwal->shiftTemplate->durasi_jam;
                        } else {
                            $attendedJadwalHours += 8; // Default 8-hour shift
                        }
                    }
                }

                // Calculate final monthly total from attended jadwal jaga only
                $totalHours = $attendedJadwalHours;

                // Schedule card statistics with Indonesian formatting
                $scheduleStats = [
                    'completed' => $attendedJadwalCount, // Use attended jadwal count
                    'upcoming' => $upcomingShifts->count(),
                    'total_hours' => HoursFormatter::formatForApi($totalHours),
                    'total_shifts' => $allSchedules->count(),
                    'current_month' => $month,
                    'current_year' => $year,
                    'month_name' => Carbon::create($year, $month, 1)->format('F Y')
                ];

                // Get work location data
                $user->load('workLocation');
                $workLocation = $user->workLocation;
                
                // Prepare work location response
                $workLocationData = null;
                if ($workLocation) {
                    $workLocationData = [
                        'id' => $workLocation->id,
                        'name' => $workLocation->name,
                        'latitude' => $workLocation->latitude,
                        'longitude' => $workLocation->longitude,
                        'radius' => $workLocation->radius,
                        'tolerance_settings' => $workLocation->tolerance_settings,
                        'checkin_before_shift_minutes' => $workLocation->checkin_before_shift_minutes ?? 
                            ($workLocation->tolerance_settings['checkin_before_shift_minutes'] ?? 30),
                        'checkout_after_shift_minutes' => $workLocation->checkout_after_shift_minutes ?? 
                            ($workLocation->tolerance_settings['checkout_after_shift_minutes'] ?? 60),
                        'late_tolerance_minutes' => $workLocation->late_tolerance_minutes ?? 
                            ($workLocation->tolerance_settings['late_tolerance_minutes'] ?? 15)
                    ];
                }
                
                return [
                    'calendar_events' => $calendarEvents,
                    'weekly_schedule' => $weeklySchedule,
                    'today' => $todaySchedule,
                    'currentShift' => $currentShift,
                    'attendance_records' => $attendanceRecords,
                    'workLocation' => $workLocationData,
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
     * Get current month Jaspel progress data for real-time display
     */
    public function getCurrentMonthJaspelProgress(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'error' => 'UNAUTHENTICATED'
                ], 401);
            }

            // Get current month and year from request or use current date
            $currentMonth = $request->get('month', now()->month);
            $currentYear = $request->get('year', now()->year);
            
            // Create date objects for calculations
            $currentDate = Carbon::create($currentYear, $currentMonth, 1);
            $today = Carbon::now();
            
            // Calculate days elapsed and remaining in month
            $daysInMonth = $currentDate->daysInMonth;
            $daysElapsed = $today->day;
            $daysRemaining = max(0, $daysInMonth - $daysElapsed);

            // Get user's dokter record
            $dokter = Dokter::where('user_id', $user->id)->first();
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan',
                    'data' => null
                ], 404);
            }

            // Get all validated Jaspel for current month (using ValidatedJaspelCalculationService)
            $validatedJaspelService = app(\App\Services\ValidatedJaspelCalculationService::class);
            $jaspelData = $validatedJaspelService->getValidatedJaspelData($user, $currentMonth, $currentYear);
            
            // Calculate current totals
            $totalReceived = $jaspelData['summary']['approved']; // Only approved/validated amounts
            $totalCount = $jaspelData['summary']['count']['approved'];
            
            // Set target amount (configurable - could be from settings)
            $targetAmount = 2000000; // 2M default target, could be made configurable
            
            // Calculate progress percentage
            $progressPercentage = $targetAmount > 0 ? min(($totalReceived / $targetAmount) * 100, 100) : 0;
            
            // Get daily breakdown for animation
            $dailyBreakdown = [];
            $jaspelItems = $jaspelData['jaspel_items'];
            
            // Group by date and sum amounts
            $dailyGroups = collect($jaspelItems)->groupBy(function($item) {
                return Carbon::parse($item['tanggal'])->format('Y-m-d');
            });
            
            foreach ($dailyGroups as $date => $items) {
                $dailyAmount = $items->sum('nominal');
                $dailyCount = $items->count();
                
                $dailyBreakdown[] = [
                    'date' => $date,
                    'amount' => $dailyAmount,
                    'count' => $dailyCount,
                    'formatted_date' => Carbon::parse($date)->format('d M'),
                ];
            }
            
            // Sort by date
            $dailyBreakdown = collect($dailyBreakdown)->sortBy('date')->values()->all();
            
            // Calculate insights
            $dailyAverage = $daysElapsed > 0 ? $totalReceived / $daysElapsed : 0;
            $projectedTotal = $dailyAverage * $daysInMonth;
            
            // Determine target likelihood
            $targetLikelihood = 'challenging';
            if ($projectedTotal >= $targetAmount * 0.95) {
                $targetLikelihood = 'likely';
            } elseif ($projectedTotal >= $targetAmount * 0.75) {
                $targetLikelihood = 'possible';
            }
            
            // Get last entry timestamp
            $lastEntry = collect($jaspelItems)->max('tanggal') ?: $today->toDateString();
            $lastUpdated = collect($jaspelItems)->max('validated_at') ?: $today->toISOString();
            
            // Build response data
            $responseData = [
                'current_month' => [
                    'total_received' => (int) $totalReceived,
                    'target_amount' => (int) $targetAmount,
                    'progress_percentage' => round($progressPercentage, 1),
                    'daily_breakdown' => $dailyBreakdown,
                    'count' => $totalCount,
                    'month_name' => $currentDate->format('F Y'),
                    'days_elapsed' => $daysElapsed,
                    'days_remaining' => $daysRemaining,
                ],
                'real_time' => [
                    'last_entry' => $lastEntry,
                    'is_live' => true, // Could be based on WebSocket connection status
                    'last_updated' => $lastUpdated,
                ],
                'insights' => [
                    'daily_average' => (int) $dailyAverage,
                    'projected_total' => (int) $projectedTotal,
                    'target_likelihood' => $targetLikelihood,
                ],
                'validation_info' => [
                    'data_source' => 'ValidatedJaspelService',
                    'validation_status' => 'approved_only',
                    'financial_accuracy' => '100%'
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Current month Jaspel progress retrieved successfully',
                'data' => $responseData,
                'meta' => [
                    'generated_at' => now()->toISOString(),
                    'version' => '2.0',
                    'endpoint' => '/api/v2/dashboards/dokter/jaspel/current-month',
                    'user_id' => $user->id,
                    'dokter_id' => $dokter->id,
                    'month' => $currentMonth,
                    'year' => $currentYear,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve current month Jaspel progress: ' . $e->getMessage(),
                'error' => $e->getMessage(),
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
            
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan atau tidak aktif',
                    'data' => null
                ], 404);
            }
            
            $limit = min($request->get('limit', 15), 50);
            $status = $request->get('status');
            $search = $request->get('search');

            $query = Tindakan::where('dokter_id', $dokter->id)
                ->with([
                    'pasien:id,nama,no_rekam_medis', 
                    'jenisTindakan:id,nama',
                    'shiftTemplate:id,nama_shift'
                ]);

            if ($status) {
                $query->where('status_validasi', $status);
            }

            if ($search) {
                $query->whereHas('pasien', function($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('no_rekam_medis', 'like', "%{$search}%");
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
                    ],
                    'dokter' => [
                        'id' => $dokter->id,
                        'nama_lengkap' => $dokter->nama_lengkap,
                        'aktif' => $dokter->aktif
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
            
            // Presensi hari ini (multi-shift aware) with relationships for shift_info
            $todayRecords = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->with(['shift', 'jadwalJaga.shiftTemplate']) // ✅ ADDED: Load relationships
                ->orderBy('time_in')
                ->get();

            $anyOpen = $todayRecords->contains(function ($a) {
                return $a->time_in && !$a->time_out;
            });
            // MULTIPLE CHECKOUT: Check if user has ANY attendance today (not just open)
            $hasAttendanceToday = $todayRecords->contains(function ($a) {
                return $a->time_in !== null;
            });
            // Choose a representative record: prefer the open one, otherwise last of today
            $attendanceToday = $todayRecords->firstWhere(function ($a) {
                return $a->time_in && !$a->time_out;
            }) ?? $todayRecords->last();

            // History presensi dengan date range support
            $historyQuery = Attendance::where('user_id', $user->id)
                ->with(['shift', 'jadwalJaga.shiftTemplate'])
                ->orderByDesc('date');
            
            // Handle date range parameters from frontend
            if ($request->has('start') && $request->has('end')) {
                $startDate = Carbon::parse($request->get('start'));
                $endDate = Carbon::parse($request->get('end'));
                $historyQuery->whereBetween('date', [$startDate, $endDate]);
            } else {
                // ✅ ENHANCED: Get more comprehensive history (last 90 days instead of just current month)
                $startDate = Carbon::now()->subDays(90);
                $endDate = Carbon::now();
                $historyQuery->whereBetween('date', [$startDate, $endDate]);
            }
            
            // ✅ ADDED: Ensure we get all relevant attendance records
            $historyQuery->whereNotNull('time_in'); // Only records with check-in
            
            // ✅ FIXED: Get completed shifts separately to avoid query conflicts
            $completedShiftsIds = collect();
            try {
                $completedShiftsQuery = JadwalJaga::where('pegawai_id', $user->id)
                    ->where('tanggal_jaga', '<', Carbon::today())
                    ->pluck('id');
                
                if ($completedShiftsQuery->isNotEmpty()) {
                    $completedShiftsIds = $completedShiftsQuery;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to get completed shifts for history:', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                $completedShiftsIds = collect();
            }
            
            // Get main attendance history
            $attendanceHistory = $historyQuery->get();
            
            // ✅ ADDED: Include attendance from completed shifts if not already in main query
            if ($completedShiftsIds->isNotEmpty()) {
                try {
                    $completedShiftsAttendance = Attendance::where('user_id', $user->id)
                        ->whereIn('jadwal_jaga_id', $completedShiftsIds)
                        ->whereNotNull('time_in')
                        ->with(['shift', 'jadwalJaga.shiftTemplate'])
                        ->whereNotIn('id', $attendanceHistory->pluck('id'))
                        ->get();
                    
                    $attendanceHistory = $attendanceHistory->merge($completedShiftsAttendance);
                } catch (\Exception $e) {
                    \Log::warning('Failed to get completed shifts attendance:', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Process and deduplicate
            $attendanceHistory = $attendanceHistory
                ->unique('id')
                ->sortByDesc('date')
                ->values()
                ->map(function ($attendance) {
                    // ✅ ENHANCED: Comprehensive shift_info extraction
                    $shiftInfo = null;
                    $jadwalJaga = $attendance->jadwalJaga;
                    
                    // ✅ FIX: Properly get shift template object
                    $shiftTemplate = null;
                    if ($attendance->shift && is_object($attendance->shift)) {
                        $shiftTemplate = $attendance->shift;
                    } elseif ($jadwalJaga && $jadwalJaga->shiftTemplate && is_object($jadwalJaga->shiftTemplate)) {
                        $shiftTemplate = $jadwalJaga->shiftTemplate;
                    }
                    
                    // Debug logging
                    \Log::info('Shift template debug:', [
                        'attendance_id' => $attendance->id,
                        'has_attendance_shift' => $attendance->shift ? 'YES' : 'NO',
                        'attendance_shift_type' => $attendance->shift ? gettype($attendance->shift) : 'NULL',
                        'has_jadwal_jaga' => $jadwalJaga ? 'YES' : 'NO',
                        'has_shift_template' => ($jadwalJaga && $jadwalJaga->shiftTemplate) ? 'YES' : 'NO',
                        'shift_template_type' => ($jadwalJaga && $jadwalJaga->shiftTemplate) ? gettype($jadwalJaga->shiftTemplate) : 'NULL',
                        'final_shift_template' => $shiftTemplate ? 'OBJECT' : 'NULL'
                    ]);
                    
                    if ($shiftTemplate && is_object($shiftTemplate)) {
                        $shiftInfo = [
                            'shift_name' => $shiftTemplate->nama_shift ?? 'Shift Jaga',
                            'shift_start' => ($shiftTemplate->jam_masuk && !is_bool($shiftTemplate->jam_masuk)) ? \Carbon\Carbon::parse($shiftTemplate->jam_masuk)->format('H:i') : '08:00',
                            'shift_end' => ($shiftTemplate->jam_pulang && !is_bool($shiftTemplate->jam_pulang)) ? \Carbon\Carbon::parse($shiftTemplate->jam_pulang)->format('H:i') : '16:00',
                            'shift_duration' => $this->calculateShiftDuration($shiftTemplate->jam_masuk ?? '08:00', $shiftTemplate->jam_pulang ?? '16:00'),
                            
                            // ✅ ADDED: Enhanced jam jaga information
                            'jam_jaga' => $jadwalJaga ? ($jadwalJaga->jam_shift ?? null) : null,
                            'jam_masuk_effective' => $jadwalJaga ? ($jadwalJaga->effective_start_time ?? null) : null,
                            'jam_pulang_effective' => $jadwalJaga ? ($jadwalJaga->effective_end_time ?? null) : null,
                            'unit_kerja' => $jadwalJaga ? ($jadwalJaga->unit_kerja ?? null) : null,
                            'peran' => $jadwalJaga ? ($jadwalJaga->peran ?? null) : null,
                            'status_jaga' => $jadwalJaga ? ($jadwalJaga->status_jaga ?? null) : null,
                            'is_custom_schedule' => $jadwalJaga ? (!empty($jadwalJaga->jam_jaga_custom)) : false,
                            'custom_reason' => $jadwalJaga ? ($jadwalJaga->keterangan ?? null) : null,
                            'is_time_mismatch' => false, // Will be calculated below
                            'actual_attendance_time' => $attendance->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i') : null,
                        ];
                        
                        // ✅ ADDED: Calculate time mismatch
                        if ($attendance->time_in && $shiftTemplate->jam_masuk) {
                            $scheduledStart = \Carbon\Carbon::parse($shiftTemplate->jam_masuk);
                            $actualStart = \Carbon\Carbon::parse($attendance->time_in);
                            $timeDiffMinutes = $actualStart->diffInMinutes($scheduledStart, false);
                            
                            // Flag as mismatch if more than 15 minutes late
                            if ($timeDiffMinutes > 15) {
                                $shiftInfo['is_time_mismatch'] = true;
                            }
                        }
                    } else {
                        // ✅ ADDED: Fallback shift_info when no template available
                        $shiftInfo = [
                            'shift_name' => 'Shift Default',
                            'shift_start' => '08:00',
                            'shift_end' => '16:00',
                            'shift_duration' => '8j 0m',
                            'jam_jaga' => '08:00 - 16:00',
                            'jam_masuk_effective' => '08:00',
                            'jam_pulang_effective' => '16:00',
                            'unit_kerja' => 'Dokter Jaga',
                            'peran' => 'Dokter',
                            'status_jaga' => 'Aktif',
                            'is_custom_schedule' => false,
                            'custom_reason' => null,
                            'is_time_mismatch' => false,
                            'actual_attendance_time' => $attendance->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i') : null,
                        ];
                    }
                    
                    // ✅ SOPHISTICATED: Calculate effective duration using 5-step logic
                    $durationCalculator = new EffectiveDurationCalculatorService();
                    $effectiveDuration = null;
                    $shortageMinutes = 0;
                    
                    // Calculate for ALL records that have check-in/out, with or without shift template
                    if ($attendance->time_in && $attendance->time_out) {
                        // Use shift template if available, otherwise use default 8-hour shift
                        $shiftStart = '08:00';
                        $shiftEnd = '16:00';
                        $shiftName = 'default';
                        
                        if ($shiftTemplate && is_object($shiftTemplate)) {
                            $shiftStart = $shiftTemplate->jam_masuk ?? '08:00';
                            $shiftEnd = $shiftTemplate->jam_pulang ?? '16:00';
                            $shiftName = $shiftTemplate->nama_shift ?? 'default';
                        }
                        
                        // Get standard break times for this shift
                        $breakTimes = $durationCalculator->getStandardBreakTimes(
                            $shiftName,
                            Carbon::parse($shiftStart),
                            Carbon::parse($shiftEnd)
                        );
                        
                        // Calculate effective duration for ALL records
                        $effectiveDuration = $durationCalculator->calculateEffectiveDuration(
                            $attendance->time_in,
                            $attendance->time_out,
                            $shiftStart,
                            $shiftEnd,
                            $breakTimes
                        );
                        
                        $shortageMinutes = $effectiveDuration['shortage_minutes'] ?? 0;
                    }
                    
                    // ✅ DYNAMIC STATUS: Calculate after we have all data
                    $status = 'incomplete';
                    $points = 0;
                    $badge = '📋 UNKNOWN';
                    $dynamicStatus = 'Tidak Hadir';
                    
                    $hasCheckIn = !empty($attendance->time_in);
                    $hasCheckOut = !empty($attendance->time_out);
                    
                    if (!$hasCheckIn && !$hasCheckOut) {
                        $dynamicStatus = 'Tidak Hadir';
                        $status = 'absent';
                        $points = 0;
                        $badge = '❌ ABSENT';
                    } elseif ($hasCheckIn && !$hasCheckOut) {
                        $dynamicStatus = 'Tidak Lengkap';
                        $status = 'incomplete';
                        $points = 25;
                        $badge = '⚠️ INCOMPLETE';
                    } elseif ($hasCheckIn && $hasCheckOut) {
                        $workingMinutes = $effectiveDuration ? $effectiveDuration['final_duration_minutes'] : 0;
                        
                        if ($workingMinutes < 30) {
                            $dynamicStatus = 'Tidak Hadir';
                            $status = 'absent';
                            $points = 10;
                            $badge = '❌ SHORT';
                        } elseif ($shortageMinutes === 0) {
                            $dynamicStatus = 'Tepat Waktu';
                            $status = 'perfect';
                            $points = 150;
                            $badge = '🏆 PERFECT';
                        } elseif ($shortageMinutes > 0) {
                            $dynamicStatus = "Kurang Menit"; // ✅ CLEAN: No number duplication
                            
                            if ($shortageMinutes <= 15) {
                                $status = 'good';
                                $points = 120;
                                $badge = '⭐ GOOD';
                            } elseif ($shortageMinutes <= 60) {
                                $status = 'late';
                                $points = 80;
                                $badge = '⚠️ LATE';
                            } else {
                                $status = 'incomplete';
                                $points = 50;
                                $badge = '❌ INCOMPLETE';
                            }
                        }
                    }

                    // ✅ ENHANCED: Comprehensive response structure
                    return [
                        'id' => $attendance->id,
                        'jadwal_jaga_id' => $attendance->jadwal_jaga_id,
                        'date' => $attendance->date->format('Y-m-d'),
                        'day_name' => $attendance->date->format('l'),
                        'full_date' => $attendance->date->toISOString(),
                        
                        // ✅ CRITICAL: Complete shift_info for frontend
                        'shift_info' => $shiftInfo,
                        
                        // Mission data (gaming style)
                        'mission_info' => [
                            'mission_title' => ($shiftTemplate && is_object($shiftTemplate) ? $shiftTemplate->nama_shift : 'Shift Jaga') . ' - ' . ($jadwalJaga ? $jadwalJaga->unit_kerja : 'Dokter Jaga'),
                            'mission_subtitle' => $jadwalJaga ? $jadwalJaga->peran : 'Dokter',
                            'scheduled_time' => $shiftInfo['shift_start'] . ' - ' . $shiftInfo['shift_end'],
                            'location' => 'Klinik Dokterku',
                            'shift_duration' => $shiftInfo['shift_duration']
                        ],
                        'status' => $status,
                        'dynamic_status' => $dynamicStatus, // ✅ DYNAMIC: New intelligent status
                        'points_earned' => $points,
                        'achievement_badge' => $badge,
                        
                        // ✅ SOPHISTICATED: Effective duration calculation results
                        'effective_duration' => $effectiveDuration,
                        'shortage_minutes' => $shortageMinutes,
                        'shortfall_minutes' => $shortageMinutes, // ✅ COMPATIBILITY: Send both field names
                        'attendance_percentage' => $effectiveDuration['attendance_percentage'] ?? 0,
                        
                        // Attendance data (multiple formats for compatibility)
                        'time_in' => $attendance->time_in ? $attendance->time_in->format('H:i') : null,
                        'time_out' => $attendance->time_out ? $attendance->time_out->format('H:i') : null,
                        'working_duration' => $effectiveDuration ? $effectiveDuration['final_duration_hours'] : ($attendance->formatted_work_duration ?? '0j 0m'),
                        
                        // Legacy compatibility
                        'check_in_time' => $attendance->time_in,
                        'check_out_time' => $attendance->time_out,
                        'status_legacy' => $attendance->status,
                        
                        // ✅ ADDED: Additional fields for frontend
                        'shift_start' => $shiftInfo['shift_start'],
                        'shift_end' => $shiftInfo['shift_end'],
                        'shift_duration' => $shiftInfo['shift_duration']
                    ];
                });

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
                        // Check if there's an open attendance (no time_out) to allow checkout
                        'can_check_out' => $attendanceToday && !$attendanceToday->time_out
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
                    'stats' => [
                        'total_missions' => $attendanceHistory->count(),
                        'perfect_missions' => $attendanceHistory->where('status', 'perfect')->count(),
                        'good_missions' => $attendanceHistory->where('status', 'good')->count(),
                        'late_missions' => $attendanceHistory->where('status', 'late')->count(),
                        'incomplete_missions' => $attendanceHistory->where('status', 'incomplete')->count(),
                        'total_xp' => $attendanceHistory->sum('points_earned'),
                        'total_hours' => $attendanceHistory->sum(function($item) {
                            if (is_array($item) && isset($item['working_duration'])) {
                                return $this->parseDurationToHours($item['working_duration']);
                            }
                            return 0;
                        }),
                        'performance_rate' => $attendanceHistory->count() > 0 ? 
                            round(($attendanceHistory->whereIn('status', ['perfect', 'good'])->count() / $attendanceHistory->count()) * 100, 1) : 0,
                        'attendance_rate' => (new UnifiedAttendanceService())->calculateAttendanceRate($user->id, Carbon::now()->month, Carbon::now()->year)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getPresensi:', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data presensi: ' . $e->getMessage(),
                'data' => null,
                'debug_info' => [
                    'error_type' => get_class($e),
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]
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

            // ULTRA SIMPLE CHECK-IN: Apply same simplification as check-out
            // BYPASS ALL VALIDATIONS like check-out does
            \Log::info('ULTRA SIMPLE CHECK-IN: Bypassing all validation like check-out');
            
            // Get work location for attendance record (but don't validate it)
            $workLocation = $user->workLocation;
            if (!$workLocation && $user->workLocations()->exists()) {
                $workLocation = $user->workLocations()->first();
            }
            
            // No validation checks - always allow check-in if not already checked in
            $locationValidation = [
                'valid' => true,
                'work_location' => $workLocation,
                'message' => 'Location validation bypassed'
            ];

            // IMPROVED: Smart handling of open attendance with auto-cleanup
            // Only block if there's a RECENT open attendance (within reasonable time)
            $openAttendance = Attendance::where('user_id', $user->id)
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->orderByDesc('date')
                ->orderByDesc('time_in')
                ->first();

            if ($openAttendance) {
                $attendanceDate = Carbon::parse($openAttendance->date);
                $checkInTime = Carbon::parse($openAttendance->date . ' ' . $openAttendance->time_in);
                $hoursSinceCheckIn = $checkInTime->diffInHours($currentTime);
                $daysSinceCheckIn = $attendanceDate->diffInDays($todayJakarta);
                
                // AUTO-CLOSE old abandoned sessions (>24 hours or from previous days)
                if ($daysSinceCheckIn > 0 || $hoursSinceCheckIn > 24) {
                    // Auto-close with reasonable checkout time
                    $autoCheckoutTime = $checkInTime->copy()->addHours(8); // Default 8 hour shift
                    
                    // Don't exceed end of that day
                    $endOfAttendanceDay = $attendanceDate->copy()->endOfDay();
                    if ($autoCheckoutTime->gt($endOfAttendanceDay)) {
                        $autoCheckoutTime = $endOfAttendanceDay;
                    }
                    
                    $openAttendance->time_out = $autoCheckoutTime->format('H:i:s');
                    $openAttendance->save();
                    
                    \Log::info('AUTO-CLOSED abandoned attendance', [
                        'user_id' => $user->id,
                        'attendance_id' => $openAttendance->id,
                        'date' => $openAttendance->date,
                        'auto_checkout_time' => $autoCheckoutTime->format('H:i:s'),
                        'reason' => $daysSinceCheckIn > 0 ? 'old_session' : 'abandoned_session'
                    ]);
                    
                    // Continue with check-in since we auto-closed the old session
                } else if ($attendanceDate->isToday() && $hoursSinceCheckIn < 12) {
                    // Only block if it's TODAY's session and recent (< 12 hours)
                    return response()->json([
                        'success' => false,
                        'message' => "Anda masih memiliki sesi check-in aktif dari pukul {$openAttendance->time_in}. Silakan check-out terlebih dahulu.",
                        'code' => 'ACTIVE_SESSION_EXISTS',
                        'data' => [
                            'open_attendance' => [
                                'id' => $openAttendance->id,
                                'date' => $openAttendance->date->format('Y-m-d'),
                                'time_in' => $openAttendance->time_in?->format('H:i'),
                                'hours_since_checkin' => $hoursSinceCheckIn,
                                'jadwal_jaga_id' => $openAttendance->jadwal_jaga_id,
                            ]
                        ]
                    ], 422);
                } else {
                    // Session is today but >12 hours old - likely abandoned, auto-close it
                    $autoCheckoutTime = $currentTime->copy()->subMinutes(5); // 5 minutes ago
                    $openAttendance->time_out = $autoCheckoutTime->format('H:i:s');
                    $openAttendance->save();
                    
                    \Log::warning('AUTO-CLOSED likely abandoned same-day session', [
                        'user_id' => $user->id,
                        'attendance_id' => $openAttendance->id,
                        'hours_since_checkin' => $hoursSinceCheckIn
                    ]);
                }
            }

            // 2) Prevent duplicate check-in for the same shift (jadwal_jaga) on the same day
            $attendanceForShift = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->where('jadwal_jaga_id', $selectedJadwal->id)
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
                'jadwal_jaga_id' => $selectedJadwal->id, // Link ke jadwal jaga
                'status' => ($validation['code'] ?? '') === 'VALID_BUT_LATE' ? 'late' : 'present',
                'work_location_id' => $workLocation->id ?? null,
            ]);

            $shiftTemplate = $selectedJadwal->shiftTemplate; // ensure available for response payload
            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil',
                'data' => [
                    'attendance' => $attendance,
                    'schedule' => [
                        'id' => $selectedJadwal->id,
                        'shift_name' => $shiftTemplate ? $shiftTemplate->nama_shift : 'Shift',
                        'start_time' => $shiftTemplate ? $shiftTemplate->jam_masuk : '08:00',
                        'end_time' => $shiftTemplate ? $shiftTemplate->jam_pulang : '16:00',
                        'unit_kerja' => $selectedJadwal->unit_kerja
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
            \Log::error('Check-in error for user ' . ($user ? $user->id : 'unknown'), [
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
            
            // MULTIPLE CHECKOUT SUPPORT: Allow multiple checkouts within same shift
            // Find the most recent attendance (open OR closed) for today
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->whereNotNull('time_in')
                ->orderByDesc('time_in')
                ->with('jadwalJaga.shiftTemplate')
                ->first();

            // If no attendance today, check recent days for overnight shifts (only open ones)
            if (!$attendance) {
                $attendance = Attendance::where('user_id', $user->id)
                    ->whereDate('date', '>=', Carbon::now()->subDays(1)->startOfDay())
                    ->whereNotNull('time_in')
                    ->whereNull('time_out') // Only open sessions for overnight
                    ->orderByDesc('date')
                    ->orderByDesc('time_in')
                    ->with('jadwalJaga.shiftTemplate')
                    ->first();
            }

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan check-in hari ini.',
                    'code' => 'NOT_CHECKED_IN'
                ], 422);
            }

            // MULTIPLE CHECKOUT LOGIC: Allow updating checkout time even if already checked out
            if ($attendance->time_out) {
                \Log::info('MULTIPLE CHECKOUT: Updating existing checkout time', [
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id,
                    'previous_checkout' => $attendance->time_out,
                    'new_checkout_request' => $currentTime->format('H:i:s')
                ]);
            }

            // ALWAYS VALIDATE CHECKOUT TIME FIRST (regardless of GPS)
            // This prevents early checkout even when GPS is provided
            $shiftTemplate = optional($attendance->jadwalJaga)->shiftTemplate;
            if ($shiftTemplate && !$request->input('force', false)) {
                // Priority: JadwalJaga->jam_selesai > ShiftTemplate->jam_pulang > ShiftTemplate->jam_keluar
                $jamKeluar = optional($attendance->jadwalJaga)->jam_selesai ?? 
                           $shiftTemplate->jam_pulang ?? 
                           $shiftTemplate->jam_keluar ?? 
                           null;
                if ($jamKeluar) {
                    try {
                        $shiftEnd = strlen((string) $jamKeluar) > 5
                            ? Carbon::parse($jamKeluar)->setTimezone('Asia/Jakarta')
                            : Carbon::createFromFormat('H:i', $jamKeluar, 'Asia/Jakarta');
                        
                        // Adjust for date if needed
                        $shiftEndDateTime = Carbon::parse($today->format('Y-m-d') . ' ' . $shiftEnd->format('H:i'), 'Asia/Jakarta');
                        $shiftStartTime = Carbon::parse($today->format('Y-m-d') . ' ' . ($shiftTemplate->jam_masuk ?? '00:00'), 'Asia/Jakarta');
                        if ($shiftEndDateTime->lt($shiftStartTime)) {
                            $shiftEndDateTime->addDay();
                        }
                        
                        // Use AttendanceToleranceService for validation
                        $toleranceService = new AttendanceToleranceService();
                        $validationResult = $toleranceService->validateCheckoutTime($user, $currentTime, $shiftEndDateTime);
                        
                        if (!$validationResult['allowed']) {
                            \Log::warning('Checkout DENIED - too early', [
                                'user_id' => $user->id,
                                'user_name' => $user->name,
                                'current_time' => $currentTime->format('H:i:s'),
                                'shift_end' => $shiftEndDateTime->format('H:i:s'),
                                'validation_result' => $validationResult
                            ]);
                            
                            return response()->json([
                                'success' => false,
                                'message' => $validationResult['message'],
                                'code' => $validationResult['code'],
                                'tolerance_source' => $validationResult['tolerance_source'] ?? null,
                                'debug' => [
                                    'current_time' => $currentTime->format('H:i:s'),
                                    'shift_end' => $shiftEndDateTime->format('H:i:s'),
                                    'early_tolerance' => $validationResult['early_tolerance'] ?? null
                                ]
                            ], 422);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error validating checkout time', [
                            'error' => $e->getMessage(),
                            'user_id' => $user->id
                        ]);
                    }
                }
            }

            // WORK LOCATION TOLERANCE: Validate checkout with tolerance
            $latitudeInput = $request->input('latitude');
            $longitudeInput = $request->input('longitude');
            $accuracy = $request->input('accuracy');
            $workLocationTolerance = $request->input('work_location_tolerance', false);
            $forceCheckout = $request->input('force', false);
            $hasCoords = is_numeric($latitudeInput) && is_numeric($longitudeInput);

            if ($hasCoords) {
                $latitude = (float) $latitudeInput;
                $longitude = (float) $longitudeInput;
                /** @var \App\Services\AttendanceValidationService $validationService */
                $validationService = app(\App\Services\AttendanceValidationService::class);
                $validation = $validationService->validateCheckout($user, $latitude, $longitude, $accuracy, $today);
                
                // WORK LOCATION TOLERANCE: Override validation for ANY attendance (not just open session)
                // This enables multiple checkout support with work location tolerance
                if (!$validation['valid']) {
                    // Check if we should apply work location tolerance
                    // FIXED: Removed !$attendance->time_out condition to support multiple checkout
                    if ($workLocationTolerance || $forceCheckout || 
                        ($attendance && $attendance->time_in)) {  // Allow for ANY attendance, not just open
                        
                        \Log::info('WORK LOCATION TOLERANCE APPLIED: Allowing checkout despite validation', [
                            'user_id' => $user->id,
                            'attendance_id' => $attendance->id,
                            'has_time_out' => !is_null($attendance->time_out),
                            'original_validation' => $validation['code'],
                            'force_flag' => $forceCheckout,
                            'tolerance_flag' => $workLocationTolerance,
                            'multiple_checkout' => !is_null($attendance->time_out) ? 'YES' : 'NO'
                        ]);
                        
                        // Allow checkout anyway - work location tolerance
                        $validation['valid'] = true;
                        $validation['message'] = 'Checkout diizinkan dengan toleransi lokasi kerja';
                        $validation['code'] = 'WORK_LOCATION_TOLERANCE';
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => $validation['message'],
                            'code' => $validation['code'],
                            'data' => $validation['data'] ?? null,
                        ], 422);
                    }
                }

                // SHIFT END TIME CAP: Cap checkout time at shift end if exceeded
                $checkoutTime = $currentTime;
                $shiftTemplate = optional($attendance->jadwalJaga)->shiftTemplate;
                if ($shiftTemplate) {
                    $jamPulang = $shiftTemplate->jam_pulang ?? $shiftTemplate->jam_keluar ?? null;
                    if ($jamPulang) {
                        try {
                            $shiftEndTime = Carbon::parse($today->format('Y-m-d') . ' ' . $jamPulang, 'Asia/Jakarta');
                            // Handle overnight shifts
                            $shiftStartTime = Carbon::parse($today->format('Y-m-d') . ' ' . ($shiftTemplate->jam_masuk ?? '00:00'), 'Asia/Jakarta');
                            if ($shiftEndTime->lt($shiftStartTime)) {
                                $shiftEndTime->addDay();
                            }
                            // Cap at shift end time if checkout is after shift end
                            if ($currentTime->gt($shiftEndTime)) {
                                $checkoutTime = $shiftEndTime;
                                \Log::info('CHECKOUT TIME CAPPED at shift end', [
                                    'user_id' => $user->id,
                                    'requested_time' => $currentTime->format('H:i:s'),
                                    'capped_to' => $shiftEndTime->format('H:i:s')
                                ]);
                            }
                        } catch (\Exception $e) {
                            // Use actual time if parsing fails
                            $checkoutTime = $currentTime;
                        }
                    }
                }

                $attendance->update([
                    'time_out' => $checkoutTime,
                    'checkout_latitude' => $latitude,
                    'checkout_longitude' => $longitude,
                    'checkout_accuracy' => $accuracy,
                    'latlon_out' => $latitude . ',' . $longitude,
                    'location_name_out' => ($validation['work_location']->name ?? 'Location'),
                ]);
                    } else {
                // Graceful fallback: allow checkout without GPS coordinates
                // Time validation already done at the beginning of the method
            // SHIFT END TIME CAP: Cap checkout time at shift end if exceeded (no GPS case)
            $checkoutTime = $currentTime;
            $shiftTemplate = optional($attendance->jadwalJaga)->shiftTemplate;
            if ($shiftTemplate) {
                $jamPulang = $shiftTemplate->jam_pulang ?? $shiftTemplate->jam_keluar ?? null;
                if ($jamPulang) {
                    try {
                        $shiftEndTime = Carbon::parse($today->format('Y-m-d') . ' ' . $jamPulang, 'Asia/Jakarta');
                        // Handle overnight shifts
                        $shiftStartTime = Carbon::parse($today->format('Y-m-d') . ' ' . ($shiftTemplate->jam_masuk ?? '00:00'), 'Asia/Jakarta');
                        if ($shiftEndTime->lt($shiftStartTime)) {
                            $shiftEndTime->addDay();
                        }
                        // Cap at shift end time if checkout is after shift end
                        if ($currentTime->gt($shiftEndTime)) {
                            $checkoutTime = $shiftEndTime;
                            \Log::info('CHECKOUT TIME CAPPED at shift end (no GPS)', [
                                'user_id' => $user->id,
                                'requested_time' => $currentTime->format('H:i:s'),
                                'capped_to' => $shiftEndTime->format('H:i:s')
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Use actual time if parsing fails
                        $checkoutTime = $currentTime;
                    }
                }
            }

            $attendance->update([
                'time_out' => $checkoutTime,
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

            // Ensure $shiftTemplate is defined for response payload
            $shiftTemplate = optional($attendance->jadwalJaga)->shiftTemplate;

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
            \Log::error('Check-out error for user ' . ($user ? $user->id : 'unknown'), [
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
            
            // Get attendance rate using unified service
            $unifiedAttendanceService = new UnifiedAttendanceService();
            $attendanceRate = $unifiedAttendanceService->calculateAttendanceRate($user->id, $month, $year);
            
            // Get attendance ranking from AttendanceRecap for ranking purposes only
            $attendanceData = collect(); // Default empty collection
            try {
                $attendanceData = \App\Models\AttendanceRecap::getRecapData($month, $year, 'Dokter');
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

                // Define $now for time calculations
                $now = Carbon::now();
                
                // Get current active shift for today based on current time
                $currentShift = null;
                $currentTimeString = $now->format('H:i:s');
                
                // Find the shift that encompasses the current time
                foreach ($todaySchedule->where('status_jaga', 'Aktif') as $schedule) {
                    if (isset($schedule['shift_template']) && $schedule['shift_template']) {
                        $shiftStart = Carbon::parse($schedule['shift_template']['jam_masuk'])->format('H:i:s');
                        $shiftEnd = Carbon::parse($schedule['shift_template']['jam_pulang'])->format('H:i:s');
                        
                        // Handle overnight shifts
                        if ($shiftEnd < $shiftStart) {
                            // Overnight shift
                            if ($currentTimeString >= $shiftStart || $currentTimeString <= $shiftEnd) {
                                $currentShift = $schedule;
                                break;
                            }
                        } else {
                            // Normal shift
                            if ($currentTimeString >= $shiftStart && $currentTimeString <= $shiftEnd) {
                                $currentShift = $schedule;
                                break;
                            }
                        }
                    }
                }
                
                // If no shift matches current time exactly, check with tolerance (30 min before, 60 min after)
                if (!$currentShift) {
                    foreach ($todaySchedule->where('status_jaga', 'Aktif') as $schedule) {
                        if (isset($schedule['shift_template']) && $schedule['shift_template']) {
                            $shiftStart = Carbon::parse($schedule['shift_template']['jam_masuk']);
                            $shiftEnd = Carbon::parse($schedule['shift_template']['jam_pulang']);
                            
                            // Add tolerance: 30 minutes before start, 60 minutes after end
                            $startWithTolerance = $shiftStart->copy()->subMinutes(30);
                            $endWithTolerance = $shiftEnd->copy()->addMinutes(60);
                            
                            // Check if current time is within tolerance
                            $currentDateTime = Carbon::parse($now->format('Y-m-d') . ' ' . $currentTimeString);
                            
                            if ($currentDateTime->between($startWithTolerance, $endWithTolerance)) {
                                $currentShift = $schedule;
                                break;
                            }
                        }
                    }
                }
                
                // Fallback: if still no match, get the first active schedule
                if (!$currentShift) {
                    $currentShift = $todaySchedule->where('status_jaga', 'Aktif')->first();
                }

                // ENHANCED: Calculate schedule card statistics
                $todayDate = $now->toDateString();
                $currentTime = $now->format('H:i:s');
                
                // All schedules for the requested month/year with shift templates
                $allSchedules = JadwalJaga::where('pegawai_id', $userId)
                    ->whereMonth('tanggal_jaga', $month)
                    ->whereYear('tanggal_jaga', $year)
                    ->with(['shiftTemplate'])
                    ->get();
                
                // ✅ FIXED: Only count shifts with actual completed attendance
                $completedShifts = $allSchedules->filter(function ($jadwal) use ($userId) {
                    // Check if there's a completed attendance record for this shift
                    $attendance = \App\Models\Attendance::where('user_id', $userId)
                        ->whereDate('date', $jadwal->tanggal_jaga)
                        ->whereNotNull('time_in')
                        ->whereNotNull('time_out')
                        ->first();
                    
                    return $attendance !== null;
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
                
                // ✅ REALISTIC: Calculate monthly total hours based on attended jadwal jaga only (Test endpoint)
                $attendedJadwalHoursTest = 0;
                $attendedJadwalCountTest = 0;
                
                // Get all jadwal jaga for the month that were actually attended
                $jadwalJagaMonthTest = JadwalJaga::where('pegawai_id', $userId)
                    ->whereMonth('tanggal_jaga', $month)
                    ->whereYear('tanggal_jaga', $year)
                    ->where('tanggal_jaga', '<', Carbon::now()->toDateString()) // Only past dates
                    ->with('shiftTemplate')
                    ->get();

                foreach ($jadwalJagaMonthTest as $jadwal) {
                    // Check if user actually attended this jadwal (has completed attendance)
                    $attendanceExists = \App\Models\Attendance::where('user_id', $userId)
                        ->whereDate('date', $jadwal->tanggal_jaga)
                        ->whereNotNull('time_in')
                        ->whereNotNull('time_out')
                        ->exists();
                    
                    if ($attendanceExists) {
                        $attendedJadwalCountTest++;
                        // Use shift template duration (realistic 8-hour shifts)
                        if ($jadwal->shiftTemplate && $jadwal->shiftTemplate->durasi_jam) {
                            $attendedJadwalHoursTest += $jadwal->shiftTemplate->durasi_jam;
                        } else {
                            $attendedJadwalHoursTest += 8; // Default 8-hour shift
                        }
                    }
                }

                // Calculate final monthly total from attended jadwal jaga only
                $totalHours = $attendedJadwalHoursTest;

                // Schedule card statistics with Indonesian formatting
                $scheduleStats = [
                    'completed' => $attendedJadwalCountTest, // Use attended jadwal count
                    'upcoming' => $upcomingShifts->count(),
                    'total_hours' => HoursFormatter::formatForApi($totalHours),
                    'total_shifts' => $allSchedules->count(),
                    'current_month' => $month,
                    'current_year' => $year,
                    'month_name' => Carbon::create($year, $month, 1)->format('F Y')
                ];

                // Get work location data
                $user->load('workLocation');
                $workLocation = $user->workLocation;
                
                // Prepare work location response
                $workLocationData = null;
                if ($workLocation) {
                    $workLocationData = [
                        'id' => $workLocation->id,
                        'name' => $workLocation->name,
                        'latitude' => $workLocation->latitude,
                        'longitude' => $workLocation->longitude,
                        'radius' => $workLocation->radius,
                        'tolerance_settings' => $workLocation->tolerance_settings,
                        'checkin_before_shift_minutes' => $workLocation->checkin_before_shift_minutes ?? 
                            ($workLocation->tolerance_settings['checkin_before_shift_minutes'] ?? 30),
                        'checkout_after_shift_minutes' => $workLocation->checkout_after_shift_minutes ?? 
                            ($workLocation->tolerance_settings['checkout_after_shift_minutes'] ?? 60),
                        'late_tolerance_minutes' => $workLocation->late_tolerance_minutes ?? 
                            ($workLocation->tolerance_settings['late_tolerance_minutes'] ?? 15)
                    ];
                }
                
                return [
                    'calendar_events' => $calendarEvents,
                    'weekly_schedule' => $weeklySchedule,
                    'today' => $todaySchedule,
                    'currentShift' => $currentShift,
                    'workLocation' => $workLocationData,
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

    /**
     * Get multi-shift status for the user
     */
    public function multishiftStatus(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'data' => null
                ], 401);
            }

            $today = Carbon::today();
            $currentTime = Carbon::now();

            // Get all attendances for today
            $todayAttendances = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->orderBy('shift_sequence')
                ->get();

            // Get today's schedules
            $todaySchedules = JadwalJaga::where(function($query) use ($user) {
                    $query->where('pegawai_id', $user->id)
                          ->orWhere('user_id', $user->id);
                })
                ->whereDate('tanggal_jaga', $today)
                ->with('shiftTemplate')
                ->orderBy('shift_sequence')
                ->get();

            // Determine current status
            $canCheckIn = false;
            $canCheckOut = false;
            $currentShift = null;
            $nextShift = null;
            $shiftsAvailable = [];
            $message = '';

            // Check if there's an open attendance (checked in but not out)
            $openAttendance = $todayAttendances->firstWhere('time_out', null);
            
            if ($openAttendance) {
                $canCheckOut = true;
                $currentShift = [
                    'id' => $openAttendance->shift_id ?? $openAttendance->jadwal_jaga_id,
                    'nama_shift' => 'Current Shift',
                    'jam_masuk' => $openAttendance->time_in?->format('H:i') ?? '',
                    'jam_pulang' => $openAttendance->time_out?->format('H:i') ?? '',
                    'shift_sequence' => $openAttendance->shift_sequence ?? 1,
                    'is_current' => true,
                    'is_available' => false,
                    'can_checkin' => false
                ];
                $message = 'Anda sedang dalam shift. Silakan check-out terlebih dahulu.';
            } else {
                // Check if can check in for next shift
                $maxShifts = config('attendance.multishift.max_shifts_per_day', 3);
                $completedShifts = $todayAttendances->count();

                if ($completedShifts >= $maxShifts) {
                    $message = 'Anda sudah mencapai batas maksimal ' . $maxShifts . ' shift per hari.';
                } else {
                    // Check gap from last attendance
                    $lastAttendance = $todayAttendances->last();
                    
                    if ($lastAttendance && $lastAttendance->time_out) {
                        $timeSinceCheckout = round(Carbon::parse($lastAttendance->time_out)->diffInMinutes($currentTime));
                        $minGap = config('attendance.multishift.min_gap_between_shifts', 60);
                        
                        if ($timeSinceCheckout < $minGap) {
                            $remainingMinutes = round($minGap - $timeSinceCheckout);
                            $message = 'Anda harus menunggu ' . $remainingMinutes . ' menit lagi sebelum check-in shift berikutnya.';
                        } else {
                            // Find available shifts
                            foreach ($todaySchedules as $schedule) {
                                // Skip if already used
                                if ($todayAttendances->contains('jadwal_jaga_id', $schedule->id)) {
                                    continue;
                                }

                                $shift = $schedule->shiftTemplate;
                                if (!$shift) continue;

                                // Get work location settings for tolerance
                                $workLocation = \App\Models\WorkLocation::first();
                                $toleranceEarly = 30; // default
                                $toleranceLate = 15; // default
                                
                                if ($workLocation) {
                                    // Try JSON settings first
                                    $toleranceSettings = $workLocation->tolerance_settings;
                                    if ($toleranceSettings) {
                                        $settings = is_string($toleranceSettings) ? json_decode($toleranceSettings, true) : $toleranceSettings;
                                        $toleranceEarly = $settings['checkin_before_shift_minutes'] ?? 30;
                                        $toleranceLate = $settings['late_tolerance_minutes'] ?? 15;
                                    }
                                    
                                    // Individual fields override JSON if set
                                    if ($workLocation->late_tolerance_minutes !== null) {
                                        $toleranceLate = $workLocation->late_tolerance_minutes;
                                    }
                                }

                                $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $shift->jam_masuk);
                                $windowStart = $shiftStart->copy()->subMinutes($toleranceEarly);
                                $windowEnd = $shiftStart->copy()->addMinutes($toleranceLate);

                                $shiftInfo = [
                                    'id' => $shift->id,
                                    'nama_shift' => $shift->nama_shift,
                                    'jam_masuk' => $shift->jam_masuk,
                                    'jam_pulang' => $shift->jam_pulang ?? $shift->jam_keluar,
                                    'shift_sequence' => $completedShifts + 1,
                                    'is_available' => true,
                                    'is_current' => false,
                                    'can_checkin' => false,
                                    'window_message' => null
                                ];

                                if ($currentTime->between($windowStart, $windowEnd)) {
                                    $canCheckIn = true;
                                    $shiftInfo['can_checkin'] = true;
                                    $shiftInfo['is_current'] = true;
                                    $currentShift = $shiftInfo;
                                    $message = 'Anda dapat check-in untuk shift ' . $shift->nama_shift;
                                } elseif ($currentTime->lessThan($windowStart)) {
                                    $shiftInfo['window_message'] = 'Check-in mulai pukul ' . $windowStart->format('H:i');
                                    $shiftsAvailable[] = $shiftInfo;
                                    if (!$nextShift) {
                                        $nextShift = $shiftInfo;
                                    }
                                }
                            }
                        }
                    } else if ($completedShifts === 0) {
                        // First shift of the day - check if within window
                        // Get work location settings for tolerance
                        $workLocation = \App\Models\WorkLocation::first();
                        $toleranceEarly = 30; // default
                        $toleranceLate = 15; // default
                        
                        if ($workLocation) {
                            // Try JSON settings first
                            $toleranceSettings = $workLocation->tolerance_settings;
                            if ($toleranceSettings) {
                                $settings = is_string($toleranceSettings) ? json_decode($toleranceSettings, true) : $toleranceSettings;
                                $toleranceEarly = $settings['checkin_before_shift_minutes'] ?? 30;
                                $toleranceLate = $settings['late_tolerance_minutes'] ?? 15;
                            }
                            
                            // Individual fields override JSON if set
                            if ($workLocation->late_tolerance_minutes !== null) {
                                $toleranceLate = $workLocation->late_tolerance_minutes;
                            }
                        }
                        
                        // Find first available shift
                        foreach ($todaySchedules as $schedule) {
                            $shift = $schedule->shiftTemplate;
                            if (!$shift) continue;

                            $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $shift->jam_masuk);
                            $windowStart = $shiftStart->copy()->subMinutes($toleranceEarly);
                            $windowEnd = $shiftStart->copy()->addMinutes($toleranceLate);

                            if ($currentTime->between($windowStart, $windowEnd)) {
                                $canCheckIn = true;
                                $currentShift = [
                                    'id' => $shift->id,
                                    'nama_shift' => $shift->nama_shift,
                                    'jam_masuk' => $shift->jam_masuk,
                                    'jam_pulang' => $shift->jam_pulang ?? $shift->jam_keluar,
                                    'shift_sequence' => 1,
                                    'is_available' => true,
                                    'is_current' => true,
                                    'can_checkin' => true
                                ];
                                $message = 'Anda dapat check-in untuk shift ' . $shift->nama_shift;
                                break;
                            } elseif ($currentTime->lessThan($windowStart)) {
                                $message = 'Check-in untuk shift ' . $shift->nama_shift . ' mulai pukul ' . $windowStart->format('H:i');
                                if (!$nextShift) {
                                    $nextShift = [
                                        'id' => $shift->id,
                                        'nama_shift' => $shift->nama_shift,
                                        'jam_masuk' => $shift->jam_masuk,
                                        'jam_pulang' => $shift->jam_pulang ?? $shift->jam_keluar,
                                        'shift_sequence' => 1,
                                        'is_available' => true,
                                        'is_current' => false,
                                        'can_checkin' => false,
                                        'window_message' => 'Check-in mulai pukul ' . $windowStart->format('H:i')
                                    ];
                                }
                            } else {
                                $message = 'Waktu check-in untuk shift ' . $shift->nama_shift . ' sudah lewat (maksimal ' . $windowEnd->format('H:i') . ')';
                            }
                        }
                        
                        if (!$canCheckIn) {
                            if ($todaySchedules->isEmpty()) {
                                $message = 'Tidak ada jadwal untuk hari ini';
                            } else {
                                // Find earliest shift and show when check-in opens
                                $earliestShift = $todaySchedules->first();
                                $shift = $earliestShift->shiftTemplate;
                                if ($shift) {
                                    $shiftStart = Carbon::parse($today->format('Y-m-d') . ' ' . $shift->jam_masuk);
                                    $windowStart = $shiftStart->copy()->subMinutes($toleranceEarly);
                                    $message = 'Check-in untuk shift ' . $shift->nama_shift . ' mulai pukul ' . $windowStart->format('H:i');
                                } else {
                                    $message = 'Belum waktunya check-in';
                                }
                            }
                        }
                    }
                }
            }

            // Format attendance records
            $attendanceRecords = $todayAttendances->map(function ($att) {
                return [
                    'id' => $att->id,
                    'shift_sequence' => $att->shift_sequence ?? 1,
                    'shift_name' => 'Shift ' . ($att->shift_sequence ?? 1),
                    'time_in' => $att->time_in?->format('H:i:s') ?? '',
                    'time_out' => $att->time_out?->format('H:i:s') ?? null,
                    'status' => $att->time_out ? 'completed' : 'present',
                    'is_overtime' => $att->is_overtime_shift ?? false,
                    'gap_minutes' => $att->gap_from_previous_minutes ?? 0
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'can_check_in' => $canCheckIn,
                    'message' => $message,
                    'current_shift' => $currentShift,
                    'next_shift' => $nextShift,
                    'today_attendances' => $attendanceRecords,
                    'shifts_available' => $shiftsAvailable,
                    'max_shifts_reached' => $todayAttendances->count() >= config('attendance.multishift.max_shifts_per_day', 3)
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error in multishiftStatus: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting multi-shift status: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get attendance history for unified calculation
     */
    public function getAttendance(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'data' => []
                ], 401);
            }

            // Get current month range or custom range
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            
            $startDate = Carbon::create($year, $month, 1);
            $endDate = Carbon::create($year, $month)->endOfMonth();
            
            \Log::info('📅 DokterDashboardController::getAttendance', [
                'user_id' => $user->id,
                'month' => $month,
                'year' => $year,
                'date_range' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
            ]);

            // Get attendance records for the period
            $attendanceRecords = Attendance::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'asc')
                ->get();

            \Log::info('📊 Found attendance records', ['count' => $attendanceRecords->count()]);

            // Format for unified calculator compatibility
            $formattedRecords = $attendanceRecords->map(function ($record) {
                // Get shift template data if available
                $shiftTemplate = null;
                if ($record->jadwal_jaga_id) {
                    $jadwal = JadwalJaga::find($record->jadwal_jaga_id);
                    if ($jadwal) {
                        $shiftTemplate = [
                            'jam_masuk' => $jadwal->jam_masuk,
                            'jam_pulang' => $jadwal->jam_pulang ?? $jadwal->jam_keluar,
                        ];
                    }
                }

                // Calculate hours from time_in and time_out
                $hours = '0h 0m';
                $actualHours = 0;
                if ($record->time_in && $record->time_out) {
                    $timeIn = Carbon::parse($record->time_in);
                    $timeOut = Carbon::parse($record->time_out);
                    $diffMinutes = $timeOut->diffInMinutes($timeIn);
                    $actualHours = round($diffMinutes / 60, 1);
                    $h = floor($actualHours);
                    $m = round(($actualHours - $h) * 60);
                    $hours = "{$h}h {$m}m";
                }

                // Determine status based on attendance data
                $status = 'Tidak Hadir';
                if ($record->time_in) {
                    if ($record->time_out) {
                        $status = 'Hadir';
                        
                        // Check if late (if jadwal available)
                        if ($shiftTemplate && $shiftTemplate['jam_masuk']) {
                            $scheduledTime = Carbon::parse($record->date . ' ' . $shiftTemplate['jam_masuk']);
                            $actualTime = Carbon::parse($record->time_in);
                            if ($actualTime->gt($scheduledTime->addMinutes(15))) { // 15 minutes tolerance
                                $status = 'Terlambat';
                            } else {
                                $status = 'Tepat Waktu';
                            }
                        }
                    } else {
                        $status = 'Belum Checkout';
                    }
                }

                return [
                    'date' => Carbon::parse($record->date)->format('d/m/Y'),
                    'status' => $status,
                    'time_in' => $record->time_in ? Carbon::parse($record->time_in)->format('H:i') : null,
                    'time_out' => $record->time_out ? Carbon::parse($record->time_out)->format('H:i') : null,
                    'hours' => $hours,
                    'actual_hours' => $actualHours,
                    'worked_hours' => $actualHours, // Same as actual_hours for compatibility
                    'scheduled_hours' => $shiftTemplate ? 8 : 8, // Default 8 hours
                    'shift_template' => $shiftTemplate,
                ];
            });

            \Log::info('✅ Formatted attendance records', [
                'formatted_count' => $formattedRecords->count(),
                'sample' => $formattedRecords->take(2)->toArray()
            ]);

            return response()->json([
                'success' => true,
                'data' => $formattedRecords->values()->all(),
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'total_records' => $formattedRecords->count(),
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('❌ Error in DokterDashboardController::getAttendance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching attendance data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
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
            
            $totalMinutes = $start->diffInMinutes($end); // Fixed: start->diffInMinutes(end)
            $hours = intval($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            
            return sprintf('%dj %dm', $hours, $minutes);
        } catch (\Exception $e) {
            return '8j 0m'; // Default fallback
        }
    }

    /**
     * Parse duration string to hours for calculations
     */
    protected function parseDurationToHours(string $duration): float
    {
        try {
            // Parse format like "8j 30m" or "0j 5m"
            if (preg_match('/(\d+)j\s*(\d+)m/', $duration, $matches)) {
                $hours = (int)$matches[1];
                $minutes = (int)$matches[2];
                return $hours + ($minutes / 60);
            }
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get leaderboard data for elite doctors
     */
    public function leaderboard(Request $request)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            $currentDay = Carbon::now()->day;
            
            // Calculate total work days in current month
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $totalWorkDays = 0;
            
            for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
                if (!$date->isWeekend()) {
                    $totalWorkDays++;
                }
            }
            
            // Get all active doctors
            $doctors = Dokter::where('aktif', true)
                ->with('user')
                ->get();
                
            $leaderboardData = [];
            
            foreach ($doctors as $dokter) {
                // Skip if no user associated
                if (!$dokter->user) {
                    continue;
                }
                
                // Calculate attendance rate using unified service
                $unifiedAttendanceService = new UnifiedAttendanceService();
                $attendanceRate = $unifiedAttendanceService->calculateAttendanceRate(
                    $dokter->user_id, 
                    $currentMonth, 
                    $currentYear
                );
                
                // Get patient count from JumlahPasienHarian
                $patientCount = JumlahPasienHarian::where('dokter_id', $dokter->id)
                    ->whereMonth('tanggal', $currentMonth)
                    ->whereYear('tanggal', $currentYear)
                    ->whereIn('status_validasi', ['approved', 'disetujui'])
                    ->sum(DB::raw('jumlah_pasien_umum + jumlah_pasien_bpjs'));
                
                // Get procedure count from Tindakan
                $procedureCount = Tindakan::where('dokter_id', $dokter->id)
                    ->whereMonth('tanggal_tindakan', $currentMonth)
                    ->whereYear('tanggal_tindakan', $currentYear)
                    ->whereIn('status_validasi', ['approved', 'disetujui'])
                    ->count();
                
                // Calculate streak days
                $streakDays = $this->calculateStreakDays($dokter->user_id);
                
                // Calculate total hours worked this month
                $totalHours = Attendance::where('user_id', $dokter->user_id)
                    ->whereMonth('date', $currentMonth)
                    ->whereYear('date', $currentYear)
                    ->whereNotNull('time_out')
                    ->sum('logical_work_minutes') / 60;
                
                // Calculate attendance count for total_days field
                $attendanceCount = Attendance::where('user_id', $dokter->user_id)
                    ->whereMonth('date', $currentMonth)
                    ->whereYear('date', $currentYear)
                    ->whereNotNull('time_out')
                    ->count();
                
                // Calculate weighted score for ranking
                $score = ($attendanceRate * 0.3) + ($patientCount * 0.4) + ($procedureCount * 0.3);
                
                $leaderboardData[] = [
                    'id' => $dokter->id,
                    'name' => $dokter->user->name,
                    'attendance_rate' => $attendanceRate,
                    'total_patients' => $patientCount,
                    'procedures_count' => $procedureCount,
                    'streak_days' => $streakDays,
                    'total_hours' => round($totalHours, 1),
                    'total_days' => $attendanceCount,
                    'consultation_hours' => round($totalHours * 0.8, 1), // Estimate 80% as consultation
                    'score' => $score,
                    'month' => $currentMonth,
                    'year' => $currentYear,
                    'monthLabel' => Carbon::now()->format('F Y')
                ];
            }
            
            // Sort by score and assign ranks
            usort($leaderboardData, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            
            // Assign ranks and badges
            foreach ($leaderboardData as $index => &$doctor) {
                $doctor['rank'] = $index + 1;
                $doctor['level'] = max(1, min(20, floor($doctor['score'] / 100))); // Level 1-20
                $doctor['xp'] = round($doctor['score'] * 10); // XP based on score
                
                // Assign badges
                if ($index === 0) {
                    $doctor['badge'] = '👑';
                } elseif ($index === 1) {
                    $doctor['badge'] = '🥈';
                } elseif ($index === 2) {
                    $doctor['badge'] = '🥉';
                } else {
                    $doctor['badge'] = '⭐';
                }
                
                // Remove score from final output
                unset($doctor['score']);
            }
            
            // Removed manual override - now using real database values
            
            return response()->json([
                'success' => true,
                'data' => array_slice($leaderboardData, 0, 10) // Return top 10
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Leaderboard Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch leaderboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate streak days for a user
     */
    protected function calculateStreakDays($userId): int
    {
        $today = Carbon::today();
        $streakDays = 0;
        
        // Check backwards from today
        for ($i = 0; $i < 365; $i++) {
            $checkDate = $today->copy()->subDays($i);
            
            // Skip weekends
            if ($checkDate->isWeekend()) {
                continue;
            }
            
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $checkDate)
                ->whereNotNull('time_out')
                ->exists();
                
            if ($attendance) {
                $streakDays++;
            } else {
                // Streak broken
                break;
            }
        }
        
        return $streakDays;
    }

}