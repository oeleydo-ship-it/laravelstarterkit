<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\Ticket;
use App\Services\Chat\AiSettingsService;
use RuntimeException;

class TicketFromConversationService
{
    public function __construct(
        protected TicketSettingsService $settings,
        protected AiSettingsService $aiSettings,
    ) {}

    public function createByAgent(ChatConversation $conversation, array $overrides = []): Ticket
    {
        $conversation->loadMissing(['visitor', 'tenant', 'assignee']);
        $defaults = $this->settings->for($conversation->tenant);

        return Ticket::create(array_merge([
            'tenant_id' => $conversation->tenant_id,
            'chat_conversation_id' => $conversation->id,
            'title' => 'Support request from '.($conversation->visitor?->name ?: 'chat visitor'),
            'description' => $this->transcript($conversation),
            'requester_name' => $conversation->visitor?->name,
            'requester_email' => $conversation->visitor?->email,
            'priority' => $defaults['default_priority'],
            'status' => 'open',
            'assigned_to' => $conversation->assigned_to ?: $defaults['default_assignee_id'],
            'source' => 'live_chat',
        ], $overrides));
    }

    public function createByAi(ChatConversation $conversation): Ticket
    {
        $conversation->loadMissing('tenant');
        $settings = $this->settings->for($conversation->tenant);
        abort_unless($settings['ai_creation_enabled'], 403, 'AI ticket creation is disabled in Ticket Settings.');

        $provider = $this->aiSettings->makeProvider($conversation->tenant);
        if (! $provider->isConfigured()) {
            throw new RuntimeException('AI is not configured for this workspace.');
        }

        $result = $provider->complete(
            'Convert this support chat into a ticket. Return only valid JSON with keys title, description, priority, category. Priority must be low, medium, high, or urgent. Be concise and factual.',
            [['role' => 'user', 'content' => $this->transcript($conversation)]],
        );
        $result = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($result));
        $data = json_decode($result, true);

        if (! is_array($data) || blank($data['title'] ?? null)) {
            throw new RuntimeException('AI returned an invalid ticket.');
        }

        $priority = in_array($data['priority'] ?? null, ['low', 'medium', 'high', 'urgent'], true)
            ? $data['priority'] : $settings['default_priority'];

        return $this->createByAgent($conversation, [
            'title' => mb_substr($data['title'], 0, 255),
            'description' => mb_substr((string) ($data['description'] ?? $this->transcript($conversation)), 0, 10000),
            'priority' => $priority,
            'category' => mb_substr((string) ($data['category'] ?? ''), 0, 255) ?: null,
            'source' => 'ai',
        ]);
    }

    private function transcript(ChatConversation $conversation): string
    {
        return $conversation->messages()->where('is_internal', false)->with('sender')->orderBy('id')->get()
            ->map(fn ($message) => ucfirst($message->sender_type).': '.$message->body)
            ->implode("\n\n");
    }
}
