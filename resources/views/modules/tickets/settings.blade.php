@extends('layouts.app')
@section('title', 'Ticket Settings')
@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="fw-bold mb-1">Ticket Settings</h4><p class="text-muted mb-0">Configure defaults and live-chat automation.</p></div><a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Back</a></div>
    <form method="POST" action="{{ route('tickets.settings.update') }}" class="card stat-card"><div class="card-body p-4">@csrf @method('PUT')
        <div class="row g-3 mb-4"><div class="col-md-6"><label class="form-label">Default priority</label><select name="default_priority" class="form-select">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" @selected($settings['default_priority']===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Default assignee</label><select name="default_assignee_id" class="form-select"><option value="">Unassigned</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($settings['default_assignee_id']==$user->id)>{{ $user->name }}</option>@endforeach</select></div></div>
        <h6>AI and live chat</h6><p class="text-muted small">Uses the AI provider configured under Live Chat settings.</p>
        <div class="form-check form-switch mb-3"><input type="hidden" name="ai_creation_enabled" value="0"><input class="form-check-input" type="checkbox" name="ai_creation_enabled" value="1" id="ai_creation_enabled" @checked($settings['ai_creation_enabled'])><label class="form-check-label" for="ai_creation_enabled">Allow agents to create AI-generated tickets from conversations</label></div>
        <div class="form-check form-switch mb-4"><input type="hidden" name="ai_auto_create_on_close" value="0"><input class="form-check-input" type="checkbox" name="ai_auto_create_on_close" value="1" id="ai_auto_create_on_close" @checked($settings['ai_auto_create_on_close'])><label class="form-check-label" for="ai_auto_create_on_close">Automatically create an AI ticket when a chat closes</label></div>
        <button class="btn btn-primary">Save settings</button>
    </div></form>
</div></div>
@endsection
