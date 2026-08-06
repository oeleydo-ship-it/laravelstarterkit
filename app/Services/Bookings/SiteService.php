<?php

namespace App\Services\Bookings;

use App\Models\BookingSite;
use App\Models\Tenant;

class SiteService
{
    public function defaultFor(Tenant $tenant): BookingSite
    {
        $site = BookingSite::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->first();

        if ($site) {
            return $site;
        }

        return BookingSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => BookingSite::generatePublicKey(),
            'name' => 'Bookings',
            'timezone' => config('app.timezone', 'UTC'),
            'allowed_origins' => [],
            'settings' => ['brand_color' => '#0f766e'],
        ]);
    }

    public function rotateKey(BookingSite $site): BookingSite
    {
        $site->update(['public_key' => BookingSite::generatePublicKey()]);

        return $site->fresh();
    }

    public function saveSettings(BookingSite $site, array $data): BookingSite
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
            'timezone' => $data['timezone'] ?? $site->timezone,
            'allowed_origins' => $origins,
            'settings' => $settings,
        ]);

        return $site->fresh();
    }

    public function publicUrl(BookingSite $site): string
    {
        return url('/b/'.$site->public_key);
    }

    public function embedSnippet(BookingSite $site): string
    {
        $url = htmlspecialchars($this->publicUrl($site), ENT_QUOTES, 'UTF-8');

        return '<a href="'.$url.'">Book a time</a>';
    }
}
