<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        mt_srand(20260526);

        $this->call([
            IndonesiaRegionSeeder::class,
            FacilitySeeder::class,
            UserSeeder::class,
            PropertySeeder::class,
            PropertyPhotoSeeder::class,
            PropertyAvailabilityCycleSeeder::class,
            RentalWorkflowSeeder::class,
        ]);
    }
}
