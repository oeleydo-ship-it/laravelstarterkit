import '../../sass/b/public.scss';

(function () {
    const cfg = window.__B || {};
    const key = cfg.k;
    const url = cfg.u;
    const brand = cfg.c || '#0f766e';
    const g = cfg.g || {};

    if (!key || !url || window.__bBooted) {
        return;
    }
    window.__bBooted = true;

    if (g.enabled === false || g.enabled === 0 || g.enabled === '0') {
        return;
    }

    const cooldownKey = `b_${key}_f`;
    const countKey = `b_${key}_n`;

    const displayCount = () => Number(localStorage.getItem(countKey) || 0);

    const canShow = () => {
        const max = Number(g.max_displays ?? 0);
        if (max > 0 && displayCount() >= max) return false;

        const hours = Number(g.frequency_hours ?? 24);
        if (hours > 0) {
            const until = Number(localStorage.getItem(cooldownKey) || 0);
            if (until && Date.now() < until) return false;
        }
        return true;
    };

    const markSeen = () => {
        localStorage.setItem(countKey, String(displayCount() + 1));
        const hours = Number(g.frequency_hours ?? 24);
        if (hours > 0) {
            localStorage.setItem(cooldownKey, String(Date.now() + hours * 3600 * 1000));
        }
    };

    if (!canShow()) {
        return;
    }

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text != null) node.textContent = text;
        return node;
    };

    const pos = String(g.position || 'bottom-right');
    const root = el('div', `b-root b-${pos.replace('_', '-')}`);
    root.style.setProperty('--b-color', brand);

    const close = el('button', 'b-close', '×');
    close.type = 'button';
    close.setAttribute('aria-label', 'Close');
    close.addEventListener('click', () => root.remove());

    const link = el('a', 'b-btn', g.label || 'Book a time');
    link.href = url;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';

    root.append(close, link);
    document.body.appendChild(root);
    markSeen();
})();
