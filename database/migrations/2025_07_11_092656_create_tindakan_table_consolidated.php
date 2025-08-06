<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for tindakan table
     * Combines: create_tindakan_table, add_input_by, add_validation_fields, 
     *           fix_foreign_keys, make_dokter_id_nullable
     */
    public function up(): void
    {
        Schema::create('tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('pasien')->onDelete('cascade');
            $table->foreignId('jenis_tindakan_id')->constrained('jenis_tindakan')->onDelete('cascade');
            
            // From make_dokter_id_nullable migration
            $table->foreignId('dokter_id')->nullable()->constrained('users')->onDelete('cascade');
            
            $table->foreignId('paramedis_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('non_paramedis_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Updated to reference shift_templates instead of shifts (from update_tindakan_shift_foreign_key)
            $table->foreignId('shift_id')->nullable()->constrained('shift_templates')->onDelete('set null');
            
            $table->dateTime('tanggal_tindakan');
            $table->decimal('tarif', 15, 2);
            $table->decimal('jasa_dokter', 15, 2)->default(0);
            $table->decimal('jasa_paramedis', 15, 2)->default(0);
            $table->decimal('jasa_non_paramedis', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->enum('status', ['pending', 'selesai', 'batal'])->default('pending');
            
            // From add_input_by_to_tindakan_table migration
            $table->foreignId('input_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('input_at')->nullable();
            
            // From add_validation_fields_to_tindakan_table migration
            $table->enum('status_validasi', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->foreignId('validasi_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('validasi_at')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->string('kode_tindakan')->unique()->nullable();
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('total_bayar', 15, 2)->default(0);
            $table->enum('metode_pembayaran', ['tunai', 'transfer', 'kartu_kredit', 'kartu_debit', 'lainnya'])->default('tunai');
            $table->boolean('is_bpjs')->default(false);
            $table->string('nomor_bpjs')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tanggal_tindakan');
            $table->index('status');
            $table->index('status_validasi');
            $table->index(['pasien_id', 'tanggal_tindakan']);
            $table->index(['dokter_id', 'tanggal_tindakan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tindakan');
    }
};