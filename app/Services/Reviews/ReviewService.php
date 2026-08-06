<?php

namespace App\Services\Reviews;

use App\Models\Review;
use App\Models\ReviewSite;
use App\Models\Tenant;
use App\Services\ModuleLeadSync;

class ReviewService
{
    public function __construct(protected ModuleLeadSync $leadSync) {}

    public function submit(Tenant $tenant, ReviewSite $site, array $data): Review
    {
        $clientId = $this->leadSync->sync($tenant, $data['email'] ?? null, $data['author_name'], 'reviews', 'Review Contacts');
        return Review::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'review_site_id' => $site->id,
            'rating' => $data['rating'], 'body' => $data['body'], 'author_name' => $data['author_name'],
            'author_company' => $data['author_company'] ?? null, 'author_avatar' => $data['author_avatar'] ?? null,
            'source' => $data['source'] ?? 'public', 'status' => Review::STATUS_PENDING, 'client_id' => $clientId,
        ]);
    }
}
