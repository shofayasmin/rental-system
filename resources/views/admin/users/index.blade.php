@extends('layouts.app')

@section('content')
<h2>Manage Users</h2>

<a href="/admin/users/create" class="btn btn-primary mb-3">+ Create User</a>

<table class="table table-bordered">
<tr>
    <th>Name</th>
    <th>Email</th>
    <th>Role</th>
    <th>Status</th>
    <th>Action</th>
</tr>

@foreach($users as $u)
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

        <form method="POST" action="/admin/users/{{ $u->id }}/toggle" style="display:inline">
            @csrf
            <button class="btn btn-sm btn-danger">
                {{ $u->enabled ? 'Disable' : 'Enable' }}
            </button>
        </form>
    </td>
</tr>
@endforeach
</table>
@endsection
