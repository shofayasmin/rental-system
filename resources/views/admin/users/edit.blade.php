@extends('layouts.app')

@section('content')
<h2>Edit User</h2>

<form method="POST" action="/admin/users/{{ $user->id }}">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label>Name</label>
        <input
            type="text"
            name="name"
            value="{{ old('name', $user->name) }}"
            class="form-control"
            required
        >
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input
            type="email"
            name="email"
            value="{{ old('email', $user->email) }}"
            class="form-control"
            required
        >
    </div>
    <div class="mb-3">
        <label>Phone</label>
        <input
            type="text"
            name="phone"
            value="{{ old('phone', $user->phone) }}"
            class="form-control"
        >
    </div>

    <button class="btn btn-primary">Update</button>
    <a href="/admin/users" class="btn btn-secondary">Cancel</a>
</form>
@endsection
