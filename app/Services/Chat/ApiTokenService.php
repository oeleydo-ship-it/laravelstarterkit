<?php

namespace App\Services\Chat;

use App\Models\ChatApiToken;
use App\Models\Tenant;
use Illuminate\Support\Str;

class ApiTokenService
{
    /**
     * SHA-256 rather than bcrypt: lookup has to be a single indexed query on
     * every API request, and the token is 40 random characters we generated —
     * there is no low-entropy secret here for a slow hash to protect.
     */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @return array{0: ChatApiToken, 1: string} The record and the plaintext
     *                                           token, which is shown once.
     */
    public function issue(Tenant $tenant, string $name): array
    {
        $plain = 'chat_'.Str::random(40);

        $token = ChatApiToken::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'token_hash' => self::hash($plain),
        ]);

        return [$token, $plain];
    }

    public function resolve(?string $plain): ?ChatApiToken
    {
        if (blank($plain)) {
            return null;
        }

        // withoutGlobalScopes: this runs before any tenant is bound — resolving
        // the token is what decides which tenant the request belongs to.
        return ChatApiToken::withoutGlobalScopes()
            ->where('token_hash', self::hash($plain))
            ->first();
    }
}
