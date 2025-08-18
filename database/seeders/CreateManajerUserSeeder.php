<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateManajerUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // Ensure manajer role exists
            $manajerRole = Role::firstOrCreate([
                'name' => 'manajer',
                'guard_name' => 'web',
            ], [
                'display_name' => 'Manajer',
                'description' => 'Manajer dengan akses ke dashboard eksekutif dan manajemen strategis',
            ]);

            $this->command->info('========================================');
            $this->command->info('🏢 CREATING MANAGER ACCOUNTS');
            $this->command->info('========================================');

            // Create Main Manager Account
            $mainManager = User::updateOrCreate([
                'email' => 'manajer@dokterku.com'
            ], [
                'name' => 'Dr. Budi Santoso, MM',
                'email' => 'manajer@dokterku.com',
                'password' => Hash::make('manajer123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign role
            if (!$mainManager->hasRole('manajer')) {
                $mainManager->assignRole('manajer');
            }

            // Skip Pegawai record creation - not needed for manager role

            $this->command->info('✅ Main Manager Created:');
            $this->command->info('   📧 Email: manajer@dokterku.com');
            $this->command->info('   🔑 Password: manajer123');
            $this->command->info('');

            // Create Alternative Manager Account
            $altManager = User::updateOrCreate([
                'email' => 'tina@manajer.com'
            ], [
                'name' => 'Tina Wijaya, SE, MBA',
                'email' => 'tina@manajer.com',
                'password' => Hash::make('tina123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!$altManager->hasRole('manajer')) {
                $altManager->assignRole('manajer');
            }

            // Skip Pegawai record creation - not needed for manager role

            $this->command->info('✅ Alternative Manager Created:');
            $this->command->info('   📧 Email: tina@manajer.com');
            $this->command->info('   🔑 Password: tina123');
            $this->command->info('');

            // Create Test Manager Account (for development)
            if (app()->environment(['local', 'development'])) {
                $testManager = User::updateOrCreate([
                    'email' => 'test.manajer@dokterku.com'
                ], [
                    'name' => 'Test Manager',
                    'email' => 'test.manajer@dokterku.com',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (!$testManager->hasRole('manajer')) {
                    $testManager->assignRole('manajer');
                }

                $this->command->info('✅ Test Manager Created (Dev Only):');
                $this->command->info('   📧 Email: test.manajer@dokterku.com');
                $this->command->info('   🔑 Password: password');
                $this->command->info('');
            }

            DB::commit();

            $this->command->info('========================================');
            $this->command->info('📊 MANAGER ACCOUNTS SUMMARY');
            $this->command->info('========================================');
            $this->command->info('');
            $this->command->info('🔐 LOGIN CREDENTIALS:');
            $this->command->info('');
            $this->command->table(
                ['Name', 'Email', 'Password', 'Status'],
                [
                    ['Dr. Budi Santoso, MM', 'manajer@dokterku.com', 'manajer123', '✅ Active'],
                    ['Tina Wijaya, SE, MBA', 'tina@manajer.com', 'tina123', '✅ Active'],
                    ['Test Manager', 'test.manajer@dokterku.com', 'password', app()->environment(['local', 'development']) ? '✅ Active' : '❌ Prod Only'],
                ]
            );
            $this->command->info('');
            $this->command->info('🌐 Dashboard URL: ' . url('/manajer'));
            $this->command->info('📊 Enhanced Dashboard: ' . url('/manajer/enhanced-manajer-dashboard'));
            $this->command->info('');
            $this->command->info('✨ All manager accounts have been created successfully!');
            $this->command->info('========================================');

        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('❌ Error creating manager accounts: ' . $e->getMessage());
            throw $e;
        }
    }
}