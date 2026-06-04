<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\District;
use App\Models\Contract;
use App\Models\Facility;
use App\Models\Property;
use App\Models\PropertyAvailabilityCycle;
use App\Models\PropertyPhoto;
use App\Models\Province;
use App\Models\Regency;
use App\Models\RentalRequest;
use App\Models\Transaction;
use App\Models\Village;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::query()
            ->where('agent_id', Auth::id());

        if ($request->filled('q')) {
            $keyword = trim((string) $request->string('q'));
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('address', 'like', '%' . $keyword . '%')
                    ->orWhereHas('regency', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'))
                    ->orWhereHas('district', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'))
                    ->orWhereHas('village', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->integer('province_id'));
        }

        if ($request->filled('regency_id')) {
            $query->where('regency_id', $request->integer('regency_id'));
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->integer('district_id'));
        }

        if ($request->filled('village_id')) {
            $query->where('village_id', $request->integer('village_id'));
        }

        if ($request->filled('min_price')) {
            $query->where('rent_price', '>=', $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('rent_price', '<=', $request->input('max_price'));
        }

        if ($request->filled('min_area')) {
            $query->where('area', '>=', $request->input('min_area'));
        }

        if ($request->filled('max_area')) {
            $query->where('area', '<=', $request->input('max_area'));
        }

        if ($request->filled('min_bedrooms')) {
            $query->where('bedrooms', '>=', $request->integer('min_bedrooms'));
        }

        if ($request->filled('max_bedrooms')) {
            $query->where('bedrooms', '<=', $request->integer('max_bedrooms'));
        }

        if ($request->filled('min_bathrooms')) {
            $query->where('bathrooms', '>=', $request->integer('min_bathrooms'));
        }

        if ($request->filled('max_bathrooms')) {
            $query->where('bathrooms', '<=', $request->integer('max_bathrooms'));
        }

        $sort = (string) $request->string('sort');
        if (!in_array($sort, ['price_desc', 'price_asc', 'newest', 'oldest', 'area_desc', 'area_asc'], true)) {
            $sort = 'newest';
        }

        switch ($sort) {
            case 'price_desc':
                $query->orderBy('rent_price', 'desc')->orderByDesc('created_at');
                break;
            case 'price_asc':
                $query->orderBy('rent_price', 'asc')->orderByDesc('created_at');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'area_desc':
                $query->orderByRaw('COALESCE(area, 0) DESC')->orderByDesc('created_at');
                break;
            case 'area_asc':
                $query->orderByRaw('COALESCE(area, 0) ASC')->orderByDesc('created_at');
                break;
            case 'newest':
            default:
                $query->orderByDesc('created_at');
                break;
        }

        $selectedProvinceId = $request->integer('province_id');
        $selectedRegencyId = $request->integer('regency_id');
        $selectedDistrictId = $request->integer('district_id');

        $provinces = Province::query()->select('id', 'name')->orderBy('name')->get();
        $regencies = $selectedProvinceId
            ? Regency::query()->select('id', 'name')->where('province_id', $selectedProvinceId)->orderBy('name')->get()
            : collect();
        $districts = $selectedRegencyId
            ? District::query()->select('id', 'name')->where('regency_id', $selectedRegencyId)->orderBy('name')->get()
            : collect();
        $villages = $selectedDistrictId
            ? Village::query()->select('id', 'name')->where('district_id', $selectedDistrictId)->orderBy('name')->get()
            : collect();

        $properties = $query
            ->with('photos', 'province', 'regency', 'district', 'village')
            ->get();

        return view('agent.properties.index', compact('properties', 'provinces', 'regencies', 'districts', 'villages'));
    }

    public function create()
    {
        return view('agent.properties.create', $this->buildPropertyFormContext());
    }

    public function show(Property $property)
    {
        abort_if($property->agent_id !== Auth::id(), 403);

        $property->loadMissing(
            'photos',
            'agent',
            'facilityItems',
            'province',
            'regency',
            'district',
            'village'
        );

        $activeContract = Contract::query()
            ->where('status', 'active')
            ->whereHas('rentalRequest', function ($query) use ($property) {
                $query->where('property_id', $property->id);
            })
            ->with(['rentalRequest.tenant'])
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();

        return view('agent.properties.show', compact('property', 'activeContract'));
    }

    public function store(StorePropertyRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            $property = Property::create($this->buildPropertyPayload($data));
            $this->syncFacilities($property, (array) ($data['facilities'] ?? []));
            $this->syncUploadedPhotos($property, (array) $request->file('photos', []));

            if ($property->status === 'to-let') {
                $this->openAvailabilityCycleIfNeeded($property, now());
            }
        });

        return redirect('/agent/properties')->with('success', 'Property created successfully.');
    }

    public function edit(Property $property)
    {
        abort_if($property->agent_id !== Auth::id(), 403);

        $property->loadMissing('facilityItems', 'photos');

        return view('agent.properties.edit', array_merge(
            ['property' => $property],
            $this->buildPropertyFormContext($property),
        ));
    }

    public function update(UpdatePropertyRequest $request, Property $property)
    {
        abort_if($property->agent_id !== Auth::id(), 403);

        $previousStatus = $property->status;
        $data = $request->validated();

        DB::transaction(function () use ($property, $data, $previousStatus, $request) {
            $property->update($this->buildPropertyPayload($data));
            $this->syncFacilities($property, (array) ($data['facilities'] ?? []));
            $this->syncUploadedPhotos($property, (array) $request->file('photos', []));

            $this->syncAvailabilityCycleOnStatusChange(
                $property->fresh(),
                $previousStatus,
                $data['status']
            );
        });

        return redirect("/agent/properties/{$property->id}")
            ->with('success', 'Property updated successfully.');
    }

    public function destroy(Property $property)
    {
        abort_if($property->agent_id !== Auth::id(), 403);

        $hasAnyRentalRequest = RentalRequest::where('property_id', $property->id)
            ->exists();

        $hasAnyTransaction = Transaction::where('property_id', $property->id)
            ->exists();

        if ($hasAnyRentalRequest || $hasAnyTransaction) {
            return back()->with('error', 'Property cannot be deleted because it already has rental history.');
        }

        $property->delete();

        return redirect('/agent/properties')->with('success', 'Property deleted successfully.');
    }

    public function regencies(Province $province): JsonResponse
    {
        return response()->json(
            $province->regencies()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    public function districts(Regency $regency): JsonResponse
    {
        return response()->json(
            $regency->districts()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    public function villages(District $district): JsonResponse
    {
        return response()->json(
            $district->villages()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    private function syncAvailabilityCycleOnStatusChange(
        Property $property,
        string $previousStatus,
        string $newStatus
    ): void {
        if ($newStatus === 'to-let') {
            $this->openAvailabilityCycleIfNeeded($property, now());
            return;
        }

        if ($previousStatus === 'to-let' && $newStatus === 'maintenance') {
            $this->closeActiveAvailabilityCycle($property, now(), 'maintenance');
            return;
        }

        if ($previousStatus === 'to-let' && $newStatus !== 'to-let') {
            $this->closeActiveAvailabilityCycle($property, now(), 'manual');
        }
    }

    private function openAvailabilityCycleIfNeeded(Property $property, $availableFromAt): void
    {
        $activeCycleExists = PropertyAvailabilityCycle::query()
            ->where('property_id', $property->id)
            ->whereNull('unavailable_at')
            ->lockForUpdate()
            ->exists();

        if ($activeCycleExists) {
            return;
        }

        PropertyAvailabilityCycle::create([
            'property_id' => $property->id,
            'available_from_at' => $availableFromAt,
            'unavailable_at' => null,
            'closed_by' => null,
        ]);
    }

    private function closeActiveAvailabilityCycle(Property $property, $unavailableAt, string $closedBy): void
    {
        $activeCycle = PropertyAvailabilityCycle::query()
            ->where('property_id', $property->id)
            ->whereNull('unavailable_at')
            ->orderByDesc('available_from_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (!$activeCycle) {
            return;
        }

        $activeCycle->update([
            'unavailable_at' => $unavailableAt,
            'closed_by' => $closedBy,
        ]);
    }

    private function buildPropertyFormContext(?Property $property = null): array
    {
        $selectedProvinceId = old('province_id', $property?->province_id);
        $selectedRegencyId = old('regency_id', $property?->regency_id);
        $selectedDistrictId = old('district_id', $property?->district_id);

        $provinces = Province::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $regencies = $selectedProvinceId
            ? Regency::query()
                ->select('id', 'name')
                ->where('province_id', $selectedProvinceId)
                ->orderBy('name')
                ->get()
            : collect();

        $districts = $selectedRegencyId
            ? District::query()
                ->select('id', 'name')
                ->where('regency_id', $selectedRegencyId)
                ->orderBy('name')
                ->get()
            : collect();

        $villages = $selectedDistrictId
            ? Village::query()
                ->select('id', 'name')
                ->where('district_id', $selectedDistrictId)
                ->orderBy('name')
                ->get()
            : collect();

        $facilities = Facility::query()
            ->where('is_active', true)
            ->select('id', 'name', 'slug', 'category')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $selectedFacilities = old('facilities');
        if ($selectedFacilities) {
            $selectedFacilities = collect($selectedFacilities)
                ->mapWithKeys(function ($row) {
                    $id = (int) data_get($row, 'id');
                    if ($id <= 0) {
                        return [];
                    }

                    return [
                        $id => [
                            'id' => $id,
                            'selected' => true,
                            'value' => data_get($row, 'value'),
                        ],
                    ];
                })
                ->all();
        } elseif ($property) {
            $selectedFacilities = $property->facilityItems
                ->mapWithKeys(function ($facility) {
                    return [
                        $facility->id => [
                            'id' => $facility->id,
                            'selected' => true,
                            'value' => $facility->pivot->value,
                        ],
                    ];
                })
                ->all();
        }

        return [
            'provinces' => $provinces,
            'regencies' => $regencies,
            'districts' => $districts,
            'villages' => $villages,
            'facilitiesByCategory' => $facilities,
            'selectedFacilities' => $selectedFacilities ?? [],
            'requiredValueFacilitySlugs' => ['electricity', 'water_supply'],
            'facilityValuePlaceholders' => [
                'electricity' => 'e.g. 2200 VA',
                'water_supply' => 'e.g. PDAM / Well water / Bore well / Tanker',
                'wifi' => 'e.g. 50 Mbps (optional)',
            ],
            'hasBuildingAreaColumn' => Schema::hasColumn('properties', 'building_area'),
            'hasFloorsColumn' => Schema::hasColumn('properties', 'floors'),
            'hasCoordinatesColumns' => Schema::hasColumn('properties', 'latitude') && Schema::hasColumn('properties', 'longitude'),
        ];
    }

    private function buildPropertyPayload(array $data): array
    {
        $regencyName = (string) Regency::query()->whereKey($data['regency_id'])->value('name');
        $villageName = (string) Village::query()->whereKey($data['village_id'])->value('name');
        $sequence = $this->nextTitleSequence((int) $data['regency_id'], (int) $data['village_id']);

        $normalizedTitle = trim((string) ($data['title'] ?? ''));
        if ($normalizedTitle === '') {
            $normalizedTitle = $this->buildDefaultTitle(
                propertyType: 'House',
                bedrooms: (int) $data['bedrooms'],
                regency: $regencyName,
                village: $villageName,
                sequence: $sequence,
            );
        }

        $facilityIds = collect((array) ($data['facilities'] ?? []))
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $facilityNames = $facilityIds->isNotEmpty()
            ? Facility::query()->whereIn('id', $facilityIds)->pluck('name')->values()->all()
            : [];

        return [
            'agent_id' => Auth::id(),
            'title' => $normalizedTitle,
            'province_id' => $data['province_id'],
            'regency_id' => $data['regency_id'],
            'district_id' => $data['district_id'],
            'village_id' => $data['village_id'],
            'address' => $data['address'],
            'bedrooms' => $data['bedrooms'],
            'bathrooms' => $data['bathrooms'],
            'area' => $data['area'],
            'facilities' => empty($facilityNames) ? null : json_encode($facilityNames),
            'rent_price' => $data['rent_price'],
            'status' => $data['status'],
        ] + (
            Schema::hasColumn('properties', 'building_area')
                ? ['building_area' => $data['building_area'] ?? $data['area']]
                : []
        ) + (
            Schema::hasColumn('properties', 'description')
                ? ['description' => trim((string) ($data['description'] ?? '')) ?: null]
                : []
        ) + (
            Schema::hasColumn('properties', 'floors')
                ? ['floors' => (int) $data['floors']]
                : []
        ) + (
            Schema::hasColumn('properties', 'latitude')
                ? ['latitude' => isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null]
                : []
        ) + (
            Schema::hasColumn('properties', 'longitude')
                ? ['longitude' => isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null]
                : []
        );
    }

    private function buildDefaultTitle(
        string $propertyType,
        int $bedrooms,
        string $regency,
        string $village,
        int $sequence
    ): string {
        $safeRegency = $regency !== '' ? $regency : 'Unknown Regency';
        $safeVillage = $village !== '' ? $village : 'Unknown Village';

        return sprintf(
            '%s %dBR in %s - %s #%d',
            $propertyType,
            $bedrooms,
            $safeRegency,
            $safeVillage,
            $sequence
        );
    }

    private function nextTitleSequence(int $regencyId, int $villageId): int
    {
        $count = Property::query()
            ->where('regency_id', $regencyId)
            ->where('village_id', $villageId)
            ->count();

        return $count + 1;
    }

    private function syncFacilities(Property $property, array $facilities): void
    {
        $syncData = collect($facilities)
            ->filter(fn ($facility) => !empty($facility['id']))
            ->mapWithKeys(function ($facility) {
                return [
                    (int) $facility['id'] => [
                        'value' => isset($facility['value']) && trim((string) $facility['value']) !== ''
                            ? trim((string) $facility['value'])
                            : null,
                    ],
                ];
            })
            ->all();

        $property->facilityItems()->sync($syncData);
    }

    private function syncUploadedPhotos(Property $property, array $photos): void
    {
        foreach ($photos as $photoFile) {
            $path = $photoFile->store('properties', 'public');

            PropertyPhoto::create([
                'property_id' => $property->id,
                'path' => $path,
            ]);
        }
    }
}
