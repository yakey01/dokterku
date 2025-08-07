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
        Schema::table('users', function (Blueprint $table) {
            // Only add columns that don't exist
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (!Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable();
            }
            if (!Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 5)->default('id');
            }
            if (!Schema::hasColumn('users', 'notifications_enabled')) {
                $table->boolean('notifications_enabled')->default(true);
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false);
            }
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->string('two_factor_secret')->nullable();
            }
            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable();
            }
            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'theme_settings')) {
                $table->json('theme_settings')->nullable();
            }
            if (!Schema::hasColumn('users', 'sidebar_collapsed')) {
                $table->boolean('sidebar_collapsed')->default(false);
            }
            if (!Schema::hasColumn('users', 'dark_mode')) {
                $table->boolean('dark_mode')->default(false);
            }
            if (!Schema::hasColumn('users', 'layout_mode')) {
                $table->string('layout_mode', 20)->default('default');
            }
            if (!Schema::hasColumn('users', 'ui_preferences')) {
                $table->json('ui_preferences')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToRemove = [
                'avatar', 'preferences', 'locale', 'notifications_enabled',
                'two_factor_enabled', 'two_factor_secret', 'two_factor_recovery_codes',
                'two_factor_confirmed_at', 'theme_settings', 'sidebar_collapsed',
                'dark_mode', 'layout_mode', 'ui_preferences'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};