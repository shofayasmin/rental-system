@extends('layouts.app')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">

            <div class="card shadow-sm mt-5">
                <div class="card-body">

                    <h4 class="text-center mb-3">
                        House Rental Management System
                    </h4>

                    <p class="text-center text-muted mb-4">
                        Please login to continue
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="/login">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required
                            >
                        </div>

                        <button type="submit"
                                class="btn btn-primary w-100">
                            Login
                        </button>
                    </form>

                    <p class="text-center mt-3 mb-0">
                        Don’t have an account?
                        <a href="/register">Register</a>
                    </p>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection
