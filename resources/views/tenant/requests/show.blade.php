@extends('layouts.app')

@section('content')
@php($activeContract = $activeContract ?? $rentalRequest->activeContract)
@php($extensions = $extensions ?? ($activeContract ? $activeContract->extensions->sortByDesc('created_at') : collect()))
@php($latestExtension = $latestExtension ?? $extensions->first())
@php($initialTx = $rentalRequest->transaction)
@php($dueAt = $rentalRequest->payment_due_at)
@php($isAwaitingPayment = $rentalRequest->status === 'awaiting_payment')
@php($isInitialExpired = $rentalRequest->is_initial_payment_expired)
@php($isInitialDueSoon = $isAwaitingPayment && $dueAt && $dueAt->isFuture() && $dueAt->lte(now()->copy()->addDay()))
@php($latestExtTx = $latestExtension ? $latestExtension->transaction : null)
@php($latestExtDueAt = $latestExtension ? $latestExtension->payment_due_at : null)
@php($isExtAwaitingPayment = $latestExtension && $latestExtension->status === 'awaiting_payment')
@php($isExtExpired = $isExtAwaitingPayment && $latestExtDueAt && $latestExtDueAt->isPast())
@php($isExtDueSoon = $isExtAwaitingPayment && $latestExtDueAt && $latestExtDueAt->isFuture() && $latestExtDueAt->lte(now()->copy()->addDay()))
@php($requestActionBy = null)
@php($requestActionAt = null)
@if($rentalRequest->status === 'rejected')
    @php($requestActionBy = 'Agent')
    @php($requestActionAt = $rentalRequest->rejected_at)
@elseif($rentalRequest->status === 'cancelled_by_tenant')
    @php($requestActionBy = 'Tenant')
    @php($requestActionAt = $rentalRequest->cancelled_at)
@elseif($rentalRequest->status === 'cancelled_by_agent')
    @php($requestActionBy = 'Agent')
    @php($requestActionAt = $rentalRequest->cancelled_at)
@elseif($rentalRequest->status === 'cancelled_lost')
    @php($requestActionBy = 'System')
    @php($requestActionAt = $rentalRequest->cancelled_at)
@elseif($rentalRequest->lifecycle_status === 'expired')
    @php($requestActionBy = 'System')
    @php($requestActionAt = $rentalRequest->payment_due_at)
@endif

<style>
    .tenant-detail-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .tenant-detail-title {
        margin: 0;
        font-weight: 800;
        font-size: 1.35rem;
        color: #0f172a;
        line-height: 1.2;
    }
    .tenant-detail-sub {
        margin-top: 4px;
        color: #64748b;
        font-size: .95rem;
    }
    .tenant-detail-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .tenant-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    .tenant-sticky {
        position: sticky;
        top: 16px;
    }
    .kv-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 14px;
    }
    .kv-label {
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
        margin-bottom: 2px;
    }
    .kv-value {
        color: #0f172a;
        font-size: .95rem;
        font-weight: 600;
        line-height: 1.3;
        word-break: break-word;
    }
    .next-action-stack {
        display: grid;
        gap: 8px;
    }
    @media (max-width: 991px) {
        .tenant-detail-grid {
            grid-template-columns: 1fr;
        }
        .tenant-sticky {
            position: static;
        }
    }
</style>

