@extends('layouts.app')

@section('title', 'Social Proof Settings')

@section('content')
@include('modules.socialproof._nav')

<h4 class="fw-bold mb-3">Settings</h4>

<form method="POST" action="{{ route('socialproof.settings.update') }}" class="card">
    @csrf
    @method('PUT')

    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Site name</label>
            <input class="form-control" required name="name" value="{{ old('name', $site->name) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Accent color</label>
            <input type="color" name="accent_color" class="form-control form-control-color"
                   value="{{ old('accent_color', $settings['accent_color'] ?? '#0f766e') }}">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled"
                       @checked(old('enabled', $settings['enabled'] ?? true))>
                <label class="form-check-label" for="enabled">Widget enabled</label>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Allowed origins</label>
            <textarea class="form-control" name="allowed_origins" rows="4" placeholder="https://example.com">{{ old('allowed_origins', implode("\n", $site->allowed_origins ?? [])) }}</textarea>
            <small class="text-muted">One origin per line. Leave empty to allow all origins.</small>
        </div>

        <div class="col-12"><hr class="my-1"><h6 class="fw-bold mb-0">Display timing</h6></div>

        <div class="col-md-3">
            <label class="form-label">Position</label>
            <select name="position" class="form-select">
                @foreach(['bottom-left' => 'Bottom left', 'bottom-right' => 'Bottom right', 'top-left' => 'Top left', 'top-right' => 'Top right'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('position', $settings['position'] ?? 'bottom-left') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Initial delay (ms)</label>
            <input type="number" min="0" max="120000" name="initial_delay_ms" class="form-control"
                   value="{{ old('initial_delay_ms', $settings['initial_delay_ms'] ?? 4000) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Show duration (ms)</label>
            <input type="number" min="1000" max="60000" name="display_duration_ms" class="form-control"
                   value="{{ old('display_duration_ms', $settings['display_duration_ms'] ?? 5000) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Gap between toasts (ms)</label>
            <input type="number" min="2000" max="120000" name="interval_ms" class="form-control"
                   value="{{ old('interval_ms', $settings['interval_ms'] ?? 9000) }}">
        </div>

        <div class="col-12"><hr class="my-1"><h6 class="fw-bold mb-0">Reload / frequency limits</h6>
            <p class="text-muted small mb-0">Controls how often the widget appears after visitors reload the page. Count is stored in their browser.</p>
        </div>

        <div class="col-md-4">
            <label class="form-label">Max displays after reloads</label>
            <input type="number" min="0" max="1000" name="max_displays" class="form-control"
                   value="{{ old('max_displays', $settings['max_displays'] ?? 5) }}">
            <small class="text-muted">How many page loads may show the widget. <strong>0 = unlimited</strong>.</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Max toasts per page load</label>
            <input type="number" min="1" max="50" name="max_per_page" class="form-control"
                   value="{{ old('max_per_page', $settings['max_per_page'] ?? 4) }}">
        </div>

        <div class="col-12"><hr class="my-1"><h6 class="fw-bold mb-0">Sources</h6></div>

        <div class="col-md-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="include_fake" value="1" id="include_fake"
                       @checked(old('include_fake', $settings['include_fake'] ?? true))>
                <label class="form-check-label" for="include_fake">Fake notifications</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="include_api" value="1" id="include_api"
                       @checked(old('include_api', $settings['include_api'] ?? true))>
                <label class="form-check-label" for="include_api">API / real purchases</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="include_live_subscribers" value="1" id="include_live_subscribers"
                       @checked(old('include_live_subscribers', $settings['include_live_subscribers'] ?? true))>
                <label class="form-check-label" for="include_live_subscribers">Live email subscribers</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="include_live_bookings" value="1" id="include_live_bookings"
                       @checked(old('include_live_bookings', $settings['include_live_bookings'] ?? true))>
                <label class="form-check-label" for="include_live_bookings">Live bookings</label>
            </div>
        </div>

        <div class="col-12"><hr class="my-1"><h6 class="fw-bold mb-0">Copy</h6></div>
        <div class="col-md-6">
            <label class="form-label">Purchase verb</label>
            <input name="purchase_verb" class="form-control" value="{{ old('purchase_verb', $settings['purchase_verb'] ?? 'purchased') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Subscribe verb</label>
            <input name="subscribe_verb" class="form-control" value="{{ old('subscribe_verb', $settings['subscribe_verb'] ?? 'subscribed to') }}">
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between">
        <button class="btn btn-primary">Save settings</button>
    </div>
</form>

<form method="POST" action="{{ route('socialproof.settings.rotate') }}" class="mt-3"
      onsubmit="return confirm('Rotate the public site key? Existing embed snippets will stop working until updated.')">
    @csrf
    <button class="btn btn-outline-danger btn-sm">Rotate site key</button>
</form>
@endsection
