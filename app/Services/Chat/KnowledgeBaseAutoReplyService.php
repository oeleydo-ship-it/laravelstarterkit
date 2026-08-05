<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Answers visitors with AI (or a clean KB fallback) while no human agent has
 * accepted the conversation.
 */
class KnowledgeBaseAutoReplyService
{
    public function __construct(
        protected KnowledgeBaseService $knowledgeBase,
        protected AiAssistService $assist,
        protected AiSettingsService $aiSettings,
        protected MessageService $messages,
    ) {
    }

    public function maybeReply(ChatConversation $conversation, ChatMessage $visitorMessage): ?ChatMessage
    {
        $conversation->loadMissing('tenant');
        $tenant = $conversation->tenant;

        if (! $tenant || ! $this->knowledgeBase->autoReplyEnabled($tenant)) {
            return null;
        }

        // Once a human accepts, stay out of the way.
        if ($conversation->assigned_to !== null) {
            return null;
        }

        if ($conversation->status !== 'open' || ! $visitorMessage->isFromVisitor()) {
            return null;
        }

        $snippets = $this->knowledgeBase->relevantSnippets(
            $visitorMessage->body,
            config('chat.ai.article_limit', 4)
        );

        $provider = $this->aiSettings->makeProvider($tenant);

        // Prefer a real model reply whenever the workspace has AI configured.
        if ($provider->isConfigured()) {
            try {
                $body = $this->assist->suggestReply($conversation, $provider);

                if (filled($body)) {
                    return $this->messages->sendAsBot($conversation, $body);
                }
            } catch (Throwable $e) {
                Log::warning('Knowledge base AI auto-reply failed', [
                    'conversation_id' => $conversation->id,
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // No AI (or it failed): only send a readable excerpt — never binary PDF junk.
        if ($snippets->isEmpty()) {
            return $this->messages->sendAsBot(
                $conversation,
                'Thanks for your message! A live agent will be with you shortly.'
            );
        }

        return $this->messages->sendAsBot($conversation, $this->fallbackReply($snippets));
    }

    protected function fallbackReply($snippets): string
    {
        $top = $snippets->first();
        $excerpt = $this->knowledgeBase->sanitizeExcerpt((string) $top->body, 500);

        if ($excerpt === '') {
            return 'Thanks for your message! A live agent will be with you shortly.';
        }

        $reply = "Here's what I found that may help:\n\n";
        $reply .= "{$top->title}\n{$excerpt}";
        $reply .= "\n\nAn agent can take over anytime if you need more help.";

        return $reply;
    }
}
