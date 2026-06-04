@extends('layouts.app')

@section('content')
@php
    $search = request('q');
    $selectedStatus = request('status');
    $selectedSort = request('sort', 'newest');
    $selectedProvinceId = request('province_id');
    $selectedRegencyId = request('regency_id');
    $selectedDistrictId = request('district_id');
    $selectedVillageId = request('village_id');
    $selectedMinPrice = request('min_price');
    $selectedMaxPrice = request('max_price');
    $selectedMinArea = request('min_area');
    $selectedMaxArea = request('max_area');
    $selectedMinBedrooms = request('min_bedrooms');
    $selectedMaxBedrooms = request('max_bedrooms');
    $selectedMinBathrooms = request('min_bathrooms');
    $selectedMaxBathrooms = request('max_bathrooms');
    $sortOptions = [
        'newest' => 'Newest',
        'oldest' => 'Oldest',
        'price_desc' => 'Price: High to Low',
        'price_asc' => 'Price: Low to High',
        'area_desc' => 'Area: Largest',
        'area_asc' => 'Area: Smallest',
    ];
    $queryUrl = function (array $overrides = []) {
        $query = request()->query();
        unset($query['page']);
        unset($query['status']);

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }

        return route('houses.index') . ($query ? '?' . http_build_query($query) : '');
    };
    $filterChips = [];
    $addChip = function (string $label, string $key, ?string $value = null) use (&$filterChips, $queryUrl) {
        if ($value === null || $value === '') {
            return;
        }

        $filterChips[] = [
            'label' => $label . ': ' . $value,
            'url' => $queryUrl([$key => null]),
        ];
    };

    $addChip('Search', 'q', $search);
    if ($selectedSort !== 'newest') {
        $filterChips[] = [
            'label' => 'Sort: ' . ($sortOptions[$selectedSort] ?? $selectedSort),
            'url' => $queryUrl(['sort' => 'newest']),
        ];
    }
    $addChip('Province', 'province_id', optional($provinces->firstWhere('id', (int) $selectedProvinceId))->name);
    $addChip('Regency', 'regency_id', optional($regencies->firstWhere('id', (int) $selectedRegencyId))->name);
    $addChip('District', 'district_id', optional($districts->firstWhere('id', (int) $selectedDistrictId))->name);
    $addChip('Village', 'village_id', optional($villages->firstWhere('id', (int) $selectedVillageId))->name);
    $addChip('Min Price', 'min_price', $selectedMinPrice);
    $addChip('Max Price', 'max_price', $selectedMaxPrice);
    $addChip('Min Area', 'min_area', $selectedMinArea);
    $addChip('Max Area', 'max_area', $selectedMaxArea);
    $addChip('Min Bedrooms', 'min_bedrooms', $selectedMinBedrooms);
    $addChip('Max Bedrooms', 'max_bedrooms', $selectedMaxBedrooms);
    $addChip('Min Bathrooms', 'min_bathrooms', $selectedMinBathrooms);
    $addChip('Max Bathrooms', 'max_bathrooms', $selectedMaxBathrooms);
@endphp

