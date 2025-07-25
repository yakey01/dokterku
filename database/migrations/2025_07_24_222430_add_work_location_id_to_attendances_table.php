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
            $table->foreignId('work_location_id')
                  ->nullable()
                  ->after('location_id')
                  ->constrained('work_locations')
                  ->onDelete('set null');
                  
            $table->foreignId('jadwal_jaga_id')
                  ->nullable()
                  ->after('work_location_id')
                  ->constrained('jadwal_jagas')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['work_location_id']);
            $table->dropForeign(['jadwal_jaga_id']);
            $table->dropColumn(['work_location_id', 'jadwal_jaga_id']);
        });
    }
};