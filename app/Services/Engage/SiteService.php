<?php

namespace App\Services\Engage;

use App\Models\EngageCampaign;
use App\Models\EngageSite;
use App\Models\Tenant;

class SiteService
{
    public function defaultFor(Tenant $tenant): EngageSite
    {
        $site = EngageSite::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->first();

        if ($site) {
            return $site;
        }

        return EngageSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => EngageSite::generatePublicKey(),
            'name' => 'Website',
            'allowed_origins' => [],
            'settings' => ['brand_color' => '#2563eb'],
        ]);
    }

    public function rotateKey(EngageSite $site): EngageSite
    {
        $site->update(['public_key' => EngageSite::generatePublicKey()]);

        return $site->fresh();
    }

    public function saveSettings(EngageSite $site, array $data): EngageSite
    {
        $origins = collect(preg_split('/\r\n|\r|\n/', (string) ($data['allowed_origins'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $settings = $site->settings ?? [];
        if (! empty($data['brand_color'])) {
            $settings['brand_color'] = $data['brand_color'];
        }

        $site->update([
            'name' => $data['name'] ?? $site->name,
            'allowed_origins' => $origins,
            'settings' => $settings,
        ]);

        return $site->fresh();
    }

    public function embedSnippet(EngageSite $site): string
    {
        $url = url('/x/'.$site->public_key.'.js');

        return '<script src="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" async></script>';
    }
}
