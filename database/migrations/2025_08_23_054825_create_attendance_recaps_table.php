<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_recaps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('staff_name');
            $table->string('staff_type');
            $table->string('position');
            $table->integer('total_working_days');
            $table->integer('days_present');
            $table->time('average_check_in')->nullable();
            $table->time('average_check_out')->nullable();
            $table->decimal('total_working_hours', 8, 2)->default(0);
            $table->decimal('attendance_percentage', 5, 2)->default(0);
            $table->string('status')->default('poor');
            $table->integer('rank')->default(0);
            $table->timestamps();
            
            $table->index(['staff_type', 'attendance_percentage']);
            $table->index('rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_recaps');
    }
};
