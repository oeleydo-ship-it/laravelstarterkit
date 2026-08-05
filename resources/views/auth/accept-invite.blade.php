@extends('layouts.auth')

@section('title', 'Accept Invite')

@section('content')
    <h4 class="fw-bold mb-1">Join {{ $invite->tenant->name }}</h4>
    <p class="text-muted mb-4">You've been invited as <strong>{{ ucfirst($invite->role) }}</strong>. Create your account to
        join.</p>

    <form method="POST" action="{{ route('invite.register', $invite->token) }}">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-medium">Email</label>
            <input type="email" class="form-control" value="{{ $invite->email }}" disabled>
        </div>

        <div class="mb-3">
            <label for="name" class="form-label fw-medium">Full Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                value="{{ old('name') }}" required autofocus>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-medium">Password</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                name="password" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-medium">Confirm Password</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">Create Account & Join</button>
    </form>
@endsection