<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlaDeadlineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $questTitle,
        public string $internName,
        public string $hoursRemaining,
        public ?string $slaDeadline = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: SLA Quest Akan Berakhir',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sla-deadline-reminder',
        );
    }
}
