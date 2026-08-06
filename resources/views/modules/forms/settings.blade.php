@extends('layouts.app')
@section('title','Forms settings')
@section('content')
@include('modules.forms._nav')
<h4 class="mb-4">Forms settings</h4><form method="POST" action="{{ route('forms.settings.update') }}" class="card card-body">@csrf @method('PUT')<div class="mb-3"><label class="form-label">Site name</label><input class="form-control" name="name" value="{{ old('name',$site->name) }}"></div><div class="mb-3"><label class="form-label">Brand color</label><input class="form-control" name="brand_color" value="{{ old('brand_color',$site->brandColor()) }}"></div><div class="mb-3"><label class="form-label">Allowed origins (one per line)</label><textarea class="form-control" name="allowed_origins" rows="4">{{ old('allowed_origins',implode("\n",$site->allowed_origins??[])) }}</textarea></div><button class="btn btn-primary align-self-start">Save settings</button></form><form method="POST" action="{{ route('forms.settings.rotate') }}" class="mt-3">@csrf<button class="btn btn-outline-danger" onclick="return confirm('Rotate the public key?')">Rotate install key</button></form>
@endsection
