<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Constants\ValidationStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Standardize all validation status values across tables
     */
    public function up(): void
    {
        // Tables that have status_validasi column
        $tables = [
            'jaspels',
            'jumlah_pasien_harians',
            'tindakans',
            'pendapatan_harians',
            'pengeluaran_harians',
            'pendapatans',
            'pengeluarans',
        ];
        
        DB::transaction(function() use ($tables) {
            foreach ($tables as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'status_validasi')) {
                    $this->standardizeTableStatus($table);
                }
            }
        });
        
        // Add indexes for better query performance
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'status_validasi')) {
                try {
                    Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                        // Try to add index, ignore if already exists
                        $indexName = $tableName . '_status_validasi_index';
                        $table->index('status_validasi', $indexName);
                    });
                } catch (\Exception $e) {
                    // Index already exists, continue
                    \Log::info("Index already exists for {$tableName}: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Standardize status values in a table
     */
    private function standardizeTableStatus(string $tableName): void
    {
        // Log current status distribution
        $currentDistribution = DB::table($tableName)
            ->select('status_validasi', DB::raw('COUNT(*) as count'))
            ->groupBy('status_validasi')
            ->get();
            
        \Log::info("Standardizing status_validasi in {$tableName}", [
            'before' => $currentDistribution->toArray()
        ]);
        
        // Update legacy 'disetujui' to 'approved'
        $updatedDisetujui = DB::table($tableName)
            ->where('status_validasi', ValidationStatus::LEGACY_APPROVED)
            ->update(['status_validasi' => ValidationStatus::APPROVED]);
            
        // Update legacy 'ditolak' to 'rejected'
        $updatedDitolak = DB::table($tableName)
            ->where('status_validasi', ValidationStatus::LEGACY_REJECTED)
            ->update(['status_validasi' => ValidationStatus::REJECTED]);
            
        // Log results
        \Log::info("Standardized {$tableName}", [
            'disetujui_to_approved' => $updatedDisetujui,
            'ditolak_to_rejected' => $updatedDitolak,
        ]);
        
        // Verify final distribution
        $finalDistribution = DB::table($tableName)
            ->select('status_validasi', DB::raw('COUNT(*) as count'))
            ->groupBy('status_validasi')
            ->get();
            
        \Log::info("Final status_validasi distribution in {$tableName}", [
            'after' => $finalDistribution->toArray()
        ]);
    }
    
    /**
     * Check if an index exists (SQLite compatible)
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        try {
            // For SQLite, we'll just try to create the index and catch the error if it exists
            // This is simpler than parsing SQLite's index listing
            return false; // Always try to create, SQLite will handle duplicates
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Reverse the migrations.
     * This is intentionally not implemented to prevent accidental rollback
     */
    public function down(): void
    {
        // We don't reverse this migration to maintain data consistency
        // If needed, manually update the status values
        \Log::warning('Attempted to rollback validation status standardization - this is not recommended');
    }
};