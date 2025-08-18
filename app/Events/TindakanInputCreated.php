<?php

namespace App\Events;

use App\Models\Tindakan;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TindakanInputCreated Event
 * 
 * Broadcasts when new tindakan is created by petugas.
 * Notifies bendahara and relevant medical staff for awareness.
 */
class TindakanInputCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Tindakan $tindakan;
    public ?User $inputBy;

    public function __construct(Tindakan $tindakan, ?User $inputBy = null)
    {
        $this->tindakan = $tindakan;
        $this->inputBy = $inputBy;

        Log::info('TindakanInputCreated event created', [
            'tindakan_id' => $this->tindakan->id,
            'jenis_tindakan' => $this->tindakan->jenisTindakan->nama ?? 'Unknown',
            'dokter_user_id' => $this->getDokterUserId(),
            'input_by' => $this->inputBy?->name ?? 'System',
        ]);
    }

    public function broadcastOn(): array
    {
        $channels = [];
        
        // Notify bendahara (validation required)
        $channels[] = new Channel('validation.updates');
        
        // Notify the dokter who performed the procedure
        $dokterUserId = $this->getDokterUserId();
        if ($dokterUserId) {
            $channels[] = new PrivateChannel("dokter.{$dokterUserId}");
        }
        
        // Notify all medical staff
        $channels[] = new Channel('medical.procedures');
        
        // Notify management oversight
        $channels[] = new Channel('management.oversight');

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'tindakan.input.created';
    }

    public function broadcastWith(): array
    {
        return [
            'event_type' => 'tindakan_input_created',
            'timestamp' => now()->toISOString(),
            'tindakan' => [
                'id' => $this->tindakan->id,
                'jenis_tindakan' => $this->tindakan->jenisTindakan->nama ?? 'Unknown',
                'tanggal_tindakan' => $this->tindakan->tanggal_tindakan->format('Y-m-d H:i:s'),
                'tarif' => $this->tindakan->tarif,
                'pasien' => $this->tindakan->pasien->nama ?? 'Unknown',
                'status' => $this->tindakan->status_validasi,
            ],
            'input_info' => [
                'input_by' => $this->inputBy?->name ?? 'System',
                'input_at' => $this->tindakan->created_at->format('Y-m-d H:i:s'),
                'requires_validation' => true,
            ],
            'dokter' => [
                'user_id' => $this->getDokterUserId(),
                'nama' => $this->tindakan->dokter->nama_lengkap ?? 'Unknown',
            ],
            'notification' => [
                'title' => '📝 Tindakan Baru Diinput',
                'message' => $this->getNotificationMessage(),
                'type' => 'info',
                'action_required' => 'validation_needed',
                'urgency' => 'normal',
            ]
        ];
    }

    private function getDokterUserId(): ?int
    {
        return $this->tindakan->dokter->user_id ?? null;
    }

    private function getNotificationMessage(): string
    {
        $jenisTindakan = $this->tindakan->jenisTindakan->nama ?? 'Tindakan';
        $tanggal = $this->tindakan->tanggal_tindakan->format('d/m/Y');
        $inputBy = $this->inputBy?->name ?? 'Petugas';
        $pasien = $this->tindakan->pasien->nama ?? 'Pasien';

        return "{$jenisTindakan} untuk pasien {$pasien} ({$tanggal}) telah diinput oleh {$inputBy}. Menunggu validasi bendahara.";
    }
}