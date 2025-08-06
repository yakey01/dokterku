<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for data import/export tables
     * Note: Creates separate tables but in one migration file for better organization
     * Combines: create_data_imports_table, create_data_exports_table, 
     *           create_imports_table, create_exports_table
     */
    public function up(): void
    {
        // Data Imports table (unified structure)
        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->string('import_type'); // Type of data being imported
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type'); // csv, xlsx, json, etc
            $table->integer('file_size'); // in bytes
            $table->string('mime_type')->nullable();
            $table->json('columns_mapping')->nullable(); // Column mapping configuration
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('success_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->json('error_summary')->nullable(); // Summary of errors
            $table->text('error_log')->nullable(); // Detailed error log
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('imported_by')->constrained('users')->onDelete('cascade');
            $table->json('import_settings')->nullable(); // Additional settings
            $table->string('batch_id')->nullable(); // For batch processing
            $table->timestamps();
            
            $table->index('import_type');
            $table->index('status');
            $table->index('batch_id');
            $table->index('imported_by');
        });

        // Data Exports table (unified structure)
        Schema::create('data_exports', function (Blueprint $table) {
            $table->id();
            $table->string('export_type'); // Type of data being exported
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('file_type'); // csv, xlsx, json, pdf, etc
            $table->integer('file_size')->nullable(); // in bytes
            $table->json('filters')->nullable(); // Applied filters
            $table->json('columns')->nullable(); // Selected columns
            $table->integer('total_records')->default(0);
            $table->integer('exported_records')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // When export file expires
            $table->foreignId('exported_by')->constrained('users')->onDelete('cascade');
            $table->json('export_settings')->nullable(); // Additional settings
            $table->string('batch_id')->nullable(); // For batch processing
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable(); // daily, weekly, monthly
            $table->timestamps();
            
            $table->index('export_type');
            $table->index('status');
            $table->index('batch_id');
            $table->index('exported_by');
            $table->index('expires_at');
        });

        // Failed import rows table (for tracking failed records)
        Schema::create('failed_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('data_imports')->onDelete('cascade');
            $table->integer('row_number');
            $table->json('row_data');
            $table->json('errors');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['import_id', 'is_resolved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_import_rows');
        Schema::dropIfExists('data_exports');
        Schema::dropIfExists('data_imports');
    }
};