<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Schedule Information
            $table->string('schedule_name')->nullable(); // e.g., "Shift Pagi", "Jaga Malam"
            $table->enum('schedule_type', ['daily', 'weekly', 'monthly', 'custom'])->default('daily');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable();
            $table->date('schedule_date')->nullable(); // For specific date schedules
            
            // Time Information
            $table->time('check_in_time'); // Expected check-in time
            $table->time('check_out_time'); // Expected check-out time
            $table->integer('work_duration_minutes')->nullable(); // Expected work duration
            
            // Recurrence Settings
            $table->date('effective_from'); // Schedule starts from this date
            $table->date('effective_until')->nullable(); // Schedule ends on this date (null = indefinite)
            $table->boolean('is_recurring')->default(true); // Whether this schedule repeats
            
            // Status & Metadata
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('exceptions')->nullable(); // Holiday exceptions, special dates
            $table->text('notes')->nullable();
            
            // Location & Role Context
            $table->string('work_location')->nullable();
            $table->string('role_context')->nullable(); // e.g., "dokter", "paramedis", "admin"
            
            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'schedule_date']);
            $table->index(['user_id', 'day_of_week', 'status']);
            $table->index(['effective_from', 'effective_until']);
            $table->index(['status', 'schedule_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_schedules');
    }
};