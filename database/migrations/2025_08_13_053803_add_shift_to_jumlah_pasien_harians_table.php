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
            $table->enum('shift', ['Pagi', 'Sore', 'Hari Libur Besar'])->default('Pagi')->after('poli');
            $table->foreignId('dokter_umum_jaspel_id')->nullable()->constrained('dokter_umum_jaspels')->onDelete('set null')->after('shift');
            $table->index('shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            $table->dropForeign(['dokter_umum_jaspel_id']);
            $table->dropIndex(['shift']);
            $table->dropColumn(['shift', 'dokter_umum_jaspel_id']);
        });
    }
};
