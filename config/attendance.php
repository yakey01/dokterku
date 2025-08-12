<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for attendance check-in/check-out validation rules
    |
    */

    // Check-in time window tolerances (in minutes)
    'check_in_tolerance_early' => env('ATTENDANCE_CHECKIN_EARLY', 30), // Can check-in 30 minutes before shift
    'check_in_tolerance_late' => env('ATTENDANCE_CHECKIN_LATE', 60),   // Can check-in up to 60 minutes after shift start

    // Check-out time window tolerances (in minutes)  
    'check_out_tolerance_early' => env('ATTENDANCE_CHECKOUT_EARLY', 30), // Can check-out 30 minutes before shift end
    'check_out_tolerance_late' => env('ATTENDANCE_CHECKOUT_LATE', 120),  // Can check-out up to 2 hours after shift end

    // GPS validation settings
    'max_gps_accuracy' => env('ATTENDANCE_MAX_GPS_ACCURACY', 50), // Maximum GPS accuracy in meters
    'default_location_radius' => env('ATTENDANCE_DEFAULT_RADIUS', 100), // Default geofence radius in meters

    // Validation flags
    'enforce_schedule' => env('ATTENDANCE_ENFORCE_SCHEDULE', true), // Require active schedule to check-in
    'enforce_location' => env('ATTENDANCE_ENFORCE_LOCATION', true), // Require valid GPS location
    'allow_multiple_shifts' => env('ATTENDANCE_ALLOW_MULTIPLE_SHIFTS', true), // Allow multiple check-ins per day

    // Timer calculation rules
    'timer_starts_at_shift' => env('ATTENDANCE_TIMER_AT_SHIFT', true), // Timer starts at shift time if early
    'timer_ends_at_shift' => env('ATTENDANCE_TIMER_ENDS_AT_SHIFT', false), // Timer ends at shift time if late

    // Late/Early thresholds (in minutes)
    'late_threshold' => env('ATTENDANCE_LATE_THRESHOLD', 15), // Mark as late if check-in after this threshold
    'early_departure_threshold' => env('ATTENDANCE_EARLY_DEPARTURE', 15), // Mark as early departure if check-out before

    // Notification settings
    'notify_late_checkin' => env('ATTENDANCE_NOTIFY_LATE', true),
    'notify_missed_checkout' => env('ATTENDANCE_NOTIFY_MISSED', true),

    // Cache settings (in seconds)
    'cache_ttl' => [
        'user_attendance' => 60,  // 1 minute
        'work_locations' => 3600,  // 1 hour
        'shift_templates' => 3600, // 1 hour
    ],

    // Roles allowed to perform attendance
    'allowed_roles' => [
        'perawat',
        'bidan', 
        'analis',
        'paramedis',
        'paramedis-lainnya',
        'nonparamedis',
        'petugas',
        'dokter',
        'dokter-gigi'
    ],

    // Multi-shift settings
    'multishift' => [
        'enabled' => env('ATTENDANCE_ALLOW_MULTIPLE_SHIFTS', true),
        'max_shifts_per_day' => env('ATTENDANCE_MAX_SHIFTS_PER_DAY', 10), // Increased for development
        'min_gap_between_shifts' => env('ATTENDANCE_MIN_SHIFT_GAP', 0), // No gap required for development
        'max_gap_between_shifts' => env('ATTENDANCE_MAX_SHIFT_GAP', 1440), // 24 hours for development
        'allow_overtime_shifts' => env('ATTENDANCE_ALLOW_OVERTIME', true),
        'overtime_after_shifts' => env('ATTENDANCE_OVERTIME_AFTER', 2), // Mark as overtime after N shifts
    ],

    // Debug mode
    'debug' => env('ATTENDANCE_DEBUG', false),
];