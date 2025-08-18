<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_approvals', function (Blueprint $table) {
            $table->id();
            $table->enum('approval_type', ['financial', 'policy_override', 'staff_action', 'emergency', 'budget_adjustment']);
            $table->string('title');
            $table->text('description');
            $table->decimal('amount', 15, 2)->nullable(); // For financial approvals
            $table->string('requester_role', 50); // bendahara, petugas, admin, etc.
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected', 'escalated'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->text('justification'); // Why approval is needed
            $table->text('approval_notes')->nullable(); // Manager's decision notes
            $table->json('supporting_data')->nullable(); // Related records, attachments
            $table->timestamp('required_by')->nullable(); // Deadline for decision
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->boolean('auto_approved')->default(false);
            $table->string('reference_type')->nullable(); // Model class name
            $table->unsignedBigInteger('reference_id')->nullable(); // Model ID
            $table->timestamps();
            
            $table->index(['status', 'priority']);
            $table->index(['approval_type', 'status']);
            $table->index(['requested_by', 'approved_by']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_approvals');
    }
};