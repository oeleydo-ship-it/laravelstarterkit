@extends('layouts.app')

@section('title', 'Install')

@section('content')
    @include('modules.bookings._nav')

    <h4 class="fw-bold mb-1">Install</h4>
    <p class="text-muted mb-4">Share your public booking page — no platform branding on the guest experience.</p>

    <div class="table-card mb-4">
        <h6 class="fw-bold mb-3">Public URL</h6>
        <a href="{{ $publicUrl }}" target="_blank" class="user-select-all">{{ $publicUrl }}</a>
    </div>

    <div class="table-card mb-4">
        <h6 class="fw-bold mb-3">Link snippet</h6>
        <pre class="bg-dark text-white rounded p-3 small">{{ $snippet }}</pre>
    </div>

    <div class="table-card">
        <h6 class="fw-bold mb-3">Public key</h6>
        <code class="user-select-all">{{ $site->public_key }}</code>
        <form method="POST" action="{{ route('bookings.settings.rotate') }}" class="mt-3"
              onsubmit="return confirm('Rotating changes the booking URL. Continue?')">
            @csrf
            <button class="btn btn-sm btn-outline-danger">Rotate key</button>
        </form>
    </div>
@endsection
