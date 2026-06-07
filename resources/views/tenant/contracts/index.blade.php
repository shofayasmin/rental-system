@extends('layouts.app')

@section('content')
<style>
    .list-tools {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .list-tools .search-wrap {
        position: relative;
        flex: 1 1 320px;
        min-width: 240px;
    }
    .list-tools .search-wrap .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }
    .list-tools .search-wrap .form-control {
        padding-left: 38px;
        max-width: 100%;
    }
    .contracts-page-tabs .nav-link {
        white-space: nowrap;
    }
    .tenant-request-list {
        display: grid;
        gap: 14px;
    }
    .tenant-request-card {
        border: 1px solid #dbe3ee;
        border-radius: 14px;
        background: #fff;
    }
    .tenant-request-card-body {
        padding: 16px;
    }
    .tenant-request-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 10px;
    }
    .tenant-request-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        line-height: 1.3;
    }
    .tenant-request-title a {
        color: #0f172a;
        text-decoration: none;
    }
    .tenant-request-title a:hover {
        text-decoration: underline;
    }
    .tenant-request-address {
        margin-top: 3px;
        color: #64748b;
        font-size: .92rem;
    }
    .tenant-request-owner {
        margin-top: 3px;
        color: #475569;
        font-size: .9rem;
        font-weight: 600;
    }
    .tenant-contract-table-wrap {
        margin-bottom: 12px;
    }
    .tenant-contract-table {
        font-size: .92rem;
    }
    .tenant-contract-table th {
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .tenant-contract-table td {
        font-weight: 600;
        color: #0f172a;
        vertical-align: middle;
    }
    @media (max-width: 767px) {
        .list-tools .search-wrap {
            width: 100%;
            min-width: 0;
        }
    }
</style>

<div class="container">
    <h2 class="mb-3">My Contracts</h2>

    <ul class="nav nav-tabs mb-3 contracts-page-tabs">
        <li class="nav-item">
            <a class="nav-link"
               href="{{ url('/tenant/requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'in_progress'])) }}">
                In Progress ({{ $tabCounts['in_progress'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               href="{{ url('/tenant/requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'active_lease'])) }}">
                Active Lease ({{ $tabCounts['active_lease'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               href="{{ url('/tenant/requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'closed'])) }}">
                Closed ({{ $tabCounts['closed'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               href="{{ url('/tenant/requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'extensions'])) }}">
                Extensions ({{ $tabCounts['extensions'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <span class="nav-link active" aria-current="page">Contracts</span>
        </li>
    </ul>

    <form id="tenant-contracts-filter-form" method="GET" action="{{ url('/tenant/contracts') }}" class="list-tools mb-3">
        <div class="search-wrap">
            <img src="{{ asset('icons/search-icon.svg') }}"
                 alt=""
                 width="18"
                 height="18"
                 class="search-icon"
                 aria-hidden="true">
            <input type="text"
                   name="q"
                   id="tenant-contracts-search"
                   value="{{ $search }}"
                   class="form-control"
                   placeholder="Search contracts">
        </div>
    </form>

    @if($contracts->isEmpty())
        <div class="alert alert-secondary mt-3 mb-0">
            No contracts yet.
        </div>
    @else
        @php($contractGroups = $contracts->groupBy('property_id'))
        <div class="tenant-request-list mt-3">
            @foreach($contractGroups as $propertyContracts)
                @php($property = $propertyContracts->first()?->property)
                @php($locationText = collect([data_get($property, 'regency.name'), data_get($property, 'district.name')])->filter()->implode(', '))
                @php($ownerName = data_get($property, 'agent.name'))
                <div class="tenant-request-card">
                    <div class="tenant-request-card-body">
                        <div class="tenant-request-header">
                            <div>
                                <h3 class="tenant-request-title">
                                    @if($property)
                                        <a href="/tenant/properties/{{ $property->id }}">
                                            {{ $property->title ?? '-' }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </h3>
                                <div class="tenant-request-address">
                                    {{ $locationText !== '' ? $locationText : (data_get($property, 'address') ?: 'Location not set') }}
                                </div>
                                <div class="tenant-request-owner">
                                    {{ $ownerName ?: '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive tenant-contract-table-wrap">
                            <table class="table table-bordered mb-0 align-middle tenant-contract-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">No</th>
                                        <th>Period</th>
                                        <th>Paid At</th>
                                        <th style="width: 150px;">View Contract</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($propertyContracts as $index => $contractTx)
                                        @php($contract = $contractTx->contract)
                                        @php($extension = $contractTx->contractExtension)
                                        @php($isExtension = $contractTx->type === 'extension_rent' && $extension)
                                        @php($periodStart = $isExtension ? $extension->old_end_date : data_get($contract, 'start_date'))
                                        @php($periodEnd = $isExtension ? $extension->new_end_date : data_get($contract, 'end_date'))
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ ($periodStart ?: '-') . ' -> ' . ($periodEnd ?: '-') }}</td>
                                            <td>{{ $contractTx->updated_at?->format('Y-m-d H:i') ?: '-' }}</td>
                                            <td>
                                                <a href="/tenant/contracts/{{ $contractTx->id }}" class="btn btn-sm btn-primary">
                                                    View Contract
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    const searchInput = document.getElementById('tenant-contracts-search');
    if (searchInput) {
        let submitTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(submitTimer);
            submitTimer = setTimeout(() => {
                searchInput.form?.requestSubmit();
            }, 450);
        });
    }
</script>

@endsection
