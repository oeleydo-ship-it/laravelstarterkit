@extends('layouts.app')

@section('title', 'Email Reports')

@section('content')
    @include('modules.email._nav')

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Reports</h4>
        <p class="text-muted mb-0 small">Delivery and engagement across your email marketing activity.</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Active subscribers', $totals['subscribers']],
            ['Unsubscribed', $totals['unsubscribed']],
            ['Emails sent', $totals['emails_sent']],
            ['Opens', $totals['opens']],
            ['Clicks', $totals['clicks']],
            ['Failed', $totals['failed']],
        ] as [$label, $value])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-4 fw-bold">{{ number_format($value) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="table-card">
                <h6 class="fw-bold mb-3">Campaign performance</h6>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Sent</th>
                            <th>Open %</th>
                            <th>Click %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $campaign)
                            <tr>
                                <td><a href="{{ route('email.campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td>
                                <td>{{ number_format($campaign->sent_count) }}</td>
                                <td>{{ $campaign->openRate() }}%</td>
                                <td>{{ $campaign->clickRate() }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No sent campaigns yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="table-card">
                <h6 class="fw-bold mb-3">Top links</h6>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Clicks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topLinks as $link)
                            <tr>
                                <td class="small text-break">{{ \Illuminate\Support\Str::limit($link->url, 60) }}</td>
                                <td>{{ number_format($link->clicks) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-muted text-center py-3">No click data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
