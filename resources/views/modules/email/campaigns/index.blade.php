@extends('layouts.app')

@section('title', 'Campaigns')

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Campaigns</h4>
            <p class="text-muted mb-0 small">Draft, schedule, and send email campaigns.</p>
        </div>
        @can('create', App\Models\EmailCampaign::class)
            <a href="{{ route('email.campaigns.create') }}" class="btn btn-primary">+ New Campaign</a>
        @endcan
    </div>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-5">
            <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search campaigns…">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary">Filter</button></div>
    </form>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>List</th>
                    <th>Status</th>
                    <th>Recipients</th>
                    <th>Opens</th>
                    <th>Clicks</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td>
                            <a href="{{ route('email.campaigns.show', $campaign) }}" class="fw-medium text-decoration-none">{{ $campaign->name }}</a>
                            <div class="text-muted small">{{ $campaign->subject }}</div>
                        </td>
                        <td>{{ $campaign->list?->name ?? '—' }}</td>
                        <td><span class="badge {{ $campaign->statusBadgeClass() }}">{{ $campaign->statusLabel() }}</span></td>
                        <td>{{ number_format($campaign->sent_count) }} / {{ number_format($campaign->recipients_count) }}</td>
                        <td>{{ $campaign->openRate() }}%</td>
                        <td>{{ $campaign->clickRate() }}%</td>
                        <td><a href="{{ route('email.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No campaigns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $campaigns->links() }}</div>
@endsection
