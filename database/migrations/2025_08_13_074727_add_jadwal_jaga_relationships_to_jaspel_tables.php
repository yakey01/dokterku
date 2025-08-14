<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds missing jadwal_jaga relationships to establish proper 
     * connection between duty schedules and jaspel calculations
     */
    public function up(): void
    {
        // Check if jumlah_pasien_harians table exists and needs jadwal_jaga_id
        if (Schema::hasTable('jumlah_pasien_harians')) {
            // Check if jadwal_jaga_id column already exists
            if (!Schema::hasColumn('jumlah_pasien_harians', 'jadwal_jaga_id')) {
                Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
                    // Add foreign key to link patient count with specific duty schedule
                    $table->foreignId('jadwal_jaga_id')
                        ->nullable()
                        ->after('dokter_umum_jaspel_id')
                        ->constrained('jadwal_jagas')
                        ->nullOnDelete()
                        ->comment('Links patient count to specific duty schedule');
                    
                    // Add index for performance
                    $table->index('jadwal_jaga_id', 'idx_jumlah_pasien_jadwal_jaga');
                });
            }

            // Update the unique constraint to include jadwal_jaga_id if it exists
            if (Schema::hasColumn('jumlah_pasien_harians', 'jadwal_jaga_id')) {
                Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
                    // Check if the old constraint exists before dropping it
                    try {
                        $table->dropUnique('unique_daily_shift_record');
                    } catch (\Exception $e) {
                        // Constraint might not exist, continue
                    }
                    
                    // Add new unique constraint that considers jadwal_jaga context
                    $table->unique(
                        ['tanggal', 'poli', 'shift', 'dokter_id', 'jadwal_jaga_id'], 
                        'unique_daily_shift_jadwal_record'
                    );
                });
            }
        }

        // Add jadwal_jaga_id to jaspels table only if it exists
        if (Schema::hasTable('jaspels')) {
            Schema::table('jaspels', function (Blueprint $table) {
                // Add foreign key to link jaspel records with duty schedule
                $table->foreignId('jadwal_jaga_id')
                    ->nullable()
                    ->after('shift_id')
                    ->constrained('jadwal_jagas')
                    ->nullOnDelete()
                    ->comment('Links jaspel calculation to specific duty schedule');
                
                // Add index for performance
                $table->index('jadwal_jaga_id', 'idx_jaspel_jadwal_jaga');
                
                // Add composite index for efficient queries
                $table->index(['user_id', 'jadwal_jaga_id', 'tanggal'], 'idx_jaspel_user_jadwal_tanggal');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove jadwal_jaga_id from jaspels table if it exists
        if (Schema::hasTable('jaspels') && Schema::hasColumn('jaspels', 'jadwal_jaga_id')) {
            Schema::table('jaspels', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_jaspel_user_jadwal_tanggal');
                } catch (\Exception $e) {
                    // Index might not exist
                }
                try {
                    $table->dropIndex('idx_jaspel_jadwal_jaga');
                } catch (\Exception $e) {
                    // Index might not exist
                }
                $table->dropForeign(['jadwal_jaga_id']);
                $table->dropColumn('jadwal_jaga_id');
            });
        }

        // Remove the new unique constraint and jadwal_jaga_id from jumlah_pasien_harians
        if (Schema::hasTable('jumlah_pasien_harians')) {
            Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
                try {
                    $table->dropUnique('unique_daily_shift_jadwal_record');
                } catch (\Exception $e) {
                    // Constraint might not exist
                }
                
                // Restore the old unique constraint if possible
                try {
                    $table->unique(['tanggal', 'poli', 'shift', 'dokter_id'], 'unique_daily_shift_record');
                } catch (\Exception $e) {
                    // Constraint might already exist
                }
            });

            // Remove jadwal_jaga_id column if it exists
            if (Schema::hasColumn('jumlah_pasien_harians', 'jadwal_jaga_id')) {
                Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
                    try {
                        $table->dropIndex('idx_jumlah_pasien_jadwal_jaga');
                    } catch (\Exception $e) {
                        // Index might not exist
                    }
                    $table->dropForeign(['jadwal_jaga_id']);
                    $table->dropColumn('jadwal_jaga_id');
                });
            }
        }
    }
};