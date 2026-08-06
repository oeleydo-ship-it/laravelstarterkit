import '../../sass/f/public.scss';

(function () {
    const cfg = window.__F || {};
    const key = cfg.k;
    const base = cfg.b;
    const brand = cfg.c || '#2563eb';

    if (!key || !base || window.__fBooted) {
        return;
    }
    window.__fBooted = true;

    const cooldownKey = (id) => `f_${key}_f_${id}`;
    const countKey = (id) => `f_${key}_n_${id}`;
    const markedThisPage = new Set();

    const displayCount = (id) => Number(localStorage.getItem(countKey(id)) || 0);

    const canShow = (form) => {
        const g = form.s || {};
        const max = Number(g.max_displays ?? 0);
        if (max > 0 && displayCount(form.id) >= max) return false;

        const hours = Number(g.frequency_hours ?? 0);
        if (hours > 0) {
            const until = Number(localStorage.getItem(cooldownKey(form.id)) || 0);
            if (until && Date.now() < until) return false;
        }
        return true;
    };

    const markSeen = (form) => {
        if (markedThisPage.has(form.id)) return;
        markedThisPage.add(form.id);

        const g = form.s || {};
        localStorage.setItem(countKey(form.id), String(displayCount(form.id) + 1));

        const hours = Number(g.frequency_hours ?? 0);
        if (hours > 0) {
            localStorage.setItem(cooldownKey(form.id), String(Date.now() + hours * 3600 * 1000));
        }
    };

    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text != null) node.textContent = text;
        return node;
    };

    const fieldHtml = (f) => {
        const n = esc(f.key);
        const l = `<label>${esc(f.label)}${f.required ? ' *' : ''}</label>`;
        if (f.type === 'textarea') {
            return `<div class="f-field">${l}<textarea name="${n}" ${f.required ? 'required' : ''}></textarea></div>`;
        }
        if (f.type === 'select') {
            return `<div class="f-field">${l}<select name="${n}" ${f.required ? 'required' : ''}><option value="">Choose…</option>${(f.options || []).map((o) => `<option>${esc(o)}</option>`).join('')}</select></div>`;
        }
        if (f.type === 'rating' || f.type === 'nps') {
            const count = f.type === 'nps' ? 11 : 5;
            const offset = f.type === 'nps' ? 0 : 1;
            return `<div class="f-field">${l}<div class="f-score">${Array.from({ length: count }, (_, i) => `<label><input type="radio" name="${n}" value="${i + offset}" ${f.required && i === 0 ? 'required' : ''}>${i + offset}</label>`).join('')}</div></div>`;
        }
        return `<div class="f-field">${l}<input type="${f.type === 'email' ? 'email' : 'text'}" name="${n}" ${f.required ? 'required' : ''}></div>`;
    };

    const bindSubmit = (formEl, form) => {
        formEl.addEventListener('submit', (e) => {
            e.preventDefault();
            const answers = Object.fromEntries(new FormData(e.target).entries());
            if (answers.f_meta_hp) return;
            delete answers.f_meta_hp;
            fetch(`${base}/s`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ i: form.id, answers, page_url: location.href, website: '' }),
                mode: 'cors',
            })
                .then((r) => r.json())
                .then((v) => {
                    if (v.ok) e.target.innerHTML = `<p class="f-thanks">${esc(v.thank_you || 'Thank you.')}</p>`;
                })
                .catch(() => {});
        });
    };

    const buildCard = (form, color, onClose) => {
        const root = el('div', 'f-root');
        root.style.setProperty('--f-color', color || brand);
        const card = el('div', 'f-card');
        card.innerHTML = `
            <h3 class="f-title">${esc(form.n)}</h3>
            <form class="f-form">
                ${(form.f || []).map(fieldHtml).join('')}
                <input class="f-hp" name="f_meta_hp" tabindex="-1" autocomplete="off">
                <button type="submit" class="f-btn">Submit</button>
            </form>`;
        if (onClose) {
            const btn = el('button', 'f-close', '×');
            btn.type = 'button';
            btn.setAttribute('aria-label', 'Close');
            btn.addEventListener('click', onClose);
            card.prepend(btn);
        }
        root.appendChild(card);
        bindSubmit(card.querySelector('form'), form);
        return root;
    };

    const showPopup = (form, color) => {
        const overlay = el('div', 'f-overlay');
        const dismiss = () => overlay.remove();
        overlay.appendChild(buildCard(form, color, dismiss));
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) dismiss();
        });
        document.body.appendChild(overlay);
        markSeen(form);
    };

    const renderInline = (mount, form, color) => {
        const mode = (form.s && form.s.display_mode) || 'inline';
        const closable = mode === 'popup' || Boolean(form.s?.closable);
        const dismiss = () => {
            mount.replaceChildren();
        };
        mount.replaceChildren(buildCard(form, color, closable ? dismiss : null));
        markSeen(form);
    };

    fetch(`${base}/c`, { headers: { Accept: 'application/json' }, mode: 'cors' })
        .then((r) => r.json())
        .then((data) => {
            const forms = Array.isArray(data.i) ? data.i : [];
            const color = data.c || brand;
            const mounts = [...document.querySelectorAll('[data-f-form]')];

            mounts.forEach((mount) => {
                const id = mount.dataset.fForm;
                const form = forms.find((v) => String(v.id) === String(id)) || forms[0];
                if (!form || !canShow(form)) return;

                const delay = Number(form.s?.delay_ms ?? 0);
                const mode = (form.s && form.s.display_mode) || 'inline';
                const run = () => {
                    if (mode === 'popup') showPopup(form, color);
                    else renderInline(mount, form, color);
                };
                if (delay > 0) setTimeout(run, delay);
                else run();
            });

            // Auto popup for live forms with display_mode=popup when no mount exists
            if (!mounts.length) {
                const popup = forms.find((f) => (f.s?.display_mode || 'inline') === 'popup' && canShow(f));
                if (popup) {
                    const delay = Number(popup.s?.delay_ms ?? 0);
                    const run = () => showPopup(popup, color);
                    if (delay > 0) setTimeout(run, delay);
                    else run();
                }
            }
        })
        .catch(() => {});
})();
