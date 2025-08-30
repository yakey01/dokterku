<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ManagerRevenueUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public float $todayRevenue,
        public float $monthlyRevenue,
        public float $changePercentage,
        public string $date,
        public array $sources = []
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('financial.updates'),
            new Channel('management.oversight'),
            new Channel('manajer.kpi-updates'),
            new PrivateChannel('executive.dashboard'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'event' => 'revenue_updated',
            'data' => [
                'today_revenue' => $this->todayRevenue,
                'monthly_revenue' => $this->monthlyRevenue,
                'change_percentage' => $this->changePercentage,
                'date' => $this->date,
                'sources' => $this->sources,
                'formatted' => [
                    'today' => 'Rp ' . number_format($this->todayRevenue, 0, ',', '.'),
                    'monthly' => 'Rp ' . number_format($this->monthlyRevenue, 0, ',', '.'),
                    'change' => ($this->changePercentage >= 0 ? '+' : '') . number_format($this->changePercentage, 1) . '%'
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
        return 'manager.revenue.updated';
    }
}