@extends('layouts.app')

@section('title', 'Email Marketing')

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Email Marketing</h4>
            <p class="text-muted mb-0 small">Campaigns, lists, and subscriber engagement for your workspace.</p>
        </div>
        @can('create', App\Models\EmailCampaign::class)
            <a href="{{ route('email.campaigns.create') }}" class="btn btn-primary">+ New Campaign</a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Lists', $stats['lists'], 'email.lists.index'],
            ['Subscribers', $stats['subscribers'], 'email.subscribers.index'],
            ['Templates', $stats['templates'], 'email.templates.index'],
            ['Campaigns', $stats['campaigns'], 'email.campaigns.index'],
            ['Sent', $stats['sent'], 'email.reports.index'],
            ['Opens', $stats['opens'], 'email.reports.index'],
            ['Clicks', $stats['clicks'], 'email.reports.index'],
        ] as [$label, $value, $route])
            <div class="col-6 col-md-3 col-xl">
                <a href="{{ route($route) }}" class="text-decoration-none">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="fs-4 fw-bold text-dark">{{ number_format($value) }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Recent campaigns</h6>
            <a href="{{ route('email.campaigns.index') }}" class="small">View all</a>
        </div>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>List</th>
                    <th>Status</th>
                    <th>Sent</th>
                    <th>Open rate</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentCampaigns as $campaign)
                    <tr>
                        <td class="fw-medium">{{ $campaign->name }}</td>
                        <td>{{ $campaign->list?->name ?? '—' }}</td>
                        <td><span class="badge {{ $campaign->statusBadgeClass() }}">{{ $campaign->statusLabel() }}</span></td>
                        <td>{{ number_format($campaign->sent_count) }} / {{ number_format($campaign->recipients_count) }}</td>
                        <td>{{ $campaign->openRate() }}%</td>
                        <td><a href="{{ route('email.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No campaigns yet.
                            @can('create', App\Models\EmailCampaign::class)
                                <a href="{{ route('email.campaigns.create') }}">Create your first campaign</a>.
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
