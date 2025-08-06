<?php

namespace Database\Seeders\Master;

use App\Models\Shift;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Pagi',
                'start_time' => '08:00',
                'end_time' => '14:00',
                'description' => 'Shift pagi dari jam 8 pagi hingga 2 siang',
            ],
            [
                'name' => 'Siang',
                'start_time' => '14:00',
                'end_time' => '20:00',
                'description' => 'Shift siang dari jam 2 siang hingga 8 malam',
            ],
            [
                'name' => 'Malam',
                'start_time' => '20:00',
                'end_time' => '08:00',
                'description' => 'Shift malam dari jam 8 malam hingga 8 pagi',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::updateOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}
