@extends('layouts.app')
@section('title', $widget->exists ? 'Edit Widget' : 'New Widget')
@section('content')
@include('modules.reviews._nav')
@php $style = $widget->style ?? []; @endphp
<h4 class="fw-bold mb-3">{{ $widget->exists ? 'Edit widget' : 'New widget' }}</h4>
<form class="card" method="POST" action="{{ $widget->exists ? route('reviews.widgets.update', $widget) : route('reviews.widgets.store') }}">
    @csrf
    @if($widget->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input required name="name" class="form-control" value="{{ old('name', $widget->name) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Layout</label>
            <select name="layout" class="form-select">
                @foreach(['stacked', 'carousel'] as $v)
                    <option value="{{ $v }}" @selected(old('layout', $widget->layout ?: 'stacked') === $v)>{{ ucfirst($v) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach(['draft', 'live'] as $v)
                    <option value="{{ $v }}" @selected(old('status', $widget->status ?: 'draft') === $v)>{{ ucfirst($v) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Minimum rating</label>
            <input name="min_rating" type="number" min="1" max="5" class="form-control" value="{{ old('min_rating', $widget->min_rating ?: 1) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Maximum reviews</label>
            <input name="max_items" type="number" min="1" max="50" class="form-control" value="{{ old('max_items', $widget->max_items ?: 6) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Accent color</label>
            <input name="accent_color" type="color" class="form-control form-control-color" value="{{ old('accent_color', data_get($style, 'accent_color', '#2563eb')) }}">
        </div>

        <div class="col-12"><hr class="my-1"><h6 class="fw-bold mb-0">Frequency on external site</h6></div>
        <div class="col-md-4">
            <label class="form-label">Repeat every (hrs)</label>
            <input name="frequency_hours" type="number" min="0" max="8760" class="form-control"
                   value="{{ old('frequency_hours', data_get($style, 'frequency_hours', 0)) }}"
                   title="Hours before showing again. 0 = every eligible visit.">
        </div>
        <div class="col-md-4">
            <label class="form-label">How many times</label>
            <input name="max_displays" type="number" min="0" max="1000" class="form-control"
                   value="{{ old('max_displays', data_get($style, 'max_displays', 0)) }}"
                   title="Total times to show on the external site. 0 = unlimited.">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <p class="form-text mb-2">
                Controls how often the widget appears for each visitor. Close button is always available.
                Use <code>0</code> for unlimited / no cooldown.
            </p>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Save widget</button></div>
</form>
@if($widget->exists)
    <form class="mt-2 text-end" method="POST" action="{{ route('reviews.widgets.destroy', $widget) }}">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger">Delete</button>
    </form>
@endif
@endsection
