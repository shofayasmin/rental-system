<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyPhoto;
use Database\Seeders\Concerns\SpecSeederSupport;
use Illuminate\Database\Seeder;

class PropertyPhotoSeeder extends Seeder
{
    use SpecSeederSupport;

    public function run(): void
    {
        $photoByCategory = $this->propertyPhotoFilesByCategory();

        if (collect($photoByCategory)->every(fn ($items) => $items->isEmpty())) {
            return;
        }

        $properties = Property::query()->orderBy('id')->get();

        foreach ($properties as $index => $property) {
            foreach ($this->selectPropertyPhotos($photoByCategory, $index) as $path) {
                if (!$path) {
                    continue;
                }

                PropertyPhoto::create([
                    'property_id' => $property->id,
                    'path' => $path,
                ]);
            }
        }
    }
}
