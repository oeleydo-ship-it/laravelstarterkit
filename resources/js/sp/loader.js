import '../../sass/sp/public.scss';

(function () {
    const cfg = window.__SP || {};
    const key = cfg.k;
    const base = cfg.b;

    if (!key || !base || window.__spBooted) {
        return;
    }
    window.__spBooted = true;

    const countKey = `sp_${key}_n`;
    const displayCount = () => Number(localStorage.getItem(countKey) || 0);

    const canShow = (g) => {
        const max = Number(g.max_displays ?? 0);
        if (max > 0 && displayCount() >= max) return false;
        return true;
    };

    const markPageShown = () => {
        localStorage.setItem(countKey, String(displayCount() + 1));
    };

    const el = (tag, cls, text) => {
        const node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text != null) node.textContent = text;
        return node;
    };

    const posClass = (position) => {
        switch (position) {
            case 'bottom-right': return 'sp-br';
            case 'top-left': return 'sp-tl';
            case 'top-right': return 'sp-tr';
            default: return 'sp-bl';
        }
    };

    const shuffle = (items) => {
        const copy = items.slice();
        for (let i = copy.length - 1; i > 0; i -= 1) {
            const j = Math.floor(Math.random() * (i + 1));
            [copy[i], copy[j]] = [copy[j], copy[i]];
        }
        return copy;
    };

    const relativeTime = (iso) => {
        if (!iso) return 'just now';
        const then = Date.parse(iso);
        if (!then) return 'just now';
        const mins = Math.max(0, Math.round((Date.now() - then) / 60000));
        if (mins < 1) return 'just now';
        if (mins < 60) return `${mins} min ago`;
        const hours = Math.round(mins / 60);
        if (hours < 24) return `${hours} hr ago`;
        const days = Math.round(hours / 24);
        return `${days} day${days === 1 ? '' : 's'} ago`;
    };

    const showToast = (item, g, root) => {
        const toast = el('div', `sp-toast ${posClass(g.position)}`);
        if (g.accent_color) toast.style.setProperty('--sp-accent', g.accent_color);

        const avatar = el('div', 'sp-avatar');
        if (item.a) {
            const img = el('img', 'sp-avatar-img');
            img.src = item.a;
            img.alt = '';
            img.loading = 'lazy';
            avatar.appendChild(img);
        } else {
            avatar.textContent = (item.n || '?').slice(0, 1).toUpperCase();
            avatar.style.background = g.accent_color || '#0f766e';
        }
        toast.appendChild(avatar);

        const text = el('div', 'sp-text');
        const line = el('div', 'sp-line');
        const name = item.n || 'Someone';
        const verb = item.v || 'purchased';
        const product = item.i || 'an item';
        line.appendChild(el('strong', null, name));
        line.appendChild(document.createTextNode(` ${verb} `));
        if (item.u) {
            const link = el('a', 'sp-item', product);
            link.href = item.u;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            line.appendChild(link);
        } else {
            line.appendChild(el('span', 'sp-item', product));
        }
        text.appendChild(line);

        const meta = el('div', 'sp-meta');
        const parts = [];
        if (item.l) parts.push(item.l);
        parts.push(relativeTime(item.at));
        meta.textContent = parts.join(' · ');
        text.appendChild(meta);
        toast.appendChild(text);

        const close = el('button', 'sp-close', '×');
        close.type = 'button';
        close.setAttribute('aria-label', 'Close');
        close.addEventListener('click', () => toast.remove());
        toast.appendChild(close);

        root.appendChild(toast);

        window.setTimeout(() => {
            toast.classList.add('sp-out');
            window.setTimeout(() => toast.remove(), 280);
        }, Number(g.display_duration_ms || 5000));
    };

    const run = (data) => {
        if (!data || data.e === false) return;
        const g = data.g || {};
        const items = Array.isArray(data.i) ? data.i : [];
        if (!items.length || !canShow(g)) return;

        markPageShown();

        const root = el('div', 'sp-root');
        document.body.appendChild(root);

        const queue = shuffle(items).slice(0, Math.max(1, Number(g.max_per_page || 4)));
        let index = 0;

        const next = () => {
            if (index >= queue.length) return;
            showToast(queue[index], g, root);
            index += 1;
            if (index < queue.length) {
                window.setTimeout(next, Number(g.interval_ms || 9000));
            }
        };

        window.setTimeout(next, Number(g.initial_delay_ms || 4000));
    };

    fetch(`${base}/c`, { headers: { Accept: 'application/json' }, mode: 'cors' })
        .then((res) => res.json())
        .then(run)
        .catch(() => {});
})();
