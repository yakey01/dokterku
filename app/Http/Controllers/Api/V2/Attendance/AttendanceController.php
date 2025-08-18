<?php

namespace App\Http\Controllers\Api\V2\Attendance;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\WorkLocation;
use App\Models\User;
use App\Models\JadwalJaga;
use App\Services\AttendanceValidationService;
use App\Services\CheckInValidationService;
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
    protected CheckInValidationService $checkInService;
    
    public function __construct(
        AttendanceValidationService $validationService,
        CheckInValidationService $checkInService
    ) {
        $this->validationService = $validationService;
        $this->checkInService = $checkInService;
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

        // Use new CheckInValidationService for comprehensive validation
        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $accuracy = $request->accuracy ? (float) $request->accuracy : null;
        
        $validation = $this->checkInService->validateCheckIn($user, $latitude, $longitude, $accuracy, $today);
        
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
        $validationData = $validation['data'];
        
        // Check if required data exists
        if (!isset($validationData['jadwal_jaga']) || !isset($validationData['shift'])) {
            return $this->errorResponse(
                'Data validasi tidak lengkap',
                500,
                ['validation_data' => $validationData],
                'INCOMPLETE_VALIDATION_DATA'
            );
        }
        
        $jadwalJaga = $validationData['jadwal_jaga'];
        $shift = $validationData['shift'];
        $workLocation = $validationData['work_location'];
        $isLate = $validationData['is_late'] ?? false;
        $metadata = $validationData['metadata'];
        
        // Extract multi-shift info
        $multishiftInfo = $validationData['multishift_info'] ?? [];
        
        // Create attendance record with enhanced data and multi-shift support
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'time_in' => Carbon::now(),
            'logical_time_in' => $validationData['logical_time_in'],
            'shift_id' => $shift->id,
            'shift_sequence' => $multishiftInfo['shift_sequence'] ?? 1,
            'previous_attendance_id' => $multishiftInfo['previous_attendance_id'] ?? null,
            'gap_from_previous_minutes' => $multishiftInfo['gap_minutes'] ?? null,
            'shift_start' => $shift->jam_masuk,
            'shift_end' => $shift->jam_pulang ?? $shift->jam_keluar,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'latlon_in' => $latitude . ',' . $longitude,
            'location_name_in' => $request->location_name ?? $workLocation?->name ?? 'Unknown Location',
            'location_id' => $workLocation instanceof WorkLocation ? null : $workLocation?->id, // Legacy location ID
            'work_location_id' => $workLocation instanceof WorkLocation ? $workLocation->id : null,
            'jadwal_jaga_id' => $jadwalJaga->id,
            'status' => $isLate ? 'late' : 'present',
            'notes' => $request->notes,
            'photo_in' => $faceRecognitionResult ? 'face_recognition_stored' : null,
            'location_validated' => true,
            'is_additional_shift' => $multishiftInfo['is_additional_shift'] ?? false,
            'is_overtime_shift' => $multishiftInfo['is_overtime'] ?? false,
            'check_in_metadata' => $metadata,
        ]);

        // Clear cache
        $this->clearUserAttendanceCache($user->id);

        return $this->successResponse([
            'attendance_id' => $attendance->id,
            'time_in' => $attendance->time_in->format('H:i'),
            'logical_time_in' => $attendance->logical_time_in->format('H:i'),
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
                'distance' => $metadata['validation']['location']['distance'] ?? null,
            ],
            'schedule' => [
                'jadwal_jaga_id' => $attendance->jadwal_jaga_id,
                'shift_id' => $attendance->shift_id,
                'shift_name' => $shift->nama_shift,
                'shift_start' => $attendance->shift_start->format('H:i'),
                'shift_end' => $attendance->shift_end->format('H:i'),
                'unit_kerja' => $jadwalJaga->unit_kerja ?? null,
                'is_late' => $isLate,
                'shift_sequence' => $attendance->shift_sequence,
                'is_additional_shift' => $attendance->is_additional_shift,
                'is_overtime' => $attendance->is_overtime_shift,
            ],
            'timer' => [
                'actual_check_in' => $attendance->time_in->format('H:i'),
                'logical_start' => $attendance->logical_time_in->format('H:i'),
                'timer_started_early' => $metadata['validation']['timer']['is_early'] ?? false,
                'early_minutes' => $metadata['validation']['timer']['early_minutes'] ?? 0,
            ],
            'validation_details' => [
                'message' => $validation['message'],
                'code' => $validation['code'],
                'check_in_window' => $metadata['validation']['time']['window'] ?? null,
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

    /**
     * Get multi-shift status for the user
     * 
     * @OA\Get(
     *     path="/api/v2/attendance/multishift-status",
     *     summary="Get comprehensive multi-shift attendance status",
     *     tags={"Attendance"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Multi-shift status retrieved successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function multishiftStatus(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $today = Carbon::today();

        // Get all attendances for today
        $todayAttendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->orderBy('shift_sequence')
            ->with(['shift', 'jadwalJaga'])
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
                'id' => $openAttendance->shift_id,
                'nama_shift' => $openAttendance->shift?->nama_shift ?? 'Unknown',
                'jam_masuk' => $openAttendance->shift_start?->format('H:i') ?? '',
                'jam_pulang' => $openAttendance->shift_end?->format('H:i') ?? '',
                'shift_sequence' => $openAttendance->shift_sequence,
                'is_current' => true,
                'is_available' => false,
                'can_checkin' => false
            ];
            $message = 'Anda sedang dalam shift ' . ($currentShift['nama_shift'] ?? '') . '. Silakan check-out terlebih dahulu.';
        } else {
            // Check if can check in for next shift
            $maxShifts = config('attendance.multishift.max_shifts_per_day', 3);
            $completedShifts = $todayAttendances->count();

            if ($completedShifts >= $maxShifts) {
                $message = 'Anda sudah mencapai batas maksimal ' . $maxShifts . ' shift per hari.';
            } else {
                // Check gap from last attendance
                $lastAttendance = $todayAttendances->last();
                $currentTime = Carbon::now();
                
                if ($lastAttendance && $lastAttendance->time_out) {
                    $timeSinceCheckout = Carbon::parse($lastAttendance->time_out)->diffInMinutes($currentTime);
                    $minGap = config('attendance.multishift.min_gap_between_shifts', 60);
                    
                    if ($timeSinceCheckout < $minGap) {
                        $remainingMinutes = $minGap - $timeSinceCheckout;
                        $message = 'Anda harus menunggu ' . $remainingMinutes . ' menit lagi sebelum check-in shift berikutnya.';
                    } else {
                        // Get work location settings for tolerance
                        $workLocation = WorkLocation::first();
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
                            // Note: early_checkin_minutes field doesn't exist in DB
                        }
                        
                        // Find available shifts
                        foreach ($todaySchedules as $schedule) {
                            // Skip if already used
                            if ($todayAttendances->contains('jadwal_jaga_id', $schedule->id)) {
                                continue;
                            }

                            $shift = $schedule->shiftTemplate;
                            if (!$shift) continue;

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
                    $workLocation = WorkLocation::first();
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
                'shift_sequence' => $att->shift_sequence,
                'shift_name' => $att->shift?->nama_shift ?? 'Unknown',
                'time_in' => $att->time_in?->format('H:i:s') ?? '',
                'time_out' => $att->time_out?->format('H:i:s') ?? null,
                'status' => $att->status,
                'is_overtime' => $att->is_overtime_shift ?? false,
                'gap_minutes' => $att->gap_from_previous_minutes
            ];
        });

        return $this->successResponse([
            'can_check_in' => $canCheckIn,
            'can_check_out' => $canCheckOut,
            'current_shift' => $currentShift,
            'next_shift' => $nextShift,
            'today_attendances' => $attendanceRecords,
            'shifts_available' => $shiftsAvailable,
            'max_shifts_reached' => $todayAttendances->count() >= config('attendance.multishift.max_shifts_per_day', 3),
            'message' => $message
        ], 'Multi-shift status retrieved');
    }
}