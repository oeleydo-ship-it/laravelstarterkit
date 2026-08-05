<?php

namespace App\Services\Chat;

use App\Events\ChatConversationUpdated;
use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\User;

class ConversationService
{
    public function __construct(
        protected RoutingService $routing,
        protected ChatNotifier $notifier,
    ) {
    }

    public function startForVisitor(Tenant $tenant, ChatVisitor $visitor, bool $forceNew = false): ChatConversation
    {
        if ($forceNew) {
            $visitor->conversations()
                ->where('status', 'open')
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
        } else {
            $conversation = $visitor->conversations()
                ->where('status', 'open')
                ->latest('id')
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        // Route to an agent per the tenant's strategy. A null agent is normal —
        // manual routing, or nobody online — and leaves it in the unassigned queue.
        $agent = $this->routing->pickAgent($tenant);

        $conversation = ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
            'assigned_to' => $agent?->id,
        ]);

        $this->notifier->conversationStarted($conversation);

        return $conversation;
    }

    public function assign(ChatConversation $conversation, ?User $agent): ChatConversation
    {
        $conversation->update(['assigned_to' => $agent?->id]);
        $conversation->setRelation('assignee', $agent);

        $this->notifier->conversationAssigned($conversation, $agent);

        broadcast(new ChatConversationUpdated($conversation->fresh(['assignee'])))->toOthers();

        return $conversation;
    }

    /**
     * Hand a conversation to another agent, leaving an internal note so the
     * thread carries its own audit trail of who passed it on and why.
     */
    public function transfer(
        ChatConversation $conversation,
        User $from,
        User $to,
        ?string $reason = null,
    ): ChatConversation {
        $conversation->update(['assigned_to' => $to->id]);
        $conversation->setRelation('assignee', $to);

        $note = "Transferred from {$from->name} to {$to->name}";

        if (filled($reason)) {
            $note .= " — {$reason}";
        }

        app(MessageService::class)->addInternalNote($conversation, $from, $note);

        $this->notifier->conversationAssigned($conversation, $to, $from);

        broadcast(new ChatConversationUpdated($conversation->fresh(['assignee'])))->toOthers();

        return $conversation;
    }

    /**
     * Record the visitor's satisfaction score. Returns false when the chat has
     * already been rated — the caller decides whether that is a conflict or a
     * no-op, and either way the original score stands.
     */
    public function rate(ChatConversation $conversation, int $rating, ?string $comment = null): bool
    {
        if ($conversation->isRated()) {
            return false;
        }

        $conversation->update([
            'rating' => $rating,
            'rating_comment' => $comment,
            'rated_at' => now(),
        ]);

        // Agents watching the thread see the score arrive without a refresh, and
        // it gives integrations a reason to react to a completed chat.
        broadcast(new ChatConversationUpdated($conversation));

        $this->notifier->conversationRated($conversation);

        return true;
    }

    public function close(ChatConversation $conversation): ChatConversation
    {
        $conversation->update(['status' => 'closed', 'closed_at' => now()]);

        $this->notifier->conversationClosed($conversation);

        broadcast(new ChatConversationUpdated($conversation))->toOthers();

        return $conversation;
    }

    public function reopen(ChatConversation $conversation): ChatConversation
    {
        $conversation->update(['status' => 'open', 'closed_at' => null]);

        broadcast(new ChatConversationUpdated($conversation))->toOthers();

        return $conversation;
    }

    public function delete(ChatConversation $conversation): void
    {
        $conversation->delete();
    }

    public function restore(ChatConversation $conversation): ChatConversation
    {
        $conversation->restore();

        return $conversation;
    }
}
