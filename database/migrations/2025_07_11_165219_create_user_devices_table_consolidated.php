<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for user_devices table
     * Combines: create_user_devices_table, add_biometric_support_to_user_devices_table
     */
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Device Identification
            $table->string('device_id')->unique(); // IMEI/UUID/Fingerprint
            $table->string('device_name')->nullable(); // "iPhone 13 Pro", "Samsung Galaxy S23"
            $table->string('device_type'); // mobile, web, tablet
            $table->string('platform'); // iOS, Android, Web
            
            // OS & Browser Info
            $table->string('os_version')->nullable(); // "iOS 16.1", "Android 13"
            $table->string('browser_name')->nullable(); // "Chrome", "Safari", "Mobile App"
            $table->string('browser_version')->nullable();
            $table->string('user_agent')->nullable();
            
            // Network & Location Info
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->json('device_specs')->nullable(); // RAM, Storage, etc.
            
            // Biometric Support (from add_biometric_support migration)
            $table->json('biometric_capabilities')->nullable(); // Device biometric capabilities
            $table->boolean('biometric_enabled')->default(false);
            $table->json('biometric_types')->nullable(); // Enabled biometric types
            $table->timestamp('biometric_enrolled_at')->nullable();
            $table->integer('biometric_verification_count')->default(0);
            $table->timestamp('last_biometric_verification_at')->nullable();
            
            // Enhanced session management (from add_biometric_support migration)
            $table->string('refresh_token_hash', 255)->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->json('session_metadata')->nullable();
            
            // Security & Validation
            $table->string('device_fingerprint')->unique(); // Unique device signature
            $table->string('push_token')->nullable(); // For notifications
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false); // Primary device flag
            $table->enum('status', ['active', 'suspended', 'revoked'])->default('active');
            
            // Security enhancements (from add_biometric_support migration)
            $table->integer('security_score')->default(100); // 0-100 security score
            $table->json('security_violations')->nullable(); // Track violations
            $table->boolean('requires_admin_approval')->default(false);
            $table->timestamp('admin_approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Timestamps & Audit
            $table->timestamp('first_login_at'); // When device was first bound
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('verified_at')->nullable(); // Admin verification
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['user_id', 'device_id']);
            $table->index(['device_fingerprint']);
            $table->index(['status', 'is_active']);
            $table->index('last_activity_at');
            $table->index(['biometric_enabled']);
            $table->index(['security_score']);
            $table->index(['requires_admin_approval']);
            
            // Unique constraint: One device per user (STRICT mode)
            $table->unique(['user_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};