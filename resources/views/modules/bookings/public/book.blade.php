<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book — {{ $site->name }}</title>
    <style>
        :root { --brand: {{ $brand }}; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, sans-serif; background: #f1f5f9; color: #0f172a; }
        .wrap { max-width: 560px; margin: 40px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgba(15,23,42,.08); }
        h1 { font-size: 1.4rem; margin: 0 0 8px; }
        .muted { color: #64748b; font-size: .9rem; margin-bottom: 20px; }
        label { display: block; font-size: .85rem; font-weight: 600; margin: 12px 0 6px; }
        select, input, textarea { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px; font: inherit; }
        .slots { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .slot { border: 1px solid #e2e8f0; background: #fff; border-radius: 999px; padding: 8px 12px; cursor: pointer; font-size: .85rem; }
        .slot.is-on { background: var(--brand); color: #fff; border-color: var(--brand); }
        .btn { margin-top: 18px; width: 100%; border: 0; border-radius: 10px; padding: 12px; background: var(--brand); color: #fff; font-weight: 700; cursor: pointer; }
        .btn:disabled { opacity: .55; cursor: not-allowed; }
        .ok { background: #ecfdf5; color: #065f46; padding: 12px; border-radius: 10px; margin-bottom: 16px; }
        .err { background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 10px; margin-bottom: 16px; }
        .hp { position: absolute !important; left: -10000px !important; top: auto !important; width: 1px !important; height: 1px !important; overflow: hidden !important; opacity: 0 !important; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>{{ $site->name }}</h1>
        <p class="muted">Pick a service and time that works for you.</p>

        @if($errors->any())
            <div class="err">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if(!empty($booked))
            <div class="ok">{{ session('success') ?: 'Your appointment is confirmed. Check your email for details.' }}</div>
            <p class="muted mb-0">You can close this page, or <a href="{{ url('/b/'.$site->public_key) }}">book another time</a>.</p>
        @else
            <form method="POST" action="{{ url('/b/'.$site->public_key.'/book') }}" id="book-form">
                @csrf
                <div class="hp" aria-hidden="true">
                    <label>Company website<input type="text" name="b_meta_hp" tabindex="-1" autocomplete="off"></label>
                </div>

                <label>Service</label>
                <select name="service_id" id="service" required>
                    <option value="">Choose…</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" @selected(old('service_id') == $service->id)>
                            {{ $service->name }} ({{ $service->duration_minutes }} min)
                        </option>
                    @endforeach
                </select>

                <label>Date</label>
                <input type="date" id="date" min="{{ now($site->timezone)->toDateString() }}"
                       value="{{ old('date') }}" required>

                <label>Available times</label>
                <div class="slots" id="slots"><span class="muted">Select a service and date.</span></div>
                <input type="hidden" name="starts_at" id="starts_at" value="{{ old('starts_at') }}" required>

                <label>Your name</label>
                <input type="text" name="guest_name" value="{{ old('guest_name') }}" required autocomplete="name">

                <label>Email</label>
                <input type="email" name="guest_email" value="{{ old('guest_email') }}" required autocomplete="email">

                <label>Phone (optional)</label>
                <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" autocomplete="tel">

                <label>Notes (optional)</label>
                <textarea name="notes" rows="2">{{ old('notes') }}</textarea>

                <button class="btn" type="submit" id="submit-btn">Confirm booking</button>
            </form>
        @endif
    </div>
</div>
@unless(!empty($booked))
<script>
(function () {
    const service = document.getElementById('service');
    const date = document.getElementById('date');
    const slotsEl = document.getElementById('slots');
    const starts = document.getElementById('starts_at');
    const form = document.getElementById('book-form');
    const base = @json(url('/b/'.$site->public_key));

    async function loadSlots() {
        const keep = starts.value;
        starts.value = '';
        if (!service.value || !date.value) return;
        slotsEl.innerHTML = '<span class="muted">Loading…</span>';
        try {
            const url = base + '/slots?service_id=' + encodeURIComponent(service.value) + '&date=' + encodeURIComponent(date.value);
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const list = data.slots || [];
            if (!list.length) {
                slotsEl.innerHTML = '<span class="muted">No times open this day.</span>';
                return;
            }
            slotsEl.innerHTML = '';
            list.forEach((iso) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'slot' + (keep === iso ? ' is-on' : '');
                if (keep === iso) starts.value = iso;
                const d = new Date(iso);
                btn.textContent = d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                btn.addEventListener('click', () => {
                    slotsEl.querySelectorAll('.slot').forEach((el) => el.classList.remove('is-on'));
                    btn.classList.add('is-on');
                    starts.value = iso;
                });
                slotsEl.appendChild(btn);
            });
        } catch (e) {
            slotsEl.innerHTML = '<span class="muted">Could not load times. Try again.</span>';
        }
    }

    form?.addEventListener('submit', (e) => {
        if (!starts.value) {
            e.preventDefault();
            alert('Please select an available time.');
        }
    });

    service.addEventListener('change', loadSlots);
    date.addEventListener('change', loadSlots);
    if (service.value && date.value) loadSlots();
})();
</script>
@endunless
</body>
</html>
