<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule system metrics collection
Schedule::command('system:collect-metrics')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/system-metrics.log'));

// Schedule metrics cleanup (weekly)
Schedule::command('system:collect-metrics --cleanup --days=30')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->appendOutputTo(storage_path('logs/system-metrics-cleanup.log'));

// Schedule auto-close attendance based on tolerance
// Runs every 5 minutes to check for attendance records that exceeded checkout tolerance
Schedule::command('attendance:auto-close')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/attendance-auto-close.log'))
    ->description('Auto-close attendance records that exceeded checkout tolerance with 1 minute penalty');
