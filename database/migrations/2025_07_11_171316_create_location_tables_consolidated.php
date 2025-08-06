<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for all location-related tables
     * Combines: work_locations, locations, location_validations, gps_spoofing tables
     */
    public function up(): void
    {
        // Work Locations table (consolidated with tolerance fields and unit_kerja)
        Schema::create('work_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius')->default(100); // in meters
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            
            // From add_tolerance_fields_to_work_locations_table
            $table->integer('check_in_tolerance')->default(15); // minutes before shift
            $table->integer('check_out_tolerance')->default(15); // minutes after shift
            $table->boolean('allow_flexible_location')->default(false);
            $table->json('alternative_locations')->nullable();
            $table->integer('max_distance_tolerance')->default(500); // meters
            
            // From add_unit_kerja_to_work_locations_table
            $table->enum('unit_kerja', ['umum', 'gigi', 'lab', 'farmasi', 'administrasi'])->default('umum');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('is_active');
            $table->index('unit_kerja');
        });

        // Locations table (generic locations)
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('general'); // general, clinic, hospital, etc
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->text('address')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('is_active');
        });

        // Location Validations table (consolidated with security fields)
        Schema::create('location_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->foreignId('work_location_id')->constrained('work_locations')->onDelete('cascade');
            $table->decimal('actual_latitude', 10, 8);
            $table->decimal('actual_longitude', 11, 8);
            $table->decimal('distance', 10, 2); // in meters
            $table->boolean('is_valid');
            $table->string('validation_method')->default('gps'); // gps, wifi, manual
            $table->json('validation_data')->nullable();
            
            // From add_security_fields_to_location_validations_table
            $table->string('device_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('security_metadata')->nullable();
            $table->integer('risk_score')->default(0);
            $table->boolean('is_suspicious')->default(false);
            $table->text('suspicious_reasons')->nullable();
            
            $table->timestamps();
            
            $table->index(['attendance_id', 'is_valid']);
            $table->index('work_location_id');
            $table->index('is_suspicious');
        });

        // GPS Spoofing Configs table (consolidated with device limit settings)
        Schema::create('gps_spoofing_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->integer('max_speed_kmh')->default(120);
            $table->integer('min_accuracy_meters')->default(50);
            $table->integer('location_history_minutes')->default(30);
            $table->boolean('check_mock_location')->default(true);
            $table->boolean('check_developer_options')->default(true);
            $table->boolean('check_root_jailbreak')->default(true);
            $table->json('trusted_apps')->nullable();
            $table->json('blacklisted_apps')->nullable();
            
            // From add_device_limit_settings_to_gps_spoofing_configs_table
            $table->integer('max_devices_per_user')->default(2);
            $table->boolean('device_verification_required')->default(true);
            $table->integer('device_trust_period_days')->default(30);
            $table->boolean('notify_on_new_device')->default(true);
            $table->json('device_fingerprint_factors')->nullable();
            
            $table->timestamps();
        });

        // GPS Spoofing Settings table
        Schema::create('gps_spoofing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->text('setting_value');
            $table->string('description')->nullable();
            $table->string('data_type')->default('string');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('setting_key');
            $table->index('is_active');
        });

        // GPS Spoofing Detections table
        Schema::create('gps_spoofing_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->nullable()->constrained()->onDelete('set null');
            $table->string('detection_type'); // mock_location, impossible_speed, etc
            $table->json('detection_data');
            $table->integer('confidence_score'); // 0-100
            $table->string('action_taken')->nullable(); // blocked, warned, logged
            $table->string('device_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('detection_type');
            $table->index('confidence_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gps_spoofing_detections');
        Schema::dropIfExists('gps_spoofing_settings');
        Schema::dropIfExists('gps_spoofing_configs');
        Schema::dropIfExists('location_validations');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('work_locations');
    }
};