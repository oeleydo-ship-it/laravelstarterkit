@extends('layouts.app')

@section('title', 'Engage settings')

@section('content')
    @include('modules.engage._nav')

    <h4 class="fw-bold mb-1">Settings</h4>
    <p class="text-muted mb-4">Site defaults for your white-label embed.</p>

    <form method="POST" action="{{ route('engage.settings.update') }}" class="table-card">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Site name (admin only)</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $site->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Default brand color</label>
                <input type="text" name="brand_color" class="form-control"
                       value="{{ old('brand_color', $site->settings['brand_color'] ?? '#2563eb') }}" placeholder="#2563eb">
            </div>
            <div class="col-12">
                <label class="form-label">Allowed origins (optional)</label>
                <textarea name="allowed_origins" class="form-control" rows="4"
                          placeholder="https://www.example.com&#10;https://example.com">{{ old('allowed_origins', implode("\n", $site->allowed_origins ?? [])) }}</textarea>
                <div class="form-text">One origin per line. Leave empty to allow any website.</div>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary">Save settings</button>
        </div>
    </form>
@endsection
