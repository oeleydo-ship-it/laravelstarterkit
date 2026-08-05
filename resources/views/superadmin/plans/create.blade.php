@extends('layouts.superadmin')

@section('title', 'Create Plan')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card stat-card">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Create New Plan</h5>

                <form method="POST" action="{{ route('superadmin.plans.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-medium">Plan Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Pro">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="key" class="form-label fw-medium">Unique Key *</label>
                            <input type="text" class="form-control @error('key') is-invalid @enderror"
                                   id="key" name="key" value="{{ old('key') }}" required placeholder="e.g. pro">
                            @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Lowercase, no spaces. Used internally.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="price_monthly" class="form-label fw-medium">Monthly Price ($) *</label>
                            <input type="number" step="0.01" class="form-control @error('price_monthly') is-invalid @enderror"
                                   id="price_monthly" name="price_monthly" value="{{ old('price_monthly', '0') }}" required>
                            @error('price_monthly') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="price_yearly" class="form-label fw-medium">Yearly Price ($) *</label>
                            <input type="number" step="0.01" class="form-control @error('price_yearly') is-invalid @enderror"
                                   id="price_yearly" name="price_yearly" value="{{ old('price_yearly', '0') }}" required>
                            @error('price_yearly') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="stripe_price_id_monthly" class="form-label fw-medium">Stripe Monthly Price ID</label>
                            <input type="text" class="form-control font-monospace @error('stripe_price_id_monthly') is-invalid @enderror"
                                   id="stripe_price_id_monthly" name="stripe_price_id_monthly"
                                   value="{{ old('stripe_price_id_monthly') }}" placeholder="price_...">
                            @error('stripe_price_id_monthly') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="stripe_price_id_yearly" class="form-label fw-medium">Stripe Yearly Price ID</label>
                            <input type="text" class="form-control font-monospace @error('stripe_price_id_yearly') is-invalid @enderror"
                                   id="stripe_price_id_yearly" name="stripe_price_id_yearly"
                                   value="{{ old('stripe_price_id_yearly') }}" placeholder="price_...">
                            @error('stripe_price_id_yearly') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Plan Limits</h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="max_users" class="form-label fw-medium">Max Users *</label>
                            <input type="number" class="form-control @error('max_users') is-invalid @enderror"
                                   id="max_users" name="max_users" value="{{ old('max_users', '5') }}" required>
                            @error('max_users') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">-1 = unlimited</div>
                        </div>
                        <div class="col-md-4">
                            <label for="max_modules" class="form-label fw-medium">Max Modules *</label>
                            <input type="number" class="form-control @error('max_modules') is-invalid @enderror"
                                   id="max_modules" name="max_modules" value="{{ old('max_modules', '3') }}" required>
                            @error('max_modules') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">-1 = unlimited</div>
                        </div>
                        <div class="col-md-4">
                            <label for="storage_limit" class="form-label fw-medium">Storage (MB) *</label>
                            <input type="number" class="form-control @error('storage_limit') is-invalid @enderror"
                                   id="storage_limit" name="storage_limit" value="{{ old('storage_limit', '500') }}" required>
                            @error('storage_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="sort_order" class="form-label fw-medium">Sort Order *</label>
                            <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                   id="sort_order" name="sort_order" value="{{ old('sort_order', '1') }}" required>
                            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create Plan</button>
                        <a href="{{ route('superadmin.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
