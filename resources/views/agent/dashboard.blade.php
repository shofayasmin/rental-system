@extends('layouts.app')

@section('content')
<div class="container">
    <style>
        .chart-wrap {
            position: relative;
            width: 100%;
        }

        .chart-wrap-sm {
            height: 300px;
        }
    </style>

    <h1 class="mb-4">Agent Dashboard</h1>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/myprop-card-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">My Properties</p>
                            <h3 class="mb-0">{{ number_format((int) $totalProperties) }}</h3>
                            <small class="text-muted">{{ number_format((int) $toLetProperties) }} to-let / {{ number_format((int) $rentedProperties) }} rented / {{ number_format((int) $maintenanceProperties) }} maintenance</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/pie-chart-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Occupancy Rate</p>
                            <h3 class="mb-0">{{ number_format((float) ($occupancyRate ?? 0), 1) }}%</h3>
                            <small class="text-muted">{{ number_format((int) $rentedProperties) }} rented / {{ number_format((int) $totalProperties) }} properties</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/calendar-check-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Total Rentals (all time)</p>
                            <h3 class="mb-0">{{ number_format((int) $totalRentalsAllTime) }}</h3>
                            <small class="text-muted">All paid requests since start</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/pend-req-card-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Pending Requests</p>
                            <h3 class="mb-0">{{ number_format((int) $pendingRequests) }}</h3>
                            <small class="text-muted">Awaiting your review</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/cash-stack-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Total Revenue</p>
                            <h3 class="mb-0">Rp{{ number_format((float) ($totalRevenue ?? 0), 0, ',', '.') }}</h3>
                            <small class="text-muted">From paid transactions in selected period</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/notification-bell-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">New Bookings (30 days)</p>
                            <h3 class="mb-0">{{ number_format((int) $newBookings) }}</h3>
                            <small class="text-muted">Paid requests in selected period</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-2">Trend (Monthly)</h5>
                    <div class="chart-wrap chart-wrap-sm">
                        <canvas id="agentTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
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
                                        <small class="text-muted">{{ $req->tenant->name }}</small>
                                    </div>
                                    <span class="badge bg-{{ $req->status === 'paid' ? 'success' : ($req->status === 'pending_review' ? 'warning' : 'secondary') }} rounded-pill flex-shrink-0">
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
            <a href="/profile" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/profile-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">My Profile</p>
                                <small class="text-muted">View & update</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="/agent/properties" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/add-prop-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">Manage Properties</p>
                                <small class="text-muted">Create & edit listings</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="/agent/rental-requests" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/search-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">Rental Requests</p>
                                <small class="text-muted">Review applications</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="/agent/contracts" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/calendar-check-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">Contracts</p>
                                <small class="text-muted">View agreements</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') {
            return;
        }

        const trendLabels = @json($trendLabels);
        const trendTotalRentals = @json($trendTotalRentals);
        const trendRevenue = @json($trendRevenue);

        const trendCanvas = document.getElementById('agentTrendChart');
        if (trendCanvas) {
            new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [
                        {
                            label: 'Rentals',
                            data: trendTotalRentals,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.15)',
                            yAxisID: 'y',
                            tension: 0.25,
                            fill: false
                        },
                        {
                            label: 'Revenue (IDR)',
                            data: trendRevenue,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.15)',
                            yAxisID: 'y1',
                            tension: 0.25,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Rentals'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Revenue (IDR)'
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
