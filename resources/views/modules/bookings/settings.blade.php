@extends('layouts.app')

@section('title', 'Booking settings')

@section('content')
    @include('modules.bookings._nav')

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
        <button class="btn btn-primary mt-4">Save settings</button>
    </form>
@endsection
