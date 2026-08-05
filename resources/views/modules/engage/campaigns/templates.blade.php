@extends('layouts.app')

@section('title', 'Choose a template')

@section('content')
<div class="eg-studio">
    @include('modules.engage._nav')

    <div class="eg-hero">
        <div>
            <h4 class="fw-bold mb-1">Start from a template</h4>
            <p class="text-muted mb-0 small">Prefilled copy, colors, and timing — edit anything after you pick one.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('engage.campaigns.create', ['template' => 'blank']) }}" class="btn btn-outline-secondary btn-sm">Blank campaign</a>
            <a href="{{ route('engage.campaigns.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    @foreach($groups as $category => $templates)
        <div class="eg-section-title">{{ $category }}</div>
        <div class="eg-template-grid mb-2">
            @foreach($templates as $tpl)
                <a class="eg-template-card" href="{{ route('engage.campaigns.create', ['template' => $tpl['key']]) }}">
                    <div class="eg-template-preview">
                        @php $d = $tpl['defaults']; $sw = $tpl['swatch']; @endphp
                        @if(($d['type'] ?? '') === 'bar')
                            <div class="eg-mini-bar" style="background: {{ $sw }}">{{ \Illuminate\Support\Str::limit($d['headline'] ?? 'Announcement', 36) }}</div>
                        @elseif(($d['type'] ?? '') === 'popup')
                            <div class="eg-mini-card">
                                <div style="font-weight:700;font-size:12px;margin-bottom:4px">{{ \Illuminate\Support\Str::limit($d['headline'] ?? 'Popup', 28) }}</div>
                                <div style="font-size:10px;color:#64748b;margin-bottom:8px">{{ \Illuminate\Support\Str::limit($d['body'] ?? '', 48) }}</div>
                                <span style="display:inline-block;background:{{ $sw }};color:#fff;border-radius:6px;padding:4px 8px;font-size:10px;font-weight:700">{{ $d['cta_label'] ?? 'Continue' }}</span>
                            </div>
                        @elseif(in_array($d['type'] ?? '', ['slide_in', 'form'], true))
                            <div class="eg-mini-slide">
                                <div style="font-weight:700;font-size:11px;margin-bottom:4px">{{ \Illuminate\Support\Str::limit($d['headline'] ?? 'Panel', 24) }}</div>
                                <div style="font-size:10px;color:#64748b;margin-bottom:8px">{{ \Illuminate\Support\Str::limit($d['body'] ?? '', 40) }}</div>
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
    @endforeach
</div>
@endsection
