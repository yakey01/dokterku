<?php

namespace App\Events;

use App\Models\Jaspel;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * JaspelUpdated Event
 * 
 * Broadcasts when JASPEL amounts are updated or validated.
 * Notifies relevant users about their financial updates.
 */
class JaspelUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Jaspel $jaspel;
    public string $updateType;
    public ?User $updatedBy;
    public ?string $comment;

    public function __construct(
        Jaspel $jaspel, 
        string $updateType = 'amount_updated', 
        ?User $updatedBy = null, 
        ?string $comment = null
    ) {
        $this->jaspel = $jaspel;
        $this->updateType = $updateType;
        $this->updatedBy = $updatedBy;
        $this->comment = $comment;

        Log::info('JaspelUpdated event created', [
            'jaspel_id' => $this->jaspel->id,
            'user_id' => $this->jaspel->user_id,
            'update_type' => $this->updateType,
            'amount' => $this->jaspel->nominal,
            'status' => $this->jaspel->status_validasi,
        ]);
    }

    public function broadcastOn(): array
    {
        $channels = [];
        
        // Private channel for the JASPEL owner
        if ($this->jaspel->user_id) {
            $user = User::find($this->jaspel->user_id);
            if ($user) {
                $role = $user->roles->first()->name ?? 'user';
                $channels[] = new PrivateChannel("{$role}.{$this->jaspel->user_id}");
            }
        }
        
        // Public financial updates channel
        $channels[] = new Channel('financial.updates');
        
        // Medical procedures channel if tindakan-related
        if ($this->jaspel->tindakan_id) {
            $channels[] = new Channel('medical.procedures');
        }
        
        // Management oversight for significant amounts
        if ($this->jaspel->nominal > 100000) { // > 100K
            $channels[] = new Channel('management.oversight');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'jaspel.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'event_type' => 'jaspel_updated',
            'timestamp' => now()->toISOString(),
            'jaspel' => [
                'id' => $this->jaspel->id,
                'user_id' => $this->jaspel->user_id,
                'jenis_jaspel' => $this->jaspel->jenis_jaspel,
                'nominal' => $this->jaspel->nominal,
                'tanggal' => $this->jaspel->tanggal,
                'status_validasi' => $this->jaspel->status_validasi,
                'tindakan_id' => $this->jaspel->tindakan_id,
            ],
            'update_info' => [
                'type' => $this->updateType,
                'updated_by' => $this->updatedBy?->name ?? 'System',
                'updated_at' => now()->format('Y-m-d H:i:s'),
                'comment' => $this->comment,
            ],
            'tindakan' => $this->jaspel->tindakan ? [
                'jenis' => $this->jaspel->tindakan->jenisTindakan->nama ?? 'Unknown',
                'tanggal' => $this->jaspel->tindakan->tanggal_tindakan->format('Y-m-d'),
                'pasien' => $this->jaspel->tindakan->pasien->nama ?? 'Unknown',
            ] : null,
            'notification' => [
                'title' => $this->getNotificationTitle(),
                'message' => $this->getNotificationMessage(),
                'type' => $this->jaspel->status_validasi === 'disetujui' ? 'success' : 
                         ($this->jaspel->status_validasi === 'ditolak' ? 'error' : 'info'),
                'action_required' => false,
            ]
        ];
    }

    private function getNotificationTitle(): string
    {
        return match($this->updateType) {
            'validated' => '✅ JASPEL Divalidasi',
            'rejected' => '❌ JASPEL Ditolak',
            'amount_updated' => '💰 JASPEL Diperbarui',
            'created' => '🆕 JASPEL Baru',
            default => '📋 JASPEL Updated'
        };
    }

    private function getNotificationMessage(): string
    {
        $jenisJaspel = ucfirst(str_replace('_', ' ', $this->jaspel->jenis_jaspel));
        $amount = 'Rp ' . number_format($this->jaspel->nominal);
        $date = $this->jaspel->tanggal;

        return match($this->updateType) {
            'validated' => "{$jenisJaspel} {$amount} ({$date}) telah divalidasi dan siap dicairkan.",
            'rejected' => "{$jenisJaspel} {$amount} ({$date}) ditolak. " . ($this->comment ?: "Silakan perbaiki dan ajukan ulang."),
            'amount_updated' => "{$jenisJaspel} diperbarui menjadi {$amount} ({$date}).",
            'created' => "{$jenisJaspel} baru {$amount} ({$date}) telah dibuat dan menunggu validasi.",
            default => "{$jenisJaspel} {$amount} ({$date}) telah diperbarui."
        };
    }
}