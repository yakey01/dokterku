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
        Schema::table('dokter_umum_jaspels', function (Blueprint $table) {
            $table->decimal('uang_duduk', 10, 2)->default(0)->comment('Base sitting fee for doctor per shift')->after('fee_pasien_bpjs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dokter_umum_jaspels', function (Blueprint $table) {
            $table->dropColumn('uang_duduk');
        });
    }
};
