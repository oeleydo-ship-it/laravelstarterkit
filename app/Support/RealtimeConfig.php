<?php

namespace App\Support;

class RealtimeConfig
{
    /**
     * Browser-facing Echo / Reverb settings (public host, usually :443 + TLS).
     */
    public static function forBrowser(): array
    {
        $client = config('broadcasting.connections.reverb.client', []);
        $appUrl = (string) config('app.url');
        $appHost = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        $appScheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';

        $host = (string) ($client['host'] ?? $appHost);
        $scheme = (string) ($client['scheme'] ?? $appScheme);

        // Never tell production browsers to open websockets on localhost.
        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true) && ! app()->environment('local')) {
            $host = $appHost;
            $scheme = $appScheme === 'http' ? 'http' : 'https';
        }

        $defaultPort = $scheme === 'https' ? 443 : 80;
        $port = (int) ($client['port'] ?? $defaultPort);

        return [
            'key' => (string) config('broadcasting.connections.reverb.key'),
            'host' => $host,
            'port' => $port > 0 ? $port : $defaultPort,
            'scheme' => $scheme,
        ];
    }
}
