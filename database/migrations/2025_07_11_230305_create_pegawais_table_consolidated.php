<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for pegawais table
     * Combines: create_pegawais_table, update_make_nik_required, add_user_id,
     *           add_login_fields, add_email_column, fix_username_constraint
     */
    public function up(): void
    {
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            
            // From update_pegawais_table_make_nik_required (made required)
            $table->string('nik')->unique();
            
            $table->string('nama_lengkap');
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->string('jabatan');
            $table->enum('jenis_pegawai', ['Paramedis', 'Non-Paramedis'])->default('Non-Paramedis');
            $table->boolean('aktif')->default(true);
            $table->string('foto')->nullable();
            
            // From add_user_id_to_pegawais_table
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // From add_login_fields_to_pegawais_table
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->boolean('can_login')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->integer('login_count')->default(0);
            
            // From add_email_column_to_pegawais_table
            $table->string('email')->nullable();
            
            $table->unsignedBigInteger('input_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['jenis_pegawai', 'aktif']);
            $table->index('nama_lengkap');
            $table->index('jabatan');
            $table->index('username');
            $table->index('email');
            
            // From fix_pegawai_username_constraint_for_soft_deletes
            // Unique constraint only on non-deleted records
            $table->unique(['username', 'deleted_at'], 'pegawais_username_deleted_at_unique');
            $table->unique(['email', 'deleted_at'], 'pegawais_email_deleted_at_unique');

            $table->foreign('input_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};