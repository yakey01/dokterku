<?php

namespace App\Http\Controllers\Api\V2\Attendance;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\WorkLocation;
use App\Models\User;
use App\Services\AttendanceValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Attendance",
 *     description="GPS-based attendance management system"
 * )
 */
class AttendanceController extends BaseApiController
{
    protected AttendanceValidationService $validationService;
    
    public function __construct(AttendanceValidationService $validationService)
    {
        $this->validationService = $validationService;
    }
    /**
     * @OA\Post(
     *     path="/api/v2/attendance/checkin",
     *     summary="GPS-based attendance check-in",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example="-6.2088"),
     *             @OA\Property(property="longitude", type="number", format="float", example="106.8456"),
     *             @OA\Property(property="accuracy", type="number", format="float", example=10.5),
     *             @OA\Property(property="face_image", type="string", format="base64", description="Base64 encoded face image"),
     *             @OA\Property(property="location_name", type="string", example="Klinik Utama"),
     *             @OA\Property(property="notes", type="string", example="Check-in normal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Check-in successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Check-in berhasil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="attendance_id", type="integer", example=123),
     *                 @OA\Property(property="time_in", type="string", format="time", example="08:15:30"),
     *                 @OA\Property(property="status", type="string", example="present"),
     *                 @OA\Property(
     *                     property="coordinates",
     *                     type="object",
     *                     @OA\Property(property="latitude", type="number", format="float"),
     *                     @OA\Property(property="longitude", type="number", format="float")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Already checked in or validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function checkin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'face_image' => 'nullable|string',
            'location_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $this->getAuthenticatedUser();
        $today = Carbon::today();

        // Check attendance status for today
        $attendanceStatus = Attendance::getTodayStatus($user->id);
        
        if (!$attendanceStatus['can_check_in']) {
            $message = $attendanceStatus['status'] === 'checked_in' 
                ? 'Anda sudah check-in hari ini. Silakan lakukan check-out terlebih dahulu.'
                : 'Anda sudah menyelesaikan presensi hari ini. Check-in dapat dilakukan kembali besok.';
                
            return $this->errorResponse(
                $message,
                400,
                [
                    'current_status' => $attendanceStatus['status'],
                    'message' => $attendanceStatus['message'],
                    'attendance' => $attendanceStatus['attendance'] ? [
                        'time_in' => $attendanceStatus['attendance']->time_in?->format('H:i'),
                        'time_out' => $attendanceStatus['attendance']->time_out?->format('H:i'),
                        'duration' => $attendanceStatus['attendance']->formatted_work_duration
                    ] : null
                ],
                'CANNOT_CHECK_IN'
            );
        }

        // Comprehensive validation using AttendanceValidationService
        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $accuracy = $request->accuracy ? (float) $request->accuracy : null;
        
        $validation = $this->validationService->validateCheckin($user, $latitude, $longitude, $accuracy, $today);
        
        if (!$validation['valid']) {
            return $this->errorResponse(
                $validation['message'],
                400,
                $validation['data'] ?? null,
                $validation['code']
            );
        }

        // Handle face recognition if provided
        $faceRecognitionResult = null;
        if ($request->face_image) {
            $faceRecognitionResult = $this->processFaceRecognition($user->id, $request->face_image);
        }

        // Extract validated data
        $jadwalJaga = $validation['jadwal_jaga'];
        $workLocation = $validation['work_location'];
        $isLate = $validation['code'] === 'VALID_BUT_LATE';
        
        // Create attendance record with enhanced data
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'time_in' => Carbon::now(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'latlon_in' => $latitude . ',' . $longitude,
            'location_name_in' => $request->location_name ?? $workLocation->name,
            'location_id' => $workLocation instanceof WorkLocation ? null : $workLocation?->id, // Legacy location ID
            'work_location_id' => $workLocation instanceof WorkLocation ? $workLocation->id : null,
            'jadwal_jaga_id' => $jadwalJaga->id,
            'status' => $isLate ? 'late' : 'present',
            'notes' => $request->notes,
            'photo_in' => $faceRecognitionResult ? 'face_recognition_stored' : null,
            'location_validated' => true,
        ]);

        // Clear cache
        $this->clearUserAttendanceCache($user->id);

        return $this->successResponse([
            'attendance_id' => $attendance->id,
            'time_in' => $attendance->time_in->format('H:i'),
            'status' => $attendance->status,
            'coordinates' => [
                'latitude' => $attendance->latitude,
                'longitude' => $attendance->longitude,
                'accuracy' => $attendance->accuracy,
            ],
            'location' => [
                'name' => $attendance->location_name_in,
                'work_location_id' => $attendance->work_location_id,
                'location_id' => $attendance->location_id, // Legacy support
            ],
            'schedule' => [
                'jadwal_jaga_id' => $attendance->jadwal_jaga_id,
                'shift_name' => $jadwalJaga->shiftTemplate?->nama_shift ?? 'Unknown',
                'unit_kerja' => $jadwalJaga->unit_kerja,
                'is_late' => $isLate,
            ],
            'validation_details' => [
                'message' => $validation['message'],
                'code' => $validation['code'],
                'all_validations' => $validation['validations'] ?? null,
            ],
            'face_recognition' => $faceRecognitionResult ? [
                'verified' => $faceRecognitionResult['verified'] ?? false,
                'confidence' => $faceRecognitionResult['confidence'] ?? 0,
            ] : null,
        ], $validation['message'], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v2/attendance/checkout",
     *     summary="GPS-based attendance check-out",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example="-6.2088"),
     *             @OA\Property(property="longitude", type="number", format="float", example="106.8456"),
     *             @OA\Property(property="accuracy", type="number", format="float", example=10.5),
     *             @OA\Property(property="face_image", type="string", format="base64", description="Base64 encoded face image"),
     *             @OA\Property(property="notes", type="string", example="Check-out normal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Check-out successful"
     *     ),
     *     @OA\Response(response=400, description="Not checked in or validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function checkout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'face_image' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $this->getAuthenticatedUser();
        $today = Carbon::today();

        // Comprehensive validation using AttendanceValidationService
        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $accuracy = $request->accuracy ? (float) $request->accuracy : null;
        
        $validation = $this->validationService->validateCheckout($user, $latitude, $longitude, $accuracy, $today);
        
        if (!$validation['valid']) {
            return $this->errorResponse(
                $validation['message'],
                400,
                $validation['data'] ?? null,
                $validation['code']
            );
        }

        $attendance = $validation['attendance'];

        // Handle face recognition if provided
        $faceRecognitionResult = null;
        if ($request->face_image) {
            $faceRecognitionResult = $this->processFaceRecognition($user->id, $request->face_image);
        }

        // Update attendance record with checkout data
        $workLocation = $validation['work_location'];
        $checkoutTime = Carbon::now();
        
        $attendance->update([
            'time_out' => $checkoutTime,
            'checkout_latitude' => $latitude,
            'checkout_longitude' => $longitude,
            'checkout_accuracy' => $accuracy,
            'latlon_out' => $latitude . ',' . $longitude,
            'location_name_out' => $workLocation ? $workLocation->name : 'Location not found',
            'notes' => ($attendance->notes ? $attendance->notes . ' | ' : '') . 'Check-out: ' . ($request->notes ?? 'Normal check-out'),
            'photo_out' => $faceRecognitionResult ? 'face_recognition_stored' : null,
        ]);
        
        // Debug logging
        \Log::info('✅ Checkout Success', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'time_in' => $attendance->time_in,
            'time_out_set' => $checkoutTime,
            'time_out_after_update' => $attendance->fresh()->time_out
        ]);

        // Calculate work duration
        $workDuration = $attendance->time_in->diffInMinutes($attendance->time_out);

        // Clear cache
        $this->clearUserAttendanceCache($user->id);

        return $this->successResponse([
            'attendance_id' => $attendance->id,
            'time_in' => $attendance->time_in->format('H:i'),
            'time_out' => $attendance->time_out->format('H:i'),
            'work_duration' => [
                'minutes' => $workDuration,
                'hours_minutes' => $attendance->formatted_work_duration,
                'formatted' => $this->formatWorkDuration($workDuration),
            ],
            'coordinates' => [
                'checkout_latitude' => $attendance->checkout_latitude,
                'checkout_longitude' => $attendance->checkout_longitude,
                'checkout_accuracy' => $attendance->checkout_accuracy,
            ],
            'status' => 'completed',
            'face_recognition' => $faceRecognitionResult ? [
                'verified' => $faceRecognitionResult['verified'] ?? false,
                'confidence' => $faceRecognitionResult['confidence'] ?? 0,
            ] : null,
        ], 'Check-out berhasil');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/attendance/today",
     *     summary="Get today's attendance status",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Today's attendance status"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function today(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        
        $attendance = Cache::remember(
            "attendance:today:{$user->id}",
            config('api.cache.attendance_today_ttl', 60),
            function () use ($user) {
                return Attendance::where('user_id', $user->id)
                    ->whereDate('date', Carbon::today())
                    ->with('location')
                    ->first();
            }
        );

        if (!$attendance) {
            return $this->successResponse([
                'has_checked_in' => false,
                'has_checked_out' => false,
                'can_check_in' => true,
                'can_check_out' => false,
                'attendance' => null,
            ], 'Today\'s attendance status');
        }

        return $this->successResponse([
            'has_checked_in' => true,
            'has_checked_out' => $attendance->time_out !== null,
            'can_check_in' => false,
            'can_check_out' => $attendance->time_out === null,
            'attendance' => [
                'id' => $attendance->id,
                'date' => $attendance->date->format('Y-m-d'),
                'time_in' => $attendance->time_in?->format('H:i'),
                'time_out' => $attendance->time_out?->format('H:i'),
                'status' => $attendance->status,
                'work_duration' => $attendance->time_out ? [
                    'minutes' => $attendance->time_in->diffInMinutes($attendance->time_out),
                    'formatted' => $this->formatWorkDuration($attendance->time_in->diffInMinutes($attendance->time_out)),
                ] : null,
                'location' => [
                    'name_in' => $attendance->location_name_in,
                    'name_out' => $attendance->location_name_out,
                    'location' => $attendance->location ? [
                        'id' => $attendance->location->id,
                        'name' => $attendance->location->name,
                    ] : null,
                ],
            ],
        ], 'Today\'s attendance status');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/attendance/history",
     *     summary="Get attendance history",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Filter by month (YYYY-MM)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-07")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance history retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function history(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $perPage = min($request->get('per_page', 15), 50); // Max 50 items per page

        $query = Attendance::where('user_id', $user->id)
            ->with(['location:id,name'])
            ->orderBy('date', 'desc');

        // Filter by month if provided
        if ($request->month && preg_match('/^\d{4}-\d{2}$/', $request->month)) {
            $month = Carbon::createFromFormat('Y-m', $request->month);
            $query->whereYear('date', $month->year)
                  ->whereMonth('date', $month->month);
        }

        $attendances = $query->paginate($perPage);

        $data = $attendances->getCollection()->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'date' => $attendance->date->format('Y-m-d'),
                'day_name' => $attendance->date->format('l'),
                'time_in' => $attendance->time_in?->format('H:i'),
                'time_out' => $attendance->time_out?->format('H:i'),
                'status' => $attendance->status,
                'work_duration' => $attendance->time_out ? [
                    'minutes' => $attendance->time_in->diffInMinutes($attendance->time_out),
                    'formatted' => $this->formatWorkDuration($attendance->time_in->diffInMinutes($attendance->time_out)),
                ] : null,
                'location' => [
                    'name_in' => $attendance->location_name_in,
                    'name_out' => $attendance->location_name_out,
                    'location' => $attendance->location ? [
                        'id' => $attendance->location->id,
                        'name' => $attendance->location->name,
                    ] : null,
                ],
            ];
        });

        return $this->paginatedResponse($attendances->setCollection($data), 'Attendance history retrieved');
    }

    /**
     * @OA\Get(
     *     path="/api/v2/attendance/statistics",
     *     summary="Get attendance statistics",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Month for statistics (YYYY-MM)",
     *         required=false,
     *         @OA\Schema(type="string", example="2025-07")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance statistics retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $month = $request->month ? Carbon::createFromFormat('Y-m', $request->month) : Carbon::now();

        $monthlyAttendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->get();

        $totalDays = $monthlyAttendances->count();
        $presentDays = $monthlyAttendances->where('status', 'present')->count();
        $lateDays = $monthlyAttendances->where('status', 'late')->count();
        $totalMinutes = $monthlyAttendances->sum(function ($attendance) {
            return $attendance->time_out ? $attendance->time_in->diffInMinutes($attendance->time_out) : 0;
        });

        return $this->successResponse([
            'month' => $month->format('Y-m'),
            'month_name' => $month->format('F Y'),
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'absent_days' => max(0, $month->daysInMonth - $totalDays),
            'total_hours' => round($totalMinutes / 60, 2),
            'average_hours_per_day' => $totalDays > 0 ? round($totalMinutes / 60 / $totalDays, 2) : 0,
            'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0,
        ], 'Attendance statistics retrieved');
    }

    /**
     * Validate GPS location against work locations
     */
    private function validateGpsLocation(float $latitude, float $longitude, ?float $accuracy): array
    {
        $gpsConfig = config('api.gps.attendance_validation');
        
        if (!$gpsConfig['enabled']) {
            return ['valid' => true, 'location' => null];
        }

        // Check accuracy requirement
        if ($accuracy && $accuracy > $gpsConfig['required_accuracy']) {
            return [
                'valid' => false,
                'message' => 'GPS accuracy tidak mencukupi. Diperlukan akurasi maksimal ' . $gpsConfig['required_accuracy'] . ' meter.',
                'location' => null,
            ];
        }

        // Get all locations (no is_active check needed)
        $locations = Cache::remember(
            'locations:all',
            config('api.cache.locations_ttl', 1800),
            fn() => Location::all()
        );

        foreach ($locations as $location) {
            if ($location->isWithinGeofence($latitude, $longitude)) {
                $distance = $location->getDistanceFrom($latitude, $longitude);
                return [
                    'valid' => true,
                    'location' => $location,
                    'distance' => round($distance, 2),
                ];
            }
        }

        return [
            'valid' => false,
            'message' => 'Lokasi Anda tidak berada dalam radius lokasi kerja yang diizinkan.',
            'location' => null,
        ];
    }


    /**
     * Process face recognition (placeholder)
     */
    private function processFaceRecognition(int $userId, string $faceImage): ?array
    {
        // TODO: Implement actual face recognition logic
        // For now, return a mock result
        return [
            'verified' => true,
            'confidence' => 0.95,
            'processed_at' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Format work duration in human readable format
     */
    private function formatWorkDuration(int $minutes): string
    {
        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return $hours . ' jam ' . $remainingMinutes . ' menit';
        }

        return $remainingMinutes . ' menit';
    }

    /**
     * Clear user attendance cache
     */
    private function clearUserAttendanceCache(int $userId): void
    {
        Cache::forget("attendance:today:{$userId}");
        // Clear other related cache keys as needed
    }
}