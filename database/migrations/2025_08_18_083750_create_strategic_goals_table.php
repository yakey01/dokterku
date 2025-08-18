<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategic_goals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['financial', 'operational', 'quality', 'growth', 'staff', 'patient_satisfaction']);
            $table->enum('period', ['monthly', 'quarterly', 'yearly']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('target_value', 15, 2);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->string('unit')->default('IDR'); // IDR, count, percentage, etc.
            $table->enum('status', ['draft', 'active', 'completed', 'paused', 'cancelled'])->default('draft');
            $table->json('success_criteria')->nullable();
            $table->integer('priority')->default(5); // 1=highest, 10=lowest
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'period']);
            $table->index(['category', 'start_date']);
            $table->index(['created_by', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_goals');
    }
};