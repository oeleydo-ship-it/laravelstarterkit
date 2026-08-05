@extends('layouts.app')

@section('title', 'Chat Settings')

@php
    // Which pane opens: an explicit ?tab wins; otherwise a failed save reopens
    // the tab that owns the first error, so the message is never on a hidden
    // pane. Falls back to the first tab.
    $errorTabs = [
        'routing_strategy' => 'routing',
        'title' => 'appearance',
        'greeting' => 'appearance',
        'launcher_text' => 'appearance',
        'color' => 'appearance',
        'offline_message' => 'appearance',
        'pre_chat_enabled' => 'appearance',
        'pre_chat_message' => 'appearance',
        'enabled' => 'hours',
        'timezone' => 'hours',
        'days' => 'hours',
        'mail_enabled' => 'notifications',
        'slack_webhook_url' => 'notifications',
        'discord_webhook_url' => 'notifications',
        'telegram_bot_token' => 'notifications',
        'telegram_chat_id' => 'notifications',
        'webhook_url' => 'notifications',
        'webhook_secret' => 'notifications',
        'name' => 'tokens',
        'document' => 'knowledge',
        'auto_reply' => 'knowledge',
        'provider' => 'knowledge',
        'openai_key' => 'knowledge',
        'openai_model' => 'knowledge',
        'openai_base_url' => 'knowledge',
        'kimi_key' => 'knowledge',
        'kimi_model' => 'knowledge',
        'kimi_base_url' => 'knowledge',
        'anthropic_key' => 'knowledge',
        'anthropic_model' => 'knowledge',
        'anthropic_base_url' => 'knowledge',
    ];

    // Deliberately not `$current` — the controller already passes that for the
    // selected routing strategy, and a partial would silently read the wrong one.
    $currentTab = $activeTab;

    if (! $currentTab && $errors->any()) {
        $firstError = Str::before(array_key_first($errors->getMessages()), '.');
        $currentTab = $errorTabs[$firstError] ?? null;
    }

    $currentTab ??= array_key_first($tabs);
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Chat Settings</h4>
    <a href="{{ route('chat.conversations.index') }}" class="btn btn-sm btn-outline-secondary">Back to Inbox</a>
</div>

<div class="row g-4">
    <div class="col-lg-3">
        <div class="list-group chat-settings-nav" role="tablist">
            @foreach($tabs as $key => $label)
                <a class="list-group-item list-group-item-action {{ $currentTab === $key ? 'active' : '' }}"
                   id="tab-{{ $key }}"
                   data-bs-toggle="list"
                   href="#pane-{{ $key }}"
                   role="tab"
                   aria-controls="pane-{{ $key }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="col-lg-9">
        <div class="tab-content">
            @foreach($tabs as $key => $label)
                <div class="tab-pane fade {{ $currentTab === $key ? 'show active' : '' }}"
                     id="pane-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}">
                    @include('modules.chat.settings.'.$key)
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Keep the address bar in step with the open tab, so a refresh or a shared
     link lands on the same pane the server already understands. --}}
<script>
    document.querySelectorAll('[data-bs-toggle="list"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (event) {
            var key = event.target.id.replace('tab-', '');
            var url = new URL(window.location);
            url.searchParams.set('tab', key);
            window.history.replaceState({}, '', url);
        });
    });
</script>
@endsection
