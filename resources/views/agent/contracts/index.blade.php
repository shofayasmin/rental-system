@extends('layouts.app')

@section('content')
<style>
    .agent-requests-tools {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
        margin-bottom: 14px;
    }
    .agent-requests-tools .search-wrap {
        position: relative;
        width: 360px;
        min-width: 320px;
        max-width: 360px;
    }
    .agent-requests-tools .search-wrap .form-control {
        padding-left: 38px;
    }
</style>

<div class="container">
    <h2 class="mb-3">Contracts</h2>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link"
               href="{{ url('/agent/rental-requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'in_progress'])) }}">
                In Progress ({{ $tabCounts['in_progress'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               href="{{ url('/agent/rental-requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'active_lease'])) }}">
                Active Lease ({{ $tabCounts['active_lease'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               href="{{ url('/agent/rental-requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'closed'])) }}">
                Closed ({{ $tabCounts['closed'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link"
               href="{{ url('/agent/rental-requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'extensions'])) }}">
                Extensions ({{ $tabCounts['extensions'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <span class="nav-link active" aria-current="page">Contracts</span>
        </li>
    </ul>

    @if($contracts->isEmpty())
        <div class="alert alert-secondary">
            No contracts yet.
        </div>
    @else
        <div class="agent-requests-tools">
            <form method="GET" action="/agent/contracts" class="search-wrap" style="max-width:360px;width:100%">
                <input type="text"
                       name="q"
                       id="contracts-search"
                       class="form-control"
                       value="{{ $search ?? '' }}"
                       placeholder="Search property, tenant...">
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle" id="contracts-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Property</th>
                        <th>Tenant</th>
                        <th>Period</th>
                        <th>Monthly</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $c)
                        @php($initialContract = $c->contract)
                        @php($extension = $c->contractExtension)
                        @php($isExtension = $c->type === 'extension_rent' && $extension)
                        @php($fallbackStart = $c->updated_at ? $c->updated_at->toDateString() : null)
                        @php($periodStart = $isExtension ? ($extension->old_end_date ?? $fallbackStart) : (data_get($initialContract, 'start_date') ?? $fallbackStart))
                        @php($periodEnd = $isExtension
                            ? ($extension->new_end_date ?? ($periodStart ? \Carbon\Carbon::parse($periodStart)->addMonths((int) ($extension->months_requested ?? 1))->toDateString() : null))
                            : ($periodStart ? \Carbon\Carbon::parse($periodStart)->addMonth()->toDateString() : null))
                        @php($monthlyRent = $isExtension
                            ? (data_get($extension, 'monthly_rent_snapshot') ?? ((int) data_get($extension, 'months_requested', 1) > 0 ? ((float) $c->amount / (int) data_get($extension, 'months_requested', 1)) : (float) $c->amount))
                            : (data_get($initialContract, 'monthly_rent') ?? (float) $c->amount))
                        <tr>
                            <td>{{ $contracts->firstItem() + $loop->index }}</td>
                            <td>{{ data_get($c, 'property.title', '-') }}</td>
                            <td>{{ data_get($c, 'tenant.name', '-') }}</td>
                            <td>
                                @if($periodStart && $periodEnd)
                                    {{ $periodStart }} → {{ $periodEnd }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>Rp {{ number_format((float) $monthlyRent, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $c->amount, 0, ',', '.') }}</td>
                            <td>
                                <a href="/contracts/{{ $c->id }}" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $contracts->links() }}
        </div>
    @endif
</div>

<script>
    (function () {
        const searchInput = document.getElementById('contracts-search');
        if (!searchInput) return;

        const rows = Array.from(document.querySelectorAll('#contracts-table tbody tr'));

        const filter = function () {
            const term = (searchInput.value || '').trim().toLowerCase();

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = term === '' || text.includes(term) ? '' : 'none';
            });
        };

        searchInput.addEventListener('input', filter);

        const params = new URLSearchParams(window.location.search);
        const q = params.get('q');
        if (q) {
            searchInput.value = q;
            filter();
        }
    })();
</script>
@endsection
