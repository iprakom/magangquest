<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuestApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $internName,
        public string $questTitle,
        public int $points = 100
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quest Kamu Disetujui! +' . $this->points . ' XP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quest-approved',
        );
    }
}
