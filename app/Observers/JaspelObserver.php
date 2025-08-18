<?php

namespace App\Observers;

use App\Models\Jaspel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * JASPEL Observer
 * 
 * Monitors JASPEL model events for data integrity and business rule compliance.
 * Provides real-time validation and anomaly detection.
 */
class JaspelObserver
{
    /**
     * Handle the Jaspel "creating" event.
     */
    public function creating(Jaspel $jaspel)
    {
        // Validate data integrity before creation
        $this->validateBusinessRules($jaspel);
        
        // Detect potential dummy data
        $this->detectDummyData($jaspel);
        
        // Monitor creation patterns
        $this->monitorCreationPatterns($jaspel);
        
        // Auto-set defaults
        $this->setDefaults($jaspel);
    }

    /**
     * Handle the Jaspel "created" event.
     */
    public function created(Jaspel $jaspel)
    {
        // Log successful creation
        Log::info('JASPEL record created', [
            'id' => $jaspel->id,
            'user_id' => $jaspel->user_id,
            'jenis_jaspel' => $jaspel->jenis_jaspel,
            'nominal' => $jaspel->nominal,
            'validation_status' => $jaspel->status_validasi,
            'tindakan_id' => $jaspel->tindakan_id
        ]);

        // Update statistics
        $this->updateStatistics($jaspel);
        
        // Check for anomalies after creation
        $this->checkPostCreationAnomalies($jaspel);
    }

    /**
     * Handle the Jaspel "updating" event.
     */
    public function updating(Jaspel $jaspel)
    {
        // Prevent modification of critical validated records
        if ($this->isProtectedRecord($jaspel)) {
            throw new \Exception('Cannot modify JASPEL record: Already validated and financially processed.');
        }

        // Log significant changes
        $this->logSignificantChanges($jaspel);
        
        // Validate updates
        $this->validateUpdates($jaspel);
    }

    /**
     * Handle the Jaspel "updated" event.
     */
    public function updated(Jaspel $jaspel)
    {
        Log::info('JASPEL record updated', [
            'id' => $jaspel->id,
            'user_id' => $jaspel->user_id,
            'changes' => $jaspel->getChanges()
        ]);

        // Invalidate related caches
        $this->invalidateRelatedCaches($jaspel);
    }

    /**
     * Handle the Jaspel "deleting" event.
     */
    public function deleting(Jaspel $jaspel)
    {
        // Prevent deletion of validated records
        if ($jaspel->status_validasi === 'disetujui') {
            throw new \Exception('Cannot delete validated JASPEL record. Contact administrator.');
        }

        // Log deletion attempt
        Log::warning('JASPEL record deletion attempted', [
            'id' => $jaspel->id,
            'user_id' => $jaspel->user_id,
            'jenis_jaspel' => $jaspel->jenis_jaspel,
            'nominal' => $jaspel->nominal,
            'deleted_by' => auth()->id()
        ]);
    }

    /**
     * Handle the Jaspel "deleted" event.
     */
    public function deleted(Jaspel $jaspel)
    {
        Log::info('JASPEL record deleted', [
            'id' => $jaspel->id,
            'user_id' => $jaspel->user_id
        ]);

        // Update statistics
        $this->updateStatisticsAfterDeletion($jaspel);
    }

    /**
     * Validate business rules
     */
    private function validateBusinessRules(Jaspel $jaspel)
    {
        // Rule 1: Validate amount reasonableness
        if ($jaspel->nominal <= 0) {
            throw new \Exception('JASPEL nominal must be positive.');
        }

        if ($jaspel->nominal > 10000000) { // > 10 million
            throw new \Exception('JASPEL nominal exceeds maximum allowed amount.');
        }

        // Rule 2: Validate date consistency
        if ($jaspel->tanggal > now()->addDays(7)) {
            throw new \Exception('JASPEL date cannot be more than 7 days in the future.');
        }

        if ($jaspel->tanggal < now()->subYear()) {
            throw new \Exception('JASPEL date cannot be more than 1 year in the past.');
        }

        // Rule 3: Validate jenis_jaspel consistency
        $validTypes = [
            'dokter_jaga_pagi', 'dokter_jaga_siang', 'dokter_jaga_malam',
            'tindakan_emergency', 'konsultasi_khusus', 'paramedis', 
            'dokter_umum', 'dokter_spesialis'
        ];

        if (!in_array($jaspel->jenis_jaspel, $validTypes)) {
            throw new \Exception('Invalid jenis_jaspel value.');
        }
    }

