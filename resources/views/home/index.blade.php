@extends('layouts.app')

@section('content')
@php
    $isAgent = auth()->check() && auth()->user()->role === 'agent';
@endphp
<style>
    .hero-section {
        background: #FFCE1B;
        color: #212529;
        padding: 100px 0 220px;
        position: relative;
        overflow: hidden;
        width: 100vw;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        margin-top: calc(-1.5rem - var(--bs-gutter-x));
    }
    .hero-content-row {
        min-height: 360px;
    }
    .hero-title {
        font-size: 2.8rem;
        font-weight: 800;
        line-height: 1.2;
    }
    .hero-subtitle {
        font-size: 1.15rem;
        opacity: 0.85;
        max-width: 600px;
    }
    .hero-cta .btn {
        border-radius: 50px;
        padding: 12px 32px;
        font-weight: 600;
    }
    .btn-find-property {
        background-color: #0B3223;
        border-color: #0B3223;
        color: #FFCE1B;
    }
    .btn-find-property:hover,
    .btn-find-property:focus {
        background-color: #072117;
        border-color: #072117;
        color: #FFCE1B;
    }
    .btn-search-highlight {
        background-color: #0B3223;
        border-color: #0B3223;
        color: #fff;
    }
    .btn-search-highlight:hover,
    .btn-search-highlight:focus {
        background-color: #072117;
        border-color: #072117;
        color: #fff;
    }
    .btn-hover-green:hover,
    .btn-hover-green:focus {
        background-color: #072117 !important;
        border-color: #072117 !important;
        color: #fff !important;
    }
    .hero-flowchart {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        width: min(100%, 360px);
        margin-inline: auto;
    }
    .flow-step {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.9rem;
        width: 100%;
        background: rgba(11, 50, 35, 0.12);
        border: 1px solid rgba(11, 50, 35, 0.2);
        border-radius: 14px;
        padding: 0.95rem 1rem;
    }
    .flow-step.is-active {
        background: #0B3223;
        border-color: #0B3223;
        color: #FFCE1B;
    }
    .flow-badge {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #0B3223;
        color: #FFCE1B;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }
    .flow-step.is-active .flow-badge {
        background: #FFCE1B;
        color: #0B3223;
    }
    .flow-icon {
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .flow-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .flow-title {
        font-weight: 700;
        color: #0B3223;
        margin: 0;
        text-align: center;
    }
    .flow-step.is-active .flow-title {
        color: #FFCE1B;
    }
    .flow-connector {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        color: #0B3223;
        margin: -0.2rem 0;
    }
    .flow-arrow-icon {
        width: 20px;
        height: 20px;
        display: block;
        object-fit: contain;
        opacity: 0.95;
    }

    .home-filter-card {
        margin-top: -140px;
        position: relative;
        z-index: 2;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    }
    .home-filter-card .card-body {
        padding: 2rem;
    }
    .search-form-row {
        display: flex;
        align-items: flex-end;
    }
    .search-col {
        padding: 0 1rem;
    }
    .search-col-location {
        flex: 1.2;
        padding-left: 0;
        border-right: 1px solid #e5e7eb;
    }
    .search-col-price {
        flex: 1.2;
    }
    .search-col-action {
        width: 200px;
        padding-right: 0;
    }
    .price-inline {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .price-field {
        flex: 1;
    }
    .price-input {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        box-shadow: none;
        background: #fff;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .price-input:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        background: #fff;
    }
    .price-dash {
        color: #6c757d;
        font-size: 1.5rem;
        line-height: 1;
    }

    .section-title {
        font-weight: 700;
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }
    .section-subtitle {
        color: #6c757d;
        max-width: 600px;
    }

    .about-icon-wrap {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .about-icon-wrap img {
        width: 26px;
        height: 26px;
        object-fit: contain;
    }

    .cta-agent-section {
        background: linear-gradient(135deg, #FFCE1B 0%, #ffb800 100%);
        border-radius: 16px;
    }

    .property-card-clickable {
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .property-card-clickable:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 1.8rem;
        }
        .hero-section {
            padding: 70px 0 150px;
            margin-top: calc(-1.5rem - var(--bs-gutter-x));
        }
        .hero-content-row {
            min-height: auto;
        }
        .home-filter-card {
            margin-top: -80px;
        }
        .search-form-row {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }
        .search-col {
            width: 100%;
            padding: 0;
        }
        .search-col-location {
            border-right: 0;
        }
        .search-col-action {
            width: 100%;
        }
        .hero-flowchart {
            gap: 0.6rem;
        }
        .flow-step {
            padding: 0.85rem 0.9rem;
        }
        .flow-title {
            font-size: 0.95rem;
        }
        .flow-badge {
            width: 30px;
            height: 30px;
            font-size: 0.85rem;
        }
        .flow-icon {
            width: 22px;
            height: 22px;
        }
    }
</style>

{{-- HERO SECTION --}}
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center hero-content-row">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <h1 class="hero-title mb-3">
                    Find Your Dream House<br>
                    Rent with Confidence
                </h1>
                <p class="hero-subtitle mb-4">
                    House Rental makes it easy to find the best rental homes
                    with a transparent and reliable process. A trusted platform for
                    tenants to discover available properties quickly.
                </p>
                <div class="d-flex flex-wrap gap-3 hero-cta">
                    @if($isAgent)
                        <span class="btn btn-find-property btn-lg disabled" aria-disabled="true" tabindex="-1">
                            Find Property
                        </span>
                    @else
                        <a href="/houses" class="btn btn-find-property btn-lg">
                            Find Property
                        </a>
                    @endif
                    @guest
                    <a href="/register" class="btn btn-outline-dark btn-lg btn-hover-green">
                        Register Now
                    </a>
                    @endguest
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-flowchart">
                    <div class="flow-step">
                        <span class="flow-badge">1</span>
                        <span class="flow-icon">
                            <img src="{{ asset('icons/search-hp.svg') }}" alt="Find House icon">
                        </span>
                        <p class="flow-title">Find House</p>
                    </div>
                    <div class="flow-connector" aria-hidden="true">
                        <img src="{{ asset('icons/arrow-square-down-hp.svg') }}" alt="" class="flow-arrow-icon">
                    </div>
                    <div class="flow-step">
                        <span class="flow-badge">2</span>
                        <span class="flow-icon">
                            <img src="{{ asset('icons/chat-hp.svg') }}" alt="Chat Agent icon">
                        </span>
                        <p class="flow-title">Chat Agent</p>
                    </div>
                    <div class="flow-connector" aria-hidden="true">
                        <img src="{{ asset('icons/arrow-square-down-hp.svg') }}" alt="" class="flow-arrow-icon">
                    </div>
                    <div class="flow-step">
                        <span class="flow-badge">3</span>
                        <span class="flow-icon">
                            <img src="{{ asset('icons/contract-hp.svg') }}" alt="Submit Request icon">
                        </span>
                        <p class="flow-title">Submit Request</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FILTER BOX --}}
<div class="container">
        <div class="card home-filter-card border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Search Properties</h5>
                <form method="GET" action="{{ route('houses.index') }}">
                    <fieldset {{ $isAgent ? 'disabled' : '' }}>
                        <div class="search-form-row">
                            <div class="search-col search-col-location">
                                <label class="form-label">Location</label>
                                <select name="province_id" class="form-select">
                                    <option value="">All locations</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="search-col search-col-price">
                                <label class="form-label">Price</label>
                                <div class="price-inline">
                                    <div class="price-field">
                                        <input type="number" name="min_price" class="form-control price-input" placeholder="Min" min="0">
                                    </div>
                                    <span class="price-dash">-</span>
                                    <div class="price-field">
                                        <input type="number" name="max_price" class="form-control price-input" placeholder="Max" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="search-col search-col-action d-flex align-items-end">
                                <button type="submit" class="btn btn-search-highlight w-100 py-2" {{ $isAgent ? 'disabled' : '' }}>
                                    Search
                                </button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>

{{-- ABOUT US --}}
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Why House Rental?</h2>
            <p class="section-subtitle mx-auto">
                A property rental platform that helps tenants find rental homes
                transparently, quickly, and reliably.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="d-flex gap-3">
                    <div class="about-icon-wrap bg-warning bg-opacity-10 text-warning">
                        <img src="{{ asset('icons/search-hp.svg') }}" alt="Easy to Find icon" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h5 class="fw-bold">Easy to Find</h5>
                        <p class="text-muted mb-0">
                            Comprehensive filters by location, price, land area, 
                            bedrooms, and more. Find the property that suits you.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-3">
                    <div class="about-icon-wrap bg-success bg-opacity-10 text-success">
                        <img src="{{ asset('icons/shaking-hands-hp.svg') }}" alt="Secure Transactions icon" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h5 class="fw-bold">Secure Transactions</h5>
                        <p class="text-muted mb-0">
                            Digital contract system with transparent history. 
                            Every rental is recorded securely.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-3">
                    <div class="about-icon-wrap bg-primary bg-opacity-10 text-primary">
                        <img src="{{ asset('icons/chat-hp.svg') }}" alt="Direct Communication icon" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h5 class="fw-bold">Direct Communication</h5>
                        <p class="text-muted mb-0">
                            Integrated chat feature to connect tenants 
                            and agents directly.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@guest
{{-- CTA TENANT --}}
<section class="py-4">
    <div class="container">
        <div class="cta-agent-section p-5 text-center">
            <h3 class="fw-bold mb-2">Ready to Find Your Next Home?</h3>
            <p class="mb-4 opacity-75" style="font-size: 1.05rem;">
                Create your tenant account to submit rental requests faster,
                chat with agents, and manage your rental process in one place.
            </p>
            <a href="/register" class="btn btn-search-highlight btn-lg px-5" style="border-radius: 50px;">
                Register Now
            </a>
        </div>
    </div>
</section>
@endguest

{{-- FEATURED PROPERTIES --}}
@if($featured->isNotEmpty())
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title mb-0">Newest Properties</h2>
                <p class="section-subtitle mb-0">Newly available properties for rent</p>
            </div>
            <a href="/houses" class="btn btn-outline-primary">
                View All
            </a>
        </div>
        <div class="row">
            @foreach($featured as $property)
                @php
                    $coverPhoto = $property->photos->first(function ($photo) {
                        return preg_match('/^front[_-]/i', basename($photo->path)) === 1;
                    }) ?? $property->photos->first();
                @endphp
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm property-card-clickable"
                         data-href="{{ route('houses.show', $property) }}"
                         role="link"
                         tabindex="0">
                        <img
                            src="{{ ($coverPhoto && \Illuminate\Support\Facades\Storage::disk('public')->exists($coverPhoto->path))
                                    ? asset('storage/'.$coverPhoto->path)
                                    : asset('images/property-placeholder.svg') }}"
                            class="card-img-top"
                            alt="Property Photo"
                            style="height: 200px; object-fit: cover;"
                        >
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $property->title }}</h5>
                            <p class="text-muted small mb-1">
                                {{ $property->province?->name ?? '-' }} /
                                {{ $property->regency?->name ?? '-' }}
                            </p>
                            <p class="mb-2 fs-5 fw-bold text-primary">
                                Rp {{ number_format((float) $property->rent_price, 0, ',', '.') }}/month
                            </p>
                            <div class="d-flex flex-wrap gap-3 text-body-secondary small">
                                <span>{{ $property->bedrooms }} Bed</span>
                                <span>{{ (int) $property->bathrooms }} Bath</span>
                                <span>LA: {{ $property->area }} m²</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<script>
    document.querySelectorAll('.property-card-clickable').forEach((card) => {
        const href = card.dataset.href;
        if (!href) return;

        card.addEventListener('click', function (event) {
            if (event.target.closest('[data-stop-card-click="true"]')) return;
            window.location.href = href;
        });

        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                window.location.href = href;
            }
        });
    });
</script>
@endsection
