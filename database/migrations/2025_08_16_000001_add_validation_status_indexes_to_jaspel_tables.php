<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add indexes and ensure validation status consistency for validated JASPEL system
     */
    public function up(): void
    {
        // Add indexes to jaspel table for validation queries
        Schema::table('jaspel', function (Blueprint $table) {
            // Compound index for validated JASPEL queries
            $table->index(['user_id', 'status_validasi', 'tanggal'], 'idx_jaspel_user_validation_date');
            
            // Index for validation status filtering
            $table->index(['status_validasi'], 'idx_jaspel_validation_status');
            
            // Index for monthly validation queries
            $table->index(['user_id', 'tanggal', 'status_validasi'], 'idx_jaspel_user_date_validation');
        });

        // Add indexes to tindakan table for validation queries
        Schema::table('tindakan', function (Blueprint $table) {
            // Compound index for validated procedure JASPEL
            $table->index(['paramedis_id', 'status_validasi', 'tanggal_tindakan'], 'idx_tindakan_paramedis_validation_date');
            
            // Index for validation status filtering
            $table->index(['status_validasi'], 'idx_tindakan_validation_status');
            
            // Index for JASPEL calculation queries
            $table->index(['paramedis_id', 'jasa_paramedis', 'status_validasi'], 'idx_tindakan_jaspel_calc');
        });

        // Add indexes to jumlah_pasien_harians table for validation queries
        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            // Compound index for validated patient count JASPEL
            $table->index(['dokter_id', 'status_validasi', 'tanggal'], 'idx_pasien_dokter_validation_date');
            
            // Index for validation status filtering
            $table->index(['status_validasi'], 'idx_pasien_validation_status');
            
            // Index for JASPEL calculation queries
            $table->index(['dokter_id', 'tanggal', 'jaspel_rupiah'], 'idx_pasien_jaspel_calc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jaspel', function (Blueprint $table) {
            $table->dropIndex('idx_jaspel_user_validation_date');
            $table->dropIndex('idx_jaspel_validation_status');
            $table->dropIndex('idx_jaspel_user_date_validation');
        });

        Schema::table('tindakan', function (Blueprint $table) {
            $table->dropIndex('idx_tindakan_paramedis_validation_date');
            $table->dropIndex('idx_tindakan_validation_status');
            $table->dropIndex('idx_tindakan_jaspel_calc');
        });

        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            $table->dropIndex('idx_pasien_dokter_validation_date');
            $table->dropIndex('idx_pasien_validation_status');
            $table->dropIndex('idx_pasien_jaspel_calc');
        });
    }
};