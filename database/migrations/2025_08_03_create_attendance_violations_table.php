<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_violations', function (Blueprint $table) {
            $table->id();
            
            // Reference to attendance record
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->onDelete('cascade');
            $table->foreignId('user_schedule_id')->nullable()->constrained()->onDelete('set null');
            
            // Violation Details
            $table->enum('violation_type', [
                'no_schedule', 
                'late_checkin', 
                'early_checkin', 
                'late_checkout', 
                'early_checkout',
                'missed_checkin',
                'missed_checkout',
                'outside_tolerance'
            ]);
            
            $table->enum('action_type', ['checkin', 'checkout']);
            $table->timestamp('attempted_at'); // When user tried to check in/out
            $table->timestamp('scheduled_at')->nullable(); // When they were supposed to check in/out
            $table->integer('tolerance_minutes')->nullable(); // Applied tolerance in minutes
            $table->integer('violation_minutes')->nullable(); // How many minutes off
            
            // Severity Classification
            $table->enum('severity', ['minor', 'moderate', 'major', 'critical'])->default('minor');
            $table->boolean('is_excused')->default(false); // Admin can excuse violations
            
            // Context Information
            $table->string('location_attempted')->nullable(); // Where they tried to check in/out
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('reason')->nullable(); // User provided reason
            
            // Resolution
            $table->enum('status', ['pending', 'approved', 'rejected', 'auto_resolved'])->default('pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            
            // Emergency Override
            $table->boolean('is_emergency_override')->default(false);
            $table->foreignId('overridden_by')->nullable()->constrained('users');
            $table->text('override_reason')->nullable();
            
            // Audit
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'violation_type', 'created_at']);
            $table->index(['severity', 'status', 'is_excused']);
            $table->index(['attempted_at', 'scheduled_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_violations');
    }
};