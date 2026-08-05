<?php

namespace App\Services\Chat;

use App\Jobs\SendChatAlert;
use App\Jobs\SendChatWebhook;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Notifications\ChatConversationAssigned;
use App\Notifications\ChatConversationStarted;
use Illuminate\Support\Facades\Notification;

/**
 * The single place chat activity turns into outbound noise — team alerts, agent
 * email and machine webhooks. Callers say what happened; this decides who hears
 * about it and how.
 */
class ChatNotifier
{
    public function __construct(protected IntegrationSettingsService $settings)
    {
    }

    public function conversationStarted(ChatConversation $conversation): void
    {
        $visitor = $this->visitorLabel($conversation);

        SendChatAlert::dispatch(
            $conversation->tenant_id,
            "New chat from {$visitor}",
            route('chat.conversations.show', $conversation),
        );

        SendChatWebhook::dispatch(
            $conversation->tenant_id,
            'conversation.created',
            $this->conversationPayload($conversation),
        );

        $this->mail($conversation, $this->recipientsFor($conversation), new ChatConversationStarted($conversation));
    }

    public function conversationAssigned(ChatConversation $conversation, ?User $agent, ?User $by = null): void
    {
        if (! $agent) {
            return;
        }

        SendChatWebhook::dispatch(
            $conversation->tenant_id,
            'conversation.assigned',
            $this->conversationPayload($conversation),
        );

        $this->mail(
            $conversation,
            collect([$agent]),
            new ChatConversationAssigned($conversation, $by?->name),
        );
    }

    /**
     * A score is the one chat event a team usually wants pushed at them in the
     * moment, so it goes to the alert destinations as well as the webhook.
     */
    public function conversationRated(ChatConversation $conversation): void
    {
        $stars = str_repeat('★', (int) $conversation->rating)
            .str_repeat('☆', ChatConversation::MAX_RATING - (int) $conversation->rating);

        $text = "{$this->visitorLabel($conversation)} rated a chat {$conversation->rating}/"
            .ChatConversation::MAX_RATING." {$stars}";

        if (filled($conversation->rating_comment)) {
            $text .= "\n“{$conversation->rating_comment}”";
        }

        SendChatAlert::dispatch(
            $conversation->tenant_id,
            $text,
            route('chat.conversations.show', $conversation),
        );

        SendChatWebhook::dispatch(
            $conversation->tenant_id,
            'conversation.rated',
            $this->conversationPayload($conversation) + [
                'rating' => $conversation->rating,
                'rating_comment' => $conversation->rating_comment,
            ],
        );
    }

    public function conversationClosed(ChatConversation $conversation): void
    {
        SendChatWebhook::dispatch(
            $conversation->tenant_id,
            'conversation.closed',
            $this->conversationPayload($conversation),
        );
    }

    /**
     * Only visitor messages go out. Echoing the team's own replies back to their
     * own webhook is how integrations end up in loops.
     */
    public function messageSent(ChatMessage $message): void
    {
        if (! $message->isFromVisitor()) {
            return;
        }

        SendChatWebhook::dispatch($message->tenant_id, 'message.created', [
            'id' => $message->id,
            'conversation_id' => $message->chat_conversation_id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Assigned agent if there is one; otherwise everyone who could pick it up.
     * Falls back to owners and admins so a chat arriving with nobody online is
     * still someone's problem.
     */
    protected function recipientsFor(ChatConversation $conversation)
    {
        if ($conversation->assigned_to) {
            return User::withoutGlobalScopes()->where('id', $conversation->assigned_to)->get();
        }

        $online = User::withoutGlobalScopes()
            ->where('tenant_id', $conversation->tenant_id)
            ->availableAgents()
            ->get();

        if ($online->isNotEmpty()) {
            return $online;
        }

        return User::withoutGlobalScopes()
            ->where('tenant_id', $conversation->tenant_id)
            ->where('status', 'active')
            ->whereIn('role', ['owner', 'admin'])
            ->get();
    }

    protected function mail(ChatConversation $conversation, $recipients, $notification): void
    {
        $tenant = $conversation->tenant;

        if (! $tenant || ! $this->settings->for($tenant)['mail_enabled'] || $recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, $notification);
    }

    protected function conversationPayload(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'assigned_to' => $conversation->assignee?->name,
            'visitor' => $this->visitorLabel($conversation),
            'created_at' => $conversation->created_at?->toIso8601String(),
            'closed_at' => $conversation->closed_at?->toIso8601String(),
        ];
    }

    protected function visitorLabel(ChatConversation $conversation): string
    {
        return $conversation->visitor?->name
            ?? $conversation->visitor?->email
            ?? "Visitor #{$conversation->chat_visitor_id}";
    }
}
