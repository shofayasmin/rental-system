@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 700px;">

    <div class="d-flex align-items-center gap-3 mb-4">
        <img src="{{ asset('icons/profile-icon.svg') }}"
             alt="Profile icon"
             width="44"
             height="44"
             onerror="this.style.display='none'">
        <h1 class="mb-0">My Profile</h1>
    </div>

    {{-- UPDATE PROFILE --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h4 class="card-title mb-3">Update Personal Information</h4>

            <form method="POST" action="/profile">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input
                        type="text"
                        name="name"     
                        value="{{ old('name', $user->name) }}"
                        class="form-control"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        class="form-control"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone', $user->phone) }}"
                        class="form-control"
                    >
                </div>

                <button class="btn btn-primary">
                    Save Profile
                </button>
            </form>
        </div>
    </div>

    {{-- CHANGE PASSWORD --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="card-title mb-3">Change Password</h4>

            <form method="POST" action="/profile/password">
                @csrf

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        required
                    >
                </div>

                <button class="btn btn-warning">
                    Change Password
                </button>
            </form>
        </div>
    </div>

    <!-- <div class="mt-4">
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            ⬅ Back
        </a>
    </div> -->

</div>
@endsection
