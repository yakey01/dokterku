<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->enum('department', ['medical', 'administrative', 'financial', 'support']);
            $table->string('metric_name');
            $table->decimal('metric_value', 12, 2);
            $table->string('metric_unit', 20); // IDR, count, percentage, hours, etc.
            $table->date('measurement_date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly']);
            $table->decimal('target_value', 12, 2)->nullable();
            $table->decimal('benchmark_value', 12, 2)->nullable();
            $table->enum('trend', ['improving', 'declining', 'stable'])->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->text('notes')->nullable();
            $table->boolean('is_kpi')->default(false); // Key Performance Indicator
            $table->integer('score')->nullable(); // 1-100 performance score
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['department', 'measurement_date']);
            $table->index(['metric_name', 'period_type']);
            $table->index(['is_kpi', 'measurement_date']);
            $table->unique(['department', 'metric_name', 'measurement_date', 'period_type'], 'dept_metric_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_performance_metrics');
    }
};