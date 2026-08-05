import '../../sass/x/public.scss';

(function () {
    const cfg = window.__X || {};
    const key = cfg.k;
    const base = cfg.b;
    const brand = cfg.c || '#2563eb';

    if (!key || !base || window.__xBooted) {
        return;
    }
    window.__xBooted = true;

    const root = document.createElement('div');
    root.className = 'x-root';
    document.body.appendChild(root);

    const storageKey = (id) => `x_${key}_f_${id}`;
    const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

    const canShow = (item) => {
        const g = item.g || {};
        if (g.url_contains && !String(window.location.href).includes(String(g.url_contains))) {
            return false;
        }
        if (g.device === 'mobile' && !isMobile()) return false;
        if (g.device === 'desktop' && isMobile()) return false;
        const hours = Number(g.frequency_hours ?? 24);
        if (hours > 0) {
            const until = Number(localStorage.getItem(storageKey(item.id)) || 0);
            if (until && Date.now() < until) return false;
        }
        return true;
    };

    const markSeen = (item) => {
        const hours = Number((item.g || {}).frequency_hours ?? 24);
        if (hours > 0) {
            localStorage.setItem(storageKey(item.id), String(Date.now() + hours * 3600 * 1000));
        }
    };

    const post = (path, body) => {
        try {
            fetch(`${base}/${path}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify(body),
                mode: 'cors',
                keepalive: true,
            }).catch(() => {});
        } catch {
            // ignore
        }
    };

    const track = (item, t) => {
        post('e', { i: item.id, t, p: window.location.pathname });
    };

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text != null) node.textContent = text;
        return node;
    };

    const colorOf = (item) => (item.s && item.s.brand_color) || brand;
    const textOf = (item) => (item.s && item.s.text_color) || '#ffffff';

    const posClass = (pos) => {
        switch (pos) {
            case 'bottom-left': return 'bl';
            case 'top-right': return 'tr';
            case 'top-left': return 'tl';
            default: return 'br';
        }
    };

    const attachCta = (parent, item) => {
        const label = item.c?.cta_label;
        const url = item.c?.cta_url;
        if (!label) return;
        const a = el('a', 'x-btn', label);
        a.style.background = colorOf(item);
        a.style.color = textOf(item);
        if (url) {
            a.href = url;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
        } else {
            a.href = '#';
            a.addEventListener('click', (e) => e.preventDefault());
        }
        a.addEventListener('click', () => track(item, 'click'));
        parent.appendChild(a);
    };

    const closeBtn = (onClose) => {
        const btn = el('button', 'x-close', '×');
        btn.type = 'button';
        btn.setAttribute('aria-label', 'Close');
        btn.addEventListener('click', onClose);
        return btn;
    };

    const maybeForm = (card, item, dismiss) => {
        const wantsForm = item.t === 'form' || item.c?.fields?.email || item.c?.fields?.name;
        if (!wantsForm) {
            attachCta(card, item);
            return;
        }

        const form = el('form', null);
        const hp = el('input', 'x-hp');
        hp.name = 'website';
        hp.tabIndex = -1;
        hp.autocomplete = 'off';
        form.appendChild(hp);

        if (item.c?.fields?.name) {
            const name = el('input', 'x-field');
            name.name = 'name';
            name.placeholder = 'Name';
            form.appendChild(name);
        }

        const email = el('input', 'x-field');
        email.type = 'email';
        email.name = 'email';
        email.placeholder = 'Email';
        email.required = true;
        form.appendChild(email);

        const submit = el('button', 'x-btn', item.c?.cta_label || 'Submit');
        submit.type = 'submit';
        submit.style.background = colorOf(item);
        submit.style.color = textOf(item);
        form.appendChild(submit);

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (hp.value) return;
            const data = Object.fromEntries(new FormData(form).entries());
            post('l', {
                i: item.id,
                email: data.email || null,
                name: data.name || null,
                page_url: window.location.href,
                website: '',
            });
            track(item, 'submit');
            markSeen(item);
            card.innerHTML = '';
            card.appendChild(el('div', 'x-title', item.c?.success_message || 'Thanks'));
            setTimeout(dismiss, 1800);
        });

        card.appendChild(form);
    };

    const renderBar = (item) => {
        const bar = el('div', `x-bar ${(item.c?.position === 'bottom') ? 'x-bottom' : 'x-top'}`);
        bar.style.background = colorOf(item);
        bar.style.color = textOf(item);
        if (item.c?.headline) bar.appendChild(el('strong', null, item.c.headline));
        if (item.c?.body) bar.appendChild(el('span', null, item.c.body));
        attachCta(bar, item);
        const dismiss = () => {
            bar.remove();
            markSeen(item);
            track(item, 'dismiss');
        };
        bar.appendChild(closeBtn(dismiss));
        root.appendChild(bar);
        track(item, 'impression');
    };

    const renderCard = (item, mode) => {
        const wrap = mode === 'popup' ? el('div', 'x-overlay') : el('div', `x-slide x-${posClass(item.c?.position)}`);
        const card = mode === 'popup' ? el('div', 'x-card') : wrap;
        if (mode === 'popup') wrap.appendChild(card);

        if (item.c?.headline) card.appendChild(el('div', 'x-title', item.c.headline));
        if (item.c?.body) card.appendChild(el('p', 'x-body', item.c.body));

        const dismiss = () => {
            (mode === 'popup' ? wrap : card).remove();
            markSeen(item);
            track(item, 'dismiss');
        };
        card.appendChild(closeBtn(dismiss));
        maybeForm(card, item, dismiss);

        root.appendChild(mode === 'popup' ? wrap : card);
        track(item, 'impression');
    };

    const renderToast = (item) => {
        const toast = el('div', `x-toast x-${posClass(item.c?.position)}`);
        const avatar = el('div', 'x-avatar', (item.c?.toast?.name || '?').slice(0, 1).toUpperCase());
        avatar.style.background = colorOf(item);
        toast.appendChild(avatar);
        const text = el('div', 'x-toast-text');
        const name = item.c?.toast?.name || 'Someone';
        const action = item.c?.toast?.action || item.c?.body || 'just joined';
        const loc = item.c?.toast?.location;
        text.textContent = loc ? `${name} ${action} · ${loc}` : `${name} ${action}`;
        toast.appendChild(text);
        root.appendChild(toast);
        track(item, 'impression');
        setTimeout(() => {
            toast.remove();
            markSeen(item);
        }, 5000);
    };

    const showOne = (item, items, force = false) => {
        if (!force && !canShow(item)) return;
        const delay = Number((item.g || {}).delay_ms || 0);
        window.setTimeout(() => {
            if (!force && !canShow(item)) return;
            switch (item.t) {
                case 'bar':
                    renderBar(item);
                    break;
                case 'popup':
                    renderCard(item, 'popup');
                    break;
                case 'slide_in':
                    renderCard(item, 'slide');
                    break;
                case 'form':
                    renderCard(item, item.c?.position === 'center' ? 'popup' : 'slide');
                    break;
                case 'toast':
                    renderToast(item);
                    break;
                case 'launcher': {
                    const btn = el('button', `x-launcher x-${posClass(item.c?.position)}`, item.c?.launcher_label || 'Updates');
                    btn.type = 'button';
                    btn.style.background = colorOf(item);
                    btn.style.color = textOf(item);
                    btn.addEventListener('click', () => {
                        track(item, 'click');
                        if (item.c?.opens_campaign_id) {
                            const target = items.find((i) => Number(i.id) === Number(item.c.opens_campaign_id));
                            if (target) showOne(target, items, true);
                        }
                    });
                    root.appendChild(btn);
                    track(item, 'impression');
                    break;
                }
                default:
                    break;
            }
        }, delay);
    };

    fetch(`${base}/c`, { headers: { Accept: 'application/json' }, mode: 'cors' })
        .then((r) => r.json())
        .then((data) => {
            const items = Array.isArray(data.i) ? data.i : [];
            items.forEach((item) => showOne(item, items));
        })
        .catch(() => {});
})();
