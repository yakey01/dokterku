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
        Schema::create('dokter_umum_jaspels', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis_shift', ['Pagi', 'Sore', 'Hari Libur Besar']);
            $table->integer('ambang_pasien')->default(0)->comment('Threshold minimum patient count');
            $table->decimal('fee_pasien_umum', 10, 2)->default(0)->comment('Fee per regular patient');
            $table->decimal('fee_pasien_bpjs', 10, 2)->default(0)->comment('Fee per BPJS patient');
            $table->boolean('status_aktif')->default(true);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('jenis_shift');
            $table->index('status_aktif');
            $table->unique('jenis_shift', 'unique_shift_per_formula');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokter_umum_jaspels');
    }
};
