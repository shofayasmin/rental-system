<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\Concerns\SpecSeederSupport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertySeeder extends Seeder
{
    use SpecSeederSupport;

    public function run(): void
    {
        $config = $this->specConfig();

        $agents = User::query()
            ->where('role', 'agent')
            ->orderBy('id')
            ->get()
            ->values();

        $villages = DB::table('villages')
            ->join('districts', 'districts.id', '=', 'villages.district_id')
            ->join('regencies', 'regencies.id', '=', 'districts.regency_id')
            ->join('provinces', 'provinces.id', '=', 'regencies.province_id')
            ->select([
                'villages.id as village_id',
                'villages.name as village_name',
                'districts.id as district_id',
                'districts.name as district_name',
                'regencies.id as regency_id',
                'regencies.name as regency_name',
                'provinces.id as province_id',
                'provinces.name as province_name',
            ])
            ->get();

        $facilityIds = Facility::query()
            ->where('is_active', true)
            ->pluck('id')
            ->values()
            ->all();

        if ($agents->isEmpty() || $villages->isEmpty()) {
            return;
        }

        $cityBuckets = $this->buildCityDistribution($config['total_properties']);
        $propertyStatusPool = $this->buildStatusPool($this->propertyStatusCounts());
        shuffle($propertyStatusPool);
        $propertiesPerAgent = intdiv($config['total_properties'], max(1, $agents->count()));
        $remainder = $config['total_properties'] % max(1, $agents->count());
        $propertyIndex = 0;

        foreach ($agents as $agentIndex => $agent) {
            $slots = $propertiesPerAgent + ($agentIndex < $remainder ? 1 : 0);

            for ($slot = 0; $slot < $slots; $slot++) {
                if ($propertyIndex >= $config['total_properties']) {
                    break 2;
                }

                $bucketKey = $cityBuckets[$propertyIndex];
                $bucket = $this->cityBuckets()[$bucketKey];
                $location = $this->locationForBucket($bucketKey, $villages);
                $status = $propertyStatusPool[$propertyIndex] ?? 'to-let';
                $bedrooms = $this->bedroomsForSlot($slot);
                $bathrooms = $this->bathroomsForSlot($slot);
                $rentPrice = $this->rentPriceForBucket($bucketKey, $propertyIndex, $slot);
                $landArea = mt_rand(55, 190);
                $buildingArea = mt_rand(45, 230);
                $latitude = $bucket['lat'] + (mt_rand(-180, 180) / 10000);
                $longitude = $bucket['lng'] + (mt_rand(-180, 180) / 10000);

                $property = Property::create([
                    'agent_id' => $agent->id,
                    'title' => sprintf('%dBR Rental in %s #%04d', $bedrooms, $location['regency_name'], $propertyIndex + 1),
                    'province_id' => $location['province_id'],
                    'regency_id' => $location['regency_id'],
                    'district_id' => $location['district_id'],
                    'village_id' => $location['village_id'],
                    'address' => sprintf('No. %d, %s, %s', $propertyIndex + 1, $location['village_name'], $location['regency_name']),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'description' => sprintf(
                        'A %d bedroom rental unit located in %s with immediate access to daily amenities and transport routes.',
                        $bedrooms,
                        $location['regency_name']
                    ),
                    'bedrooms' => $bedrooms,
                    'bathrooms' => (float) $bathrooms,
                    'floors' => mt_rand(1, 2),
                    'area' => $landArea,
                    'building_area' => $buildingArea,
                    'facilities' => json_encode([]),
                    'rent_price' => $rentPrice,
                    'status' => $status,
                ]);

                if (!empty($facilityIds)) {
                    $selectedFacilityIds = collect($facilityIds)
                        ->shuffle()
                        ->take(mt_rand(4, 7))
                        ->values()
                        ->all();

                    foreach ($selectedFacilityIds as $facilityId) {
                        DB::table('property_facility')->insert([
                            'property_id' => $property->id,
                            'facility_id' => $facilityId,
                            'value' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $propertyIndex++;
            }
        }
    }
}
