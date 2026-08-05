<?php

namespace App\Services\Chat\Ai;

use RuntimeException;

/**
 * The default. AI assist stays entirely inert — and costs nothing — until a
 * workspace deliberately configures a real provider.
 */
class NullAiProvider implements AiProvider
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function complete(string $system, array $messages): string
    {
        throw new RuntimeException('No AI provider is configured for chat assist.');
    }
}
