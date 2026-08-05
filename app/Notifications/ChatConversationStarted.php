<?php

namespace App\Notifications;

use App\Models\ChatConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatConversationStarted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ChatConversation $conversation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $visitor = $this->conversation->visitor?->name
            ?? $this->conversation->visitor?->email
            ?? 'A visitor';

        return (new MailMessage)
            ->subject('New live chat conversation')
            ->greeting("Hi {$notifiable->name},")
            ->line("{$visitor} just started a chat.")
            ->action('Open the conversation', route('chat.conversations.show', $this->conversation))
            ->line('Reply from the inbox to keep them waiting as little as possible.');
    }
}
