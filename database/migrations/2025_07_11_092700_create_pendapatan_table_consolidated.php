<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for pendapatan table
     * Combines: create_pendapatan_table, add_new_fields, update_nullable_fields, add_is_aktif
     */
    public function up(): void
    {
        Schema::create('pendapatan', function (Blueprint $table) {
            $table->id();
            
            // From add_new_fields migration
            $table->string('kode_pendapatan', 20)->nullable();
            $table->string('nama_pendapatan', 100)->nullable();
            $table->enum('sumber_pendapatan', ['Umum', 'Gigi'])->nullable();
            
            // From add_is_aktif migration
            $table->boolean('is_aktif')->default(true);
            
            // Original fields (with nullable updates from update_nullable_fields)
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->decimal('nominal', 15, 2)->nullable();
            $table->string('kategori')->nullable(); // Changed from enum to string and made nullable
            $table->foreignId('tindakan_id')->nullable()->constrained('tindakan')->onDelete('set null');
            $table->foreignId('input_by')->constrained('users')->onDelete('cascade');
            $table->enum('status_validasi', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->foreignId('validasi_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('validasi_at')->nullable();
            $table->text('catatan_validasi')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tanggal');
            $table->index('kategori');
            $table->index('status_validasi');
            $table->index(['tanggal', 'kategori']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendapatan');
    }
};