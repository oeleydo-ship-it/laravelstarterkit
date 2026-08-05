/**
 * Shared Echo / Reverb options for the agent app and the public widget.
 * Prefers runtime config from the server (window.ChatRealtime) so production
 * does not depend on VITE_* values baked in at an earlier build.
 */
export function reverbEchoOptions(overrides = {}) {
    const cfg = window.ChatRealtime || {};
    const scheme = cfg.scheme
        || import.meta.env.VITE_REVERB_SCHEME
        || (window.location.protocol === 'https:' ? 'https' : 'http');
    const forceTLS = scheme === 'https';
    const defaultPort = forceTLS ? 443 : 80;
    const port = Number(
        cfg.port
        || import.meta.env.VITE_REVERB_PORT
        || defaultPort,
    );
    const host = cfg.host
        || import.meta.env.VITE_REVERB_HOST
        || window.location.hostname;

    return {
        broadcaster: 'reverb',
        key: cfg.key || import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        ...overrides,
    };
}
