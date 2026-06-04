@extends('layouts.app')

@section('content')
@if($hasCoordinatesColumns)
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="">
    <style>
        #coordinate-picker-map {
            width: 100%;
            height: 300px;
            border: 1px solid #dbe3ee;
            border-radius: 12px;
        }
    </style>
@endif
<div class="container">
    <h2 class="mb-4">Create Property</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following issues:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/agent/properties" enctype="multipart/form-data" autocomplete="off">
        @csrf

        <div class="card mb-4">
            <div class="card-header fw-semibold">Basic Information</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Title (Optional)</label>
                    <input type="text"
                           name="title"
                           value="{{ old('title') }}"
                           class="form-control"
                           placeholder="Auto: House {bedrooms}BR in {regency} - {village} #{sequence}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <textarea name="description"
                              rows="2"
                              class="form-control"
                              placeholder="Write a short marketing description for this property.">{{ old('description') }}</textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rent Price</label>
                    <input type="number" step="0.01" min="0" name="rent_price" value="{{ old('rent_price') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach(['to-let' => 'To Let', 'rented' => 'Rented', 'maintenance' => 'Maintenance'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', 'to-let') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header fw-semibold">Location</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Province</label>
                    <select id="province_id" name="province_id" class="form-select" required>
                        <option value="">Select province</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}" {{ (string) old('province_id') === (string) $province->id ? 'selected' : '' }}>
                                {{ $province->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Regency</label>
                    <select id="regency_id" name="regency_id" class="form-select" required>
                        <option value="">Select regency</option>
                        @foreach($regencies as $regency)
                            <option value="{{ $regency->id }}" {{ (string) old('regency_id') === (string) $regency->id ? 'selected' : '' }}>
                                {{ $regency->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">District</label>
                    <select id="district_id" name="district_id" class="form-select" required>
                        <option value="">Select district</option>
                        @foreach($districts as $district)
                            <option value="{{ $district->id }}" {{ (string) old('district_id') === (string) $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Village</label>
                    <select id="village_id" name="village_id" class="form-select" required>
                        <option value="">Select village</option>
                        @foreach($villages as $village)
                            <option value="{{ $village->id }}" {{ (string) old('village_id') === (string) $village->id ? 'selected' : '' }}>
                                {{ $village->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="form-control" required>
                </div>
                @if($hasCoordinatesColumns)
                    <div class="col-md-6">
                        <label class="form-label">Latitude (Optional)</label>
                        <input type="number"
                               step="0.0000001"
                               min="-90"
                               max="90"
                               name="latitude"
                               value="{{ old('latitude') }}"
                               class="form-control"
                               placeholder="e.g. -6.1753924">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Longitude (Optional)</label>
                        <input type="number"
                               step="0.0000001"
                               min="-180"
                               max="180"
                               name="longitude"
                               value="{{ old('longitude') }}"
                               class="form-control"
                               placeholder="e.g. 106.8271528">
                    </div>
                    <div class="col-12">
                        <small class="text-muted">Use decimal coordinates so tenant can see zoomable map in property detail.</small>
                    </div>
                    <div class="col-12">
                        <div id="coordinate-picker-map" aria-label="Location picker map"></div>
                        <small class="text-muted d-block mt-2">
                            Click on the map to set property pin. You can also drag the pin to adjust exact location.
                        </small>
                    </div>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header fw-semibold">Property Specs</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Bedrooms</label>
                    <input type="number" min="0" name="bedrooms" value="{{ old('bedrooms', 1) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bathrooms</label>
                    <input type="number" min="1" step="1" name="bathrooms" value="{{ old('bathrooms', 1) }}" class="form-control" required>
                </div>
                @if($hasFloorsColumn)
                    <div class="col-md-3">
                        <label class="form-label">Floors</label>
                        <input type="number" min="1" max="99" name="floors" value="{{ old('floors') }}" class="form-control" placeholder="e.g. 1" required>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Land Area (m²)</label>
                    <input type="number" min="1" name="area" value="{{ old('area') }}" class="form-control" required>
                </div>
                @if($hasBuildingAreaColumn)
                    <div class="col-md-3">
                        <label class="form-label">Building Area (m²)</label>
                        <input type="number" min="1" name="building_area" value="{{ old('building_area') }}" class="form-control" required>
                    </div>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header fw-semibold">Facilities</div>
            <div class="card-body">
                @php
                    $categoryLabels = [
                        'utilities' => 'Utilities',
                        'interior' => 'Interior',
                        'outdoor' => 'Outdoor',
                    ];
                @endphp

                @foreach($categoryLabels as $categoryKey => $categoryLabel)
                    <h6 class="mt-2 mb-3">{{ $categoryLabel }}</h6>
                    <div class="row g-3 mb-3">
                        @foreach(($facilitiesByCategory[$categoryKey] ?? collect()) as $facility)
                            @php
                                $facilityState = data_get($selectedFacilities, $facility->id, []);
                                $isChecked = filter_var(data_get($facilityState, 'selected', false), FILTER_VALIDATE_BOOLEAN);
                                $value = data_get($facilityState, 'value');
                                $requiresValue = in_array($facility->slug, $requiredValueFacilitySlugs, true);
                                $placeholder = data_get($facilityValuePlaceholders, $facility->slug, 'Optional details');
                            @endphp
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input facility-checkbox"
                                               type="checkbox"
                                               id="facility_{{ $facility->id }}"
                                               name="facilities[{{ $facility->id }}][selected]"
                                               value="1"
                                               {{ $isChecked ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="facility_{{ $facility->id }}">
                                            {{ $facility->name }}
                                        </label>
                                    </div>
                                    <input type="hidden" name="facilities[{{ $facility->id }}][id]" value="{{ $facility->id }}">
                                    <label class="form-label mb-1 small text-muted">
                                        Value {{ $requiresValue ? '(Required when selected)' : '(Optional)' }}
                                    </label>
                                    <input type="text"
                                           class="form-control form-control-sm facility-value"
                                           name="facilities[{{ $facility->id }}][value]"
                                           value="{{ $value }}"
                                           placeholder="{{ $placeholder }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header fw-semibold">Initial Photos</div>
            <div class="card-body">
                <label class="form-label">Upload Photos</label>
                <input type="file" name="photos[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">For To Let status, at least one photo is required. Maximum 5MB per image.</small>
            </div>
        </div>

        <button class="btn btn-primary">Save Property</button>
        <a href="/agent/properties" class="btn btn-secondary">Cancel</a>
    </form>
</div>

@if($hasCoordinatesColumns)
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
@endif
<script>
    async function populateSelect(url, targetId, placeholder) {
        const target = document.getElementById(targetId);
        target.innerHTML = `<option value="">${placeholder}</option>`;

        if (!url) {
            return;
        }

        const response = await fetch(url);
        const items = await response.json();

        for (const item of items) {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            target.appendChild(option);
        }
    }

    document.getElementById('province_id').addEventListener('change', async function () {
        const provinceId = this.value;
        await populateSelect(
            provinceId ? `/agent/locations/regencies/${provinceId}` : null,
            'regency_id',
            'Select regency'
        );
        await populateSelect(null, 'district_id', 'Select district');
        await populateSelect(null, 'village_id', 'Select village');
    });

    document.getElementById('regency_id').addEventListener('change', async function () {
        const regencyId = this.value;
        await populateSelect(
            regencyId ? `/agent/locations/districts/${regencyId}` : null,
            'district_id',
            'Select district'
        );
        await populateSelect(null, 'village_id', 'Select village');
    });

    document.getElementById('district_id').addEventListener('change', async function () {
        const districtId = this.value;
        await populateSelect(
            districtId ? `/agent/locations/villages/${districtId}` : null,
            'village_id',
            'Select village'
        );
    });

    function initializeCoordinatePickerMap() {
        if (typeof L === 'undefined') {
            return;
        }

        const mapElement = document.getElementById('coordinate-picker-map');
        const latInput = document.querySelector('input[name="latitude"]');
        const lngInput = document.querySelector('input[name="longitude"]');

        if (!mapElement || !latInput || !lngInput) {
            return;
        }

        const defaultCenter = [-2.5489, 118.0149];
        const initialLat = parseFloat(latInput.value);
        const initialLng = parseFloat(lngInput.value);
        const hasInitialCoordinates = !Number.isNaN(initialLat) && !Number.isNaN(initialLng);
        const center = hasInitialCoordinates ? [initialLat, initialLng] : defaultCenter;

        const map = L.map('coordinate-picker-map').setView(center, hasInitialCoordinates ? 15 : 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let marker = null;

        const setCoordinateValues = (lat, lng) => {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
        };

        const attachDragHandler = (targetMarker) => {
            targetMarker.on('dragend', (event) => {
                const point = event.target.getLatLng();
                setCoordinateValues(point.lat, point.lng);
            });
        };

        const setMarker = (lat, lng, moveMap = true) => {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                attachDragHandler(marker);
            }

            setCoordinateValues(lat, lng);
            if (moveMap) {
                map.panTo([lat, lng]);
            }
        };

        if (hasInitialCoordinates) {
            setMarker(initialLat, initialLng, false);
        }

        map.on('click', (event) => {
            setMarker(event.latlng.lat, event.latlng.lng);
        });

        const syncFromInputs = () => {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);

            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }

            setMarker(lat, lng);
            if (map.getZoom() < 14) {
                map.setZoom(14);
            }
        };

        latInput.addEventListener('change', syncFromInputs);
        lngInput.addEventListener('change', syncFromInputs);
        setTimeout(() => map.invalidateSize(), 0);
    }

    function syncFacilityInputs() {
        document.querySelectorAll('.facility-checkbox').forEach((checkbox) => {
            const valueInput = checkbox.closest('.border').querySelector('.facility-value');
            valueInput.disabled = !checkbox.checked;
        });
    }

    document.querySelectorAll('.facility-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', syncFacilityInputs);
    });
    syncFacilityInputs();
    initializeCoordinatePickerMap();
</script>
@endsection
