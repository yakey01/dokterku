<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for jenis_tindakan table
     * Combines: create_jenis_tindakan_table, add_persentase_jaspel_to_jenis_tindakan_table
     */
    public function up(): void
    {
        Schema::create('jenis_tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->decimal('tarif', 15, 2);
            $table->decimal('jasa_dokter', 15, 2)->default(0);
            $table->decimal('jasa_paramedis', 15, 2)->default(0);
            $table->decimal('jasa_non_paramedis', 15, 2)->default(0);
            
            // From add_persentase_jaspel_to_jenis_tindakan_table migration
            $table->decimal('persentase_jaspel', 5, 2)->default(40.00);
            
            $table->enum('kategori', ['konsultasi', 'pemeriksaan', 'tindakan', 'obat', 'lainnya']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('kode');
            $table->index('nama');
            $table->index('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_tindakan');
    }
};