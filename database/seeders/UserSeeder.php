<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::all()->keyBy('name');

        $users = [
            [
                'role_id' => $roles['admin']->id,
                'name' => 'Administrator',
                'email' => 'admin@dokterku.com',
                'password' => Hash::make('admin123'),
                'nip' => 'ADM001',
                'no_telepon' => '081234567890',
                'tanggal_bergabung' => '2024-01-01',
            ],
            [
                'role_id' => $roles['manajer']->id,
                'name' => 'Dr. Manajer Klinik',
                'email' => 'manajer@dokterku.com',
                'password' => Hash::make('manajer123'),
                'nip' => 'MNG001',
                'no_telepon' => '081234567891',
                'tanggal_bergabung' => '2024-01-01',
            ],
            [
                'role_id' => $roles['bendahara']->id,
                'name' => 'Bendahara Klinik',
                'email' => 'bendahara@dokterku.com',
                'password' => Hash::make('bendahara123'),
                'nip' => 'BND001',
                'no_telepon' => '081234567892',
                'tanggal_bergabung' => '2024-01-01',
            ],
            [
                'role_id' => $roles['petugas']->id,
                'name' => 'Petugas Administrasi',
                'email' => 'petugas@dokterku.com',
                'password' => Hash::make('petugas123'),
                'nip' => 'PTG001',
                'no_telepon' => '081234567893',
                'tanggal_bergabung' => '2024-01-01',
            ],
            [
                'role_id' => $roles['paramedis']->id,
                'name' => 'Perawat Suster',
                'email' => 'perawat@dokterku.com',
                'password' => Hash::make('perawat123'),
                'nip' => 'PMD001',
                'no_telepon' => '081234567896',
                'tanggal_bergabung' => '2024-01-01',
            ],
            [
                'role_id' => $roles['paramedis']->id,
                'name' => 'Bidan Praktik',
                'email' => 'bidan@dokterku.com',
                'password' => Hash::make('bidan123'),
                'nip' => 'PMD002',
                'no_telepon' => '081234567897',
                'tanggal_bergabung' => '2024-01-01',
            ],
            [
                'role_id' => $roles['paramedis']->id,
                'name' => 'Naning Paramedis',
                'username' => 'naning',
                'email' => 'naning@dokterku.com',
                'password' => Hash::make('naning123'),
                'nip' => 'PMD003',
                'no_telepon' => '081234567810',
                'tanggal_bergabung' => '2024-01-01',
            ],
            [
                'role_id' => $roles['non_paramedis']->id,
                'name' => 'Asisten Klinik',
                'email' => 'asisten@dokterku.com',
                'password' => Hash::make('asisten123'),
                'nip' => 'NPM001',
                'no_telepon' => '081234567898',
                'tanggal_bergabung' => '2024-01-01',
            ],
            [
                'role_id' => $roles['non_paramedis']->id,
                'name' => 'Apoteker',
                'email' => 'apoteker@dokterku.com',
                'password' => Hash::make('apoteker123'),
                'nip' => 'NPM002',
                'no_telepon' => '081234567899',
                'tanggal_bergabung' => '2024-01-01',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);
            
            // Assign Spatie role based on role_id
            $role = Role::find($userData['role_id']);
            if ($role) {
                $user->assignRole($role->name);
            }
        }
    }
}
