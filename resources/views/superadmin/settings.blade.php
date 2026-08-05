@extends('layouts.superadmin')

@section('title', 'System Settings')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Tabs --}}
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#general" role="tab">General</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#stripe" role="tab">Stripe / Payment Gateway</a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- General Tab --}}
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">General Settings</h5>
                            <form method="POST" action="{{ route('superadmin.settings.update') }}"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="tab" value="general">

                                <div class="mb-3">
                                    <label for="app_name" class="form-label fw-medium">Application Name</label>
                                    <input type="text" class="form-control @error('app_name') is-invalid @enderror"
                                        id="app_name" name="app_name"
                                        value="{{ old('app_name', $general['app_name'] ?? config('app.name')) }}">
                                    @error('app_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="app_logo" class="form-label fw-medium">Application Logo</label>
                                    @if(!empty($general['app_logo']))
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $general['app_logo']) }}" alt="Logo"
                                                style="max-height:48px;border-radius:8px;">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @error('app_logo') is-invalid @enderror"
                                        id="app_logo" name="app_logo" accept="image/*">
                                    @error('app_logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Recommended: 200×50px, PNG or SVG.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="support_email" class="form-label fw-medium">Support Email</label>
                                    <input type="email" class="form-control @error('support_email') is-invalid @enderror"
                                        id="support_email" name="support_email"
                                        value="{{ old('support_email', $general['support_email'] ?? '') }}">
                                    @error('support_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="footer_text" class="form-label fw-medium">Footer Text</label>
                                    <textarea class="form-control @error('footer_text') is-invalid @enderror"
                                        id="footer_text" name="footer_text"
                                        rows="2">{{ old('footer_text', $general['footer_text'] ?? '') }}</textarea>
                                    @error('footer_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <button type="submit" class="btn btn-primary">Save General Settings</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Stripe Tab --}}
                <div class="tab-pane fade" id="stripe" role="tabpanel">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-1">Stripe Configuration</h5>
                            <p class="text-muted small mb-4">Configure your Stripe API keys for payment processing.</p>

                            <form method="POST" action="{{ route('superadmin.settings.update') }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="tab" value="stripe">

                                <div class="mb-3">
                                    <label for="stripe_key" class="form-label fw-medium">Publishable Key</label>
                                    <input type="text"
                                        class="form-control font-monospace @error('stripe_key') is-invalid @enderror"
                                        id="stripe_key" name="stripe_key"
                                        value="{{ old('stripe_key', $stripe['stripe_key'] ?? '') }}"
                                        placeholder="pk_test_...">
                                    @error('stripe_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="stripe_secret" class="form-label fw-medium">Secret Key</label>
                                    <input type="password"
                                        class="form-control font-monospace @error('stripe_secret') is-invalid @enderror"
                                        id="stripe_secret" name="stripe_secret"
                                        value="{{ old('stripe_secret', $stripe['stripe_secret'] ?? '') }}"
                                        placeholder="sk_test_...">
                                    @error('stripe_secret') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Your secret key is stored securely and never displayed.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="stripe_webhook_secret" class="form-label fw-medium">Webhook Secret</label>
                                    <input type="password"
                                        class="form-control font-monospace @error('stripe_webhook_secret') is-invalid @enderror"
                                        id="stripe_webhook_secret" name="stripe_webhook_secret"
                                        value="{{ old('stripe_webhook_secret', $stripe['stripe_webhook_secret'] ?? '') }}"
                                        placeholder="whsec_...">
                                    @error('stripe_webhook_secret') <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="alert alert-warning py-2 small mb-4">
                                    <strong>⚠ Note:</strong> Changing Stripe keys will affect active subscriptions. Make
                                    sure you're using the correct environment keys (test vs. live).
                                </div>

                                <button type="submit" class="btn btn-primary">Save Stripe Settings</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection