@extends('layouts.auth')

@section('title', 'Create Your Workspace')

@section('content')
<h4 class="fw-bold mb-1">Create Your Workspace</h4>
<p class="text-muted mb-4">Set up your company workspace to get started.</p>

<form method="POST" action="{{ route('onboarding.store') }}">
    @csrf

    <div class="mb-3">
        <label for="company_name" class="form-label fw-medium">Company Name</label>
        <input type="text" class="form-control @error('company_name') is-invalid @enderror"
               id="company_name" name="company_name" value="{{ old('company_name') }}"
               placeholder="Acme Inc." required autofocus>
        @error('company_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="slug" class="form-label fw-medium">Workspace URL Slug</label>
        <div class="input-group">
            <span class="input-group-text text-muted small">app.example.com/</span>
            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                   id="slug" name="slug" value="{{ old('slug') }}" placeholder="acme-inc" required>
        </div>
        @error('slug')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
        <div class="form-text">Only lowercase letters, numbers, and dashes.</div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">
        Create Workspace →
    </button>
</form>
@endsection