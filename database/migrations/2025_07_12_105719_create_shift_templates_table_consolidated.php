<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for shift_templates table
     * Note: Data seeding from update_shift_templates_table should be moved to seeders
     */
    public function up(): void
    {
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nama_shift');
            $table->time('jam_masuk');
            $table->time('jam_pulang');
            $table->timestamps();
            
            // Index for performance
            $table->index('nama_shift');
        });
        
        // Note: Initial data seeding should be done via database seeders
        // The following can be moved to ShiftTemplateSeeder::class
        // - Shift Pagi: 06:00 - 12:00
        // - Shift Sore: 16:00 - 21:00
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_templates');
    }
};