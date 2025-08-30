<?php

namespace App\Listeners;

use App\Events\DataInputDisimpan;
use App\Events\ExpenseCreated;
use App\Events\IncomeCreated;
use App\Events\JaspelSelesai;
use App\Events\JaspelUpdated;
use App\Events\PatientCreated;
use App\Events\TindakanInputCreated;
use App\Events\TindakanValidated;
use App\Events\UserCreated;
use App\Events\ValidasiBerhasil;
use App\Events\ValidationStatusReset;
use App\Events\WorkLocationUpdated;
use App\Enums\TelegramNotificationType;
use App\Services\NotificationDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * EnhancedTelegramNotificationListener
 * 
 * Unified event listener that handles all notification events and dispatches
 * them through the NotificationDispatcherService for intelligent routing.
 */
class EnhancedTelegramNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected NotificationDispatcherService $dispatcher;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationDispatcherService $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handle income created events
     */
    public function handleIncomeCreated(IncomeCreated $event): void
    {
        Log::info('Handling IncomeCreated event for Telegram notification');

        $data = [
            'amount' => $event->income->jumlah,
            'description' => $event->income->deskripsi,
            'date' => $event->income->tanggal->format('d/m/Y'),
            'shift' => $event->income->shift,
            'petugas' => $event->income->inputUser->name ?? 'Unknown',
            'user_id' => $event->income->input_by,
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::PENDAPATAN->value,
            $data,
            ['priority' => 'normal']
        );
    }

    /**
     * Handle expense created events
     */
    public function handleExpenseCreated(ExpenseCreated $event): void
    {
        Log::info('Handling ExpenseCreated event for Telegram notification');

        $data = [
            'amount' => $event->expense->jumlah,
            'description' => $event->expense->deskripsi,
            'date' => $event->expense->tanggal->format('d/m/Y'),
            'shift' => $event->expense->shift,
            'petugas' => $event->expense->inputUser->name ?? 'Unknown',
            'user_id' => $event->expense->input_by,
        ];

        $options = ['priority' => 'normal'];
        
        // High priority for large expenses
        if ($event->expense->jumlah >= 1000000) {
            $options['priority'] = 'high';
        }

        $this->dispatcher->dispatch(
            TelegramNotificationType::PENGELUARAN->value,
            $data,
            $options
        );
    }

    /**
     * Handle patient created events
     */
    public function handlePatientCreated(PatientCreated $event): void
    {
        Log::info('Handling PatientCreated event for Telegram notification');

        $data = [
            'patient_name' => $event->patient->nama,
            'patient_id' => $event->patient->id,
            'alamat' => $event->patient->alamat,
            'telepon' => $event->patient->telepon,
            'jenis_kelamin' => $event->patient->jenis_kelamin,
            'tanggal_lahir' => $event->patient->tanggal_lahir?->format('d/m/Y'),
            'registered_by' => auth()->user()->name ?? 'System',
            'user_id' => auth()->id(),
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::PASIEN->value,
            $data,
            ['priority' => 'normal']
        );
    }

    /**
     * Handle user created events
     */
    public function handleUserCreated(UserCreated $event): void
    {
        Log::info('Handling UserCreated event for Telegram notification');

        $data = [
            'user_name' => $event->user->name,
            'username' => $event->user->username,
            'email' => $event->user->email,
            'role' => $event->user->role->display_name ?? 'Unknown',
            'created_by' => auth()->user()->name ?? 'System',
            'user_id' => $event->user->id,
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::USER_BARU->value,
            $data,
            ['priority' => 'normal']
        );
    }

    /**
     * Handle tindakan input created events
     */
    public function handleTindakanInputCreated(TindakanInputCreated $event): void
    {
        Log::info('Handling TindakanInputCreated event for Telegram notification');

        $data = [
            'patient_name' => $event->tindakan->pasien->nama ?? 'Unknown',
            'procedure' => $event->tindakan->jenisTindakan->nama ?? 'Unknown',
            'dokter_name' => $event->tindakan->dokter->nama_lengkap ?? 'Unknown',
            'dokter_id' => $event->tindakan->dokter_id,
            'paramedis_name' => $event->tindakan->paramedis->nama_lengkap ?? null,
            'paramedis_id' => $event->tindakan->paramedis_id,
            'tarif' => $event->tindakan->tarif,
            'tanggal_tindakan' => $event->tindakan->tanggal_tindakan->format('d/m/Y H:i'),
            'input_by' => $event->tindakan->inputUser->name ?? 'Unknown',
            'user_id' => $event->tindakan->input_by,
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::TINDAKAN_BARU->value,
            $data,
            ['priority' => 'normal']
        );

        // Also trigger validation pending notification
        $this->dispatcher->dispatch(
            TelegramNotificationType::VALIDASI_PENDING->value,
            array_merge($data, [
                'type' => 'Tindakan Medis',
                'amount' => $event->tindakan->tarif,
                'date' => $event->tindakan->tanggal_tindakan->format('d/m/Y'),
            ]),
            ['priority' => 'normal']
        );
    }

    /**
     * Handle tindakan validated events
     */
    public function handleTindakanValidated(TindakanValidated $event): void
    {
        Log::info('Handling TindakanValidated event for Telegram notification');

        if ($event->validationStatus === 'disetujui') {
            $data = [
                'type' => 'Tindakan Medis',
                'amount' => $event->tindakan->tarif,
                'description' => $event->tindakan->jenisTindakan->nama ?? 'Unknown',
                'date' => $event->tindakan->tanggal_tindakan->format('d/m/Y'),
                'patient_name' => $event->tindakan->pasien->nama ?? 'Unknown',
                'dokter_name' => $event->tindakan->dokter->nama_lengkap ?? 'Unknown',
                'dokter_id' => $event->tindakan->dokter_id,
                'paramedis_id' => $event->tindakan->paramedis_id,
                'validator_name' => $event->validator->name ?? 'Bendahara',
                'input_by' => $event->tindakan->input_by,
                'exclude_user_id' => $event->validator->id, // Don't notify the validator
            ];

            $this->dispatcher->dispatch(
                TelegramNotificationType::VALIDASI_DISETUJUI->value,
                $data,
                ['priority' => 'high'] // High priority for approvals
            );

            // If JASPEL should be created, notify about that too
            if ($event->jaspelInfo['should_create'] ?? false) {
                $jaspelData = [
                    'dokter_name' => $event->tindakan->dokter->nama_lengkap ?? 'Unknown',
                    'dokter_id' => $event->tindakan->dokter_id,
                    'jaspel_amount' => $event->jaspelInfo['amount'] ?? 0,
                    'procedure' => $event->tindakan->jenisTindakan->nama ?? 'Unknown',
                    'patient_name' => $event->tindakan->pasien->nama ?? 'Unknown',
                ];

                $this->dispatcher->dispatch(
                    TelegramNotificationType::JASPEL_DOKTER_READY->value,
                    $jaspelData,
                    ['priority' => 'high']
                );
            }
        }
    }

    /**
     * Handle jaspel completed events
     */
    public function handleJaspelSelesai(JaspelSelesai $event): void
    {
        Log::info('Handling JaspelSelesai event for Telegram notification');

        $data = [
            'doctor_name' => $event->tindakan->dokter->nama_lengkap ?? 'Unknown',
            'dokter_id' => $event->tindakan->dokter_id,
            'jaspel_amount' => $event->totalJaspel,
            'procedure' => $event->tindakan->jenisTindakan->nama ?? 'Unknown',
            'patient_name' => $event->tindakan->pasien->nama ?? 'Unknown',
            'total_records' => $event->jaspelRecords,
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::JASPEL_SELESAI->value,
            $data,
            ['priority' => 'high']
        );
    }

    /**
     * Handle jaspel updated events
     */
    public function handleJaspelUpdated(JaspelUpdated $event): void
    {
        Log::info('Handling JaspelUpdated event for Telegram notification');

        // Get dokter information
        $dokter = $event->jaspel->dokter ?? null;
        $jaspelData = $event->aggregatedData ?? [];

        $data = [
            'dokter_name' => $dokter->nama_lengkap ?? 'Unknown',
            'dokter_id' => $dokter->id ?? null,
            'total_jaspel' => $jaspelData['total_jaspel'] ?? 0,
            'total_procedures' => $jaspelData['total_procedures'] ?? 0,
            'period' => $jaspelData['period'] ?? now()->format('F Y'),
            'update_type' => $event->updateType ?? 'calculation_updated',
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::JASPEL_DOKTER_READY->value,
            $data,
            ['priority' => 'high']
        );
    }

    /**
     * Handle validation berhasil events (legacy support)
     */
    public function handleValidasiBerhasil(ValidasiBerhasil $event): void
    {
        Log::info('Handling ValidasiBerhasil event for Telegram notification');

        if ($event->status === 'disetujui') {
            $data = [
                'type' => $event->type,
                'validator_name' => $event->validator->name,
                'data_id' => $event->data->id,
            ];

            $this->dispatcher->dispatch(
                TelegramNotificationType::VALIDASI_DISETUJUI->value,
                $data,
                ['priority' => 'high']
            );
        }
    }

    /**
     * Handle data input disimpan events (legacy support)
     */
    public function handleDataInputDisimpan(DataInputDisimpan $event): void
    {
        Log::info('Handling DataInputDisimpan event for Telegram notification');

        $data = [
            'type' => $event->type,
            'user_name' => $event->user->name,
            'user_role' => $event->user->role->display_name ?? 'Unknown',
            'user_id' => $event->user->id,
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::VALIDASI_PENDING->value,
            $data,
            ['priority' => 'normal']
        );
    }

    /**
     * Handle work location updated events
     */
    public function handleWorkLocationUpdated(WorkLocationUpdated $event): void
    {
        Log::info('Handling WorkLocationUpdated event for Telegram notification');

        $data = [
            'location_name' => $event->workLocation->nama,
            'updated_by' => auth()->user()->name ?? 'System',
            'description' => 'Lokasi kerja telah diperbarui',
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::SISTEM_MAINTENANCE->value,
            $data,
            ['priority' => 'low']
        );
    }

    /**
     * Handle attendance events (when implemented)
     */
    public function handleAttendanceCheckin(object $event): void
    {
        Log::info('Handling attendance check-in event');

        $isDokter = $event->user->hasRole('dokter');
        $isParamedis = $event->user->hasRole('paramedis');

        if ($isDokter || $isParamedis) {
            $notificationType = $isDokter 
                ? TelegramNotificationType::PRESENSI_DOKTER->value
                : TelegramNotificationType::PRESENSI_PARAMEDIS->value;

            $data = [
                'staff_name' => $event->user->name,
                'type' => 'Check-in',
                'time' => $event->time ?? now()->format('H:i:s'),
                'shift' => $event->shift ?? 'Unknown',
                'location' => $event->location ?? 'Rumah Sakit',
                'user_id' => $event->user->id,
            ];

            $this->dispatcher->dispatch(
                $notificationType,
                $data,
                ['priority' => 'normal']
            );
        }
    }

    /**
     * Handle emergency alert events
     */
    public function handleEmergencyAlert(object $event): void
    {
        Log::info('Handling emergency alert event');

        $data = [
            'level' => $event->level ?? 'HIGH',
            'location' => $event->location ?? 'Rumah Sakit',
            'description' => $event->description ?? 'Situasi emergency detected',
            'reporter' => $event->reporter ?? auth()->user()->name ?? 'System',
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::EMERGENCY_ALERT->value,
            $data,
            ['priority' => 'emergency']
        );
    }

    /**
     * Handle system maintenance events
     */
    public function handleSystemMaintenance(object $event): void
    {
        Log::info('Handling system maintenance event');

        $data = [
            'type' => $event->type ?? 'Maintenance terjadwal',
            'start_time' => $event->start_time ?? 'Segera',
            'end_time' => $event->end_time ?? null,
            'affected_services' => $event->affected_services ?? 'Semua layanan',
            'description' => $event->description ?? 'Maintenance sistem',
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::SISTEM_MAINTENANCE->value,
            $data,
            ['priority' => 'high']
        );
    }

    /**
     * Handle shift report events
     */
    public function handleShiftReport(object $event): void
    {
        Log::info('Handling shift report event');

        $data = [
            'shift' => $event->shift ?? 'Unknown',
            'date' => $event->date ?? now()->format('d/m/Y'),
            'pic' => $event->pic ?? 'Unknown',
            'patient_count' => $event->patient_count ?? 0,
            'procedure_count' => $event->procedure_count ?? 0,
            'notes' => $event->notes ?? '',
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::LAPORAN_SHIFT->value,
            $data,
            ['priority' => 'normal']
        );
    }

    /**
     * Handle schedule update events
     */
    public function handleScheduleUpdate(object $event): void
    {
        Log::info('Handling schedule update event');

        $data = [
            'staff_name' => $event->staff_name ?? 'Unknown',
            'date' => $event->date ?? now()->format('d/m/Y'),
            'shift' => $event->shift ?? 'Unknown',
            'old_shift' => $event->old_shift ?? null,
            'update_type' => $event->update_type ?? 'Perubahan jadwal',
            'reason' => $event->reason ?? null,
            'user_id' => $event->user_id ?? null,
            'affected_role' => $event->affected_role ?? null,
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::JADWAL_JAGA_UPDATE->value,
            $data,
            ['priority' => 'high']
        );
    }

    /**
     * Handle leave request events
     */
    public function handleLeaveRequest(object $event): void
    {
        Log::info('Handling leave request event');

        $data = [
            'staff_name' => $event->staff_name ?? 'Unknown',
            'leave_type' => $event->leave_type ?? 'Cuti',
            'start_date' => $event->start_date ?? 'Unknown',
            'end_date' => $event->end_date ?? 'Unknown',
            'duration' => $event->duration ?? 0,
            'reason' => $event->reason ?? 'Tidak disebutkan',
            'status' => $event->status ?? 'Menunggu persetujuan',
            'user_id' => $event->user_id ?? null,
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::CUTI_REQUEST->value,
            $data,
            ['priority' => 'normal']
        );
    }

    /**
     * Handle shift assignment events
     */
    public function handleShiftAssignment(object $event): void
    {
        Log::info('Handling shift assignment event');

        $data = [
            'staff_name' => $event->staff_name ?? 'Unknown',
            'role' => $event->role ?? 'Unknown',
            'date' => $event->date ?? now()->format('d/m/Y'),
            'shift' => $event->shift ?? 'Unknown',
            'work_hours' => $event->work_hours ?? 'Unknown',
            'location' => $event->location ?? null,
            'user_id' => $event->user_id ?? null,
        ];

        $this->dispatcher->dispatch(
            TelegramNotificationType::SHIFT_ASSIGNMENT->value,
            $data,
            ['priority' => 'normal']
        );
    }

    /**
     * Handle validation status reset events
     */
    public function handleValidationStatusReset(ValidationStatusReset $event): void
    {
        Log::info('Handling ValidationStatusReset event for Telegram notification');

        $data = $event->data;
        
        // Create bendahara notification message
        $message = "🔄 *Status Validasi Di-reset*\n\n";
        $message .= "📋 *{$data['model_type']}*\n";
        
        if ($data['model_type'] === 'Tindakan') {
            $message .= "👥 Pasien: {$data['patient']}\n";
            $message .= "⚕️ Tindakan: {$data['procedure']}\n";
            $message .= "👨‍⚕️ Dokter: {$data['doctor']}\n";
            $message .= "💰 Tarif: Rp " . number_format($data['tarif'], 0, ',', '.') . "\n";
        } else if ($data['model_type'] === 'JumlahPasienHarian') {
            $message .= "👨‍⚕️ Dokter: {$data['doctor']}\n";
            $message .= "👥 Total Pasien: {$data['total_pasien']}\n";
        }
        
        $message .= "📅 Tanggal: {$data['date']}\n";
        $message .= "👤 Diedit oleh: {$data['user_name']}\n";
        $message .= "🔧 Field yang diubah: " . implode(', ', $data['changed_fields']) . "\n";
        $message .= "⏳ Status: {$data['original_status']} → {$data['new_status']}\n\n";
        $message .= "❗ *Perlu validasi ulang di panel bendahara*";

        // Send to bendahara notification channel
        $this->dispatcher->dispatch(
            TelegramNotificationType::VALIDASI_PENDING->value,
            [
                'type' => $data['model_type'],
                'message' => $message,
                'model_id' => $data['model_id'],
                'original_status' => $data['original_status'],
                'new_status' => $data['new_status'],
                'edited_by' => $data['user_name'],
                'date' => $data['date'],
                'priority' => 'high', // High priority for validation resets
            ],
            ['priority' => 'high']
        );
    }

    /**
     * Generic event handler for unknown events
     */
    public function handle($event): void
    {
        $eventClass = get_class($event);
        
        Log::info('Handling event for enhanced Telegram notifications', [
            'event_class' => $eventClass,
        ]);

        // Route to specific handlers based on event type
        match ($eventClass) {
            IncomeCreated::class => $this->handleIncomeCreated($event),
            ExpenseCreated::class => $this->handleExpenseCreated($event),
            PatientCreated::class => $this->handlePatientCreated($event),
            UserCreated::class => $this->handleUserCreated($event),
            TindakanInputCreated::class => $this->handleTindakanInputCreated($event),
            TindakanValidated::class => $this->handleTindakanValidated($event),
            JaspelSelesai::class => $this->handleJaspelSelesai($event),
            JaspelUpdated::class => $this->handleJaspelUpdated($event),
            ValidasiBerhasil::class => $this->handleValidasiBerhasil($event),
            DataInputDisimpan::class => $this->handleDataInputDisimpan($event),
            WorkLocationUpdated::class => $this->handleWorkLocationUpdated($event),
            ValidationStatusReset::class => $this->handleValidationStatusReset($event),
            default => Log::info('No specific handler for event', ['event_class' => $eventClass])
        };
    }
}