<?php

namespace Database\Seeders\Concerns;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

trait SpecSeederSupport
{
    protected function specConfig(): array
    {
        return [
            'period_start' => '2025-01-01 00:00:00',
            'period_end' => '2026-05-31 23:59:59',
            'total_properties' => 200,
            'total_agents' => 30,
            'total_tenants' => 150,
            'target_requests' => 160,
            'random_seed' => 20260526,
        ];
    }

    protected function cityBuckets(): array
    {
        return [
            'jakarta' => [
                'label' => 'Jakarta',
                'province_code' => 'ID-JK',
                'regency_code' => 'ID-JK-JP',
                'district_code' => 'ID-JK-JP-GM',
                'village_code' => 'ID-JK-JP-GM-CD',
                'province_name' => 'DKI Jakarta',
                'regency_name' => 'Jakarta Pusat',
                'district_name' => 'Gambir',
                'village_name' => 'Cideng',
                'share' => 0.28,
                'rent_min' => 4200000,
                'rent_max' => 7200000,
                'lat' => -6.177,
                'lng' => 106.827,
            ],
            'bandung' => [
                'label' => 'Bandung',
                'province_code' => 'ID-JB',
                'regency_code' => 'ID-JB-BDG',
                'district_code' => 'ID-JB-BDG-CB',
                'village_code' => 'ID-JB-BDG-CB-DG',
                'province_name' => 'Jawa Barat',
                'regency_name' => 'Kota Bandung',
                'district_name' => 'Coblong',
                'village_name' => 'Dago',
                'share' => 0.16,
                'rent_min' => 3800000,
                'rent_max' => 6200000,
                'lat' => -6.895,
                'lng' => 107.610,
            ],
            'surabaya' => [
                'label' => 'Surabaya',
                'province_code' => 'ID-JI',
                'regency_code' => 'ID-JI-SBY',
                'district_code' => 'ID-JI-SBY-TG',
                'village_code' => 'ID-JI-SBY-TG-KR',
                'province_name' => 'Jawa Timur',
                'regency_name' => 'Kota Surabaya',
                'district_name' => 'Tegalsari',
                'village_name' => 'Kedungdoro',
                'share' => 0.14,
                'rent_min' => 3600000,
                'rent_max' => 5800000,
                'lat' => -7.256,
                'lng' => 112.739,
            ],
            'medan' => [
                'label' => 'Medan',
                'province_code' => 'ID-SU',
                'regency_code' => 'ID-SU-MDN',
                'district_code' => 'ID-SU-MDN-MK',
                'village_code' => 'ID-SU-MDN-MK-KP',
                'province_name' => 'Sumatera Utara',
                'regency_name' => 'Kota Medan',
                'district_name' => 'Medan Kota',
                'village_name' => 'Kota Matsum I',
                'share' => 0.10,
                'rent_min' => 3200000,
                'rent_max' => 5200000,
                'lat' => 3.586,
                'lng' => 98.675,
            ],
            'makassar' => [
                'label' => 'Makassar',
                'province_code' => 'ID-SN',
                'regency_code' => 'ID-SN-MKS',
                'district_code' => 'ID-SN-MKS-PA',
                'village_code' => 'ID-SN-MKS-PA-MS',
                'province_name' => 'Sulawesi Selatan',
                'regency_name' => 'Kota Makassar',
                'district_name' => 'Panakkukang',
                'village_name' => 'Masale',
                'share' => 0.08,
                'rent_min' => 3200000,
                'rent_max' => 5200000,
                'lat' => -5.149,
                'lng' => 119.432,
            ],
            'yogyakarta' => [
                'label' => 'Yogyakarta',
                'province_code' => 'ID-YO',
                'regency_code' => 'ID-YO-YGY',
                'district_code' => 'ID-YO-YGY-GD',
                'village_code' => 'ID-YO-YGY-GD-KL',
                'province_name' => 'DI Yogyakarta',
                'regency_name' => 'Kota Yogyakarta',
                'district_name' => 'Gondokusuman',
                'village_name' => 'Klitren',
                'share' => 0.08,
                'rent_min' => 3300000,
                'rent_max' => 5400000,
                'lat' => -7.786,
                'lng' => 110.384,
            ],
            'semarang' => [
                'label' => 'Semarang',
                'province_code' => 'ID-JT',
                'regency_code' => 'ID-JT-SMG',
                'district_code' => 'ID-JT-SMG-CN',
                'village_code' => 'ID-JT-SMG-CN-JM',
                'province_name' => 'Jawa Tengah',
                'regency_name' => 'Kota Semarang',
                'district_name' => 'Candisari',
                'village_name' => 'Jomblang',
                'share' => 0.08,
                'rent_min' => 3300000,
                'rent_max' => 5200000,
                'lat' => -6.981,
                'lng' => 110.408,
            ],
            'denpasar' => [
                'label' => 'Denpasar',
                'province_code' => 'ID-BA',
                'regency_code' => 'ID-BA-DPS',
                'district_code' => 'ID-BA-DPS-DS',
                'village_code' => 'ID-BA-DPS-DS-SN',
                'province_name' => 'Bali',
                'regency_name' => 'Kota Denpasar',
                'district_name' => 'Denpasar Selatan',
                'village_name' => 'Sanur',
                'share' => 0.08,
                'rent_min' => 3600000,
                'rent_max' => 5600000,
                'lat' => -8.693,
                'lng' => 115.263,
            ],
        ];
    }

