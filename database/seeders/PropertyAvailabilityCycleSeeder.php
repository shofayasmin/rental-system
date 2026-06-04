<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyAvailabilityCycle;
use Carbon\Carbon;
use Database\Seeders\Concerns\SpecSeederSupport;
use Illuminate\Database\Seeder;

class PropertyAvailabilityCycleSeeder extends Seeder
{
    use SpecSeederSupport;

    public function run(): void
    {
        $periodStart = Carbon::parse($this->specConfig()['period_start']);
        $properties = Property::query()->orderBy('id')->get();

        foreach ($properties as $index => $property) {
            $availableFrom = match ($property->status) {
                'rented' => $periodStart->copy()->addDays(20 + ($index % 180)),
                'maintenance' => $periodStart->copy()->addDays(10 + ($index % 120)),
                default => $periodStart->copy()->addDays(5 + ($index % 90)),
            };

            $unavailableAt = match ($property->status) {
                'rented' => $availableFrom->copy()->addMonthsNoOverflow(14),
                'maintenance' => $availableFrom->copy()->addDays(10 + ($index % 14)),
                default => null,
            };

            PropertyAvailabilityCycle::create([
                'property_id' => $property->id,
                'available_from_at' => $availableFrom,
                'unavailable_at' => $unavailableAt,
                'closed_by' => match ($property->status) {
                    'rented' => 'rented',
                    'maintenance' => 'maintenance',
                    default => null,
                },
            ]);
        }
    }
}
