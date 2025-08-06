<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for pendapatan_harians table
     * Combines: create_pendapatan_harians_table, change_pendapatan_harians_relation_to_pendapatan,
     *           add_validation_fields_to_pendapatan_harians_table
     */
    public function up(): void
    {
        Schema::create('pendapatan_harians', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            
            // From change_pendapatan_harians_relation_to_pendapatan
            $table->foreignId('pendapatan_id')->constrained('pendapatan')->onDelete('cascade');
            
            $table->string('jenis_transaksi');
            $table->decimal('nominal', 15, 2);
            $table->text('keterangan')->nullable();
            $table->foreignId('input_by')->constrained('users')->onDelete('cascade');
            
            // From add_validation_fields_to_pendapatan_harians_table
            $table->enum('status_validasi', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->foreignId('validasi_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('validasi_at')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->string('bukti_transaksi')->nullable(); // Path to transaction proof file
            $table->enum('metode_pembayaran', ['tunai', 'transfer', 'kartu_kredit', 'kartu_debit', 'lainnya'])->default('tunai');
            $table->string('referensi_transaksi')->nullable(); // Transaction reference number
            $table->boolean('is_reconciled')->default(false); // For financial reconciliation
            $table->dateTime('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tanggal');
            $table->index('pendapatan_id');
            $table->index('status_validasi');
            $table->index(['tanggal', 'status_validasi']);
            $table->index('is_reconciled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendapatan_harians');
    }
};