@extends('layouts.app')

@section('content')
@php
    $orderedPhotos = $property->photos
        ->sortBy(function ($photo) {
            return preg_match('/^front[_-]/i', basename((string) $photo->path)) === 1 ? 0 : 1;
        })
        ->values();

    $photoUrls = $orderedPhotos->map(function ($photo) {
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($photo->path)
            ? asset('storage/' . $photo->path)
            : asset('images/property-placeholder.svg');
    })->values();

    if ($photoUrls->isEmpty()) {
        $photoUrls = collect([asset('images/property-placeholder.svg')]);
    }

    $initialMainPhoto = $photoUrls->first();
    $sideIndexes = collect(range(1, max($photoUrls->count() - 1, 0)))
        ->take(3)
        ->values();
    $hasSidePanel = true;
    $showSeeAllTile = true;
    $sideSlotCount = max(1, $sideIndexes->count() + 1);
    $firstHiddenPhotoIndex = $sideIndexes->count() + 1;
    $seeAllPreview = $photoUrls->get($firstHiddenPhotoIndex, $photoUrls->last());
    $photoGalleryBaseUrl = route('agent.properties.photos.gallery', ['property' => $property->id]);
    $hasCoordinates = !is_null($property->latitude) && !is_null($property->longitude);
    $googleMapsUrl = $hasCoordinates
        ? 'https://www.google.com/maps?q=' . $property->latitude . ',' . $property->longitude
        : null;
@endphp

<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="">
@include('partials.property-detail-styles')

<div class="container property-detail-page agent-detail-page">
    <nav class="property-detail-breadcrumb mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/agent/properties">My Properties</a></li>
            <li class="breadcrumb-item active" aria-current="page">Property Detail</li>
        </ol>
    </nav>

    @include('partials.property-gallery-hero', [
        'photoUrls' => $photoUrls,
        'hasSidePanel' => $hasSidePanel,
        'sideSlotCount' => $sideSlotCount,
        'sideIndexes' => $sideIndexes,
        'initialMainPhoto' => $initialMainPhoto,
        'galleryUrl' => $photoGalleryBaseUrl,
        'seeAllPreview' => $seeAllPreview,
        'seeAllAlt' => 'See all photos',
        'seeAllLabel' => 'See All',
    ])

    <div class="listing-summary">
        <div>
            <div class="listing-price">
                Rp {{ number_format((float) $property->rent_price, 0, ',', '.') }}/month
            </div>
            <h1 class="listing-title">{{ $property->title }}</h1>
            <div class="listing-location">
                <img src="{{ asset('icons/map-pin-icon.svg') }}" alt="" onerror="this.style.display='none'">
                {{ $property->village?->name ?? '-' }}, {{ $property->district?->name ?? '-' }}, {{ $property->regency?->name ?? '-' }}
            </div>
            <div class="mb-2">
                @if($property->status === 'to-let')
                    <span class="badge bg-success">TO LET</span>
                @elseif($property->status === 'maintenance')
                    <span class="badge bg-secondary">MAINTENANCE</span>
                @elseif($property->status === 'rented')
                    <span class="badge bg-dark">RENTED</span>
                @else
                    <span class="badge bg-light text-dark">{{ strtoupper($property->status) }}</span>
                @endif
            </div>
            <div class="listing-updated">
                Updated {{ optional($property->updated_at)->format('d M Y') }}
            </div>
            @if($property->status !== 'to-let')
                <div class="alert alert-secondary mt-3 mb-0">
                    This property is currently {{ str_replace('-', ' ', $property->status) }} and not available for new applications.
                </div>
            @endif

            @include('partials.property-home-information', [
                'property' => $property,
                'hasCoordinates' => $hasCoordinates,
                'googleMapsUrl' => $googleMapsUrl,
            ])
        </div>

        @include('partials.property-sidebar-agent', [
            'property' => $property,
        ])
    </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>
<script>
    (function () {
        const hasCoordinates = @json($hasCoordinates);
        if (!hasCoordinates || typeof L === 'undefined') {
            return;
        }

        const mapElement = document.getElementById('property-map');
        if (!mapElement) {
            return;
        }

        const lat = Number(@json($property->latitude));
        const lng = Number(@json($property->longitude));

        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            return;
        }

        const map = L.map('property-map').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lng]).addTo(map);
        setTimeout(() => map.invalidateSize(), 0);
    })();

</script>
@include('partials.property-gallery-script', [
    'photoUrls' => $photoUrls,
    'photoGalleryBaseUrl' => $photoGalleryBaseUrl,
])
@endsection
