<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add break time support to shift_templates table for enhanced work duration calculation
     */
    public function up(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            // Break duration in minutes (e.g., 60 for 1 hour break)
            $table->integer('break_duration_minutes')->default(0)->after('jam_pulang');
            
            // Optional: Scheduled break start time (e.g., '12:00:00' for lunch break)
            $table->time('break_start_time')->nullable()->after('break_duration_minutes');
            
            // Break end time (calculated automatically or can be set explicitly)
            $table->time('break_end_time')->nullable()->after('break_start_time');
            
            // Flag to indicate if break time is flexible or fixed schedule
            $table->boolean('is_break_flexible')->default(true)->after('break_end_time');
            
            // Index for performance
            $table->index(['break_duration_minutes', 'is_break_flexible']);
        });
        
        // Update existing shift templates with reasonable defaults
        \DB::table('shift_templates')->update([
            'break_duration_minutes' => 60, // Default 1 hour break
            'is_break_flexible' => true      // Flexible break timing by default
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropIndex(['break_duration_minutes', 'is_break_flexible']);
            $table->dropColumn([
                'break_duration_minutes',
                'break_start_time', 
                'break_end_time',
                'is_break_flexible'
            ]);
        });
    }
};