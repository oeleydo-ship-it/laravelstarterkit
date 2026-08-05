<?php

namespace App\Services\Chat\Ai;

/**
 * The whole surface the chat module needs from an AI vendor. Keeping it this
 * small is the point — swapping vendors means one new class implementing two
 * methods, and nothing in the module knows which one is in play.
 */
interface AiProvider
{
    /**
     * Whether this provider has everything it needs (credentials, model) to be
     * called. The UI hides the assist controls when it does not.
     */
    public function isConfigured(): bool;

    /**
     * @param  string  $system  Instructions and grounding context.
     * @param  array<int, array{role: string, content: string}>  $messages  Conversation turns, oldest first.
     */
    public function complete(string $system, array $messages): string;
}
