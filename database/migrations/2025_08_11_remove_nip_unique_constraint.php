<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove unique constraint from NIP to allow one person to have multiple roles
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the unique index on NIP
            $table->dropUnique('users_nip_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Re-add the unique constraint if rolling back
            $table->unique('nip');
        });
    }
};