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
            // Add shift_template_id column to unify with admin system
            $table->foreignId('shift_template_id')->nullable()->after('shift')->constrained('shift_templates')->onDelete('set null');
            $table->index('shift_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            $table->dropForeign(['shift_template_id']);
            $table->dropColumn('shift_template_id');
        });
    }
};