    protected function statusCounts(): array
    {
        return [
            'paid' => 136,
            'pending_review' => 10,
            'awaiting_payment' => 6,
            'rejected' => 4,
            'cancelled_by_tenant' => 2,
            'cancelled_by_agent' => 2,
            'cancelled_lost' => 0,
        ];
    }

    protected function propertyStatusCounts(): array
    {
        return [
            'rented' => 136,
            'to-let' => 49,
            'maintenance' => 15,
        ];
    }

    protected function buildCityDistribution(int $totalProperties): array
    {
        $result = [];

        foreach ($this->cityBuckets() as $key => $bucket) {
            $count = (int) round($totalProperties * $bucket['share']);

            for ($i = 0; $i < $count; $i++) {
                $result[] = $key;
            }
        }

        while (count($result) < $totalProperties) {
            $result[] = 'jakarta';
        }

        shuffle($result);

        return $result;
    }

    protected function buildStatusPool(array $counts): array
    {
        $pool = [];

        foreach ($counts as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $pool[] = $status;
            }
        }

        return $pool;
    }

    protected function buildRequestDistribution(int $totalRequests, int $propertyCount): array
    {
        $baseCount = $totalRequests >= $propertyCount ? 1 : 0;
        $counts = array_fill(0, $propertyCount, $baseCount);
        $remaining = max(0, $totalRequests - ($baseCount * $propertyCount));

        if ($baseCount === 0) {
            $counts = array_fill(0, $propertyCount, 0);
            $remaining = $totalRequests;
        }

        for ($i = 0; $i < $remaining; $i++) {
            $counts[$i % $propertyCount]++;
        }

        shuffle($counts);

        return $counts;
    }

    protected function buildDatePool(Carbon $start, Carbon $end): array
    {
        $days = [];
        $period = CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $end->copy()->startOfDay());

        foreach ($period as $day) {
            $monthWeight = match ((int) $day->month) {
                1, 11, 12 => 1.25,
                6, 7, 8 => 1.35,
                default => 1.0,
            };

            $weekdayWeight = match ((int) $day->dayOfWeekIso) {
                1, 2, 3, 4 => 1.25,
                5 => 1.05,
                6 => 0.8,
                7 => 0.7,
            };

            $weight = max(0.2, $monthWeight * $weekdayWeight);
            $copies = max(1, (int) round($weight * 3));

            for ($i = 0; $i < $copies; $i++) {
                $days[] = $day->copy();
            }
        }

        return $days;
    }

    protected function sampleDateTime(array $pool): Carbon
    {
        $date = $pool[array_rand($pool)]->copy();
        $hour = mt_rand(8, 18);
        $minute = [0, 15, 30, 45][array_rand([0, 1, 2, 3])];

        return $date->setTime($hour, $minute);
    }

    protected function locationForBucket(string $bucketKey, Collection $villages): array
    {
        $bucket = $this->cityBuckets()[$bucketKey];
        $match = $villages->first(function ($row) use ($bucket) {
            return $row->province_name === $bucket['province_name']
                && $row->regency_name === $bucket['regency_name']
                && $row->district_name === $bucket['district_name']
                && $row->village_name === $bucket['village_name'];
        });

        if ($match) {
            return [
                'province_id' => $match->province_id,
                'regency_id' => $match->regency_id,
                'district_id' => $match->district_id,
                'village_id' => $match->village_id,
                'province_name' => $match->province_name,
                'regency_name' => $match->regency_name,
                'district_name' => $match->district_name,
                'village_name' => $match->village_name,
            ];
        }

        return [
            'province_id' => null,
            'regency_id' => null,
            'district_id' => null,
            'village_id' => null,
            'province_name' => $bucket['province_name'],
            'regency_name' => $bucket['regency_name'],
            'district_name' => $bucket['district_name'],
            'village_name' => $bucket['village_name'],
        ];
    }

    protected function bedroomsForSlot(int $slot): int
    {
        return match ($slot % 5) {
            0 => 4,
            1 => 2,
            2 => 3,
            3 => 1,
            default => 2,
        };
    }

    protected function bathroomsForSlot(int $slot): float
    {
        return match ($slot % 4) {
            0 => 2.0,
            1 => 1.0,
            2 => 2.0,
            default => 1.0,
        };
    }

    protected function rentPriceForBucket(string $bucketKey, int $propertyIndex, int $slot): int
    {
        $bucket = $this->cityBuckets()[$bucketKey];
        $base = mt_rand($bucket['rent_min'], $bucket['rent_max']);
        $pattern = [0, 50000, 100000, 150000, 200000][$propertyIndex % 5];

        return (int) (($base + $pattern) / 1000) * 1000;
    }

    protected function timeToRentDays(string $cityName, ?int $provinceId = null): int
    {
        $isJakartaBandung = in_array($cityName, ['Jakarta Pusat', 'Kota Bandung'], true);
        $isOutlier = mt_rand(1, 100) <= 5;

        if ($isOutlier) {
            return mt_rand(30, 60);
        }

        return $isJakartaBandung ? mt_rand(8, 12) : mt_rand(10, 18);
    }

    protected function userAgents(): array
    {
        return [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        ];
    }

    protected function propertyPhotoFilesByCategory(): array
    {
        $files = collect(Storage::disk('public')->files('property-master'))
            ->filter(fn (string $path) => is_string($path) && $path !== '')
            ->values();

        return [
            'front' => $files->filter(fn (string $path) => preg_match('/^property-master\/front[-_]/i', $path) === 1)->values(),
            'living_room' => $files->filter(fn (string $path) => preg_match('/^property-master\/living_room[-_]/i', $path) === 1)->values(),
            'bedroom' => $files->filter(fn (string $path) => preg_match('/^property-master\/bedroom[-_]/i', $path) === 1)->values(),
            'bathroom' => $files->filter(fn (string $path) => preg_match('/^property-master\/bathroom[-_]/i', $path) === 1)->values(),
        ];
    }

    protected function selectPropertyPhotos(array $photoByCategory, int $index): array
    {
        return [
            $photoByCategory['front']->get($index % max(1, $photoByCategory['front']->count())),
            $photoByCategory['living_room']->get($index % max(1, $photoByCategory['living_room']->count())),
            $photoByCategory['bedroom']->get($index % max(1, $photoByCategory['bedroom']->count())),
            $photoByCategory['bathroom']->get($index % max(1, $photoByCategory['bathroom']->count())),
        ];
    }
}
