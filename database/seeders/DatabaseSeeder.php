<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core master data
            Master\RoleSeeder::class,
            Master\ShiftSeeder::class,
            Master\JenisTindakanSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            
            // Additional users added via admin
            NewUsersSeeder::class,
            
            // Location and GPS validation
            // LocationValidationSeeder::class, // Doesn't exist
            GpsSpoofingDetectionSeeder::class,
            
            // NonParamedis system seeders
            WorkLocationSeeder::class,
            NonParamedisUserSeeder::class,
            NonParamedisAttendanceSeeder::class,
            
            // Dokter system seeders
            DokterPermissionsSeeder::class,
            DokterSeeder::class,
            DokterUserSeeder::class,
            DokterJadwalJagaSeeder::class,
            DokterTindakanSeeder::class,
            DokterAttendanceSeeder::class,
        ]);
    }
}
