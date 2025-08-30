<?php

namespace App\Events;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Real-time attendance update event for WebSocket broadcasting
 * Triggered when check-in, check-out, or attendance data changes
 */
class AttendanceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Attendance $attendance;
    public User $user;
    public string $action; // 'checkin', 'checkout', 'update'
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(Attendance $attendance, string $action = 'update', array $metadata = [])
    {
        $this->attendance = $attendance->load(['location', 'shift', 'jadwalJaga']);
        $this->user = $attendance->user;
        $this->action = $action;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Private channel for the specific user
            new PrivateChannel('user.' . $this->user->id . '.attendance'),
            
            // General attendance channel for admin/managers
            new PrivateChannel('attendance.updates'),
            
            // Team/department channel
            new PrivateChannel('team.' . ($this->user->role_id ?? 'general') . '.attendance'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'attendance.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $workDuration = null;
        $formattedDuration = null;

        // Calculate duration if both check-in and check-out exist
        if ($this->attendance->time_in && $this->attendance->time_out) {
            $durationMinutes = $this->attendance->time_in->diffInMinutes($this->attendance->time_out);
            $hours = intval($durationMinutes / 60);
            $minutes = $durationMinutes % 60;
            
            $workDuration = [
                'minutes' => $durationMinutes,
                'hours' => $hours,
                'remaining_minutes' => $minutes,
            ];
            
            $formattedDuration = $hours . 'h ' . $minutes . 'm';
        }

        return [
            'attendance' => [
                'id' => $this->attendance->id,
                'user_id' => $this->attendance->user_id,
                'date' => $this->attendance->date->format('Y-m-d'),
                'time_in' => $this->attendance->time_in?->format('H:i'),
                'time_out' => $this->attendance->time_out?->format('H:i'),
                'status' => $this->attendance->status,
                'work_duration' => $workDuration,
                'formatted_duration' => $formattedDuration,
                'location' => [
                    'name_in' => $this->attendance->location_name_in,
                    'name_out' => $this->attendance->location_name_out,
                    'location' => $this->attendance->location ? [
                        'id' => $this->attendance->location->id,
                        'name' => $this->attendance->location->name,
                    ] : null,
                ],
                'shift' => $this->attendance->shift ? [
                    'id' => $this->attendance->shift->id,
                    'name' => $this->attendance->shift->nama_shift,
                    'start' => $this->attendance->shift->jam_masuk,
                    'end' => $this->attendance->shift->jam_pulang ?? $this->attendance->shift->jam_keluar,
                ] : null,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'role' => $this->user->role->name ?? 'user',
            ],
            'action' => $this->action,
            'timestamp' => now()->toISOString(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function shouldBroadcast(): bool
    {
        // Only broadcast if broadcasting is enabled in config
        return config('broadcasting.default') !== 'null' && 
               config('app.enable_realtime_attendance', true);
    }
}