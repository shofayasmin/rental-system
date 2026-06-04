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
    .properties-grid {
        display: grid;
        grid-template-columns: 360px 1fr;
        gap: 18px;
        align-items: start;
    }
    .properties-list {
        max-height: 70vh;
        overflow-y: auto;
    }
    .property-pill {
        border-radius: 12px;
    }
    .request-card-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 10px;
    }
    .request-card-meta {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px 14px;
        margin-bottom: 12px;
    }
    .request-meta-label {
        color: #64748b;
        font-size: .78rem;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .request-meta-value {
        color: #0f172a;
        font-size: .92rem;
        font-weight: 600;
        word-break: break-word;
    }
    .request-actions {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        flex-wrap: wrap;
    }
    .request-actions-left,
    .request-actions-right {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    @media (max-width: 991px) {
        .properties-grid {
            grid-template-columns: 1fr;
        }
        .request-card-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 767px) {
        .agent-requests-tools {
            justify-content: stretch;
        }
        .agent-requests-tools .search-wrap,
        .agent-requests-tools .form-control,
        .agent-requests-tools .form-select,
        .agent-requests-tools .btn {
            width: 100%;
            max-width: none;
        }
        .request-card-meta {
            grid-template-columns: 1fr;
        }
        .request-actions-left,
        .request-actions-right {
            width: 100%;
        }
        .request-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="container">
    <h2 class="mb-3">Rental Requests</h2>

    <div class="properties-grid">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong>Properties with Requests</strong>
            </div>
            <div class="list-group list-group-flush properties-list">
                @forelse($properties as $property)
                    @php($isActive = (int) $selectedPropertyId === (int) $property->id)
                    <a
                        href="{{ request()->fullUrlWithQuery(['property' => $property->id, 'applicants_page' => 1]) }}"
                        class="list-group-item list-group-item-action {{ $isActive ? 'active' : '' }}"
                    >
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">{{ $property->title }}</div>
                                <div class="small {{ $isActive ? 'text-white-50' : 'text-muted' }}">
                                    {{ $property->address ?: 'Address not set' }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-1">
                            <span class="badge text-bg-warning text-dark">Pending: {{ $property->pending_review_count }}</span>
                            <span class="badge text-bg-info text-dark">Awaiting: {{ $property->awaiting_payment_count }}</span>
                            <span class="badge text-bg-success">Paid: {{ $property->paid_count }}</span>
                        </div>

                        <div class="mt-2 small {{ $isActive ? 'text-white-50' : 'text-muted' }}">
                            Total: {{ $property->total_requests_count }}
                            @if($property->last_request_activity_at)
                                · Last: {{ \Carbon\Carbon::parse($property->last_request_activity_at)->format('Y-m-d H:i') }}
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">No rental requests available.</div>
                @endforelse
            </div>
            <div class="card-footer">
                {{ $properties->links() }}
            </div>
        </div>

        <div>
            @if(!$selectedProperty)
                <div class="alert alert-secondary">Select a property to view applicants.</div>
            @else
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div>
                                <h5 class="mb-1">{{ $selectedProperty->title }}</h5>
                                <div class="text-muted small">{{ $selectedProperty->address ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <span class="badge text-bg-warning text-dark">Pending: {{ $selectedProperty->pending_review_count }}</span>
                            <span class="badge text-bg-info text-dark">Awaiting Payment: {{ $selectedProperty->awaiting_payment_count }}</span>
                            <span class="badge text-bg-success">Paid: {{ $selectedProperty->paid_count }}</span>
                            <span class="badge text-bg-secondary">Total: {{ $selectedProperty->total_requests_count }}</span>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <form method="GET" action="/agent/rental-requests" class="row g-2 align-items-end">
                            <input type="hidden" name="property" value="{{ $selectedProperty->id }}">

                            <div class="col-md-4">
                                <label class="form-label mb-1">Applicant Status</label>
                                <select name="applicant_status" class="form-select">
                                    <option value="">All</option>
                                    @foreach(['pending_review','awaiting_payment','paid','rejected','cancelled_by_tenant','cancelled_by_agent','cancelled_lost'] as $statusOption)
                                        <option value="{{ $statusOption }}" {{ $applicantStatus === $statusOption ? 'selected' : '' }}>
                                            {{ strtoupper($statusOption) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label mb-1">Search Tenant</label>
                                <input
                                    type="text"
                                    name="q"
                                    value="{{ $tenantSearch }}"
                                    class="form-control"
                                    placeholder="Tenant name"
                                >
                            </div>

                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="needs_action" value="1" id="needs-action" {{ $needsActionOnly ? 'checked' : '' }}>
                                    <label class="form-check-label" for="needs-action">Needs action only</label>
                                </div>
                            </div>

                            <div class="col-md-1 d-grid">
                                <button class="btn btn-primary">Go</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 24px;">#</th>
                                    <th>Tenant</th>
                                    <th>Submitted At</th>
                                    <th>Status</th>
                                    <th>Payment Due</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($applicants && $applicants->count())
                                    @foreach($applicants as $idx => $req)
                                        @php($hasAnotherActivePayment = (bool) ($hasActiveAwaitingPaymentByProperty[$req->property_id] ?? false) && $req->status !== 'awaiting_payment')
                                        <tr>
                                            <td>{{ ($applicants->firstItem() ?? 1) + $idx }}</td>
                                            <td>{{ $req->tenant->name }}</td>
                                            <td>{{ $req->created_at?->format('Y-m-d H:i') }}</td>
                                            <td>
                                                @if($req->status === 'pending_review')
                                                    <span class="badge bg-warning text-dark">PENDING REVIEW</span>
                                                @elseif($req->status === 'awaiting_payment')
                                                    <span class="badge bg-info text-dark">AWAITING PAYMENT</span>
                                                @elseif($req->status === 'paid')
                                                    <span class="badge bg-success">PAID</span>
                                                @elseif($req->status === 'cancelled_by_tenant')
                                                    <span class="badge bg-dark">CANCELLED BY TENANT</span>
                                                @elseif($req->status === 'cancelled_by_agent')
                                                    <span class="badge bg-dark">CANCELLED BY AGENT</span>
                                                @elseif($req->status === 'cancelled_lost')
                                                    <span class="badge bg-dark">CANCELLED (LOST)</span>
                                                @else
                                                    <span class="badge bg-danger">REJECTED</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($req->payment_due_at)
                                                    {{ $req->payment_due_at->format('Y-m-d H:i') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($req->status === 'pending_review')
                                                    @if(!$hasAnotherActivePayment)
                                                        <form method="POST" action="/agent/rental-requests/{{ $req->id }}/approve" style="display:inline">
                                                            @csrf
                                                            <button class="btn btn-sm btn-success">Approve</button>
                                                        </form>
                                                        <form method="POST" action="/agent/rental-requests/{{ $req->id }}/reject" style="display:inline">
                                                            @csrf
                                                            <button class="btn btn-sm btn-danger">Reject</button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted">Waiting current payment lock holder</span>
                                                    @endif
                                                @elseif($req->status === 'awaiting_payment')
                                                    <form method="POST" action="/agent/rental-requests/{{ $req->id }}/cancel-lock" style="display:inline">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-danger">Cancel Lock</button>
                                                    </form>
                                                    <a href="/messages/{{ $req->id }}" class="btn btn-sm btn-info">Open Chat</a>
                                                @elseif(in_array($req->status, ['rejected', 'cancelled_by_tenant', 'cancelled_by_agent', 'cancelled_lost'], true))
                                                    <span class="badge bg-secondary">Closed</span>
                                                @else
                                                    <a href="/messages/{{ $req->id }}" class="btn btn-sm btn-info">Open Chat</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No applicants found for current filter.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @if($applicants)
                        <div class="card-footer">
                            {{ $applicants->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <h2 class="mb-3 mt-5">Extension Requests</h2>

    @if($pendingExtensions->isEmpty())
        <div class="alert alert-secondary">
            No pending extension requests.
        </div>
    @else
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Property</th>
                    <th>Tenant</th>
                    <th>Current End</th>
                    <th>Months</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingExtensions as $extension)
                    <tr>
                        <td>{{ data_get($extension, 'contract.rentalRequest.property.title', '-') }}</td>
                        <td>{{ data_get($extension, 'contract.rentalRequest.tenant.name', '-') }}</td>
                        <td>{{ $extension->old_end_date }}</td>
                        <td>{{ $extension->months_requested }}</td>
                        <td>{{ $extension->amount }}</td>
                        <td>
                            <form method="POST" action="/agent/contract-extensions/{{ $extension->id }}/approve" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <form method="POST" action="/agent/contract-extensions/{{ $extension->id }}/reject" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
