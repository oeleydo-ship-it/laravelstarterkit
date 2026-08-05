const thread = document.getElementById('chat-thread');

if (thread) {
    const tenantId = thread.dataset.tenantId;
    const conversationId = thread.dataset.conversationId;
    const sendUrl = thread.dataset.sendUrl;
    const messagesUrl = thread.dataset.messagesUrl;
    const readUrl = thread.dataset.readUrl;
    const typingUrl = thread.dataset.typingUrl;
    const noteUrl = thread.dataset.noteUrl;
    const attachmentUrl = thread.dataset.attachmentUrl;
    const suggestUrl = thread.dataset.suggestUrl;
    const articleSearchUrl = thread.dataset.articleSearchUrl;

    const form = document.getElementById('chat-send-form');
    const input = document.getElementById('chat-message-input');
    const typingIndicator = document.getElementById('chat-typing-indicator');
    const statusBadge = document.getElementById('chat-status-badge');
    const sendButton = document.getElementById('chat-send-button');
    const modeHint = document.getElementById('chat-mode-hint');
    const cannedSelect = document.getElementById('chat-canned');
    const fileInput = document.getElementById('chat-file-input');
    const fileName = document.getElementById('chat-file-name');
    const suggestButton = document.getElementById('chat-suggest-button');
    const articleSearch = document.getElementById('chat-article-search');
    const articleResults = document.getElementById('chat-article-results');

    const canReply = thread.dataset.canReply === '1';
    let mode = canReply ? 'reply' : 'note';

    const scrollToBottom = () => {
        thread.scrollTop = thread.scrollHeight;
    };

    const appendMessage = ({
        id,
        sender_type: senderType,
        sender_name: senderName,
        body,
        attachment,
        download_url: downloadUrl,
        created_at: createdAt,
    }) => {
        if (id != null && thread.querySelector(`[data-id="${id}"]`)) {
            return false;
        }

        const el = document.createElement('div');
        el.className = `chat-message chat-message-${senderType}`;
        if (id != null) {
            el.dataset.id = String(id);
        }

        const time = new Date(createdAt || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const name = senderName
            || (senderType === 'bot' ? 'Assistant' : (senderType === 'agent' ? 'Agent' : 'Visitor'));

        el.innerHTML = `
            <div class="chat-message-bubble">
                <div class="chat-message-meta">${name} · ${time}</div>
                <div class="chat-message-body"></div>
            </div>
        `;
        el.querySelector('.chat-message-body').textContent = body;

        if (attachment) {
            // The broadcast payload has no URL of its own — each audience builds
            // the one its own download route expects.
            const link = document.createElement('a');
            link.className = 'chat-attachment';
            link.target = '_blank';
            link.rel = 'noopener';
            link.href = downloadUrl || `/chat/attachments/${attachment.id}`;
            link.textContent = `📎 ${attachment.name} (${attachment.human_size})`;
            el.querySelector('.chat-message-bubble').appendChild(link);
        }

        thread.appendChild(el);
        scrollToBottom();

        return true;
    };

    const appendNote = ({ id, author_name: author, body, created_at: createdAt }) => {
        if (id != null && thread.querySelector(`[data-id="${id}"]`)) {
            return false;
        }

        const el = document.createElement('div');
        el.className = 'chat-message chat-message-internal';
        if (id != null) {
            el.dataset.id = String(id);
        }

        const time = new Date(createdAt || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        el.innerHTML = `
            <div class="chat-message-meta small">
                <span class="badge bg-warning text-dark">Internal note</span>
                <span class="chat-note-author"></span> &middot; ${time}
            </div>
            <div class="chat-message-body"></div>
        `;
        el.querySelector('.chat-note-author').textContent = author || 'Agent';
        el.querySelector('.chat-message-body').textContent = body;

        thread.appendChild(el);
        scrollToBottom();

        return true;
    };

    // Reply vs internal note. Notes go to a different endpoint and a different
    // channel, so the visitor never receives them. Visitor replies require
    // assignment to the current agent (`data-can-reply`).
    document.querySelectorAll('[data-chat-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.chatMode === 'reply' && !canReply) {
                return;
            }

            mode = button.dataset.chatMode;

            document.querySelectorAll('[data-chat-mode]').forEach((b) => b.classList.remove('active'));
            button.classList.add('active');

            const isNote = mode === 'note';
            input.placeholder = isNote ? 'Add a note for your team…' : 'Type a reply…';
            sendButton.textContent = isNote ? 'Add note' : 'Send';
            sendButton.classList.toggle('btn-warning', isNote);
            sendButton.classList.toggle('btn-primary', !isNote);
            modeHint.textContent = isNote
                ? 'Internal notes are only visible to your team.'
                : 'Replies are visible to the visitor.';
            form.classList.toggle('chat-note-mode', isNote);

            const replyTools = document.getElementById('chat-reply-tools');
            if (replyTools) replyTools.style.display = isNote ? 'none' : '';
            if (cannedSelect) cannedSelect.style.display = isNote ? 'none' : '';
            const attachBtn = document.querySelector('.chat-attach-btn');
            if (attachBtn) attachBtn.style.visibility = isNote ? 'hidden' : 'visible';
        });
    });

    if (cannedSelect) {
        cannedSelect.addEventListener('change', () => {
            if (!cannedSelect.value) return;

            input.value = cannedSelect.value;
            cannedSelect.value = '';
            input.focus();
        });
    }

    scrollToBottom();

    axios.post(readUrl).catch(() => {});

    window.Echo.private(`tenant.${tenantId}.conversation.${conversationId}`)
        .listen('.message.sent', (data) => {
            appendMessage(data);
            axios.post(readUrl).catch(() => {});
        })
        .listen('.chat.typing', (data) => {
            if (data.sender_type === 'visitor') {
                typingIndicator.textContent = 'Visitor is typing…';
                typingIndicator.style.display = 'block';
                clearTimeout(typingIndicator._hideTimer);
                typingIndicator._hideTimer = setTimeout(() => {
                    typingIndicator.style.display = 'none';
                }, 3000);
            }
        })
        .listen('.conversation.updated', (data) => {
            if (statusBadge) {
                statusBadge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                statusBadge.className = `chat-pill chat-pill--${data.status}`;
            }
        });

    // Separate agent-only channel — the visitor is not authorized to join it.
    window.Echo.private(`tenant.${tenantId}.conversation.${conversationId}.internal`)
        .listen('.note.added', (data) => appendNote(data));

    // Polling fallback when websocket/auth/proxy is misconfigured in production.
    // Dedupes by data-id so Echo + poll can both run safely.
    if (messagesUrl) {
        const pollMessages = () => {
            axios.get(messagesUrl)
                .then(({ data }) => {
                    let added = false;
                    (data.data || []).forEach((item) => {
                        if (item.type === 'note') {
                            added = appendNote(item) || added;
                        } else {
                            added = appendMessage(item) || added;
                        }
                    });
                    if (added) {
                        axios.post(readUrl).catch(() => {});
                    }
                })
                .catch(() => {});
        };

        window.setInterval(pollMessages, 3000);
    }

    // ─── Knowledge base lookup ───

    if (articleSearch) {
        let searchTimer = null;

        articleSearch.addEventListener('input', () => {
            clearTimeout(searchTimer);

            const term = articleSearch.value.trim();
            if (term.length < 2) {
                articleResults.classList.add('d-none');
                articleResults.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(() => {
                axios.get(articleSearchUrl, { params: { q: term } }).then(({ data }) => {
                    articleResults.innerHTML = '';

                    if (!data.length) {
                        articleResults.classList.add('d-none');
                        return;
                    }

                    data.forEach((article) => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action py-1 small';
                        item.textContent = article.title;
                        item.addEventListener('click', () => {
                            input.value = article.body;
                            input.focus();
                            articleResults.classList.add('d-none');
                            articleSearch.value = '';
                        });
                        articleResults.appendChild(item);
                    });

                    articleResults.classList.remove('d-none');
                });
            }, 250);
        });
    }

    // ─── AI draft ───

    if (suggestButton) {
        suggestButton.addEventListener('click', () => {
            const original = suggestButton.textContent;
            suggestButton.disabled = true;
            suggestButton.textContent = 'Drafting…';

            axios.post(suggestUrl)
                .then(({ data }) => {
                    // Into the composer, never straight to the visitor.
                    input.value = data.suggestion;
                    input.focus();
                })
                .catch((error) => {
                    modeHint.textContent = error.response?.data?.message
                        || 'The assistant could not draft a reply right now.';
                })
                .finally(() => {
                    suggestButton.disabled = false;
                    suggestButton.textContent = original;
                });
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            fileName.textContent = fileInput.files[0] ? `Attached: ${fileInput.files[0].name}` : '';
        });
    }

    const clearFile = () => {
        if (!fileInput) return;
        fileInput.value = '';
        fileName.textContent = '';
    };

    const sendAttachment = (file, caption) => {
        const payload = new FormData();
        payload.append('file', file);
        if (caption) payload.append('caption', caption);

        axios.post(attachmentUrl, payload)
            .then(({ data }) => {
                appendMessage(data);
                clearFile();
            })
            .catch((error) => {
                const message = error.response?.data?.errors?.file?.[0] || 'That file could not be sent.';
                fileName.textContent = message;
            });
    };

    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const body = input.value.trim();
            const file = fileInput?.files[0];

            if (mode === 'reply' && !canReply) {
                modeHint.textContent = 'Accept this chat before sending a reply.';
                return;
            }

            // Files always go to the visitor as a reply; internal notes stay text
            // only, so there is no path for a note's file to reach the widget.
            if (file && mode === 'reply') {
                input.value = '';
                sendAttachment(file, body);
                return;
            }

            if (!body) return;

            input.value = '';

            if (mode === 'note') {
                axios.post(noteUrl, { body }).then(({ data }) => appendNote(data));
                return;
            }

            axios.post(sendUrl, { body }).then(({ data }) => {
                appendMessage({
                    id: data.id,
                    sender_type: 'agent',
                    sender_name: data.sender?.name,
                    body: data.body,
                    created_at: data.created_at,
                });
            }).catch((error) => {
                modeHint.textContent = error.response?.data?.message
                    || 'Accept this chat before sending a reply.';
            });
        });

        let typingTimer = null;
        input.addEventListener('input', () => {
            // Never leak "agent is typing" to the visitor while drafting a note,
            // or before the chat has been accepted.
            if (typingTimer || mode === 'note' || !canReply) return;

            axios.post(typingUrl).catch(() => {});

            typingTimer = setTimeout(() => {
                typingTimer = null;
            }, 2000);
        });
    }
}
