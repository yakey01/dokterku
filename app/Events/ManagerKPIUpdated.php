<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ManagerKPIUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $kpiType,
        public float $currentValue,
        public float $targetValue,
        public float $progressPercentage,
        public string $period,
        public array $metadata = []
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('manajer.kpi-updates'),
            new Channel('manajer.performance-updates'),
            new PrivateChannel('executive.dashboard'),
            new Channel('management.oversight'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Determine status based on progress
        $status = 'on_track';
        if ($this->progressPercentage >= 100) {
            $status = 'achieved';
        } elseif ($this->progressPercentage >= 80) {
            $status = 'on_track';
        } elseif ($this->progressPercentage >= 60) {
            $status = 'attention';
        } else {
            $status = 'critical';
        }

        return [
            'event' => 'kpi_updated',
            'kpi' => [
                'type' => $this->kpiType,
                'current_value' => $this->currentValue,
                'target_value' => $this->targetValue,
                'progress_percentage' => round($this->progressPercentage, 2),
                'status' => $status,
                'period' => $this->period,
                'metadata' => $this->metadata,
                'formatted' => [
                    'current' => number_format($this->currentValue, 0, ',', '.'),
                    'target' => number_format($this->targetValue, 0, ',', '.'),
                    'progress' => number_format($this->progressPercentage, 1) . '%'
                ]
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'manager.kpi.updated';
    }
}