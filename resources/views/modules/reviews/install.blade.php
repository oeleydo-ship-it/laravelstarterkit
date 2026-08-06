@extends('layouts.app')
@section('title', 'Install Reviews')
@section('content')
@include('modules.reviews._nav')
<h4 class="fw-bold">Install</h4><p class="text-muted">Add this once to your website, then add <code>data-r-widget</code> to an element where a live widget should appear. Without a target, the first live widget is mounted automatically.</p>
<div class="card"><div class="card-body"><label class="form-label">Embed snippet</label><pre class="bg-dark text-white rounded p-3">{{ $snippet }}</pre><label class="form-label">Optional placement</label><pre class="bg-light border rounded p-3">&lt;div data-r-widget="1"&gt;&lt;/div&gt;</pre><p class="mb-0">Share reviews at <a href="{{ route('reviews.write',$site->public_key) }}" target="_blank">{{ route('reviews.write',$site->public_key) }}</a>.</p></div></div>
@endsection
