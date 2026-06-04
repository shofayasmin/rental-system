<?php

namespace App\Http\Requests;

use App\Models\Facility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'agent';
    }

    protected function prepareForValidation(): void
    {
        $facilities = [];

        foreach ((array) $this->input('facilities', []) as $row) {
            if (!filter_var(data_get($row, 'selected', false), FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $facilities[] = [
                'id' => (int) data_get($row, 'id'),
                'value' => trim((string) data_get($row, 'value', '')) ?: null,
            ];
        }

        $this->merge([
            'facilities' => $facilities,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['nullable', 'string', 'max:255'],
            'rent_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:to-let,rented,maintenance'],

            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'regency_id' => ['required', 'integer', 'exists:regencies,id'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'village_id' => ['required', 'integer', 'exists:villages,id'],
            'address' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            'bedrooms' => ['required', 'integer', 'min:0'],
            'bathrooms' => ['required', 'integer', 'min:1'],
            'area' => ['required', 'numeric', 'min:1'],

            'facilities' => ['nullable', 'array'],
            'facilities.*.id' => ['required_with:facilities', 'integer', 'distinct', 'exists:facilities,id'],
            'facilities.*.value' => ['nullable', 'string', 'max:100'],

            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
        ];

        if (Schema::hasColumn('properties', 'building_area')) {
            $rules['building_area'] = ['required', 'numeric', 'min:1'];
        }
        if (Schema::hasColumn('properties', 'floors')) {
            $rules['floors'] = ['required', 'integer', 'min:1', 'max:99'];
        }
        if (Schema::hasColumn('properties', 'latitude')) {
            $rules['latitude'] = ['nullable', 'numeric', 'between:-90,90'];
        }
        if (Schema::hasColumn('properties', 'longitude')) {
            $rules['longitude'] = ['nullable', 'numeric', 'between:-180,180'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'rent_price.required' => 'Rent price is required.',
            'status.in' => 'Invalid property status.',
            'province_id.required' => 'Province is required.',
            'regency_id.required' => 'Regency is required.',
            'district_id.required' => 'District is required.',
            'village_id.required' => 'Village is required.',
            'address.required' => 'Address is required.',
            'area.min' => 'Area must be at least 1 square meter.',
            'facilities.*.id.distinct' => 'Duplicate facilities are not allowed.',
            'photos.*.image' => 'Each photo must be a valid image file.',
            'photos.*.max' => 'Each photo must not exceed 5MB.',
        ] + (
            Schema::hasColumn('properties', 'building_area')
                ? [
                    'building_area.required' => 'Building area is required.',
                    'building_area.min' => 'Building area must be at least 1 square meter.',
                ]
                : []
        ) + (
            Schema::hasColumn('properties', 'floors')
                ? [
                    'floors.required' => 'Floors is required.',
                    'floors.min' => 'Floors must be at least 1.',
                    'floors.max' => 'Floors must not exceed 99.',
                ]
                : []
        ) + (
            Schema::hasColumn('properties', 'latitude')
                ? [
                    'latitude.between' => 'Latitude must be between -90 and 90.',
                ]
                : []
        ) + (
            Schema::hasColumn('properties', 'longitude')
                ? [
                    'longitude.between' => 'Longitude must be between -180 and 180.',
                ]
                : []
        );
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateLocationHierarchy($validator);
            $this->validateStatusSpecificRules($validator);
            $this->validateFacilityValues($validator);
        });
    }

    private function validateStatusSpecificRules($validator): void
    {
        $status = $this->string('status')->toString();
        $facilities = collect((array) $this->input('facilities', []));
        $photos = (array) $this->file('photos', []);

        if ($status !== 'to-let') {
            return;
        }

        if ($facilities->isEmpty()) {
            $validator->errors()->add('facilities', 'At least one facility is required for a To Let property.');
        }

        if (count($photos) < 1) {
            $validator->errors()->add('photos', 'At least one photo is required for a To Let property.');
        }

        if ((float) $this->input('rent_price', 0) <= 0) {
            $validator->errors()->add('rent_price', 'Rent price for a To Let property must be greater than zero.');
        }
    }

    private function validateFacilityValues($validator): void
    {
        $facilities = collect((array) $this->input('facilities', []));
        if ($facilities->isEmpty()) {
            return;
        }

        $requiredValueSlugs = ['electricity', 'water_supply'];
        $slugById = Facility::query()->pluck('slug', 'id');

        foreach ($facilities as $index => $item) {
            $facilityId = (int) data_get($item, 'id');
            $value = trim((string) data_get($item, 'value', ''));
            $slug = $slugById[$facilityId] ?? null;

            if ($slug && in_array($slug, $requiredValueSlugs, true) && $value === '') {
                $validator->errors()->add("facilities.$index.value", "A value is required for {$slug}.");
            }
        }
    }

    private function validateLocationHierarchy($validator): void
    {
        $provinceId = (int) $this->input('province_id');
        $regencyId = (int) $this->input('regency_id');
        $districtId = (int) $this->input('district_id');
        $villageId = (int) $this->input('village_id');

        $regency = \App\Models\Regency::query()->find($regencyId);
        $district = \App\Models\District::query()->find($districtId);
        $village = \App\Models\Village::query()->find($villageId);

        if ($regency && (int) $regency->province_id !== $provinceId) {
            $validator->errors()->add('regency_id', 'Selected regency does not belong to the selected province.');
        }

        if ($district && (int) $district->regency_id !== $regencyId) {
            $validator->errors()->add('district_id', 'Selected district does not belong to the selected regency.');
        }

        if ($village && (int) $village->district_id !== $districtId) {
            $validator->errors()->add('village_id', 'Selected village does not belong to the selected district.');
        }
    }
}
