@extends('layouts.app')
@section('title', $widget->exists ? 'Edit Widget' : 'New Widget')
@section('content')
@include('modules.reviews._nav')
<h4 class="fw-bold mb-3">{{ $widget->exists ? 'Edit widget' : 'New widget' }}</h4>
<form class="card" method="POST" action="{{ $widget->exists ? route('reviews.widgets.update',$widget) : route('reviews.widgets.store') }}">@csrf @if($widget->exists) @method('PUT') @endif<div class="card-body row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input required name="name" class="form-control" value="{{ old('name',$widget->name) }}"></div>
<div class="col-md-3"><label class="form-label">Layout</label><select name="layout" class="form-select">@foreach(['stacked','carousel'] as $v)<option value="{{ $v }}" @selected(old('layout',$widget->layout ?: 'stacked') === $v)>{{ ucfirst($v) }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['draft','live'] as $v)<option value="{{ $v }}" @selected(old('status',$widget->status ?: 'draft') === $v)>{{ ucfirst($v) }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Minimum rating</label><input name="min_rating" type="number" min="1" max="5" class="form-control" value="{{ old('min_rating',$widget->min_rating ?: 1) }}"></div><div class="col-md-4"><label class="form-label">Maximum reviews</label><input name="max_items" type="number" min="1" max="50" class="form-control" value="{{ old('max_items',$widget->max_items ?: 6) }}"></div><div class="col-md-4"><label class="form-label">Accent color</label><input name="accent_color" type="color" class="form-control form-control-color" value="{{ old('accent_color',data_get($widget->style,'accent_color','#2563eb')) }}"></div>
</div><div class="card-footer"><button class="btn btn-primary">Save widget</button></div></form>
@if($widget->exists)<form class="mt-2 text-end" method="POST" action="{{ route('reviews.widgets.destroy',$widget) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete</button></form>@endif
@endsection
