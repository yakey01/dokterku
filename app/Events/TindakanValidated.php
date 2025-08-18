<?php

namespace App\Events;

use App\Models\Tindakan;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TindakanValidated Event
 * 
 * Broadcasts real-time notifications when tindakan validation status changes.
 * Enables instant synchronization between bendahara validation and dokter dashboard.
 */
class TindakanValidated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Tindakan $tindakan;
    public string $validationStatus;
    public ?User $validator;
    public ?string $comment;
    public array $jaspelInfo;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Tindakan $tindakan, 
        string $validationStatus, 
        ?User $validator = null, 
        ?string $comment = null
    ) {
        $this->tindakan = $tindakan;
        $this->validationStatus = $validationStatus;
        $this->validator = $validator;
        $this->comment = $comment;
        
        // Calculate JASPEL information for the event
        $this->jaspelInfo = $this->calculateJaspelInfo();

        Log::info('TindakanValidated event created', [
            'tindakan_id' => $this->tindakan->id,
            'status' => $this->validationStatus,
            'dokter_user_id' => $this->getDokterUserId(),
            'jaspel_amount' => $this->jaspelInfo['amount'],
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Private channel for the specific dokter
        $dokterUserId = $this->getDokterUserId();
        if ($dokterUserId) {
            $channels[] = new PrivateChannel("dokter.{$dokterUserId}");
        }
        
        // 🎯 SYSTEM-WIDE CHANNELS: Notify all relevant parties
        
        // Public validation updates (all roles can listen)
        $channels[] = new Channel('validation.updates');
        
        // Medical procedures channel (dokter, paramedis, petugas)
        $channels[] = new Channel('medical.procedures');
        
        // Financial updates (bendahara, manajer, admin)
        if (in_array($this->validationStatus, ['disetujui', 'ditolak'])) {
            $channels[] = new Channel('financial.updates');
        }
        
        // Management oversight for approved procedures
        if ($this->validationStatus === 'disetujui') {
            $channels[] = new Channel('management.oversight');
        }
        
        // 🎯 ROLE-SPECIFIC NOTIFICATIONS
        
        // Notify paramedis if they're involved in the procedure
        if ($this->tindakan->paramedis_id) {
            $paramedisUserId = $this->tindakan->paramedis->user_id ?? null;
            if ($paramedisUserId) {
                $channels[] = new PrivateChannel("paramedis.{$paramedisUserId}");
            }
        }
        
        // Notify petugas who input the data
        if ($this->tindakan->input_by) {
            $petugasUser = \App\Models\User::find($this->tindakan->input_by);
            if ($petugasUser && $petugasUser->hasRole('petugas')) {
                $channels[] = new PrivateChannel("petugas.{$this->tindakan->input_by}");
            }
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'tindakan.validated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'event_type' => 'tindakan_validation',
            'timestamp' => now()->toISOString(),
            'tindakan' => [
                'id' => $this->tindakan->id,
                'jenis_tindakan' => $this->tindakan->jenisTindakan->nama ?? 'Unknown',
                'tanggal_tindakan' => $this->tindakan->tanggal_tindakan->format('Y-m-d H:i:s'),
                'tarif' => $this->tindakan->tarif,
                'pasien' => $this->tindakan->pasien->nama ?? 'Unknown',
            ],
            'validation' => [
                'status' => $this->validationStatus,
                'previous_status' => $this->tindakan->getOriginal('status_validasi'),
                'validated_by' => $this->validator ? $this->validator->name : 'System',
                'validated_at' => now()->format('Y-m-d H:i:s'),
                'comment' => $this->comment,
            ],
            'jaspel' => $this->jaspelInfo,
            'dokter' => [
                'user_id' => $this->getDokterUserId(),
                'nama' => $this->tindakan->dokter->nama_lengkap ?? 'Unknown',
            ],
            'notification' => [
                'title' => $this->getNotificationTitle(),
                'message' => $this->getNotificationMessage(),
                'type' => $this->validationStatus === 'disetujui' ? 'success' : 
                         ($this->validationStatus === 'ditolak' ? 'error' : 'info'),
                'action_required' => false,
            ]
        ];
    }

    /**
     * Get dokter user ID for targeting the right user
     */
    private function getDokterUserId(): ?int
    {
        return $this->tindakan->dokter->user_id ?? null;
    }

    /**
     * Calculate JASPEL information
     */
    private function calculateJaspelInfo(): array
    {
        $jaspelAmount = 0;
        $category = 'dokter_umum';
        
        // Use the bendahara-compatible calculation
        if ($this->tindakan->jenisTindakan) {
            $persentaseJaspel = $this->tindakan->jenisTindakan->persentase_jaspel ?? 0;
            if ($persentaseJaspel > 0) {
                $jaspelAmount = $this->tindakan->tarif * ($persentaseJaspel / 100);
            }
        }
        
        // Determine category based on performer
        if ($this->tindakan->dokter_id && $this->tindakan->jasa_dokter > 0) {
            $category = 'dokter_umum';
        } elseif ($this->tindakan->paramedis_id && $this->tindakan->jasa_paramedis > 0) {
            $category = 'paramedis';
        }

        return [
            'amount' => $jaspelAmount,
            'category' => $category,
            'should_create' => $this->validationStatus === 'disetujui' && $jaspelAmount > 0,
            'calculation_method' => 'bendahara_compatible',
        ];
    }

    /**
     * Get notification title
     */
    private function getNotificationTitle(): string
    {
        return match($this->validationStatus) {
            'disetujui' => '✅ Tindakan Disetujui',
            'ditolak' => '❌ Tindakan Ditolak', 
            'pending' => '⏳ Menunggu Validasi',
            default => '📋 Status Validasi Diperbarui'
        };
    }

    /**
     * Get notification message
     */
    private function getNotificationMessage(): string
    {
        $jenisTindakan = $this->tindakan->jenisTindakan->nama ?? 'Tindakan';
        $tanggal = $this->tindakan->tanggal_tindakan->format('d/m/Y');
        $validator = $this->validator->name ?? 'Bendahara';

        $baseMessage = "{$jenisTindakan} ({$tanggal})";

        return match($this->validationStatus) {
            'disetujui' => "{$baseMessage} telah disetujui oleh {$validator}. JASPEL Rp " . number_format($this->jaspelInfo['amount']) . " siap dicairkan.",
            'ditolak' => "{$baseMessage} ditolak oleh {$validator}. " . ($this->comment ? "Alasan: {$this->comment}" : "Silakan perbaiki dan ajukan ulang."),
            'pending' => "{$baseMessage} dikembalikan ke status pending untuk review ulang.",
            default => "{$baseMessage} status diperbarui ke {$this->validationStatus}."
        };
    }

    /**
     * Determine if the event should be queued for broadcasting.
     */
    public function shouldQueue(): bool
    {
        return false; // Use ShouldBroadcastNow for immediate broadcasting
    }
}