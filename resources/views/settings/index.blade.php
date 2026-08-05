@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Workspace Settings</h5>

                    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="company_name" class="form-label fw-medium">Company Name</label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror"
                                id="company_name" name="company_name"
                                value="{{ old('company_name', $settings['company_name'] ?? currentTenant()->name) }}"
                                required>
                            @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="timezone" class="form-label fw-medium">Timezone</label>
                            <select class="form-select @error('timezone') is-invalid @enderror" id="timezone"
                                name="timezone">
                                <option value="">Select timezone...</option>
                                @foreach($timezones as $tz)
                                    <option value="{{ $tz }}" {{ ($settings['timezone'] ?? config('app.timezone')) === $tz ? 'selected' : '' }}>
                                        {{ $tz }}
                                    </option>
                                @endforeach
                            </select>
                            @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notification_email" class="form-label fw-medium">Notification Email</label>
                            <input type="email" class="form-control @error('notification_email') is-invalid @enderror"
                                id="notification_email" name="notification_email"
                                value="{{ old('notification_email', $settings['notification_email'] ?? '') }}"
                                placeholder="notifications@company.com">
                            @error('notification_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="logo" class="form-label fw-medium">Logo</label>
                            @if(isset($settings['logo']))
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $settings['logo']) }}" alt="Logo" class="rounded"
                                        style="max-height:60px;">
                                </div>
                            @endif
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo"
                                name="logo" accept="image/*">
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">JPG, PNG, or SVG. Max 2MB.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Workspace Info</h6>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Slug</span>
                        <strong>{{ currentTenant()->slug }}</strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Created</span>
                        <strong>{{ currentTenant()->created_at->format('M d, Y') }}</strong>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Plan</span>
                        <strong>{{ currentTenant()->plan->name ?? 'Free' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection