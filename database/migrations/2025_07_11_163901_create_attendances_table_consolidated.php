<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for attendances table
     * Combines: create_attendances_table, add_device_fields, add_gps_fields, 
     *           add_work_location_id, add_location_id
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->time('time_in');
            $table->time('time_out')->nullable();
            $table->string('latlon_in'); // "latitude,longitude" format
            $table->string('latlon_out')->nullable(); // "latitude,longitude" format
            $table->string('location_name_in')->nullable(); // Nama lokasi readable
            $table->string('location_name_out')->nullable(); // Nama lokasi readable
            
            // From add_device_fields_to_attendances_table
            $table->string('device_info')->nullable(); // Info device untuk tracking
            $table->string('device_id')->nullable();
            $table->string('device_type')->nullable(); // mobile, web, desktop
            $table->string('device_platform')->nullable(); // iOS, Android, Windows, Mac, Linux
            $table->string('app_version')->nullable();
            $table->string('ip_address_in')->nullable();
            $table->string('ip_address_out')->nullable();
            $table->string('user_agent')->nullable();
            
            // From add_gps_fields_to_attendances_table
            $table->decimal('latitude_in', 10, 8)->nullable();
            $table->decimal('longitude_in', 11, 8)->nullable();
            $table->decimal('latitude_out', 10, 8)->nullable();
            $table->decimal('longitude_out', 11, 8)->nullable();
            $table->decimal('accuracy_in', 8, 2)->nullable(); // GPS accuracy in meters
            $table->decimal('accuracy_out', 8, 2)->nullable();
            $table->integer('distance_from_location_in')->nullable(); // Distance in meters
            $table->integer('distance_from_location_out')->nullable();
            $table->boolean('is_location_valid_in')->default(true);
            $table->boolean('is_location_valid_out')->nullable();
            
            // From add_work_location_id_to_attendances_table
            $table->foreignId('work_location_id')->nullable()
                ->constrained('work_locations')->onDelete('set null');
            
            // From add_location_id_to_attendances_table (if exists)
            $table->foreignId('location_id')->nullable()
                ->constrained('locations')->onDelete('set null');
            
            $table->string('photo_in')->nullable(); // Selfie saat check-in
            $table->string('photo_out')->nullable(); // Selfie saat check-out
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->enum('status', ['present', 'late', 'incomplete'])->default('present');
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'date']);
            $table->index('date');
            $table->index('status');
            $table->index('work_location_id');
            $table->index('location_id');
            $table->index(['is_location_valid_in', 'is_location_valid_out']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};