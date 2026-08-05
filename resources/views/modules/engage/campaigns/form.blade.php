@extends('layouts.app')

@section('title', $campaign->exists ? 'Edit campaign' : 'New campaign')

@section('content')
    @include('modules.engage._nav')

    @php
        $c = $campaign->content ?? [];
        $g = $campaign->targeting ?? [];
        $s = $campaign->style ?? [];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $campaign->exists ? 'Edit campaign' : 'New campaign' }}</h4>
            <p class="text-muted mb-0 small">Copy is plain text only — safe to show on any website.</p>
        </div>
        <a href="{{ route('engage.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>

    <form method="POST"
          action="{{ $campaign->exists ? route('engage.campaigns.update', $campaign) : route('engage.campaigns.store') }}"
          class="table-card">
        @csrf
        @if($campaign->exists) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $campaign->name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" required>
                    @foreach(App\Models\EngageCampaign::types() as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $campaign->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach(App\Models\EngageCampaign::statuses() as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $campaign->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-8">
                <label class="form-label">Headline</label>
                <input type="text" name="headline" class="form-control" value="{{ old('headline', $c['headline'] ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Priority</label>
                <input type="number" name="priority" class="form-control" min="0" max="1000"
                       value="{{ old('priority', $campaign->priority ?? 0) }}">
            </div>

            <div class="col-12">
                <label class="form-label">Body</label>
                <textarea name="body" class="form-control" rows="3">{{ old('body', $c['body'] ?? '') }}</textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label">CTA label</label>
                <input type="text" name="cta_label" class="form-control" value="{{ old('cta_label', $c['cta_label'] ?? '') }}">
            </div>
            <div class="col-md-8">
                <label class="form-label">CTA URL</label>
                <input type="text" name="cta_url" class="form-control" value="{{ old('cta_url', $c['cta_url'] ?? '') }}" placeholder="https://">
            </div>

            <div class="col-md-4">
                <label class="form-label">Position</label>
                <select name="position" class="form-select">
                    @foreach(['top','bottom','center','bottom-left','bottom-right','top-left','top-right'] as $pos)
                        <option value="{{ $pos }}" @selected(old('position', $c['position'] ?? '') === $pos)>{{ $pos }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Brand color</label>
                <input type="text" name="brand_color" class="form-control" value="{{ old('brand_color', $s['brand_color'] ?? '#2563eb') }}" placeholder="#2563eb">
            </div>
            <div class="col-md-4">
                <label class="form-label">Text color</label>
                <input type="text" name="text_color" class="form-control" value="{{ old('text_color', $s['text_color'] ?? '#ffffff') }}" placeholder="#ffffff">
            </div>

            <div class="col-12"><hr class="my-2"><h6 class="fw-bold">Lead form fields</h6></div>
            <div class="col-md-3">
                <div class="form-check">
                    <input type="checkbox" name="fields_name" value="1" class="form-check-input" id="fields_name"
                           @checked(old('fields_name', $c['fields']['name'] ?? false))>
                    <label class="form-check-label" for="fields_name">Ask for name</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input type="checkbox" name="fields_email" value="1" class="form-check-input" id="fields_email"
                           @checked(old('fields_email', $c['fields']['email'] ?? true))>
                    <label class="form-check-label" for="fields_email">Ask for email</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Success message</label>
                <input type="text" name="success_message" class="form-control"
                       value="{{ old('success_message', $c['success_message'] ?? '') }}">
            </div>

            <div class="col-12"><hr class="my-2"><h6 class="fw-bold">Toast / social proof</h6></div>
            <div class="col-md-4">
                <label class="form-label">Person name</label>
                <input type="text" name="toast_name" class="form-control" value="{{ old('toast_name', $c['toast']['name'] ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Action</label>
                <input type="text" name="toast_action" class="form-control" value="{{ old('toast_action', $c['toast']['action'] ?? '') }}" placeholder="signed up">
            </div>
            <div class="col-md-4">
                <label class="form-label">Location</label>
                <input type="text" name="toast_location" class="form-control" value="{{ old('toast_location', $c['toast']['location'] ?? '') }}">
            </div>

            <div class="col-12"><hr class="my-2"><h6 class="fw-bold">Launcher</h6></div>
            <div class="col-md-4">
                <label class="form-label">Button label</label>
                <input type="text" name="launcher_label" class="form-control" value="{{ old('launcher_label', $c['launcher_label'] ?? '') }}">
            </div>
            <div class="col-md-8">
                <label class="form-label">Opens campaign</label>
                <select name="opens_campaign_id" class="form-select">
                    <option value="">—</option>
                    @foreach($openable as $item)
                        <option value="{{ $item->id }}" @selected((string) old('opens_campaign_id', $c['opens_campaign_id'] ?? '') === (string) $item->id)>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12"><hr class="my-2"><h6 class="fw-bold">Targeting</h6></div>
            <div class="col-md-6">
                <label class="form-label">URL contains</label>
                <input type="text" name="url_contains" class="form-control" value="{{ old('url_contains', $g['url_contains'] ?? '') }}" placeholder="/pricing">
            </div>
            <div class="col-md-2">
                <label class="form-label">Delay (ms)</label>
                <input type="number" name="delay_ms" class="form-control" min="0" value="{{ old('delay_ms', $g['delay_ms'] ?? 0) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Show again (hours)</label>
                <input type="number" name="frequency_hours" class="form-control" min="0" value="{{ old('frequency_hours', $g['frequency_hours'] ?? 24) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Device</label>
                <select name="device" class="form-select">
                    @foreach(['any' => 'Any', 'desktop' => 'Desktop', 'mobile' => 'Mobile'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('device', $g['device'] ?? 'any') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                @if($campaign->exists)
                    @can('delete', $campaign)
                        <button form="delete-campaign" type="submit" class="btn btn-outline-danger"
                                onclick="return confirm('Delete this campaign?')">Delete</button>
                    @endcan
                @endif
            </div>
            <button class="btn btn-primary">Save campaign</button>
        </div>
    </form>

    @if($campaign->exists)
        <form id="delete-campaign" method="POST" action="{{ route('engage.campaigns.destroy', $campaign) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection
