@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    @include('modules.engage._nav')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Leads</h4>
            <p class="text-muted mb-0 small">Captured from on-site forms.</p>
        </div>
        <a href="{{ route('engage.leads.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">Export CSV</a>
    </div>

    <form method="GET" class="mb-3">
        <select name="campaign_id" class="form-select form-select-sm" style="max-width: 280px" onchange="this.form.submit()">
            <option value="">All campaigns</option>
            @foreach($campaigns as $campaign)
                <option value="{{ $campaign->id }}" @selected((string) request('campaign_id') === (string) $campaign->id)>
                    {{ $campaign->name }}
                </option>
            @endforeach
        </select>
    </form>

    <div class="table-card">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Contact</th>
                    <th>Campaign</th>
                    <th>Page</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $lead->name ?: '—' }}</div>
                            <div class="small text-muted">{{ $lead->email }}</div>
                        </td>
                        <td>{{ $lead->campaign?->name }}</td>
                        <td class="small text-truncate" style="max-width: 240px">{{ $lead->page_url }}</td>
                        <td class="small">{{ $lead->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No leads yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $leads->links() }}</div>
@endsection
