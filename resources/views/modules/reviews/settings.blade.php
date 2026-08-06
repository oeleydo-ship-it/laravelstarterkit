@extends('layouts.app')
@section('title', 'Review Settings')
@section('content')
@include('modules.reviews._nav')
<h4 class="fw-bold mb-3">Settings</h4><form method="POST" action="{{ route('reviews.settings.update') }}" class="card">@csrf @method('PUT')<div class="card-body"><div class="mb-3"><label class="form-label">Site name</label><input class="form-control" required name="name" value="{{ old('name',$site->name) }}"></div><div><label class="form-label">Allowed origins</label><textarea class="form-control" name="allowed_origins" rows="5" placeholder="https://example.com">{{ old('allowed_origins',implode("\n",$site->allowed_origins ?? [])) }}</textarea><small class="text-muted">One origin per line. Leave empty to allow all origins.</small></div></div><div class="card-footer"><button class="btn btn-primary">Save settings</button></div></form>
@endsection
