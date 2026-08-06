@extends('layouts.app')

@section('title', 'Booking settings')

@section('content')
    @include('modules.bookings._nav')

    @php $s = $site->settings ?? []; @endphp

    <h4 class="fw-bold mb-4">Settings</h4>

    <form method="POST" action="{{ route('bookings.settings.update') }}" class="table-card">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Site name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $site->name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Timezone</label>
                <input type="text" name="timezone" class="form-control" value="{{ old('timezone', $site->timezone) }}" required placeholder="America/New_York">
            </div>
            <div class="col-md-3">
                <label class="form-label">Brand color</label>
                <input type="color" name="brand_color" class="form-control form-control-color" value="{{ old('brand_color', $site->brandColor()) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Allowed origins (optional, one per line)</label>
                <textarea name="allowed_origins" class="form-control" rows="3">{{ old('allowed_origins', implode("\n", $site->allowed_origins ?? [])) }}</textarea>
            </div>
        </div>

        <hr class="my-4">
        <h6 class="fw-bold">Frequency on external site</h6>
        <p class="text-muted small">Floating Book button on your website — control how often each visitor sees it.</p>
        <div class="row g-3">
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="widget_enabled" value="1" id="widget_enabled"
                           @checked(old('widget_enabled', $s['widget_enabled'] ?? true))>
                    <label class="form-check-label" for="widget_enabled">Enable embed widget</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Button label</label>
                <input type="text" name="widget_label" class="form-control"
                       value="{{ old('widget_label', $s['widget_label'] ?? 'Book a time') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Position</label>
                <select name="widget_position" class="form-select">
                    @foreach(['bottom-right','bottom-left','top-right','top-left'] as $pos)
                        <option value="{{ $pos }}" @selected(old('widget_position', $s['widget_position'] ?? 'bottom-right') === $pos)>{{ $pos }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Repeat every (hrs)</label>
                <input type="number" name="frequency_hours" class="form-control" min="0" max="8760"
                       value="{{ old('frequency_hours', $s['frequency_hours'] ?? 24) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">How many times</label>
                <input type="number" name="max_displays" class="form-control" min="0" max="1000"
                       value="{{ old('max_displays', $s['max_displays'] ?? 0) }}">
            </div>
            <div class="col-12">
                <p class="form-text mb-0">
                    <strong>How many times</strong> = total shows per visitor (<code>1</code> = once ever, <code>0</code> = unlimited).
                    <strong>Repeat every</strong> = cooldown hours between shows. Visitors can close the widget anytime.
                </p>
            </div>
        </div>

        <button class="btn btn-primary mt-4">Save settings</button>
    </form>
@endsection
