<?php

namespace App\Mail;

use App\Models\BookingAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BookingAppointment $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking confirmed: '.$this->appointment->service?->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-confirmation',
        );
    }
}
