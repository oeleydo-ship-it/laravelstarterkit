@extends('layouts.app')

@section('title', $campaign->name)

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">{{ $campaign->name }}</h4>
            <p class="text-muted mb-1">{{ $campaign->subject }}</p>
            <span class="badge {{ $campaign->statusBadgeClass() }}">{{ $campaign->statusLabel() }}</span>
            @if($campaign->scheduled_at)
                <span class="text-muted small ms-2">Scheduled {{ $campaign->scheduled_at->format('M d, Y H:i') }}</span>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('email.campaigns.preview', $campaign) }}" class="btn btn-outline-secondary" target="_blank">Preview</a>
            @if($campaign->isEditable())
                @can('update', $campaign)
                    <a href="{{ route('email.campaigns.edit', $campaign) }}" class="btn btn-outline-primary">Edit</a>
                @endcan
                @can('send', $campaign)
                    <form method="POST" action="{{ route('email.campaigns.send', $campaign) }}"
                          onsubmit="return confirm('Send this campaign to all active subscribers on the list now?')">
                        @csrf
                        <button class="btn btn-primary">Send now</button>
                    </form>
                @endcan
            @endif
            @if(in_array($campaign->status, ['scheduled', 'sending'], true))
                @can('send', $campaign)
                    <form method="POST" action="{{ route('email.campaigns.cancel', $campaign) }}"
                          onsubmit="return confirm('Cancel this campaign?')">
                        @csrf
                        <button class="btn btn-outline-danger">Cancel</button>
                    </form>
                @endcan
            @endif
            @can('delete', $campaign)
                <form method="POST" action="{{ route('email.campaigns.destroy', $campaign) }}"
                      onsubmit="return confirm('Delete this campaign?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger">Delete</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Recipients', $campaign->recipients_count],
            ['Sent', $campaign->sent_count],
            ['Failed', $campaign->failed_count],
            ['Opens', $campaign->open_count.' ('.$campaign->openRate().'%)'],
            ['Clicks', $campaign->click_count.' ('.$campaign->clickRate().'%)'],
        ] as [$label, $value])
            <div class="col">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-5 fw-bold">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($campaign->isEditable())
        @can('send', $campaign)
            <div class="card stat-card mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Schedule send</h6>
                    <form method="POST" action="{{ route('email.campaigns.schedule', $campaign) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small" for="scheduled_at">Send at</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control"
                                   value="{{ old('scheduled_at', optional($campaign->scheduled_at)->format('Y-m-d\\TH:i')) }}" required>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-primary">Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endif

    <div class="card stat-card mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-2">Details</h6>
            <dl class="row mb-0 small">
                <dt class="col-sm-3 text-muted">List</dt>
                <dd class="col-sm-9">{{ $campaign->list?->name ?? '—' }}</dd>
                <dt class="col-sm-3 text-muted">From</dt>
                <dd class="col-sm-9">{{ $campaign->from_name }} &lt;{{ $campaign->from_email }}&gt;</dd>
                <dt class="col-sm-3 text-muted">Created by</dt>
                <dd class="col-sm-9">{{ $campaign->creator?->name ?? '—' }}</dd>
                <dt class="col-sm-3 text-muted">Sent at</dt>
                <dd class="col-sm-9">{{ optional($campaign->sent_at)->format('M d, Y H:i') ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    @if($recipients->total() > 0)
        <div class="table-card">
            <h6 class="fw-bold mb-3">Recipients</h6>
            <table class="table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Opens</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recipients as $recipient)
                        <tr>
                            <td>{{ $recipient->email }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($recipient->status) }}</span></td>
                            <td class="small text-muted">{{ optional($recipient->sent_at)->format('M d H:i') ?? '—' }}</td>
                            <td>{{ $recipient->open_count }}</td>
                            <td>{{ $recipient->click_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-2">{{ $recipients->links() }}</div>
        </div>
    @endif
@endsection
