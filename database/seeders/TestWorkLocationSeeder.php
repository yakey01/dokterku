<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkLocation;
use App\Models\User;

class TestWorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating test work locations...');

        // Create main office
        $mainOffice = WorkLocation::create([
            'name' => 'Kantor Pusat Jakarta',
            'description' => 'Kantor pusat utama di Jakarta Selatan',
            'address' => 'Jl. Sudirman No. 123, Jakarta Selatan',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius_meters' => 100,
            'is_active' => true,
            'location_type' => 'main_office',
            'unit_kerja' => 'umum',
            'contact_person' => 'John Doe',
            'contact_phone' => '081234567890',
            'require_photo' => true,
            'strict_geofence' => true,
            'gps_accuracy_required' => 20,
            'late_tolerance_minutes' => 15,
            'early_departure_tolerance_minutes' => 15,
            'break_time_minutes' => 60,
            'overtime_threshold_minutes' => 480,
            'checkin_before_shift_minutes' => 30,
            'checkout_after_shift_minutes' => 60,
            'allowed_shifts' => ['Pagi', 'Siang'],
            'working_hours' => [
                'monday' => ['start' => '08:00', 'end' => '17:00'],
                'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                'thursday' => ['start' => '08:00', 'end' => '17:00'],
                'friday' => ['start' => '08:00', 'end' => '17:00'],
            ]
        ]);

        // Create branch office
        $branchOffice = WorkLocation::create([
            'name' => 'Cabang Bandung',
            'description' => 'Kantor cabang di Bandung',
            'address' => 'Jl. Asia Afrika No. 45, Bandung',
            'latitude' => -6.9175,
            'longitude' => 107.6191,
            'radius_meters' => 150,
            'is_active' => true,
            'location_type' => 'branch_office',
            'unit_kerja' => 'gigi',
            'contact_person' => 'Jane Smith',
            'contact_phone' => '081987654321',
            'require_photo' => false,
            'strict_geofence' => false,
            'gps_accuracy_required' => 30,
            'late_tolerance_minutes' => 20,
            'early_departure_tolerance_minutes' => 10,
            'break_time_minutes' => 45,
            'overtime_threshold_minutes' => 480,
            'checkin_before_shift_minutes' => 45,
            'checkout_after_shift_minutes' => 90,
            'allowed_shifts' => ['Pagi', 'Siang', 'Malam'],
            'working_hours' => [
                'monday' => ['start' => '09:00', 'end' => '18:00'],
                'tuesday' => ['start' => '09:00', 'end' => '18:00'],
                'wednesday' => ['start' => '09:00', 'end' => '18:00'],
                'thursday' => ['start' => '09:00', 'end' => '18:00'],
                'friday' => ['start' => '09:00', 'end' => '18:00'],
                'saturday' => ['start' => '09:00', 'end' => '15:00'],
            ]
        ]);

        // Create a test location that can be safely deleted
        $testLocation = WorkLocation::create([
            'name' => 'Test Location - Safe to Delete',
            'description' => 'This is a test location with no dependencies',
            'address' => 'Jl. Test No. 999, Test City',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'radius_meters' => 50,
            'is_active' => true,
            'location_type' => 'project_site',
            'unit_kerja' => 'lab',
            'contact_person' => 'Test Person',
            'contact_phone' => '081111111111',
            'require_photo' => false,
            'strict_geofence' => true,
            'gps_accuracy_required' => 25,
            'late_tolerance_minutes' => 10,
            'early_departure_tolerance_minutes' => 5,
            'break_time_minutes' => 30,
            'overtime_threshold_minutes' => 420,
            'checkin_before_shift_minutes' => 15,
            'checkout_after_shift_minutes' => 30,
            'allowed_shifts' => ['Pagi'],
        ]);

        $this->command->info("Created {$mainOffice->name} (ID: {$mainOffice->id})");
        $this->command->info("Created {$branchOffice->name} (ID: {$branchOffice->id})");
        $this->command->info("Created {$testLocation->name} (ID: {$testLocation->id})");
        $this->command->info('Test work locations created successfully!');
    }
}