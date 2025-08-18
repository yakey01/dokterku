<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\UnifiedAttendanceService;
use Carbon\Carbon;

class DokterStatsController extends Controller
{
    /**
     * Get dokter dashboard statistics
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            Log::info('Dokter stats API called');
            
            // Get basic stats using unified attendance service
            $stats = $this->getDashboardStats();
            
            // Get performance data
            $performanceData = $this->getPerformanceData();
            
            // Get recent activities
            $recentActivities = $this->getRecentActivities();
            
            $response = [
                'success' => true,
                'data' => [
                    'attendance_current' => $stats['attendance_current'],
                    'attendance_rate' => $stats['attendance_rate'],
                    'performance_data' => $performanceData,
                    'patients_today' => $stats['patients_today'],
                    'patients_week' => $stats['patients_week'],
                    'patients_month' => $stats['patients_month'],
                    'revenue_today' => $stats['revenue_today'],
                    'revenue_week' => $stats['revenue_week'],
                    'revenue_month' => $stats['revenue_month'],
                    'recent_activities' => $recentActivities,
                ],
                'meta' => [
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'version' => '1.0.0'
                ]
            ];
            
            Log::info('Dokter stats API success', ['stats_count' => count($stats)]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Dokter stats API error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return fallback data to prevent frontend crashes
            return response()->json([
                'success' => false,
                'message' => 'Error loading stats',
                'data' => $this->getFallbackStats(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        
        try {
            // Attendance stats using unified service
            $user = Auth::user();
            $unifiedAttendanceService = new UnifiedAttendanceService();
            $attendanceRate = $user ? $unifiedAttendanceService->calculateAttendanceRate($user->id, $today->month, $today->year) : 0;
            $attendanceStats = ['current' => 1, 'rate' => $attendanceRate]; // Simplified
            
            // Patient stats from tindakan
            $patientStats = DB::table('tindakan')
                ->selectRaw('
                    COUNT(CASE WHEN DATE(tanggal_tindakan) = ? THEN 1 END) as today_patients,
                    COUNT(CASE WHEN DATE(tanggal_tindakan) >= ? THEN 1 END) as week_patients,
                    COUNT(CASE WHEN DATE(tanggal_tindakan) >= ? THEN 1 END) as month_patients
                ')
                ->where('dokter_id', '!=', null)
                ->addBinding([$today->format('Y-m-d'), $weekStart->format('Y-m-d'), $monthStart->format('Y-m-d')])
                ->first();
            
            // Revenue stats
            $revenueStats = DB::table('tindakan')
                ->selectRaw('
                    COALESCE(SUM(CASE WHEN DATE(tanggal_tindakan) = ? THEN jasa_dokter END), 0) as today_revenue,
                    COALESCE(SUM(CASE WHEN DATE(tanggal_tindakan) >= ? THEN jasa_dokter END), 0) as week_revenue,
                    COALESCE(SUM(CASE WHEN DATE(tanggal_tindakan) >= ? THEN jasa_dokter END), 0) as month_revenue
                ')
                ->where('dokter_id', '!=', null)
                ->addBinding([$today->format('Y-m-d'), $weekStart->format('Y-m-d'), $monthStart->format('Y-m-d')])
                ->first();
            
            return [
                'attendance_current' => $attendanceStats['current'],
                'attendance_rate' => $attendanceStats['rate'],
                'patients_today' => $patientStats->today_patients ?? 0,
                'patients_week' => $patientStats->week_patients ?? 0,
                'patients_month' => $patientStats->month_patients ?? 0,
                'revenue_today' => $revenueStats->today_revenue ?? 0,
                'revenue_week' => $revenueStats->week_revenue ?? 0,
                'revenue_month' => $revenueStats->month_revenue ?? 0,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting dashboard stats: ' . $e->getMessage());
            return $this->getFallbackStats()['data'];
        }
    }
    
    /**
     * Get attendance statistics
     */
    private function getAttendanceStats($today): array
    {
        try {
            // Check if kehadiran table exists
            $tableExists = DB::select("SHOW TABLES LIKE 'kehadiran'");
            
            if (empty($tableExists)) {
                return ['current' => 0, 'rate' => 0];
            }
            
            // Get attendance count for today
            $attendanceToday = DB::table('kehadiran as k')
                ->join('dokters as d', 'k.user_id', '=', 'd.user_id')
                ->whereDate('k.tanggal', $today)
                ->count();
            
            // Get total active dokters
            $totalDokters = DB::table('dokters')
                ->where('status_akun', 'Aktif')
                ->count();
            
            $rate = $totalDokters > 0 ? round(($attendanceToday / $totalDokters) * 100, 1) : 0;
            
            return [
                'current' => $attendanceToday,
                'rate' => $rate
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting attendance stats: ' . $e->getMessage());
            return ['current' => 0, 'rate' => 0];
        }
    }
    
    /**
     * Get performance trend data
     */
    private function getPerformanceData(): array
    {
        try {
            $attendanceTrend = [];
            $patientTrend = [];
            
            // Generate 7 days of trend data
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dateStr = $date->format('Y-m-d');
                
                // Attendance trend
                $attendanceCount = 0;
                $tableExists = DB::select("SHOW TABLES LIKE 'kehadiran'");
                
                if (!empty($tableExists)) {
                    $attendanceCount = DB::table('kehadiran as k')
                        ->join('dokters as d', 'k.user_id', '=', 'd.user_id')
                        ->whereDate('k.tanggal', $date)
                        ->count();
                }
                
                $totalDokters = DB::table('dokters')->where('status_akun', 'Aktif')->count();
                $attendanceRate = $totalDokters > 0 ? round(($attendanceCount / $totalDokters) * 100, 1) : 0;
                
                $attendanceTrend[] = [
                    'date' => $dateStr,
                    'value' => $attendanceRate
                ];
                
                // Patient trend
                $patientCount = DB::table('tindakan')
                    ->whereDate('tanggal_tindakan', $date)
                    ->where('dokter_id', '!=', null)
                    ->count();
                
                $patientTrend[] = [
                    'date' => $dateStr,
                    'value' => $patientCount
                ];
            }
            
            return [
                'attendance_trend' => $attendanceTrend,
                'patient_trend' => $patientTrend
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting performance data: ' . $e->getMessage());
            return [
                'attendance_trend' => [],
                'patient_trend' => []
            ];
        }
    }
    
    /**
     * Get recent activities
     */
    private function getRecentActivities(): array
    {
        try {
            $activities = DB::table('tindakan as t')
                ->join('pasien as p', 't.pasien_id', '=', 'p.id')
                ->join('jenis_tindakan as jt', 't.jenis_tindakan_id', '=', 'jt.id')
                ->leftJoin('dokters as d', 't.dokter_id', '=', 'd.id')
                ->select([
                    't.id',
                    'p.nama_pasien',
                    'jt.nama_tindakan',
                    'd.nama_lengkap as dokter_nama',
                    't.tanggal_tindakan',
                    't.status_validasi',
                    't.created_at'
                ])
                ->where('t.dokter_id', '!=', null)
                ->orderBy('t.created_at', 'desc')
                ->limit(5)
                ->get();
            
            return $activities->map(function ($activity) {
                return [
                    'type' => 'tindakan',
                    'description' => "{$activity->nama_tindakan} - {$activity->nama_pasien}",
                    'dokter' => $activity->dokter_nama,
                    'time' => Carbon::parse($activity->tanggal_tindakan)->format('H:i'),
                    'date' => Carbon::parse($activity->tanggal_tindakan)->format('d/m/Y'),
                    'status' => $activity->status_validasi
                ];
            })->toArray();
            
        } catch (\Exception $e) {
            Log::error('Error getting recent activities: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get fallback stats when errors occur
     */
    private function getFallbackStats(): array
    {
        return [
            'success' => true,
            'data' => [
                'attendance_current' => 0,
                'attendance_rate' => 0,
                'performance_data' => [
                    'attendance_trend' => [],
                    'patient_trend' => []
                ],
                'patients_today' => 0,
                'patients_week' => 0,
                'patients_month' => 0,
                'revenue_today' => 0,
                'revenue_week' => 0,
                'revenue_month' => 0,
                'recent_activities' => []
            ],
            'meta' => [
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'fallback' => true
            ]
        ];
    }
}