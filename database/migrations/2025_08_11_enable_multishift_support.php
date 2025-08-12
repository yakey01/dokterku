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
        // Add multi-shift support fields to attendances table
        Schema::table('attendances', function (Blueprint $table) {
            // Track which shift sequence this is (1st, 2nd, 3rd shift of the day)
            $table->integer('shift_sequence')->default(1)->after('shift_id')
                ->comment('Shift number for the day (1=first, 2=second, etc)');
            
            // Link to previous attendance if this is a subsequent shift
            $table->foreignId('previous_attendance_id')->nullable()->after('shift_sequence')
                ->constrained('attendances')->nullOnDelete()
                ->comment('Links to previous shift attendance on same day');
            
            // Time gap from previous shift
            $table->integer('gap_from_previous_minutes')->nullable()->after('previous_attendance_id')
                ->comment('Minutes gap from previous shift checkout');
            
            // Next expected shift info
            $table->time('next_shift_start')->nullable()->after('shift_end')
                ->comment('Start time of next scheduled shift');
            $table->foreignId('next_shift_id')->nullable()->after('next_shift_start')
                ->constrained('shift_templates')->nullOnDelete()
                ->comment('Next scheduled shift template');
            
            // Multi-shift validation flags
            $table->boolean('is_additional_shift')->default(false)->after('location_validated')
                ->comment('True if this is 2nd+ shift of the day');
            $table->boolean('is_overtime_shift')->default(false)->after('is_additional_shift')
                ->comment('True if this shift is considered overtime');
            
            // Add index for faster multi-shift queries
            $table->index(['user_id', 'date', 'shift_sequence']);
            $table->index('previous_attendance_id');
        });

        // Modify jadwal_jagas to allow multiple shifts per day
        // First, drop the existing unique constraint if it exists
        try {
            Schema::table('jadwal_jagas', function (Blueprint $table) {
                $table->dropUnique('unique_staff_shift_per_day');
            });
        } catch (\Exception $e) {
            // Constraint might not exist in all databases
            \Log::info('unique_staff_shift_per_day constraint not found, skipping drop');
        }

        // Add new fields to jadwal_jagas for multi-shift support
        Schema::table('jadwal_jagas', function (Blueprint $table) {
            // Add shift sequence field
            $table->integer('shift_sequence')->default(1)->after('shift_template_id')
                ->comment('Shift sequence for the day');
            
            // Add overtime flag
            $table->boolean('is_overtime')->default(false)->after('status_jaga')
                ->comment('Whether this is an overtime shift');
            
            // Add new unique constraint that allows multiple shifts
            // Now unique by: date + employee + shift_template + sequence
            $table->unique(
                ['tanggal_jaga', 'pegawai_id', 'shift_template_id', 'shift_sequence'],
                'unique_staff_shift_sequence'
            );
            
            // Add index for multi-shift queries
            $table->index(['pegawai_id', 'tanggal_jaga', 'shift_sequence']);
        });

        // Create a table to track shift gaps and rules
        Schema::create('shift_gap_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_shift_id')->constrained('shift_templates')->cascadeOnDelete();
            $table->foreignId('to_shift_id')->constrained('shift_templates')->cascadeOnDelete();
            $table->integer('minimum_gap_minutes')->default(60)
                ->comment('Minimum minutes required between these shifts');
            $table->integer('maximum_gap_minutes')->nullable()
                ->comment('Maximum minutes allowed between these shifts');
            $table->boolean('is_allowed')->default(true)
                ->comment('Whether this shift combination is allowed');
            $table->string('restriction_reason')->nullable()
                ->comment('Reason if combination is not allowed');
            $table->timestamps();
            
            $table->unique(['from_shift_id', 'to_shift_id']);
            $table->index('from_shift_id');
            $table->index('to_shift_id');
        });

        // Add default shift gap rules
        $this->seedDefaultShiftGapRules();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop shift gap rules table
        Schema::dropIfExists('shift_gap_rules');

        // Remove multi-shift support from jadwal_jagas
        Schema::table('jadwal_jagas', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique('unique_staff_shift_sequence');
            
            // Remove new columns
            $table->dropColumn(['shift_sequence', 'is_overtime']);
            
            // Restore original unique constraint
            $table->unique(
                ['tanggal_jaga', 'pegawai_id', 'shift_template_id'],
                'unique_staff_shift_per_day'
            );
            
            // Drop indexes
            $table->dropIndex(['pegawai_id', 'tanggal_jaga', 'shift_sequence']);
        });

        // Remove multi-shift support from attendances
        Schema::table('attendances', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['user_id', 'date', 'shift_sequence']);
            $table->dropIndex(['previous_attendance_id']);
            
            // Drop foreign keys
            $table->dropForeign(['previous_attendance_id']);
            $table->dropForeign(['next_shift_id']);
            
            // Drop columns
            $table->dropColumn([
                'shift_sequence',
                'previous_attendance_id',
                'gap_from_previous_minutes',
                'next_shift_start',
                'next_shift_id',
                'is_additional_shift',
                'is_overtime_shift'
            ]);
        });
    }

    /**
     * Seed default shift gap rules
     */
    private function seedDefaultShiftGapRules(): void
    {
        $shifts = DB::table('shift_templates')->get();
        
        foreach ($shifts as $fromShift) {
            foreach ($shifts as $toShift) {
                if ($fromShift->id === $toShift->id) {
                    // Same shift - requires longer gap (prevent back-to-back same shifts)
                    DB::table('shift_gap_rules')->insert([
                        'from_shift_id' => $fromShift->id,
                        'to_shift_id' => $toShift->id,
                        'minimum_gap_minutes' => 480, // 8 hours minimum
                        'maximum_gap_minutes' => null,
                        'is_allowed' => true,
                        'restriction_reason' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    // Different shifts - standard gap
                    DB::table('shift_gap_rules')->insert([
                        'from_shift_id' => $fromShift->id,
                        'to_shift_id' => $toShift->id,
                        'minimum_gap_minutes' => 60, // 1 hour minimum
                        'maximum_gap_minutes' => 720, // 12 hours maximum
                        'is_allowed' => true,
                        'restriction_reason' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
};