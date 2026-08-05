<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chat\Ai\AiProvider;

/**
 * Drafts a reply for an agent, grounded in the workspace's own knowledge base.
 * The suggestion is never sent anywhere — it lands in the composer for a human
 * to read, edit and choose to send. Also used for visitor auto-replies.
 */
class AiAssistService
{
    public function __construct(
        protected KnowledgeBaseService $knowledgeBase,
        protected AiSettingsService $aiSettings,
    ) {
    }

    public function isAvailable(?\App\Models\Tenant $tenant = null): bool
    {
        return $this->providerFor($tenant)->isConfigured();
    }

    public function suggestReply(ChatConversation $conversation, ?AiProvider $provider = null): string
    {
        $conversation->loadMissing('tenant');
        $provider ??= $this->providerFor($conversation->tenant);

        $transcript = $conversation->messages()
            // Internal notes are excluded: they are staff shorthand, and a draft
            // built from them reads back to the visitor as leaked backchannel.
            ->where('is_internal', false)
            ->latest('id')
            ->limit(config('chat.ai.history_limit'))
            ->get()
            ->reverse()
            ->values();

        $lastVisitorMessage = $transcript->last(fn (ChatMessage $message) => $message->isFromVisitor());
        $snippets = $this->knowledgeBase->relevantSnippets(
            $lastVisitorMessage?->body,
            config('chat.ai.article_limit')
        );

        return $provider->complete(
            $this->systemPrompt($conversation, $snippets),
            $this->asTurns($transcript),
        );
    }

    protected function providerFor(?\App\Models\Tenant $tenant = null): AiProvider
    {
        return $this->aiSettings->makeProvider($tenant ?? currentTenant());
    }

    protected function systemPrompt(ChatConversation $conversation, $snippets): string
    {
        $workspace = $conversation->tenant?->name ?? 'this company';

        $prompt = "You are a helpful customer support assistant for {$workspace}. "
            ."Reply to the visitor's latest message in a short, friendly way. "
            ."Write only the reply text — no greeting boilerplate, no subject line, no commentary. "
            ."If you are unsure, say an agent can help further.\n\n";

        if ($snippets->isEmpty()) {
            return $prompt."There is little or no knowledge base context for this question. "
                ."Answer helpfully if you can from the conversation, otherwise invite them to wait for a live agent.";
        }

        $prompt .= "Ground your answer in these knowledge base articles and documents when relevant. "
            ."If they do not cover the question, say a live agent can take over rather than inventing facts.\n\n";

        foreach ($snippets as $snippet) {
            $prompt .= "--- {$snippet->title} ---\n{$snippet->body}\n\n";
        }

        return $prompt;
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    protected function asTurns($transcript): array
    {
        $turns = $transcript
            ->map(fn (ChatMessage $message) => [
                'role' => $message->isFromVisitor() ? 'user' : 'assistant',
                'content' => $message->body,
            ])
            ->values()
            ->all();

        // Providers require the exchange to open on a user turn, which is not
        // guaranteed when an agent greeted first.
        while ($turns && $turns[0]['role'] !== 'user') {
            array_shift($turns);
        }

        return $turns ?: [['role' => 'user', 'content' => 'Hello?']];
    }
}
