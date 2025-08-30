<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\AttendanceHistoryPenaltyService;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller untuk demonstrasi logic penalty attendance
 * Khusus untuk skenario Dr. Yaya - check in tanpa check out
 */
class AttendancePenaltyController extends Controller
{
    protected AttendanceHistoryPenaltyService $penaltyService;
    
    public function __construct(AttendanceHistoryPenaltyService $penaltyService)
    {
        $this->penaltyService = $penaltyService;
    }
    
    /**
     * Endpoint untuk demo skenario Dr. Yaya
     * 
     * @OA\Get(
     *     path="/api/v2/attendance/penalty/demo-yaya-scenario",
     *     summary="Demo skenario Dr. Yaya dengan penalty 1 menit",
     *     tags={"Attendance Penalty Demo"},
     *     @OA\Response(
     *         response=200,
     *         description="Demo scenario details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="scenario", type="object",
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="schedule", type="string"),
     *                 @OA\Property(property="check_in_status", type="string"),
     *                 @OA\Property(property="check_out_status", type="string"),
     *                 @OA\Property(property="penalty_applied", type="boolean"),
     *                 @OA\Property(property="work_duration", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function demoYayaScenario(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Demo skenario Dr. Yaya - Logic penalty 1 menit untuk incomplete checkout',
            'scenario' => [
                'description' => 'Dr. Yaya punya jadwal jaga hari ini, sudah check in tapi tidak check out dan melewati batas toleransi',
                'schedule' => [
                    'shift_name' => 'Evening Clinic',
                    'start_time' => '17:30',
                    'end_time' => '18:30',
                    'duration' => '1 jam'
                ],
                'timeline' => [
                    '17:30' => 'Dr. Yaya check-in berhasil',
                    '18:30' => 'Shift berakhir (seharusnya check-out)',
                    '19:30' => 'Batas toleransi terlewati (60 menit setelah shift)',
                    '20:00+' => 'System auto-close dengan penalty 1 menit'
                ],
                'penalty_logic' => [
                    'trigger' => 'Current time > (shift_end + tolerance)',
                    'auto_checkout_time' => 'check_in_time + 1 minute',
                    'work_duration' => '1 menit',
                    'reason' => 'Exceeded checkout tolerance'
                ],
                'history_result' => [
                    'date' => date('Y-m-d'),
                    'check_in' => '17:30',
                    'check_out' => '17:31 (auto)',
                    'work_duration' => '1 menit',
                    'status' => 'Penalty Applied',
                    'notes' => 'Auto-closed: Exceeded checkout tolerance (1 minute work time penalty)'
                ]
            ],
            'implementation_info' => [
                'command' => 'php artisan attendance:auto-close',
                'service' => 'AutoCloseAttendanceCommand',
                'models' => ['Attendance', 'AttendanceToleranceSetting'],
                'cron_schedule' => 'Runs every hour to check tolerance violations'
            ]
        ]);
    }
    
    /**
     * Get penalty attendance history for specific user
     * 
     * @OA\Get(
     *     path="/api/v2/attendance/penalty/history/{userId}",
     *     summary="Get penalty attendance history",
     *     tags={"Attendance Penalty Demo"},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Penalty attendance history",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="penalty_history", type="array")
     *         )
     *     )
     * )
     */
    public function getPenaltyHistory(int $userId): JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        $penaltyHistory = $this->penaltyService->getPenaltyAttendanceHistory($userId);
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role
            ],
            'penalty_history' => $penaltyHistory,
            'summary' => [
                'total_penalty_days' => count($penaltyHistory),
                'total_penalty_minutes' => array_sum(array_column($penaltyHistory, 'work_duration_minutes')),
                'most_common_reason' => $this->getMostCommonPenaltyReason($penaltyHistory)
            ]
        ]);
    }
    
    /**
     * Apply penalty logic to specific attendance
     * 
     * @OA\Post(
     *     path="/api/v2/attendance/penalty/apply/{attendanceId}",
     *     summary="Apply penalty logic to attendance",
     *     tags={"Attendance Penalty Demo"},
     *     @OA\Parameter(
     *         name="attendanceId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Penalty applied successfully"
     *     )
     * )
     */
    public function applyPenalty(int $attendanceId): JsonResponse
    {
        $result = $this->penaltyService->handleToleranceExceededScenario($attendanceId);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'penalty_details' => $result['data'] ?? null
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }
    
    /**
     * Get current attendance status with penalty risk
     */
    public function getAttendanceWithPenaltyRisk(Request $request): JsonResponse
    {
        $userId = $request->user()->id ?? $request->get('user_id');
        if (!$userId) {
            return response()->json(['error' => 'User ID required'], 400);
        }
        
        // Find today's incomplete attendance
        $today = now('Asia/Jakarta')->toDateString();
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $today)
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->with(['jadwalJaga.shiftTemplate'])
            ->first();
        
        if (!$attendance) {
            return response()->json([
                'success' => true,
                'message' => 'No active attendance found',
                'has_penalty_risk' => false
            ]);
        }
        
        $user = User::find($userId);
        $penaltyRisk = $this->calculatePenaltyRisk($attendance, $user);
        
        return response()->json([
            'success' => true,
            'attendance' => [
                'id' => $attendance->id,
                'date' => $attendance->date,
                'time_in' => $attendance->time_in,
                'shift_schedule' => $this->getShiftInfo($attendance)
            ],
            'penalty_risk' => $penaltyRisk,
            'recommendations' => $this->getPenaltyPreventionRecommendations($penaltyRisk)
        ]);
    }
    
    /**
     * Calculate penalty risk for current attendance
     */
    protected function calculatePenaltyRisk(Attendance $attendance, User $user): array
    {
        $now = now('Asia/Jakarta');
        $checkInTime = \Carbon\Carbon::parse($attendance->date . ' ' . $attendance->time_in, 'Asia/Jakarta');
        
        // Determine shift end time
        $shiftEndTime = null;
        if ($attendance->shift_end) {
            $shiftEndTime = \Carbon\Carbon::parse($attendance->date . ' ' . $attendance->shift_end, 'Asia/Jakarta');
        } elseif ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
            $shiftEndTime = \Carbon\Carbon::parse($attendance->date . ' ' . $attendance->jadwalJaga->shiftTemplate->jam_pulang, 'Asia/Jakarta');
        } else {
            $shiftEndTime = $checkInTime->copy()->addHours(8);
        }
        
        // Get tolerance
        $toleranceData = app(AttendanceToleranceService::class)->getCheckoutTolerance($user, $attendance->date);
        $lateTolerance = $toleranceData['late'] ?? 60;
        
        $maxCheckoutTime = $shiftEndTime->copy()->addMinutes($lateTolerance);
        
        // Calculate risk
        $hasExceeded = $now->gt($maxCheckoutTime);
        $minutesToPenalty = $hasExceeded ? 0 : $now->diffInMinutes($maxCheckoutTime);
        $minutesExceeded = $hasExceeded ? $now->diffInMinutes($maxCheckoutTime) : 0;
        
        $riskLevel = $hasExceeded ? 'CRITICAL' : 
                    ($minutesToPenalty <= 30 ? 'HIGH' : 
                    ($minutesToPenalty <= 60 ? 'MEDIUM' : 'LOW'));
        
        return [
            'has_risk' => $minutesToPenalty <= 120 || $hasExceeded,
            'risk_level' => $riskLevel,
            'has_exceeded_tolerance' => $hasExceeded,
            'minutes_to_penalty' => $minutesToPenalty,
            'minutes_exceeded' => $minutesExceeded,
            'shift_end_time' => $shiftEndTime->format('H:i'),
            'max_checkout_time' => $maxCheckoutTime->format('H:i'),
            'tolerance_minutes' => $lateTolerance,
            'current_time' => $now->format('H:i')
        ];
    }
    
    /**
     * Get shift information from attendance
     */
    protected function getShiftInfo(Attendance $attendance): array
    {
        if ($attendance->jadwalJaga && $attendance->jadwalJaga->shiftTemplate) {
            $shift = $attendance->jadwalJaga->shiftTemplate;
            return [
                'shift_name' => $shift->nama_shift,
                'start_time' => \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i'),
                'end_time' => \Carbon\Carbon::parse($shift->jam_pulang)->format('H:i')
            ];
        }
        
        return [
            'shift_name' => 'Default Shift',
            'start_time' => $attendance->shift_start ?? '08:00',
            'end_time' => $attendance->shift_end ?? '16:00'
        ];
    }
    
    /**
     * Get recommendations to prevent penalty
     */
    protected function getPenaltyPreventionRecommendations(array $penaltyRisk): array
    {
        if (!$penaltyRisk['has_risk']) {
            return ['message' => 'No immediate penalty risk'];
        }
        
        $recommendations = [];
        
        if ($penaltyRisk['has_exceeded_tolerance']) {
            $recommendations[] = '🚨 URGENT: Anda sudah melewati batas toleransi ' . $penaltyRisk['minutes_exceeded'] . ' menit';
            $recommendations[] = '⚡ System akan otomatis close attendance dengan penalty 1 menit';
            $recommendations[] = '📞 Hubungi admin untuk approval manual jika diperlukan';
        } else {
            switch ($penaltyRisk['risk_level']) {
                case 'HIGH':
                    $recommendations[] = '⚠️ Segera check-out! Hanya tersisa ' . $penaltyRisk['minutes_to_penalty'] . ' menit';
                    $recommendations[] = '🏃‍♂️ Penalty akan aktif pada ' . $penaltyRisk['max_checkout_time'];
                    break;
                case 'MEDIUM':
                    $recommendations[] = '⏰ Mulai bersiap untuk check-out';
                    $recommendations[] = '📅 Batas toleransi: ' . $penaltyRisk['max_checkout_time'];
                    break;
                case 'LOW':
                    $recommendations[] = '✅ Masih dalam batas aman';
                    $recommendations[] = '📋 Check-out sebelum ' . $penaltyRisk['max_checkout_time'];
                    break;
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Get most common penalty reason
     */
    protected function getMostCommonPenaltyReason(array $penaltyHistory): string
    {
        if (empty($penaltyHistory)) {
            return 'No penalty history';
        }
        
        $reasons = array_column($penaltyHistory, 'penalty_info');
        $reasonCounts = [];
        
        foreach ($reasons as $reason) {
            $reasonText = $reason['penalty_reason'] ?? 'Unknown';
            $reasonCounts[$reasonText] = ($reasonCounts[$reasonText] ?? 0) + 1;
        }
        
        return array_keys($reasonCounts)[0] ?? 'Unknown';
    }
}