@extends('layouts.app')

@section('content')
<div class="container">
    <style>
        .chart-wrap {
            position: relative;
            width: 100%;
        }

        .chart-wrap-lg {
            height: 340px;
        }

        .chart-wrap-sm {
            height: 300px;
        }
    </style>

    <h1 class="mb-4">Admin Dashboard</h1>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/myprop-card-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Total Properties</p>
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

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/admin/dashboard" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" class="form-control">
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
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
        <div class="col-12 col-md-6 col-xl-3">
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
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/area-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Average Rent Price</p>
                            <h3 class="mb-0">Rp{{ number_format((float) ($averageRentPrice ?? 0), 0, ',', '.') }}</h3>
                            <small class="text-muted">From paid rentals in selected period</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <img src="/icons/floor-icon.svg" alt="" width="40" height="40" class="me-3 flex-shrink-0">
                        <div>
                            <p class="text-uppercase text-dark fw-semibold small mb-1">Average Time to Rent</p>
                            <h3 class="mb-0">{{ number_format((float) ($averageTimeToRentDays ?? 0), 1) }} days</h3>
                            <small class="text-muted">From listing available (to-let) to paid</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">KPI Trend (Monthly)</h5>
                    <div class="chart-wrap chart-wrap-lg">
                        <canvas id="kpiTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Occupancy Snapshot</h5>
                    <div class="chart-wrap chart-wrap-sm">
                        <canvas id="occupancySnapshotChart"></canvas>
                    </div>
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
            <a href="/admin/users" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/search-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">Manage Users</p>
                                <small class="text-muted">Agents & Tenants</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="/admin/transactions" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/cash-stack-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">Transactions</p>
                                <small class="text-muted">View payments</small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="/admin/login-audit" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="/icons/notification-bell-icon.svg" alt="" width="32" height="32" class="me-3 flex-shrink-0">
                            <div>
                                <p class="fw-semibold small mb-0">Login Audit</p>
                                <small class="text-muted">User activity logs</small>
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
        const trendAverageRentPrice = @json($trendAverageRentPrice);
        const trendAverageTimeToRentDays = @json($trendAverageTimeToRentDays);

        const trendCanvas = document.getElementById('kpiTrendChart');
        if (trendCanvas) {
            new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [
                        {
                            label: 'Total Rentals',
                            data: trendTotalRentals,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.15)',
                            yAxisID: 'y',
                            tension: 0.25,
                            fill: false
                        },
                        {
                            label: 'Avg Time to Rent: To Let -> Paid (days)',
                            data: trendAverageTimeToRentDays,
                            borderColor: '#fd7e14',
                            backgroundColor: 'rgba(253, 126, 20, 0.15)',
                            yAxisID: 'y',
                            tension: 0.25,
                            fill: false
                        },
                        {
                            label: 'Avg Rent Price (IDR)',
                            data: trendAverageRentPrice,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.15)',
                            yAxisID: 'y1',
                            tension: 0.25,
                            fill: false
                        },
                        {
                            label: 'Total Revenue (IDR)',
                            data: trendRevenue,
                            borderColor: '#6f42c1',
                            backgroundColor: 'rgba(111, 66, 193, 0.15)',
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
                                text: 'Rentals / Days'
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
                                text: 'Average Rent Price (IDR)'
                            }
                        }
                    }
                }
            });
        }

        const occupancyCanvas = document.getElementById('occupancySnapshotChart');
        if (occupancyCanvas) {
            new Chart(occupancyCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Rented', 'To Let', 'Maintenance'],
                    datasets: [{
                        data: [{{ (int) $rentedProperties }}, {{ (int) $toLetProperties }}, {{ (int) $maintenanceProperties }}],
                        backgroundColor: ['#198754', '#0d6efd', '#dc3545'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    });
</script>
@endsection