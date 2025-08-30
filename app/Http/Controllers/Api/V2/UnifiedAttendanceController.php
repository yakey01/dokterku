<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Dokter;
use App\Models\JadwalJaga;
use App\Models\Attendance;
use App\Models\DokterPresensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Unified Attendance Controller
 * 
 * Menggunakan JadwalJaga sebagai single source of truth
 * untuk memastikan 100% konsistensi data antara JadwalJaga dan History components
 */
class UnifiedAttendanceController extends Controller
{
    /**
     * Get unified attendance data (today + history + stats)
     * Source of truth: JadwalJaga table
     */
    public function getUnifiedAttendanceData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }

            $dokter = Dokter::where('user_id', $user->id)->first();
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan'
                ], 404);
            }

            // Get date range parameters
            $startDate = $request->get('start') ? 
                Carbon::parse($request->get('start')) : 
                Carbon::now()->subDays(30);
            $endDate = $request->get('end') ? 
                Carbon::parse($request->get('end')) : 
                Carbon::now();

            // Cache key based on user and date range
            $cacheKey = "unified_attendance_{$user->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
            $cacheDuration = 60; // 1 minute for real-time needs

            return Cache::remember($cacheKey, $cacheDuration, function() use ($user, $dokter, $startDate, $endDate) {
                // 🎯 PRIMARY DATA SOURCE: JadwalJaga (source of truth)
                $jadwalJagaRecords = JadwalJaga::where('pegawai_id', $user->id)
                    ->whereBetween('tanggal_jaga', [$startDate, $endDate])
                    ->with(['shiftTemplate', 'user'])
                    ->orderByDesc('tanggal_jaga')
                    ->get();

                // Transform JadwalJaga data to unified format
                $unifiedHistory = $this->transformJadwalJagaToUnifiedFormat($jadwalJagaRecords, $dokter);

                // Get today's data for real-time status
                $todayData = $this->getTodayUnifiedData($user, $dokter);

                // Get statistics
                $statsData = $this->getUnifiedStats($jadwalJagaRecords);

                return response()->json([
                    'success' => true,
                    'message' => 'Unified attendance data loaded successfully',
                    'data' => [
                        'today' => $todayData,
                        'history' => $unifiedHistory,
                        'stats' => $statsData,
                        'meta' => [
                            'data_source' => 'jadwal_jaga', // Always JadwalJaga
                            'cached_at' => now()->toISOString(),
                            'total_records' => count($unifiedHistory),
                            'date_range' => [
                                'start' => $startDate->format('Y-m-d'),
                                'end' => $endDate->format('Y-m-d')
                            ]
                        ]
                    ]
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Unified attendance data error', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data unified attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transform JadwalJaga records ke format unified untuk History dan JadwalJaga components
     */
    protected function transformJadwalJagaToUnifiedFormat($jadwalJagaRecords, $dokter): array
    {
        $unifiedData = [];

        foreach ($jadwalJagaRecords as $jadwal) {
            // Get corresponding attendance/presensi data for this jadwal
            $attendanceData = $this->getAttendanceDataForJadwal($jadwal, $dokter);
            
            // Create unified record format
            $unifiedRecord = [
                // 🎯 PRIMARY: JadwalJaga data (source of truth)
                'jadwal_id' => $jadwal->id,
                'tanggal' => $jadwal->tanggal_jaga->format('d/m/Y'), // Mobile app format
                'tanggal_full' => $jadwal->tanggal_jaga->format('Y-m-d'),
                'day_name' => $jadwal->tanggal_jaga->format('l'),
                'jam_shift' => $jadwal->jam_shift,
                'unit_kerja' => $jadwal->unit_kerja,
                'peran' => $jadwal->peran,
                'status_jaga' => $jadwal->status_jaga, // 🎯 REAL-TIME STATUS
                
                // 🕐 SHIFT SCHEDULE: From ShiftTemplate
                'shift_info' => [
                    'nama_shift' => $jadwal->shiftTemplate?->nama_shift ?? 'Default',
                    'jam_masuk' => $jadwal->shiftTemplate?->jam_masuk ?? '08:00',
                    'jam_pulang' => $jadwal->shiftTemplate?->jam_pulang ?? '16:00',
                    'durasi_jam' => $jadwal->shiftTemplate?->durasi_jam ?? 8,
                    'is_overnight' => $jadwal->shiftTemplate?->is_overnight ?? false
                ],
                
                // ⚡ ATTENDANCE DATA: Merged from multiple sources
                'attendance' => $attendanceData,
                
                // 📊 STATUS INDICATORS: For frontend consistency
                'display_status' => $this->determineDisplayStatus($jadwal, $attendanceData),
                'is_completed' => $jadwal->status_jaga === 'Completed' || $attendanceData['is_complete'],
                'is_active' => $jadwal->status_jaga === 'Aktif' && !$attendanceData['is_complete'],
                'needs_attention' => $this->needsAttention($jadwal, $attendanceData),
                
                // 🔄 SYNC METADATA: For frontend cache management
                'last_updated' => $jadwal->updated_at->toISOString(),
                'data_freshness' => 'live', // Always live from JadwalJaga
                'source_priority' => 'jadwal_jaga' // Indicate primary source
            ];
            
            $unifiedData[] = $unifiedRecord;
        }

        return $unifiedData;
    }

    /**
     * Get attendance data untuk specific JadwalJaga record
     */
    protected function getAttendanceDataForJadwal($jadwal, $dokter): array
    {
        $tanggal = $jadwal->tanggal_jaga;
        
        // Priority 1: Check Attendance table (new system)
        $attendance = Attendance::where('user_id', $jadwal->pegawai_id)
            ->whereDate('date', $tanggal)
            ->where('jadwal_jaga_id', $jadwal->id)
            ->first();
            
        if ($attendance) {
            return [
                'source' => 'attendance_table',
                'id' => $attendance->id,
                'jam_masuk' => $attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i') : null,
                'jam_pulang' => $attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i') : null,
                'durasi_menit' => $attendance->work_duration,
                'durasi_formatted' => $attendance->formatted_work_duration,
                'status' => $attendance->status,
                'is_complete' => $attendance->hasCheckedOut(),
                'location_in' => $attendance->location_name_in,
                'location_out' => $attendance->location_name_out,
                'notes' => $attendance->notes
            ];
        }
        
        // Priority 2: Check DokterPresensi table (legacy system)
        $presensi = DokterPresensi::where('dokter_id', $dokter->id)
            ->whereDate('tanggal', $tanggal)
            ->first();
            
        if ($presensi) {
            $durasi = null;
            $durasiFormatted = '-';
            
            if ($presensi->jam_masuk && $presensi->jam_pulang) {
                $durasi = $presensi->jam_masuk->diffInMinutes($presensi->jam_pulang);
                $hours = floor($durasi / 60);
                $minutes = $durasi % 60;
                $durasiFormatted = $hours . 'h ' . $minutes . 'm';
            }
            
            return [
                'source' => 'dokter_presensi_table',
                'id' => $presensi->id,
                'jam_masuk' => $presensi->jam_masuk ? $presensi->jam_masuk->format('H:i') : null,
                'jam_pulang' => $presensi->jam_pulang ? $presensi->jam_pulang->format('H:i') : null,
                'durasi_menit' => $durasi,
                'durasi_formatted' => $durasiFormatted,
                'status' => $presensi->jam_pulang ? 'completed' : 'active',
                'is_complete' => !empty($presensi->jam_pulang),
                'location_in' => null,
                'location_out' => null,
                'notes' => null
            ];
        }
        
        // Priority 3: No attendance record (jadwal only)
        return [
            'source' => 'jadwal_only',
            'id' => null,
            'jam_masuk' => null,
            'jam_pulang' => null,
            'durasi_menit' => null,
            'durasi_formatted' => '-',
            'status' => 'scheduled',
            'is_complete' => false,
            'location_in' => null,
            'location_out' => null,
            'notes' => null
        ];
    }

    /**
     * Determine display status berdasarkan JadwalJaga + Attendance data
     */
    protected function determineDisplayStatus($jadwal, $attendanceData): string
    {
        // 🎯 JadwalJaga status takes priority (source of truth)
        if ($jadwal->status_jaga === 'Completed') {
            return 'completed';
        }
        
        if ($jadwal->status_jaga === 'Cuti' || $jadwal->status_jaga === 'Izin') {
            return 'leave';
        }
        
        // Check attendance data
        if ($attendanceData['is_complete']) {
            return 'completed';
        }
        
        if ($attendanceData['jam_masuk'] && !$attendanceData['jam_pulang']) {
            return 'active';
        }
        
        if ($jadwal->status_jaga === 'Aktif') {
            return 'scheduled';
        }
        
        return 'unknown';
    }

    /**
     * Check if jadwal needs attention (discrepancy, issues, etc.)
     */
    protected function needsAttention($jadwal, $attendanceData): bool
    {
        $today = Carbon::today('Asia/Jakarta');
        $jadwalDate = Carbon::parse($jadwal->tanggal_jaga);
        
        // Past jadwal without attendance
        if ($jadwalDate->lt($today) && !$attendanceData['is_complete']) {
            return true;
        }
        
        // Active shift without check-in (if shift time has passed)
        if ($jadwal->status_jaga === 'Aktif' && $jadwalDate->isToday()) {
            $shiftStart = Carbon::parse($jadwalDate->format('Y-m-d') . ' ' . $jadwal->shiftTemplate?->jam_masuk ?? '08:00');
            if (Carbon::now('Asia/Jakarta')->gt($shiftStart->addMinutes(30)) && !$attendanceData['jam_masuk']) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get today's unified data for real-time display
     */
    protected function getTodayUnifiedData($user, $dokter): array
    {
        $today = Carbon::today('Asia/Jakarta');
        
        // Get today's jadwal jaga (source of truth)
        $todayJadwal = JadwalJaga::where('pegawai_id', $user->id)
            ->whereDate('tanggal_jaga', $today)
            ->with('shiftTemplate')
            ->orderBy('created_at')
            ->get();
            
        $todayData = [];
        foreach ($todayJadwal as $jadwal) {
            $attendanceData = $this->getAttendanceDataForJadwal($jadwal, $dokter);
            
            $todayData[] = [
                'jadwal_id' => $jadwal->id,
                'jam_shift' => $jadwal->jam_shift,
                'unit_kerja' => $jadwal->unit_kerja,
                'status_jaga' => $jadwal->status_jaga, // 🎯 REAL-TIME STATUS
                'attendance' => $attendanceData,
                'display_status' => $this->determineDisplayStatus($jadwal, $attendanceData),
                'can_check_in' => $this->canCheckIn($jadwal, $attendanceData),
                'can_check_out' => $this->canCheckOut($jadwal, $attendanceData)
            ];
        }
        
        return [
            'date' => $today->format('Y-m-d'),
            'schedules' => $todayData,
            'summary' => [
                'total_shifts' => count($todayData),
                'completed_shifts' => count(array_filter($todayData, fn($s) => $s['display_status'] === 'completed')),
                'active_shifts' => count(array_filter($todayData, fn($s) => $s['display_status'] === 'active')),
                'scheduled_shifts' => count(array_filter($todayData, fn($s) => $s['display_status'] === 'scheduled'))
            ]
        ];
    }

    /**
     * Get unified statistics
     */
    protected function getUnifiedStats($jadwalJagaRecords): array
    {
        $totalShifts = $jadwalJagaRecords->count();
        $completedShifts = $jadwalJagaRecords->where('status_jaga', 'Completed')->count();
        $activeShifts = $jadwalJagaRecords->where('status_jaga', 'Aktif')->count();
        
        return [
            'total_shifts' => $totalShifts,
            'completed_shifts' => $completedShifts,
            'active_shifts' => $activeShifts,
            'completion_rate' => $totalShifts > 0 ? round(($completedShifts / $totalShifts) * 100, 1) : 0,
            'period_summary' => [
                'start_date' => $jadwalJagaRecords->last()?->tanggal_jaga?->format('Y-m-d'),
                'end_date' => $jadwalJagaRecords->first()?->tanggal_jaga?->format('Y-m-d'),
                'total_days' => $jadwalJagaRecords->pluck('tanggal_jaga')->unique()->count()
            ]
        ];
    }

    /**
     * Check if can check in for this jadwal
     */
    protected function canCheckIn($jadwal, $attendanceData): bool
    {
        if ($attendanceData['jam_masuk']) {
            return false; // Already checked in
        }
        
        if ($jadwal->status_jaga !== 'Aktif') {
            return false; // Not active schedule
        }
        
        $today = Carbon::today('Asia/Jakarta');
        $jadwalDate = Carbon::parse($jadwal->tanggal_jaga);
        
        return $jadwalDate->isSameDay($today); // Only today's shifts
    }

    /**
     * Check if can check out for this jadwal
     */
    protected function canCheckOut($jadwal, $attendanceData): bool
    {
        if (!$attendanceData['jam_masuk']) {
            return false; // Must check in first
        }
        
        if ($attendanceData['jam_pulang']) {
            return false; // Already checked out
        }
        
        return true; // Can check out if checked in but not out
    }

    /**
     * Force refresh cache (untuk manual sync)
     */
    public function forceRefresh(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // Clear all related caches
            $patterns = [
                "unified_attendance_{$user->id}_*",
                "dokter_dashboard_{$user->id}_*",
                "jadwal_jaga_{$user->id}_*"
            ];

            foreach ($patterns as $pattern) {
                Cache::flush(); // For simplicity, flush all cache
            }

            Log::info('Unified attendance cache cleared', [
                'user_id' => $user->id,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cache refreshed successfully',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get history only (optimized for History tab)
     */
    public function getHistoryOnly(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $dokter = Dokter::where('user_id', $user->id)->first();
            if (!$dokter) {
                return response()->json(['success' => false, 'message' => 'Dokter not found'], 404);
            }

            // Get date range (default: last 30 days)
            $startDate = $request->get('start') ? 
                Carbon::parse($request->get('start')) : 
                Carbon::now()->subDays(30);
            $endDate = $request->get('end') ? 
                Carbon::parse($request->get('end')) : 
                Carbon::now();

            // 🎯 ALWAYS USE JADWAL JAGA as source of truth
            $jadwalJagaRecords = JadwalJaga::where('pegawai_id', $user->id)
                ->whereBetween('tanggal_jaga', [$startDate, $endDate])
                ->with(['shiftTemplate'])
                ->orderByDesc('tanggal_jaga')
                ->get();

            $historyData = $this->transformJadwalJagaToUnifiedFormat($jadwalJagaRecords, $dokter);

            return response()->json([
                'success' => true,
                'message' => 'History data loaded from JadwalJaga (source of truth)',
                'data' => $historyData,
                'meta' => [
                    'total_records' => count($historyData),
                    'data_source' => 'jadwal_jaga',
                    'real_time' => true,
                    'cached_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Unified history error', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today only (optimized for JadwalJaga tab)
     */
    public function getTodayOnly(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $dokter = Dokter::where('user_id', $user->id)->first();
            if (!$dokter) {
                return response()->json(['success' => false, 'message' => 'Dokter not found'], 404);
            }

            $todayData = $this->getTodayUnifiedData($user, $dokter);

            return response()->json([
                'success' => true,
                'message' => 'Today data loaded from JadwalJaga (real-time)',
                'data' => $todayData,
                'meta' => [
                    'data_source' => 'jadwal_jaga',
                    'real_time' => true,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load today data: ' . $e->getMessage()
            ], 500);
        }
    }
}