<style>
    .property-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: nowrap;
        margin-bottom: .75rem;
    }
    .property-toolbar-controls {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: nowrap;
        justify-content: flex-end;
        margin-left: auto;
    }
    .property-toolbar-sort {
        min-width: 214px;
        border-color: #0d6efd;
        color: #0d6efd;
        font-weight: 600;
        border-radius: 999px;
    }
    .property-toolbar-search {
        min-width: 295px;
        width: 295px;
        position: relative;
    }
    .property-toolbar-search-input {
        padding-left: 2.4rem;
        padding-right: 1rem;
        position: relative;
        z-index: 1;
        background: #fff;
        border-radius: 999px;
    }
    .property-toolbar-search-overlay {
        position: absolute;
        left: 2.4rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
        z-index: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: calc(100% - 3.5rem);
        font-size: .95rem;
    }
    .property-toolbar-search-icon {
        position: absolute;
        left: .9rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.05rem;
        height: 1.05rem;
        pointer-events: none;
        z-index: 2;
        opacity: .72;
    }
    .property-toolbar-search.has-value .property-toolbar-search-overlay,
    .property-toolbar-search:focus-within .property-toolbar-search-overlay {
        opacity: 0;
        transform: translateY(-50%) scale(.98);
    }
    .property-filter-toggle {
        white-space: nowrap;
        border-color: #0d6efd;
        color: #0d6efd;
        font-weight: 600;
        border-radius: 999px;
        min-width: 128px;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding-inline: 1rem;
    }
    .property-toolbar-action-icon {
        width: 1rem;
        height: 1rem;
        flex: 0 0 auto;
    }
    .property-toolbar-add {
        white-space: nowrap;
        padding-inline: 1rem;
        border-radius: 999px;
        min-width: 128px;
    }
    .property-filter-panel {
        border: 1px solid #dee2e6;
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
    }
    .property-filter-heading {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: .85rem 1rem;
        font-weight: 700;
        font-size: 1.05rem;
    }
    .property-filter-body {
        padding: 1rem;
    }
    .property-filter-group {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 1rem;
        height: 100%;
        background: #fff;
    }
    .property-filter-group-title {
        font-weight: 700;
        margin-bottom: 1rem;
    }
    .range-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        gap: .75rem;
        align-items: center;
    }
    .active-filter-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1rem;
    }
    .active-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 999px;
        padding: .35rem .7rem;
        font-size: .9rem;
        font-weight: 600;
        text-decoration: none;
    }
    .active-filter-chip:hover {
        background: #dbeafe;
        color: #1e40af;
    }
    .active-filter-chip .chip-close {
        font-size: 1rem;
        line-height: 1;
    }
    .clear-filter-chip {
        border-color: #dee2e6;
        background: #fff;
        color: #6c757d;
    }
    @media (max-width: 768px) {
        .property-toolbar {
            flex-wrap: wrap;
        }
        .property-toolbar-controls {
            flex-wrap: wrap;
            width: 100%;
        }
        .property-toolbar-sort,
        .property-toolbar-search {
            width: 100%;
            min-width: 0;
        }
        .property-filter-toggle,
        .property-toolbar-add {
            width: 100%;
        }
    }
</style>

