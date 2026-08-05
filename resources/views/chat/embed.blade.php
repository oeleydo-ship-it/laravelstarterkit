{{-- Rendered as JavaScript, not HTML. See WidgetController@embedScript. --}}
@php
    // Blade's default @json flags escape forward slashes, which turns every URL
    // in this customer-facing snippet into "http:\/\/…". Keep the HEX escapes
    // that make embedding in <script> safe, drop only the slash escaping.
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES;
@endphp
(function () {
    var WIDGET_URL = @json($widgetUrl, $jsonFlags);
    var ORIGIN = @json($origin, $jsonFlags);
    // Guard against stale APP_URL/port mismatch: use the widget URL's real
    // origin as the primary check, but fall back to the server-provided ORIGIN
    // so existing tests and behavior remain compatible.
    var WIDGET_ORIGIN = ORIGIN;
    try {
        WIDGET_ORIGIN = new URL(WIDGET_URL, window.location.href).origin;
    } catch (e) {}

    // Guard against the snippet being pasted twice (a common copy/paste result
    // on sites that include a shared footer on every template).
    if (window.__chatWidgetLoaded) return;
    window.__chatWidgetLoaded = true;

    var CLOSED = { width: '96px', height: '96px' };
    var OPEN = { width: '400px', height: '680px' };
    var EXPANDED = { width: '480px', height: '800px' };

    function mount() {
        var frame = document.createElement('iframe');

        frame.src = WIDGET_URL
            + (WIDGET_URL.indexOf('?') === -1 ? '?' : '&')
            + 'page_url=' + encodeURIComponent(window.location.href)
            + '&page_title=' + encodeURIComponent(document.title || '');
        frame.title = 'Live chat';
        frame.setAttribute('allowtransparency', 'true');
        frame.style.cssText = [
            'position:fixed',
            'right:0',
            'bottom:0',
            'border:0',
            'z-index:2147483000',
            'color-scheme:normal',
            'background:transparent',
            'max-width:100vw',
            'max-height:100vh',
            'transition:width .15s ease, height .15s ease',
            'width:' + CLOSED.width,
            'height:' + CLOSED.height,
        ].join(';');

        document.body.appendChild(frame);

        // The iframe is cross-origin, so the widget cannot resize its own frame.
        // It asks the host page to, and the host only listens to its own origin.
        window.addEventListener('message', function (event) {
            if (event.origin !== WIDGET_ORIGIN && event.origin !== ORIGIN) return;
            if (!event.data || event.data.type !== 'chat-widget-resize') return;

            var size = !event.data.open
                ? CLOSED
                : (event.data.expanded ? EXPANDED : OPEN);
            frame.style.width = size.width;
            frame.style.height = size.height;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();
