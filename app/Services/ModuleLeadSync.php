<?php

namespace App\Services;

use App\Models\Client;
use App\Models\EmailList;
use App\Models\EmailSubscriber;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Shared CRM + Email list sync used by Forms, Reviews, and Bookings.
 */
class ModuleLeadSync
{
    public function sync(
        Tenant $tenant,
        ?string $email,
        ?string $name,
        string $source,
        string $listName,
    ): ?int {
        $email = $email ? Str::lower(trim($email)) : null;
        $name = $name ? trim($name) : null;

        if (! $email) {
            return null;
        }

        $clientId = null;

        if ($tenant->isModuleEnabled('clients')) {
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
                    'source' => $source,
                ]);
            }

            $clientId = $client->id;
        }

        if ($tenant->isModuleEnabled('email')) {
            $this->syncEmailSubscriber($tenant, $email, $name, $listName);
        }

        return $clientId;
    }

    protected function syncEmailSubscriber(Tenant $tenant, string $email, ?string $name, string $listName): void
    {
        $list = EmailList::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('name', $listName)
            ->first();

        if (! $list) {
            $list = EmailList::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->first();
        }

        if (! $list) {
            $list = EmailList::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => $listName,
                'description' => 'Auto-created from '.$listName,
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
