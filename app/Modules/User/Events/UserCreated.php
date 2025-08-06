<?php

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a user is created
 */
class UserCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;

    /**
     * Create a new event instance
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}