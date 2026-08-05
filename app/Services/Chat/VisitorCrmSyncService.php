<?php

namespace App\Services\Chat;

use App\Models\ChatVisitor;
use App\Models\Client;
use App\Models\ClientNote;
use App\Models\User;

/**
 * Pushes the live-chat CRM sidebar into the workspace Clients (CRM) module so
 * agents do not re-enter the same lead twice.
 */
class VisitorCrmSyncService
{
    public function sync(ChatVisitor $visitor, array $data, ?User $actor = null): Client
    {
        $payload = [
            'name' => filled($data['name'] ?? null) ? $data['name'] : ($visitor->name ?: 'Chat visitor #'.$visitor->id),
            'email' => $data['email'] ?? $visitor->email,
            'phone' => $data['phone'] ?? $visitor->phone,
            'company' => $data['company'] ?? $visitor->company,
            'city' => $data['city'] ?? $visitor->city,
            'country' => $data['country'] ?? $visitor->country,
            'notes' => $data['crm_notes'] ?? $visitor->crm_notes,
        ];

        $client = $this->resolveClient($visitor, $payload['email'] ?? null);

        if ($client) {
            $client->fill(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
            if (! filled($client->source)) {
                $client->source = 'Live Chat';
            }
            $client->save();
        } else {
            $client = Client::create([
                'tenant_id' => $visitor->tenant_id,
                'name' => $payload['name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'company' => $payload['company'],
                'city' => $payload['city'],
                'country' => $payload['country'],
                'notes' => $payload['notes'],
                'status' => Client::STATUS_LEAD,
                'source' => 'Live Chat',
                'tags' => ['live-chat'],
            ]);
        }

        $visitor->forceFill(['client_id' => $client->id])->save();

        $noteBody = trim((string) ($payload['notes'] ?? ''));
        if ($noteBody !== '' && $actor) {
            $alreadyLogged = ClientNote::query()
                ->where('client_id', $client->id)
                ->where('body', $noteBody)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->exists();

            if (! $alreadyLogged) {
                ClientNote::create([
                    'tenant_id' => $visitor->tenant_id,
                    'client_id' => $client->id,
                    'user_id' => $actor->id,
                    'body' => $noteBody,
                ]);
            }
        }

        return $client;
    }

    protected function resolveClient(ChatVisitor $visitor, ?string $email): ?Client
    {
        if ($visitor->client_id) {
            $linked = Client::withoutGlobalScopes()
                ->where('tenant_id', $visitor->tenant_id)
                ->find($visitor->client_id);

            if ($linked) {
                return $linked;
            }
        }

        if (filled($email)) {
            return Client::withoutGlobalScopes()
                ->where('tenant_id', $visitor->tenant_id)
                ->where('email', $email)
                ->first();
        }

        return null;
    }
}
