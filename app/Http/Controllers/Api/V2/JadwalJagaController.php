<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\JadwalJaga;
use App\Models\WorkLocation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Jadwal Jaga",
 *     description="Doctor schedule management with work location integration"
 * )
 */
class JadwalJagaController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/jadwal-jaga/today",
     *     summary="Get today's jadwal jaga for authenticated user",
     *     tags={"Jadwal Jaga"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Date in Y-m-d format (optional, defaults to today)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-08-03")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Today's jadwal jaga retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="No schedule found for today")
     * )
     */
    public function today(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $date = $request->get('date', now()->format('Y-m-d'));
        
        try {
            $targetDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid date format. Use Y-m-d format.', 400);
        }

        // Find jadwal jaga for the user on the specified date
        // Note: jadwal_jagas.pegawai_id directly references users.id per DB schema
        $jadwalJaga = JadwalJaga::whereDate('tanggal_jaga', $targetDate)
            ->where('pegawai_id', $user->id)  // Correct: Use user->id as per DB schema
            ->with(['shiftTemplate', 'pegawai'])
            ->first();

        if (!$jadwalJaga) {
            return $this->errorResponse('No schedule found for ' . $targetDate->format('d M Y'), 404);
        }

        // Try to find associated work location
        $workLocation = null;
        
        // Look for work location based on unit_kerja or other criteria
        if ($jadwalJaga->unit_kerja) {
            $workLocation = WorkLocation::where('unit_kerja', $jadwalJaga->unit_kerja)
                ->where('is_active', true)
                ->first();
        }
        
        // If no specific work location found, get default one
        if (!$workLocation) {
            $workLocation = WorkLocation::where('location_type', 'main_office')
                ->where('is_active', true)
                ->first();
        }

        $response = [
            'id' => $jadwalJaga->id,
            'tanggal_jaga' => $jadwalJaga->tanggal_jaga->format('Y-m-d'),
            'shift_template_id' => $jadwalJaga->shift_template_id,
            'pegawai_id' => $jadwalJaga->pegawai_id,
            'unit_kerja' => $jadwalJaga->unit_kerja,
            'peran' => $jadwalJaga->peran,
            'status_jaga' => $jadwalJaga->status_jaga,
            'keterangan' => $jadwalJaga->keterangan,
            'effective_start_time' => $jadwalJaga->effective_start_time,
            'effective_end_time' => $jadwalJaga->effective_end_time,
            'shift_template' => $jadwalJaga->shiftTemplate ? [
                'id' => $jadwalJaga->shiftTemplate->id,
                'nama_shift' => $jadwalJaga->shiftTemplate->nama_shift,
                'jam_masuk' => $jadwalJaga->shiftTemplate->jam_masuk,
                'jam_pulang' => $jadwalJaga->shiftTemplate->jam_pulang,
                'durasi_jam' => $jadwalJaga->shiftTemplate->durasi_jam,
                'warna' => $jadwalJaga->shiftTemplate->warna ?? '#3b82f6',
            ] : null,
            'work_location' => $workLocation ? [
                'id' => $workLocation->id,
                'name' => $workLocation->name,
                'description' => $workLocation->description,
                'address' => $workLocation->address,
                'latitude' => (float) $workLocation->latitude,
                'longitude' => (float) $workLocation->longitude,
                'radius_meters' => $workLocation->radius_meters,
                'location_type' => $workLocation->location_type,
                'location_type_label' => $workLocation->location_type_label,
                // Tolerance settings from WorkLocation
                'late_tolerance_minutes' => $workLocation->late_tolerance_minutes ?? 15,
                'early_departure_tolerance_minutes' => $workLocation->early_departure_tolerance_minutes ?? 15,
                'break_time_minutes' => $workLocation->break_time_minutes ?? 60,
                'overtime_threshold_minutes' => $workLocation->overtime_threshold_minutes ?? 480,
                'checkin_before_shift_minutes' => $workLocation->checkin_before_shift_minutes ?? 30,
                'checkout_after_shift_minutes' => $workLocation->checkout_after_shift_minutes ?? 60,
                'require_photo' => $workLocation->require_photo,
                'strict_geofence' => $workLocation->strict_geofence,
                'gps_accuracy_required' => $workLocation->gps_accuracy_required ?? 50,
            ] : null,
            'can_check_in' => $this->canCheckIn($jadwalJaga),
            'can_check_out' => $this->canCheckOut($jadwalJaga),
            'timing_info' => $this->getTimingInfo($jadwalJaga, $workLocation),
        ];

        return $this->successResponse($response, 'Today\'s schedule retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/jadwal-jaga/week",
     *     summary="Get week schedule for authenticated user",
     *     tags={"Jadwal Jaga"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="week_start",
     *         in="query",
     *         description="Week start date in Y-m-d format (optional, defaults to current week)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-08-03")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Week schedule retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function week(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        
        try {
            $weekStart = $request->get('week_start') 
                ? Carbon::parse($request->get('week_start'))->startOfWeek()
                : Carbon::now()->startOfWeek();
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid date format. Use Y-m-d format.', 400);
        }

        $weekEnd = $weekStart->copy()->endOfWeek();

        // Get all jadwal jaga for the week  
        // Note: jadwal_jagas.pegawai_id directly references users.id per DB schema
        $jadwalJagas = JadwalJaga::whereBetween('tanggal_jaga', [$weekStart, $weekEnd])
            ->where('pegawai_id', $user->id)  // Correct: Use user->id as per DB schema
            ->with(['shiftTemplate', 'pegawai'])
            ->orderBy('tanggal_jaga')
            ->get();

        $weekSchedule = [];
        
        foreach ($jadwalJagas as $jadwal) {
            // Find work location for each schedule
            $workLocation = WorkLocation::where('unit_kerja', $jadwal->unit_kerja)
                ->where('is_active', true)
                ->first();
                
            if (!$workLocation) {
                $workLocation = WorkLocation::where('location_type', 'main_office')
                    ->where('is_active', true)
                    ->first();
            }

            $weekSchedule[] = [
                'id' => $jadwal->id,
                'date' => $jadwal->tanggal_jaga->format('Y-m-d'),
                'day_name' => $jadwal->tanggal_jaga->format('l'),
                'unit_kerja' => $jadwal->unit_kerja,
                'peran' => $jadwal->peran,
                'status_jaga' => $jadwal->status_jaga,
                'shift_template' => $jadwal->shiftTemplate ? [
                    'nama_shift' => $jadwal->shiftTemplate->nama_shift,
                    'jam_masuk' => $jadwal->shiftTemplate->jam_masuk,
                    'jam_pulang' => $jadwal->shiftTemplate->jam_pulang,
                    'warna' => $jadwal->shiftTemplate->warna ?? '#3b82f6',
                ] : null,
                'work_location' => $workLocation ? [
                    'name' => $workLocation->name,
                    'address' => $workLocation->address,
                ] : null,
                'is_today' => $jadwal->tanggal_jaga->isToday(),
            ];
        }

        return $this->successResponse([
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'schedules' => $weekSchedule,
            'total_schedules' => count($weekSchedule),
        ], 'Week schedule retrieved successfully');
    }

    /**
     * Check if user can check in for this jadwal
     */
    private function canCheckIn(JadwalJaga $jadwal): bool
    {
        // Basic checks
        if ($jadwal->status_jaga !== 'Aktif') {
            return false;
        }

        // Check if it's the right day
        if (!$jadwal->tanggal_jaga->isToday()) {
            return false;
        }

        // Check if already checked in today (basic implementation)
        // This should be expanded to check actual attendance records
        return true;
    }

    /**
     * Check if user can check out for this jadwal
     */
    private function canCheckOut(JadwalJaga $jadwal): bool
    {
        // Basic checks
        if ($jadwal->status_jaga !== 'Aktif') {
            return false;
        }

        // Check if it's the right day
        if (!$jadwal->tanggal_jaga->isToday()) {
            return false;
        }

        // This should check if user has already checked in
        // For now, assume they can check out if schedule is active
        return true;
    }

    /**
     * Get timing information with tolerance
     */
    private function getTimingInfo(JadwalJaga $jadwal, ?WorkLocation $workLocation): array
    {
        $now = Carbon::now();
        $shiftStart = Carbon::createFromFormat('H:i', $jadwal->effective_start_time);
        $shiftEnd = Carbon::createFromFormat('H:i', $jadwal->effective_end_time);
        
        // Set date to today for comparison
        $shiftStart->setDate($now->year, $now->month, $now->day);
        $shiftEnd->setDate($now->year, $now->month, $now->day);
        
        // Handle overnight shifts
        if ($shiftEnd->lt($shiftStart)) {
            $shiftEnd->addDay();
        }

        $lateTolerance = $workLocation->late_tolerance_minutes ?? 15;
        $earlyTolerance = $workLocation->checkin_before_shift_minutes ?? 30;
        
        $allowedCheckInStart = $shiftStart->copy()->subMinutes($earlyTolerance);
        $allowedCheckInEnd = $shiftStart->copy()->addMinutes($lateTolerance);
        $allowedCheckOutStart = $shiftEnd->copy()->subMinutes($workLocation->early_departure_tolerance_minutes ?? 15);
        $allowedCheckOutEnd = $shiftEnd->copy()->addMinutes($workLocation->checkout_after_shift_minutes ?? 60);

        return [
            'shift_start' => $shiftStart->format('H:i'),
            'shift_end' => $shiftEnd->format('H:i'),
            'current_time' => $now->format('H:i'),
            'check_in_window' => [
                'start' => $allowedCheckInStart->format('H:i'),
                'end' => $allowedCheckInEnd->format('H:i'),
                'is_open' => $now->between($allowedCheckInStart, $allowedCheckInEnd),
            ],
            'check_out_window' => [
                'start' => $allowedCheckOutStart->format('H:i'),
                'end' => $allowedCheckOutEnd->format('H:i'),
                'is_open' => $now->between($allowedCheckOutStart, $allowedCheckOutEnd),
            ],
            'status' => $this->getCurrentTimingStatus($now, $shiftStart, $shiftEnd, $allowedCheckInStart, $allowedCheckInEnd),
            'next_action' => $this->getNextAction($now, $shiftStart, $shiftEnd, $allowedCheckInStart, $allowedCheckInEnd),
        ];
    }

    /**
     * Get current timing status
     */
    private function getCurrentTimingStatus(Carbon $now, Carbon $shiftStart, Carbon $shiftEnd, Carbon $checkInStart, Carbon $checkInEnd): string
    {
        if ($now->lt($checkInStart)) {
            $minutesUntilCheckIn = $now->diffInMinutes($checkInStart);
            return "Check-in opens in {$minutesUntilCheckIn} minutes";
        }
        
        if ($now->between($checkInStart, $checkInEnd)) {
            if ($now->lt($shiftStart)) {
                return "Early check-in period (within tolerance)";
            } elseif ($now->eq($shiftStart)) {
                return "Perfect time to check in!";
            } else {
                $minutesLate = $now->diffInMinutes($shiftStart);
                return "Late check-in period ({$minutesLate} min late, within tolerance)";
            }
        }
        
        if ($now->gt($checkInEnd) && $now->lt($shiftEnd)) {
            return "Check-in window closed, contact supervisor";
        }
        
        if ($now->between($shiftEnd->copy()->subMinutes(30), $shiftEnd)) {
            return "Check-out period approaching";
        }
        
        if ($now->gt($shiftEnd)) {
            return "Shift ended, time to check out";
        }
        
        return "During shift hours";
    }

    /**
     * Get next recommended action
     */
    private function getNextAction(Carbon $now, Carbon $shiftStart, Carbon $shiftEnd, Carbon $checkInStart, Carbon $checkInEnd): string
    {
        if ($now->lt($checkInStart)) {
            return "wait_for_checkin";
        }
        
        if ($now->between($checkInStart, $checkInEnd)) {
            return "can_checkin";
        }
        
        if ($now->between($shiftEnd->copy()->subMinutes(15), $shiftEnd->copy()->addMinutes(60))) {
            return "can_checkout";
        }
        
        return "wait";
    }

    /**
     * Get schedule status relative to current time
     */
    private function getScheduleStatus(JadwalJaga $jadwalJaga, Carbon $now): array
    {
        $shiftStart = Carbon::createFromFormat('H:i', $jadwalJaga->effective_start_time);
        $shiftEnd = Carbon::createFromFormat('H:i', $jadwalJaga->effective_end_time);
        
        // Set date to today for comparison
        $shiftStart->setDate($now->year, $now->month, $now->day);
        $shiftEnd->setDate($now->year, $now->month, $now->day);
        
        // Handle overnight shifts
        if ($shiftEnd->lt($shiftStart)) {
            $shiftEnd->addDay();
        }

        $workLocation = $jadwalJaga->pegawai->workLocation;
        $checkInBeforeMinutes = $workLocation ? $workLocation->checkin_before_shift_minutes ?? 30 : 30;
        $lateToleranceMinutes = $workLocation ? $workLocation->late_tolerance_minutes ?? 15 : 15;
        
        $checkInStart = $shiftStart->copy()->subMinutes($checkInBeforeMinutes);
        $checkInEnd = $shiftStart->copy()->addMinutes($lateToleranceMinutes);
        
        if ($now->lt($checkInStart)) {
            return [
                'status' => 'upcoming',
                'message' => 'Shift will start at ' . $shiftStart->format('H:i'),
                'minutes_until_checkin' => $now->diffInMinutes($checkInStart),
                'can_checkin_in' => $checkInStart->diffForHumans(),
            ];
        }
        
        if ($now->between($checkInStart, $checkInEnd)) {
            return [
                'status' => 'checkin_window',
                'message' => 'Check-in window is open',
                'window_closes_in' => $now->diffInMinutes($checkInEnd),
                'is_late' => $now->gt($shiftStart),
            ];
        }
        
        if ($now->between($shiftStart, $shiftEnd)) {
            return [
                'status' => 'in_progress',
                'message' => 'Shift is currently active',
                'shift_ends_in' => $now->diffInMinutes($shiftEnd),
                'shift_ends_at' => $shiftEnd->format('H:i'),
            ];
        }
        
        if ($now->gt($shiftEnd)) {
            return [
                'status' => 'completed',
                'message' => 'Shift has ended',
                'ended_at' => $shiftEnd->format('H:i'),
                'ended_ago' => $shiftEnd->diffForHumans(),
            ];
        }
        
        return [
            'status' => 'unknown',
            'message' => 'Schedule status unclear',
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/jadwal-jaga/current",
     *     summary="Get current active schedule for authenticated user",
     *     tags={"Jadwal Jaga"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Date in Y-m-d format (optional, defaults to today)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-08-06")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Current schedule retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="No active schedule found")
     * )
     */
    public function current(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $date = $request->get('date', now()->format('Y-m-d'));
        
        try {
            $targetDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid date format. Use Y-m-d format.', 400);
        }

        // Find active jadwal jaga for the user on the specified date
        $jadwalJaga = JadwalJaga::whereDate('tanggal_jaga', $targetDate)
            ->where('pegawai_id', $user->id)
            ->where('status_jaga', 'Aktif')
            ->with(['shiftTemplate', 'pegawai'])
            ->first();

        if (!$jadwalJaga) {
            return $this->errorResponse('No active schedule found for ' . $targetDate->format('d M Y'), 404);
        }

        // Try to find associated work location
        $workLocation = null;
        
        if ($jadwalJaga->unit_kerja) {
            $workLocation = WorkLocation::where('unit_kerja', $jadwalJaga->unit_kerja)
                ->where('is_active', true)
                ->first();
        }
        
        if (!$workLocation) {
            $workLocation = WorkLocation::where('location_type', 'main_office')
                ->where('is_active', true)
                ->first();
        }

        $now = Carbon::now();
        $shiftStart = Carbon::createFromFormat('H:i', $jadwalJaga->effective_start_time);
        $shiftEnd = Carbon::createFromFormat('H:i', $jadwalJaga->effective_end_time);
        
        // Set date to today for comparison
        $shiftStart->setDate($now->year, $now->month, $now->day);
        $shiftEnd->setDate($now->year, $now->month, $now->day);
        
        // Handle overnight shifts
        if ($shiftEnd->lt($shiftStart)) {
            $shiftEnd->addDay();
        }

        $response = [
            'id' => $jadwalJaga->id,
            'tanggal_jaga' => $jadwalJaga->tanggal_jaga->format('Y-m-d'),
            'shift_template_id' => $jadwalJaga->shift_template_id,
            'pegawai_id' => $jadwalJaga->pegawai_id,
            'unit_kerja' => $jadwalJaga->unit_kerja,
            'unit_instalasi' => $jadwalJaga->unit_instalasi ?? null,
            'peran' => $jadwalJaga->peran,
            'status_jaga' => $jadwalJaga->status_jaga,
            'keterangan' => $jadwalJaga->keterangan,
            'effective_start_time' => $jadwalJaga->effective_start_time,
            'effective_end_time' => $jadwalJaga->effective_end_time,
            'is_today' => $jadwalJaga->tanggal_jaga->isToday(),
            'shift_template' => $jadwalJaga->shiftTemplate ? [
                'id' => $jadwalJaga->shiftTemplate->id,
                'nama_shift' => $jadwalJaga->shiftTemplate->nama_shift,
                'jam_masuk' => $jadwalJaga->shiftTemplate->jam_masuk,
                'jam_pulang' => $jadwalJaga->shiftTemplate->jam_pulang,
                'durasi' => $jadwalJaga->shiftTemplate->durasi,
                'warna' => $jadwalJaga->shiftTemplate->warna ?? '#3b82f6',
            ] : null,
            'work_location' => $workLocation ? [
                'id' => $workLocation->id,
                'name' => $workLocation->name,
                'description' => $workLocation->description,
                'address' => $workLocation->address,
                'latitude' => (float) $workLocation->latitude,
                'longitude' => (float) $workLocation->longitude,
                'radius_meters' => $workLocation->radius_meters,
                'location_type' => $workLocation->location_type,
                'location_type_label' => $workLocation->location_type_label,
                'tolerance_settings' => [
                    'late_tolerance_minutes' => $workLocation->late_tolerance_minutes ?? 15,
                    'early_departure_tolerance_minutes' => $workLocation->early_departure_tolerance_minutes ?? 15,
                    'checkin_before_shift_minutes' => $workLocation->checkin_before_shift_minutes ?? 30,
                    'checkout_after_shift_minutes' => $workLocation->checkout_after_shift_minutes ?? 60,
                ],
                'require_photo' => $workLocation->require_photo,
                'strict_geofence' => $workLocation->strict_geofence,
                'gps_accuracy_required' => $workLocation->gps_accuracy_required ?? 50,
            ] : null,
            'schedule_status' => $this->getScheduleStatus($jadwalJaga, $now),
            'timing_info' => $this->getTimingInfo($jadwalJaga, $workLocation),
        ];

        return $this->successResponse($response, 'Current active schedule retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/jadwal-jaga/validate-checkin",
     *     summary="Validate if user can check-in now with current location",
     *     tags={"Jadwal Jaga"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example="-6.2088"),
     *             @OA\Property(property="longitude", type="number", format="float", example="106.8456"),
     *             @OA\Property(property="accuracy", type="number", format="float", example="10.5"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-08-06")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Validation result returned"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function validateCheckin(Request $request): JsonResponse
    {
        // Debug logging for troubleshooting validation errors
        \Log::info('Doctor check-in validation request', [
            'request_data' => $request->all(),
            'user_id' => auth('sanctum')->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:1000', // Max 1000m GPS accuracy
            'date' => 'nullable|date_format:Y-m-d',
        ], [
            'latitude.required' => 'Latitude is required for location validation',
            'latitude.numeric' => 'Latitude must be a valid number',
            'latitude.between' => 'Latitude must be between -90 and 90 degrees',
            'longitude.required' => 'Longitude is required for location validation',
            'longitude.numeric' => 'Longitude must be a valid number', 
            'longitude.between' => 'Longitude must be between -180 and 180 degrees',
            'accuracy.numeric' => 'GPS accuracy must be a valid number',
            'accuracy.max' => 'GPS accuracy cannot exceed 1000 meters',
            'date.date_format' => 'Date must be in Y-m-d format (e.g., 2025-08-06)'
        ]);

        if ($validator->fails()) {
            \Log::warning('Doctor check-in validation failed - Input validation', [
                'user_id' => auth('sanctum')->id(),
                'validation_errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            \Log::warning('Doctor check-in validation failed - no authenticated user');
            return $this->unauthorizedResponse('User authentication required');
        }
        
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::today();
        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $accuracy = $request->accuracy ? (float) $request->accuracy : null;
        
        // Additional validation for coordinate sanity
        if (abs($latitude) < 0.001 && abs($longitude) < 0.001) {
            \Log::warning('Doctor check-in validation failed - suspicious coordinates', [
                'user_id' => $user->id,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            return $this->errorResponse('Invalid GPS coordinates detected. Please ensure location services are enabled.', 400);
        }

        // Use enhanced AttendanceValidationService for comprehensive validation with GPS diagnostics
        $validation = app(\App\Services\AttendanceValidationService::class)
            ->validateCheckin($user, $latitude, $longitude, $accuracy, $date);
        
        // Log validation result for debugging
        \Log::info('Doctor check-in validation result', [
            'user_id' => $user->id,
            'validation_valid' => $validation['valid'],
            'validation_code' => $validation['code'] ?? 'unknown',
            'validation_message' => $validation['message'] ?? 'no message'
        ]);

        // Check if user has already checked in today
        $todayAttendance = \App\Models\Attendance::getTodayAttendance($user->id);
        $attendanceStatus = \App\Models\Attendance::getTodayStatus($user->id);

        $response = [
            'validation' => [
                'valid' => $validation['valid'],
                'message' => $validation['message'],
                'code' => $validation['code'],
                'can_checkin' => $validation['valid'] && $attendanceStatus['can_check_in'],
            ],
            'attendance_status' => [
                'status' => $attendanceStatus['status'],
                'message' => $attendanceStatus['message'],
                'can_check_in' => $attendanceStatus['can_check_in'],
                'can_check_out' => $attendanceStatus['can_check_out'],
                'has_checked_in_today' => $todayAttendance !== null,
                'attendance' => $todayAttendance ? [
                    'id' => $todayAttendance->id,
                    'time_in' => $todayAttendance->time_in?->format('H:i:s'),
                    'time_out' => $todayAttendance->time_out?->format('H:i:s'),
                    'status' => $todayAttendance->status,
                ] : null,
            ],
            'schedule_details' => null,
            'validation_details' => $validation['validations'] ?? null,
        ];

        // Add schedule details if validation includes schedule
        if ($validation['valid'] && isset($validation['jadwal_jaga'])) {
            $jadwalJaga = $validation['jadwal_jaga'];
            $response['schedule_details'] = [
                'id' => $jadwalJaga->id,
                'tanggal_jaga' => $jadwalJaga->tanggal_jaga->format('Y-m-d'),
                'shift_name' => $jadwalJaga->shiftTemplate?->nama_shift ?? 'Unknown',
                'unit_kerja' => $jadwalJaga->unit_kerja,
                'status_jaga' => $jadwalJaga->status_jaga,
                'effective_start_time' => $jadwalJaga->effective_start_time,
                'effective_end_time' => $jadwalJaga->effective_end_time,
                'is_late_checkin' => $validation['code'] === 'VALID_BUT_LATE',
            ];
        }

        // Add work location details if validation includes location
        if (isset($validation['work_location'])) {
            $workLocation = $validation['work_location'];
            $response['work_location'] = [
                'id' => $workLocation->id,
                'name' => $workLocation->name,
                'address' => $workLocation->address,
                'latitude' => (float) $workLocation->latitude,
                'longitude' => (float) $workLocation->longitude,
                'radius_meters' => $workLocation->radius_meters,
                'distance_from_user' => $workLocation->calculateDistance($latitude, $longitude),
                'within_geofence' => $workLocation->isWithinGeofence($latitude, $longitude, $accuracy),
                'gps_diagnostics' => $validation['gps_diagnostics'] ?? null,
                'troubleshooting_tips' => $validation['data']['troubleshooting_tips'] ?? [],
            ];
        }
        
        // Add admin override information if present
        if (isset($validation['override_info'])) {
            $response['admin_override'] = $validation['override_info'];
        }

        $httpStatus = $validation['valid'] ? 200 : 400;
        
        // Enhanced success/error messaging
        if ($validation['valid']) {
            $message = $attendanceStatus['can_check_in'] 
                ? 'Validation successful - ready for check-in' 
                : 'Validation successful but check-in not allowed';
            
            // Add override information to success message if applicable
            if (isset($validation['override_info'])) {
                $message .= ' (Admin override active)';
            }
        } else {
            $message = 'GPS validation failed';
            
            // Add helpful troubleshooting context
            $troubleshootingTips = $validation['data']['troubleshooting_tips'] ?? [];
            $highPriorityTips = array_filter($troubleshootingTips, fn($tip) => ($tip['priority'] ?? 'low') === 'critical' || ($tip['priority'] ?? 'low') === 'high');
            
            if (!empty($highPriorityTips)) {
                $firstTip = reset($highPriorityTips);
                $message .= ' - ' . ($firstTip['title'] ?? 'Check troubleshooting tips');
            }
        }
        
        // Enhanced error logging for 400 responses with GPS diagnostics
        if (!$validation['valid']) {
            $diagnostics = $validation['gps_diagnostics'] ?? null;
            
            \Log::error('Doctor check-in validation failed - comprehensive analysis', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'validation_code' => $validation['code'] ?? 'unknown',
                'validation_message' => $validation['message'] ?? 'no message',
                'coordinates' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy' => $accuracy,
                ],
                'date' => $date->format('Y-m-d'),
                'user_data' => [
                    'work_location_id' => $user->work_location_id,
                    'location_id' => $user->location_id,
                    'role' => $user->role ?? 'unknown',
                ],
                'gps_diagnostics' => $diagnostics ? [
                    'coordinate_quality' => $diagnostics['location_analysis']['coordinate_quality'] ?? null,
                    'vpn_risk_level' => $diagnostics['location_analysis']['potential_vpn_proxy']['risk_level'] ?? 'unknown',
                    'estimated_region' => $diagnostics['location_analysis']['estimated_region']['region'] ?? 'unknown',
                    'distance_from_work' => $diagnostics['work_location_analysis']['distance_meters'] ?? null,
                ] : null,
                'troubleshooting_available' => !empty($validation['data']['troubleshooting_tips'] ?? []),
            ]);
        }

        return response()->json([
            'success' => $validation['valid'],
            'message' => $message,
            'data' => $response,
            'meta' => [
                'validation_timestamp' => now()->toISOString(),
                'has_troubleshooting_tips' => !empty($validation['data']['troubleshooting_tips'] ?? []),
                'gps_diagnostics_available' => isset($validation['gps_diagnostics']),
                'admin_override_active' => isset($validation['override_info']),
            ],
        ], $httpStatus);
    }

    /**
     * @OA\Get(
     *     path="/api/jadwal-jaga/duration",
     *     summary="Get today's jadwal jaga duration for work hour calculation",
     *     tags={"Jadwal Jaga"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Date in Y-m-d format (optional, defaults to today)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-08-04")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jadwal duration retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="No schedule found")
     * )
     */
    public function duration(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $date = $request->get('date', now()->format('Y-m-d'));
        
        try {
            $targetDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid date format. Use Y-m-d format.', 400);
        }

        // Find jadwal jaga for the user on the specified date
        // Note: jadwal_jagas.pegawai_id directly references users.id per DB schema
        $jadwalJaga = JadwalJaga::whereDate('tanggal_jaga', $targetDate)
            ->where('pegawai_id', $user->id)  // Correct: Use user->id as per DB schema
            ->with(['shiftTemplate'])
            ->first();

        if (!$jadwalJaga) {
            // Return default 8-hour target if no specific schedule
            return $this->successResponse([
                'has_schedule' => false,
                'target_minutes' => 480, // 8 hours default
                'target_hours' => 8,
                'message' => 'No specific schedule found, using standard 8-hour target'
            ], 'Default target duration retrieved');
        }

        // Calculate actual shift duration
        $startTime = Carbon::createFromFormat('H:i', $jadwalJaga->effective_start_time);
        $endTime = Carbon::createFromFormat('H:i', $jadwalJaga->effective_end_time);
        
        // Handle overnight shifts
        if ($endTime->lt($startTime)) {
            $endTime->addDay();
        }
        
        $durationMinutes = $startTime->diffInMinutes($endTime);
        $durationHours = round($durationMinutes / 60, 2);

        return $this->successResponse([
            'has_schedule' => true,
            'schedule_id' => $jadwalJaga->id,
            'shift_name' => $jadwalJaga->shiftTemplate->nama_shift,
            'start_time' => $jadwalJaga->effective_start_time,
            'end_time' => $jadwalJaga->effective_end_time,
            'target_minutes' => $durationMinutes,
            'target_hours' => $durationHours,
            'is_short_shift' => $durationMinutes < 240, // Less than 4 hours
            'message' => "Target duration from {$jadwalJaga->shiftTemplate->nama_shift} shift"
        ], 'Schedule duration retrieved successfully');
    }
}