<?php

namespace App\Services;

use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Financial Sync Service
 * 
 * Handles synchronization between daily financial records and master financial records
 * Ensures consistency between bendahara validation and manager dashboard data
 */
class FinancialSyncService
{
    /**
     * Sync all approved daily records to master tables
     */
    public function syncAllApprovedRecords(): array
    {
        DB::beginTransaction();
        
        try {
            $synced = [
                'revenue' => $this->syncRevenueRecords(),
                'expenses' => $this->syncExpenseRecords()
            ];
            
            DB::commit();
            
            Log::info('FinancialSyncService: All approved records synced', $synced);
            
            return [
                'success' => true,
                'message' => 'All approved financial records synchronized successfully',
                'data' => $synced
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('FinancialSyncService: Sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to sync financial records',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync approved revenue daily records to master pendapatan table
     */
    public function syncRevenueRecords(): array
    {
        $approvedDaily = PendapatanHarian::where('status_validasi', 'approved')
            ->whereNotNull('pendapatan_id')
            ->with('pendapatan')
            ->get();
        
        $synced = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($approvedDaily as $daily) {
            try {
                if ($daily->pendapatan && $daily->pendapatan->status_validasi !== 'disetujui') {
                    // Update master record status and amount
                    $daily->pendapatan->update([
                        'status_validasi' => 'disetujui',
                        'nominal' => $daily->nominal,
                        'validasi_by' => $daily->validasi_by,
                        'validasi_at' => $daily->validasi_at,
                        'keterangan' => $daily->deskripsi ?? $daily->pendapatan->keterangan
                    ]);
                    
                    $synced++;
                    
                    Log::info('Revenue record synced', [
                        'daily_id' => $daily->id,
                        'master_id' => $daily->pendapatan->id,
                        'amount' => $daily->nominal
                    ]);
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to sync revenue record', [
                    'daily_id' => $daily->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'type' => 'revenue',
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_processed' => $approvedDaily->count()
        ];
    }

    /**
     * Sync approved expense daily records to master pengeluaran table
     */
    public function syncExpenseRecords(): array
    {
        $approvedDaily = PengeluaranHarian::where('status_validasi', 'approved')
            ->whereNotNull('pengeluaran_id')
            ->with('pengeluaran')
            ->get();
        
        $synced = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($approvedDaily as $daily) {
            try {
                if ($daily->pengeluaran && $daily->pengeluaran->status_validasi !== 'disetujui') {
                    // Update master record status and amount
                    $daily->pengeluaran->update([
                        'status_validasi' => 'disetujui',
                        'nominal' => $daily->nominal,
                        'validasi_by' => $daily->validasi_by,
                        'validasi_at' => $daily->validasi_at,
                        'keterangan' => $daily->deskripsi ?? $daily->pengeluaran->keterangan
                    ]);
                    
                    $synced++;
                    
                    Log::info('Expense record synced', [
                        'daily_id' => $daily->id,
                        'master_id' => $daily->pengeluaran->id,
                        'amount' => $daily->nominal
                    ]);
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to sync expense record', [
                    'daily_id' => $daily->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'type' => 'expenses',
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_processed' => $approvedDaily->count()
        ];
    }

    /**
     * Sync specific daily record to master table
     */
    public function syncRecordToMaster($dailyRecord): bool
    {
        try {
            if ($dailyRecord instanceof PendapatanHarian) {
                return $this->syncSingleRevenueRecord($dailyRecord);
            } elseif ($dailyRecord instanceof PengeluaranHarian) {
                return $this->syncSingleExpenseRecord($dailyRecord);
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('FinancialSyncService: Failed to sync single record', [
                'record_id' => $dailyRecord->id,
                'record_type' => get_class($dailyRecord),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Sync single revenue record
     */
    private function syncSingleRevenueRecord(PendapatanHarian $daily): bool
    {
        if (!$daily->pendapatan || $daily->status_validasi !== 'approved') {
            return false;
        }
        
        $daily->pendapatan->update([
            'status_validasi' => 'disetujui',
            'nominal' => $daily->nominal,
            'validasi_by' => $daily->validasi_by,
            'validasi_at' => $daily->validasi_at,
            'keterangan' => $daily->deskripsi ?? $daily->pendapatan->keterangan
        ]);
        
        return true;
    }

    /**
     * Sync single expense record
     */
    private function syncSingleExpenseRecord(PengeluaranHarian $daily): bool
    {
        if (!$daily->pengeluaran || $daily->status_validasi !== 'approved') {
            return false;
        }
        
        $daily->pengeluaran->update([
            'status_validasi' => 'disetujui',
            'nominal' => $daily->nominal,
            'validasi_by' => $daily->validasi_by,
            'validasi_at' => $daily->validasi_at,
            'keterangan' => $daily->deskripsi ?? $daily->pengeluaran->keterangan
        ]);
        
        return true;
    }

    /**
     * Get sync status and statistics
     */
    public function getSyncStatus(): array
    {
        try {
            // Revenue sync status
            $revenueStatus = $this->getRevenueSyncStatus();
            
            // Expense sync status  
            $expenseStatus = $this->getExpenseSyncStatus();
            
            return [
                'success' => true,
                'data' => [
                    'revenue' => $revenueStatus,
                    'expenses' => $expenseStatus,
                    'last_check' => now()->toISOString(),
                    'sync_health' => $this->calculateSyncHealth($revenueStatus, $expenseStatus)
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('FinancialSyncService: Failed to get sync status', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to get sync status',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get revenue sync status
     */
    private function getRevenueSyncStatus(): array
    {
        $approvedDaily = PendapatanHarian::where('status_validasi', 'approved')->count();
        $syncedMaster = Pendapatan::where('status_validasi', 'disetujui')->count();
        $pendingSync = PendapatanHarian::where('status_validasi', 'approved')
            ->whereHas('pendapatan', function($q) {
                $q->where('status_validasi', '!=', 'disetujui');
            })
            ->count();
        
        return [
            'approved_daily' => $approvedDaily,
            'synced_master' => $syncedMaster,
            'pending_sync' => $pendingSync,
            'sync_percentage' => $approvedDaily > 0 ? round(($syncedMaster / $approvedDaily) * 100, 2) : 100
        ];
    }

    /**
     * Get expense sync status
     */
    private function getExpenseSyncStatus(): array
    {
        $approvedDaily = PengeluaranHarian::where('status_validasi', 'approved')->count();
        $syncedMaster = Pengeluaran::where('status_validasi', 'disetujui')->count();
        $pendingSync = PengeluaranHarian::where('status_validasi', 'approved')
            ->whereHas('pengeluaran', function($q) {
                $q->where('status_validasi', '!=', 'disetujui');
            })
            ->count();
        
        return [
            'approved_daily' => $approvedDaily,
            'synced_master' => $syncedMaster,
            'pending_sync' => $pendingSync,
            'sync_percentage' => $approvedDaily > 0 ? round(($syncedMaster / $approvedDaily) * 100, 2) : 100
        ];
    }

    /**
     * Calculate overall sync health
     */
    private function calculateSyncHealth(array $revenueStatus, array $expenseStatus): string
    {
        $avgSyncPercentage = ($revenueStatus['sync_percentage'] + $expenseStatus['sync_percentage']) / 2;
        
        if ($avgSyncPercentage >= 95) {
            return 'excellent';
        } elseif ($avgSyncPercentage >= 80) {
            return 'good';
        } elseif ($avgSyncPercentage >= 60) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Manual sync trigger for emergency fixes
     */
    public function emergencySync(): array
    {
        Log::warning('FinancialSyncService: Emergency sync triggered');
        
        return $this->syncAllApprovedRecords();
    }
}