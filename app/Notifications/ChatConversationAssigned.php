<?php

namespace App\Notifications;

use App\Models\ChatConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatConversationAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ChatConversation $conversation,
        public ?string $assignedBy = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $line = $this->assignedBy
            ? "{$this->assignedBy} assigned a live chat conversation to you."
            : 'A live chat conversation has been assigned to you.';

        return (new MailMessage)
            ->subject('A chat conversation was assigned to you')
            ->greeting("Hi {$notifiable->name},")
            ->line($line)
            ->action('Open the conversation', route('chat.conversations.show', $this->conversation));
    }
}
