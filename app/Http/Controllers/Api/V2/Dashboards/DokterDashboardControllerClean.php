<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Dokter;
use App\Models\DokterPresensi;
use App\Models\JadwalJaga;
use App\Services\RecentAchievementsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Clean DokterDashboardController dengan DokterPresensi sebagai source untuk mobile app
 */
class DokterDashboardControllerClean extends Controller
{
    protected $achievementsService;

    public function __construct(RecentAchievementsService $achievementsService)
    {
        $this->achievementsService = $achievementsService;
    }

    /**
     * CLEAN: getPresensi method using DokterPresensi as primary data source
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
            
            // Get dokter record for this user
            $dokter = Dokter::where('user_id', $user->id)->first();
            
            if (!$dokter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data dokter tidak ditemukan untuk user ini',
                    'data' => [
                        'today' => null,
                        'today_records' => [],
                        'history' => [],
                        'stats' => $this->getEmptyStats()
                    ]
                ], 404);
            }
            
            Log::info('getPresensi called', [
                'user_id' => $user->id,
                'dokter_id' => $dokter->id,
                'date_range' => [
                    'start' => $request->get('start'),
                    'end' => $request->get('end')
                ]
            ]);
            
            // ✅ TODAY: Query DokterPresensi for today
            $todayRecords = DokterPresensi::where('dokter_id', $dokter->id)
                ->whereDate('tanggal', $today)
                ->orderBy('jam_masuk')
                ->get();
            
            $attendanceToday = $todayRecords->last();
            
            // ✅ HISTORY: Query DokterPresensi for history
            $historyQuery = DokterPresensi::where('dokter_id', $dokter->id)
                ->orderByDesc('tanggal');
            
            // Handle date range parameters
            if ($request->has('start') && $request->has('end')) {
                $startDate = Carbon::parse($request->get('start'));
                $endDate = Carbon::parse($request->get('end'));
                $historyQuery->whereBetween('tanggal', [$startDate, $endDate]);
            } else {
                // Default: last 90 days
                $startDate = Carbon::now()->subDays(90);
                $endDate = Carbon::now();
                $historyQuery->whereBetween('tanggal', [$startDate, $endDate]);
            }
            
            $historyQuery->whereNotNull('jam_masuk');
            $attendanceHistory = $historyQuery->get();
            
            Log::info('History query result', [
                'total_records' => $attendanceHistory->count(),
                'date_range' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
            ]);
            
            // ✅ PROCESS: Transform DokterPresensi records for mobile app
            $processedHistory = $attendanceHistory->map(function ($presensi) {
                $durationFormatted = '-';
                $durationMinutes = 0;
                
                if ($presensi->jam_masuk && $presensi->jam_pulang) {
                    $durationMinutes = $presensi->jam_masuk->diffInMinutes($presensi->jam_pulang);
                    $hours = floor($durationMinutes / 60);
                    $minutes = $durationMinutes % 60;
                    $durationFormatted = $hours . 'h ' . $minutes . 'm';
                }
                
                // Simple status determination
                $status = 'incomplete';
                if ($presensi->jam_masuk && $presensi->jam_pulang) {
                    $status = $durationMinutes >= 480 ? 'perfect' : 
                             ($durationMinutes >= 360 ? 'good' : 'late');
                }
                
                return [
                    'id' => $presensi->id,
                    'date' => $presensi->tanggal->format('d-n-y'), // ✅ UNIFIED: DD-MM-Y format to match mobile app (21-8-25)
                    'tanggal' => $presensi->tanggal->format('d/m/Y'), // Mobile format fallback
                    'day_name' => $presensi->tanggal->format('l'),
                    'full_date' => $presensi->tanggal->toISOString(),
                    
                    // ✅ DokterPresensi fields
                    'time_in' => $presensi->jam_masuk ? $presensi->jam_masuk->format('H:i') : null,
                    'time_out' => $presensi->jam_pulang ? $presensi->jam_pulang->format('H:i') : null,
                    'jam_masuk' => $presensi->jam_masuk ? $presensi->jam_masuk->format('H:i') : null,
                    'jam_pulang' => $presensi->jam_pulang ? $presensi->jam_pulang->format('H:i') : null,
                    
                    // Duration and status
                    'working_duration' => $durationFormatted,
                    'work_duration' => $durationFormatted,
                    'duration_minutes' => $durationMinutes,
                    'status' => $status,
                    'dynamic_status' => $presensi->jam_pulang ? 'Selesai' : 'Aktif',
                    
                    // Gaming elements
                    'points_earned' => $status === 'perfect' ? 150 : ($status === 'good' ? 100 : 50),
                    'achievement_badge' => $status === 'perfect' ? '🏆 PERFECT' : ($status === 'good' ? '⭐ GOOD' : '⚠️ LATE'),
                    
                    // Default shift info
                    'shift_info' => [
                        'shift_name' => 'Shift Dokter',
                        'shift_start' => '08:00',
                        'shift_end' => '16:00',
                        'shift_duration' => '8j 0m'
                    ],
                    
                    // Compatibility fields
                    'check_in_time' => $presensi->jam_masuk,
                    'check_out_time' => $presensi->jam_pulang,
                    'status_legacy' => $presensi->jam_pulang ? 'completed' : 'active',
                    'shortage_minutes' => max(0, 480 - $durationMinutes), // 8 hours target
                    'shortfall_minutes' => max(0, 480 - $durationMinutes),
                    'target_minutes' => 480,
                    'attendance_percentage' => $durationMinutes > 0 ? min(100, ($durationMinutes / 480) * 100) : 0
                ];
            });

            // ✅ TODAY DATA: Format today's record
            $todayData = $attendanceToday ? [
                'date' => $attendanceToday->tanggal->format('Y-m-d'),
                'time_in' => $attendanceToday->jam_masuk?->format('H:i'),
                'time_out' => $attendanceToday->jam_pulang?->format('H:i'),
                'status' => $attendanceToday->jam_pulang ? 'completed' : 'active',
                'work_duration' => $this->calculateDokterPresensiDuration($attendanceToday),
                'can_check_in' => !$attendanceToday->jam_masuk,
                'can_check_out' => $attendanceToday->jam_masuk && !$attendanceToday->jam_pulang
            ] : [
                'date' => $today->format('Y-m-d'),
                'time_in' => null,
                'time_out' => null,
                'status' => null,
                'work_duration' => null,
                'can_check_in' => true,
                'can_check_out' => false
            ];

            // ✅ TODAY RECORDS: Format all today records
            $todayRecordsFormatted = $todayRecords->map(function ($record) {
                return [
                    'id' => $record->id,
                    'time_in' => $record->jam_masuk?->format('H:i'),
                    'time_out' => $record->jam_pulang?->format('H:i'),
                    'status' => $record->jam_pulang ? 'completed' : 'active',
                ];
            })->values();

            // ✅ STATS: Calculate from processed history
            $stats = [
                'total_missions' => $processedHistory->count(),
                'perfect_missions' => $processedHistory->where('status', 'perfect')->count(),
                'good_missions' => $processedHistory->where('status', 'good')->count(),
                'late_missions' => $processedHistory->where('status', 'late')->count(),
                'incomplete_missions' => $processedHistory->where('status', 'incomplete')->count(),
                'total_xp' => $processedHistory->sum('points_earned'),
                'total_hours' => round($processedHistory->sum('duration_minutes') / 60, 1),
                'performance_rate' => $processedHistory->count() > 0 ? 
                    round(($processedHistory->whereIn('status', ['perfect', 'good'])->count() / $processedHistory->count()) * 100, 1) : 0,
                'attendance_rate' => 95.5 // Will be calculated properly later
            ];

            return response()->json([
                'success' => true,
                'message' => 'Data presensi berhasil dimuat dari DokterPresensi',
                'data' => [
                    'today' => $todayData,
                    'today_records' => $todayRecordsFormatted,
                    'history' => $processedHistory->values()->all(),
                    'stats' => $stats
                ],
                'meta' => [
                    'data_source' => 'dokter_presensi',
                    'total_records' => $processedHistory->count(),
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ],
                    'dokter_id' => $dokter->id,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getPresensi:', [
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
                    'file' => basename($e->getFile())
                ]
            ], 500);
        }
    }

    /**
     * Calculate duration for DokterPresensi record
     */
    protected function calculateDokterPresensiDuration($presensi): string
    {
        if (!$presensi || !$presensi->jam_masuk || !$presensi->jam_pulang) {
            return '0j 0m';
        }
        
        $durationMinutes = $presensi->jam_masuk->diffInMinutes($presensi->jam_pulang);
        $hours = floor($durationMinutes / 60);
        $minutes = $durationMinutes % 60;
        
        return $hours . 'j ' . $minutes . 'm';
    }

    /**
     * Get empty stats structure
     */
    protected function getEmptyStats(): array
    {
        return [
            'total_missions' => 0,
            'perfect_missions' => 0,
            'good_missions' => 0,
            'late_missions' => 0,
            'incomplete_missions' => 0,
            'total_xp' => 0,
            'total_hours' => 0,
            'performance_rate' => 0,
            'attendance_rate' => 0
        ];
    }
}