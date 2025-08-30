<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ManagerCriticalAlert implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $alertType,
        public string $title,
        public string $message,
        public string $severity, // 'low', 'medium', 'high', 'critical'
        public array $data = [],
        public ?int $managerId = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('manajer.critical-alerts'),
            new Channel('management.oversight'),
        ];
        
        // If specific manager, add private channel
        if ($this->managerId) {
            $channels[] = new PrivateChannel("manajer.{$this->managerId}");
        }
        
        // Critical alerts go to executive dashboard
        if ($this->severity === 'critical') {
            $channels[] = new PrivateChannel('executive.dashboard');
        }
        
        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'event' => 'critical_alert',
            'alert' => [
                'type' => $this->alertType,
                'title' => $this->title,
                'message' => $this->message,
                'severity' => $this->severity,
                'data' => $this->data,
                'manager_id' => $this->managerId,
                'requires_action' => in_array($this->severity, ['high', 'critical']),
                'auto_dismiss' => $this->severity === 'low',
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'manager.critical.alert';
    }
}