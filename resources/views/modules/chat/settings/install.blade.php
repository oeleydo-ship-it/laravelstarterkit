<div class="card stat-card">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1">Install the widget</h5>
        <p class="text-muted small">
            Paste this immediately before the closing <code>&lt;/body&gt;</code> tag on every page of your
            site. It loads the chat launcher in the bottom-right corner.
        </p>

        <div class="mb-2">
            <label for="embed-snippet" class="form-label fw-medium small mb-1">Embed snippet</label>
            <textarea id="embed-snippet" class="form-control font-monospace small" rows="2"
                      readonly onclick="this.select();">{{ $embedSnippet }}</textarea>
        </div>

        <button type="button" class="btn btn-sm btn-primary" id="copy-embed-snippet">Copy snippet</button>
        <span class="text-success small ms-2" id="copy-embed-feedback" style="display:none;">Copied.</span>

        <hr class="my-4">

        <h6 class="fw-bold mb-1">Direct link</h6>
        <p class="text-muted small mb-2">
            The widget as its own page — useful for testing, or to link to from an email signature.
        </p>
        <a href="{{ $widgetUrl }}" target="_blank" rel="noopener" class="small">{{ $widgetUrl }}</a>

        <hr class="my-4">

        <h6 class="fw-bold mb-1">Notes</h6>
        <ul class="text-muted small mb-0">
            <li>The snippet is safe to include on every page — it will not load twice.</li>
            <li>Appearance and business hours apply automatically; there is nothing to change here after editing them.</li>
            <li>Turning the Live Chat module off hides the widget everywhere it is embedded.</li>
            <li>The widget page must allow framing. If the launcher stays blank on an external site, remove any server-level <code>X-Frame-Options: SAMEORIGIN</code> rule for <code>/widget/*</code>.</li>
        </ul>
    </div>
</div>

<script>
    document.getElementById('copy-embed-snippet')?.addEventListener('click', function () {
        var field = document.getElementById('embed-snippet');
        var feedback = document.getElementById('copy-embed-feedback');

        // Selecting first means the fallback path still works if the async
        // clipboard API is unavailable (it needs a secure context).
        field.select();

        var done = function () {
            feedback.style.display = 'inline';
            setTimeout(function () { feedback.style.display = 'none'; }, 2000);
        };

        if (navigator.clipboard) {
            navigator.clipboard.writeText(field.value).then(done, function () { document.execCommand('copy'); done(); });
        } else {
            document.execCommand('copy');
            done();
        }
    });
</script>
