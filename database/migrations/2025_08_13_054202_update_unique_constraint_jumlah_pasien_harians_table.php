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
        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('unique_daily_record');
            
            // Add new unique constraint that includes shift
            $table->unique(['tanggal', 'poli', 'shift', 'dokter_id'], 'unique_daily_shift_record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            // Drop new unique constraint
            $table->dropUnique('unique_daily_shift_record');
            
            // Restore old unique constraint
            $table->unique(['tanggal', 'poli', 'dokter_id'], 'unique_daily_record');
        });
    }
};
