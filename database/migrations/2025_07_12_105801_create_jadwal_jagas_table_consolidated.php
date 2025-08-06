<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for jadwal_jagas table
     * Combines: create_jadwal_jagas_table, update_jadwal_jagas_table_units_and_constraints,
     *           add_jam_jaga_custom_to_jadwal_jagas_table
     */
    public function up(): void
    {
        Schema::create('jadwal_jagas', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_jaga');
            $table->foreignId('shift_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('pegawai_id')->constrained('users')->onDelete('cascade');
            
            // From update_jadwal_jagas_table_units_and_constraints
            $table->enum('unit_instalasi', ['Poli Umum', 'Poli Gigi', 'UGD', 'Laboratorium', 'Farmasi', 'Administrasi'])->default('Poli Umum');
            
            $table->enum('peran', ['Paramedis', 'NonParamedis', 'Dokter']);
            $table->enum('status_jaga', ['Aktif', 'Cuti', 'Izin', 'OnCall'])->default('Aktif');
            
            // From add_jam_jaga_custom_to_jadwal_jagas_table
            $table->time('jam_masuk_custom')->nullable();
            $table->time('jam_pulang_custom')->nullable();
            $table->boolean('is_custom_schedule')->default(false);
            $table->string('custom_reason')->nullable();
            
            $table->text('keterangan')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['tanggal_jaga', 'pegawai_id']);
            $table->index(['tanggal_jaga', 'status_jaga']);
            $table->index(['pegawai_id', 'status_jaga']);
            $table->index('unit_instalasi');
            $table->index('is_custom_schedule');
            
            // Unique constraint to prevent double booking
            $table->unique(['tanggal_jaga', 'pegawai_id', 'shift_template_id'], 'unique_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_jagas');
    }
};