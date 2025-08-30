<?php

namespace App\Providers;

use App\Events\DataInputDisimpan;
use App\Events\ExpenseCreated;
use App\Events\IncomeCreated;
use App\Events\JaspelSelesai;
use App\Events\JaspelUpdated;
use App\Events\PatientCreated;
use App\Events\TindakanInputCreated;
use App\Events\TindakanValidated;
use App\Events\UserCreated;
use App\Events\ValidasiBerhasil;
use App\Events\WorkLocationUpdated;
use App\Listeners\EnhancedTelegramNotificationListener;
use App\Services\NotificationDispatcherService;
use App\Services\TelegramService;
use App\Services\TelegramTemplateService;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * EnhancedNotificationServiceProvider
 * 
 * Service provider for the enhanced notification system that:
 * - Registers all notification services
 * - Binds event listeners for comprehensive notification coverage
 * - Provides configuration and service registration
 */
class EnhancedNotificationServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        // Financial events
        IncomeCreated::class => [
            EnhancedTelegramNotificationListener::class . '@handleIncomeCreated',
        ],
        ExpenseCreated::class => [
            EnhancedTelegramNotificationListener::class . '@handleExpenseCreated',
        ],

        // Medical events
        PatientCreated::class => [
            EnhancedTelegramNotificationListener::class . '@handlePatientCreated',
        ],
        TindakanInputCreated::class => [
            EnhancedTelegramNotificationListener::class . '@handleTindakanInputCreated',
        ],
        TindakanValidated::class => [
            EnhancedTelegramNotificationListener::class . '@handleTindakanValidated',
        ],

        // JASPEL events
        JaspelSelesai::class => [
            EnhancedTelegramNotificationListener::class . '@handleJaspelSelesai',
        ],
        JaspelUpdated::class => [
            EnhancedTelegramNotificationListener::class . '@handleJaspelUpdated',
        ],

        // User management events
        UserCreated::class => [
            EnhancedTelegramNotificationListener::class . '@handleUserCreated',
        ],

        // Legacy events (for backward compatibility)
        ValidasiBerhasil::class => [
            EnhancedTelegramNotificationListener::class . '@handleValidasiBerhasil',
        ],
        DataInputDisimpan::class => [
            EnhancedTelegramNotificationListener::class . '@handleDataInputDisimpan',
        ],

        // System events
        WorkLocationUpdated::class => [
            EnhancedTelegramNotificationListener::class . '@handleWorkLocationUpdated',
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register core notification services
        $this->app->singleton(TelegramTemplateService::class, function ($app) {
            return new TelegramTemplateService();
        });

        $this->app->singleton(NotificationDispatcherService::class, function ($app) {
            return new NotificationDispatcherService($app->make(TelegramService::class));
        });

        // Register the enhanced telegram service
        $this->app->extend(TelegramService::class, function (TelegramService $service, $app) {
            // The service will auto-inject the template service via constructor
            return $service;
        });

        Log::info('Enhanced notification services registered');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();

        // Register additional dynamic events that might be created at runtime
        $this->registerDynamicEvents();

        // Register custom notification events
        $this->registerCustomNotificationEvents();

        Log::info('Enhanced notification service provider booted');
    }

    /**
     * Register dynamic events that might be created at runtime
     */
    protected function registerDynamicEvents(): void
    {
        // Attendance events (when attendance system is implemented)
        Event::listen('attendance.checkin', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleAttendanceCheckin($event);
        });

        Event::listen('attendance.checkout', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleAttendanceCheckin($event); // Same handler for both
        });

        // Emergency events
        Event::listen('emergency.alert', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleEmergencyAlert($event);
        });

        // System maintenance events
        Event::listen('system.maintenance.start', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleSystemMaintenance($event);
        });

        Event::listen('system.maintenance.end', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleSystemMaintenance($event);
        });

        // Shift and schedule events
        Event::listen('shift.report', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleShiftReport($event);
        });

        Event::listen('schedule.updated', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleScheduleUpdate($event);
        });

        // Leave request events
        Event::listen('leave.requested', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleLeaveRequest($event);
        });

        Event::listen('leave.approved', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleLeaveRequest($event);
        });

        Event::listen('leave.rejected', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleLeaveRequest($event);
        });

        // Shift assignment events
        Event::listen('shift.assigned', function ($event) {
            $this->app->make(EnhancedTelegramNotificationListener::class)
                ->handleShiftAssignment($event);
        });

        Log::info('Dynamic notification events registered');
    }

    /**
     * Register custom notification events for manual triggering
     */
    protected function registerCustomNotificationEvents(): void
    {
        // Custom notification events that can be triggered manually
        Event::listen('notification.send', function ($event) {
            $dispatcher = $this->app->make(NotificationDispatcherService::class);
            
            $dispatcher->dispatch(
                $event->notificationType,
                $event->data,
                $event->options ?? []
            );
        });

        Event::listen('notification.emergency', function ($event) {
            $telegramService = $this->app->make(TelegramService::class);
            
            $telegramService->sendEmergencyNotification(
                $event->message,
                $event->data ?? []
            );
        });

        Event::listen('notification.cross_role', function ($event) {
            $telegramService = $this->app->make(TelegramService::class);
            
            $telegramService->sendCrossRoleNotification(
                $event->targetRoles,
                $event->notificationType,
                $event->data,
                $event->excludeUserId ?? null
            );
        });

        Log::info('Custom notification events registered');
    }

    /**
     * Get the events and handlers.
     */
    public function listens(): array
    {
        return $this->listen;
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // We explicitly define our events
    }

    /**
     * Get the listener directories that should be used to discover events.
     */
    protected function discoverEventsWithin(): array
    {
        return [
            $this->app->path('Listeners'),
        ];
    }
}