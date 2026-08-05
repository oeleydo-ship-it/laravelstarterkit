@extends('layouts.app')

@section('title', $campaign->exists ? 'Edit campaign' : 'New campaign')

@section('content')
<div class="eg-studio" id="eg-studio">
    @include('modules.engage._nav')

    @php
        $c = $campaign->content ?? [];
        $g = $campaign->targeting ?? [];
        $s = $campaign->style ?? [];
        $brand = old('brand_color', $s['brand_color'] ?? '#0f766e');
        $text = old('text_color', $s['text_color'] ?? '#ffffff');
        $type = old('type', $campaign->type ?? 'bar');
    @endphp

    <div class="eg-hero">
        <div>
            <h4 class="fw-bold mb-1">{{ $campaign->exists ? 'Edit campaign' : 'Customize campaign' }}</h4>
            <p class="text-muted mb-0 small">
                @if(!empty($templateLabel))
                    Template: <strong>{{ $templateLabel }}</strong> — edit anything below; live preview updates as you type.
                @else
                    Design preview updates as you edit. Use full Preview to check every layout type.
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(!$campaign->exists)
                <a href="{{ route('engage.campaigns.create') }}" class="btn btn-sm btn-outline-secondary">Change template</a>
            @endif
            <a href="{{ route('engage.campaigns.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
    </div>

    <form method="POST"
          action="{{ $campaign->exists ? route('engage.campaigns.update', $campaign) : route('engage.campaigns.store') }}"
          id="eg-campaign-form">
        @csrf
        @if($campaign->exists) @method('PUT') @endif

        <div class="eg-editor">
            <div class="eg-panel">
                <h6>Basics</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" data-eg value="{{ old('name', $campaign->name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" data-eg id="eg-type" required>
                            @foreach(App\Models\EngageCampaign::types() as $value => $label)
                                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
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
                        <input type="text" name="headline" class="form-control" data-eg value="{{ old('headline', $c['headline'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Priority</label>
                        <input type="number" name="priority" class="form-control" min="0" max="1000"
                               value="{{ old('priority', $campaign->priority ?? 0) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Body</label>
                        <textarea name="body" class="form-control" rows="3" data-eg>{{ old('body', $c['body'] ?? '') }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">CTA label</label>
                        <input type="text" name="cta_label" class="form-control" data-eg value="{{ old('cta_label', $c['cta_label'] ?? '') }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">CTA URL</label>
                        <input type="text" name="cta_url" class="form-control" value="{{ old('cta_url', $c['cta_url'] ?? '') }}" placeholder="https://">
                    </div>

                    <div class="col-12" data-eg-section="video">
                        <label class="form-label">Video URL</label>
                        <input type="url" name="video_url" class="form-control" data-eg
                               value="{{ old('video_url', $c['video_url'] ?? '') }}"
                               placeholder="https://www.youtube.com/watch?v=… or Vimeo / .mp4">
                        <div class="form-text">YouTube, Vimeo, or a direct HTTPS .mp4 / .webm link.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Position</label>
                        <select name="position" class="form-select" data-eg>
                            @foreach(['top','bottom','center','bottom-left','bottom-right','top-left','top-right'] as $pos)
                                <option value="{{ $pos }}" @selected(old('position', $c['position'] ?? '') === $pos)>{{ $pos }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Brand color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="eg-brand-picker" value="{{ $brand }}" title="Brand color">
                            <input type="text" name="brand_color" class="form-control" data-eg id="eg-brand" value="{{ $brand }}" placeholder="#0f766e" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Text color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="eg-text-picker" value="{{ $text }}" title="Text color">
                            <input type="text" name="text_color" class="form-control" data-eg id="eg-text" value="{{ $text }}" placeholder="#ffffff" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <h6 data-eg-section="form">Lead form fields</h6>
                <div class="row g-3 mb-3" data-eg-section="form">
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="fields_name" value="1" class="form-check-input" id="fields_name" data-eg
                                   @checked(old('fields_name', $c['fields']['name'] ?? false))>
                            <label class="form-check-label" for="fields_name">Ask for name</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" name="fields_email" value="1" class="form-check-input" id="fields_email" data-eg
                                   @checked(old('fields_email', $c['fields']['email'] ?? true))>
                            <label class="form-check-label" for="fields_email">Ask for email</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Success message</label>
                        <input type="text" name="success_message" class="form-control"
                               value="{{ old('success_message', $c['success_message'] ?? '') }}">
                    </div>
                </div>

                <h6 data-eg-section="toast">Toast / social proof</h6>
                <div class="row g-3 mb-3" data-eg-section="toast">
                    <div class="col-md-4">
                        <label class="form-label">Person name</label>
                        <input type="text" name="toast_name" class="form-control" data-eg value="{{ old('toast_name', $c['toast']['name'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Action</label>
                        <input type="text" name="toast_action" class="form-control" data-eg value="{{ old('toast_action', $c['toast']['action'] ?? '') }}" placeholder="signed up">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Location</label>
                        <input type="text" name="toast_location" class="form-control" data-eg value="{{ old('toast_location', $c['toast']['location'] ?? '') }}">
                    </div>
                </div>

                <h6 data-eg-section="launcher">Launcher</h6>
                <div class="row g-3 mb-3" data-eg-section="launcher">
                    <div class="col-md-4">
                        <label class="form-label">Button label</label>
                        <input type="text" name="launcher_label" class="form-control" data-eg value="{{ old('launcher_label', $c['launcher_label'] ?? '') }}">
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
                </div>

                <h6>Display &amp; targeting</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">URL contains</label>
                        <input type="text" name="url_contains" class="form-control" value="{{ old('url_contains', $g['url_contains'] ?? '') }}" placeholder="/pricing">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Delay (ms)</label>
                        <input type="number" name="delay_ms" class="form-control" min="0" value="{{ old('delay_ms', $g['delay_ms'] ?? 0) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cooldown between (hrs)</label>
                        <input type="number" name="frequency_hours" class="form-control" min="0"
                               value="{{ old('frequency_hours', $g['frequency_hours'] ?? 24) }}"
                               title="Hours before showing again after a display. 0 = every eligible visit.">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Max displays</label>
                        <input type="number" name="max_displays" class="form-control" min="0" max="1000"
                               value="{{ old('max_displays', $g['max_displays'] ?? 0) }}"
                               title="How many times to show across reloads. 0 = unlimited.">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Device</label>
                        <select name="device" class="form-select">
                            @foreach(['any' => 'Any', 'desktop' => 'Desktop', 'mobile' => 'Mobile'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('device', $g['device'] ?? 'any') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <p class="form-text mb-0">
                            <strong>Max displays</strong> counts each page load that shows the campaign (reloads included).
                            Use <code>1</code> for once ever, or <code>0</code> for unlimited. Pair with “Repeat between” to space shows out.
                        </p>
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
                    <button class="btn btn-primary px-4">Save campaign</button>
                </div>
            </div>

            <div class="eg-stage-wrap">
                <div class="eg-preview-actions">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="eg-open-preview">Full design preview</button>
                </div>
                <div class="eg-panel p-0 overflow-hidden">
                    <div class="eg-stage" id="eg-stage">
                        <div class="eg-stage-chrome"><i></i><i></i><i></i></div>
                        <div class="eg-stage-canvas" id="eg-preview"></div>
                    </div>
                </div>
                <p class="eg-hint">Live design preview for bars, popups, slide-ins, forms, toasts, launchers, and video.</p>
            </div>
        </div>
    </form>

    <div class="eg-modal-backdrop" id="eg-preview-modal" aria-hidden="true">
        <div class="eg-modal" role="dialog" aria-modal="true" aria-labelledby="eg-preview-title">
            <div class="eg-modal-head">
                <strong id="eg-preview-title">Design preview</strong>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="eg-close-preview">Close</button>
            </div>
            <div class="eg-modal-stage">
                <div class="eg-stage-canvas" id="eg-preview-large"></div>
            </div>
        </div>
    </div>

    @if($campaign->exists)
        <form id="delete-campaign" method="POST" action="{{ route('engage.campaigns.destroy', $campaign) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('eg-campaign-form');
    const preview = document.getElementById('eg-preview');
    const previewLarge = document.getElementById('eg-preview-large');
    const modal = document.getElementById('eg-preview-modal');
    if (!form || !preview) return;

    const brandPicker = document.getElementById('eg-brand-picker');
    const textPicker = document.getElementById('eg-text-picker');
    const brandInput = document.getElementById('eg-brand');
    const textInput = document.getElementById('eg-text');

    function val(name) {
        const el = form.elements.namedItem(name);
        if (!el) return '';
        if (el.type === 'checkbox') return el.checked;
        return el.value || '';
    }

    function esc(s) {
        return String(s || '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]);
    }

    function slideClass(pos) {
        if (pos === 'bottom-left') return 'bl';
        if (pos === 'top-right') return 'tr';
        if (pos === 'top-left') return 'tl';
        return 'br';
    }

    function videoEmbed(raw) {
        if (!raw) return null;
        try {
            const url = new URL(String(raw).trim());
            if (url.protocol !== 'https:') return null;
            const host = url.hostname.replace(/^www\./, '');
            if (host === 'youtu.be') {
                const id = url.pathname.split('/').filter(Boolean)[0];
                return id ? { type: 'iframe', src: 'https://www.youtube.com/embed/' + encodeURIComponent(id) + '?rel=0' } : null;
            }
            if (host === 'youtube.com' || host === 'm.youtube.com' || host === 'youtube-nocookie.com') {
                let id = url.searchParams.get('v');
                if (!id && url.pathname.startsWith('/embed/')) id = url.pathname.split('/')[2];
                if (!id && url.pathname.startsWith('/shorts/')) id = url.pathname.split('/')[2];
                return id ? { type: 'iframe', src: 'https://www.youtube.com/embed/' + encodeURIComponent(id) + '?rel=0' } : null;
            }
            if (host === 'vimeo.com' || host === 'player.vimeo.com') {
                const parts = url.pathname.split('/').filter(Boolean);
                const id = host === 'player.vimeo.com' ? parts[1] : parts[0];
                return id && /^\d+$/.test(id) ? { type: 'iframe', src: 'https://player.vimeo.com/video/' + id } : null;
            }
            if (/\.(mp4|webm|ogg)(\?|$)/i.test(url.pathname)) return { type: 'video', src: url.href };
        } catch (e) {}
        return null;
    }

    function syncSections(type) {
        document.querySelectorAll('[data-eg-section]').forEach((node) => {
            const section = node.getAttribute('data-eg-section');
            let show = false;
            if (section === 'form') show = type === 'form';
            if (section === 'toast') show = type === 'toast';
            if (section === 'launcher') show = type === 'launcher';
            if (section === 'video') show = type === 'video';
            node.style.display = show ? '' : 'none';
        });
    }

    function buildHtml() {
        const type = val('type');
        const brand = val('brand_color') || '#0f766e';
        const text = val('text_color') || '#ffffff';
        const headline = val('headline') || 'Headline';
        const body = val('body');
        const cta = val('cta_label') || 'Continue';
        const pos = val('position') || 'center';
        const toastName = val('toast_name') || 'Alex';
        const toastAction = val('toast_action') || 'just joined';
        const toastLoc = val('toast_location');
        const launcher = val('launcher_label') || 'Updates';
        const askName = !!val('fields_name');
        const askEmail = !!val('fields_email');
        let html = '';

        if (type === 'bar') {
            const side = pos === 'bottom' ? 'bottom' : 'top';
            html = `<div class="eg-pv-bar ${side}" style="background:${esc(brand)};color:${esc(text)}">
                <span>${esc(headline)}${body ? ' · ' + esc(body) : ''}</span>
                ${cta ? `<span class="eg-pv-btn" style="background:rgba(255,255,255,.2);color:${esc(text)}">${esc(cta)}</span>` : ''}
                <button type="button" class="eg-pv-close" style="color:${esc(text)}">×</button>
            </div>`;
        } else if (type === 'video') {
            const media = videoEmbed(val('video_url'));
            let mediaHtml = '<div class="eg-pv-play">▶</div>';
            if (media?.type === 'iframe') {
                mediaHtml = `<iframe src="${esc(media.src)}" title="Video preview" allowfullscreen loading="lazy"></iframe>`;
            } else if (media?.type === 'video') {
                mediaHtml = `<video src="${esc(media.src)}" controls playsinline></video>`;
            }
            html = `<div class="eg-pv-overlay"><div class="eg-pv-card eg-pv-video-card">
                <button type="button" class="eg-pv-close">×</button>
                <div class="eg-pv-title">${esc(headline)}</div>
                ${body ? `<div class="eg-pv-body">${esc(body)}</div>` : ''}
                <div class="eg-pv-video-frame">${mediaHtml}</div>
                <button type="button" class="eg-pv-btn" style="background:${esc(brand)};color:${esc(text)}">${esc(cta)}</button>
            </div></div>`;
        } else if (type === 'popup' || (type === 'form' && (pos === 'center' || !pos))) {
            const fields = type === 'form'
                ? `${askName ? '<div class="eg-pv-field">Your name</div>' : ''}${askEmail !== false ? '<div class="eg-pv-field">Email address</div>' : ''}`
                : '';
            html = `<div class="eg-pv-overlay"><div class="eg-pv-card">
                <button type="button" class="eg-pv-close">×</button>
                <div class="eg-pv-title">${esc(headline)}</div>
                ${body ? `<div class="eg-pv-body">${esc(body)}</div>` : ''}
                ${fields}
                <button type="button" class="eg-pv-btn" style="background:${esc(brand)};color:${esc(text)}">${esc(cta)}</button>
            </div></div>`;
        } else if (type === 'slide_in' || type === 'form') {
            const fields = type === 'form'
                ? `${askName ? '<div class="eg-pv-field">Your name</div>' : ''}<div class="eg-pv-field">Email address</div>`
                : '';
            html = `<div class="eg-pv-slide ${slideClass(pos)}">
                <button type="button" class="eg-pv-close">×</button>
                <div class="eg-pv-title">${esc(headline)}</div>
                ${body ? `<div class="eg-pv-body">${esc(body)}</div>` : ''}
                ${fields}
                <button type="button" class="eg-pv-btn" style="background:${esc(brand)};color:${esc(text)}">${esc(cta)}</button>
            </div>`;
        } else if (type === 'toast') {
            const initial = (toastName || 'A').charAt(0).toUpperCase();
            const side = pos === 'bottom-right' ? 'br' : 'bl';
            html = `<div class="eg-pv-toast ${side}">
                <span class="eg-pv-avatar" style="background:${esc(brand)}">${esc(initial)}</span>
                <span><strong>${esc(toastName)}</strong> ${esc(toastAction)}${toastLoc ? `<br><span style="color:#64748b;font-size:11px">${esc(toastLoc)}</span>` : ''}</span>
            </div>`;
        } else {
            html = `<button type="button" class="eg-pv-launcher" style="background:${esc(brand)};color:${esc(text)}">${esc(launcher)}</button>`;
        }

        return html;
    }

    function render() {
        syncSections(val('type'));
        const html = buildHtml();
        preview.innerHTML = html;
        if (previewLarge) previewLarge.innerHTML = html;
    }

    function syncPicker(picker, input) {
        if (!picker || !input) return;
        picker.addEventListener('input', () => { input.value = picker.value; render(); });
        input.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(input.value)) picker.value = input.value;
            render();
        });
    }

    syncPicker(brandPicker, brandInput);
    syncPicker(textPicker, textInput);

    form.querySelectorAll('[data-eg]').forEach((el) => {
        el.addEventListener('input', render);
        el.addEventListener('change', render);
    });

    document.getElementById('eg-open-preview')?.addEventListener('click', () => {
        render();
        modal?.classList.add('is-open');
        modal?.setAttribute('aria-hidden', 'false');
    });
    document.getElementById('eg-close-preview')?.addEventListener('click', () => {
        modal?.classList.remove('is-open');
        modal?.setAttribute('aria-hidden', 'true');
    });
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
    });

    render();
})();
</script>
@endpush
