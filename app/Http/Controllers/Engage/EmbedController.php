<?php

namespace App\Http\Controllers\Engage;

use App\Http\Controllers\Controller;
use App\Models\EngageCampaign;
use App\Models\EngageSite;
use App\Models\Tenant;
use App\Services\Engage\LeadService;
use App\Services\Engage\PublicAssetService;
use App\Services\Engage\PublicConfigService;
use Illuminate\Http\Request;

class EmbedController extends Controller
{
    public function __construct(
        protected PublicConfigService $config,
        protected PublicAssetService $assets,
        protected LeadService $leads,
    ) {
    }

    protected function site(Request $request): EngageSite
    {
        return $request->attributes->get('engage_site');
    }

    protected function tenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant');
    }

    protected function assertOrigin(Request $request, EngageSite $site): void
    {
        $origin = $request->headers->get('Origin')
            ?: ($request->headers->get('Referer') ? (parse_url($request->headers->get('Referer'), PHP_URL_SCHEME).'://'.parse_url($request->headers->get('Referer'), PHP_URL_HOST)) : null);

        abort_unless($site->allowsOrigin($origin), 403);
    }

    /**
     * Boot loader at /x/{siteKey}.js — only opaque paths, injects CSS+JS inline
     * so the host page never references /build or product filenames.
     */
    public function boot(Request $request, string $siteKey)
    {
        $site = $this->site($request);
        $this->assertOrigin($request, $site);

        $base = rtrim(url('/x/'.$site->public_key), '/');
        $css = $this->assets->stylesheet();
        $js = $this->assets->javascript();

        $payload = 'window.__X='.json_encode([
            'k' => $site->public_key,
            'b' => $base,
            'c' => $site->brandColor(),
        ], JSON_UNESCAPED_SLASHES).';';

        if ($css !== '') {
            $payload .= '(function(){var s=document.createElement("style");s.textContent='.json_encode($css, JSON_UNESCAPED_SLASHES).';document.head.appendChild(s);})();';
        }

        $payload .= "\n".$js;

        return response($payload, 200, [
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

        return response()->json([
            'c' => $site->brandColor(),
            'i' => $this->config->campaignsFor($site, $tenant),
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function event(Request $request, string $siteKey)
    {
        $site = $this->site($request);
        $tenant = $this->tenant($request);
        $this->assertOrigin($request, $site);

        $validated = $request->validate([
            'i' => ['required', 'integer'],
            't' => ['required', 'string', 'max:32'],
            'p' => ['nullable', 'string', 'max:2000'],
        ]);

        $campaign = EngageCampaign::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('engage_site_id', $site->id)
            ->where('id', $validated['i'])
            ->firstOrFail();

        $this->config->recordEvent(
            $tenant,
            $campaign,
            $validated['t'],
            $validated['p'] ?? null,
        );

        return response()->noContent();
    }

    public function lead(Request $request, string $siteKey)
    {
        $site = $this->site($request);
        $tenant = $this->tenant($request);
        $this->assertOrigin($request, $site);

        // Honeypot — bots fill "website"; humans leave it empty.
        if (filled($request->input('website')) || filled($request->input('hp'))) {
            return response()->json(['ok' => true]);
        }

        $validated = $request->validate([
            'i' => ['required', 'integer'],
            'email' => ['nullable', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'page_url' => ['nullable', 'string', 'max:2000'],
            'payload' => ['nullable', 'array'],
        ]);

        abort_if(blank($validated['email'] ?? null) && blank($validated['name'] ?? null), 422);

        $campaign = EngageCampaign::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('engage_site_id', $site->id)
            ->where('id', $validated['i'])
            ->where('status', EngageCampaign::STATUS_LIVE)
            ->firstOrFail();

        $data = array_merge($validated['payload'] ?? [], [
            'email' => $validated['email'] ?? null,
            'name' => $validated['name'] ?? null,
        ]);

        $this->leads->capture($tenant, $campaign, $data, $validated['page_url'] ?? null);

        return response()->json(['ok' => true], 201, [
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    public function preflight(Request $request)
    {
        return response('', 204, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Access-Control-Max-Age' => '86400',
        ]);
    }
}
