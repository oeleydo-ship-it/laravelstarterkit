<?php

namespace App\Services\Reviews;

use App\Models\ReviewSite;
use App\Models\Tenant;

class SiteService
{
    public function defaultFor(Tenant $tenant): ReviewSite
    {
        return ReviewSite::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['public_key' => ReviewSite::generatePublicKey(), 'name' => 'Website', 'allowed_origins' => [], 'settings' => []],
        );
    }

    public function saveSettings(ReviewSite $site, array $data): ReviewSite
    {
        $origins = collect(preg_split('/\r\n|\r|\n/', (string) ($data['allowed_origins'] ?? '')))
            ->map(fn ($origin) => trim($origin))->filter()->values()->all();
        $site->update(['name' => $data['name'], 'allowed_origins' => $origins]);
        return $site->fresh();
    }

    public function embedSnippet(ReviewSite $site): string
    {
        return '<script src="'.htmlspecialchars(url('/r/'.$site->public_key.'.js'), ENT_QUOTES, 'UTF-8').'" async></script>';
    }
}
