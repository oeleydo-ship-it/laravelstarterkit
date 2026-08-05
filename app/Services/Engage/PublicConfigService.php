<?php

namespace App\Services\Engage;

use App\Models\EngageCampaign;
use App\Models\EngageEvent;
use App\Models\EngageSite;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicConfigService
{
    public function campaignsFor(EngageSite $site, Tenant $tenant): Collection
    {
        return EngageCampaign::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('engage_site_id', $site->id)
            ->where('status', EngageCampaign::STATUS_LIVE)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get()
            ->map(fn (EngageCampaign $campaign) => $campaign->toPublicPayload())
            ->values();
    }

    public function recordEvent(
        Tenant $tenant,
        EngageCampaign $campaign,
        string $type,
        ?string $path = null,
        ?array $meta = null,
    ): void {
        if (! in_array($type, [
            EngageEvent::TYPE_IMPRESSION,
            EngageEvent::TYPE_CLICK,
            EngageEvent::TYPE_DISMISS,
            EngageEvent::TYPE_SUBMIT,
        ], true)) {
            return;
        }

        EngageEvent::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'type' => $type,
            'path' => $path ? Str::limit($path, 2000, '') : null,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
