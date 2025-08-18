<?php

namespace App\Observers;

use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Events\TindakanValidated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Tindakan Observer
 * 
 * Automatically handles JASPEL creation and real-time event broadcasting
 * when tindakan validation status changes.
 */
class TindakanObserver
{
    /**
     * Handle the Tindakan "updated" event.
     */
    public function updated(Tindakan $tindakan): void
    {
        // Check if validation status changed
        if ($tindakan->wasChanged('status_validasi')) {
            $this->handleValidationStatusChange($tindakan);
        }
        
        // Invalidate related caches
        $this->invalidateRelatedCaches($tindakan);
    }

    /**
     * Handle validation status changes
     */
    private function handleValidationStatusChange(Tindakan $tindakan): void
    {
        $newStatus = $tindakan->status_validasi;
        $oldStatus = $tindakan->getOriginal('status_validasi');
        
        Log::info('Tindakan validation status changed', [
            'tindakan_id' => $tindakan->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'dokter_id' => $tindakan->dokter_id,
            'changed_by' => Auth::id(),
        ]);

        // Handle approved status
        if ($newStatus === 'disetujui') {
            $this->handleApproval($tindakan);
        }
        
        // Handle rejected status
        if ($newStatus === 'ditolak') {
            $this->handleRejection($tindakan);
        }
        
        // Fire real-time event for all status changes
        $this->broadcastValidationEvent($tindakan, $newStatus);
    }

