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
@endphp

<style>
    .property-toolbar-controls {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .property-toolbar-sort {
        min-width: 170px;
    }
    .property-toolbar-search {
        min-width: 260px;
        width: 260px;
    }
    .property-filter-toggle {
        white-space: nowrap;
    }
    .property-filter-panel {
        border: 1px solid #dee2e6;
        border-radius: 14px;
        background: #fff;
    }
    @media (max-width: 768px) {
        .property-toolbar-sort,
        .property-toolbar-search {
            width: 100%;
            min-width: 0;
        }
    }
</style>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0">Properties</h2>
        <div class="property-toolbar-controls">
            <form method="GET" action="{{ route('houses.index') }}" class="property-toolbar-search">
                <input type="text"
                       name="q"
                       class="form-control"
                       value="{{ $search }}"
                       placeholder="Search properties...">
            </form>

            <select class="form-select property-toolbar-sort" name="sort" form="tenant-property-filter-form">
                <option value="newest" @selected($selectedSort === 'newest')>Newest</option>
                <option value="oldest" @selected($selectedSort === 'oldest')>Oldest</option>
                <option value="price_desc" @selected($selectedSort === 'price_desc')>Price: High to Low</option>
                <option value="price_asc" @selected($selectedSort === 'price_asc')>Price: Low to High</option>
                <option value="area_desc" @selected($selectedSort === 'area_desc')>Area: Largest</option>
                <option value="area_asc" @selected($selectedSort === 'area_asc')>Area: Smallest</option>
            </select>

            <button class="btn btn-outline-secondary property-filter-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#propertyFilterPanel"
                    aria-expanded="false"
                    aria-controls="propertyFilterPanel">
                Filters
            </button>
        </div>
    </div>

    <div class="collapse mb-4" id="propertyFilterPanel" data-has-active-filters="{{ request()->hasAny(['status', 'province_id', 'regency_id', 'district_id', 'village_id', 'min_price', 'max_price', 'min_area', 'max_area', 'min_bedrooms', 'max_bedrooms', 'min_bathrooms', 'max_bathrooms']) ? '1' : '0' }}">
        <div class="property-filter-panel p-3">
            <form id="tenant-property-filter-form" method="GET" action="{{ route('houses.index') }}">
                <input type="hidden" name="q" value="{{ $search }}">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="to-let" @selected($selectedStatus === 'to-let')>To Let</option>
                            <option value="rented" @selected($selectedStatus === 'rented')>Rented</option>
                            <option value="maintenance" @selected($selectedStatus === 'maintenance')>Maintenance</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Province</label>
                        <select id="province_id" name="province_id" class="form-select">
                            <option value="">All provinces</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}" @selected((string) $selectedProvinceId === (string) $province->id)>{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Regency / City</label>
                        <select id="regency_id" name="regency_id" class="form-select">
                            <option value="">All regencies / cities</option>
                            @foreach($regencies as $regency)
                                <option value="{{ $regency->id }}" @selected((string) $selectedRegencyId === (string) $regency->id)>{{ $regency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">District</label>
                        <select id="district_id" name="district_id" class="form-select">
                            <option value="">All districts</option>
                            @foreach($districts as $district)
                                <option value="{{ $district->id }}" @selected((string) $selectedDistrictId === (string) $district->id)>{{ $district->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Village</label>
                        <select id="village_id" name="village_id" class="form-select">
                            <option value="">All villages</option>
                            @foreach($villages as $village)
                                <option value="{{ $village->id }}" @selected((string) $selectedVillageId === (string) $village->id)>{{ $village->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Min Price</label>
                        <input type="number" name="min_price" class="form-control" value="{{ $selectedMinPrice }}" min="0">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Max Price</label>
                        <input type="number" name="max_price" class="form-control" value="{{ $selectedMaxPrice }}" min="0">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Min Area</label>
                        <input type="number" name="min_area" class="form-control" value="{{ $selectedMinArea }}" min="0">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Max Area</label>
                        <input type="number" name="max_area" class="form-control" value="{{ $selectedMaxArea }}" min="0">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Min BR</label>
                        <input type="number" name="min_bedrooms" class="form-control" value="{{ $selectedMinBedrooms }}" min="0">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Max BR</label>
                        <input type="number" name="max_bedrooms" class="form-control" value="{{ $selectedMaxBedrooms }}" min="0">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Min BA</label>
                        <input type="number" name="min_bathrooms" class="form-control" value="{{ $selectedMinBathrooms }}" min="0">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Max BA</label>
                        <input type="number" name="max_bathrooms" class="form-control" value="{{ $selectedMaxBathrooms }}" min="0">
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('houses.index') }}" class="btn btn-outline-secondary">Reset</a>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
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
</script>
@endsection