<div class="container">
    <div class="tenant-detail-header">
        <div>
            <h2 class="tenant-detail-title">Rental Detail</h2>
            <div class="tenant-detail-sub">
                <strong>{{ $rentalRequest->property->title ?? 'Property' }}</strong>
                <span class="text-muted">·</span>
                Agent: {{ $rentalRequest->property->agent?->name ?? '-' }}
            </div>
        </div>
        <div class="tenant-detail-actions">
            <a href="/messages/{{ $rentalRequest->id }}" class="btn btn-outline-info btn-sm">Chat with Agent</a>
            <a href="/tenant/properties/{{ $rentalRequest->property_id }}" class="btn btn-outline-secondary btn-sm">Open Property</a>
        </div>
    </div>

    <div class="tenant-detail-grid">
        <div>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">Request Summary</h5>
                    <div class="kv-grid">
                        <div>
                            <div class="kv-label">Property</div>
                            <div class="kv-value">{{ $rentalRequest->property->title ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="kv-label">Agent</div>
                            <div class="kv-value">{{ $rentalRequest->property->agent?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="kv-label">Submitted At</div>
                            <div class="kv-value">{{ $rentalRequest->created_at?->format('Y-m-d H:i') ?: '-' }}</div>
                        </div>
                        <div>
                            <div class="kv-label">Monthly Rent</div>
                            <div class="kv-value">Rp {{ number_format((float) data_get($rentalRequest, 'property.rent_price', 0), 0, ',', '.') }}/month</div>
                        </div>
                        <div>
                            <div class="kv-label">Status</div>
                            <div class="kv-value">
                                @php($statusText = match ($rentalRequest->lifecycle_status) {
                                    'pending_review' => 'REQUEST UNDER REVIEW',
                                    'awaiting_payment' => 'AWAITING INITIAL PAYMENT',
                                    'paid' => ($activeContract && $activeContract->status === 'active' ? 'ACTIVE LEASE' : 'APPROVED (PAID)'),
                                    'expired' => 'PAYMENT EXPIRED',
                                    'cancelled_lost' => 'PAYMENT EXPIRED',
                                    'cancelled_by_tenant' => 'CANCELLED BY TENANT',
                                    'cancelled_by_agent' => 'CANCELLED BY AGENT',
                                    'rejected' => 'REJECTED',
                                    default => strtoupper((string) $rentalRequest->lifecycle_status),
                                })
                                @php($statusClass = match ($rentalRequest->lifecycle_status) {
                                    'pending_review' => 'bg-warning text-dark',
                                    'awaiting_payment' => 'bg-info text-dark',
                                    'expired' => 'bg-dark',
                                    'paid' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    'cancelled_lost', 'cancelled_by_tenant', 'cancelled_by_agent' => 'bg-dark',
                                    default => 'bg-secondary',
                                })
                                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="kv-label">Initial Payment Due</div>
                            <div class="kv-value">
                                @if($isAwaitingPayment && $dueAt)
                                    {{ $dueAt->format('Y-m-d H:i') }}
                                    @if($isInitialExpired)
                                        <span class="badge bg-danger">Expired</span>
                                    @elseif($isInitialDueSoon)
                                        <span class="badge bg-warning text-dark">Due Soon</span>
                                    @endif
                                @elseif($rentalRequest->status === 'paid')
                                    Paid
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        @if($requestActionBy)
                            <div>
                                <div class="kv-label">Action By</div>
                                <div class="kv-value">{{ $requestActionBy }}</div>
                            </div>
                        @endif
                        @if($requestActionAt)
                            <div>
                                <div class="kv-label">Action At</div>
                                <div class="kv-value">{{ $requestActionAt->format('Y-m-d H:i') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">Payment & Contract</h5>

                    @if($initialTx)
                        <div class="kv-grid mb-3">
                            <div>
                                <div class="kv-label">Initial Amount</div>
                                <div class="kv-value">Rp {{ number_format((float) $initialTx->amount, 0, ',', '.') }}</div>
                            </div>
                            <div>
                                <div class="kv-label">Initial Payment Status</div>
                                <div class="kv-value">
                                    @if($initialTx->status === 'paid')
                                        <span class="badge bg-success">PAID</span>
                                    @elseif($initialTx->status === 'unpaid')
                                        <span class="badge bg-warning text-dark">UNPAID</span>
                                    @else
                                        <span class="badge bg-secondary">{{ strtoupper($initialTx->status) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($activeContract)
                        <div class="border rounded p-3 mb-3">
                            <div class="kv-grid">
                                <div>
                                    <div class="kv-label">Contract Status</div>
                                    <div class="kv-value">{{ strtoupper($activeContract->status) }}</div>
                                </div>
                                <div>
                                    <div class="kv-label">Start Date</div>
                                    <div class="kv-value">{{ $activeContract->start_date ?: '-' }}</div>
                                </div>
                                <div>
                                    <div class="kv-label">End Date</div>
                                    <div class="kv-value">{{ $activeContract->end_date ?: '-' }}</div>
                                </div>
                                <div>
                                    <div class="kv-label">Monthly Rent</div>
                                    <div class="kv-value">Rp {{ number_format((float) ($activeContract->monthly_rent ?? 0), 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted">
                            Contract will appear after initial payment is completed.
                        </div>
                    @endif

                    @if($rentalRequest->status === 'paid' && $initialTx && $initialTx->status === 'paid')
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="{{ url('/tenant/contracts?q=' . urlencode($rentalRequest->property->title ?? '')) }}" class="btn btn-sm btn-primary">
                                View All Contracts →
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">Extension</h5>
                    @if($latestExtension)
                        @php($extStatusText = match ($latestExtension->status) {
                            'pending' => 'Pending Approval',
                            'awaiting_payment' => 'Awaiting Payment',
                            'paid' => 'Paid',
                            'cancelled_by_tenant' => 'Cancelled by Tenant',
                            'rejected' => 'Rejected',
                            'expired' => 'Expired',
                            default => strtoupper((string) $latestExtension->status),
                        })
                        @php($extStatusClass = match ($latestExtension->status) {
                            'pending' => 'bg-warning text-dark',
                            'awaiting_payment' => 'bg-info text-dark',
                            'paid' => 'bg-success',
                            'rejected' => 'bg-danger',
                            'cancelled_by_tenant', 'expired' => 'bg-dark',
                            default => 'bg-secondary',
                        })
                        <div class="kv-grid">
                            <div>
                                <div class="kv-label">Requested At</div>
                                <div class="kv-value">{{ $latestExtension->created_at?->format('Y-m-d H:i') ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="kv-label">Status</div>
                                <div class="kv-value"><span class="badge {{ $extStatusClass }}">{{ $extStatusText }}</span></div>
                            </div>
                            <div>
                                <div class="kv-label">Months</div>
                                <div class="kv-value">{{ $latestExtension->months_requested }}</div>
                            </div>
                            <div>
                                <div class="kv-label">Amount</div>
                                <div class="kv-value">Rp {{ number_format((float) $latestExtension->amount, 0, ',', '.') }}</div>
                            </div>
                            <div>
                                <div class="kv-label">Period</div>
                                <div class="kv-value">{{ $latestExtension->old_end_date }} → {{ $latestExtension->new_end_date }}</div>
                            </div>
                            <div>
                                <div class="kv-label">Payment Due</div>
                                <div class="kv-value">
                                    @if($isExtAwaitingPayment && $latestExtDueAt)
                                        {{ $latestExtDueAt->format('Y-m-d H:i') }}
                                        @if($isExtExpired)
                                            <span class="badge bg-danger">Expired</span>
                                        @elseif($isExtDueSoon)
                                            <span class="badge bg-warning text-dark">Due Soon</span>
                                        @endif
                                    @elseif($latestExtension->status === 'paid')
                                        Paid
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted">No extension request yet.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="tenant-sticky">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">Next Action</h5>
                    <div class="next-action-stack">
                        @php($hasAction = false)

                        @if(in_array($rentalRequest->lifecycle_status, ['pending_review', 'awaiting_payment'], true))
                            @php($hasAction = true)
                            <form method="POST" action="/tenant/requests/{{ $rentalRequest->id }}/cancel">
                                @csrf
                                <button class="btn btn-outline-danger w-100"
                                        onclick="return confirm('Are you sure you want to cancel this request?')">
                                    Cancel Request
                                </button>
                            </form>
                        @endif

                        @if($rentalRequest->status === 'awaiting_payment' && !$isInitialExpired && $initialTx && $initialTx->status === 'unpaid')
                            @php($hasAction = true)
                            <form method="POST" action="/tenant/transactions/{{ $initialTx->id }}/pay">
                                @csrf
                                <button class="btn btn-success w-100">Pay First Month</button>
                            </form>
                        @endif

                        @if($latestExtension && $latestExtension->status === 'pending')
                            @php($hasAction = true)
                            <form method="POST" action="/tenant/contract-extensions/{{ $latestExtension->id }}/cancel">
                                @csrf
                                <button class="btn btn-outline-danger w-100"
                                        onclick="return confirm('Are you sure you want to cancel this extension request?')">
                                    Cancel Extension
                                </button>
                            </form>
                        @elseif($latestExtension && $latestExtension->status === 'awaiting_payment' && !$isExtExpired && $latestExtTx && $latestExtTx->status === 'unpaid')
                            @php($hasAction = true)
                            <form method="POST" action="/tenant/transactions/{{ $latestExtTx->id }}/pay">
                                @csrf
                                <button class="btn btn-success w-100">Pay Extension</button>
                            </form>
                            <form method="POST" action="/tenant/contract-extensions/{{ $latestExtension->id }}/cancel">
                                @csrf
                                <button class="btn btn-outline-danger w-100"
                                        onclick="return confirm('Are you sure you want to cancel this extension request?')">
                                    Cancel Extension
                                </button>
                            </form>
                        @elseif($activeContract && $activeContract->status === 'active' && (!$latestExtension || in_array($latestExtension->status, ['paid', 'rejected', 'expired', 'cancelled_by_tenant'], true)))
                            @php($hasAction = true)
                            <form method="POST" action="/tenant/contracts/{{ $activeContract->id }}/extend">
                                @csrf
                                <input type="hidden" name="months_requested" value="1">
                                <button class="btn btn-warning w-100"
                                        onclick="return confirm('Are you sure you want to request an extension?')">
                                    Request Extend
                                </button>
                            </form>
                        @endif

                        @if(!$hasAction)
                            <div class="text-muted">No actions available.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
