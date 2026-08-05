@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <h4 class="fw-bold mb-1">Welcome back</h4>
    <p class="text-muted mb-4">Sign in to your account.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label fw-medium">Email Address</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                value="{{ old('email') }}" required autocomplete="email" autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-medium">Password</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                name="password" required autocomplete="current-password">
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small" for="remember">Remember me</label>
            </div>
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-medium mb-3">Sign In</button>

        @if (Route::has('register'))
            <p class="text-center text-muted small mb-0">
                Don't have an account? <a href="{{ route('register') }}" class="text-decoration-none">Create one</a>
            </p>
        @endif
    </form>
@endsection