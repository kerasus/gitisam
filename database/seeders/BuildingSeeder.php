<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buildings = [];

        foreach ($buildings as $buildingData) {
            Building::factory()->create($buildingData);
        }
    }
}
