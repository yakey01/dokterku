<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_tolerance_settings', function (Blueprint $table) {
            $table->id();
            
            // Scope Definition
            $table->string('setting_name'); // e.g., "Default Tolerance", "Doctor Tolerance", "Emergency Tolerance"
            $table->enum('scope_type', ['global', 'role', 'user', 'location', 'schedule_type'])->default('global');
            $table->string('scope_value')->nullable(); // role name, user_id, location, etc.
            
            // Tolerance Settings (in minutes)
            $table->integer('check_in_early_tolerance')->default(15); // Can check-in 15 minutes early
            $table->integer('check_in_late_tolerance')->default(15);  // Can check-in 15 minutes late
            $table->integer('check_out_early_tolerance')->default(30); // Can check-out 30 minutes early
            $table->integer('check_out_late_tolerance')->default(30);  // Can check-out 30 minutes late
            
            // Validation Settings
            $table->boolean('require_schedule_match')->default(true); // Must have active schedule
            $table->boolean('allow_early_checkin')->default(true);
            $table->boolean('allow_late_checkin')->default(true);
            $table->boolean('allow_early_checkout')->default(false);
            $table->boolean('allow_late_checkout')->default(true);
            
            // Emergency Override Settings
            $table->boolean('allow_emergency_override')->default(false);
            $table->json('emergency_override_roles')->nullable(); // Roles that can override
            $table->integer('emergency_override_duration')->default(120); // Minutes for emergency override
            
            // Business Rules
            $table->boolean('weekend_different_tolerance')->default(false);
            $table->integer('weekend_check_in_tolerance')->nullable();
            $table->integer('weekend_check_out_tolerance')->nullable();
            
            $table->boolean('holiday_different_tolerance')->default(false);
            $table->integer('holiday_check_in_tolerance')->nullable();
            $table->integer('holiday_check_out_tolerance')->nullable();
            
            // Status & Priority
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100); // Lower number = higher priority
            
            // Metadata
            $table->text('description')->nullable();
            $table->json('additional_rules')->nullable(); // For future extensibility
            
            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['scope_type', 'scope_value', 'is_active']);
            $table->index(['priority', 'is_active']);
            $table->unique(['scope_type', 'scope_value'], 'unique_scope_setting');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_tolerance_settings');
    }
};