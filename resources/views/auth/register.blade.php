@extends('layouts.app')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-7">

            <div class="card shadow-sm">
                <div class="card-body">

                    <h4 class="text-center mb-3">
                        Tenant Registration
                    </h4>

                    <p class="text-center text-muted mb-4">
                        Please fill in your personal information to create a tenant account
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="/register">
                        @csrf

                        <div class="row">

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control"
                                        value="{{ old('name') }}"
                                        placeholder="Full Name"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control"
                                        value="{{ old('email') }}"
                                        placeholder="Email"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input
                                        type="text"
                                        name="phone"
                                        class="form-control"
                                        value="{{ old('phone') }}"
                                        placeholder="Phone"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input
                                        type="password"
                                        name="password"
                                        class="form-control"
                                        placeholder="Password"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        class="form-control"
                                        placeholder="Confirm Password"
                                        required
                                    >
                                </div>
                            </div>

                        </div>

                        <button type="submit"
                                class="btn btn-primary w-100 mt-3">
                            Register
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="/login">Already have an account? Login</a>
                    </div>

                    <!-- <div class="text-center mt-2">
                        <a href="javascript:history.back()">⬅ Back</a>
                    </div> -->

                </div>
            </div>

        </div>
    </div>

</div>
@endsection
