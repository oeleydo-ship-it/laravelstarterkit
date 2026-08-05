<?php

namespace App\Services\Engage;

use App\Models\Client;
use App\Models\EmailList;
use App\Models\EmailSubscriber;
use App\Models\EngageCampaign;
use App\Models\EngageEvent;
use App\Models\EngageLead;
use App\Models\Tenant;
use Illuminate\Support\Str;

class LeadService
{
    public function capture(
        Tenant $tenant,
        EngageCampaign $campaign,
        array $data,
        ?string $pageUrl = null,
    ): EngageLead {
        $email = isset($data['email']) ? Str::lower(trim((string) $data['email'])) : null;
        $name = isset($data['name']) ? trim((string) $data['name']) : null;

        $payload = collect($data)
            ->except(['email', 'name', 'hp', '_hp', 'website'])
            ->all();

        $clientId = null;

        if ($email && $tenant->isModuleEnabled('clients')) {
            $client = Client::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('email', $email)
                ->first();

            if (! $client) {
                $client = Client::withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->id,
                    'name' => $name ?: Str::before($email, '@'),
                    'email' => $email,
                    'status' => Client::STATUS_LEAD,
                    'source' => 'engage',
                ]);
            }

            $clientId = $client->id;
        }

        if ($email && $tenant->isModuleEnabled('email')) {
            $this->syncEmailSubscriber($tenant, $email, $name);
        }

        $lead = EngageLead::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'email' => $email,
            'name' => $name,
            'payload' => $payload,
            'page_url' => $pageUrl ? Str::limit($pageUrl, 2000, '') : null,
            'client_id' => $clientId,
        ]);

        EngageEvent::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'type' => EngageEvent::TYPE_SUBMIT,
            'path' => $pageUrl ? Str::limit(parse_url($pageUrl, PHP_URL_PATH) ?: $pageUrl, 2000, '') : null,
            'meta' => ['lead_id' => $lead->id],
            'created_at' => now(),
        ]);

        return $lead;
    }

    protected function syncEmailSubscriber(Tenant $tenant, string $email, ?string $name): void
    {
        $list = EmailList::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->first();

        if (! $list) {
            $list = EmailList::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => 'Engage Leads',
                'description' => 'Auto-created from on-site forms',
            ]);
        }

        $subscriber = EmailSubscriber::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->first();

        if (! $subscriber) {
            $subscriber = EmailSubscriber::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'email' => $email,
                'first_name' => $name ? Str::before($name, ' ') : null,
                'last_name' => $name && str_contains($name, ' ') ? Str::after($name, ' ') : null,
                'status' => EmailSubscriber::STATUS_SUBSCRIBED,
                'subscribed_at' => now(),
            ]);
        }

        $list->subscribers()->syncWithoutDetaching([
            $subscriber->id => [
                'status' => 'subscribed',
                'subscribed_at' => now(),
            ],
        ]);
    }
}
