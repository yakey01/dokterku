<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for all user table modifications
     * Combines: add_role_id, add_username, add_profile_settings, make_role_id_nullable,
     *           add_pegawai_id, add_work_location, add_themes_settings
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // From add_role_id_to_users_table
            $table->foreignId('role_id')->nullable()->after('password')
                ->constrained('roles')->onDelete('set null');
            
            // From add_username_to_users_table
            $table->string('username')->unique()->nullable()->after('email');
            
            // From add_profile_settings_to_users_table
            $table->string('avatar')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->json('preferences')->nullable();
            $table->string('locale', 5)->default('id');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            
            // From add_pegawai_id_to_users_table
            $table->foreignId('pegawai_id')->nullable()->after('role_id')
                ->constrained('pegawais')->onDelete('cascade');
            
            // From add_work_location_to_users_table
            $table->foreignId('work_location_id')->nullable()
                ->constrained('work_locations')->onDelete('set null');
            
            // From add_themes_settings_to_users_table
            $table->string('theme', 20)->default('light');
            $table->json('theme_settings')->nullable();
            $table->boolean('sidebar_collapsed')->default(false);
            $table->boolean('dark_mode')->default(false);
            $table->string('layout_mode', 20)->default('default');
            $table->json('ui_preferences')->nullable();
            
            // Add soft deletes if not exists
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
            
            // Add indexes for performance
            $table->index('username');
            $table->index('role_id');
            $table->index('pegawai_id');
            $table->index('work_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['role_id']);
            $table->dropForeign(['pegawai_id']);
            $table->dropForeign(['work_location_id']);
            
            // Drop indexes
            $table->dropIndex(['username']);
            $table->dropIndex(['role_id']);
            $table->dropIndex(['pegawai_id']);
            $table->dropIndex(['work_location_id']);
            
            // Drop columns
            $table->dropColumn([
                'role_id', 'username', 'avatar', 'phone', 'address', 
                'preferences', 'locale', 'timezone', 'notifications_enabled',
                'two_factor_enabled', 'two_factor_secret', 'two_factor_recovery_codes',
                'two_factor_confirmed_at', 'pegawai_id', 'work_location_id',
                'theme', 'theme_settings', 'sidebar_collapsed', 'dark_mode',
                'layout_mode', 'ui_preferences'
            ]);
            
            $table->dropSoftDeletes();
        });
    }
};