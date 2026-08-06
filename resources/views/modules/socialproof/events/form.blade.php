@extends('layouts.app')

@section('title', $event->exists ? 'Edit notification' : 'New notification')

@section('content')
@include('modules.socialproof._nav')

<h4 class="fw-bold mb-3">{{ $event->exists ? 'Edit notification' : 'New notification' }}</h4>

<form class="card" method="POST" action="{{ $event->exists ? route('socialproof.events.update', $event) : route('socialproof.events.store') }}">
    @csrf
    @if($event->exists) @method('PUT') @endif

    <div class="card-body row g-3">
        <div class="col-md-4">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" required>
                @foreach(\App\Models\SocialProofEvent::types() as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $event->type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Source</label>
            <select name="source" class="form-select" required>
                <option value="fake" @selected(old('source', $event->source) === 'fake')>Fake</option>
                <option value="api" @selected(old('source', $event->source) === 'api')>API / real</option>
            </select>
            <small class="text-muted">Use Fake for demo social proof. API is for real store ingest.</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Occurred at</label>
            <input type="datetime-local" name="occurred_at" class="form-control"
                   value="{{ old('occurred_at', optional($event->occurred_at)->format('Y-m-d\TH:i')) }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Customer name</label>
            <input required name="customer_name" class="form-control" value="{{ old('customer_name', $event->customer_name) }}" placeholder="Sarah from NYC">
        </div>
        <div class="col-md-6">
            <label class="form-label">Location</label>
            <input name="location" class="form-control" value="{{ old('location', $event->location) }}" placeholder="New York, USA">
        </div>

        <div class="col-md-6">
            <label class="form-label">Product / plan</label>
            <input required name="item_name" class="form-control" value="{{ old('item_name', $event->item_name) }}" placeholder="Pro Plan">
        </div>
        <div class="col-md-6">
            <label class="form-label">Product URL</label>
            <input type="url" name="product_url" class="form-control" value="{{ old('product_url', $event->product_url) }}">
        </div>

        <div class="col-md-8">
            <label class="form-label">Avatar URL</label>
            <input type="url" name="avatar_url" class="form-control" value="{{ old('avatar_url', $event->avatar_url) }}">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                       @checked(old('is_active', $event->is_active ?? true))>
                <label class="form-check-label" for="is_active">Active on widget</label>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <button class="btn btn-primary">Save notification</button>
    </div>
</form>

@if($event->exists)
    <form class="mt-2 text-end" method="POST" action="{{ route('socialproof.events.destroy', $event) }}"
          onsubmit="return confirm('Delete this notification?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger">Delete</button>
    </form>
@endif
@endsection