    /**
     * Detect potential dummy data
     */
    private function detectDummyData(Jaspel $jaspel)
    {
        $flags = [];

        // Check 1: Round number patterns
        if ($jaspel->nominal >= 100000 && $jaspel->nominal % 10000 == 0) {
            $flags[] = 'round_number';
        }

        // Check 2: Common dummy patterns
        $dummyPatterns = [123456, 234567, 345678, 456789, 111111, 222222, 333333];
        if (in_array($jaspel->nominal, $dummyPatterns)) {
            $flags[] = 'dummy_pattern';
        }

        // Check 3: Konsultasi khusus without tindakan_id
        if ($jaspel->jenis_jaspel === 'konsultasi_khusus' && !$jaspel->tindakan_id) {
            $flags[] = 'konsultasi_no_tindakan';
        }

        // Check 4: Rapid creation pattern
        $recentCount = Jaspel::where('user_id', $jaspel->user_id)
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();
        
        if ($recentCount > 5) {
            $flags[] = 'rapid_creation';
        }

        if (!empty($flags)) {
            Log::warning('Potential dummy JASPEL data detected', [
                'flags' => $flags,
                'jaspel' => [
                    'user_id' => $jaspel->user_id,
                    'jenis_jaspel' => $jaspel->jenis_jaspel,
                    'nominal' => $jaspel->nominal,
                    'tindakan_id' => $jaspel->tindakan_id
                ]
            ]);

            // In production, might block creation
            if (app()->environment('production') && in_array('dummy_pattern', $flags)) {
                throw new \Exception('Data appears to be test/dummy data. Creation blocked.');
            }
        }
    }

    /**
     * Monitor creation patterns
     */
    private function monitorCreationPatterns(Jaspel $jaspel)
    {
        $cacheKey = "jaspel_creation_monitor_{$jaspel->user_id}";
        $currentCount = Cache::get($cacheKey, 0);
        
        Cache::put($cacheKey, $currentCount + 1, 300); // 5 minutes

        // Alert on excessive creation
        if ($currentCount > 20) {
            Log::alert('Excessive JASPEL creation detected', [
                'user_id' => $jaspel->user_id,
                'count' => $currentCount + 1,
                'timeframe' => '5 minutes'
            ]);
        }
    }

    /**
     * Set default values
     */
    private function setDefaults(Jaspel $jaspel)
    {
        // Set default status if not provided
        if (!$jaspel->status_validasi) {
            $jaspel->status_validasi = 'pending';
        }

        // Set default total_jaspel if not provided
        if (!$jaspel->total_jaspel) {
            $jaspel->total_jaspel = $jaspel->nominal;
        }

        // Set input_by if not provided
        if (!$jaspel->input_by && auth()->check()) {
            $jaspel->input_by = auth()->id();
        }
    }

    /**
     * Update statistics
     */
    private function updateStatistics(Jaspel $jaspel)
    {
        // Update cache statistics
        $statsKey = "jaspel_stats_user_{$jaspel->user_id}";
        $stats = Cache::get($statsKey, [
            'total_count' => 0,
            'total_amount' => 0,
            'pending_count' => 0,
            'validated_count' => 0
        ]);

        $stats['total_count']++;
        $stats['total_amount'] += $jaspel->nominal;
        
        if ($jaspel->status_validasi === 'pending') {
            $stats['pending_count']++;
        } elseif ($jaspel->status_validasi === 'disetujui') {
            $stats['validated_count']++;
        }

        Cache::put($statsKey, $stats, 3600); // 1 hour
    }

