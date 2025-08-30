<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enhanced Telegram Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the enhanced notification system
    | including queue settings, retry policies, and notification rules.
    |
    */

    'enabled' => env('ENHANCED_NOTIFICATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the queue settings for different notification priorities.
    |
    */
    'queues' => [
        'high' => env('TELEGRAM_HIGH_QUEUE', 'telegram-high'),
        'normal' => env('TELEGRAM_NORMAL_QUEUE', 'telegram'),
        'low' => env('TELEGRAM_LOW_QUEUE', 'telegram-low'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for failed notifications.
    |
    */
    'retry' => [
        'max_attempts' => env('TELEGRAM_MAX_RETRY_ATTEMPTS', 3),
        'backoff_seconds' => [10, 30, 90], // Exponential backoff
        'timeout_seconds' => env('TELEGRAM_TIMEOUT_SECONDS', 60),
        'retry_until_minutes' => env('TELEGRAM_RETRY_UNTIL_MINUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Rules
    |--------------------------------------------------------------------------
    |
    | Define automatic notification routing rules based on conditions.
    |
    */
    'notification_rules' => [
        'high_amount_threshold' => env('HIGH_AMOUNT_THRESHOLD', 1000000),
        'emergency_roles' => ['admin', 'manajer', 'dokter', 'paramedis'],
        'financial_roles' => ['bendahara', 'manajer', 'admin'],
        'medical_roles' => ['dokter', 'paramedis', 'petugas'],
        'management_roles' => ['manajer', 'admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the template system.
    |
    */
    'templates' => [
        'enabled' => env('TELEGRAM_TEMPLATES_ENABLED', true),
        'cache_enabled' => env('TELEGRAM_TEMPLATE_CACHE_ENABLED', true),
        'cache_ttl' => env('TELEGRAM_TEMPLATE_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for fallback strategies when notifications fail.
    |
    */
    'fallback' => [
        'enabled' => env('TELEGRAM_FALLBACK_ENABLED', true),
        'log_failures' => env('TELEGRAM_LOG_FAILURES', true),
        'create_system_alerts' => env('TELEGRAM_SYSTEM_ALERTS', true),
        'alternative_methods' => [
            'email' => env('FALLBACK_EMAIL_ENABLED', false),
            'sms' => env('FALLBACK_SMS_ENABLED', false),
            'in_app' => env('FALLBACK_IN_APP_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent spam and API limits.
    |
    */
    'rate_limiting' => [
        'enabled' => env('TELEGRAM_RATE_LIMITING_ENABLED', true),
        'max_per_minute' => env('TELEGRAM_MAX_PER_MINUTE', 30),
        'max_per_hour' => env('TELEGRAM_MAX_PER_HOUR', 1000),
        'burst_limit' => env('TELEGRAM_BURST_LIMIT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Logging
    |--------------------------------------------------------------------------
    |
    | Configure monitoring and logging behavior.
    |
    */
    'monitoring' => [
        'log_all_notifications' => env('TELEGRAM_LOG_ALL_NOTIFICATIONS', true),
        'log_failed_notifications' => env('TELEGRAM_LOG_FAILED_NOTIFICATIONS', true),
        'log_performance_metrics' => env('TELEGRAM_LOG_PERFORMANCE_METRICS', false),
        'alert_on_failures' => env('TELEGRAM_ALERT_ON_FAILURES', true),
        'failure_threshold_percent' => env('TELEGRAM_FAILURE_THRESHOLD_PERCENT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Role Communication Rules
    |--------------------------------------------------------------------------
    |
    | Define which roles should be notified for specific notification types.
    |
    */
    'cross_role_rules' => [
        'validasi_disetujui' => [
            'notify_dokter' => true,
            'notify_paramedis' => true,
            'notify_input_user' => true,
            'notify_management' => true,
        ],
        'tindakan_baru' => [
            'notify_medical_staff' => true,
            'notify_financial_staff' => true,
            'notify_management' => false,
        ],
        'jaspel_ready' => [
            'notify_target_dokter' => true,
            'notify_financial_staff' => true,
            'notify_management' => true,
        ],
        'emergency_alert' => [
            'notify_all_medical' => true,
            'notify_management' => true,
            'notify_admin' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Conditional Notifications
    |--------------------------------------------------------------------------
    |
    | Configure conditions for automatic notification triggers.
    |
    */
    'conditions' => [
        'weekend_notifications' => env('WEEKEND_NOTIFICATIONS_ENABLED', true),
        'night_shift_notifications' => env('NIGHT_SHIFT_NOTIFICATIONS_ENABLED', true),
        'holiday_notifications' => env('HOLIDAY_NOTIFICATIONS_ENABLED', true),
        'emergency_hours' => [
            'start' => env('EMERGENCY_HOURS_START', '22:00'),
            'end' => env('EMERGENCY_HOURS_END', '06:00'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Priorities
    |--------------------------------------------------------------------------
    |
    | Define priority levels for different notification types.
    |
    */
    'priorities' => [
        'emergency_alert' => 'emergency',
        'backup_gagal' => 'high',
        'validasi_disetujui' => 'high',
        'jaspel_dokter_ready' => 'high',
        'tindakan_baru' => 'normal',
        'pendapatan' => 'normal',
        'pengeluaran' => 'normal',
        'pasien' => 'normal',
        'presensi_dokter' => 'normal',
        'presensi_paramedis' => 'normal',
        'sistem_maintenance' => 'high',
        'approval_request' => 'high',
        'jadwal_jaga_update' => 'high',
        'cuti_request' => 'normal',
        'shift_assignment' => 'normal',
        'laporan_shift' => 'normal',
        'user_baru' => 'low',
        'rekap_harian' => 'low',
        'rekap_mingguan' => 'low',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the notification system.
    |
    */
    'features' => [
        'cross_role_notifications' => env('CROSS_ROLE_NOTIFICATIONS_ENABLED', true),
        'template_system' => env('TEMPLATE_SYSTEM_ENABLED', true),
        'smart_routing' => env('SMART_ROUTING_ENABLED', true),
        'notification_batching' => env('NOTIFICATION_BATCHING_ENABLED', false),
        'user_preferences' => env('USER_NOTIFICATION_PREFERENCES_ENABLED', true),
        'notification_scheduling' => env('NOTIFICATION_SCHEDULING_ENABLED', false),
        'notification_grouping' => env('NOTIFICATION_GROUPING_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for the notification system.
    |
    */
    'performance' => [
        'cache_user_settings' => env('CACHE_USER_SETTINGS', true),
        'cache_templates' => env('CACHE_TEMPLATES', true),
        'batch_notifications' => env('BATCH_NOTIFICATIONS', false),
        'async_processing' => env('ASYNC_NOTIFICATION_PROCESSING', true),
        'database_logging' => env('DATABASE_NOTIFICATION_LOGGING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related settings for notifications.
    |
    */
    'security' => [
        'sanitize_messages' => env('SANITIZE_NOTIFICATION_MESSAGES', true),
        'validate_chat_ids' => env('VALIDATE_CHAT_IDS', true),
        'encrypt_sensitive_data' => env('ENCRYPT_NOTIFICATION_DATA', false),
        'audit_notifications' => env('AUDIT_NOTIFICATIONS', true),
        'max_message_length' => env('MAX_NOTIFICATION_MESSAGE_LENGTH', 4096),
    ],
];