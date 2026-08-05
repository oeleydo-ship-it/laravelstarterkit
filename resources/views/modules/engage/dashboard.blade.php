@extends('layouts.app')

@section('title', 'Engage')

@section('content')
<div class="eg-studio">
    @include('modules.engage._nav')

    <div class="eg-hero">
        <div>
            <h4 class="fw-bold mb-1">Engage</h4>
            <p class="text-muted mb-0 small">On-site bars, popups, forms, and notifications — white-label on your website.</p>
        </div>
        @can('create', App\Models\EngageCampaign::class)
            <a href="{{ route('engage.campaigns.create') }}" class="btn btn-primary">+ New from template</a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Live campaigns', $stats['live'], 'engage.campaigns.index'],
            ['All campaigns', $stats['campaigns'], 'engage.campaigns.index'],
            ['Leads', $stats['leads'], 'engage.leads.index'],
        ] as [$label, $value, $route])
            <div class="col-md-4">
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

    @can('create', App\Models\EngageCampaign::class)
        <div class="eg-section-title">Quick start templates</div>
        <div class="eg-template-grid mb-4">
            @foreach(collect(\App\Support\EngageTemplates::all())->take(4) as $key => $tpl)
                <a class="eg-template-card" href="{{ route('engage.campaigns.create', ['template' => $key]) }}">
                    <div class="eg-template-preview">
                        @php $d = $tpl['defaults']; $sw = $tpl['swatch']; @endphp
                        @if(($d['type'] ?? '') === 'bar')
                            <div class="eg-mini-bar" style="background: {{ $sw }}">{{ \Illuminate\Support\Str::limit($d['headline'] ?? 'Announcement', 36) }}</div>
                        @elseif(($d['type'] ?? '') === 'popup')
                            <div class="eg-mini-card">
                                <div style="font-weight:700;font-size:12px;margin-bottom:4px">{{ \Illuminate\Support\Str::limit($d['headline'] ?? 'Popup', 28) }}</div>
                                <span style="display:inline-block;background:{{ $sw }};color:#fff;border-radius:6px;padding:4px 8px;font-size:10px;font-weight:700">{{ $d['cta_label'] ?? 'Continue' }}</span>
                            </div>
                        @elseif(in_array($d['type'] ?? '', ['slide_in', 'form'], true))
                            <div class="eg-mini-slide">
                                <div style="font-weight:700;font-size:11px;margin-bottom:4px">{{ \Illuminate\Support\Str::limit($d['headline'] ?? 'Panel', 24) }}</div>
                                <span style="display:inline-block;background:{{ $sw }};color:#fff;border-radius:6px;padding:4px 8px;font-size:10px;font-weight:700">{{ $d['cta_label'] ?? 'Submit' }}</span>
                            </div>
                        @elseif(($d['type'] ?? '') === 'toast')
                            <div class="eg-mini-toast">
                                <span class="eg-mini-avatar" style="background: {{ $sw }}">{{ strtoupper(substr($d['toast_name'] ?? 'A', 0, 1)) }}</span>
                                <span>{{ ($d['toast_name'] ?? 'Alex').' '.($d['toast_action'] ?? 'just joined') }}</span>
                            </div>
                        @elseif(($d['type'] ?? '') === 'video')
                            <div class="eg-mini-video">
                                <div class="eg-mini-video-frame">▶ Video</div>
                                <div style="font-weight:700;font-size:11px">{{ \Illuminate\Support\Str::limit($d['headline'] ?? 'Video', 28) }}</div>
                            </div>
                        @else
                            <div class="eg-mini-launcher" style="background: {{ $sw }}">{{ $d['launcher_label'] ?? 'Updates' }}</div>
                        @endif
                    </div>
                    <div class="eg-template-body">
                        <strong>{{ $tpl['label'] }}</strong>
                        <span>{{ $tpl['blurb'] }}</span>
                        <div class="eg-template-meta">
                            <span><i class="eg-dot" style="background: {{ $sw }}"></i> &nbsp;Use template</span>
                            <span>→</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mb-4">
            <a href="{{ route('engage.campaigns.create') }}" class="btn btn-sm btn-outline-secondary">Browse all templates</a>
        </div>
    @endcan

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Recent campaigns</h6>
                    <a href="{{ route('engage.campaigns.index') }}" class="small">View all</a>
                </div>
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentCampaigns as $campaign)
                            <tr>
                                <td class="fw-medium">{{ $campaign->name }}</td>
                                <td>{{ $campaign->typeLabel() }}</td>
                                <td><span class="badge {{ $campaign->statusBadgeClass() }}">{{ $campaign->statusLabel() }}</span></td>
                                <td><a href="{{ route('engage.campaigns.edit', $campaign) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No campaigns yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="table-card mb-4">
                <h6 class="fw-bold mb-3">Install</h6>
                <p class="small text-muted">Add this snippet once to your website. Visitors will never see platform branding.</p>
                @php($snippet = app(\App\Services\Engage\SiteService::class)->embedSnippet($site))
                <pre class="bg-dark text-white rounded p-3 small mb-3" style="white-space: pre-wrap;">{{ $snippet }}</pre>
                <a href="{{ route('engage.install') }}" class="btn btn-sm btn-outline-secondary">Install guide</a>
            </div>
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Recent leads</h6>
                    <a href="{{ route('engage.leads.index') }}" class="small">View all</a>
                </div>
                <ul class="list-unstyled mb-0">
                    @forelse($recentLeads as $lead)
                        <li class="border-bottom py-2">
                            <div class="fw-medium">{{ $lead->name ?: $lead->email ?: 'Visitor' }}</div>
                            <div class="small text-muted">{{ $lead->campaign?->name }} · {{ $lead->created_at?->diffForHumans() }}</div>
                        </li>
                    @empty
                        <li class="text-muted small py-2">No leads yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
