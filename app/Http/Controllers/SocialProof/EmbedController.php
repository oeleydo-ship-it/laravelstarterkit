<?php

namespace App\Http\Controllers\SocialProof;

use App\Http\Controllers\Controller;
use App\Models\SocialProofEvent;
use App\Models\SocialProofSite;
use App\Models\Tenant;
use App\Services\SocialProof\FeedService;
use App\Services\SocialProof\PublicAssetService;
use Illuminate\Http\Request;

class EmbedController extends Controller
{
    public function __construct(
        protected PublicAssetService $assets,
        protected FeedService $feed,
    ) {}

    protected function site(Request $request): SocialProofSite
    {
        return $request->attributes->get('social_proof_site');
    }

    protected function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }

    protected function assertOrigin(Request $request, SocialProofSite $site): void
    {
        $origin = $request->headers->get('Origin');
        if (! $origin && $request->headers->get('Referer')) {
            $scheme = parse_url($request->headers->get('Referer'), PHP_URL_SCHEME);
            $host = parse_url($request->headers->get('Referer'), PHP_URL_HOST);
            $origin = ($scheme && $host) ? $scheme.'://'.$host : null;
        }

        abort_unless($site->allowsOrigin($origin), 403);
    }

    public function boot(Request $request, string $siteKey)
    {
        $site = $this->site($request);
        $this->assertOrigin($request, $site);

        $payload = 'window.__SP='.json_encode([
            'k' => $site->public_key,
            'b' => rtrim(url('/sp/'.$site->public_key), '/'),
        ], JSON_UNESCAPED_SLASHES).';';

        if ($css = $this->assets->stylesheet()) {
            $payload .= '(function(){var s=document.createElement("style");s.textContent='.json_encode($css, JSON_UNESCAPED_SLASHES).';document.head.appendChild(s);})();';
        }

        return response($payload."\n".$this->assets->javascript(), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=120',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function config(Request $request, string $siteKey)
    {
        $site = $this->site($request);
        $tenant = $this->tenant($request);
        $this->assertOrigin($request, $site);

        $settings = $site->resolvedSettings();

        if (! ($settings['enabled'] ?? true)) {
            return response()->json(['ok' => true, 'e' => false, 'i' => []], 200, [
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'no-store',
            ]);
        }

        return response()->json([
            'ok' => true,
            'e' => true,
            'g' => [
                'position' => $settings['position'],
                'initial_delay_ms' => (int) $settings['initial_delay_ms'],
                'display_duration_ms' => (int) $settings['display_duration_ms'],
                'interval_ms' => (int) $settings['interval_ms'],
                'max_displays' => (int) $settings['max_displays'],
                'max_per_page' => (int) $settings['max_per_page'],
                'accent_color' => $settings['accent_color'],
            ],
            'i' => $this->feed->forSite($tenant, $site),
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function ingest(Request $request, string $siteKey)
    {
        $site = $this->site($request);
        $tenant = $this->tenant($request);
        $this->assertOrigin($request, $site);

        if (filled($request->input('website')) || filled($request->input('hp'))) {
            return response()->json(['ok' => true]);
        }

        $data = $request->validate([
            'type' => ['required', 'in:purchase,subscribe'],
            'customer_name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'item_name' => ['required', 'string', 'max:190'],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
            'product_url' => ['nullable', 'url', 'max:2048'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        SocialProofEvent::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'social_proof_site_id' => $site->id,
            'type' => $data['type'],
            'source' => SocialProofEvent::SOURCE_API,
            'customer_name' => $data['customer_name'],
            'location' => $data['location'] ?? null,
            'item_name' => $data['item_name'],
            'avatar_url' => $data['avatar_url'] ?? null,
            'product_url' => $data['product_url'] ?? null,
            'is_active' => true,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        return response()->json(['ok' => true], 201, [
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function preflight()
    {
        return response('', 204, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Access-Control-Max-Age' => '86400',
        ]);
    }
}
