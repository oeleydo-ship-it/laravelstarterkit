@extends('layouts.app')

@section('title', 'Live Chat')

@section('content')
<div class="chat-inbox"
     data-chat-inbox
     data-variant="inbox"
     data-tenant-id="{{ currentTenant()->id }}"
     data-current-user-id="{{ auth()->id() }}"
     data-status="{{ request('status') }}"
     data-filter="{{ request('filter') }}"
     data-feed-url="{{ route('chat.conversations.index', request()->query()) }}"
     data-empty-text="{{ filled($search) ? 'No conversations match that search.' : 'No conversations yet.' }}">
    <div class="chat-inbox__header">
        <div>
            <h1 class="chat-inbox__title">Live Chat</h1>
            <p class="chat-inbox__subtitle">Accept a conversation before replying to the visitor.</p>
        </div>

        <div class="chat-inbox__tools">
            <form method="POST" action="{{ route('chat.availability.update') }}" class="chat-inbox__availability">
                @csrf
                @method('PUT')
                <label for="availability">My status</label>
                <select name="availability" id="availability" class="form-select form-select-sm"
                        onchange="this.form.submit();">
                    @foreach(['online' => 'Online', 'away' => 'Away', 'offline' => 'Offline'] as $value => $label)
                        <option value="{{ $value }}" {{ auth()->user()->chat_availability === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('chat.canned-responses.index') }}" class="btn btn-sm btn-outline-secondary">Canned Replies</a>
            <a href="{{ route('chat.articles.index') }}" class="btn btn-sm btn-outline-secondary">Knowledge Base</a>

            @if(auth()->user()->isOwnerOrAdmin())
                <a href="{{ route('chat.reports.index') }}" class="btn btn-sm btn-outline-secondary">Reports</a>
                <a href="{{ route('chat.settings.index') }}" class="btn btn-sm btn-outline-secondary">Settings</a>
            @endif
        </div>
    </div>

    <div class="chat-inbox__filters">
        <div class="chat-inbox__tabs">
            <a href="{{ route('chat.conversations.index', array_filter(['q' => $search])) }}"
                class="chat-inbox__tab {{ !request('status') && !request('filter') ? 'is-active' : '' }}">Open</a>
            <a href="{{ route('chat.conversations.index', array_filter(['filter' => 'mine', 'q' => $search])) }}"
                class="chat-inbox__tab {{ request('filter') === 'mine' ? 'is-active' : '' }}">Assigned to me</a>
            <a href="{{ route('chat.conversations.index', array_filter(['filter' => 'unassigned', 'q' => $search])) }}"
                class="chat-inbox__tab {{ request('filter') === 'unassigned' ? 'is-active' : '' }}">Unassigned</a>
            <a href="{{ route('chat.conversations.index', array_filter(['status' => 'closed', 'q' => $search])) }}"
                class="chat-inbox__tab {{ request('status') === 'closed' && request('filter') !== 'rated' ? 'is-active' : '' }}">Closed</a>
            <a href="{{ route('chat.conversations.index', array_filter(['status' => 'closed', 'filter' => 'rated', 'q' => $search])) }}"
                class="chat-inbox__tab {{ request('filter') === 'rated' ? 'is-active' : '' }}">Rated</a>
        </div>

        <div class="chat-inbox__presence" id="online-agents" data-tenant-id="{{ currentTenant()->id }}">
            <span class="chat-presence-dot"></span>
            <span id="online-agents-count">0</span> agent(s) online
        </div>
    </div>

    <form method="GET" action="{{ route('chat.conversations.index') }}" class="chat-inbox__search">
        @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
        @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
        <input type="search" name="q" class="form-control form-control-sm" value="{{ $search }}"
               placeholder="Search visitors, messages and feedback…">
        <button class="btn btn-sm btn-outline-secondary">Search</button>
        @if(filled($search))
            <a href="{{ route('chat.conversations.index', array_filter(['status' => request('status'), 'filter' => request('filter')])) }}"
               class="btn btn-sm btn-link text-decoration-none">Clear</a>
        @endif
    </form>

    <div class="chat-inbox__list" data-chat-inbox-list>
        @forelse($conversations as $conversation)
            @php
                $visitorLabel = $conversation->visitor->name
                    ?? $conversation->visitor->email
                    ?? 'Visitor #'.$conversation->chat_visitor_id;
                $isMine = (int) $conversation->assigned_to === (int) auth()->id();
            @endphp
            <a href="{{ route('chat.conversations.show', $conversation) }}" class="chat-inbox__row" data-conversation-id="{{ $conversation->id }}">
                <div class="chat-inbox__row-main">
                    <div class="chat-inbox__visitor">
                        <strong>{{ $visitorLabel }}</strong>
                        @if($conversation->unread_count)
                            <span class="chat-inbox__unread">{{ $conversation->unread_count }}</span>
                        @endif
                    </div>
                    <p class="chat-inbox__preview">{{ $conversation->last_message_preview ?? 'No messages yet' }}</p>
                </div>
                <div class="chat-inbox__row-side">
                    <span class="chat-pill chat-pill--{{ $conversation->status === 'open' ? 'open' : 'closed' }}">
                        {{ ucfirst($conversation->status) }}
                    </span>
                    @if($conversation->assignee)
                        <span class="chat-pill {{ $isMine ? 'chat-pill--agent' : 'chat-pill--muted' }}">
                            {{ $isMine ? 'You' : $conversation->assignee->name }}
                        </span>
                    @else
                        <span class="chat-pill chat-pill--warn">Needs accept</span>
                    @endif
                    @if($conversation->isRated())
                        <span class="chat-rating-badge">
                            {{ str_repeat('★', $conversation->rating) }}{{ str_repeat('☆', \App\Models\ChatConversation::MAX_RATING - $conversation->rating) }}
                        </span>
                    @endif
                    <time>{{ $conversation->last_message_at?->diffForHumans() ?? $conversation->created_at->diffForHumans() }}</time>
                </div>
            </a>
        @empty
            <div class="chat-inbox__empty">
                {{ filled($search) ? 'No conversations match that search.' : 'No conversations yet.' }}
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $conversations->appends(request()->query())->links() }}
    </div>
</div>

@vite(['resources/js/chat/presence.js', 'resources/js/chat/list.js'])
@endsection
