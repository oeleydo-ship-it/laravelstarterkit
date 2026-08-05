@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    <h4 class="fw-bold mb-1">Create your account</h4>
    <p class="text-muted mb-4">Get started with your SaaS workspace.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label fw-medium">Full Name</label>
            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                value="{{ old('name') }}" required autofocus>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label fw-medium">Email Address</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                value="{{ old('email') }}" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-medium">Password</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                name="password" required>
        </div>

        <div class="mb-4">
            <label for="password-confirm" class="form-label fw-medium">Confirm Password</label>
            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-medium mb-3">Create Account</button>

        <p class="text-center text-muted small mb-0">
            Already have an account? <a href="{{ route('login') }}" class="text-decoration-none">Sign in</a>
        </p>
    </form>
@endsection