    /**
     * Check post-creation anomalies
     */
    private function checkPostCreationAnomalies(Jaspel $jaspel)
    {
        // Check daily total for user
        $dailyTotal = Jaspel::where('user_id', $jaspel->user_id)
            ->whereDate('tanggal', $jaspel->tanggal)
            ->sum('nominal');

        if ($dailyTotal > 5000000) { // > 5 million per day
            Log::warning('High daily JASPEL total detected', [
                'user_id' => $jaspel->user_id,
                'date' => $jaspel->tanggal,
                'total' => $dailyTotal
            ]);
        }

        // Check for duplicate entries
        $duplicateCount = Jaspel::where('user_id', $jaspel->user_id)
            ->where('tanggal', $jaspel->tanggal)
            ->where('jenis_jaspel', $jaspel->jenis_jaspel)
            ->where('nominal', $jaspel->nominal)
            ->where('id', '!=', $jaspel->id)
            ->count();

        if ($duplicateCount > 0) {
            Log::warning('Potential duplicate JASPEL entry', [
                'new_id' => $jaspel->id,
                'user_id' => $jaspel->user_id,
                'duplicate_count' => $duplicateCount
            ]);
        }
    }

    /**
     * Check if record is protected from modification
     */
    private function isProtectedRecord(Jaspel $jaspel): bool
    {
        // Protect validated records
        if ($jaspel->getOriginal('status_validasi') === 'disetujui') {
            return true;
        }

        // Protect records older than 30 days
        if ($jaspel->created_at && $jaspel->created_at->lt(now()->subDays(30))) {
            return true;
        }

        return false;
    }

    /**
     * Log significant changes
     */
    private function logSignificantChanges(Jaspel $jaspel)
    {
        $changes = $jaspel->getDirty();
        $significantFields = ['nominal', 'status_validasi', 'jenis_jaspel'];
        
        $significantChanges = array_intersect_key($changes, array_flip($significantFields));
        
        if (!empty($significantChanges)) {
            Log::info('Significant JASPEL changes detected', [
                'id' => $jaspel->id,
                'user_id' => $jaspel->user_id,
                'changes' => $significantChanges,
                'original' => array_intersect_key($jaspel->getOriginal(), array_flip($significantFields)),
                'modified_by' => auth()->id()
            ]);
        }
    }

    /**
     * Validate updates
     */
    private function validateUpdates(Jaspel $jaspel)
    {
        $changes = $jaspel->getDirty();

        // Prevent status changes without proper authorization
        if (isset($changes['status_validasi']) && $changes['status_validasi'] === 'disetujui') {
            if (!auth()->user() || !auth()->user()->can('validate_jaspel')) {
                throw new \Exception('Insufficient permissions to validate JASPEL.');
            }
            
            // Set validation timestamp
            $jaspel->validasi_at = now();
            $jaspel->validasi_by = auth()->id();
        }

        // Prevent nominal changes for validated records
        if (isset($changes['nominal']) && $jaspel->getOriginal('status_validasi') === 'disetujui') {
            throw new \Exception('Cannot change nominal of validated JASPEL record.');
        }
    }

    /**
     * Invalidate related caches
     */
    private function invalidateRelatedCaches(Jaspel $jaspel)
    {
        // Clear user statistics cache
        Cache::forget("jaspel_stats_user_{$jaspel->user_id}");
        
        // Clear daily totals cache
        Cache::forget("jaspel_daily_total_{$jaspel->user_id}_{$jaspel->tanggal}");
        
        // Clear gaming data cache if it exists
        Cache::forget("validated_jaspel_gaming_{$jaspel->user_id}");
    }

    /**
     * Update statistics after deletion
     */
    private function updateStatisticsAfterDeletion(Jaspel $jaspel)
    {
        // Update cache statistics
        $statsKey = "jaspel_stats_user_{$jaspel->user_id}";
        $stats = Cache::get($statsKey);
        
        if ($stats) {
            $stats['total_count'] = max(0, $stats['total_count'] - 1);
            $stats['total_amount'] = max(0, $stats['total_amount'] - $jaspel->nominal);
            
            if ($jaspel->status_validasi === 'pending') {
                $stats['pending_count'] = max(0, $stats['pending_count'] - 1);
            } elseif ($jaspel->status_validasi === 'disetujui') {
                $stats['validated_count'] = max(0, $stats['validated_count'] - 1);
            }
            
            Cache::put($statsKey, $stats, 3600);
        }
    }
}