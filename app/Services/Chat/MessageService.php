<?php

namespace App\Services\Chat;

use App\Events\ChatInternalNoteAdded;
use App\Events\ChatMessageSent;
use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class MessageService
{
    public function __construct(protected ChatNotifier $notifier)
    {
    }

    public function sendAsAgent(
        ChatConversation $conversation,
        User $agent,
        string $body,
        ?UploadedFile $file = null,
    ): ChatMessage {
        if ((int) $conversation->assigned_to !== (int) $agent->id || $conversation->status !== 'open') {
            abort(403, 'Accept this chat before sending a reply.');
        }

        return $this->send($conversation, 'agent', $agent->id, $body, $file);
    }

    public function sendAsVisitor(
        ChatConversation $conversation,
        string $body,
        ?UploadedFile $file = null,
    ): ChatMessage {
        return $this->send($conversation, 'visitor', null, $body, $file);
    }

    /**
     * Knowledge-base / AI assistant reply. Visible to the visitor like an agent
     * message, but not tied to a human user account.
     */
    public function sendAsBot(ChatConversation $conversation, string $body): ChatMessage
    {
        return $this->send($conversation, 'bot', null, $body);
    }

    /**
     * Add a staff-only note. This intentionally does NOT go through send():
     * it must not emit ChatMessageSent (the visitor is on that channel) and it
     * must not change the conversation preview the visitor-facing list shows.
     */
    public function addInternalNote(ChatConversation $conversation, User $agent, string $body): ChatMessage
    {
        $note = ChatMessage::create([
            'tenant_id' => $conversation->tenant_id,
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'body' => $body,
            'is_internal' => true,
        ]);

        broadcast(new ChatInternalNoteAdded($note))->toOthers();

        return $note;
    }

    protected function send(
        ChatConversation $conversation,
        string $senderType,
        ?int $senderId,
        string $body,
        ?UploadedFile $file = null,
    ): ChatMessage {
        $message = ChatMessage::create([
            'tenant_id' => $conversation->tenant_id,
            'chat_conversation_id' => $conversation->id,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'body' => $body,
        ]);

        // Stored before broadcasting so the realtime payload already carries the
        // file — otherwise the receiving client renders a bodiless message and
        // has to refetch to discover there was an attachment.
        if ($file) {
            $this->storeAttachment($message, $file);
            $message->load('attachment');
        }

        $conversation->update([
            'last_message_at' => $message->created_at,
            'last_message_preview' => str($body)->limit(140),
        ]);

        broadcast(new ChatMessageSent($message))->toOthers();

        $this->notifier->messageSent($message);

        return $message;
    }

    protected function storeAttachment(ChatMessage $message, UploadedFile $file): ChatAttachment
    {
        $disk = config('chat.attachments.disk');

        // Foldered by tenant then conversation so a stray path traversal or a
        // mistaken bulk delete can only ever touch one workspace's files.
        $path = $file->store(
            "chat/{$message->tenant_id}/{$message->chat_conversation_id}",
            $disk
        );

        return ChatAttachment::create([
            'tenant_id' => $message->tenant_id,
            'chat_message_id' => $message->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function markRead(ChatConversation $conversation, string $readerType): int
    {
        // Mark unread messages from the *other* party as read.
        $senderTypeToMark = $readerType === 'agent' ? 'visitor' : 'agent';

        return $conversation->messages()
            ->where('sender_type', $senderTypeToMark)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
