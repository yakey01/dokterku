<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for pasien table
     * Combines: create_pasien_table, add_input_by_to_pasien_table, add_status_to_pasien_table
     */
    public function up(): void
    {
        Schema::create('pasien', function (Blueprint $table) {
            $table->id();
            $table->string('no_rekam_medis')->unique();
            $table->string('nama');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->text('alamat')->nullable();
            $table->string('no_telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->enum('status_pernikahan', ['belum_menikah', 'menikah', 'janda', 'duda'])->nullable();
            $table->string('kontak_darurat_nama')->nullable();
            $table->string('kontak_darurat_telepon')->nullable();
            
            $table->timestamps();
            
            // From add_input_by_to_pasien_table migration
            $table->unsignedBigInteger('input_by')->nullable();
            $table->foreign('input_by')->references('id')->on('users')->onDelete('set null');
            
            // From add_status_to_pasien_table migration
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('verification_notes')->nullable();
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            
            $table->softDeletes();
            
            // Indexes
            $table->index('no_rekam_medis');
            $table->index('nama');
            $table->index('tanggal_lahir');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasien');
    }
};