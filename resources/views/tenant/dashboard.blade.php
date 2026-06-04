@extends('layouts.app')

@section('content')
<div class="container">

    <h1 class="mb-4">Tenant Dashboard</h1>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/calendar-check-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Active Contracts</p>
                            <h3 class="mb-0">{{ number_format((int) $activeContracts) }}</h3>
                            <small class="text-muted">Ongoing rental agreements</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/pend-req-card-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Pending Requests</p>
                            <h3 class="mb-0">{{ number_format((int) $pendingRequests) }}</h3>
                            <small class="text-muted">Awaiting review or payment</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/notification-bell-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Expiring Soon</p>
                            <h3 class="mb-0">{{ number_format((int) $expiringSoon) }}</h3>
                            <small class="text-muted">Contracts ending within 30 days</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/cash-stack-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Total Spent</p>
                            <h3 class="mb-0">Rp{{ number_format((float) ($totalSpent ?? 0), 0, ',', '.') }}</h3>
                            <small class="text-muted">All paid transactions</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-2">Recent Requests</h5>
                    @if ($recentRequests->isEmpty())
                        <p class="text-muted mb-0">No requests yet.</p>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($recentRequests as $req)
                                <div class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center">
                                    <div class="me-2 text-truncate">
                                        <div class="fw-medium small">{{ $req->property->title }}</div>
                                        <small class="text-muted">{{ $req->created_at->format('d M Y') }}</small>
                                    </div>
                                    <span class="badge bg-{{ $req->status === 'paid' ? 'success' : ($req->status === 'pending_review' || $req->status === 'awaiting_payment' ? 'warning' : 'secondary') }} rounded-pill flex-shrink-0">
                                        {{ str_replace('_', ' ', $req->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <a href="/tenant/properties" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/search-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">Browse Properties</p>
                                <small class="text-muted">Find available houses</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="/tenant/contracts" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/calendar-check-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">My Contracts</p>
                                <small class="text-muted">View rental agreements</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="/tenant/requests" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/pend-req-card-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">My Requests</p>
                                <small class="text-muted">Check application status</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="/profile" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/profile-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">My Profile</p>
                                <small class="text-muted">View & update profile</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

</div>
@endsection