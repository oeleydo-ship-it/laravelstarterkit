@extends('layouts.app')

@section('title', $subscriber->email)

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">{{ $subscriber->email }}</h4>
            <p class="text-muted mb-0">{{ $subscriber->fullName() }}</p>
            <div class="mt-2"><span class="badge {{ $subscriber->statusBadgeClass() }}">{{ $subscriber->statusLabel() }}</span></div>
        </div>
        @can('update', $subscriber)
            <a href="{{ route('email.subscribers.edit', $subscriber) }}" class="btn btn-outline-primary">Edit</a>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="fw-bold">Details</h6>
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-muted">Subscribed</dt>
                        <dd class="col-8">{{ optional($subscriber->subscribed_at)->format('M d, Y H:i') ?? '—' }}</dd>
                        <dt class="col-4 text-muted">Unsubscribed</dt>
                        <dd class="col-8">{{ optional($subscriber->unsubscribed_at)->format('M d, Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="fw-bold">Lists</h6>
                    @forelse($subscriber->lists as $list)
                        <span class="badge bg-light text-dark border me-1 mb-1">{{ $list->name }}</span>
                    @empty
                        <p class="text-muted mb-0 small">Not on any lists.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
