@extends('layouts.app')

@section('content')
<style>
    .contracts-page-tabs .nav-link {
        white-space: nowrap;
    }
    .contracts-toolbar {
        margin-top: 18px;
    }
    .contracts-table-wrap {
        margin-top: 14px;
    }
    .contracts-table .badge {
        vertical-align: middle;
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

    @if($contracts->isEmpty())
        <div class="alert alert-secondary mt-3 mb-0">
            No contracts yet.
        </div>
    @else
        <div class="card shadow-sm contracts-table-wrap">
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0 align-middle contracts-table">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Rent</th>
                            <th>Status</th>
                            <th>Contract End</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contracts as $contractTx)
                            @php($contract = $contractTx->contract)
                            @php($property = $contractTx->property)
                            @php($latestExtension = data_get($contract, 'latestExtension'))
                            @php($locationText = collect([data_get($property, 'regency.name'), data_get($property, 'district.name')])->filter()->implode(', '))
                            @php($statusText = $contractTx->status === 'paid' ? 'PAID' : strtoupper((string) $contractTx->status))
                            @php($statusClass = $contractTx->status === 'paid' ? 'bg-success' : 'bg-secondary')
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $property->title ?? '-' }}</div>
                                    <div class="small text-muted">
                                        {{ $locationText !== '' ? $locationText : ($property->address ?: 'Location not set') }}
                                    </div>
                                </td>
                                <td>
                                    Rp {{ number_format((float) ($contract->monthly_rent ?? $contractTx->amount), 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                    @if($latestExtension && $latestExtension->status === 'awaiting_payment')
                                        <div class="small text-muted mt-1">
                                            Extension awaiting payment
                                        </div>
                                    @elseif($latestExtension && $latestExtension->status === 'pending')
                                        <div class="small text-muted mt-1">
                                            Extension pending approval
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{ data_get($contract, 'end_date', '-') }}
                                    @if($latestExtension && data_get($latestExtension, 'new_end_date'))
                                        <div class="small text-muted">
                                            Latest extension: {{ $latestExtension->new_end_date }}
                                        </div>
                                    @endif
                                </td>
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
    @endif
</div>

@endsection