<div class="container">
    <div class="property-toolbar">
        <h2 class="mb-0">Properties</h2>
        <div class="property-toolbar-controls">
            <form method="GET" action="{{ route('houses.index') }}" class="property-toolbar-search">
                @foreach(request()->except(['q', 'page', 'status']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <img src="{{ asset('icons/search-icon.svg') }}"
                     alt=""
                     aria-hidden="true"
                     class="property-toolbar-search-icon">
                <span class="property-toolbar-search-overlay" aria-hidden="true">Search...</span>
                <input type="text"
                       name="q"
                       class="form-control property-toolbar-search-input"
                       value="{{ $search }}"
                       placeholder=" "
                       aria-label="Search properties">
            </form>

            <div class="dropdown">
                <button class="btn btn-outline-primary property-filter-toggle property-toolbar-sort dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                    <img src="{{ asset('icons/sort-icon.svg') }}" alt="" aria-hidden="true" class="property-toolbar-action-icon">
                    Sort : {{ $sortOptions[$selectedSort] ?? 'Newest' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach($sortOptions as $sortValue => $sortLabel)
                        <li>
                            <button class="dropdown-item {{ $selectedSort === $sortValue ? 'active' : '' }}"
                                    type="button"
                                    data-sort-value="{{ $sortValue }}">
                                {{ $sortLabel }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <button class="btn btn-outline-secondary property-filter-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#propertyFilterPanel"
                    aria-expanded="false"
                    aria-controls="propertyFilterPanel">
                <img src="{{ asset('icons/filter-icon.svg') }}" alt="" aria-hidden="true" class="property-toolbar-action-icon">
                Filters
            </button>
        </div>
    </div>

    @if(count($filterChips) > 0)
        <div class="active-filter-chips">
            @foreach($filterChips as $chip)
                <a href="{{ $chip['url'] }}" class="active-filter-chip">
                    <span>{{ $chip['label'] }}</span>
                    <span class="chip-close" aria-hidden="true">&times;</span>
                </a>
            @endforeach
            @if(count($filterChips) > 1)
                <a href="{{ route('houses.index') }}" class="active-filter-chip clear-filter-chip">Clear all</a>
            @endif
        </div>
    @endif

    <div class="collapse mb-4" id="propertyFilterPanel" data-has-active-filters="{{ request()->hasAny(['province_id', 'regency_id', 'district_id', 'village_id', 'min_price', 'max_price', 'min_area', 'max_area', 'min_bedrooms', 'max_bedrooms', 'min_bathrooms', 'max_bathrooms']) ? '1' : '0' }}">
        <div class="property-filter-panel">
            <div class="property-filter-heading">Filter Properties</div>
            <form id="tenant-property-filter-form" method="GET" action="{{ route('houses.index') }}">
                <input type="hidden" name="q" value="{{ $search }}">
                <div class="property-filter-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <div class="property-filter-group">
                                <div class="property-filter-group-title">Location</div>
                                <label class="form-label">Province</label>
                                <select id="province_id" name="province_id" class="form-select mb-2">
                                    <option value="">All provinces</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->id }}" @selected((string) $selectedProvinceId === (string) $province->id)>{{ $province->name }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">Regency / City</label>
                                <select id="regency_id" name="regency_id" class="form-select mb-2">
                                    <option value="">All regencies / cities</option>
                                    @foreach($regencies as $regency)
                                        <option value="{{ $regency->id }}" @selected((string) $selectedRegencyId === (string) $regency->id)>{{ $regency->name }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">District</label>
                                <select id="district_id" name="district_id" class="form-select mb-2">
                                    <option value="">All districts</option>
                                    @foreach($districts as $district)
                                        <option value="{{ $district->id }}" @selected((string) $selectedDistrictId === (string) $district->id)>{{ $district->name }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label">Village</label>
                                <select id="village_id" name="village_id" class="form-select">
                                    <option value="">All villages</option>
                                    @foreach($villages as $village)
                                        <option value="{{ $village->id }}" @selected((string) $selectedVillageId === (string) $village->id)>{{ $village->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="property-filter-group">
                                <div class="property-filter-group-title">Price</div>
                                <label class="form-label">Price</label>
                                <div class="range-row">
                                    <input type="number" name="min_price" class="form-control" value="{{ $selectedMinPrice }}" min="0" placeholder="Min">
                                    <span>&mdash;</span>
                                    <input type="number" name="max_price" class="form-control" value="{{ $selectedMaxPrice }}" min="0" placeholder="Max">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="property-filter-group">
                                <div class="property-filter-group-title">Area &amp; Rooms</div>
                                <label class="form-label">Land Area (m²)</label>
                                <div class="range-row mb-2">
                                    <input type="number" name="min_area" class="form-control" value="{{ $selectedMinArea }}" min="0" placeholder="Min">
                                    <span>&mdash;</span>
                                    <input type="number" name="max_area" class="form-control" value="{{ $selectedMaxArea }}" min="0" placeholder="Max">
                                </div>
                                <label class="form-label">Bedrooms</label>
                                <div class="range-row mb-2">
                                    <input type="number" name="min_bedrooms" class="form-control" value="{{ $selectedMinBedrooms }}" min="0" placeholder="Min">
                                    <span>&mdash;</span>
                                    <input type="number" name="max_bedrooms" class="form-control" value="{{ $selectedMaxBedrooms }}" min="0" placeholder="Max">
                                </div>
                                <label class="form-label">Bathrooms</label>
                                <div class="range-row">
                                    <input type="number" name="min_bathrooms" class="form-control" value="{{ $selectedMinBathrooms }}" min="0" placeholder="Min">
                                    <span>&mdash;</span>
                                    <input type="number" name="max_bathrooms" class="form-control" value="{{ $selectedMaxBathrooms }}" min="0" placeholder="Max">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="{{ route('houses.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php
        $availableProperties = $properties->where('status', 'to-let')->values();
        $unavailableProperties = $properties->whereIn('status', ['maintenance', 'rented'])->values();
        $isTenant = auth()->check() && auth()->user()->role === 'tenant';
    @endphp

    @if($availableProperties->isEmpty() && $unavailableProperties->isEmpty())
        <div class="alert alert-secondary">No properties found.</div>
    @endif

    @if($availableProperties->isNotEmpty())
        <div class="row">
            @foreach($availableProperties as $property)
                @php
                    $chatUrl = $isTenant ? '/messages/property/' . $property->id : '/login';
                    $coverPhoto = $property->photos->first(function ($photo) {
                        return preg_match('/^front[_-]/i', basename($photo->path)) === 1;
                    }) ?? $property->photos->first();
                @endphp
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm property-card-clickable"
                         data-href="{{ route('houses.show', $property) }}"
                         role="link"
                         tabindex="0"
                         style="cursor: pointer;">
                        <img
                            src="{{ ($coverPhoto && \Illuminate\Support\Facades\Storage::disk('public')->exists($coverPhoto->path))
                                    ? asset('storage/'.$coverPhoto->path)
                                    : asset('images/property-placeholder.svg') }}"
                            class="card-img-top"
                            alt="Property Photo"
                            style="height: 220px; object-fit: cover;"
                        >

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $property->title }}</h5>

                            <p class="mb-2">
                                <span class="badge bg-success">TO LET</span>
                            </p>

                            <p class="text-muted mb-1">
                                {{ $property->province?->name ?? '-' }} /
                                {{ $property->regency?->name ?? '-' }} /
                                {{ $property->district?->name ?? '-' }}
                            </p>

                            <p class="mb-2 fs-5 fw-bold text-primary">
                                Rp {{ number_format((float) $property->rent_price, 0, ',', '.') }}/month
                            </p>
                            <div class="d-flex flex-wrap gap-3 mb-3 text-body-secondary">
                                <span class="d-inline-flex align-items-center gap-1">
                                    <img src="{{ asset('icons/bedroom-icon.svg') }}"
                                         alt=""
                                         width="16"
                                         height="16"
                                         onerror="this.style.display='none'">
                                    {{ $property->bedrooms }}
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <img src="{{ asset('icons/bathroom-icon.svg') }}"
                                         alt=""
                                         width="16"
                                         height="16"
                                         onerror="this.style.display='none'">
                                    {{ (int) $property->bathrooms }}
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <img src="{{ asset('icons/area-icon.svg') }}"
                                         alt=""
                                         width="16"
                                         height="16"
                                         onerror="this.style.display='none'">
                                    LA: {{ $property->area }} m²
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <img src="{{ asset('icons/area-icon.svg') }}"
                                         alt=""
                                         width="16"
                                         height="16"
                                         onerror="this.style.display='none'">
                                    BA: {{ $property->building_area ?? $property->area }} m²
                                </span>
                            </div>

                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center gap-2">
                                <div class="text-truncate">
                                    <small class="text-muted d-block">Agent</small>
                                    <span class="fw-semibold d-inline-flex align-items-center gap-1">
                                        <img src="{{ asset('icons/profile-icon.svg') }}"
                                             alt=""
                                             width="14"
                                             height="14"
                                             onerror="this.style.display='none'">
                                        {{ $property->agent?->name ?? '-' }}
                                    </span>
                                </div>
                                <a href="{{ $chatUrl }}"
                                   class="btn btn-sm btn-outline-success chat-agent-btn"
                                   data-stop-card-click="true">
                                    Chat Agent
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($unavailableProperties->isNotEmpty())
        <div class="d-flex align-items-center justify-content-between mt-2 mb-3 pt-2 border-top">
            <h5 class="mb-0">Unavailable Properties</h5>
            <small class="text-muted">Rented &amp; Maintenance</small>
        </div>
        <div class="row">
            @foreach($unavailableProperties as $property)
                @php
                    $chatUrl = $isTenant ? '/messages/property/' . $property->id : '/login';
                    $coverPhoto = $property->photos->first(function ($photo) {
                        return preg_match('/^front[_-]/i', basename($photo->path)) === 1;
                    }) ?? $property->photos->first();
                @endphp
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm bg-light text-muted property-card-clickable"
                         data-href="{{ route('houses.show', $property) }}"
                         role="link"
                         tabindex="0"
                         style="cursor: pointer;">
                        <img
                            src="{{ ($coverPhoto && \Illuminate\Support\Facades\Storage::disk('public')->exists($coverPhoto->path))
                                    ? asset('storage/'.$coverPhoto->path)
                                    : asset('images/property-placeholder.svg') }}"
                            class="card-img-top"
                            alt="Property Photo"
                            style="height: 220px; object-fit: cover; filter: grayscale(100%);"
                        >

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $property->title }}</h5>

                            <p class="mb-2">
                                @if($property->status === 'maintenance')
                                    <span class="badge bg-secondary">MAINTENANCE</span>
                                @elseif($property->status === 'rented')
                                    <span class="badge bg-dark">RENTED</span>
                                @else
                                    <span class="badge bg-light text-dark">{{ strtoupper($property->status) }}</span>
                                @endif
                            </p>

                            <p class="text-muted mb-1">
                                {{ $property->province?->name ?? '-' }} /
                                {{ $property->regency?->name ?? '-' }} /
                                {{ $property->district?->name ?? '-' }}
                            </p>

                            <p class="mb-2 fs-5 fw-bold text-primary">
                                Rp {{ number_format((float) $property->rent_price, 0, ',', '.') }}/month
                            </p>
                            <div class="d-flex flex-wrap gap-3 mb-3 text-body-secondary">
                                <span class="d-inline-flex align-items-center gap-1">
                                    <img src="{{ asset('icons/bedroom-icon.svg') }}"
                                         alt=""
                                         width="16"
                                         height="16"
                                         onerror="this.style.display='none'">
                                    {{ $property->bedrooms }}
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <img src="{{ asset('icons/bathroom-icon.svg') }}"
                                         alt=""
                                         width="16"
                                         height="16"
                                         onerror="this.style.display='none'">
                                    {{ (int) $property->bathrooms }}
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <img src="{{ asset('icons/area-icon.svg') }}"
                                         alt=""
                                         width="16"
                                         height="16"
                                         onerror="this.style.display='none'">
                                    LA: {{ $property->area }} m²
                                </span>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <img src="{{ asset('icons/area-icon.svg') }}"
                                         alt=""
                                         width="16"
                                         height="16"
                                         onerror="this.style.display='none'">
                                    BA: {{ $property->building_area ?? $property->area }} m²
                                </span>
                            </div>

                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center gap-2">
                                <div class="text-truncate">
                                    <small class="text-muted d-block">Agent</small>
                                    <span class="fw-semibold d-inline-flex align-items-center gap-1">
                                        <img src="{{ asset('icons/profile-icon.svg') }}"
                                             alt=""
                                             width="14"
                                             height="14"
                                             onerror="this.style.display='none'">
                                        {{ $property->agent?->name ?? '-' }}
                                    </span>
                                </div>
                                <a href="{{ $chatUrl }}"
                                   class="btn btn-sm btn-outline-success chat-agent-btn"
                                   data-stop-card-click="true">
                                    Chat Agent
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    async function populateSelect(url, targetId, placeholder) {
        const target = document.getElementById(targetId);
        if (!target) {
            return;
        }
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

    const provinceSelect = document.getElementById('province_id');
    const regencySelect = document.getElementById('regency_id');
    const districtSelect = document.getElementById('district_id');

    if (provinceSelect) {
        provinceSelect.addEventListener('change', async function () {
            const provinceId = this.value;
            await populateSelect(
                provinceId ? `/locations/regencies/${provinceId}` : null,
                'regency_id',
                'All regencies / cities'
            );
            await populateSelect(null, 'district_id', 'All districts');
            await populateSelect(null, 'village_id', 'All villages');
        });
    }

    if (regencySelect) {
        regencySelect.addEventListener('change', async function () {
            const regencyId = this.value;
            await populateSelect(
                regencyId ? `/locations/districts/${regencyId}` : null,
                'district_id',
                'All districts'
            );
            await populateSelect(null, 'village_id', 'All villages');
        });
    }

    if (districtSelect) {
        districtSelect.addEventListener('change', async function () {
            const districtId = this.value;
            await populateSelect(
                districtId ? `/locations/villages/${districtId}` : null,
                'village_id',
                'All villages'
            );
        });
    }

    document.querySelectorAll('.property-card-clickable').forEach((card) => {
        const href = card.dataset.href;
        if (!href) {
            return;
        }

        card.addEventListener('click', function (event) {
            if (event.target.closest('[data-stop-card-click="true"]')) {
                return;
            }
            window.location.href = href;
        });

        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                window.location.href = href;
            }
        });
    });

    const filterPanel = document.getElementById('propertyFilterPanel');
    const filterForm = document.getElementById('tenant-property-filter-form');
    const savedFilterPanelState = localStorage.getItem('tenantPropertyFilterPanelState');
    let filterPanelCollapse = null;

    if (filterPanel) {
        const hasActiveFilters = filterPanel.dataset.hasActiveFilters === '1';
        filterPanelCollapse = bootstrap.Collapse.getOrCreateInstance(filterPanel, { toggle: false });

        if (savedFilterPanelState === 'open') {
            filterPanelCollapse.show();
        } else if (savedFilterPanelState === 'closed') {
            filterPanelCollapse.hide();
        } else if (hasActiveFilters) {
            filterPanelCollapse.show();
        } else {
            filterPanelCollapse.hide();
        }

        filterPanel.addEventListener('shown.bs.collapse', () => {
            localStorage.setItem('tenantPropertyFilterPanelState', 'open');
        });
        filterPanel.addEventListener('hidden.bs.collapse', () => {
            localStorage.setItem('tenantPropertyFilterPanelState', 'closed');
        });
    }

    if (filterForm) {
        filterForm.addEventListener('submit', () => {
            localStorage.setItem('tenantPropertyFilterPanelState', 'closed');
        });
    }

    const sortDropdownItems = document.querySelectorAll('[data-sort-value]');
    if (sortDropdownItems.length > 0) {
        sortDropdownItems.forEach((item) => {
            item.addEventListener('click', () => {
                const nextSort = item.dataset.sortValue || 'newest';
                const form = document.getElementById('tenant-property-filter-form');
                if (!form) {
                    return;
                }

                let sortInput = form.querySelector('input[name="sort"]');
                if (!sortInput) {
                    sortInput = document.createElement('input');
                    sortInput.type = 'hidden';
                    sortInput.name = 'sort';
                    form.appendChild(sortInput);
                }
                sortInput.value = nextSort;
                form.requestSubmit();
            });
        });
    }

    document.querySelectorAll('.property-toolbar-search').forEach((searchForm) => {
        const searchInput = searchForm.querySelector('input[name="q"]');
        if (!searchInput) {
            return;
        }

        const syncSearchOverlay = () => {
            searchForm.classList.toggle('has-value', searchInput.value.trim() !== '');
        };

        searchInput.addEventListener('input', syncSearchOverlay);
        searchInput.addEventListener('change', syncSearchOverlay);
        syncSearchOverlay();
    });
</script>
@endsection
