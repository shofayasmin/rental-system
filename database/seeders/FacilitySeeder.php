<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['slug' => 'electricity', 'name' => 'Electricity', 'category' => 'utilities'],
            ['slug' => 'water_supply', 'name' => 'Water Supply', 'category' => 'utilities'],
            ['slug' => 'wifi', 'name' => 'WiFi', 'category' => 'utilities'],
            ['slug' => 'ac', 'name' => 'Air Conditioner', 'category' => 'utilities'],
            ['slug' => 'water_heater', 'name' => 'Water Heater', 'category' => 'utilities'],
            ['slug' => 'furnished', 'name' => 'Furnished', 'category' => 'interior'],
            ['slug' => 'kitchen_set', 'name' => 'Kitchen Set', 'category' => 'interior'],
            ['slug' => 'wardrobe', 'name' => 'Wardrobe', 'category' => 'interior'],
            ['slug' => 'carport', 'name' => 'Carport', 'category' => 'outdoor'],
            ['slug' => 'garden', 'name' => 'Garden', 'category' => 'outdoor'],
            ['slug' => 'balcony', 'name' => 'Balcony', 'category' => 'outdoor'],
            ['slug' => 'backyard', 'name' => 'Backyard', 'category' => 'outdoor'],
        ];

        foreach ($items as $item) {
            Facility::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'is_active' => true,
                ]
            );
        }
    }
}
