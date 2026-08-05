import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { reverbEchoOptions } from './realtime';

window.Pusher = Pusher;

const root = document.getElementById('chat-widget');
if (root) {
    const tenantSlug = root.dataset.tenantSlug;
    const base = `/widget/${tenantSlug}`;
    const storageKey = `chat_visitor_token_${tenantSlug}`;

    const toggleBtn = document.getElementById('chat-widget-toggle');
    const unreadBadge = document.getElementById('chat-widget-unread-badge');
    const notifToggle = document.getElementById('chat-widget-notif-toggle');
    const backBtn = document.getElementById('chat-widget-back');
    const expandBtn = document.getElementById('chat-widget-expand');
    const menuBtn = document.getElementById('chat-widget-menu-btn');
    const menuPanel = document.getElementById('chat-widget-menu');
    const emojiBtn = document.getElementById('chat-widget-emoji');
    const toasts = document.getElementById('chat-widget-toasts');
    const panel = document.getElementById('chat-widget-panel');
    const thread = document.getElementById('chat-widget-thread');
    const form = document.getElementById('chat-widget-form');
    const input = document.getElementById('chat-widget-input');
    const typingIndicator = document.getElementById('chat-widget-typing');
    const offlineNotice = document.getElementById('chat-widget-offline');
    const agentBanner = document.getElementById('chat-widget-agent');
    const agentLabel = document.getElementById('chat-widget-agent-label');
    const fileInput = document.getElementById('chat-widget-file');
    const fileName = document.getElementById('chat-widget-file-name');
    const preChatForm = document.getElementById('chat-widget-prechat');
    const preChatError = document.getElementById('chat-widget-prechat-error');
    const conversationPane = document.getElementById('chat-widget-conversation');
    const ratingForm = document.getElementById('chat-widget-rating');
    const ratingThanks = document.getElementById('chat-widget-rating-thanks');
    const ratingSubmit = document.getElementById('chat-widget-rating-submit');
    const ratingError = document.getElementById('chat-widget-rating-error');
    const ratingStars = document.querySelectorAll('.chat-rating-star');
    const homePane = document.getElementById('chat-widget-home');
    const kbList = document.getElementById('chat-widget-kb-list');
    const articlePane = document.getElementById('chat-widget-article');
    const articleTitle = document.getElementById('chat-widget-article-title');
    const articleBody = document.getElementById('chat-widget-article-body');
    const articleBack = document.getElementById('chat-widget-article-back');
    const articleChat = document.getElementById('chat-widget-article-chat');
    const openChatBtn = document.getElementById('chat-widget-open-chat');
    const newChatBtn = document.getElementById('chat-widget-new-chat');
    const homeMenuBtn = document.getElementById('chat-widget-home-btn');
    const headerTitle = document.getElementById('chat-widget-header-title');
    const agentName = root.dataset.agentName || 'Support';
    const defaultTitle = headerTitle?.textContent?.trim() || 'Hi there! 👋';

    let visitorToken = localStorage.getItem(storageKey) || null;
    let conversationId = null;
    let echo = null;
    let echoConnected = false;
    let rating = null;
    let unreadCount = 0;
    let unreadStorageKey = null;
    let seenMessageIds = new Set();
    let pollTimer = null;
    let currentView = 'home';
    let kbItems = [];
    let notificationsEnabled = localStorage.getItem(`chat_widget_notifications_enabled_${tenantSlug}`);
    notificationsEnabled = notificationsEnabled === null ? '1' : notificationsEnabled;
    notificationsEnabled = notificationsEnabled === '1';

    // Only ask a visitor who they are once — a returning visitor already has a
    // token, and their details are on the record it points at.
    const needsPreChat = root.dataset.preChat === '1' && !visitorToken;

    const isPanelOpen = () => {
        if (!panel) return false;
        if (panel.style.display) return panel.style.display === 'flex';
        return getComputedStyle(panel).display === 'flex';
    };

    const updateUnreadBadge = () => {
        if (!unreadBadge) return;

        if (!unreadStorageKey || unreadCount <= 0) {
            unreadBadge.style.display = 'none';
            unreadBadge.textContent = '';
            return;
        }

        unreadBadge.style.display = 'inline-flex';
        unreadBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
    };

    const setUnreadCount = (count) => {
        unreadCount = Math.max(0, Number(count) || 0);
        if (unreadStorageKey) {
            localStorage.setItem(unreadStorageKey, String(unreadCount));
        }
        updateUnreadBadge();
    };

    const markConversationRead = () => {
        if (!unreadStorageKey) return;
        setUnreadCount(0);
    };

    const playBeep = () => {
        // Best-effort: browsers may block audio without a user gesture.
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;

            const ctx = new AudioCtx();
            const o = ctx.createOscillator();
            const g = ctx.createGain();

            o.type = 'sine';
            o.frequency.value = 880;
            g.gain.value = 0.0001;

            o.connect(g);
            g.connect(ctx.destination);
            o.start();

            const now = ctx.currentTime;
            g.gain.setValueAtTime(0.0001, now);
            g.gain.exponentialRampToValueAtTime(0.25, now + 0.01);
            g.gain.exponentialRampToValueAtTime(0.0001, now + 0.18);

            o.stop(now + 0.2);
            setTimeout(() => ctx.close?.(), 400);
        } catch {
            // Ignore.
        }
    };

    const showToast = ({ title, body }) => {
        if (!toasts) return;

        const toast = document.createElement('div');
        toast.className = 'chat-widget-toast';
        toast.innerHTML = `
            <div class="chat-widget-toast-title">${title}</div>
            <div class="chat-widget-toast-body"></div>
        `;
        toast.querySelector('.chat-widget-toast-body').textContent = body;

        toasts.prepend(toast);

        // Keep the stack small.
        const existing = toasts.querySelectorAll('.chat-widget-toast');
        if (existing.length > 3) {
            existing[existing.length - 1].remove();
        }

        window.setTimeout(() => toast.classList.add('chat-widget-toast--dismissed'), 3500);
        window.setTimeout(() => toast.remove(), 4200);
    };

    const maybeNotifyDesktop = (preview) => {
        if (!('Notification' in window)) return;
        if (Notification.permission !== 'granted') return;

        try {
            new Notification('New chat message', { body: preview });
        } catch {
            // Ignore.
        }
    };

    const syncNotifToggle = () => {
        if (!notifToggle) return;

        notifToggle.setAttribute('aria-pressed', String(notificationsEnabled));
        notifToggle.classList.toggle('active', notificationsEnabled);

        const state = notifToggle.querySelector('.chat-widget-menu-item-state');
        if (state) state.textContent = notificationsEnabled ? 'On' : 'Off';
    };

    syncNotifToggle();

    if (notifToggle) {
        notifToggle.addEventListener('click', () => {
            notificationsEnabled = !notificationsEnabled;
            localStorage.setItem(`chat_widget_notifications_enabled_${tenantSlug}`, notificationsEnabled ? '1' : '0');
            syncNotifToggle();

            // Ask permission only on user action.
            if (notificationsEnabled && 'Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().catch(() => {});
            }
        });
    }

    const setMenuOpen = (open) => {
        if (!menuPanel || !menuBtn) return;
        menuPanel.hidden = !open;
        menuBtn.setAttribute('aria-expanded', String(open));
    };

    if (menuBtn && menuPanel) {
        menuBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            setMenuOpen(menuPanel.hidden);
        });

        document.addEventListener('click', () => setMenuOpen(false));
        menuPanel.addEventListener('click', (event) => event.stopPropagation());
    }

    if (expandBtn) {
        expandBtn.addEventListener('click', () => {
            const expanded = panel.classList.toggle('chat-widget-panel--expanded');
            expandBtn.setAttribute('aria-pressed', String(expanded));
            reportSize(true);
        });
    }

    if (emojiBtn) {
        emojiBtn.addEventListener('click', () => {
            input.value += '😊';
            input.focus();
        });
    }

    const setPanelOpen = (open) => {
        panel.style.display = open ? 'flex' : 'none';
        root.classList.toggle('chat-widget--open', open);
        toggleBtn.classList.toggle('chat-widget-toggle--open', open);
        setMenuOpen(false);
        reportSize(open);

        if (open) {
            markConversationRead();
        }
    };

    const setAssignee = (name) => {
        if (!agentBanner || !agentLabel) return;

        if (name) {
            agentLabel.textContent = `${name} joined the chat`;
            agentBanner.style.display = 'flex';
        } else {
            agentLabel.textContent = '';
            agentBanner.style.display = 'none';
        }
    };

    const hideAllViews = () => {
        if (homePane) homePane.style.display = 'none';
        if (articlePane) articlePane.style.display = 'none';
        if (preChatForm) preChatForm.style.display = 'none';
        if (conversationPane) conversationPane.style.display = 'none';
        if (ratingForm) ratingForm.style.display = 'none';
        if (ratingThanks) ratingThanks.style.display = 'none';
        if (form) form.style.display = 'none';
    };

    const showView = (view) => {
        currentView = view;
        hideAllViews();
        setMenuOpen(false);

        if (headerTitle) {
            headerTitle.textContent = view === 'article'
                ? 'Help center'
                : (view === 'chat' ? 'Chat' : defaultTitle);
        }

        if (view === 'home' && homePane) {
            homePane.style.display = 'flex';
            if (form) form.style.display = 'block';
        } else if (view === 'article' && articlePane) {
            articlePane.style.display = 'flex';
        } else if (view === 'prechat' && preChatForm) {
            preChatForm.style.display = 'block';
        } else if (view === 'chat' && conversationPane) {
            conversationPane.style.display = 'flex';
            if (form) form.style.display = 'block';
            scrollToBottom();
            input?.focus();
        }
    };

    const renderKnowledge = (items) => {
        if (!kbList) return;

        kbItems = items || [];

        if (!kbItems.length) {
            kbList.innerHTML = '<div class="chat-widget-kb-empty">No help articles yet. Start a conversation below.</div>';
            return;
        }

        kbList.innerHTML = '';
        kbItems.forEach((item, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chat-widget-kb-item';
            btn.innerHTML = `
                <span class="chat-widget-kb-item-title"></span>
                <span class="chat-widget-kb-item-excerpt"></span>
            `;
            btn.querySelector('.chat-widget-kb-item-title').textContent = item.title;
            btn.querySelector('.chat-widget-kb-item-excerpt').textContent = item.excerpt || '';
            btn.addEventListener('click', () => openArticle(index));
            kbList.appendChild(btn);
        });
    };

    const loadKnowledge = () => {
        return axios.get(`${base}/knowledge`)
            .then(({ data }) => renderKnowledge(data.data || []))
            .catch(() => {
                if (kbList) {
                    kbList.innerHTML = '<div class="chat-widget-kb-empty">Could not load the knowledge base.</div>';
                }
            });
    };

    const openArticle = (index) => {
        const item = kbItems[index];
        if (!item || !articlePane) return;

        if (articleTitle) articleTitle.textContent = item.title;
        if (articleBody) articleBody.textContent = item.body || '';
        showView('article');
    };

    const ensureChatStarted = ({ forceNew = false, identity = {} } = {}) => {
        if (conversationId && !forceNew) {
            return Promise.resolve();
        }

        return start({ forceNew, ...identity });
    };

    const enterChat = ({ forceNew = false } = {}) => {
        if (needsPreChat && !visitorToken) {
            showView('prechat');
            return Promise.resolve();
        }

        return ensureChatStarted({ forceNew }).then(() => {
            showView('chat');
            markConversationRead();
        });
    };

    const formatMessageTime = (iso) => {
        if (!iso) return '';

        try {
            const date = new Date(iso);
            if (Number.isNaN(date.getTime())) return '';

            const now = new Date();
            const sameDay = date.toDateString() === now.toDateString();
            const time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            if (sameDay) return `Today, ${time}`;

            return `${date.toLocaleDateString([], { month: 'short', day: 'numeric' })}, ${time}`;
        } catch {
            return '';
        }
    };

    /**
     * The embed loader hosts this page in a cross-origin iframe sized to the
     * launcher, so it has to be told when the panel opens or closes. Harmless
     * no-op when the widget is loaded directly rather than embedded.
     */
    const reportSize = (open) => {
        if (window.parent === window) return;

        window.parent.postMessage({
            type: 'chat-widget-resize',
            open,
            expanded: panel.classList.contains('chat-widget-panel--expanded'),
        }, '*');
    };

    const scrollToBottom = () => {
        thread.scrollTop = thread.scrollHeight;
    };

    const appendMessage = (message, { fromPoll = false } = {}) => {
        const {
            id,
            sender_type: senderType,
            sender_name: senderName,
            body,
            attachment,
            download_url: downloadUrl,
            created_at: createdAt,
        } = message;

        if (id != null) {
            const key = String(id);
            if (seenMessageIds.has(key)) {
                return false;
            }
            seenMessageIds.add(key);
        }

        const wrap = document.createElement('div');
        wrap.className = `chat-message chat-message-${senderType}`;
        if (id != null) {
            wrap.dataset.messageId = String(id);
        }

        const bubble = document.createElement('div');
        bubble.className = 'chat-message-bubble';

        const bodyEl = document.createElement('div');
        bodyEl.className = 'chat-message-body';
        bodyEl.textContent = body || '';
        bubble.appendChild(bodyEl);

        if (attachment) {
            const link = document.createElement('a');
            link.className = 'chat-attachment';
            link.target = '_blank';
            link.rel = 'noopener';
            // The download route authorizes on the visitor token, not the session.
            link.href = `${downloadUrl || `${base}/conversations/${conversationId}/attachments/${attachment.id}`}?visitor_token=${encodeURIComponent(visitorToken)}`;
            link.textContent = `📎 ${attachment.name} (${attachment.human_size})`;
            bubble.appendChild(link);
        }

        wrap.appendChild(bubble);

        if (senderType === 'agent' || senderType === 'bot') {
            const meta = document.createElement('div');
            meta.className = 'chat-message-meta';
            const when = formatMessageTime(createdAt);
            const label = senderType === 'bot' ? (senderName || 'Assistant') : (senderName || agentName);
            meta.textContent = when
                ? `${label} - ${when}`
                : label;
            wrap.appendChild(meta);
        }

        thread.appendChild(wrap);
        scrollToBottom();

        if (fromPoll && (senderType === 'agent' || senderType === 'bot') && !isPanelOpen()) {
            setUnreadCount(unreadCount + 1);
            if (notificationsEnabled) {
                const preview = (body || '').trim().slice(0, 80);
                showToast({ title: senderType === 'bot' ? 'Assistant' : 'Support', body: preview || 'New message' });
                playBeep();
                maybeNotifyDesktop(preview || 'New message');
            }
        }

        return true;
    };

    const loadMessages = ({ notifyNew = false, reset = false } = {}) => {
        if (!conversationId || !visitorToken) {
            return Promise.resolve();
        }

        return axios.get(`${base}/conversations/${conversationId}/messages`, {
            params: { visitor_token: visitorToken },
        }).then(({ data: messages }) => {
            if (reset) {
                seenMessageIds = new Set();
                thread.innerHTML = '';
            }

            messages.forEach((message) => {
                appendMessage(message, { fromPoll: notifyNew && !reset });
            });
        }).catch(() => {});
    };

    const stopPolling = () => {
        if (pollTimer) {
            window.clearTimeout(pollTimer);
            pollTimer = null;
        }
    };

    // Polling is the reliable path when the host page throttles the tiny closed
    // iframe or when the websocket proxy is misconfigured. Never depend on Echo
    // succeeding before this starts.
    const startPolling = () => {
        stopPolling();

        const tick = () => {
            loadMessages({ notifyNew: true }).finally(() => {
                // Faster while waiting on websockets; ease off once Echo is live.
                const delay = echoConnected ? 4000 : 1500;
                pollTimer = window.setTimeout(tick, delay);
            });
        };

        tick();
    };

    const subscribe = () => {
        // Fetch agent replies even if Echo fails to construct or connect.
        startPolling();

        if (echo) {
            try {
                echo.disconnect();
            } catch {
                // Ignore.
            }
            echo = null;
        }

        echoConnected = false;

        try {
            echo = new Echo(reverbEchoOptions({
                // `endpoint` is required here. Supplying channelAuthorization at all
                // makes pusher-js ignore the legacy `authEndpoint`, and its own
                // default is /pusher/auth — which this app does not serve, so the
                // subscription silently 404s and the visitor never receives replies.
                channelAuthorization: {
                    endpoint: `${base}/broadcasting/auth`,
                    // Same param name as every other widget endpoint — the auth
                    // route runs the identical ownership check on it.
                    params: { visitor_token: visitorToken },
                },
            }));
        } catch {
            return;
        }

        // Lets broadcast(...)->toOthers() skip this tab, the same way the agent
        // side does in bootstrap.js.
        echo.connector.pusher.connection.bind('connected', () => {
            echoConnected = true;
            axios.defaults.headers.common['X-Socket-Id'] = echo.socketId();
            // Catch anything missed while the socket was down.
            loadMessages({ notifyNew: true });
        });

        echo.connector.pusher.connection.bind('disconnected', () => {
            echoConnected = false;
            delete axios.defaults.headers.common['X-Socket-Id'];
            loadMessages({ notifyNew: true });
        });

        echo.connector.pusher.connection.bind('unavailable', () => {
            echoConnected = false;
        });

        echo.private(`tenant.${root.dataset.tenantId}.conversation.${conversationId}`)
            .listen('.conversation.updated', (data) => {
                // Asking for a score is the last thing that happens to a chat,
                // so it is driven by the agent closing it rather than by
                // anything the visitor does.
                if (data.status === 'closed' && !data.is_rated) {
                    showRating();
                } else if (data.is_rated) {
                    hideRating();
                }

                setAssignee(data.assignee_name || null);
            })
            .listen('.message.sent', (data) => {
                if (data.sender_type === 'agent' || data.sender_type === 'bot') {
                    const added = appendMessage(data);

                    // When the visitor isn't looking at the panel, treat agent
                    // replies as unread notifications.
                    if (added && !isPanelOpen()) {
                        setUnreadCount(unreadCount + 1);

                        if (notificationsEnabled) {
                            const preview = (data.body || '').trim().slice(0, 80);
                            showToast({
                                title: data.sender_type === 'bot' ? 'Assistant' : 'Support',
                                body: preview || 'New message',
                            });
                            playBeep();
                            maybeNotifyDesktop(preview || 'New message');
                        }
                    }
                }
            })
            .listen('.chat.typing', (data) => {
                if (data.sender_type === 'agent' || data.sender_type === 'bot') {
                    typingIndicator.style.display = 'block';
                    clearTimeout(typingIndicator._hideTimer);
                    typingIndicator._hideTimer = setTimeout(() => {
                        typingIndicator.style.display = 'none';
                    }, 3000);
                }
            });
    };

    const pageContext = () => {
        const params = new URLSearchParams(window.location.search);
        const pageUrl = params.get('page_url') || document.referrer || window.location.href;
        const pageTitle = params.get('page_title') || document.title || null;

        return {
            page_url: pageUrl || null,
            page_title: pageTitle || null,
        };
    };

    const start = (identity = {}) => {
        const { forceNew = false, ...rest } = identity;

        return axios.post(`${base}/start`, {
            visitor_token: visitorToken,
            force_new: forceNew ? 1 : 0,
            ...pageContext(),
            ...rest,
        }).then(({ data }) => {
            visitorToken = data.visitor_token;
            conversationId = data.conversation_id;
            localStorage.setItem(storageKey, visitorToken);

            unreadStorageKey = `chat_widget_unread_${tenantSlug}_${conversationId}`;
            unreadCount = Number(localStorage.getItem(unreadStorageKey) || '0');
            updateUnreadBadge();

            // Server-side hours win over whatever the page was rendered with —
            // a widget left open all evening still tells the truth.
            if (offlineNotice) {
                offlineNotice.style.display = data.is_online ? 'none' : 'block';
            }

            setAssignee(data.assignee_name || null);

            // Await history load before resolving. A fire-and-forget reset was
            // wiping the visitor's first message when they sent it immediately
            // after start (appendMessage → loadMessages reset → empty thread).
            return loadMessages({ reset: true }).then(() => {
                subscribe();
            });
        });
    };

    // ─── Satisfaction rating ───

    const showRating = () => {
        if (!ratingForm || ratingThanks.style.display === 'block') return;

        hideAllViews();
        ratingForm.style.display = 'block';
        currentView = 'rating';
    };

    const hideRating = () => {
        if (!ratingForm) return;

        ratingForm.style.display = 'none';
        ratingThanks.style.display = 'block';
        if (form) form.style.display = 'none';
        currentView = 'rating';
    };

    const paintStars = () => {
        ratingStars.forEach((star) => {
            const value = Number(star.dataset.rating);
            star.textContent = rating !== null && value <= rating ? '★' : '☆';
            star.setAttribute('aria-checked', String(value === rating));
        });
    };

    ratingStars.forEach((star) => {
        star.addEventListener('click', () => {
            rating = Number(star.dataset.rating);
            ratingSubmit.disabled = false;
            paintStars();
        });
    });

    if (ratingForm) {
        ratingForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (rating === null || !conversationId) return;

            ratingSubmit.disabled = true;
            ratingError.style.display = 'none';

            axios.post(`${base}/conversations/${conversationId}/rating`, {
                rating,
                comment: document.getElementById('chat-widget-rating-comment').value.trim() || null,
                visitor_token: visitorToken,
            })
                .then(hideRating)
                .catch((error) => {
                    // 409 means it was already scored — from another tab, most
                    // likely. Treat that as done rather than as a failure.
                    if (error.response?.status === 409) {
                        hideRating();
                        return;
                    }

                    ratingError.textContent = error.response?.data?.message
                        || 'We could not save your feedback.';
                    ratingError.style.display = 'block';
                    ratingSubmit.disabled = false;
                });
        });
    }

    // Open on the help-center home. Chat starts when the visitor asks.
    showView('home');
    loadKnowledge();

    if (preChatForm) {
        preChatForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const name = document.getElementById('chat-widget-name').value.trim();
            const email = document.getElementById('chat-widget-email').value.trim();
            const submit = preChatForm.querySelector('button[type="submit"]');

            preChatError.style.display = 'none';
            submit.disabled = true;

            start({ name: name || null, email: email || null })
                .then(() => {
                    showView('chat');
                })
                .catch((error) => {
                    // Chiefly a malformed email — the only field the server
                    // validates beyond length.
                    preChatError.textContent = error.response?.data?.errors?.email?.[0]
                        || 'We could not start the chat. Please check your details.';
                    preChatError.style.display = 'block';
                })
                .finally(() => {
                    submit.disabled = false;
                });
        });
    }

    const openPanel = () => {
        setPanelOpen(true);
        if (currentView === 'chat' && conversationId) {
            // Closed iframes get aggressive timer throttling — pull anything the
            // poll may have missed the moment the visitor opens the panel again.
            loadMessages({ notifyNew: true });
            markConversationRead();
            return;
        }

        showView('home');
        loadKnowledge();
    };

    if (openChatBtn) {
        openChatBtn.addEventListener('click', () => enterChat());
    }

    if (homeMenuBtn) {
        homeMenuBtn.addEventListener('click', () => {
            showView('home');
            loadKnowledge();
        });
    }

    if (newChatBtn) {
        newChatBtn.addEventListener('click', () => {
            setMenuOpen(false);
            enterChat({ forceNew: true });
        });
    }

    if (articleBack) {
        articleBack.addEventListener('click', () => showView('home'));
    }

    if (articleChat) {
        articleChat.addEventListener('click', () => enterChat());
    }

    toggleBtn.addEventListener('click', () => {
        if (isPanelOpen()) {
            setPanelOpen(false);
            return;
        }

        openPanel();
    });

    if (backBtn) {
        backBtn.addEventListener('click', () => {
            if (currentView === 'article') {
                showView('home');
                return;
            }

            if (currentView === 'chat' || currentView === 'prechat') {
                showView('home');
                loadKnowledge();
                return;
            }

            setPanelOpen(false);
        });
    }

    // The widget route is also used as the admin's standalone preview. Without
    // an iframe host, a collapsed launcher leaves an otherwise empty page and
    // looks broken. Embedded widgets remain collapsed until the visitor clicks.
    if (window.parent === window) {
        document.body.classList.add('chat-widget-standalone');
        openPanel();
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            fileName.textContent = fileInput.files[0] ? `Attached: ${fileInput.files[0].name}` : '';
        });
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const body = input.value.trim();
        const file = fileInput?.files[0];

        if (!body && !file) return;

        const send = () => {
            if (!conversationId) return;

            // Composer is also shown on the help-center home; always jump to the
            // conversation view before appending so the reply is not hidden.
            if (currentView !== 'chat') {
                showView('chat');
            }

            const pendingBody = body;
            input.value = '';
            input.disabled = true;

            const finish = () => {
                input.disabled = false;
                input.focus();
            };

            if (file) {
                const payload = new FormData();
                payload.append('file', file);
                payload.append('visitor_token', visitorToken);
                if (pendingBody) payload.append('caption', pendingBody);

                axios.post(`${base}/conversations/${conversationId}/attachments`, payload)
                    .then(({ data }) => {
                        appendMessage(data);
                        fileInput.value = '';
                        fileName.textContent = '';
                    })
                    .catch((error) => {
                        input.value = pendingBody;
                        fileName.textContent = error.response?.data?.errors?.file?.[0]
                            || 'That file could not be sent.';
                    })
                    .finally(finish);

                return;
            }

            axios.post(`${base}/conversations/${conversationId}/messages`, {
                body: pendingBody,
                visitor_token: visitorToken,
            }).then(({ data }) => {
                appendMessage(data);
                if (data.bot_reply) {
                    appendMessage(data.bot_reply);
                }
            }).catch(() => {
                // Restore so a failed send does not look like the message vanished.
                input.value = pendingBody;
            }).finally(finish);
        };

        if (!conversationId) {
            enterChat().then(send).catch(() => {
                // Keep the draft if chat could not start.
            });
            return;
        }

        send();
    });

    let typingTimer = null;
    input.addEventListener('input', () => {
        if (typingTimer || !conversationId) return;

        axios.post(`${base}/conversations/${conversationId}/typing`, { visitor_token: visitorToken }).catch(() => {});

        typingTimer = setTimeout(() => {
            typingTimer = null;
        }, 2000);
    });

    // Host pages keep the iframe at 96×96 while collapsed; browsers throttle
    // timers in that state. Pull fresh messages whenever the visitor comes back.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && conversationId) {
            loadMessages({ notifyNew: true });
        }
    });

    window.addEventListener('focus', () => {
        if (conversationId) {
            loadMessages({ notifyNew: true });
        }
    });
}