    /**
     * Handle tindakan approval
     */
    private function handleApproval(Tindakan $tindakan): void
    {
        try {
            // Auto-create JASPEL record if it doesn't exist
            $this->autoCreateJaspelRecord($tindakan);
            
            Log::info('Tindakan approved and JASPEL processed', [
                'tindakan_id' => $tindakan->id,
                'dokter_user_id' => $tindakan->dokter->user_id ?? null,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to process approved tindakan', [
                'tindakan_id' => $tindakan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle tindakan rejection
     */
    private function handleRejection(Tindakan $tindakan): void
    {
        try {
            // Update related JASPEL records to rejected status
            $relatedJaspel = Jaspel::where('tindakan_id', $tindakan->id)->get();
            
            foreach ($relatedJaspel as $jaspel) {
                $jaspel->update([
                    'status_validasi' => 'ditolak',
                    'validasi_by' => Auth::id(),
                    'validasi_at' => now(),
                    'catatan_validasi' => 'Ditolak bersamaan dengan tindakan - ' . ($tindakan->komentar_validasi ?? 'No comment'),
                ]);
            }
            
            Log::info('Tindakan rejected and related JASPEL updated', [
                'tindakan_id' => $tindakan->id,
                'jaspel_affected' => $relatedJaspel->count(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to process rejected tindakan', [
                'tindakan_id' => $tindakan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Auto-create JASPEL record for approved tindakan
     */
    private function autoCreateJaspelRecord(Tindakan $tindakan): void
    {
        // Skip if JASPEL already exists
        $existingJaspel = Jaspel::where('tindakan_id', $tindakan->id)
            ->where('user_id', $tindakan->dokter->user_id ?? 0)
            ->first();
            
        if ($existingJaspel) {
            Log::info('JASPEL already exists for tindakan', [
                'tindakan_id' => $tindakan->id,
                'jaspel_id' => $existingJaspel->id,
            ]);
            return;
        }

        // Calculate JASPEL amount using bendahara method
        $jaspelAmount = $this->calculateBendaharaJaspel($tindakan);
        
        if ($jaspelAmount <= 0) {
            Log::warning('Tindakan approved but no JASPEL amount calculated', [
                'tindakan_id' => $tindakan->id,
                'tarif' => $tindakan->tarif,
                'persentase_jaspel' => $tindakan->jenisTindakan->persentase_jaspel ?? 0,
            ]);
            return;
        }

        // Determine correct JASPEL category
        $jaspelCategory = $this->determineJaspelCategory($tindakan);
        
        // Create JASPEL record
        $jaspel = new Jaspel();
        $jaspel->user_id = $tindakan->dokter->user_id;
        $jaspel->tindakan_id = $tindakan->id;
        $jaspel->tanggal = $tindakan->tanggal_tindakan->format('Y-m-d');
        $jaspel->jenis_jaspel = $jaspelCategory;
        $jaspel->nominal = $jaspelAmount;
        $jaspel->total_jaspel = $jaspelAmount;
        $jaspel->status_validasi = 'disetujui'; // Auto-approve with tindakan
        $jaspel->validasi_at = $tindakan->validated_at ?? now();
        $jaspel->validasi_by = $tindakan->validated_by;
        $jaspel->input_by = $tindakan->dokter->user_id;
        $jaspel->catatan_validasi = "AUTO-CREATED: JASPEL {$tindakan->jenisTindakan->nama} - {$jaspelCategory} - Tervalidasi Bendahara";
        
        $jaspel->save();
        
        Log::info('Auto-created JASPEL for approved tindakan', [
            'tindakan_id' => $tindakan->id,
            'jaspel_id' => $jaspel->id,
            'amount' => $jaspelAmount,
            'category' => $jaspelCategory,
            'user_id' => $jaspel->user_id,
        ]);
    }

    /**
     * Calculate JASPEL using bendahara method
     */
    private function calculateBendaharaJaspel(Tindakan $tindakan): float
    {
        if (!$tindakan->jenisTindakan) {
            return 0;
        }
        
        $persentaseJaspel = $tindakan->jenisTindakan->persentase_jaspel ?? 0;
        
        if ($persentaseJaspel <= 0) {
            return 0;
        }
        
        return $tindakan->tarif * ($persentaseJaspel / 100);
    }

    /**
     * Determine correct JASPEL category based on performer
     */
    private function determineJaspelCategory(Tindakan $tindakan): string
    {
        // If dokter performed and got paid, it's dokter category
        if ($tindakan->dokter_id && $tindakan->jasa_dokter > 0) {
            return 'dokter_umum';
        }
        
        // If paramedis performed and got paid, it's paramedis category
        if ($tindakan->paramedis_id && $tindakan->jasa_paramedis > 0) {
            return 'paramedis';
        }
        
        // If both performed, prioritize dokter
        if ($tindakan->dokter_id && $tindakan->paramedis_id) {
            return 'dokter_umum';
        }
        
        // Default to dokter if dokter_id exists
        if ($tindakan->dokter_id) {
            return 'dokter_umum';
        }
        
        // Fallback to paramedis
        return 'paramedis';
    }

    /**
     * Broadcast validation event
     */
    private function broadcastValidationEvent(Tindakan $tindakan, string $status): void
    {
        try {
            $validator = Auth::user();
            $comment = $tindakan->komentar_validasi;
            
            // Fire the event
            event(new TindakanValidated($tindakan, $status, $validator, $comment));
            
            Log::info('TindakanValidated event broadcasted', [
                'tindakan_id' => $tindakan->id,
                'status' => $status,
                'dokter_user_id' => $tindakan->dokter->user_id ?? null,
                'channels' => ['dokter.' . ($tindakan->dokter->user_id ?? 'unknown'), 'bendahara.validations'],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to broadcast TindakanValidated event', [
                'tindakan_id' => $tindakan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate related caches
     */
    private function invalidateRelatedCaches(Tindakan $tindakan): void
    {
        $dokterUserId = $tindakan->dokter->user_id ?? null;
        
        if ($dokterUserId) {
            $currentMonth = now()->month;
            $currentYear = now()->year;
            $tindakanMonth = $tindakan->tanggal_tindakan->month;
            $tindakanYear = $tindakan->tanggal_tindakan->year;
            
            // Clear current month cache
            Cache::forget("validated_jaspel_user_{$dokterUserId}_{$currentYear}_{$currentMonth}");
            
            // Clear tindakan month cache if different
            if ($tindakanMonth !== $currentMonth || $tindakanYear !== $currentYear) {
                Cache::forget("validated_jaspel_user_{$dokterUserId}_{$tindakanYear}_{$tindakanMonth}");
            }
            
            // Clear summary caches
            Cache::forget("jaspel_summary_user_{$dokterUserId}");
            Cache::forget("gaming_stats_user_{$dokterUserId}");
            
            Log::info('Cache invalidated for user', [
                'user_id' => $dokterUserId,
                'tindakan_id' => $tindakan->id,
            ]);
        }
    }

    /**
     * Handle the Tindakan "created" event.
     */
    public function created(Tindakan $tindakan): void
    {
        Log::info('New tindakan created', [
            'tindakan_id' => $tindakan->id,
            'jenis_tindakan' => $tindakan->jenisTindakan->nama ?? 'Unknown',
            'dokter_id' => $tindakan->dokter_id,
            'dokter_user_id' => $tindakan->dokter->user_id ?? null,
            'status' => $tindakan->status_validasi,
            'input_by' => $tindakan->input_by,
        ]);
        
        // 🎯 SYSTEM-WIDE: Fire input created event for all panels to know
        try {
            $inputBy = \App\Models\User::find($tindakan->input_by);
            event(new \App\Events\TindakanInputCreated($tindakan, $inputBy));
            
            Log::info('TindakanInputCreated event fired', [
                'tindakan_id' => $tindakan->id,
                'input_by' => $inputBy?->name ?? 'Unknown',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fire TindakanInputCreated event', [
                'tindakan_id' => $tindakan->id,
                'error' => $e->getMessage(),
            ]);
        }
        
        // If created with approved status (rare but possible), handle immediately
        if ($tindakan->status_validasi === 'disetujui') {
            $this->handleApproval($tindakan);
            $this->broadcastValidationEvent($tindakan, 'disetujui');
        }
    }

    /**
     * Handle the Tindakan "deleted" event.
     */
    public function deleted(Tindakan $tindakan): void
    {
        // Clean up related JASPEL records
        $relatedJaspel = Jaspel::where('tindakan_id', $tindakan->id)->get();
        
        foreach ($relatedJaspel as $jaspel) {
            $jaspel->delete();
        }
        
        // Invalidate caches
        $this->invalidateRelatedCaches($tindakan);
        
        Log::info('Tindakan deleted and related data cleaned up', [
            'tindakan_id' => $tindakan->id,
            'jaspel_cleaned' => $relatedJaspel->count(),
        ]);
    }
}