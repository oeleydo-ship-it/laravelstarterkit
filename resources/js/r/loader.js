import '../../sass/r/public.scss';

(function () {
    const cfg = window.__R || {};
    if (!cfg.k || !cfg.b || window.__rBooted) return;
    window.__rBooted = true;
    const el = (tag, cls, text) => { const node = document.createElement(tag); if (cls) node.className = cls; if (text != null) node.textContent = text; return node; };
    const stars = (rating) => '★★★★★'.slice(0, Number(rating || 0));
    const card = (review) => {
        const node = el('article', 'r-card');
        node.appendChild(el('div', 'r-stars', stars(review.r)));
        node.appendChild(el('blockquote', 'r-quote', `“${review.b || ''}”`));
        const author = el('div', 'r-author');
        if (review.a) { const image = el('img', 'r-avatar'); image.src = review.a; image.alt = ''; image.loading = 'lazy'; author.appendChild(image); }
        const name = el('div', 'r-name', review.n || 'Customer'); author.appendChild(name);
        if (review.c) author.appendChild(el('div', 'r-company', review.c));
        node.appendChild(author); return node;
    };
    const render = (widget, mount) => {
        const root = el('section', `r-widget r-${widget.l === 'carousel' ? 'carousel' : 'stacked'}`);
        if (widget.s?.accent_color) root.style.setProperty('--r-accent', widget.s.accent_color);
        const list = el('div', 'r-list'); (widget.i || []).forEach((review) => list.appendChild(card(review)));
        root.appendChild(list);
        if (widget.l === 'carousel' && widget.i?.length > 1) {
            let index = 0; const move = (delta) => { index = (index + delta + widget.i.length) % widget.i.length; list.style.transform = `translateX(-${index * 100}%)`; };
            const controls = el('div', 'r-controls'); const prev = el('button', 'r-prev', '‹'); const next = el('button', 'r-next', '›');
            prev.type = next.type = 'button'; prev.onclick = () => move(-1); next.onclick = () => move(1); controls.append(prev, next); root.appendChild(controls);
        }
        mount.replaceChildren(root);
    };
    fetch(`${cfg.b}/c`, { headers: { Accept: 'application/json' }, mode: 'cors' }).then((res) => res.json()).then((data) => {
        const widgets = Array.isArray(data.w) ? data.w : [];
        const mounts = [...document.querySelectorAll('[data-r-widget]')];
        if (!mounts.length && widgets[0]) { const mount = el('div', 'r-mount'); document.body.appendChild(mount); render(widgets[0], mount); return; }
        mounts.forEach((mount, index) => { const wanted = Number(mount.dataset.rWidget); const widget = widgets.find((item) => item.id === wanted) || widgets[index] || widgets[0]; if (widget) render(widget, mount); });
    }).catch(() => {});
})();
