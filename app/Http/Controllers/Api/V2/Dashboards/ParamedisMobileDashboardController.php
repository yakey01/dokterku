<?php

namespace App\Http\Controllers\Api\V2\Dashboards;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Pegawai;
use App\Models\Jaspel;
use App\Models\Tindakan;
use App\Models\JadwalJaga;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Paramedis Mobile Dashboard",
 *     description="Mobile dashboard endpoints for paramedis users"
 * )
 */
class ParamedisMobileDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/paramedis/dashboard",
     *     summary="Get paramedis mobile dashboard data",
     *     tags={"Paramedis Mobile Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="name", type="string", example="Dr. John Doe"),
     *                     @OA\Property(property="role", type="string", example="Paramedis")
     *                 ),
     *                 @OA\Property(property="jaspel", type="object",
     *                     @OA\Property(property="monthly", type="number", example=2500000),
     *                     @OA\Property(property="weekly", type="number", example=625000),
     *                     @OA\Property(property="approved", type="number", example=2000000),
     *                     @OA\Property(property="pending", type="number", example=500000)
     *                 ),
     *                 @OA\Property(property="attendance", type="object",
     *                     @OA\Property(property="shifts_this_month", type="integer", example=15),
     *                     @OA\Property(property="today", type="object", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Not authenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paramedis data not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Paramedis data not found")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        // Get paramedis data
        $paramedis = Pegawai::where('user_id', $user->id)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();
        
        if (!$paramedis) {
            return response()->json(['error' => 'Paramedis data not found'], 404);
        }
        
        $dashboardData = $this->buildDashboardData($user, $paramedis);
        
        // Log dashboard access
        Log::info('Paramedis dashboard accessed', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'jaspel_monthly' => $dashboardData['jaspel']['monthly'],
            'controller' => self::class
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $dashboardData
        ]);
    }

    /**
     * Build comprehensive dashboard data
     */
    private function buildDashboardData($user, $paramedis): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisWeek = Carbon::now()->startOfWeek();
        
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'Paramedis',
                'paramedis_id' => $paramedis->id
            ],
            'jaspel' => $this->calculateJaspelData($user, $paramedis, $thisMonth, $thisWeek),
            'attendance' => $this->getAttendanceData($user, $today, $thisMonth),
            'quick_stats' => $this->getQuickStats($user, $paramedis, $thisMonth),
            'meta' => [
                'generated_at' => now()->toISOString(),
                'version' => '2.0',
                'controller' => 'ParamedisMobileDashboardController'
            ]
        ];
    }

    /**
     * Calculate comprehensive Jaspel data
     */
    private function calculateJaspelData($user, $paramedis, $thisMonth, $thisWeek): array
    {
        // Monthly Jaspel from Jaspel model
        $jaspelMonthly = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $thisMonth->month)
            ->whereYear('tanggal', $thisMonth->year)
            ->whereIn('status_validasi', ['disetujui', 'approved'])
            ->sum('nominal');
        
        // Weekly Jaspel
        $jaspelWeekly = Jaspel::where('user_id', $user->id)
            ->where('tanggal', '>=', $thisWeek)
            ->whereIn('status_validasi', ['disetujui', 'approved'])
            ->sum('nominal');
        
        // Approved Jaspel
        $approvedJaspel = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $thisMonth->month)
            ->whereYear('tanggal', $thisMonth->year)
            ->whereIn('status_validasi', ['disetujui', 'approved'])
            ->sum('nominal');
        
        // Calculate pending Jaspel from multiple sources
        $pendingJaspel = $this->calculatePendingJaspel($user, $paramedis, $thisMonth);
        
        return [
            'monthly' => (float) $jaspelMonthly,
            'weekly' => (float) $jaspelWeekly,
            'approved' => (float) $approvedJaspel,
            'pending' => (float) $pendingJaspel,
            'total_potential' => (float) ($approvedJaspel + $pendingJaspel)
        ];
    }

    /**
     * Calculate pending Jaspel from multiple sources
     */
    private function calculatePendingJaspel($user, $paramedis, $thisMonth): float
    {
        // 1. Existing Jaspel records with pending status
        $pendingJaspelRecords = Jaspel::where('user_id', $user->id)
            ->whereMonth('tanggal', $thisMonth->month)
            ->whereYear('tanggal', $thisMonth->year)
            ->where('status_validasi', 'pending')
            ->sum('nominal');
        
        // 2. Approved Tindakan that haven't generated Jaspel records yet
        $pendingFromTindakan = Tindakan::where('paramedis_id', $paramedis->id)
            ->whereMonth('tanggal_tindakan', $thisMonth->month)
            ->whereYear('tanggal_tindakan', $thisMonth->year)
            ->whereIn('status_validasi', ['approved', 'disetujui'])
            ->whereDoesntHave('jaspel', function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('jenis_jaspel', 'paramedis');
            })
            ->where('jasa_paramedis', '>', 0)
            ->sum('jasa_paramedis');
        
        // Total pending = existing pending + paramedis portion (15%)
        return $pendingJaspelRecords + ($pendingFromTindakan * 0.15);
    }

    /**
     * Get attendance data
     */
    private function getAttendanceData($user, $today, $thisMonth): array
    {
        $shiftsThisMonth = JadwalJaga::where('pegawai_id', $user->id)
            ->whereMonth('tanggal_jaga', $thisMonth->month)
            ->count();
        
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        
        return [
            'shifts_this_month' => $shiftsThisMonth,
            'today' => $todayAttendance ? [
                'id' => $todayAttendance->id,
                'date' => $todayAttendance->date->format('Y-m-d'),
                'time_in' => $todayAttendance->time_in,
                'time_out' => $todayAttendance->time_out,
                'status' => $todayAttendance->status,
                'work_duration' => $todayAttendance->formatted_work_duration,
            ] : null
        ];
    }

    /**
     * Get quick statistics
     */
    private function getQuickStats($user, $paramedis, $thisMonth): array
    {
        $totalTindakan = Tindakan::where('paramedis_id', $paramedis->id)
            ->whereMonth('tanggal_tindakan', $thisMonth->month)
            ->count();
        
        $approvedTindakan = Tindakan::where('paramedis_id', $paramedis->id)
            ->whereMonth('tanggal_tindakan', $thisMonth->month)
            ->whereIn('status_validasi', ['approved', 'disetujui'])
            ->count();
        
        return [
            'total_tindakan' => $totalTindakan,
            'approved_tindakan' => $approvedTindakan,
            'pending_tindakan' => $totalTindakan - $approvedTindakan,
            'approval_rate' => $totalTindakan > 0 ? round(($approvedTindakan / $totalTindakan) * 100, 1) : 0
        ];
    }
}