@extends('layouts.app')

@section('title', 'Install')

@section('content')
    @include('modules.engage._nav')

    <h4 class="fw-bold mb-1">Install</h4>
    <p class="text-muted mb-4">One script tag. No branding, no vendor names in the visitor experience.</p>

    <div class="table-card mb-4">
        <h6 class="fw-bold mb-3">Snippet</h6>
        <pre class="bg-dark text-white rounded p-3 small mb-3" style="white-space: pre-wrap;">{{ $snippet }}</pre>
        <p class="small text-muted mb-0">Place it before <code>&lt;/body&gt;</code> on every page where widgets should appear.</p>
    </div>

    <div class="table-card">
        <h6 class="fw-bold mb-3">Public key</h6>
        <code class="user-select-all">{{ $site->public_key }}</code>
        <form method="POST" action="{{ route('engage.settings.rotate') }}" class="mt-3"
              onsubmit="return confirm('Rotating invalidates the current snippet. Continue?')">
            @csrf
            <button class="btn btn-sm btn-outline-danger">Rotate key</button>
        </form>
    </div>
@endsection
