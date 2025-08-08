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
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('password')
                    ->constrained('roles')->onDelete('set null');
            }
            
            // From add_username_to_users_table
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable()->after('email');
            }
            
            // From add_profile_settings_to_users_table
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable();
            }
            if (!Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 5)->default('id');
            }
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('Asia/Jakarta');
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
            
            // From add_pegawai_id_to_users_table
            if (!Schema::hasColumn('users', 'pegawai_id')) {
                $table->foreignId('pegawai_id')->nullable()->after('role_id')
                    ->constrained('pegawais')->onDelete('cascade');
            }
            
            // From add_work_location_to_users_table
            if (!Schema::hasColumn('users', 'work_location_id')) {
                $table->foreignId('work_location_id')->nullable()
                    ->constrained('work_locations')->onDelete('set null');
            }
            
            // From add_themes_settings_to_users_table
            if (!Schema::hasColumn('users', 'theme')) {
                $table->string('theme', 20)->default('light');
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
            
            // Add soft deletes if not exists
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add indexes for performance (only if they don't exist)
        if (!Schema::hasColumn('users', 'username') || !Schema::hasIndex('users', 'users_username_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('username');
            });
        }
        if (!Schema::hasColumn('users', 'role_id') || !Schema::hasIndex('users', 'users_role_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('role_id');
            });
        }
        if (!Schema::hasColumn('users', 'pegawai_id') || !Schema::hasIndex('users', 'users_pegawai_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('pegawai_id');
            });
        }
        if (!Schema::hasColumn('users', 'work_location_id') || !Schema::hasIndex('users', 'users_work_location_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('work_location_id');
            });
        }
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