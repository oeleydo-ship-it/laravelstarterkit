@extends('layouts.app')

@section('title', 'Social Proof')

@section('content')
@include('modules.socialproof._nav')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Social Proof</h4>
        <p class="text-muted mb-0">Show live and fake purchase / subscribe notifications on your website.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('socialproof.events.create') }}">Add notification</a>
</div>

<div class="row g-3 mb-4">
    @foreach([['Active', $stats['active']], ['Fake', $stats['fake']], ['API / live ingest', $stats['api']]] as [$label, $value])
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-3 fw-bold">{{ number_format($value) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-1">Widget status</h6>
                <p class="text-muted mb-0 small">
                    {{ ($site->resolvedSettings()['enabled'] ?? true) ? 'Enabled' : 'Disabled' }}
                    · Max displays after reload:
                    <strong>{{ (int) ($site->resolvedSettings()['max_displays'] ?? 5) }}</strong>
                    (0 = unlimited)
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('socialproof.settings') }}" class="btn btn-outline-secondary btn-sm">Settings</a>
                <a href="{{ route('socialproof.install') }}" class="btn btn-outline-primary btn-sm">Install snippet</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <h6 class="mb-0">Recent notifications</h6>
            <a href="{{ route('socialproof.events.index') }}">View all</a>
        </div>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                    <tr>
                        <td>
                            {{ $event->customer_name }}
                            @if($event->location)
                                <div class="small text-muted">{{ $event->location }}</div>
                            @endif
                        </td>
                        <td>{{ $event->item_name }}</td>
                        <td>{{ ucfirst($event->type) }}</td>
                        <td>{{ ucfirst($event->source) }}</td>
                        <td>{{ optional($event->occurred_at)->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No notifications yet. Add fake purchases or enable live sources.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
