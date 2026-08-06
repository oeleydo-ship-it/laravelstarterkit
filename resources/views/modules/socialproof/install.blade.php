@extends('layouts.app')

@section('title', 'Install Social Proof')

@section('content')
@include('modules.socialproof._nav')

<h4 class="fw-bold">Install</h4>
<p class="text-muted">Add this snippet once to your website. The widget shows recent purchase and subscribe notifications as toasts.</p>

<div class="card mb-3">
    <div class="card-body">
        <label class="form-label">Embed snippet</label>
        <pre class="bg-dark text-white rounded p-3 mb-3">{{ $snippet }}</pre>

        <label class="form-label">Ingest live purchases (optional)</label>
        <pre class="bg-light border rounded p-3 mb-2">POST {{ url('/sp/'.$site->public_key.'/e') }}
Content-Type: application/json

{
  "type": "purchase",
  "customer_name": "Alex",
  "location": "London",
  "item_name": "Pro Plan"
}</pre>
        <p class="small text-muted mb-0">
            Current limit: shows on up to <strong>{{ (int) ($settings['max_displays'] ?? 5) }}</strong> page loads per visitor
            (0 = unlimited). Change this under Settings.
        </p>
    </div>
</div>
@endsection
