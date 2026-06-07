@extends('layouts.app')

@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Manage Users</h1>
        <a href="/admin/users/create" class="btn btn-primary">+ Create User</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>{{ strtoupper($u->role) }}</td>
                                <td>
                                    @if($u->enabled)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="/admin/users/{{ $u->id }}/edit" class="btn btn-sm btn-warning">Edit</a>

                                    <form method="POST" action="/admin/users/{{ $u->id }}/toggle" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-danger">
                                            {{ $u->enabled ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No users found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
