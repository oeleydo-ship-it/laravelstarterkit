@extends('layouts.app')

@section('title', 'Campaigns')

@section('content')
<div class="eg-studio">
    @include('modules.engage._nav')

    <div class="eg-hero">
        <div>
            <h4 class="fw-bold mb-1">Campaigns</h4>
            <p class="text-muted mb-0 small">Bars, popups, forms, toasts, and launchers shown on your site.</p>
        </div>
        @can('create', App\Models\EngageCampaign::class)
            <a href="{{ route('engage.campaigns.create') }}" class="btn btn-primary">+ New from template</a>
        @endcan
    </div>

    <div class="eg-type-pills">
        <a href="{{ route('engage.campaigns.index') }}" class="{{ !request('type') ? 'is-active' : '' }}">All</a>
        @foreach(App\Models\EngageCampaign::types() as $value => $label)
            <a href="{{ route('engage.campaigns.index', ['type' => $value, 'status' => request('status')]) }}"
               class="{{ request('type') === $value ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        @if(request('type'))
            <input type="hidden" name="type" value="{{ request('type') }}">
        @endif
        <select name="status" class="form-select form-select-sm" style="max-width: 160px" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach(App\Models\EngageCampaign::statuses() as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <div class="table-card">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td class="fw-medium">{{ $campaign->name }}</td>
                        <td>{{ $campaign->typeLabel() }}</td>
                        <td><span class="badge {{ $campaign->statusBadgeClass() }}">{{ $campaign->statusLabel() }}</span></td>
                        <td>{{ $campaign->priority }}</td>
                        <td class="text-end">
                            <a href="{{ route('engage.campaigns.edit', $campaign) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <div class="mb-2">No campaigns yet.</div>
                            @can('create', App\Models\EngageCampaign::class)
                                <a href="{{ route('engage.campaigns.create') }}" class="btn btn-sm btn-primary">Pick a template</a>
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $campaigns->links() }}</div>
</div>
@endsection
