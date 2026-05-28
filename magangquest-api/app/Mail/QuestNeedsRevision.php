<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuestNeedsRevision extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $internName,
        public string $questTitle,
        public string $notes
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quest Dikembalikan untuk Revisi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quest-needs-revision',
        );
    }
}
