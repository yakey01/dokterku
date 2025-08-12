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
            if (!Schema::hasColumn('jumlah_pasien_harians', 'catatan')) {
                $table->text('catatan')->nullable()->after('catatan_validasi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });
    }
};