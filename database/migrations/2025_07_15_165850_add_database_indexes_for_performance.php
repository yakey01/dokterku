<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper function to safely add index
        $addIndexIfNotExists = function($table, $columns, $indexName) {
            if (!Schema::hasIndex($table, $indexName)) {
                Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                    if (is_array($columns)) {
                        $blueprint->index($columns, $indexName);
                    } else {
                        $blueprint->index($columns, $indexName);
                    }
                });
            }
        };

        // Indexes for pasien table
        $addIndexIfNotExists('pasien', 'created_at', 'idx_pasien_created_at');
        $addIndexIfNotExists('pasien', 'jenis_kelamin', 'idx_pasien_jenis_kelamin');
        $addIndexIfNotExists('pasien', 'tanggal_lahir', 'idx_pasien_tanggal_lahir');
        $addIndexIfNotExists('pasien', ['jenis_kelamin', 'created_at'], 'idx_pasien_gender_created');
        $addIndexIfNotExists('pasien', 'no_rekam_medis', 'idx_pasien_no_rekam_medis');
        $addIndexIfNotExists('pasien', 'nama', 'idx_pasien_nama');
        $addIndexIfNotExists('pasien', 'deleted_at', 'idx_pasien_deleted_at');

        // Indexes for tindakan table
        $addIndexIfNotExists('tindakan', 'pasien_id', 'idx_tindakan_pasien_id');
        $addIndexIfNotExists('tindakan', 'dokter_id', 'idx_tindakan_dokter_id');
        $addIndexIfNotExists('tindakan', 'jenis_tindakan_id', 'idx_tindakan_jenis_tindakan_id');
        $addIndexIfNotExists('tindakan', 'tanggal_tindakan', 'idx_tindakan_tanggal');
        $addIndexIfNotExists('tindakan', 'status', 'idx_tindakan_status');
        $addIndexIfNotExists('tindakan', 'status_validasi', 'idx_tindakan_status_validasi');
        $addIndexIfNotExists('tindakan', 'created_at', 'idx_tindakan_created_at');
        $addIndexIfNotExists('tindakan', 'validated_at', 'idx_tindakan_validated_at');
        $addIndexIfNotExists('tindakan', 'input_by', 'idx_tindakan_input_by');
        $addIndexIfNotExists('tindakan', 'validated_by', 'idx_tindakan_validated_by');
        $addIndexIfNotExists('tindakan', 'deleted_at', 'idx_tindakan_deleted_at');
        
        // Composite indexes for common query patterns
        $addIndexIfNotExists('tindakan', ['pasien_id', 'tanggal_tindakan'], 'idx_tindakan_pasien_tanggal');
        $addIndexIfNotExists('tindakan', ['dokter_id', 'tanggal_tindakan'], 'idx_tindakan_dokter_tanggal');
        $addIndexIfNotExists('tindakan', ['status', 'tanggal_tindakan'], 'idx_tindakan_status_tanggal');
        $addIndexIfNotExists('tindakan', ['status_validasi', 'created_at'], 'idx_tindakan_validasi_created');
        $addIndexIfNotExists('tindakan', ['jenis_tindakan_id', 'status'], 'idx_tindakan_jenis_status');

        // Indexes for pendapatan table
        $addIndexIfNotExists('pendapatan', 'tindakan_id', 'idx_pendapatan_tindakan_id');
        $addIndexIfNotExists('pendapatan', 'kategori', 'idx_pendapatan_kategori');
        $addIndexIfNotExists('pendapatan', 'status_validasi', 'idx_pendapatan_status_validasi');
        $addIndexIfNotExists('pendapatan', 'created_at', 'idx_pendapatan_created_at');
        $addIndexIfNotExists('pendapatan', 'input_by', 'idx_pendapatan_input_by');
        $addIndexIfNotExists('pendapatan', 'validasi_by', 'idx_pendapatan_validasi_by');
        $addIndexIfNotExists('pendapatan', 'validasi_at', 'idx_pendapatan_validasi_at');
        $addIndexIfNotExists('pendapatan', 'deleted_at', 'idx_pendapatan_deleted_at');
        
        // Composite indexes for financial queries
        $addIndexIfNotExists('pendapatan', ['status_validasi', 'created_at'], 'idx_pendapatan_status_created');
        $addIndexIfNotExists('pendapatan', ['kategori', 'status_validasi'], 'idx_pendapatan_kategori_status');
        $addIndexIfNotExists('pendapatan', ['created_at', 'nominal'], 'idx_pendapatan_created_nominal');
        $addIndexIfNotExists('pendapatan', ['tindakan_id', 'status_validasi'], 'idx_pendapatan_tindakan_status');

        // Indexes for pengeluaran table
        $addIndexIfNotExists('pengeluaran', 'kategori', 'idx_pengeluaran_kategori');
        $addIndexIfNotExists('pengeluaran', 'status_validasi', 'idx_pengeluaran_status_validasi');
        $addIndexIfNotExists('pengeluaran', 'created_at', 'idx_pengeluaran_created_at');
        $addIndexIfNotExists('pengeluaran', 'input_by', 'idx_pengeluaran_input_by');
        $addIndexIfNotExists('pengeluaran', 'validasi_by', 'idx_pengeluaran_validasi_by');
        $addIndexIfNotExists('pengeluaran', 'validasi_at', 'idx_pengeluaran_validasi_at');
        $addIndexIfNotExists('pengeluaran', 'deleted_at', 'idx_pengeluaran_deleted_at');
        
        // Composite indexes for financial queries
        $addIndexIfNotExists('pengeluaran', ['status_validasi', 'created_at'], 'idx_pengeluaran_status_created');
        $addIndexIfNotExists('pengeluaran', ['kategori', 'status_validasi'], 'idx_pengeluaran_kategori_status');
        $addIndexIfNotExists('pengeluaran', ['created_at', 'nominal'], 'idx_pengeluaran_created_nominal');

        // Indexes for dokters table
        $addIndexIfNotExists('dokters', 'user_id', 'idx_dokters_user_id');
        $addIndexIfNotExists('dokters', 'spesialisasi', 'idx_dokters_spesialisasi');
        $addIndexIfNotExists('dokters', 'aktif', 'idx_dokters_aktif');
        $addIndexIfNotExists('dokters', 'status_akun', 'idx_dokters_status_akun');
        $addIndexIfNotExists('dokters', 'created_at', 'idx_dokters_created_at');
        $addIndexIfNotExists('dokters', 'input_by', 'idx_dokters_input_by');
        $addIndexIfNotExists('dokters', 'deleted_at', 'idx_dokters_deleted_at');
        $addIndexIfNotExists('dokters', 'nomor_sip', 'idx_dokters_nomor_sip');
        $addIndexIfNotExists('dokters', 'nik', 'idx_dokters_nik');
        $addIndexIfNotExists('dokters', 'nama_lengkap', 'idx_dokters_nama_lengkap');
        
        // Composite indexes
        $addIndexIfNotExists('dokters', ['aktif', 'spesialisasi'], 'idx_dokters_aktif_spesialisasi');
        $addIndexIfNotExists('dokters', ['user_id', 'aktif'], 'idx_dokters_user_aktif');

        // Indexes for users table
        // $addIndexIfNotExists('users', 'role', 'idx_users_role'); // Column doesn't exist
        $addIndexIfNotExists('users', 'role_id', 'idx_users_role_id');
        $addIndexIfNotExists('users', 'is_active', 'idx_users_is_active');
        $addIndexIfNotExists('users', 'created_at', 'idx_users_created_at');
        // $addIndexIfNotExists('users', 'last_login_at', 'idx_users_last_login_at'); // Column doesn't exist yet
        $addIndexIfNotExists('users', 'deleted_at', 'idx_users_deleted_at');
        $addIndexIfNotExists('users', 'nip', 'idx_users_nip');
        
        // Composite indexes
        // $addIndexIfNotExists('users', ['role', 'is_active'], 'idx_users_role_active'); // role column doesn't exist
        $addIndexIfNotExists('users', ['is_active', 'created_at'], 'idx_users_active_created');
        $addIndexIfNotExists('users', ['role_id', 'is_active'], 'idx_users_role_id_active');

        // Indexes for jenis_tindakan table
        $addIndexIfNotExists('jenis_tindakan', 'nama', 'idx_jenis_tindakan_nama');
        $addIndexIfNotExists('jenis_tindakan', 'kategori', 'idx_jenis_tindakan_kategori');
        $addIndexIfNotExists('jenis_tindakan', 'is_active', 'idx_jenis_tindakan_is_active');
        $addIndexIfNotExists('jenis_tindakan', 'created_at', 'idx_jenis_tindakan_created_at');
        $addIndexIfNotExists('jenis_tindakan', 'deleted_at', 'idx_jenis_tindakan_deleted_at');
        
        // Composite indexes
        $addIndexIfNotExists('jenis_tindakan', ['kategori', 'is_active'], 'idx_jenis_tindakan_kategori_active');
        $addIndexIfNotExists('jenis_tindakan', ['is_active', 'tarif'], 'idx_jenis_tindakan_active_tarif');

        // Indexes for jaspel table
        $addIndexIfNotExists('jaspel', 'tindakan_id', 'idx_jaspel_tindakan_id');
        $addIndexIfNotExists('jaspel', 'user_id', 'idx_jaspel_user_id');
        $addIndexIfNotExists('jaspel', 'jenis_jaspel', 'idx_jaspel_jenis_jaspel');
        $addIndexIfNotExists('jaspel', 'periode', 'idx_jaspel_periode');
        $addIndexIfNotExists('jaspel', 'status', 'idx_jaspel_status');
        $addIndexIfNotExists('jaspel', 'created_at', 'idx_jaspel_created_at');
        $addIndexIfNotExists('jaspel', 'deleted_at', 'idx_jaspel_deleted_at');
        
        // Composite indexes
        $addIndexIfNotExists('jaspel', ['user_id', 'periode'], 'idx_jaspel_user_periode');
        $addIndexIfNotExists('jaspel', ['tindakan_id', 'jenis_jaspel'], 'idx_jaspel_tindakan_jenis');
        $addIndexIfNotExists('jaspel', ['status', 'periode'], 'idx_jaspel_status_periode');
        $addIndexIfNotExists('jaspel', ['user_id', 'status'], 'idx_jaspel_user_status');

        // Indexes for audit_logs table
        $addIndexIfNotExists('audit_logs', 'user_id', 'idx_audit_logs_user_id');
        $addIndexIfNotExists('audit_logs', 'action', 'idx_audit_logs_action');
        $addIndexIfNotExists('audit_logs', 'model_type', 'idx_audit_logs_model_type');
        $addIndexIfNotExists('audit_logs', 'model_id', 'idx_audit_logs_model_id');
        $addIndexIfNotExists('audit_logs', 'created_at', 'idx_audit_logs_created_at');
        $addIndexIfNotExists('audit_logs', 'user_role', 'idx_audit_logs_user_role');
        $addIndexIfNotExists('audit_logs', 'ip_address', 'idx_audit_logs_ip_address');
        
        // Composite indexes
        $addIndexIfNotExists('audit_logs', ['model_type', 'model_id'], 'idx_audit_logs_model');
        $addIndexIfNotExists('audit_logs', ['user_id', 'action'], 'idx_audit_logs_user_action');
        $addIndexIfNotExists('audit_logs', ['action', 'created_at'], 'idx_audit_logs_action_created');
        $addIndexIfNotExists('audit_logs', ['user_role', 'created_at'], 'idx_audit_logs_role_created');

        // Indexes for error_logs table (commented out - table doesn't exist)
        // Schema::table('error_logs', function (Blueprint $table) {
        //     $table->index('level', 'idx_error_logs_level');
        //     $table->index('created_at', 'idx_error_logs_created_at');
        //     $table->index('user_id', 'idx_error_logs_user_id');
        //     $table->index(['level', 'created_at'], 'idx_error_logs_level_created');
        // });

        // Indexes for security_logs table (commented out - table doesn't exist)
        // Schema::table('security_logs', function (Blueprint $table) {
        //     $table->index('event_type', 'idx_security_logs_event_type');
        //     $table->index('user_id', 'idx_security_logs_user_id');
        //     $table->index('ip_address', 'idx_security_logs_ip_address');
        //     $table->index('created_at', 'idx_security_logs_created_at');
        //     $table->index(['event_type', 'created_at'], 'idx_security_logs_event_created');
        //     $table->index(['user_id', 'event_type'], 'idx_security_logs_user_event');
        // });

        // Indexes for performance_logs table (commented out - table doesn't exist)
        // Schema::table('performance_logs', function (Blueprint $table) {
        //     $table->index('operation', 'idx_performance_logs_operation');
        //     $table->index('level', 'idx_performance_logs_level');
        //     $table->index('created_at', 'idx_performance_logs_created_at');
        //     $table->index('duration', 'idx_performance_logs_duration');
        //     $table->index(['operation', 'created_at'], 'idx_performance_logs_operation_created');
        //     $table->index(['level', 'duration'], 'idx_performance_logs_level_duration');
        // });

        // Full-text indexes for search functionality
        if (config('database.default') === 'mysql') {
            // Helper function to safely add fulltext index
            $addFulltextIfNotExists = function($table, $columns, $indexName) {
                try {
                    DB::statement("ALTER TABLE $table ADD FULLTEXT $indexName ($columns)");
                } catch (\Exception $e) {
                    // Index might already exist, ignore error
                    if (!str_contains($e->getMessage(), 'Duplicate key name')) {
                        throw $e;
                    }
                }
            };
            
            // Full-text search on pasien
            $addFulltextIfNotExists('pasien', 'nama, no_rekam_medis, alamat', 'idx_pasien_fulltext');
            
            // Full-text search on dokters
            $addFulltextIfNotExists('dokters', 'nama_lengkap, spesialisasi', 'idx_dokters_fulltext');
            
            // Full-text search on jenis_tindakan
            $addFulltextIfNotExists('jenis_tindakan', 'nama, deskripsi', 'idx_jenis_tindakan_fulltext');
            
            // Full-text search on users
            $addFulltextIfNotExists('users', 'name, email', 'idx_users_fulltext');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text indexes first
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE pasien DROP INDEX idx_pasien_fulltext');
            DB::statement('ALTER TABLE dokters DROP INDEX idx_dokters_fulltext');
            DB::statement('ALTER TABLE jenis_tindakan DROP INDEX idx_jenis_tindakan_fulltext');
            DB::statement('ALTER TABLE users DROP INDEX idx_users_fulltext');
        }

        // Drop indexes from performance_logs table
        Schema::table('performance_logs', function (Blueprint $table) {
            $table->dropIndex('idx_performance_logs_operation');
            $table->dropIndex('idx_performance_logs_level');
            $table->dropIndex('idx_performance_logs_created_at');
            $table->dropIndex('idx_performance_logs_duration');
            $table->dropIndex('idx_performance_logs_operation_created');
            $table->dropIndex('idx_performance_logs_level_duration');
        });

        // Drop indexes from security_logs table
        Schema::table('security_logs', function (Blueprint $table) {
            $table->dropIndex('idx_security_logs_event_type');
            $table->dropIndex('idx_security_logs_user_id');
            $table->dropIndex('idx_security_logs_ip_address');
            $table->dropIndex('idx_security_logs_created_at');
            $table->dropIndex('idx_security_logs_event_created');
            $table->dropIndex('idx_security_logs_user_event');
        });

        // Drop indexes from error_logs table
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropIndex('idx_error_logs_level');
            $table->dropIndex('idx_error_logs_created_at');
            $table->dropIndex('idx_error_logs_user_id');
            $table->dropIndex('idx_error_logs_level_created');
        });

        // Drop indexes from audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_user_id');
            $table->dropIndex('idx_audit_logs_action');
            $table->dropIndex('idx_audit_logs_model_type');
            $table->dropIndex('idx_audit_logs_model_id');
            $table->dropIndex('idx_audit_logs_created_at');
            $table->dropIndex('idx_audit_logs_user_role');
            $table->dropIndex('idx_audit_logs_ip_address');
            $table->dropIndex('idx_audit_logs_model');
            $table->dropIndex('idx_audit_logs_user_action');
            $table->dropIndex('idx_audit_logs_action_created');
            $table->dropIndex('idx_audit_logs_role_created');
        });

        // Drop indexes from jaspel table
        Schema::table('jaspel', function (Blueprint $table) {
            $table->dropIndex('idx_jaspel_tindakan_id');
            $table->dropIndex('idx_jaspel_user_id');
            $table->dropIndex('idx_jaspel_jenis_jaspel');
            $table->dropIndex('idx_jaspel_periode');
            $table->dropIndex('idx_jaspel_status');
            $table->dropIndex('idx_jaspel_created_at');
            $table->dropIndex('idx_jaspel_deleted_at');
            $table->dropIndex('idx_jaspel_user_periode');
            $table->dropIndex('idx_jaspel_tindakan_jenis');
            $table->dropIndex('idx_jaspel_status_periode');
            $table->dropIndex('idx_jaspel_user_status');
        });

        // Drop indexes from jenis_tindakan table
        Schema::table('jenis_tindakan', function (Blueprint $table) {
            $table->dropIndex('idx_jenis_tindakan_nama');
            $table->dropIndex('idx_jenis_tindakan_kategori');
            $table->dropIndex('idx_jenis_tindakan_is_active');
            $table->dropIndex('idx_jenis_tindakan_created_at');
            $table->dropIndex('idx_jenis_tindakan_deleted_at');
            $table->dropIndex('idx_jenis_tindakan_kategori_active');
            $table->dropIndex('idx_jenis_tindakan_active_tarif');
        });

        // Drop indexes from users table
        Schema::table('users', function (Blueprint $table) {
            // $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_role_id');
            $table->dropIndex('idx_users_is_active');
            $table->dropIndex('idx_users_created_at');
            // $table->dropIndex('idx_users_last_login_at');
            $table->dropIndex('idx_users_deleted_at');
            $table->dropIndex('idx_users_nip');
            // $table->dropIndex('idx_users_role_active');
            $table->dropIndex('idx_users_active_created');
            $table->dropIndex('idx_users_role_id_active');
        });



        // Drop indexes from pengeluaran table
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropIndex('idx_pengeluaran_kategori');
            $table->dropIndex('idx_pengeluaran_status_validasi');
            $table->dropIndex('idx_pengeluaran_created_at');
            $table->dropIndex('idx_pengeluaran_input_by');
            $table->dropIndex('idx_pengeluaran_validasi_by');
            $table->dropIndex('idx_pengeluaran_validasi_at');
            $table->dropIndex('idx_pengeluaran_deleted_at');
            $table->dropIndex('idx_pengeluaran_status_created');
            $table->dropIndex('idx_pengeluaran_kategori_status');
            $table->dropIndex('idx_pengeluaran_created_nominal');
        });

        // Drop indexes from pendapatan table
        Schema::table('pendapatan', function (Blueprint $table) {
            $table->dropIndex('idx_pendapatan_tindakan_id');
            $table->dropIndex('idx_pendapatan_kategori');
            $table->dropIndex('idx_pendapatan_status_validasi');
            $table->dropIndex('idx_pendapatan_created_at');
            $table->dropIndex('idx_pendapatan_input_by');
            $table->dropIndex('idx_pendapatan_validasi_by');
            $table->dropIndex('idx_pendapatan_validasi_at');
            $table->dropIndex('idx_pendapatan_deleted_at');
            $table->dropIndex('idx_pendapatan_status_created');
            $table->dropIndex('idx_pendapatan_kategori_status');
            $table->dropIndex('idx_pendapatan_created_nominal');
            $table->dropIndex('idx_pendapatan_tindakan_status');
        });

        // Drop indexes from tindakan table
        Schema::table('tindakan', function (Blueprint $table) {
            $table->dropIndex('idx_tindakan_pasien_id');
            $table->dropIndex('idx_tindakan_dokter_id');
            $table->dropIndex('idx_tindakan_jenis_tindakan_id');
            $table->dropIndex('idx_tindakan_tanggal');
            $table->dropIndex('idx_tindakan_status');
            $table->dropIndex('idx_tindakan_status_validasi');
            $table->dropIndex('idx_tindakan_created_at');
            $table->dropIndex('idx_tindakan_validated_at');
            $table->dropIndex('idx_tindakan_input_by');
            $table->dropIndex('idx_tindakan_validated_by');
            $table->dropIndex('idx_tindakan_deleted_at');
            $table->dropIndex('idx_tindakan_pasien_tanggal');
            $table->dropIndex('idx_tindakan_dokter_tanggal');
            $table->dropIndex('idx_tindakan_status_tanggal');
            $table->dropIndex('idx_tindakan_validasi_created');
            $table->dropIndex('idx_tindakan_jenis_status');
        });

        // Drop indexes from pasien table
        Schema::table('pasien', function (Blueprint $table) {
            $table->dropIndex('idx_pasien_created_at');
            $table->dropIndex('idx_pasien_jenis_kelamin');
            $table->dropIndex('idx_pasien_tanggal_lahir');
            $table->dropIndex('idx_pasien_gender_created');
            $table->dropIndex('idx_pasien_no_rekam_medis');
            $table->dropIndex('idx_pasien_nama');
            $table->dropIndex('idx_pasien_deleted_at');
        });
    }
};