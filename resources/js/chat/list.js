/**
 * Live conversation list for the inbox page and the conversation sidebar.
 * Prefer Echo on the tenant inbox channel; poll JSON as a production fallback.
 */
const root = document.querySelector('[data-chat-inbox]');

if (root && window.axios) {
    const list = root.querySelector('[data-chat-inbox-list]');
    const variant = root.dataset.variant || 'inbox';
    const tenantId = root.dataset.tenantId;
    const currentUserId = Number(root.dataset.currentUserId || 0);
    const statusFilter = root.dataset.status || '';
    const listFilter = root.dataset.filter || '';
    const emptyText = root.dataset.emptyText || 'No conversations yet.';
    const feedUrl = root.dataset.feedUrl
        || `${window.location.pathname}${window.location.search}`;

    const items = new Map();

    const matchesFilters = (item) => {
        if (statusFilter) {
            if (item.status !== statusFilter) {
                return false;
            }
        } else if (item.status !== 'open') {
            return false;
        }

        if (listFilter === 'mine' && Number(item.assigned_to) !== currentUserId) {
            return false;
        }
        if (listFilter === 'unassigned' && item.assigned_to != null) {
            return false;
        }
        if (listFilter === 'rated' && !item.is_rated) {
            return false;
        }

        return true;
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const renderInboxRow = (item) => {
        const unread = item.unread_count
            ? `<span class="chat-inbox__unread">${escapeHtml(item.unread_count)}</span>`
            : '';
        const isMine = item.is_mine || Number(item.assigned_to) === currentUserId;
        const assignee = item.assigned_to
            ? `<span class="chat-pill ${isMine ? 'chat-pill--agent' : 'chat-pill--muted'}">${
                isMine ? 'You' : escapeHtml(item.assignee_name || 'Agent')
            }</span>`
            : '<span class="chat-pill chat-pill--warn">Needs accept</span>';
        const rating = item.is_rated
            ? `<span class="chat-rating-badge">${'★'.repeat(Number(item.rating || 0))}${'☆'.repeat(Math.max(0, 5 - Number(item.rating || 0)))}</span>`
            : '';
        const statusLabel = item.status
            ? item.status.charAt(0).toUpperCase() + item.status.slice(1)
            : '';

        return `
            <a href="${escapeHtml(item.url)}" class="chat-inbox__row" data-conversation-id="${item.id}">
                <div class="chat-inbox__row-main">
                    <div class="chat-inbox__visitor">
                        <strong>${escapeHtml(item.visitor_label)}</strong>
                        ${unread}
                    </div>
                    <p class="chat-inbox__preview">${escapeHtml(item.last_message_preview || 'No messages yet')}</p>
                </div>
                <div class="chat-inbox__row-side">
                    <span class="chat-pill chat-pill--${item.status === 'open' ? 'open' : 'closed'}">${escapeHtml(statusLabel)}</span>
                    ${assignee}
                    ${rating}
                    <time>${escapeHtml(item.last_message_at_human || '')}</time>
                </div>
            </a>
        `;
    };

    const renderSidebarRow = (item) => {
        const label = item.visitor_label || 'Visitor';
        const initial = escapeHtml((label.trim().charAt(0) || '?').toUpperCase());
        const unread = item.unread_count
            ? `<span class="chat-inbox__unread">${escapeHtml(item.unread_count)}</span>`
            : '';
        const tags = [];
        if (!item.assigned_to) {
            tags.push('<span class="chat-pill chat-pill--warn">Needs accept</span>');
        } else if (Number(item.assigned_to) === currentUserId) {
            tags.push('<span class="chat-pill chat-pill--agent">You</span>');
        }

        const activeId = Number(root.dataset.activeId || 0);
        const isActive = activeId === Number(item.id);

        return `
            <a href="${escapeHtml(item.url)}"
               class="chat-workspace__person ${isActive ? 'is-active' : ''}"
               data-conversation-id="${item.id}">
                <div class="chat-workspace__avatar" aria-hidden="true">${initial}</div>
                <div class="chat-workspace__person-body">
                    <div class="chat-workspace__person-top">
                        <strong>${escapeHtml(label)}</strong>
                        <time>${escapeHtml(item.last_message_at_short || item.last_message_at_human || '')}</time>
                    </div>
                    <p>${escapeHtml(item.last_message_preview || 'No messages yet')}</p>
                    <div class="chat-workspace__person-tags">
                        ${unread}
                        ${tags.join('')}
                    </div>
                </div>
            </a>
        `;
    };

    const renderRow = variant === 'sidebar' ? renderSidebarRow : renderInboxRow;
    const emptyClass = variant === 'sidebar' ? 'chat-workspace__people-empty' : 'chat-inbox__empty';

    const render = () => {
        const filtered = [...items.values()]
            .filter(matchesFilters)
            .sort((a, b) => {
                const aTime = Date.parse(a.last_message_at || '') || 0;
                const bTime = Date.parse(b.last_message_at || '') || 0;
                return bTime - aTime;
            });

        if (!filtered.length) {
            list.innerHTML = `<div class="${emptyClass}">${escapeHtml(emptyText)}</div>`;
            return;
        }

        list.innerHTML = filtered.map(renderRow).join('');
    };

    const replaceAll = (rows) => {
        items.clear();
        rows.forEach((row) => items.set(Number(row.id), row));
        render();
    };

    const upsert = (row) => {
        items.set(Number(row.id), {
            ...items.get(Number(row.id)),
            ...row,
            is_mine: row.is_mine ?? (Number(row.assigned_to) === currentUserId),
        });
        render();
    };

    const poll = () => {
        window.axios.get(feedUrl, { headers: { Accept: 'application/json' } })
            .then(({ data }) => replaceAll(data.data || []))
            .catch(() => {});
    };

    if (window.Echo && tenantId) {
        window.Echo.private(`tenant.${tenantId}.inbox`)
            .listen('.conversation.updated', (data) => upsert(data));
    }

    // Seed from the server so Echo updates have siblings to sort against,
    // then keep polling if websockets are misconfigured in production.
    poll();
    window.setInterval(poll, 3000);
}
