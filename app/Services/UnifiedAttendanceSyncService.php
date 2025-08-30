<?php

namespace App\Services;

use App\Models\JadwalJaga;
use App\Models\Attendance;
use App\Models\DokterPresensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

/**
 * Unified Attendance Sync Service
 * 
 * Memastikan 100% konsistensi data antara JadwalJaga dan History
 * dengan menggunakan JadwalJaga sebagai single source of truth
 */
class UnifiedAttendanceSyncService
{
    /**
     * Sync attendance data from JadwalJaga to History
     * Dipanggil saat status JadwalJaga berubah ke 'Completed'
     */
    public function syncJadwalCompletionToHistory(int $jadwalJagaId): array
    {
        try {
            $jadwal = JadwalJaga::with(['shiftTemplate', 'user'])->find($jadwalJagaId);
            
            if (!$jadwal) {
                throw new \Exception('JadwalJaga not found');
            }

            Log::info('Syncing jadwal completion to history', [
                'jadwal_id' => $jadwalJagaId,
                'user_id' => $jadwal->pegawai_id,
                'status_jaga' => $jadwal->status_jaga,
                'tanggal_jaga' => $jadwal->tanggal_jaga
            ]);

            // 1. Update atau create attendance record sesuai jadwal yang completed
            $syncResult = $this->ensureAttendanceMatchesJadwal($jadwal);
            
            // 2. Clear related caches untuk immediate update
            $this->clearUserCaches($jadwal->pegawai_id);
            
            // 3. Broadcast event untuk real-time frontend update
            $this->broadcastSyncEvent($jadwal, $syncResult);
            
            return [
                'success' => true,
                'message' => 'Jadwal completion synced to history successfully',
                'data' => [
                    'jadwal_id' => $jadwalJagaId,
                    'sync_result' => $syncResult,
                    'timestamp' => now()->toISOString()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to sync jadwal completion', [
                'jadwal_id' => $jadwalJagaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to sync: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ensure attendance record matches completed jadwal
     */
    protected function ensureAttendanceMatchesJadwal(JadwalJaga $jadwal): array
    {
        $userId = $jadwal->pegawai_id;
        $tanggal = $jadwal->tanggal_jaga;
        
        // Check if attendance record exists for this jadwal
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $tanggal)
            ->where('jadwal_jaga_id', $jadwal->id)
            ->first();
            
        if (!$attendance) {
            // Check if there's any attendance for this date
            $anyAttendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $tanggal)
                ->first();
                
            if ($anyAttendance) {
                // Link existing attendance to this jadwal
                $anyAttendance->jadwal_jaga_id = $jadwal->id;
                $anyAttendance->save();
                
                return [
                    'action' => 'linked_existing_attendance',
                    'attendance_id' => $anyAttendance->id,
                    'details' => 'Linked existing attendance to completed jadwal'
                ];
            } else {
                // Create attendance record for completed jadwal
                $newAttendance = $this->createAttendanceForCompletedJadwal($jadwal);
                
                return [
                    'action' => 'created_attendance_record',
                    'attendance_id' => $newAttendance->id,
                    'details' => 'Created attendance record for completed jadwal'
                ];
            }
        }
        
        // Attendance exists, ensure it's marked as completed
        if (!$attendance->time_out && $jadwal->status_jaga === 'Completed') {
            $this->completeAttendanceRecord($attendance, $jadwal);
            
            return [
                'action' => 'completed_attendance_record',
                'attendance_id' => $attendance->id,
                'details' => 'Marked attendance as completed based on jadwal status'
            ];
        }
        
        return [
            'action' => 'no_action_needed',
            'attendance_id' => $attendance->id,
            'details' => 'Attendance already matches jadwal status'
        ];
    }

    /**
     * Create attendance record untuk jadwal yang sudah completed
     */
    protected function createAttendanceForCompletedJadwal(JadwalJaga $jadwal): Attendance
    {
        $shiftTemplate = $jadwal->shiftTemplate;
        $tanggal = $jadwal->tanggal_jaga;
        
        // Default times dari shift template
        $defaultCheckIn = Carbon::parse($tanggal->format('Y-m-d') . ' ' . ($shiftTemplate->jam_masuk ?? '08:00'));
        $defaultCheckOut = Carbon::parse($tanggal->format('Y-m-d') . ' ' . ($shiftTemplate->jam_pulang ?? '16:00'));
        
        // Handle overnight shifts
        if ($shiftTemplate && $shiftTemplate->is_overnight) {
            $defaultCheckOut->addDay();
        }
        
        $attendance = Attendance::create([
            'user_id' => $jadwal->pegawai_id,
            'date' => $tanggal,
            'time_in' => $defaultCheckIn->format('H:i:s'),
            'time_out' => $defaultCheckOut->format('H:i:s'),
            'status' => 'present',
            'jadwal_jaga_id' => $jadwal->id,
            'shift_id' => $jadwal->shift_template_id,
            'shift_start' => $shiftTemplate->jam_masuk ?? '08:00',
            'shift_end' => $shiftTemplate->jam_pulang ?? '16:00',
            'logical_work_minutes' => ($shiftTemplate->durasi_jam ?? 8) * 60, // Full shift duration
            'notes' => 'Auto-created from completed JadwalJaga status',
            'check_in_metadata' => [
                'auto_created' => true,
                'source' => 'jadwal_jaga_completed',
                'jadwal_id' => $jadwal->id,
                'created_at' => now()->toISOString()
            ],
            'check_out_metadata' => [
                'auto_completed' => true,
                'source' => 'jadwal_jaga_status',
                'reason' => 'jadwal_marked_completed'
            ]
        ]);

        Log::info('Created attendance record from completed jadwal', [
            'attendance_id' => $attendance->id,
            'jadwal_id' => $jadwal->id,
            'user_id' => $jadwal->pegawai_id,
            'date' => $tanggal->format('Y-m-d')
        ]);

        return $attendance;
    }

    /**
     * Complete attendance record berdasarkan jadwal completion
     */
    protected function completeAttendanceRecord(Attendance $attendance, JadwalJaga $jadwal): void
    {
        $shiftTemplate = $jadwal->shiftTemplate;
        $tanggal = $jadwal->tanggal_jaga;
        
        // Auto check-out at shift end time
        $checkOutTime = Carbon::parse($tanggal->format('Y-m-d') . ' ' . ($shiftTemplate->jam_pulang ?? '16:00'));
        
        $attendance->time_out = $checkOutTime->format('H:i:s');
        $attendance->logical_time_out = $checkOutTime->format('H:i:s');
        
        // Set logical work duration to full shift duration
        if (!$attendance->logical_work_minutes) {
            $attendance->logical_work_minutes = ($shiftTemplate->durasi_jam ?? 8) * 60;
        }
        
        $attendance->check_out_metadata = array_merge(
            $attendance->check_out_metadata ?? [],
            [
                'auto_completed_from_jadwal' => true,
                'jadwal_id' => $jadwal->id,
                'jadwal_status' => $jadwal->status_jaga,
                'completed_at' => now()->toISOString(),
                'reason' => 'jadwal_marked_completed'
            ]
        );
        
        $attendance->notes = ($attendance->notes ? $attendance->notes . ' | ' : '') 
            . 'Auto-completed from JadwalJaga status: ' . $jadwal->status_jaga;
            
        $attendance->save();

        Log::info('Completed attendance record from jadwal status', [
            'attendance_id' => $attendance->id,
            'jadwal_id' => $jadwal->id,
            'auto_checkout_time' => $checkOutTime->format('H:i:s')
        ]);
    }

    /**
     * Clear user caches untuk immediate frontend update
     */
    protected function clearUserCaches(int $userId): void
    {
        $cachePatterns = [
            "unified_attendance_{$userId}_*",
            "dokter_dashboard_{$userId}_*", 
            "jadwal_jaga_{$userId}_*",
            "presensi_history_{$userId}_*"
        ];

        // Clear all cache (simplified approach)
        Cache::flush();

        Log::info('Cleared user caches for real-time sync', [
            'user_id' => $userId,
            'patterns' => $cachePatterns
        ]);
    }

    /**
     * Broadcast sync event untuk real-time frontend update
     */
    protected function broadcastSyncEvent(JadwalJaga $jadwal, array $syncResult): void
    {
        try {
            // Dispatch custom event untuk frontend listening
            Event::dispatch('attendance.synced', [
                'user_id' => $jadwal->pegawai_id,
                'jadwal_id' => $jadwal->id,
                'date' => $jadwal->tanggal_jaga->format('Y-m-d'),
                'status' => $jadwal->status_jaga,
                'sync_result' => $syncResult,
                'timestamp' => now()->toISOString()
            ]);

            // Broadcast via WebSocket if available
            if (class_exists('\Pusher\Pusher')) {
                broadcast(new \App\Events\AttendanceUpdated([
                    'user_id' => $jadwal->pegawai_id,
                    'type' => 'jadwal_completed',
                    'data' => [
                        'jadwal_id' => $jadwal->id,
                        'date' => $jadwal->tanggal_jaga->format('Y-m-d'),
                        'status' => $jadwal->status_jaga
                    ]
                ]));
            }

        } catch (\Exception $e) {
            Log::warning('Failed to broadcast sync event', [
                'jadwal_id' => $jadwal->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate data consistency between JadwalJaga and Attendance
     */
    public function validateDataConsistency(int $userId, Carbon $dateRange = null): array
    {
        $dateRange = $dateRange ?? Carbon::now()->subDays(30);
        
        // Get all jadwal jaga dalam range
        $jadwalRecords = JadwalJaga::where('pegawai_id', $userId)
            ->where('tanggal_jaga', '>=', $dateRange)
            ->with('shiftTemplate')
            ->get();
            
        $inconsistencies = [];
        
        foreach ($jadwalRecords as $jadwal) {
            $issues = $this->checkJadwalConsistency($jadwal);
            if (!empty($issues)) {
                $inconsistencies[] = [
                    'jadwal_id' => $jadwal->id,
                    'date' => $jadwal->tanggal_jaga->format('Y-m-d'),
                    'status_jaga' => $jadwal->status_jaga,
                    'issues' => $issues
                ];
            }
        }
        
        return [
            'total_checked' => $jadwalRecords->count(),
            'inconsistencies_found' => count($inconsistencies),
            'consistency_rate' => $jadwalRecords->count() > 0 ? 
                round((($jadwalRecords->count() - count($inconsistencies)) / $jadwalRecords->count()) * 100, 1) : 100,
            'details' => $inconsistencies
        ];
    }

    /**
     * Check consistency untuk single jadwal record
     */
    protected function checkJadwalConsistency(JadwalJaga $jadwal): array
    {
        $issues = [];
        
        // Check 1: Completed jadwal should have attendance record
        if ($jadwal->status_jaga === 'Completed') {
            $attendance = Attendance::where('user_id', $jadwal->pegawai_id)
                ->whereDate('date', $jadwal->tanggal_jaga)
                ->where('jadwal_jaga_id', $jadwal->id)
                ->first();
                
            if (!$attendance) {
                $issues[] = 'Completed jadwal missing attendance record';
            } elseif (!$attendance->time_out) {
                $issues[] = 'Completed jadwal has incomplete attendance (no check-out)';
            }
        }
        
        // Check 2: Active jadwal pada hari lalu should be completed atau have reason
        $yesterday = Carbon::yesterday('Asia/Jakarta');
        if ($jadwal->tanggal_jaga->isSameDay($yesterday) && $jadwal->status_jaga === 'Aktif') {
            $issues[] = 'Yesterday jadwal still active (should be completed or have status change)';
        }
        
        // Check 3: Jadwal without shift template
        if (!$jadwal->shiftTemplate) {
            $issues[] = 'Jadwal missing shift template';
        }
        
        return $issues;
    }

    /**
     * Auto-complete jadwal berdasarkan attendance completion
     */
    public function autoCompleteJadwalFromAttendance(int $attendanceId): array
    {
        try {
            $attendance = Attendance::with('jadwalJaga')->find($attendanceId);
            
            if (!$attendance) {
                throw new \Exception('Attendance not found');
            }
            
            if (!$attendance->hasCheckedOut()) {
                return [
                    'success' => false,
                    'message' => 'Attendance not completed yet'
                ];
            }
            
            if (!$attendance->jadwalJaga) {
                return [
                    'success' => false,
                    'message' => 'No jadwal jaga linked to this attendance'
                ];
            }
            
            $jadwal = $attendance->jadwalJaga;
            
            // Update jadwal status to completed
            if ($jadwal->status_jaga !== 'Completed') {
                $jadwal->status_jaga = 'Completed';
                $jadwal->keterangan = ($jadwal->keterangan ? $jadwal->keterangan . ' | ' : '') 
                    . 'Auto-completed from attendance check-out';
                $jadwal->save();
                
                Log::info('Auto-completed jadwal from attendance', [
                    'jadwal_id' => $jadwal->id,
                    'attendance_id' => $attendanceId,
                    'user_id' => $attendance->user_id
                ]);
                
                // Clear caches untuk immediate update
                $this->clearUserCaches($attendance->user_id);
                
                return [
                    'success' => true,
                    'message' => 'Jadwal auto-completed from attendance',
                    'action' => 'jadwal_completed'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Jadwal already completed',
                'action' => 'no_change'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to auto-complete jadwal', [
                'attendance_id' => $attendanceId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to auto-complete jadwal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sync bidirectional: JadwalJaga ↔ Attendance consistency
     */
    public function syncBidirectionalConsistency(int $userId): array
    {
        $today = Carbon::today('Asia/Jakarta');
        $syncActions = [];

        // 1. Find completed jadwal without proper attendance
        $completedJadwal = JadwalJaga::where('pegawai_id', $userId)
            ->where('status_jaga', 'Completed')
            ->where('tanggal_jaga', '>=', $today->copy()->subDays(7)) // Last 7 days
            ->get();

        foreach ($completedJadwal as $jadwal) {
            $syncResult = $this->ensureAttendanceMatchesJadwal($jadwal);
            if ($syncResult['action'] !== 'no_action_needed') {
                $syncActions[] = $syncResult;
            }
        }

        // 2. Find completed attendance without updated jadwal status
        $completedAttendance = Attendance::where('user_id', $userId)
            ->whereNotNull('time_out')
            ->whereDate('date', '>=', $today->copy()->subDays(7))
            ->whereNotNull('jadwal_jaga_id')
            ->with('jadwalJaga')
            ->get();

        foreach ($completedAttendance as $attendance) {
            if ($attendance->jadwalJaga && $attendance->jadwalJaga->status_jaga !== 'Completed') {
                $autoCompleteResult = $this->autoCompleteJadwalFromAttendance($attendance->id);
                if ($autoCompleteResult['success']) {
                    $syncActions[] = $autoCompleteResult;
                }
            }
        }

        // 3. Clear all user caches after sync
        $this->clearUserCaches($userId);

        return [
            'success' => true,
            'message' => 'Bidirectional sync completed',
            'data' => [
                'total_actions' => count($syncActions),
                'actions_performed' => $syncActions,
                'timestamp' => now()->toISOString()
            ]
        ];
    }

    /**
     * Get data consistency report untuk debugging
     */
    public function getConsistencyReport(int $userId): array
    {
        $report = $this->validateDataConsistency($userId);
        
        // Add additional insights
        $user = User::find($userId);
        $dokter = $user ? Dokter::where('user_id', $userId)->first() : null;
        
        $summary = [
            'user_info' => [
                'user_id' => $userId,
                'user_name' => $user?->name ?? 'Unknown',
                'dokter_id' => $dokter?->id ?? null
            ],
            'data_sources' => [
                'jadwal_jaga_records' => JadwalJaga::where('pegawai_id', $userId)->count(),
                'attendance_records' => Attendance::where('user_id', $userId)->count(),
                'dokter_presensi_records' => $dokter ? DokterPresensi::where('dokter_id', $dokter->id)->count() : 0
            ],
            'consistency_status' => $report['consistency_rate'] >= 95 ? 'excellent' : 
                                   ($report['consistency_rate'] >= 80 ? 'good' : 'needs_attention'),
            'report_generated_at' => now()->toISOString()
        ];

        return array_merge($summary, $report);
    }
}