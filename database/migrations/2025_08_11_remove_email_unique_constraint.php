<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove unique constraint from email to allow one person to have multiple roles
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the unique index on email
            $table->dropUnique('users_email_unique');
        });
        
        // Also make email nullable since it's optional in the form
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First remove nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
        
        // Then re-add the unique constraint if rolling back
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};