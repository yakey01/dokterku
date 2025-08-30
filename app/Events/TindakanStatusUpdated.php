<?php

namespace App\Events;

use App\Models\Tindakan;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TindakanStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tindakan;
    public $oldStatus;
    public $oldStatusValidasi;
    public $updatedBy;

    public function __construct(
        Tindakan $tindakan,
        ?string $oldStatus = null,
        ?string $oldStatusValidasi = null,
        ?int $updatedBy = null
    ) {
        $this->tindakan = $tindakan;
        $this->oldStatus = $oldStatus;
        $this->oldStatusValidasi = $oldStatusValidasi;
        $this->updatedBy = $updatedBy;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('tindakan-updates'),
            new PrivateChannel('petugas-dashboard'),
            new PrivateChannel('bendahara-validation'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->tindakan->id,
            'status' => $this->tindakan->status,
            'status_validasi' => $this->tindakan->status_validasi,
            'old_status' => $this->oldStatus,
            'old_status_validasi' => $this->oldStatusValidasi,
            'patient_name' => $this->tindakan->pasien?->nama,
            'procedure_name' => $this->tindakan->jenisTindakan?->nama,
            'doctor_name' => $this->tindakan->dokter?->nama_lengkap,
            'updated_by' => $this->updatedBy,
            'updated_at' => $this->tindakan->updated_at->toISOString(),
            'status_display' => $this->getStatusDisplay(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.updated';
    }

    private function getStatusDisplay(): string
    {
        // Same logic as TindakanResource status_gabungan
        if ($this->tindakan->status_validasi === 'disetujui') {
            return 'disetujui';
        }
        
        if ($this->tindakan->status_validasi === 'ditolak') {
            return 'ditolak';
        }
        
        if ($this->tindakan->status_validasi === 'pending') {
            return match($this->tindakan->status) {
                'batal' => 'batal',
                'selesai' => 'selesai_belum_validasi',
                'pending' => 'pending',
                default => 'pending'
            };
        }
        
        return 'pending';
    }
}