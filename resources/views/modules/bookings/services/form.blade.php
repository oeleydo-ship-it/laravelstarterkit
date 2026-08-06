@extends('layouts.app')

@section('title', $service->exists ? 'Edit service' : 'New service')

@section('content')
    @include('modules.bookings._nav')

    <h4 class="fw-bold mb-4">{{ $service->exists ? 'Edit service' : 'New service' }}</h4>

    <form method="POST"
          action="{{ $service->exists ? route('bookings.services.update', $service) : route('bookings.services.store') }}"
          class="table-card">
        @csrf
        @if($service->exists) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $service->name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Color</label>
                <input type="color" name="color" class="form-control form-control-color" value="{{ old('color', $service->color ?? '#0f766e') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $service->description) }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Duration (min)</label>
                <input type="number" name="duration_minutes" class="form-control" min="5" value="{{ old('duration_minutes', $service->duration_minutes ?? 30) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Buffer (min)</label>
                <input type="number" name="buffer_minutes" class="form-control" min="0" value="{{ old('buffer_minutes', $service->buffer_minutes ?? 0) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sort order</label>
                <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $service->sort_order ?? 0) }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input type="checkbox" name="active" value="1" class="form-check-input" id="active" @checked(old('active', $service->active ?? true))>
                    <label class="form-check-label" for="active">Active</label>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            @if($service->exists)
                <button form="delete-service" type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete?')">Delete</button>
            @else
                <span></span>
            @endif
            <button class="btn btn-primary">Save</button>
        </div>
    </form>

    @if($service->exists)
        <form id="delete-service" method="POST" action="{{ route('bookings.services.destroy', $service) }}" class="d-none">
            @csrf @method('DELETE')
        </form>
    @endif
@endsection
