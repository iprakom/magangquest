<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuestAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $internName,
        public string $questTitle,
        public ?string $slaDeadline = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quest Baru Assigned ke Kamu: ' . $this->questTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quest-assigned',
        );
    }
}
