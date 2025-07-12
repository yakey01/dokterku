<?php

namespace App\Providers;

use App\Listeners\AuthenticationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Events are registered via the AuthenticationListener subscriber
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        AuthenticationListener::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}