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
            $table->decimal('jaspel_rupiah', 12, 2)->nullable()->after('jumlah_pasien_bpjs')
                ->comment('Jaspel amount in Indonesian Rupiah');
            
            // Index for performance when filtering by jaspel amount
            $table->index('jaspel_rupiah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            $table->dropIndex(['jaspel_rupiah']);
            $table->dropColumn('jaspel_rupiah');
        });
    }
};
