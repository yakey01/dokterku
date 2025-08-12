<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Shift tracking fields
            $table->foreignId('shift_id')->nullable()->after('jadwal_jaga_id')
                ->constrained('shift_templates')->onDelete('set null');
            $table->time('shift_start')->nullable()->after('shift_id');
            $table->time('shift_end')->nullable()->after('shift_start');
            
            // Logical timer calculation fields
            $table->time('logical_time_in')->nullable()->after('time_in')
                ->comment('Logical timer start (max of actual check-in or shift start)');
            $table->time('logical_time_out')->nullable()->after('time_out')
                ->comment('Logical timer end (min of actual check-out or shift end)');
            $table->integer('logical_work_minutes')->nullable()->after('logical_time_out')
                ->comment('Calculated work minutes based on logical times');
            
            // Validation metadata
            $table->json('check_in_metadata')->nullable()->after('notes')
                ->comment('Detailed check-in validation data');
            $table->json('check_out_metadata')->nullable()->after('check_in_metadata')
                ->comment('Detailed check-out validation data');
            
            // Rejection tracking
            $table->string('check_in_rejection_code')->nullable()->after('status');
            $table->text('check_in_rejection_reason')->nullable()->after('check_in_rejection_code');
            
            // Indexes for performance
            $table->index('shift_id');
            $table->index(['user_id', 'date', 'shift_id']);
            $table->index('check_in_rejection_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['user_id', 'date', 'shift_id']);
            $table->dropIndex(['shift_id']);
            $table->dropIndex(['check_in_rejection_code']);
            
            // Drop foreign key constraint
            $table->dropForeign(['shift_id']);
            
            // Drop columns
            $table->dropColumn([
                'shift_id',
                'shift_start',
                'shift_end',
                'logical_time_in',
                'logical_time_out',
                'logical_work_minutes',
                'check_in_metadata',
                'check_out_metadata',
                'check_in_rejection_code',
                'check_in_rejection_reason'
            ]);
        });
    }
};