<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $htmlBody,
        public string $textBody,
        public string $fromName,
        public string $fromEmail,
        public ?string $replyTo = null,
    ) {
    }

    public function build(): static
    {
        $mailable = $this->from($this->fromEmail, $this->fromName)
            ->subject($this->subjectLine)
            ->html($this->htmlBody)
            ->text('emails.campaign-text', ['body' => $this->textBody]);

        if (filled($this->replyTo)) {
            $mailable->replyTo($this->replyTo);
        }

        return $mailable;
    }
}
