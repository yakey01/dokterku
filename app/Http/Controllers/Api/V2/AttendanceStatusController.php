<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Attendance;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Attendance Status",
 *     description="Attendance status checking endpoints"
 * )
 */
class AttendanceStatusController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/paramedis/attendance/status",
     *     summary="Get current attendance status for the authenticated user",
     *     tags={"Attendance Status"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Attendance status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="has_checked_in", type="boolean", example=true),
     *                 @OA\Property(property="has_checked_out", type="boolean", example=false),
     *                 @OA\Property(property="can_check_in", type="boolean", example=false),
     *                 @OA\Property(property="can_check_out", type="boolean", example=true),
     *                 @OA\Property(property="attendance", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="date", type="string", format="date", example="2025-01-15"),
     *                     @OA\Property(property="time_in", type="string", format="time", example="08:00:00"),
     *                     @OA\Property(property="time_out", type="string", format="time", nullable=true, example=null),
     *                     @OA\Property(property="status", type="string", example="present"),
     *                     @OA\Property(property="work_duration", type="string", nullable=true, example="8 hours 30 minutes")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="date", type="string", format="date", example="2025-01-15"),
     *                 @OA\Property(property="timestamp", type="string", format="datetime", example="2025-01-15T10:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        
        $hasCheckedIn = $attendance ? true : false;
        $hasCheckedOut = $attendance && $attendance->time_out ? true : false;
        
        $responseData = [
            'has_checked_in' => $hasCheckedIn,
            'has_checked_out' => $hasCheckedOut,
            'can_check_in' => !$hasCheckedIn,
            'can_check_out' => $hasCheckedIn && !$hasCheckedOut,
            'attendance' => $this->formatAttendanceData($attendance)
        ];
        
        return response()->json([
            'success' => true,
            'data' => $responseData,
            'meta' => [
                'date' => $today->format('Y-m-d'),
                'timestamp' => now()->toISOString(),
                'user_id' => $user->id
            ]
        ]);
    }

    /**
     * Format attendance data for API response
     */
    private function formatAttendanceData(?Attendance $attendance): ?array
    {
        if (!$attendance) {
            return null;
        }
        
        return [
            'id' => $attendance->id,
            'date' => $attendance->date->format('Y-m-d'),
            'time_in' => $attendance->time_in,
            'time_out' => $attendance->time_out,
            'status' => $attendance->status,
            'work_duration' => $attendance->formatted_work_duration,
            'location' => [
                'check_in_lat' => $attendance->check_in_lat,
                'check_in_lng' => $attendance->check_in_lng,
                'check_out_lat' => $attendance->check_out_lat,
                'check_out_lng' => $attendance->check_out_lng,
            ],
            'notes' => $attendance->notes
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/dashboards/paramedis/attendance/status",
     *     summary="Get attendance status for paramedis dashboard",
     *     tags={"Attendance Status"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard attendance status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="today_status", type="string", example="checked_in"),
     *                 @OA\Property(property="this_week_attendance", type="integer", example=4),
     *                 @OA\Property(property="this_month_attendance", type="integer", example=18),
     *                 @OA\Property(property="attendance_percentage", type="number", example=90.5)
     *             )
     *         )
     *     )
     * )
     */
    public function dashboardStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Today's attendance
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        
        $todayStatus = 'not_checked_in';
        if ($todayAttendance) {
            $todayStatus = $todayAttendance->time_out ? 'checked_out' : 'checked_in';
        }
        
        // Weekly attendance count
        $weekAttendance = Attendance::where('user_id', $user->id)
            ->where('date', '>=', $thisWeek)
            ->count();
        
        // Monthly attendance count
        $monthAttendance = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $thisMonth->month)
            ->whereYear('date', $thisMonth->year)
            ->count();
        
        // Calculate attendance percentage (based on working days)
        $workingDaysThisMonth = Carbon::now()->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday();
        }, $thisMonth);
        
        $attendancePercentage = $workingDaysThisMonth > 0 
            ? round(($monthAttendance / $workingDaysThisMonth) * 100, 1) 
            : 0;
        
        return response()->json([
            'success' => true,
            'data' => [
                'today_status' => $todayStatus,
                'today_attendance' => $this->formatAttendanceData($todayAttendance),
                'this_week_attendance' => $weekAttendance,
                'this_month_attendance' => $monthAttendance,
                'attendance_percentage' => $attendancePercentage,
                'working_days_this_month' => $workingDaysThisMonth
            ],
            'meta' => [
                'date' => $today->format('Y-m-d'),
                'timestamp' => now()->toISOString()
            ]
        ]);
    }
}