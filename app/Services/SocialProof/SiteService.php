<?php

namespace App\Services\SocialProof;

use App\Models\SocialProofSite;
use App\Models\Tenant;

class SiteService
{
    public function defaultFor(Tenant $tenant): SocialProofSite
    {
        return SocialProofSite::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'public_key' => SocialProofSite::generatePublicKey(),
                'name' => 'Website',
                'allowed_origins' => [],
                'settings' => SocialProofSite::defaultSettings(),
            ],
        );
    }

    public function saveSettings(SocialProofSite $site, array $data): SocialProofSite
    {
        $origins = collect(preg_split('/\r\n|\r|\n/', (string) ($data['allowed_origins'] ?? '')))
            ->map(fn ($origin) => trim($origin))
            ->filter()
            ->values()
            ->all();

        $settings = array_merge($site->resolvedSettings(), [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'position' => $data['position'] ?? 'bottom-left',
            'initial_delay_ms' => (int) ($data['initial_delay_ms'] ?? 4000),
            'display_duration_ms' => (int) ($data['display_duration_ms'] ?? 5000),
            'interval_ms' => (int) ($data['interval_ms'] ?? 9000),
            'max_displays' => (int) ($data['max_displays'] ?? 5),
            'max_per_page' => (int) ($data['max_per_page'] ?? 4),
            'include_fake' => (bool) ($data['include_fake'] ?? false),
            'include_live_subscribers' => (bool) ($data['include_live_subscribers'] ?? false),
            'include_live_bookings' => (bool) ($data['include_live_bookings'] ?? false),
            'include_api' => (bool) ($data['include_api'] ?? false),
            'accent_color' => $data['accent_color'] ?? '#0f766e',
            'purchase_verb' => $data['purchase_verb'] ?? 'purchased',
            'subscribe_verb' => $data['subscribe_verb'] ?? 'subscribed to',
        ]);

        $site->update([
            'name' => $data['name'],
            'allowed_origins' => $origins,
            'settings' => $settings,
        ]);

        return $site->fresh();
    }

    public function embedSnippet(SocialProofSite $site): string
    {
        return '<script src="'.htmlspecialchars(url('/sp/'.$site->public_key.'.js'), ENT_QUOTES, 'UTF-8').'" async></script>';
    }

    public function rotateKey(SocialProofSite $site): SocialProofSite
    {
        $site->update(['public_key' => SocialProofSite::generatePublicKey()]);

        return $site->fresh();
    }
}
