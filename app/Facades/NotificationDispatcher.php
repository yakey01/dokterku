<?php

namespace App\Facades;

use App\Services\NotificationDispatcherService;
use Illuminate\Support\Facades\Facade;

/**
 * NotificationDispatcher Facade
 * 
 * Provides easy access to the notification dispatcher service.
 * 
 * @method static array dispatch(string $notificationType, array $data, array $options = [])
 * @method static array getDispatchStats()
 * 
 * @see \App\Services\NotificationDispatcherService
 */
class NotificationDispatcher extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return NotificationDispatcherService::class;
    }
}