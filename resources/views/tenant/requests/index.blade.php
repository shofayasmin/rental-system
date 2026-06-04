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
    .list-tools .status-select {
        width: 220px;
        min-width: 220px;
    }
    .list-tools .sort-select {
        width: 180px;
        min-width: 180px;
    }
    .list-tools .form-check {
        white-space: nowrap;
        padding-left: 0;
        margin: 0;
        gap: 8px;
    }
    .list-tools .form-check .form-check-input {
        float: none;
        margin: 0;
    }
    .request-section {
        margin-top: 18px;
    }
    .request-section-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0 0 10px;
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
    .tenant-request-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 18px;
        margin-bottom: 12px;
    }
    .tenant-request-meta-item {
        min-width: 0;
    }
    .tenant-request-meta-label {
        color: #64748b;
        font-size: .8rem;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .tenant-request-meta-value {
        color: #0f172a;
        font-size: .95rem;
        font-weight: 600;
        line-height: 1.3;
        word-break: break-word;
    }
    .tenant-request-actions {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }
    .tenant-request-actions-fixed,
    .tenant-request-actions-state {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .tenant-request-actions-state {
        justify-content: flex-end;
    }
    .tenant-request-meta-value .badge {
        margin-left: 6px;
        vertical-align: middle;
    }
    .tenant-request-meta-value .status-pill {
        margin-left: 0;
    }
    @media (max-width: 767px) {
        .list-tools .search-wrap,
        .list-tools .status-select,
        .list-tools .sort-select,
        .list-tools .form-check {
            width: 100%;
            min-width: 0;
        }
        .tenant-request-meta {
            grid-template-columns: 1fr;
        }
        .tenant-request-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .tenant-request-actions-fixed,
        .tenant-request-actions-state {
            width: 100%;
        }
        .tenant-request-actions-state {
            justify-content: flex-start;
        }
        .tenant-request-actions .btn,
        .tenant-request-actions button {
            width: 100%;
        }
    }
</style>

@php($displayList = $tab === 'extensions' ? $extensions : $requests)
<div class="container">
    <h2 class="mb-3">My Rental Requests</h2>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'in_progress' ? 'active' : '' }}"
               href="{{ url('/tenant/requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'in_progress'])) }}">
                In Progress ({{ $tabCounts['in_progress'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'active_lease' ? 'active' : '' }}"
               href="{{ url('/tenant/requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'active_lease'])) }}">
                Active Lease ({{ $tabCounts['active_lease'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'closed' ? 'active' : '' }}"
               href="{{ url('/tenant/requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'closed'])) }}">
                Closed ({{ $tabCounts['closed'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'extensions' ? 'active' : '' }}"
               href="{{ url('/tenant/requests') . '?' . http_build_query(array_merge(request()->except(['tab', 'status', 'page']), ['tab' => 'extensions'])) }}">
                Extensions ({{ $tabCounts['extensions'] ?? 0 }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/tenant/contracts">Contracts</a>
        </li>
    </ul>

    <form method="GET" action="/tenant/requests" class="list-tools">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="search-wrap">
            <img src="{{ asset('icons/search-icon.svg') }}"
                 alt=""
                 width="18"
                 height="18"
                 class="search-icon"
                 onerror="this.style.display='none'">
            <input type="text"
                   name="q"
                   id="tenant-requests-search"
                   value="{{ $tenantSearch }}"
                   class="form-control"
                   placeholder="Search property, city, tenant...">
        </div>
        <select name="status" class="form-select status-select">
            <option value="">All statuses</option>
            @foreach($statusOptions as $statusValue => $statusText)
                <option value="{{ $statusValue }}" {{ $statusFilter === $statusValue ? 'selected' : '' }}>{{ $statusText }}</option>
            @endforeach
        </select>
        <select name="sort" class="form-select sort-select" aria-label="Sort requests">
            @foreach($sortOptions as $sortValue => $sortLabel)
                <option value="{{ $sortValue }}" {{ $sort === $sortValue ? 'selected' : '' }}>{{ $sortLabel }}</option>
            @endforeach
        </select>
        @if(in_array($tab, ['in_progress', 'extensions'], true))
            <div class="form-check d-flex align-items-center px-2">
                <input class="form-check-input me-2" type="checkbox" name="needs_action" value="1" id="needs-action" {{ $needsActionOnly ? 'checked' : '' }}>
                <label class="form-check-label small" for="needs-action">Needs action only</label>
            </div>
        @endif
        <button class="btn btn-primary">Apply</button>
        <a class="btn btn-outline-secondary"
           href="{{ url('/tenant/requests') . '?' . http_build_query(['tab' => $tab]) }}">
            Reset
        </a>
    </form>

    @if($displayList->isEmpty())
        <div class="alert alert-secondary mt-3 mb-0">
            @if($tab === 'active_lease')
                No active leases.
            @elseif($tab === 'extensions')
                No open extensions.
            @elseif($tab === 'closed')
                No closed requests.
            @else
                No in-progress requests.
            @endif
        </div>
    @else
        <div class="tenant-request-list mt-3" id="tenant-request-list">
            @if($tab === 'extensions')
                @foreach($extensions as $extension)
                    @php($r = data_get($extension, 'contract.rentalRequest'))
                    @php($property = data_get($r, 'property'))
                    @php($locationText = collect([data_get($property, 'regency.name'), data_get($property, 'district.name')])->filter()->implode(', '))
                    @php($extensionTx = data_get($extension, 'transaction'))
                    @php($isAwaitingPayment = $extension->status === 'awaiting_payment')
                    @php($dueAt = $extension->payment_due_at)
                    @php($isExpired = $isAwaitingPayment && $dueAt && $dueAt->isPast())
                    @php($isDueSoon = $isAwaitingPayment && $dueAt && $dueAt->isFuture() && $dueAt->lte(now()->copy()->addDay()))
                    @php($mainStatusText = $extensionStatusLabelMap[$extension->status] ?? strtoupper((string) $extension->status))
                    @php($mainStatusClass = $extension->status === 'pending' ? 'bg-warning text-dark' : ($extension->status === 'awaiting_payment' ? 'bg-info text-dark' : 'bg-secondary'))
                    @php($monthlyRent = (float) ($extension->monthly_rent_snapshot ?? data_get($extension, 'contract.monthly_rent') ?? data_get($property, 'rent_price', 0)))
                    <div class="tenant-request-card">
                        <div class="tenant-request-card-body">
                            <div class="tenant-request-header">
                                <div>
                                    <h3 class="tenant-request-title">
                                        <a href="/tenant/properties/{{ $property->id }}">
                                            {{ $property->title }}
                                        </a>
                                    </h3>
                                    <div class="tenant-request-address">
                                        {{ $locationText !== '' ? $locationText : ($property->address ?: 'Location not set') }}
                                    </div>
                                </div>
                                <span class="badge {{ $mainStatusClass }}">{{ $mainStatusText }}</span>
                            </div>

                            <div class="tenant-request-meta">
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Monthly Rent</div>
                                    <div class="tenant-request-meta-value">Rp {{ number_format($monthlyRent, 0, ',', '.') }}/month</div>
                                </div>
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Requested At</div>
                                    <div class="tenant-request-meta-value">{{ $extension->created_at?->format('Y-m-d H:i') ?: '-' }}</div>
                                </div>
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Period</div>
                                    <div class="tenant-request-meta-value">{{ data_get($extension, 'old_end_date', '-') }} -> {{ data_get($extension, 'new_end_date', '-') }}</div>
                                </div>
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Payment Due</div>
                                    <div class="tenant-request-meta-value">
                                        @if($isAwaitingPayment && $dueAt)
                                            {{ $dueAt->format('Y-m-d H:i') }}
                                            @if($isExpired)
                                                <span class="badge bg-danger">Expired</span>
                                            @elseif($isDueSoon)
                                                <span class="badge bg-warning text-dark">Due Soon</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Extension Status</div>
                                    <div class="tenant-request-meta-value">{{ $mainStatusText }}</div>
                                </div>
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Amount</div>
                                    <div class="tenant-request-meta-value">Rp {{ number_format((float) $extension->amount, 0, ',', '.') }}</div>
                                </div>
                            </div>

                            <div class="tenant-request-actions">
                                <div class="tenant-request-actions-fixed">
                                    @if($r)
                                        <a href="/tenant/requests/{{ $r->id }}" class="btn btn-outline-primary btn-sm">Rental Detail</a>
                                        <a href="/messages/{{ $r->id }}" class="btn btn-info btn-sm">Chat Agent</a>
                                    @endif
                                </div>
                                <div class="tenant-request-actions-state">
                                    @if($extension->status === 'pending')
                                        <form method="POST" action="/tenant/contract-extensions/{{ $extension->id }}/cancel" class="d-inline">
                                            @csrf
                                            <button class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to cancel this extension request?')">
                                                Cancel Extension
                                            </button>
                                        </form>
                                    @elseif($isAwaitingPayment && !$isExpired && $extensionTx && $extensionTx->status === 'unpaid')
                                        <form method="POST" action="/tenant/transactions/{{ $extensionTx->id }}/pay" class="d-inline">
                                            @csrf
                                            <button class="btn btn-success btn-sm">Pay Extension</button>
                                        </form>
                                        <form method="POST" action="/tenant/contract-extensions/{{ $extension->id }}/cancel" class="d-inline">
                                            @csrf
                                            <button class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to cancel this extension request?')">
                                                Cancel Extension
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                @foreach($requests as $r)
                    @php($activeContract = $r->activeContract)
                    @php($latestExtension = data_get($activeContract, 'latestExtension'))
                    @php($extensionTransaction = data_get($latestExtension, 'transaction'))
                    @php($monthlyRent = (float) ($activeContract ? $activeContract->monthly_rent : $r->property->rent_price))
                    @php($locationText = collect([$r->property->regency?->name, $r->property->district?->name])->filter()->implode(', '))
                    @php($isAwaitingPayment = $r->status === 'awaiting_payment')
                    @php($initialDueAt = $r->payment_due_at)
                    @php($isInitialExpired = $isAwaitingPayment && $initialDueAt && $initialDueAt->isPast())
                    @php($isInitialDueSoon = $isAwaitingPayment && $initialDueAt && $initialDueAt->isFuture() && $initialDueAt->lte(now()->copy()->addDay()))
                    @php($extensionDueAt = data_get($latestExtension, 'payment_due_at'))
                    @php($isExtensionAwaitingPayment = $latestExtension && $latestExtension->status === 'awaiting_payment')
                    @php($isExtensionExpired = $isExtensionAwaitingPayment && $extensionDueAt && $extensionDueAt->isPast())
                    @php($isExtensionDueSoon = $isExtensionAwaitingPayment && $extensionDueAt && $extensionDueAt->isFuture() && $extensionDueAt->lte(now()->copy()->addDay()))
                    @php($currentRequestType = '-')
                    @php($currentRequestState = 'No Pending Payment')
                    @php($currentRequestBadgeClass = 'bg-light text-dark')
                    @php($currentRequestDueAt = null)
                    @php($currentRequestAmount = null)
                    @php($payTransaction = null)
                    @if($isAwaitingPayment && $r->transaction && $r->transaction->status === 'unpaid')
                        @php($currentRequestType = 'Initial')
                        @php($currentRequestState = 'Awaiting Payment')
                        @php($currentRequestBadgeClass = 'bg-info text-dark')
                        @php($currentRequestDueAt = $initialDueAt)
                        @php($currentRequestAmount = (float) $r->transaction->amount)
                        @if(!$isInitialExpired)
                            @php($payTransaction = $r->transaction)
                        @endif
                    @elseif($latestExtension && $latestExtension->status === 'awaiting_payment')
                        @php($currentRequestType = 'Extension')
                        @php($currentRequestState = 'Awaiting Payment')
                        @php($currentRequestBadgeClass = 'bg-info text-dark')
                        @php($currentRequestDueAt = $extensionDueAt)
                        @php($currentRequestAmount = (float) data_get($latestExtension, 'amount'))
                        @if(!$isExtensionExpired && $extensionTransaction && $extensionTransaction->status === 'unpaid')
                            @php($payTransaction = $extensionTransaction)
                        @endif
                    @elseif($latestExtension && $latestExtension->status === 'pending')
                        @php($currentRequestType = 'Extension')
                        @php($currentRequestState = 'Pending Approval')
                        @php($currentRequestBadgeClass = 'bg-warning text-dark')
                        @php($currentRequestAmount = (float) data_get($latestExtension, 'amount'))
                    @elseif($latestExtension && $latestExtension->status === 'paid')
                        @php($currentRequestType = 'Extension')
                        @php($currentRequestState = 'Paid')
                        @php($currentRequestBadgeClass = 'bg-success')
                        @php($currentRequestAmount = (float) data_get($latestExtension, 'amount'))
                    @elseif($r->status === 'pending_review')
                        @php($currentRequestType = 'Initial')
                        @php($currentRequestState = 'Under Review')
                        @php($currentRequestBadgeClass = 'bg-warning text-dark')
                        @php($currentRequestAmount = (float) $r->property->rent_price)
                    @endif
                    @php($requestActionBy = null)
                    @php($requestActionAt = null)
                    @if($r->status === 'rejected')
                        @php($requestActionBy = 'Agent')
                        @php($requestActionAt = $r->rejected_at)
                    @elseif($r->status === 'cancelled_by_tenant')
                        @php($requestActionBy = 'Tenant')
                        @php($requestActionAt = $r->cancelled_at)
                    @elseif($r->status === 'cancelled_by_agent')
                        @php($requestActionBy = 'Agent')
                        @php($requestActionAt = $r->cancelled_at)
                    @elseif($r->status === 'cancelled_lost')
                        @php($requestActionBy = 'System')
                        @php($requestActionAt = $r->cancelled_at)
                    @endif
                    @php($mainStatusText = 'REQUEST APPROVED (PAID)')
                    @php($mainStatusClass = 'bg-primary')
                    @if($currentRequestState === 'Awaiting Payment')
                        @php($mainStatusText = 'AWAITING PAYMENT')
                        @php($mainStatusClass = 'bg-info text-dark')
                    @elseif($currentRequestState === 'Pending Approval')
                        @php($mainStatusText = 'PENDING APPROVAL')
                        @php($mainStatusClass = 'bg-warning text-dark')
                    @elseif($r->status === 'pending_review')
                        @php($mainStatusText = 'REQUEST UNDER REVIEW')
                        @php($mainStatusClass = 'bg-warning text-dark')
                    @elseif($r->status === 'paid' && $activeContract && $activeContract->status === 'active')
                        @php($mainStatusText = 'ACTIVE LEASE')
                        @php($mainStatusClass = 'bg-success')
                    @elseif($r->status === 'cancelled_lost')
                        @php($mainStatusText = 'PAYMENT EXPIRED')
                        @php($mainStatusClass = 'bg-dark')
                    @elseif($r->status === 'cancelled_by_tenant')
                        @php($mainStatusText = 'REQUEST CANCELLED BY TENANT')
                        @php($mainStatusClass = 'bg-dark')
                    @elseif($r->status === 'cancelled_by_agent')
                        @php($mainStatusText = 'REQUEST CANCELLED BY AGENT')
                        @php($mainStatusClass = 'bg-dark')
                    @elseif($r->status === 'rejected')
                        @php($mainStatusText = 'REQUEST REJECTED BY AGENT')
                        @php($mainStatusClass = 'bg-danger')
                    @endif
                    <div class="tenant-request-card">
                        <div class="tenant-request-card-body">
                            <div class="tenant-request-header">
                                <div>
                                    <h3 class="tenant-request-title">
                                        <a href="/tenant/properties/{{ $r->property->id }}">
                                            {{ $r->property->title }}
                                        </a>
                                    </h3>
                                    <div class="tenant-request-address">
                                        {{ $locationText !== '' ? $locationText : ($r->property->address ?: 'Location not set') }}
                                    </div>
                                </div>
                                <div>
                                    <span class="badge {{ $mainStatusClass }}">{{ $mainStatusText }}</span>
                                </div>
                            </div>

                            <div class="tenant-request-meta">
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Monthly Rent</div>
                                    <div class="tenant-request-meta-value">Rp {{ number_format($monthlyRent, 0, ',', '.') }}/month</div>
                                </div>
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Requested At</div>
                                    <div class="tenant-request-meta-value">{{ $r->created_at?->format('Y-m-d H:i') ?: '-' }}</div>
                                </div>
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Contract End</div>
                                    <div class="tenant-request-meta-value">{{ data_get($activeContract, 'end_date', '-') }}</div>
                                </div>
                                <div class="tenant-request-meta-item">
                                    <div class="tenant-request-meta-label">Current Request</div>
                                    <div class="tenant-request-meta-value">
                                        <span class="badge status-pill {{ $currentRequestBadgeClass }}">{{ $currentRequestState }}</span>
                                        <div class="small text-muted mt-1">
                                            Type: {{ $currentRequestType }}
                                            @if($currentRequestAmount !== null)
                                                · Amount: Rp {{ number_format($currentRequestAmount, 0, ',', '.') }}
                                            @endif
                                            @if($currentRequestDueAt)
                                                · Due: {{ $currentRequestDueAt->format('Y-m-d H:i') }}
                                            @endif
                                            @if($currentRequestDueAt)
                                                @if($isInitialExpired || $isExtensionExpired)
                                                    <span class="badge bg-danger">Expired</span>
                                                @elseif($isInitialDueSoon || $isExtensionDueSoon)
                                                    <span class="badge bg-warning text-dark">Due Soon</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($requestActionBy)
                                    <div class="tenant-request-meta-item">
                                        <div class="tenant-request-meta-label">Last Action</div>
                                        <div class="tenant-request-meta-value">
                                            {{ $requestActionBy }}
                                            @if($requestActionAt)
                                                · {{ $requestActionAt->format('Y-m-d H:i') }}
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="tenant-request-actions">
                                <div class="tenant-request-actions-fixed">
                                    <a href="/tenant/requests/{{ $r->id }}" class="btn btn-outline-primary btn-sm">Rental Detail</a>
                                    <a href="/messages/{{ $r->id }}" class="btn btn-info btn-sm">Chat Agent</a>
                                </div>
                                <div class="tenant-request-actions-state">
                                    @if(in_array($r->status, ['pending_review', 'awaiting_payment'], true))
                                        <form method="POST" action="/tenant/requests/{{ $r->id }}/cancel" class="d-inline">
                                            @csrf
                                            <button class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to cancel this request?')">
                                                Cancel Request
                                            </button>
                                        </form>
                                    @endif

                                    @if($payTransaction)
                                        <form method="POST" action="/tenant/transactions/{{ $payTransaction->id }}/pay" class="d-inline">
                                            @csrf
                                            <button class="btn btn-success btn-sm">Pay Now</button>
                                        </form>
                                    @endif

                                    @if($r->status === 'paid' && $r->transaction && $r->transaction->status === 'paid')
                                        @if($activeContract && $activeContract->status === 'active' && (!$latestExtension || in_array($latestExtension->status, ['paid', 'rejected', 'expired'], true)))
                                            <form method="POST" action="/tenant/contracts/{{ $activeContract->id }}/extend" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="months_requested" value="1">
                                                <button class="btn btn-warning btn-sm"
                                                        onclick="return confirm('Are you sure you want to request an extension?')">
                                                    Request Extend
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif
</div>

<script>
(function () {
    const searchInput = document.getElementById('tenant-requests-search');
    if (searchInput) {
        let submitTimer = null;
        searchInput.addEventListener('input', () => {
            clearTimeout(submitTimer);
            submitTimer = setTimeout(() => {
                searchInput.form?.requestSubmit();
            }, 300);
        });
    }

    const sortSelect = document.querySelector('select[name="sort"]');
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            sortSelect.form?.requestSubmit();
        });
    }
})();
</script>

@endsection
