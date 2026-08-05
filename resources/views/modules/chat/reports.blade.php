@extends('layouts.app')

@section('title', 'Chat Reports')

@php use App\Services\Chat\ReportService; @endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Chat Reports</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('chat.reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'type' => 'conversations']) }}"
               class="btn btn-sm btn-outline-secondary">Export conversations</a>
            <a href="{{ route('chat.reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'type' => 'agents']) }}"
               class="btn btn-sm btn-outline-secondary">Export agents</a>
            <a href="{{ route('chat.conversations.index') }}" class="btn btn-sm btn-outline-secondary">Back to Inbox</a>
        </div>
    </div>

    <form method="GET" action="{{ route('chat.reports.index') }}" class="row g-2 align-items-end mb-4" style="max-width: 520px;">
        <div class="col-auto">
            <label for="from" class="form-label fw-medium small mb-1">From</label>
            <input type="date" class="form-control form-control-sm" id="from" name="from" value="{{ $from->toDateString() }}">
        </div>
        <div class="col-auto">
            <label for="to" class="form-label fw-medium small mb-1">To</label>
            <input type="date" class="form-control form-control-sm" id="to" name="to" value="{{ $to->toDateString() }}">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-primary">Apply</button>
        </div>
    </form>

    <div class="row g-3 mb-4">
        @php
            $tiles = [
                ['Conversations', $summary['conversations']],
                ['Closed', $summary['closed']],
                ['Still open', $summary['open']],
                ['Unanswered', $summary['unanswered']],
                ['Visitor messages', $summary['visitor_messages']],
                ['Agent replies', $summary['agent_messages']],
                ['Avg first response', ReportService::humanDuration($summary['avg_first_response_seconds'])],
                ['Median first response', ReportService::humanDuration($summary['median_first_response_seconds'])],
                ['Avg time to close', ReportService::humanDuration($summary['avg_resolution_seconds'])],
                ['Avg rating', $summary['avg_rating'] !== null ? $summary['avg_rating'].' / '.\App\Models\ChatConversation::MAX_RATING : '—'],
                ['Ratings received', $summary['rated']],
                ['Rating response rate', $summary['rating_response_rate'] !== null ? $summary['rating_response_rate'].'%' : '—'],
            ];
        @endphp

        @foreach($tiles as [$label, $value])
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card stat-card h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-4 fw-bold">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <h6 class="fw-bold mb-2">By agent</h6>
    <div class="table-card mb-4">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Conversations</th>
                    <th>Closed</th>
                    <th>Replies</th>
                    <th>Avg first response</th>
                    <th>Avg rating</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perAgent as $row)
                    <tr>
                        <td class="fw-medium">{{ $row['agent'] }}</td>
                        <td>{{ $row['conversations'] }}</td>
                        <td>{{ $row['closed'] }}</td>
                        <td>{{ $row['replies'] }}</td>
                        <td>{{ ReportService::humanDuration($row['avg_first_response_seconds']) }}</td>
                        <td>
                            @if($row['avg_rating'] !== null)
                                {{ $row['avg_rating'] }}
                                <span class="text-muted small">({{ $row['rated'] }})</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No agents yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h6 class="fw-bold mb-2">Daily volume</h6>
    <div class="table-card">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Conversations</th>
                    <th>Messages</th>
                </tr>
            </thead>
            <tbody>
                @foreach($daily as $day)
                    <tr>
                        <td>{{ $day['date'] }}</td>
                        <td>{{ $day['conversations'] }}</td>
                        <td>{{ $day['messages'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
