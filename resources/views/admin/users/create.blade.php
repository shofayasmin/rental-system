@extends('layouts.app')

@section('content')
<h2>Create User</h2>

<form method="POST" action="/admin/users">
    @csrf

    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control">
    </div>

    <div class="mb-3">
        <label>Role</label>
        <select name="role" class="form-control" required>
            <option value="agent">Agent</option>
            <option value="tenant">Tenant</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <button class="btn btn-success">Create</button>
    <a href="/admin/users" class="btn btn-secondary">Cancel</a>
</form>
@endsection
