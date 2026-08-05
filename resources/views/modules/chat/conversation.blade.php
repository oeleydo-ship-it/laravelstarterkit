@extends('layouts.app')

@section('title', 'Conversation')

@php
    $visitor = $conversation->visitor;
    $visitorLabel = $visitor->displayName();
    $isAssignedToMe = (int) $conversation->assigned_to === (int) auth()->id();
    $canReply = $isAssignedToMe && $conversation->status === 'open';
    $isUnassigned = $conversation->assigned_to === null;
    $pageVisits = collect($visitor->page_visits ?? []);
@endphp

@section('content')
<div class="chat-workspace">
    {{-- Left: people who messaged --}}
    <aside class="chat-workspace__people" aria-label="Conversations">
        <div class="chat-workspace__people-head">
            <h2>Inbox</h2>
            <a href="{{ route('chat.conversations.index') }}" class="chat-workspace__people-all">All</a>
        </div>
        <div class="chat-workspace__people-list">
            @forelse($sidebarConversations as $item)
                @php
                    $itemLabel = $item->visitor?->displayName() ?? ('Visitor #'.$item->chat_visitor_id);
                    $isActive = (int) $item->id === (int) $conversation->id;
                @endphp
                <a href="{{ route('chat.conversations.show', $item) }}"
                   class="chat-workspace__person {{ $isActive ? 'is-active' : '' }}">
                    <div class="chat-workspace__avatar" aria-hidden="true">
                        {{ strtoupper(substr($itemLabel, 0, 1)) }}
                    </div>
                    <div class="chat-workspace__person-body">
                        <div class="chat-workspace__person-top">
                            <strong>{{ $itemLabel }}</strong>
                            <time>{{ $item->last_message_at?->diffForHumans(short: true) ?? $item->created_at->diffForHumans(short: true) }}</time>
                        </div>
                        <p>{{ $item->last_message_preview ?? 'No messages yet' }}</p>
                        <div class="chat-workspace__person-tags">
                            @if($item->unread_count)
                                <span class="chat-inbox__unread">{{ $item->unread_count }}</span>
                            @endif
                            @if(! $item->assigned_to)
                                <span class="chat-pill chat-pill--warn">Needs accept</span>
                            @elseif((int) $item->assigned_to === (int) auth()->id())
                                <span class="chat-pill chat-pill--agent">You</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="chat-workspace__people-empty">No open chats yet.</div>
            @endforelse
        </div>
    </aside>

    {{-- Center: conversation --}}
    <section class="chat-workspace__main">
        <div class="chat-desk chat-desk--embedded">
            <div class="chat-desk__toolbar">
                <div class="chat-desk__identity">
                    <div>
                        <h1 class="chat-desk__title">{{ $visitorLabel }}</h1>
                        <div class="chat-desk__meta">
                            <span class="chat-pill chat-pill--{{ $conversation->status === 'open' ? 'open' : 'closed' }}" id="chat-status-badge">
                                {{ ucfirst($conversation->status) }}
                            </span>
                            @if($conversation->assignee)
                                <span class="chat-pill chat-pill--agent">{{ $conversation->assignee->name }}</span>
                            @else
                                <span class="chat-pill chat-pill--muted">Unassigned</span>
                            @endif
                            @if($conversation->isRated())
                                <span class="chat-rating-badge">
                                    {{ str_repeat('★', $conversation->rating) }}{{ str_repeat('☆', \App\Models\ChatConversation::MAX_RATING - $conversation->rating) }}
                                </span>
                            @endif
                        </div>
                        @if($conversation->isRated() && filled($conversation->rating_comment))
                            <p class="chat-desk__feedback">“{{ $conversation->rating_comment }}”</p>
                        @endif
                    </div>
                </div>

                <div class="chat-desk__actions">
                    @if($conversation->status === 'open' && $isUnassigned)
                        <form method="POST" action="{{ route('chat.conversations.update', $conversation) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="accept">
                            <button class="btn btn-primary btn-sm chat-desk__accept">Accept chat</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('chat.conversations.update', $conversation) }}" class="chat-desk__assign">
                        @csrf
                        @method('PUT')
                        <select name="assigned_to" class="form-select form-select-sm"
                            onchange="this.form.elements.action.value = this.value ? 'assign' : 'unassign'; this.form.submit();"
                            aria-label="Assign conversation">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ (int) $conversation->assigned_to === (int) $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}{{ (int) $user->id === (int) auth()->id() ? ' (you)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="action" value="assign">
                    </form>

                    @if($conversation->status === 'open')
                        <form method="POST" action="{{ route('chat.conversations.update', $conversation) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="close">
                            <button class="btn btn-sm btn-outline-danger">Close</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('chat.conversations.update', $conversation) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="action" value="reopen">
                            <button class="btn btn-sm btn-outline-success">Reopen</button>
                        </form>
                    @endif

                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                            data-bs-target="#transfer-panel">Transfer</button>
                </div>
            </div>

            <div class="collapse mb-3" id="transfer-panel">
                <div class="chat-desk__transfer">
                    <form method="POST" action="{{ route('chat.conversations.transfer', $conversation) }}"
                          class="row g-2 align-items-end">
                        @csrf
                        @method('PUT')
                        <div class="col-md-4">
                            <label for="transfer-to" class="form-label fw-medium small mb-1">Transfer to</label>
                            <select name="to" id="transfer-to" class="form-select form-select-sm" required>
                                <option value="">Choose an agent…</option>
                                @foreach($users->where('id', '!=', auth()->id()) as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}{{ $user->chat_availability === 'online' ? ' — online' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="transfer-reason" class="form-label fw-medium small mb-1">Reason (optional)</label>
                            <input type="text" name="reason" id="transfer-reason" class="form-control form-control-sm"
                                   maxlength="500" placeholder="Billing question — needs finance">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-primary w-100">Transfer</button>
                        </div>
                    </form>
                    @error('to') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="chat-desk__panel">
                <div id="chat-thread" class="chat-thread chat-desk__thread"
                     data-tenant-id="{{ $conversation->tenant_id }}"
                     data-conversation-id="{{ $conversation->id }}"
                     data-can-reply="{{ $canReply ? '1' : '0' }}"
                     data-send-url="{{ route('chat.conversations.messages.store', $conversation) }}"
                     data-messages-url="{{ route('chat.conversations.messages.index', $conversation) }}"
                     data-read-url="{{ route('chat.conversations.read', $conversation) }}"
                     data-typing-url="{{ route('chat.conversations.typing', $conversation) }}"
                     data-note-url="{{ route('chat.conversations.notes.store', $conversation) }}"
                     data-attachment-url="{{ route('chat.conversations.attachments.store', $conversation) }}"
                     data-suggest-url="{{ route('chat.conversations.suggest', $conversation) }}"
                     data-article-search-url="{{ route('chat.articles.search') }}">
                    @foreach($conversation->messages as $message)
                        @if($message->is_internal)
                            <div class="chat-message chat-message-internal" data-id="{{ $message->id }}">
                                <div class="chat-message-meta">
                                    <span class="badge bg-warning text-dark">Internal note</span>
                                    <span>{{ $message->sender->name ?? 'Agent' }} · {{ $message->created_at->format('h:i A') }}</span>
                                </div>
                                <div class="chat-message-body">{{ $message->body }}</div>
                            </div>
                        @else
                            <div class="chat-message chat-message-{{ $message->sender_type }}" data-id="{{ $message->id }}">
                                <div class="chat-message-bubble">
                                    <div class="chat-message-meta">
                                        {{ $message->displayName() }}
                                        · {{ $message->created_at->format('h:i A') }}
                                    </div>
                                    <div class="chat-message-body">{{ $message->body }}</div>
                                    @if($message->attachment)
                                        <a class="chat-attachment" target="_blank" rel="noopener"
                                           href="{{ route('chat.attachments.download', $message->attachment) }}">
                                            📎 {{ $message->attachment->original_name }}
                                            <span>({{ $message->attachment->humanSize() }})</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div id="chat-typing-indicator" class="chat-typing-indicator" style="display:none;"></div>

                <div class="chat-desk__composer">
                    @if(! $canReply)
                        <div class="chat-desk__gate">
                            @if($conversation->status !== 'open')
                                <strong>This conversation is closed.</strong>
                                <span>Reopen it if you need to continue the thread. Internal notes are still available.</span>
                            @elseif($isUnassigned)
                                <strong>Accept this chat to reply.</strong>
                                <span>Visitors see who accepted once you take the conversation.</span>
                                <form method="POST" action="{{ route('chat.conversations.update', $conversation) }}" class="mt-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="accept">
                                    <button class="btn btn-primary btn-sm">Accept chat</button>
                                </form>
                            @else
                                <strong>Assigned to {{ $conversation->assignee->name }}.</strong>
                                <span>Only the assigned agent can send visitor-visible replies.</span>
                            @endif
                        </div>
                    @endif

                    <ul class="nav nav-pills nav-sm mb-2" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link {{ $canReply ? 'active' : '' }} btn-sm" data-chat-mode="reply" type="button"
                                    @disabled(! $canReply)>Reply</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link {{ $canReply ? '' : 'active' }} btn-sm" data-chat-mode="note" type="button">Internal note</button>
                        </li>
                    </ul>

                    <div class="d-flex gap-2 align-items-center mb-2" id="chat-reply-tools" @style([ 'display: none' => ! $canReply ])>
                        <input type="search" id="chat-article-search" class="form-control form-control-sm"
                               placeholder="Search the knowledge base…" autocomplete="off">
                        @if($aiAvailable)
                            <button type="button" id="chat-suggest-button" class="btn btn-sm btn-outline-primary text-nowrap">
                                Suggest reply
                            </button>
                        @endif
                    </div>
                    <div id="chat-article-results" class="list-group list-group-flush mb-2 d-none"></div>

                    @if($cannedResponses->isNotEmpty())
                        <select id="chat-canned" class="form-select form-select-sm mb-2" @style([ 'display: none' => ! $canReply ])>
                            <option value="">Insert a canned reply…</option>
                            @foreach($cannedResponses as $canned)
                                <option value="{{ $canned->body }}">
                                    {{ $canned->title }}@if($canned->shortcut) ({{ $canned->shortcut }})@endif
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <form id="chat-send-form" class="chat-send-form {{ $canReply ? '' : 'chat-note-mode' }}">
                        <div class="chat-send-form__row">
                            <input type="text" id="chat-message-input" class="form-control"
                                   placeholder="{{ $canReply ? 'Type a reply…' : 'Add a note for your team…' }}"
                                   autocomplete="off">
                            <label class="btn btn-outline-secondary mb-0 chat-attach-btn" for="chat-file-input" title="Attach a file"
                                   @style([ 'visibility: hidden' => ! $canReply ])>📎</label>
                            <input type="file" id="chat-file-input" class="d-none"
                                   accept=".{{ implode(',.', config('chat.attachments.extensions')) }}"
                                   @disabled(! $canReply)>
                            <button type="submit" class="btn {{ $canReply ? 'btn-primary' : 'btn-warning' }}" id="chat-send-button">
                                {{ $canReply ? 'Send' : 'Add note' }}
                            </button>
                        </div>
                    </form>
                    <div class="small text-muted mt-1" id="chat-file-name"></div>
                    <div class="form-text" id="chat-mode-hint">
                        {{ $canReply ? 'Replies are visible to the visitor.' : 'Internal notes are only visible to your team.' }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Right: visitor info + CRM --}}
    <aside class="chat-workspace__details" aria-label="Visitor details">
        <div class="chat-details__section">
            <h2>Visitor</h2>
            <dl class="chat-details__facts">
                <div>
                    <dt>Name</dt>
                    <dd>{{ $visitor->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd>{{ $visitor->email ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Location</dt>
                    <dd>{{ $visitor->displayLocation() ?: '—' }}</dd>
                </div>
                <div>
                    <dt>IP address</dt>
                    <dd class="chat-details__mono">{{ $visitor->ip_address ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Current page</dt>
                    <dd>
                        @if($visitor->current_page)
                            <a href="{{ $visitor->current_page }}" target="_blank" rel="noopener" class="chat-details__link">
                                {{ $visitor->page_title ?: Str::limit($visitor->current_page, 42) }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Last seen</dt>
                    <dd>{{ $visitor->last_seen_at?->diffForHumans() ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="chat-details__section">
            <h2>Page visits</h2>
            @if($pageVisits->isEmpty())
                <p class="chat-details__empty">No page visits recorded yet.</p>
            @else
                <ul class="chat-details__visits">
                    @foreach($pageVisits->take(8) as $visit)
                        <li>
                            <a href="{{ $visit['url'] ?? '#' }}" target="_blank" rel="noopener">
                                {{ $visit['title'] ?? Str::limit($visit['url'] ?? 'Page', 40) }}
                            </a>
                            @if(! empty($visit['visited_at']))
                                <time>{{ \Illuminate\Support\Carbon::parse($visit['visited_at'])->diffForHumans() }}</time>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="chat-details__section">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="mb-0">CRM</h2>
                @if($visitor->client)
                    <a href="{{ route('clients.show', $visitor->client) }}" class="small">
                        View in CRM →
                    </a>
                @endif
            </div>
            <p class="text-muted small mb-2">
                Saving stores this lead in the CRM module
                @if($visitor->client)
                    (linked to <strong>{{ $visitor->client->name }}</strong>)
                @endif
                .
            </p>
            <form method="POST" action="{{ route('chat.conversations.visitor.update', $conversation) }}" class="chat-details__crm">
                @csrf
                @method('PUT')

                <label>
                    <span>Name</span>
                    <input type="text" name="name" class="form-control form-control-sm"
                           value="{{ old('name', $visitor->name) }}" maxlength="255">
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" class="form-control form-control-sm"
                           value="{{ old('email', $visitor->email) }}" maxlength="255">
                </label>
                <label>
                    <span>Phone</span>
                    <input type="text" name="phone" class="form-control form-control-sm"
                           value="{{ old('phone', $visitor->phone) }}" maxlength="50">
                </label>
                <label>
                    <span>Company</span>
                    <input type="text" name="company" class="form-control form-control-sm"
                           value="{{ old('company', $visitor->company) }}" maxlength="255">
                </label>
                <label>
                    <span>Location</span>
                    <input type="text" name="location" class="form-control form-control-sm"
                           value="{{ old('location', $visitor->location) }}" maxlength="255"
                           placeholder="City, Country">
                </label>
                <label>
                    <span>City</span>
                    <input type="text" name="city" class="form-control form-control-sm"
                           value="{{ old('city', $visitor->city) }}" maxlength="100">
                </label>
                <label>
                    <span>Country</span>
                    <input type="text" name="country" class="form-control form-control-sm"
                           value="{{ old('country', $visitor->country) }}" maxlength="100">
                </label>
                <label>
                    <span>Notes</span>
                    <textarea name="crm_notes" class="form-control form-control-sm" rows="4"
                              maxlength="5000">{{ old('crm_notes', $visitor->crm_notes) }}</textarea>
                </label>

                <button type="submit" class="btn btn-sm btn-primary w-100">Save to CRM</button>
            </form>
        </div>
    </aside>
</div>

@vite('resources/js/chat/inbox.js')
@endsection
