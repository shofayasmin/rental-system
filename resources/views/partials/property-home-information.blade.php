@php
    $categoryLabels = [
        'utilities' => 'Utilities',
        'interior' => 'Interior',
        'outdoor' => 'Outdoor',
    ];
    $hasFacilities = $property->facilityItems->isNotEmpty();
@endphp

<section class="home-info-section">
    <h4 class="home-info-title">Home Information</h4>

    <div class="home-info-block">
        <h5 class="section-title">Specifications</h5>
        <div class="spec-grid">
            <p class="spec-item">
                <img src="{{ asset('icons/bedroom-icon.svg') }}" alt="" class="spec-item-icon" onerror="this.style.display='none'">
                <span><span class="spec-label">Bedrooms</span><span class="spec-value">{{ $property->bedrooms }}</span></span>
            </p>
            <p class="spec-item">
                <img src="{{ asset('icons/bathroom-icon.svg') }}" alt="" class="spec-item-icon" onerror="this.style.display='none'">
                <span><span class="spec-label">Bathrooms</span><span class="spec-value">{{ (int) $property->bathrooms }}</span></span>
            </p>
            <p class="spec-item">
                <img src="{{ asset('icons/floor-icon.svg') }}" alt="" class="spec-item-icon" onerror="this.style.display='none'">
                <span><span class="spec-label">Floors</span><span class="spec-value">{{ $property->floors ?? '-' }}</span></span>
            </p>
            <p class="spec-item">
                <img src="{{ asset('icons/area-icon.svg') }}" alt="" class="spec-item-icon" onerror="this.style.display='none'">
                <span><span class="spec-label">Land Area</span><span class="spec-value">{{ $property->area }} m²</span></span>
            </p>
            <p class="spec-item">
                <img src="{{ asset('icons/area-icon.svg') }}" alt="" class="spec-item-icon" onerror="this.style.display='none'">
                <span><span class="spec-label">Building Area</span><span class="spec-value">{{ $property->building_area ?? $property->area }} m²</span></span>
            </p>
        </div>
    </div>

    <div class="home-info-block">
        <h5 class="section-title">Facilities</h5>
        @if($hasFacilities)
            @foreach($categoryLabels as $category => $label)
                @php($items = $property->facilityItems->where('category', $category))
                @if($items->isNotEmpty())
                    <div class="facility-category">
                        <div class="facility-category-title">{{ $label }}</div>
                        <ul class="facility-list">
                            @foreach($items as $facility)
                                <li>
                                    {{ $facility->name }}
                                    @if(!empty(data_get($facility, 'pivot.value')))
                                        <span class="facility-item-value">
                                            : {{ data_get($facility, 'pivot.value') }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        @else
            <p class="text-muted mb-0">No facilities listed yet.</p>
        @endif
    </div>

    <div class="home-info-block">
        <h5 class="section-title">Description</h5>
        @if(!empty($property->description))
            <p class="mb-0">{!! nl2br(e($property->description)) !!}</p>
        @else
            <p class="text-muted mb-0">No description available yet.</p>
        @endif
    </div>

    <div class="home-info-block">
        <h5 class="section-title">Location</h5>
        <div class="location-grid">
            <div class="location-item">
                <div class="location-label">Province</div>
                <div class="location-value">{{ $property->province?->name ?? '-' }}</div>
            </div>
            <div class="location-item">
                <div class="location-label">Regency</div>
                <div class="location-value">{{ $property->regency?->name ?? '-' }}</div>
            </div>
            <div class="location-item">
                <div class="location-label">District</div>
                <div class="location-value">{{ $property->district?->name ?? '-' }}</div>
            </div>
            <div class="location-item">
                <div class="location-label">Village</div>
                <div class="location-value">{{ $property->village?->name ?? '-' }}</div>
            </div>
            <div class="location-item location-item-full">
                <div class="location-label">Address</div>
                <div class="location-value">{{ $property->address ?? '-' }}</div>
            </div>
        </div>

        @if($hasCoordinates)
            <div id="property-map" aria-label="Property location map"></div>
            <div class="location-coordinates">
                {{ number_format((float) $property->latitude, 6) }}, {{ number_format((float) $property->longitude, 6) }}
            </div>
            <a href="{{ $googleMapsUrl }}"
               target="_blank"
               rel="noopener noreferrer"
               class="btn btn-outline-primary btn-sm">
                Open in Google Maps
            </a>
        @else
            <p class="text-muted mb-0">Map location not available yet.</p>
        @endif
    </div>
</section>
