@extends('layouts.app')

@section('content')
@php($activeContract = $rentalRequest->activeContract)
@php($extensions = $activeContract ? $activeContract->extensions->sortByDesc('created_at') : collect())
@php($latestExtension = $extensions->first())
@php($isLatestExtensionCancelledByTenant = $latestExtension && $latestExtension->status === 'cancelled_by_tenant')
@php($latestExtensionDueAt = data_get($latestExtension, 'payment_due_at'))
@php($isExtensionAwaitingPayment = $latestExtension && $latestExtension->status === 'awaiting_payment')
@php($isExtensionExpired = $isExtensionAwaitingPayment && $latestExtensionDueAt && $latestExtensionDueAt->isPast())
@php($isExtensionDueSoon = $isExtensionAwaitingPayment && $latestExtensionDueAt && $latestExtensionDueAt->isFuture() && $latestExtensionDueAt->lte(now()->copy()->addDay()))
@php($isRequestExpired = $rentalRequest->is_initial_payment_expired)
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
    @media (max-width: 991px) {
        .kv-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container">
    <h2 class="mb-4">Rental Detail</h2>

    <div class="mb-3 d-flex gap-2">
        <a href="/messages/{{ $rentalRequest->id }}" class="btn btn-outline-info btn-sm">Chat with Agent</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Property</h5>
            <div class="kv-grid mb-3">
                <div>
                    <div class="kv-label">Title</div>
                    <div class="kv-value">{{ $rentalRequest->property->title }}</div>
                </div>
                <div>
                    <div class="kv-label">Agent</div>
                    <div class="kv-value">{{ $rentalRequest->property->agent->name }}</div>
                </div>
                <div>
                    <div class="kv-label">Address</div>
                    <div class="kv-value">{{ $rentalRequest->property->address }}</div>
                </div>
                <div>
                    <div class="kv-label">Monthly Rent</div>
                    <div class="kv-value">Rp {{ number_format((float) $rentalRequest->property->rent_price, 0, ',', '.') }}/month</div>
                </div>
                <div>
                    <div class="kv-label">Bedrooms</div>
                    <div class="kv-value">{{ $rentalRequest->property->bedrooms }}</div>
                </div>
                <div>
                    <div class="kv-label">Bathrooms</div>
                    <div class="kv-value">{{ (int) $rentalRequest->property->bathrooms }}</div>
                </div>
            </div>
            <a href="/tenant/properties/{{ $rentalRequest->property->id }}" class="btn btn-outline-secondary btn-sm">Open Property Detail</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Request Workflow</h5>
            <div class="kv-grid">
                <div>
                    <div class="kv-label">Current Status</div>
                    <div class="kv-value">
                @if($rentalRequest->lifecycle_status === 'pending_review')
                    <span class="badge bg-warning text-dark">REQUEST UNDER REVIEW</span>
                @elseif($rentalRequest->lifecycle_status === 'expired')
                    <span class="badge bg-dark">PAYMENT EXPIRED</span>
                @elseif($rentalRequest->status === 'awaiting_payment')
                    <span class="badge bg-info text-dark">AWAITING INITIAL PAYMENT</span>
                @elseif($rentalRequest->status === 'paid')
                    @if($activeContract && $activeContract->status === 'active')
                        <span class="badge bg-success">ACTIVE LEASE</span>
                    @else
                        <span class="badge bg-primary">REQUEST APPROVED (PAID)</span>
                    @endif
                @elseif($rentalRequest->status === 'cancelled_lost')
                    <span class="badge bg-dark">PAYMENT EXPIRED</span>
                @elseif($rentalRequest->status === 'cancelled_by_tenant')
                    <span class="badge bg-dark">REQUEST CANCELLED BY TENANT</span>
                @elseif($rentalRequest->status === 'cancelled_by_agent')
                    <span class="badge bg-dark">REQUEST CANCELLED BY AGENT</span>
                @elseif($rentalRequest->status === 'rejected')
                    <span class="badge bg-danger">REQUEST REJECTED BY AGENT</span>
                @else
                    <span class="badge bg-secondary">{{ strtoupper($rentalRequest->status) }}</span>
                @endif
                    </div>
                </div>
                <div>
                    <div class="kv-label">Applied At</div>
                    <div class="kv-value">{{ $rentalRequest->created_at->format('Y-m-d H:i') }}</div>
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
                @if($rentalRequest->awaiting_payment_at)
                    <div>
                        <div class="kv-label">Moved to Payment Stage</div>
                        <div class="kv-value">{{ $rentalRequest->awaiting_payment_at }}</div>
                    </div>
                @endif
            </div>
            @php($isAwaitingPayment = $rentalRequest->status === 'awaiting_payment')
            @php($initialDueAt = $rentalRequest->payment_due_at)
            @php($isInitialExpired = $isAwaitingPayment && $initialDueAt && $initialDueAt->isPast())
            <div class="kv-grid mt-3">
                <div>
                    <div class="kv-label">Initial Payment Due</div>
                    <div class="kv-value">
                        @if($isAwaitingPayment && $initialDueAt)
                            {{ $initialDueAt->format('Y-m-d H:i') }}
                            @if($isInitialExpired)
                                <span class="badge bg-danger">Expired</span>
                            @elseif($initialDueAt->isFuture() && $initialDueAt->lte(now()->copy()->addDay()))
                                <span class="badge bg-warning text-dark">Due Soon</span>
                            @endif
                        @elseif($rentalRequest->status === 'paid')
                            Paid
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div>
                    <div class="kv-label">Extension Payment Due</div>
                    <div class="kv-value">
                        @if($isExtensionAwaitingPayment && $latestExtensionDueAt)
                            {{ $latestExtensionDueAt->format('Y-m-d H:i') }}
                            @if($isExtensionExpired)
                                <span class="badge bg-danger">Expired</span>
                            @elseif($isExtensionDueSoon)
                                <span class="badge bg-warning text-dark">Due Soon</span>
                            @endif
                        @elseif($latestExtension && $latestExtension->status === 'paid')
                            Paid
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>
            @if($rentalRequest->paid_at)
                <p class="mb-0"><strong>Paid At:</strong> {{ $rentalRequest->paid_at }}</p>
            @else
                <p class="mb-0 text-muted">
                    @if($rentalRequest->lifecycle_status === 'pending_review')
                        Waiting for agent approval step.
                    @elseif($rentalRequest->lifecycle_status === 'awaiting_payment')
                        You currently hold payment lock for this property.
                        @if($isRequestExpired)
                            Payment deadline has passed.
                        @endif
                    @elseif($rentalRequest->lifecycle_status === 'expired')
                        This request is closed because the payment deadline has passed.
                    @endif
                </p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Payment & Contract</h5>

            @if($rentalRequest->transaction)
                <div class="kv-grid mb-3">
                    <div>
                        <div class="kv-label">Initial Amount</div>
                        <div class="kv-value">Rp {{ number_format((float) $rentalRequest->transaction->amount, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="kv-label">Initial Payment Status</div>
                        <div class="kv-value">
                            @if($rentalRequest->transaction->status === 'paid')
                                <span class="badge bg-success">PAID</span>
                            @elseif($rentalRequest->transaction->status === 'unpaid')
                                <span class="badge bg-warning text-dark">UNPAID</span>
                            @else
                                <span class="badge bg-secondary">{{ strtoupper($rentalRequest->transaction->status) }}</span>
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
                <p class="text-muted mb-3">Contract will appear after initial payment is completed.</p>
            @endif

            <div class="d-flex flex-wrap gap-2">
                @if(in_array($rentalRequest->lifecycle_status, ['pending_review', 'awaiting_payment'], true))
                    <form method="POST" action="/tenant/requests/{{ $rentalRequest->id }}/cancel" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Are you sure you want to cancel this request?')">
                            Cancel Request
                        </button>
                    </form>
                @endif

                @if($rentalRequest->status === 'awaiting_payment' && !$isRequestExpired && $rentalRequest->transaction && $rentalRequest->transaction->status === 'unpaid')
                    <form method="POST" action="/tenant/transactions/{{ $rentalRequest->transaction->id }}/pay" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-success">Pay First Month</button>
                    </form>
                @endif

                @if($rentalRequest->status === 'paid' && $rentalRequest->transaction && $rentalRequest->transaction->status === 'paid')
                    <a href="{{ url('/tenant/contracts?q=' . urlencode($rentalRequest->property->title ?? '')) }}" class="btn btn-sm btn-primary">
                        View All Contracts →
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if($activeContract)
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Extension Status</h5>

                @if($latestExtension)
                    <div class="kv-grid mb-3">
                        <div>
                            <div class="kv-label">Latest Request</div>
                            <div class="kv-value">{{ $latestExtension->created_at->format('Y-m-d H:i') }}</div>
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
                            <div class="kv-label">Status</div>
                            <div class="kv-value">
                        @if($latestExtension->status === 'pending')
                            <span class="badge bg-warning text-dark">Pending Approval</span>
                        @elseif($latestExtension->status === 'awaiting_payment')
                            <span class="badge bg-info text-dark">Awaiting Payment</span>
                        @elseif($isLatestExtensionCancelledByTenant)
                            <span class="badge bg-dark">Cancelled by Tenant</span>
                        @elseif($latestExtension->status === 'paid')
                            <span class="badge bg-success">Paid</span>
                        @elseif($latestExtension->status === 'cancelled_by_tenant')
                            <span class="badge bg-dark">Cancelled by Tenant</span>
                        @elseif($latestExtension->status === 'rejected')
                            <span class="badge bg-danger">Rejected by Agent</span>
                        @elseif($latestExtension->status === 'expired')
                            <span class="badge bg-dark">Payment Expired</span>
                        @else
                            <span class="badge bg-secondary">{{ strtoupper($latestExtension->status) }}</span>
                        @endif
                            </div>
                        </div>
                    </div>

                    @if($latestExtension->status === 'cancelled_by_tenant')
                        <p class="mb-3">
                            <strong>Cancelled At:</strong>
                            {{ $latestExtension->cancelled_at?->format('Y-m-d H:i') ?: '-' }}
                        </p>
                    @endif

                    @php($latestExtTx = $latestExtension->transaction)
                    @if($latestExtension->status === 'pending')
                        <form method="POST" action="/tenant/contract-extensions/{{ $latestExtension->id }}/cancel" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to cancel this extension request?')">
                                Cancel Extension
                            </button>
                        </form>
                    @elseif($latestExtension->status === 'awaiting_payment' && !$isExtensionExpired && $latestExtTx && $latestExtTx->status === 'unpaid')
                        <form method="POST" action="/tenant/transactions/{{ $latestExtTx->id }}/pay" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success">Pay Extension</button>
                        </form>
                        <form method="POST" action="/tenant/contract-extensions/{{ $latestExtension->id }}/cancel" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to cancel this extension request?')">
                                Cancel Extension
                            </button>
                        </form>
                    @elseif($latestExtTx && $latestExtTx->status === 'paid')
                        <a href="{{ url('/tenant/contracts?q=' . urlencode($rentalRequest->property->title ?? '')) }}" class="btn btn-sm btn-primary">
                            View All Contracts →
                        </a>
                    @endif
                @else
                    <p class="text-muted mb-0">No extension request yet.</p>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
