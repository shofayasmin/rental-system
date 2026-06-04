<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Database\Seeder;

class IndonesiaRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['province' => ['code' => 'ID-JK', 'name' => 'DKI Jakarta'], 'regency' => ['code' => 'ID-JK-JP', 'name' => 'Jakarta Pusat'], 'district' => ['code' => 'ID-JK-JP-GM', 'name' => 'Gambir'], 'village' => ['code' => 'ID-JK-JP-GM-CD', 'name' => 'Cideng']],
            ['province' => ['code' => 'ID-JB', 'name' => 'Jawa Barat'], 'regency' => ['code' => 'ID-JB-BDG', 'name' => 'Kota Bandung'], 'district' => ['code' => 'ID-JB-BDG-CB', 'name' => 'Coblong'], 'village' => ['code' => 'ID-JB-BDG-CB-DG', 'name' => 'Dago']],
            ['province' => ['code' => 'ID-JI', 'name' => 'Jawa Timur'], 'regency' => ['code' => 'ID-JI-SBY', 'name' => 'Kota Surabaya'], 'district' => ['code' => 'ID-JI-SBY-TG', 'name' => 'Tegalsari'], 'village' => ['code' => 'ID-JI-SBY-TG-KR', 'name' => 'Kedungdoro']],
            ['province' => ['code' => 'ID-SU', 'name' => 'Sumatera Utara'], 'regency' => ['code' => 'ID-SU-MDN', 'name' => 'Kota Medan'], 'district' => ['code' => 'ID-SU-MDN-MK', 'name' => 'Medan Kota'], 'village' => ['code' => 'ID-SU-MDN-MK-KP', 'name' => 'Kota Matsum I']],
            ['province' => ['code' => 'ID-SN', 'name' => 'Sulawesi Selatan'], 'regency' => ['code' => 'ID-SN-MKS', 'name' => 'Kota Makassar'], 'district' => ['code' => 'ID-SN-MKS-PA', 'name' => 'Panakkukang'], 'village' => ['code' => 'ID-SN-MKS-PA-MS', 'name' => 'Masale']],
            ['province' => ['code' => 'ID-YO', 'name' => 'DI Yogyakarta'], 'regency' => ['code' => 'ID-YO-YGY', 'name' => 'Kota Yogyakarta'], 'district' => ['code' => 'ID-YO-YGY-GD', 'name' => 'Gondokusuman'], 'village' => ['code' => 'ID-YO-YGY-GD-KL', 'name' => 'Klitren']],
            ['province' => ['code' => 'ID-JT', 'name' => 'Jawa Tengah'], 'regency' => ['code' => 'ID-JT-SMG', 'name' => 'Kota Semarang'], 'district' => ['code' => 'ID-JT-SMG-CN', 'name' => 'Candisari'], 'village' => ['code' => 'ID-JT-SMG-CN-JM', 'name' => 'Jomblang']],
            ['province' => ['code' => 'ID-BA', 'name' => 'Bali'], 'regency' => ['code' => 'ID-BA-DPS', 'name' => 'Kota Denpasar'], 'district' => ['code' => 'ID-BA-DPS-DS', 'name' => 'Denpasar Selatan'], 'village' => ['code' => 'ID-BA-DPS-DS-SN', 'name' => 'Sanur']],
        ];

        foreach ($rows as $row) {
            $province = Province::updateOrCreate(
                ['code' => $row['province']['code']],
                ['name' => $row['province']['name']]
            );

            $regency = Regency::updateOrCreate(
                ['code' => $row['regency']['code']],
                [
                    'province_id' => $province->id,
                    'name' => $row['regency']['name'],
                ]
            );

            $district = District::updateOrCreate(
                ['code' => $row['district']['code']],
                [
                    'regency_id' => $regency->id,
                    'name' => $row['district']['name'],
                ]
            );

            Village::updateOrCreate(
                ['code' => $row['village']['code']],
                [
                    'district_id' => $district->id,
                    'name' => $row['village']['name'],
                ]
            );
        }
    }
}
