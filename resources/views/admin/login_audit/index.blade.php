@extends('layouts.app')

@section('content')
<div class="container">

    <h2 class="mb-3">Login Activity Logs</h2>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>IP Address</th>
                        <th>Device / Browser</th>
                        <th>Login Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $index => $log)
                        <tr>
                            <td>{{ $logs->firstItem() + $index }}</td>
                            <td>{{ $log->user->name ?? 'N/A' }}</td>
                            <td>{{ $log->user->email ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $log->ip_address }}
                                </span>
                            </td>
                            <td style="max-width: 250px;">
                                <small>{{ $log->user_agent }}</small>
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($log->logged_in_at)->format('d M Y, H:i:s') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No login activity recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="d-flex justify-content-center mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
