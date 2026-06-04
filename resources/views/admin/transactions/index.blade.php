@extends('layouts.app')

@section('content')
<div class="container">

    <h1 class="mb-4">All Transactions</h1>

    {{-- SEARCH / FILTER --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3">

                <div class="col-md-2">
                    <label class="form-label">Transaction ID</label>
                    <input type="text" name="transaction_id"
                           value="{{ request('transaction_id') }}"
                           class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="date"
                           value="{{ request('date') }}"
                           class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tenant</label>
                    <input type="text" name="tenant"
                           value="{{ request('tenant') }}"
                           class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Property</label>
                    <input type="text" name="property"
                           value="{{ request('property') }}"
                           class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Agent</label>
                    <input type="text" name="agent"
                           value="{{ request('agent') }}"
                           class="form-control">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">
                        Search
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Property</th>
                            <th>Tenant</th>
                            <th>Agent</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($transactions as $t)
                            <tr>
                                <td>{{ $t->id }}</td>
                                <td>{{ $t->created_at->toDateString() }}</td>
                                <td>{{ $t->property->title }}</td>
                                <td>{{ $t->tenant->name }}</td>
                                <td>{{ $t->property->agent->name }}</td>
                                <td>{{ strtoupper(str_replace('_', ' ', $t->type ?? 'initial_rent')) }}</td>
                                <td>{{ $t->amount }}</td>
                                <td>
                                    <span class="badge
                                        {{ $t->status === 'paid' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ strtoupper($t->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    No transactions found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $transactions->links() }}
            </div>

        </div>
    </div>

    <!-- <div class="mt-4">
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            ⬅ Back
        </a>
    </div> -->

</div>
@endsection
