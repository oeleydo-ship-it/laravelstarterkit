@extends('layouts.app')
@section('title','Install Forms')
@section('content')
@include('modules.forms._nav')
<h4>Install Forms</h4><p class="text-muted">Add this once to your website, then place a form container where you need it.</p>
<label class="form-label">Loader</label><textarea class="form-control" rows="2" readonly>{{ $snippet }}</textarea>
<label class="form-label mt-3">Container example</label><textarea class="form-control" rows="2" readonly>&lt;div data-f-form="FORM_ID"&gt;&lt;/div&gt;</textarea>
@endsection
