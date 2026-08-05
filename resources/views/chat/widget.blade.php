<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat with {{ $appearance['title'] }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/chat/widget.js'])
    {{-- Validated as a six-digit hex by UpdateChatAppearanceRequest before it
         ever reaches this stylesheet. --}}
    <style>
        #chat-widget {
            --chat-widget-color: {{ $appearance['color'] }};
        }
    </style>
</head>

<body>
    <div id="chat-widget"
         data-tenant-slug="{{ $tenantSlug }}"
         data-tenant-id="{{ $tenant->id }}"
         data-agent-name="{{ $appearance['title'] }}"
         data-pre-chat="{{ $appearance['pre_chat_enabled'] ? '1' : '0' }}"
         data-offline-message="{{ $appearance['offline_message'] }}">

        <button id="chat-widget-toggle" class="chat-widget-toggle" type="button"
                aria-label="{{ $appearance['launcher_text'] }}"
                style="background-color: {{ $appearance['color'] }};">
            <span class="chat-widget-toggle-icon chat-widget-toggle-icon--open" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </span>
            <span class="chat-widget-toggle-icon chat-widget-toggle-icon--close" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M18 6L6 18M6 6l12 12"></path>
                </svg>
            </span>
            <span id="chat-widget-unread-badge" class="chat-widget-unread-badge" style="display:none;"></span>
        </button>

        <div id="chat-widget-toasts" class="chat-widget-toasts" aria-live="polite"></div>

        <div id="chat-widget-panel" class="chat-widget-panel">
            <div class="chat-widget-header">
                <div class="chat-widget-header-left">
                    <button type="button" id="chat-widget-back" class="chat-widget-icon-btn" aria-label="Back">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6"></path>
                        </svg>
                    </button>
                    <span class="chat-widget-header-title" id="chat-widget-header-title">
                        {{ filled($appearance['greeting']) ? $appearance['greeting'] : 'Hi there! 👋' }}
                    </span>
                </div>
                <div class="chat-widget-header-actions">
                    <button type="button" id="chat-widget-expand" class="chat-widget-icon-btn" aria-label="Expand chat" aria-pressed="false">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3"></path>
                        </svg>
                    </button>
                    <div class="chat-widget-menu">
                        <button type="button" id="chat-widget-menu-btn" class="chat-widget-icon-btn" aria-label="More options" aria-expanded="false" aria-haspopup="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                <circle cx="12" cy="5" r="1.6"></circle>
                                <circle cx="12" cy="12" r="1.6"></circle>
                                <circle cx="12" cy="19" r="1.6"></circle>
                            </svg>
                        </button>
                        <div id="chat-widget-menu" class="chat-widget-menu-panel" hidden>
                            <button type="button" id="chat-widget-home-btn" class="chat-widget-menu-item">
                                <span class="chat-widget-menu-item-label">Help center</span>
                            </button>
                            <button type="button" id="chat-widget-new-chat" class="chat-widget-menu-item">
                                <span class="chat-widget-menu-item-label">New chat</span>
                            </button>
                            <button type="button" id="chat-widget-notif-toggle" class="chat-widget-menu-item" aria-pressed="true">
                                <span class="chat-widget-menu-item-label">Notifications</span>
                                <span class="chat-widget-menu-item-state">On</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="chat-widget-offline" class="chat-widget-offline"
                 style="{{ $isOnline ? 'display:none;' : '' }}">
                {{ $appearance['offline_message'] }}
            </div>

            <div id="chat-widget-agent" class="chat-widget-agent" style="display:none;" aria-live="polite">
                <span class="chat-widget-agent-dot" aria-hidden="true"></span>
                <span id="chat-widget-agent-label"></span>
            </div>

            <div class="chat-widget-body">
                {{-- Home / knowledge base from admin articles & documents --}}
                <div id="chat-widget-home" class="chat-widget-home">
                    <p class="chat-widget-home-lead">
                        {{ filled($appearance['greeting']) ? $appearance['greeting'] : 'Hi there! How can we help?' }}
                    </p>
                    <div class="chat-widget-kb-heading">Knowledge base</div>
                    <div id="chat-widget-kb-list" class="chat-widget-kb-list">
                        <div class="chat-widget-kb-empty text-muted">Loading articles…</div>
                    </div>
                    <button type="button" id="chat-widget-open-chat" class="chat-widget-primary-btn"
                            style="background-color: {{ $appearance['color'] }};">
                        Start a conversation
                    </button>
                </div>

                <div id="chat-widget-article" class="chat-widget-article" style="display:none;">
                    <button type="button" id="chat-widget-article-back" class="chat-widget-article-back">← Back</button>
                    <h3 id="chat-widget-article-title" class="chat-widget-article-title"></h3>
                    <div id="chat-widget-article-body" class="chat-widget-article-body"></div>
                    <button type="button" id="chat-widget-article-chat" class="chat-widget-primary-btn"
                            style="background-color: {{ $appearance['color'] }};">
                        Still need help? Chat with us
                    </button>
                </div>

                {{-- Shown ahead of the thread when the workspace asks visitors to
                     identify themselves. Hidden by default; the script decides. --}}
                <form id="chat-widget-prechat" class="chat-widget-prechat" style="display:none;">
                    @if(filled($appearance['pre_chat_message']))
                        <p class="chat-widget-prechat-lead">{{ $appearance['pre_chat_message'] }}</p>
                    @endif

                    <div class="chat-widget-field">
                        <label for="chat-widget-name">Your name</label>
                        <input type="text" id="chat-widget-name" maxlength="255" autocomplete="name">
                    </div>

                    <div class="chat-widget-field">
                        <label for="chat-widget-email">Email</label>
                        <input type="email" id="chat-widget-email" maxlength="255" autocomplete="email">
                        <div id="chat-widget-prechat-error" class="chat-widget-error" style="display:none;"></div>
                    </div>

                    <button type="submit" class="chat-widget-primary-btn"
                            style="background-color: {{ $appearance['color'] }};">
                        Start chat
                    </button>
                </form>

                {{-- Offered only once the agent closes the chat, and only until a
                     score is recorded. The script shows and hides it. --}}
                <form id="chat-widget-rating" class="chat-widget-rating" style="display:none;">
                    <p class="chat-widget-rating-title">How did we do?</p>

                    <div class="chat-rating-stars" role="radiogroup" aria-label="Rating">
                        @for($star = 1; $star <= \App\Models\ChatConversation::MAX_RATING; $star++)
                            <button type="button" class="chat-rating-star" data-rating="{{ $star }}"
                                    role="radio" aria-checked="false"
                                    aria-label="{{ $star }} out of {{ \App\Models\ChatConversation::MAX_RATING }}">☆</button>
                        @endfor
                    </div>

                    <textarea id="chat-widget-rating-comment" rows="2"
                              maxlength="1000" placeholder="Anything you'd like to add? (optional)"></textarea>

                    <button type="submit" class="chat-widget-primary-btn" disabled
                            id="chat-widget-rating-submit"
                            style="background-color: {{ $appearance['color'] }};">
                        Send feedback
                    </button>

                    <div id="chat-widget-rating-error" class="chat-widget-error" style="display:none;"></div>
                </form>

                <div id="chat-widget-rating-thanks" class="chat-widget-rating chat-widget-rating-thanks" style="display:none;">
                    Thanks for the feedback.
                </div>

                <div id="chat-widget-conversation" class="chat-widget-conversation" style="display:none;">
                    <div id="chat-widget-thread" class="chat-thread chat-widget-thread"></div>
                    <div id="chat-widget-typing" class="chat-typing-indicator" style="display:none;">
                        Agent is typing…
                    </div>
                </div>
            </div>

            <form id="chat-widget-form" class="chat-widget-composer" style="display:none;">
                <div class="chat-widget-composer-row">
                    <input type="text" id="chat-widget-input" placeholder="Enter your message..." autocomplete="off">
                    <button type="submit" class="chat-widget-send-btn" aria-label="Send message">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                        </svg>
                    </button>
                </div>
                <div class="chat-widget-composer-tools">
                    <div class="chat-widget-composer-actions">
                        <label class="chat-widget-icon-btn" for="chat-widget-file" title="Attach a file">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                            </svg>
                        </label>
                        <input type="file" id="chat-widget-file" class="chat-widget-file-input"
                               accept=".{{ implode(',.', config('chat.attachments.extensions')) }}">
                        <button type="button" class="chat-widget-icon-btn" id="chat-widget-emoji" aria-label="Insert emoji" title="Emoji">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                <line x1="15" y1="9" x2="15.01" y2="9"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="chat-widget-powered">
                        <span>POWERED BY</span>
                        <strong>{{ strtoupper($appearance['title']) }}</strong>
                    </div>
                </div>
                <div id="chat-widget-file-name" class="chat-widget-file-name"></div>
            </form>
        </div>
    </div>
</body>

</html>
