<?php

namespace Database\Seeders\Master;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Super admin dengan akses penuh ke semua fitur sistem',
                'permissions' => [
                    'manage_users', 'manage_roles', 'manage_clinic', 'view_reports',
                    'manage_finance', 'validate_transactions', 'export_data'
                ]
            ],
            [
                'name' => 'manajer',
                'display_name' => 'Manajer',
                'description' => 'Manajer klinik dengan akses ke laporan dan validasi',
                'permissions' => [
                    'view_reports', 'validate_transactions', 'manage_finance',
                    'view_analytics', 'export_data'
                ]
            ],
            [
                'name' => 'bendahara',
                'display_name' => 'Bendahara',
                'description' => 'Bendahara yang mengelola keuangan dan validasi transaksi',
                'permissions' => [
                    'manage_finance', 'validate_transactions', 'view_reports',
                    'manage_expenses', 'manage_income'
                ]
            ],
            [
                'name' => 'petugas',
                'display_name' => 'Petugas',
                'description' => 'Petugas yang melakukan input data transaksi',
                'permissions' => [
                    'input_transactions', 'view_own_data', 'manage_patients'
                ]
            ],
            [
                'name' => 'paramedis',
                'display_name' => 'Paramedis',
                'description' => 'Tenaga paramedis yang melakukan tindakan medis',
                'permissions' => [
                    'input_medical_actions', 'view_own_data', 'manage_patients'
                ]
            ],
            [
                'name' => 'dokter',
                'display_name' => 'Dokter',
                'description' => 'Dokter yang melakukan tindakan medis dan konsultasi',
                'permissions' => [
                    'input_medical_actions', 'view_own_data', 'manage_patients',
                    'view_medical_reports'
                ]
            ],
            [
                'name' => 'non_paramedis',
                'display_name' => 'Non Paramedis',
                'description' => 'Tenaga non-paramedis yang membantu operasional klinik',
                'permissions' => [
                    'input_support_actions', 'view_own_data'
                ]
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}
