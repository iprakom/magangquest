<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $internName,
        public string $questTitle,
        public ?string $submittedAt = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Intern ' . $this->internName . ' Submit Quest untuk Review',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quest-submitted',
        );
    }
}
